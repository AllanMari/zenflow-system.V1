<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ScheduleException;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Notifications\AbsenceStreakAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AbsenceMonitor
{
    /**
     * Minimum consecutive absences before alerting
     */
    private const STREAK_THRESHOLD = 3;

    /**
     * How many days back to check (safety limit)
     */
    private const MAX_LOOKBACK_DAYS = 30;

    /**
     * Check a staff member for absence streak and notify if needed.
     * Called by AttendanceObserver after any attendance save.
     */
    public static function check(int $staffId): void
    {
        $staff = User::find($staffId);
        if (!$staff || !$staff->isStaff()) {
            return;
        }

        $streak = self::calculateStreak($staffId);

        if ($streak['count'] >= self::STREAK_THRESHOLD) {
            self::notifyAdmins($staff, $streak);
            self::notifyStaff($staff, $streak);
        }
    }

    /**
     * Calculate consecutive absence streak, skipping non-working days and approved leaves.
     */
    public static function calculateStreak(int $staffId): array
    {
        $today = Carbon::today();
        $streakDates = [];
        $currentDate = $today->copy();
        $lookbackLimit = $today->copy()->subDays(self::MAX_LOOKBACK_DAYS);

        while ($currentDate->gte($lookbackLimit)) {
            $dateStr = $currentDate->toDateString();
            $dow = $currentDate->dayOfWeek;

            // SKIP 1: Check if staff is scheduled to work this day
            $schedule = WorkSchedule::where('user_id', $staffId)
                ->where('day_of_week', $dow)
                ->first();

            // No schedule OR day off = not a working day, skip it (don't break streak, just skip)
            if (!$schedule || $schedule->is_day_off) {
                $currentDate->subDay();
                continue;
            }

            // SKIP 2: Check for approved exception (holiday, sick leave, etc.)
            $exception = ScheduleException::where('user_id', $staffId)
                ->whereDate('exception_date', $dateStr)
                ->whereIn('type', ['day_off', 'holiday', 'sick_leave', 'urgent_leave'])
                ->first();

            if ($exception) {
                // Approved leave = not an unauthorized absence, skip it
                $currentDate->subDay();
                continue;
            }

            // This IS a working day — check attendance
            $attendance = Attendance::where('user_id', $staffId)
                ->whereDate('date', $dateStr)
                ->first();

            if ($attendance && $attendance->status === 'absent') {
                $streakDates[] = $dateStr;
                $currentDate->subDay();
            } else {
                // Found a present/late/on_leave day OR no record (treat as not absent)
                // Streak broken
                break;
            }
        }

        return [
            'count' => count($streakDates),
            'dates' => array_reverse($streakDates), // Oldest first
        ];
    }

    /**
     * Notify all admins and authorized receptionists
     */
    private static function notifyAdmins(User $staff, array $streak): void
    {
        $notification = new AbsenceStreakAlert(
            staffId: $staff->id,
            staffName: $staff->full_name,
            streakDays: $streak['count'],
            absentDates: $streak['dates'],
            isForStaff: false
        );

        // All admins
        $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get();
        foreach ($admins as $admin) {
            $admin->notify($notification);
        }

        // Receptionists with can_mark_attendance = true
        $receptionists = User::whereHas('roles', fn($q) => $q->where('name', 'receptionist'))
            ->where('can_mark_attendance', true)
            ->get();

        foreach ($receptionists as $rec) {
            $rec->notify($notification);
        }
    }

    /**
     * Notify the staff member themselves (warning)
     */
    private static function notifyStaff(User $staff, array $streak): void
    {
        $notification = new AbsenceStreakAlert(
            staffId: $staff->id,
            staffName: $staff->full_name,
            streakDays: $streak['count'],
            absentDates: $streak['dates'],
            isForStaff: true
        );

        $staff->notify($notification);
    }

    /**
     * Get unread absence alerts for a user (for UI)
     */
    public static function unreadFor(User $user): array
    {
        return $user->unreadNotifications()
            ->where('type', AbsenceStreakAlert::class)
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'severity' => $n->data['severity'] ?? 'info',
                'time' => $n->created_at->diffForHumans(),
                'action_url' => $n->data['action_url'] ?? null,
                'action_text' => $n->data['action_text'] ?? null,
            ])
            ->toArray();
    }

    /**
     * Mark all absence notifications as read for a user
     */
    public static function markAllRead(User $user): void
    {
        $user->unreadNotifications()
            ->where('type', AbsenceStreakAlert::class)
            ->update(['read_at' => now()]);
    }
}