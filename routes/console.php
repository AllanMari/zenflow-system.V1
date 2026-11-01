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

    // Check establishment-wide operating window; skip automatic attendance marking if closed all day
    $bizWindow = \App\Services\BusinessScheduleService::getOperatingWindow($today);
    if (isset($bizWindow['is_open']) && !$bizWindow['is_open']) {
        return;
    }

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

/* ───────────────────────────────────────────
   ZenFlow Pending Appointment Expiration
   ─────────────────────────────────────────── */

Artisan::command('appointments:expire-pending', function () {
    $now = \Carbon\Carbon::now('Asia/Manila');
    $this->info("[{$now->toDateTimeString()}] Checking for overdue pending appointments...");

    $expiredCount = 0;

    \App\Models\Appointment::pendingOverdue()
        ->chunkById(100, function ($appointments) use (&$expiredCount, $now) {
            foreach ($appointments as $appointment) {
                if ($appointment->markAsExpired()) {
                    $expiredCount++;
                    \Illuminate\Support\Facades\Log::info("Pending appointment #{$appointment->id} expired automatically.", [
                        'appointment_id' => $appointment->id,
                        'created_at' => (string) $appointment->created_at,
                        'appointment_date' => (string) $appointment->appointment_date,
                        'start_time' => (string) $appointment->start_time,
                        'expired_at' => $now->toDateTimeString(),
                    ]);
                }
            }
        });

    $this->info("[{$now->toDateTimeString()}] Completed sweep. Expired {$expiredCount} pending appointment(s).");
})->purpose('Expire pending appointments older than 24 hours or whose start time has arrived');

Schedule::command('appointments:expire-pending')
    ->everyMinute()
    ->withoutOverlapping();
