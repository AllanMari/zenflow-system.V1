<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AbsenceStreakAlert extends Notification
{
    use Queueable;

    public function __construct(
        public int $staffId,
        public string $staffName,
        public int $streakDays,
        public array $absentDates,
        public bool $isForStaff = false
    ) {}

    /**
     * Send via database only (no email/SMS needed)
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Store in notifications table as JSON
     */
    public function toDatabase(object $notifiable): array
    {
        $dateRange = implode(', ', array_map(
            fn($d) => \Carbon\Carbon::parse($d)->format('M j'),
            $this->absentDates
        ));

        if ($this->isForStaff) {
            return [
                'title' => 'Attendance Warning',
                'message' => "You have been marked absent for {$this->streakDays} consecutive working days ({$dateRange}). Please contact management if this is an error.",
                'type' => 'absence_warning',
                'severity' => 'warning',
                'staff_id' => $this->staffId,
                'streak_days' => $this->streakDays,
                'dates' => $this->absentDates,
                'action_url' => route('staff.schedule'),
                'action_text' => 'View My Schedule',
            ];
        }

        return [
            'title' => 'Staff Absence Alert',
            'message' => "{$this->staffName} has been absent for {$this->streakDays} consecutive working days ({$dateRange}).",
            'type' => 'absence_alert',
            'severity' => $this->streakDays >= 5 ? 'critical' : 'warning',
            'staff_id' => $this->staffId,
            'staff_name' => $this->staffName,
            'streak_days' => $this->streakDays,
            'dates' => $this->absentDates,
            'action_url' => route('attendance.today'),
            'action_text' => 'Mark Attendance',
        ];
    }

    /**
     * Array format (for broadcasting if needed later)
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}