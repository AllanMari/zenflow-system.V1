<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
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
        $query = Appointment::with(['customer', 'services'])
            ->where('user_id', Auth::id());

        if ($request->filled('date')) {
            $query->whereDate(
                'appointment_date',
                $request->date
            );
        }

        $appointments = $query
            ->orderBy('appointment_date', 'desc')
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        return view(
            'staff.appointments',
            compact('appointments')
        );
    }

    /**
     * Display the logged-in staff member's weekly schedule.
     */
    public function mySchedule(Request $request)
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Determine selected week
        |--------------------------------------------------------------------------
        */

        $weekStart = $request->filled('week_start')
            ? Carbon::parse($request->week_start)->startOfWeek()
            : now()->startOfWeek();

        $weekEnd = $weekStart->copy()->endOfWeek();

        /*
        |--------------------------------------------------------------------------
        | Get recurring weekly schedule
        |--------------------------------------------------------------------------
        */

        $schedules = WorkSchedule::where('user_id', $user->id)
            ->get()
            ->keyBy('day_of_week');

        /*
        |--------------------------------------------------------------------------
        | Get exceptions that affect this week
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Build final schedule
        |--------------------------------------------------------------------------
        */

        $weeklySchedule = [];

        $totalHours = 0;
        $daysWorking = 0;

        for ($date = $weekStart->copy(); $date <= $weekEnd; $date->addDay()) {

            $dateString = $date->toDateString();

            /*
            |--------------------------------------------------------------------------
            | Laravel dayOfWeek:
            | Sunday = 0
            | Monday = 1
            | ...
            | Saturday = 6
            |--------------------------------------------------------------------------
            */

            $dayOfWeek = $date->dayOfWeek;

            $schedule = $schedules->get($dayOfWeek);
            $exception = $weekExceptions->get($dateString);

            /*
            |--------------------------------------------------------------------------
            | Exception takes priority
            |--------------------------------------------------------------------------
            */

            if ($exception) {

                $type = $exception->type;

                /*
                | Custom working hours
                */

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

                /*
                | Full-day exception
                | Example:
                | holiday
                | sick_leave
                | urgent_leave
                | day_off
                */

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

            /*
            |--------------------------------------------------------------------------
            | Normal recurring schedule
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | Day off
            |--------------------------------------------------------------------------
            */

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

        /*
        |--------------------------------------------------------------------------
        | Future exceptions
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Optional template information
        |--------------------------------------------------------------------------
        */

        $templateHint = null;

        if ($schedules->isNotEmpty()) {
            $templateHint = ShiftTemplate::where(
                'is_active',
                true
            )->first()?->name;
        }

        /*
        |--------------------------------------------------------------------------
        | Week navigation
        |--------------------------------------------------------------------------
        */

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
            'templateHint'
        ));
    }

    /**
     * Calculate shift duration in hours.
     */
    private function calculateHours(
        $startTime,
        $endTime
    ): float {
        if (!$startTime || !$endTime) {
            return 0;
        }

        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        /*
        | Handle overnight shifts.
        */

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return round(
            $start->diffInMinutes($end) / 60,
            1
        );
    }
}