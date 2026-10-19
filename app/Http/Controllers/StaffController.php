<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\ScheduleException;
use App\Models\ShiftTemplate;
use App\Models\WorkSchedule;
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
        $myToday = Appointment::with(['customer', 'services', 'room'])
            ->where('user_id', $userId)
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['confirmed', 'completed', 'pending', 'cancelled'])
            ->orderBy('start_time')
            ->get();

        // 2. Upcoming Appointments for authenticated staff member (next 5 eligible future appointments)
        $myUpcoming = Appointment::with(['customer', 'services', 'room'])
            ->where('user_id', $userId)
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
        $weeklyCompleted = Appointment::where('user_id', $userId)
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
            'todayAttendance'
        ));
    }

    public function myAppointments(Request $request)
    {
        $userId = Auth::id();

        $status = $request->input('status', 'all');

        $query = Appointment::with(['customer', 'services'])
            ->where('user_id', $userId);

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
            ->where('user_id', $userId);

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
}