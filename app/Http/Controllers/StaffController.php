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
        $today = today();

        $myToday = Appointment::with(['customer', 'services'])
            ->where('user_id', $userId)
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['confirmed', 'pending', 'completed'])
            ->orderBy('start_time')
            ->get();

        $myUpcoming = Appointment::with(['customer', 'services'])
            ->where('user_id', $userId)
            ->whereDate('appointment_date', '>', $today)
            ->whereIn('status', ['confirmed', 'pending'])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        $todayRevenue = Appointment::where('user_id', $userId)
            ->whereDate('appointment_date', $today)
            ->where('status', 'completed')
            ->with('payments')
            ->get()
            ->sum(fn ($appointment) =>
                $appointment->payments->sum('amount')
            );

        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $today->copy()->endOfWeek();

        $weeklyCompleted = Appointment::where('user_id', $userId)
            ->whereBetween('appointment_date', [
                $weekStart,
                $weekEnd
            ])
            ->where('status', 'completed')
            ->count();

        $weeklyRevenue = Appointment::where('user_id', $userId)
            ->whereBetween('appointment_date', [
                $weekStart,
                $weekEnd
            ])
            ->where('status', 'completed')
            ->with('payments')
            ->get()
            ->sum(fn ($appointment) =>
                $appointment->payments->sum('amount')
            );

        return view('staff-dashboard', compact(
            'myToday',
            'myUpcoming',
            'todayRevenue',
            'weeklyCompleted',
            'weeklyRevenue'
        ));
    }

    public function myAppointments(Request $request)
    {
        $userId = Auth::id();

        $status = $request->input('status', 'all');

        $query = Appointment::with(['customer', 'services'])
            ->where('user_id', $userId);

        if ($request->filled('date')) {
            $query->whereDate(
                'appointment_date',
                $request->date
            );
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
                $query->where('status', 'cancelled')
                    ->where('cancellation_reason', 'customer_no_show');
                break;
        }

        $appointments = $query
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        $countBase = Appointment::query()
            ->where('user_id', $userId);

        if ($request->filled('date')) {
            $countBase->whereDate(
                'appointment_date',
                $request->date
            );
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
                ->where('status', 'cancelled')
                ->where('cancellation_reason', 'customer_no_show')
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

        $templateHint = null;

        if ($schedules->isNotEmpty()) {
            $templateHint = ShiftTemplate::where(
                'is_active',
                true
            )->first()?->name;
        }

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
            'totalWorkedHours',
            'templateHint'
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