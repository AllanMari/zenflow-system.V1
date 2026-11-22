<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\ScheduleException;
use App\Models\ShiftTemplate;
use App\Models\WorkSchedule;
use App\Services\CsvExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $today = Carbon::today('Asia/Manila');
        $currentTime = Carbon::now('Asia/Manila')->format('H:i:s');

        // 1. Today's Appointments for authenticated staff member (with customer, services, room)
        $myToday = Appointment::with(['customer', 'services', 'room', 'assignedStaff', 'packageSelections'])
            ->forStaff($userId)
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['confirmed', 'completed', 'pending', 'cancelled'])
            ->orderBy('start_time')
            ->get();

        // 2. Upcoming Appointments for authenticated staff member (next 5 eligible future appointments)
        $myUpcoming = Appointment::with(['customer', 'services', 'room', 'assignedStaff', 'packageSelections'])
            ->forStaff($userId)
            ->whereDate('appointment_date', '>', $today)
            ->whereIn('status', ['confirmed', 'pending'])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        // 3. Operational KPI Counts
        // Today's assigned non-cancelled bookings
        $todayAssignedCount = $myToday->whereIn('status', ['confirmed', 'completed', 'pending'])->count();
        // Completed today
        $todayCompletedCount = $myToday->where('status', 'completed')->count();
        // Remaining confirmed sessions today that are not completed or cancelled and have not yet ended
        $remainingConfirmedCount = $myToday->where('status', 'confirmed')
            ->where('end_time', '>', $currentTime)
            ->count();

        // Weekly completed sessions (Mon-Sun in Asia/Manila)
        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $today->copy()->endOfWeek();
        $weeklyCompleted = Appointment::forStaff($userId)
            ->whereBetween('appointment_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->where('status', 'completed')
            ->count();

        // 4. Next/Current Session Highlight (earliest confirmed session today that hasn't ended yet)
        $nextSession = $myToday->first(function ($appt) use ($currentTime) {
            return $appt->status === 'confirmed' && $appt->end_time > $currentTime;
        });

        // 5. Today's Shift Schedule Resolution
        $dayOfWeek = $today->dayOfWeek; // 0=Sunday, 6=Saturday
        $schedule = WorkSchedule::where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        $exception = ScheduleException::where('user_id', $userId)
            ->whereDate('exception_date', $today)
            ->first();

        $shiftInfo = [
            'is_working' => false,
            'label' => 'No scheduled shift today',
            'start_time' => null,
            'end_time' => null,
            'type' => 'off',
        ];

        if ($exception) {
            if ($exception->type === 'custom_hours') {
                $shiftInfo = [
                    'is_working' => true,
                    'label' => Carbon::parse($exception->start_time)->format('g:i A') . ' – ' . Carbon::parse($exception->end_time)->format('g:i A'),
                    'start_time' => $exception->start_time,
                    'end_time' => $exception->end_time,
                    'type' => 'custom_hours',
                ];
            } else {
                $typeLabel = match ($exception->type) {
                    'sick_leave' => 'On Sick Leave',
                    'urgent_leave' => 'On Urgent Leave',
                    'holiday' => 'Holiday',
                    'day_off' => 'Day Off',
                    default => ucfirst(str_replace('_', ' ', $exception->type)),
                };
                $shiftInfo = [
                    'is_working' => false,
                    'label' => $typeLabel,
                    'start_time' => null,
                    'end_time' => null,
                    'type' => $exception->type,
                ];
            }
        } elseif ($schedule && !$schedule->is_day_off && $schedule->start_time && $schedule->end_time) {
            $shiftInfo = [
                'is_working' => true,
                'label' => Carbon::parse($schedule->start_time)->format('g:i A') . ' – ' . Carbon::parse($schedule->end_time)->format('g:i A'),
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'type' => 'regular',
            ];
        }

        // 6. Today's Attendance Record for Authenticated Staff
        $todayAttendance = Attendance::where('user_id', $userId)
            ->whereDate('date', $today)
            ->first();

        // 7. Commission metrics for authenticated therapist (Current Month & Today)
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $monthAppointments = Appointment::with(['services', 'assignedStaff', 'staff', 'payments'])
            ->forStaff($userId)
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $monthStart->toDateString())
            ->whereDate('appointment_date', '<=', $monthEnd->toDateString())
            ->get();

        $monthCommissionCents = 0;
        $todayCommissionCents = 0;
        $todayDateStr = $today->toDateString();

        foreach ($monthAppointments as $appt) {
            $row = $this->calculateTherapistCommissionRow($appt, $userId);
            if ($row) {
                $monthCommissionCents += $row['my_commission_cents'];
                if ($row['raw_date'] === $todayDateStr) {
                    $todayCommissionCents += $row['my_commission_cents'];
                }
            }
        }
        $monthCommission = $monthCommissionCents / 100;
        $todayCommission = $todayCommissionCents / 100;

        return view('staff-dashboard', compact(
            'myToday',
            'myUpcoming',
            'todayAssignedCount',
            'todayCompletedCount',
            'remainingConfirmedCount',
            'weeklyCompleted',
            'weekStart',
            'weekEnd',
            'nextSession',
            'shiftInfo',
            'todayAttendance',
            'monthCommission',
            'todayCommission'
        ));
    }

    public function myAppointments(Request $request)
    {
        $userId = Auth::id();

        $status = $request->input('status', 'all');

        $query = Appointment::with(['customer', 'services', 'assignedStaff', 'packageSelections'])
            ->forStaff($userId);

        // Date filter handling: single date takes precedence, then valid date range (date_from & date_to)
        $hasSingleDate = false;
        $hasDateRange = false;
        $dateFromStr = null;
        $dateToStr = null;

        if ($request->filled('date')) {
            try {
                $parsedDate = Carbon::parse($request->date)->toDateString();
                $query->whereDate('appointment_date', $parsedDate);
                $hasSingleDate = true;
            } catch (\Exception $e) {
                // Invalid single date ignored safely
            }
        } elseif ($request->filled('date_from') && $request->filled('date_to')) {
            try {
                $dFrom = Carbon::parse($request->date_from)->toDateString();
                $dTo = Carbon::parse($request->date_to)->toDateString();
                if ($dFrom <= $dTo) {
                    $query->whereDate('appointment_date', '>=', $dFrom)
                          ->whereDate('appointment_date', '<=', $dTo);
                    $hasDateRange = true;
                    $dateFromStr = $dFrom;
                    $dateToStr = $dTo;
                }
            } catch (\Exception $e) {
                // Invalid date range ignored safely
            }
        }

        switch ($status) {
            case 'confirmed':
                $query->where('status', 'confirmed');
                break;

            case 'pending':
                $query->where('status', 'pending');
                break;

            case 'completed':
                $query->where('status', 'completed');
                break;

            case 'cancelled':
                $query->where('status', 'cancelled')
                    ->where(function ($q) {
                        $q->whereNull('cancellation_reason')
                            ->orWhere('cancellation_reason', '!=', 'customer_no_show');
                    });
                break;

            case 'no_show':
                $query->where(function ($q) {
                    $q->where('status', 'no_show')
                      ->orWhere(function ($sq) {
                          $sq->where('status', 'cancelled')
                             ->where('cancellation_reason', 'customer_no_show');
                      });
                });
                break;
        }

        $appointments = $query
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        $countBase = Appointment::query()
            ->forStaff($userId);

        if ($hasSingleDate) {
            $countBase->whereDate('appointment_date', $parsedDate);
        } elseif ($hasDateRange) {
            $countBase->whereDate('appointment_date', '>=', $dateFromStr)
                      ->whereDate('appointment_date', '<=', $dateToStr);
        }

        $counts = [
            'all' => (clone $countBase)->count(),

            'confirmed' => (clone $countBase)
                ->where('status', 'confirmed')
                ->count(),

            'pending' => (clone $countBase)
                ->where('status', 'pending')
                ->count(),

            'completed' => (clone $countBase)
                ->where('status', 'completed')
                ->count(),

            'cancelled' => (clone $countBase)
                ->where('status', 'cancelled')
                ->where(function ($q) {
                    $q->whereNull('cancellation_reason')
                      ->orWhere('cancellation_reason', '!=', 'customer_no_show');
                })
                ->count(),

            'no_show' => (clone $countBase)
                ->where(function ($q) {
                    $q->where('status', 'no_show')
                      ->orWhere(function ($sq) {
                          $sq->where('status', 'cancelled')
                             ->where('cancellation_reason', 'customer_no_show');
                      });
                })
                ->count(),
        ];

        return view(
            'staff.appointments',
            compact(
                'appointments',
                'counts',
                'status'
            )
        );
    }

    public function mySchedule(Request $request)
    {
        $user = Auth::user();

        $weekStart = $request->filled('week_start')
            ? Carbon::parse($request->week_start)->startOfWeek()
            : now()->startOfWeek();

        $weekEnd = $weekStart->copy()->endOfWeek();

        $schedules = WorkSchedule::where('user_id', $user->id)
            ->get()
            ->keyBy('day_of_week');

        $weekExceptions = ScheduleException::where(
                'user_id',
                $user->id
            )
            ->whereBetween('exception_date', [
                $weekStart->toDateString(),
                $weekEnd->toDateString()
            ])
            ->get()
            ->keyBy(function ($exception) {
                return Carbon::parse(
                    $exception->exception_date
                )->toDateString();
            });

        $weeklySchedule = [];

        $totalHours = 0;
        $daysWorking = 0;

        for ($date = $weekStart->copy(); $date <= $weekEnd; $date->addDay()) {

            $dateString = $date->toDateString();
            $dayOfWeek = $date->dayOfWeek;

            $schedule = $schedules->get($dayOfWeek);
            $exception = $weekExceptions->get($dateString);

            if ($exception) {

                $type = $exception->type;

                if ($type === 'custom_hours') {

                    $startTime = $exception->start_time;
                    $endTime = $exception->end_time;

                    $hours = $this->calculateHours(
                        $startTime,
                        $endTime
                    );

                    $totalHours += $hours;
                    $daysWorking++;

                    $weeklySchedule[] = [
                        'date' => $dateString,
                        'day_name' => $date->format('l'),
                        'type' => 'exception',
                        'exception_type' => 'custom_hours',
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'reason' => $exception->reason ?? null,
                        'attendance' => null,
                    ];

                    continue;
                }

                $weeklySchedule[] = [
                    'date' => $dateString,
                    'day_name' => $date->format('l'),
                    'type' => 'exception',
                    'exception_type' => $type,
                    'start_time' => null,
                    'end_time' => null,
                    'reason' => $exception->reason ?? null,
                    'attendance' => null,
                ];

                continue;
            }

            if (
                $schedule &&
                !$schedule->is_day_off &&
                $schedule->start_time &&
                $schedule->end_time
            ) {

                $hours = $this->calculateHours(
                    $schedule->start_time,
                    $schedule->end_time
                );

                $totalHours += $hours;
                $daysWorking++;

                $weeklySchedule[] = [
                    'date' => $dateString,
                    'day_name' => $date->format('l'),
                    'type' => 'work',
                    'exception_type' => null,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'reason' => null,
                    'attendance' => null,
                ];

                continue;
            }

            $weeklySchedule[] = [
                'date' => $dateString,
                'day_name' => $date->format('l'),
                'type' => 'off',
                'exception_type' => null,
                'start_time' => null,
                'end_time' => null,
                'reason' => null,
                'attendance' => null,
            ];
        }

        $weekAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [
                $weekStart->toDateString(),
                $weekEnd->toDateString(),
            ])
            ->get()
            ->keyBy(fn ($a) =>
                Carbon::parse($a->date)->toDateString()
            );

        $totalOvertime = 0;
        $totalWorkedHours = 0;

        foreach ($weeklySchedule as &$day) {

            $att = $weekAttendances->get($day['date']);

            $day['check_in'] = $att?->check_in;
            $day['check_out'] = $att?->check_out;
            $day['attendance'] = $att?->status;
            $day['worked_hours'] = 0;
            $day['overtime_hours'] = 0;

            if ($att && $att->check_in && $att->check_out) {

                $clockIn = Carbon::parse($att->check_in);
                $clockOut = Carbon::parse($att->check_out);

                if ($clockOut->lessThanOrEqualTo($clockIn)) {
                    $clockOut->addDay();
                }

                $workedMinutes = $clockIn->diffInMinutes($clockOut);
                $workedHours = round($workedMinutes / 60, 2);

                $day['worked_hours'] = $workedHours;
                $totalWorkedHours += $workedHours;

                $scheduledHours = 0;

                if ($day['start_time'] && $day['end_time']) {
                    $scheduledHours = $this->calculateHours(
                        $day['start_time'],
                        $day['end_time']
                    );
                }

                $ot = round(
                    max(0, $workedHours - $scheduledHours),
                    2
                );

                $day['overtime_hours'] = $ot;
                $totalOvertime += $ot;
            }
        }

        unset($day);

        $exceptions = ScheduleException::where(
                'user_id',
                $user->id
            )
            ->whereDate(
                'exception_date',
                '>=',
                today()
            )
            ->orderBy('exception_date')
            ->get();

        $prevWeek = $weekStart
            ->copy()
            ->subWeek()
            ->toDateString();

        $nextWeek = $weekStart
            ->copy()
            ->addWeek()
            ->toDateString();

        $weekLabel = $weekStart->format('M j')
            . ' – '
            . $weekEnd->format('M j, Y');

        return view('staff.schedule', compact(
            'user',
            'schedules',
            'exceptions',
            'weeklySchedule',
            'weekStart',
            'weekEnd',
            'weekLabel',
            'prevWeek',
            'nextWeek',
            'totalHours',
            'daysWorking',
            'totalOvertime',
            'totalWorkedHours'
        ));
    }

    private function calculateHours(
        $startTime,
        $endTime
    ): float {
        if (!$startTime || !$endTime) {
            return 0;
        }

        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return round(
            $start->diffInMinutes($end) / 60,
            1
        );
    }

    /**
     * Retrieve treatment notes for an assigned appointment.
     * Authorized only for the primary therapist or assisting therapists.
     */
    public function getTreatmentNotes(Appointment $appointment)
    {
        $user = Auth::user();

        if (!$appointment->canViewTreatmentNotes($user)) {
            abort(403, 'Unauthorized access to treatment notes.');
        }

        return response()->json([
            'success' => true,
            'appointment_id' => $appointment->id,
            'treatment_notes' => $appointment->treatment_notes,
        ]);
    }

    /**
     * Update treatment notes for an assigned appointment.
     * Authorized only for the primary therapist or assisting therapists.
     */
    public function updateTreatmentNotes(Request $request, Appointment $appointment)
    {
        $user = Auth::user();

        if (!$appointment->canViewTreatmentNotes($user)) {
            abort(403, 'Unauthorized access to treatment notes.');
        }

        $request->validate([
            'treatment_notes' => 'nullable|string|max:2000',
        ]);

        $appointment->update([
            'treatment_notes' => $request->treatment_notes ?: null,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Treatment notes updated successfully.',
                'treatment_notes' => $appointment->treatment_notes,
            ]);
        }

        return back()->with('success', 'Treatment notes updated successfully.');
    }

    /**
     * Display therapist commissions with date range, daily/monthly/yearly presets,
     * and equal multi-therapist 30% pool splitting. Strictly isolated to Auth::id().
     */
    public function myCommissions(Request $request)
    {
        $userId = Auth::id();
        $dateFilter = $this->resolveCommissionDateFilter($request);
        $startDate = $dateFilter['startDate'];
        $endDate = $dateFilter['endDate'];
        $rangePreset = $dateFilter['rangePreset'];

        $appointments = Appointment::with([
            'customer',
            'services',
            'room',
            'assignedStaff',
            'staff',
            'payments',
            'packageSelections',
        ])
            ->forStaff($userId)
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $startDate->toDateString())
            ->whereDate('appointment_date', '<=', $endDate->toDateString())
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        $rows = [];
        $totalCommissionCents = 0;
        $totalNetCents = 0;
        $soloCount = 0;
        $multiCount = 0;

        foreach ($appointments as $appt) {
            $row = $this->calculateTherapistCommissionRow($appt, $userId);
            if ($row) {
                $rows[] = $row;
                $totalCommissionCents += $row['my_commission_cents'];
                $totalNetCents += $row['net_cents'];
                if ($row['therapists_count'] > 1) {
                    $multiCount++;
                } else {
                    $soloCount++;
                }
            }
        }

        $summary = [
            'total_commission' => $totalCommissionCents / 100,
            'total_net' => $totalNetCents / 100,
            'completed_count' => count($rows),
            'solo_count' => $soloCount,
            'multi_count' => $multiCount,
            'avg_commission' => count($rows) > 0 ? round(($totalCommissionCents / 100) / count($rows), 2) : 0,
        ];

        $perPage = 15;
        $currentPage = (int) $request->input('page', 1);
        $pagedRows = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($rows, ($currentPage - 1) * $perPage, $perPage),
            count($rows),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('staff.commissions', [
            'rows' => $pagedRows,
            'allRows' => $rows,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rangePreset' => $rangePreset,
            'dateFrom' => $startDate->toDateString(),
            'dateTo' => $endDate->toDateString(),
        ]);
    }

    /**
     * Export staff's own commission statement to a clean CSV spreadsheet.
     */
    public function exportCommissionCsv(Request $request, CsvExportService $csvService)
    {
        $user = Auth::user();
        $dateFilter = $this->resolveCommissionDateFilter($request);
        $startDate = $dateFilter['startDate'];
        $endDate = $dateFilter['endDate'];

        $appointments = Appointment::with([
            'customer',
            'services',
            'room',
            'assignedStaff',
            'staff',
            'payments',
            'packageSelections',
        ])
            ->forStaff($user->id)
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $startDate->toDateString())
            ->whereDate('appointment_date', '<=', $endDate->toDateString())
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        $rows = [];
        $totalCommission = 0;
        $totalNet = 0;

        foreach ($appointments as $appt) {
            $row = $this->calculateTherapistCommissionRow($appt, $user->id);
            if ($row) {
                $totalCommission += $row['my_commission'];
                $totalNet += $row['net_amount'];

                $rows[] = [
                    '#' . ($row['ref'] ?? $appt->id),
                    $row['date'],
                    $row['time'],
                    $row['service_name'],
                    $row['client_name'],
                    $row['room_name'],
                    $row['assignment_type'],
                    number_format($row['net_amount'], 2, '.', ''),
                    number_format($row['total_pool_commission'], 2, '.', ''),
                    $row['share_percent'] . '%',
                    number_format($row['my_commission'], 2, '.', ''),
                ];
            }
        }

        $meta = [
            ['ZENFLOW SPA', 'THERAPIST COMMISSION STATEMENT'],
            ['STAFF MEMBER', $user->full_name ?? $user->name],
            ['PERIOD', $startDate->format('M j, Y') . ' — ' . $endDate->format('M j, Y')],
            ['TOTAL SESSIONS', count($rows)],
            ['TOTAL NET REVENUE', 'PHP ' . number_format($totalNet, 2)],
            ['TOTAL COMMISSION (30% SHARE)', 'PHP ' . number_format($totalCommission, 2)],
            ['GENERATED AT', now('Asia/Manila')->format('F j, Y g:i A')],
        ];

        $headers = [
            'Reference #',
            'Date',
            'Time',
            'Service / Package',
            'Client',
            'Room',
            'Team / Assignment',
            'Net Amount (PHP)',
            'Total 30% Pool (PHP)',
            'Your Share %',
            'Your Commission (PHP)',
        ];

        $filename = 'commissions-' . strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $user->username ?: 'staff')) . '-' . now()->format('Ymd') . '.csv';

        return $csvService->stream($filename, $headers, $rows, $meta);
    }

    /**
     * Resolve date boundaries based on Flatpickr date_from/date_to or quick preset.
     */
    private function resolveCommissionDateFilter(Request $request): array
    {
        $today = Carbon::today('Asia/Manila');
        $rangePreset = $request->input('range', 'this_month');

        if ($request->filled('date_from') && $request->filled('date_to')) {
            try {
                $start = Carbon::parse($request->input('date_from'), 'Asia/Manila')->startOfDay();
                $end = Carbon::parse($request->input('date_to'), 'Asia/Manila')->endOfDay();
                if ($start->lte($end)) {
                    return [
                        'startDate' => $start,
                        'endDate' => $end,
                        'rangePreset' => 'custom',
                    ];
                }
            } catch (\Throwable $e) {
                // fallback to preset
            }
        }

        return match ($rangePreset) {
            'today', 'daily' => [
                'startDate' => $today->copy()->startOfDay(),
                'endDate' => $today->copy()->endOfDay(),
                'rangePreset' => 'today',
            ],
            'this_week', 'weekly' => [
                'startDate' => $today->copy()->startOfWeek(),
                'endDate' => $today->copy()->endOfWeek(),
                'rangePreset' => 'this_week',
            ],
            'this_year', 'yearly' => [
                'startDate' => $today->copy()->startOfYear(),
                'endDate' => $today->copy()->endOfYear(),
                'rangePreset' => 'this_year',
            ],
            default => [ // this_month (Default)
                'startDate' => $today->copy()->startOfMonth(),
                'endDate' => $today->copy()->endOfMonth(),
                'rangePreset' => 'this_month',
            ],
        };
    }

    /**
     * Compute single commission record for a completed appointment and therapist.
     */
    private function calculateTherapistCommissionRow(Appointment $appt, int $userId): ?array
    {
        // 1. Gather therapists assigned to this session
        $therapists = collect();
        if ($appt->relationLoaded('assignedStaff') && $appt->assignedStaff->isNotEmpty()) {
            $therapists = $appt->assignedStaff;
        } elseif ($appt->staff) {
            $therapists = collect([$appt->staff]);
        }
        if ($appt->staff && !$therapists->contains('id', $appt->staff->id)) {
            $therapists = collect([$appt->staff])->merge($therapists);
        }

        if ($therapists->isEmpty() || !$therapists->contains('id', $userId)) {
            return null;
        }

        $count = $therapists->count();

        // 2. Resolve net amount collected
        $collectedPayments = (float) $appt->payments
            ->whereIn('type', ['deposit', 'full', 'completion', 'additional'])
            ->where('amount', '>', 0)
            ->sum('amount');

        $net = $collectedPayments > 0 ? $collectedPayments : (float) ($appt->total_price ?? 0);
        $netCents = (int) round($net * 100);

        // 3. 30% Total Commission Pool calculation
        $totalComCents = (int) round($net * 0.30 * 100);
        $baseComCents = intdiv($totalComCents, $count);
        $remComCents = $totalComCents % $count;

        // Sort by ID for deterministic allocation of odd penny
        $sortedTherapists = $therapists->sortBy('id')->values();
        $myIndex = $sortedTherapists->search(fn ($t) => $t->id == $userId);
        if ($myIndex === false) {
            return null;
        }

        $myCommissionCents = $baseComCents + ($myIndex < $remComCents ? 1 : 0);
        $myCommission = $myCommissionCents / 100;
        $totalPoolCommission = $totalComCents / 100;

        // 4. Formatted teammates string
        $otherTherapists = $therapists->reject(fn ($t) => $t->id == $userId);
        $assignmentType = $count === 1
            ? 'Solo'
            : 'Shared (' . $count . ' therapists)';

        $otherNames = $otherTherapists->map(fn ($t) => $t->full_name ?? trim($t->first_name . ' ' . $t->last_name))->filter()->implode(', ');
        $teamDescription = $count === 1
            ? 'Solo Session'
            : 'Shared with ' . ($otherNames ?: 'Colleague');

        // Services string
        $serviceName = $appt->services->pluck('name')->implode(', ') ?: 'Spa Session';

        return [
            'id' => $appt->id,
            'ref' => $appt->reference_number ?? $appt->id,
            'date' => $appt->appointment_date ? Carbon::parse($appt->appointment_date)->format('M j, Y') : 'N/A',
            'raw_date' => $appt->appointment_date ? Carbon::parse($appt->appointment_date)->toDateString() : '',
            'time' => $appt->start_time ? Carbon::parse($appt->start_time)->format('g:i A') : 'N/A',
            'service_name' => $serviceName,
            'client_name' => $appt->client_name ?: 'Walk-in Guest',
            'room_name' => $appt->room->name ?? 'Unassigned',
            'therapists_count' => $count,
            'assignment_type' => $assignmentType,
            'team_description' => $teamDescription,
            'net_cents' => $netCents,
            'net_amount' => $net,
            'total_pool_commission' => $totalPoolCommission,
            'share_percent' => round(100 / $count, 1),
            'my_commission_cents' => $myCommissionCents,
            'my_commission' => $myCommission,
        ];
    }
}