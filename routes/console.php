<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Console\Scheduling\Schedule as ScheduleInstance;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/* ───────────────────────────────────────────
   ZenFlow Attendance Automation
   ─────────────────────────────────────────── */

Schedule::call(function () {
    $today = \Carbon\Carbon::today();
    $now = \Carbon\Carbon::now();
    $dayOfWeek = $today->dayOfWeek;

    $staffIds = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
        ->where('is_active', true)
        ->pluck('id');

    foreach ($staffIds as $staffId) {
        // Determine effective schedule for today
        $exception = \App\Models\ScheduleException::where('user_id', $staffId)
            ->whereDate('exception_date', $today)
            ->first();

        $startTime = null;
        $endTime = null;

        if ($exception) {
            if (in_array($exception->type, ['day_off', 'holiday', 'sick_leave', 'urgent_leave'])) {
                continue;
            }
            if ($exception->type === 'custom_hours' && $exception->start_time && $exception->end_time) {
                $startTime = \Carbon\Carbon::parse($exception->start_time);
                $endTime = \Carbon\Carbon::parse($exception->end_time);
            }
        }

        if (!$startTime || !$endTime) {
            $schedule = \App\Models\WorkSchedule::where('user_id', $staffId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_day_off', false)
                ->first();
            if (!$schedule || !$schedule->start_time || !$schedule->end_time) {
                continue;
            }
            $startTime = \Carbon\Carbon::parse($schedule->start_time);
            $endTime = \Carbon\Carbon::parse($schedule->end_time);
        }

        $attendance = \App\Models\Attendance::where('user_id', $staffId)
            ->whereDate('date', $today)
            ->first();

        if ($attendance && $attendance->status === 'present') {
            continue;
        }

        // End of shift → absent
        if ($now->greaterThan($endTime)) {
            if (!$attendance || !$attendance->check_in) {
                \App\Models\Attendance::updateOrCreate(
                    ['user_id' => $staffId, 'date' => $today],
                    [
                        'status' => 'absent',
                        'marked_by' => null,
                        'notes' => 'Auto-marked absent: no clock-in by end of shift (' . $endTime->format('H:i') . ')',
                    ]
                );
            }
            continue;
        }

        // Grace period passed → late
        $gracePeriodEnd = $startTime->copy()->addMinutes(15);
        if ($now->greaterThan($gracePeriodEnd)) {
            if (!$attendance) {
                \App\Models\Attendance::updateOrCreate(
                    ['user_id' => $staffId, 'date' => $today],
                    [
                        'status' => 'late',
                        'marked_by' => null,
                        'notes' => 'Auto-marked late: not checked in within 15 min of start (' . $startTime->format('H:i') . ')',
                    ]
                );
            }
        }
    }
})->everyFiveMinutes()->name('attendance:sync')->withoutOverlapping();
