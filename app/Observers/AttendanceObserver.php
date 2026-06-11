<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Services\AbsenceMonitor;

class AttendanceObserver
{
    /**
     * Trigger absence check whenever attendance is created or updated.
     */
    public function saved(Attendance $attendance): void
    {
        // Only check when status is actually "absent"
        if ($attendance->status === 'absent') {
            AbsenceMonitor::check($attendance->user_id);
        }
    }
}