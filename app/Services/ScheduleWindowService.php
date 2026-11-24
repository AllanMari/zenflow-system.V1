<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ScheduleException;
use App\Models\WorkSchedule;
use Carbon\Carbon;

/**
 * Shared, read-only access to the effective working window of a therapist.
 *
 * This is the single source of truth used by billing/availability endpoints and
 * by the write-side validation. It deliberately preserves the historical
 * behaviour of BookingController::getStaffWorkWindow():
 *  - leave / day-off exceptions win over a check-in,
 *  - custom_hours overrides the weekly WorkSchedule,
 *  - an active check-in today extends the effective end to 23:59 so that
 *    Right Now bookings can continue after the scheduled shift ends.
 */
class ScheduleWindowService
{
    public const TZ = 'Asia/Manila';

    /** A therapist who is checked in today is treated as available until end of day. */
    public const CHECKED_IN_END = '23:59';

    public function isCheckedIn(int $staffId, string $date): bool
    {
        if ($date !== Carbon::today(self::TZ)->format('Y-m-d')) {
            return false;
        }

        return Attendance::where('user_id', $staffId)
            ->whereNotIn('status', ['absent', 'on_leave', 'holiday'])
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->whereDate('date', $date)
            ->orderBy('id', 'desc')
            ->exists();
    }

    public function workWindow(
        int $staffId,
        string $date,
        bool $allowCheckedInExtension = false
    ): ?array {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        $exception = ScheduleException::where('user_id', $staffId)
            ->whereDate('exception_date', $date)
            ->first();

        if ($exception && in_array(
            $exception->type,
            ['day_off', 'holiday', 'sick_leave', 'urgent_leave'],
            true
        )) {
            return null;
        }

        $schedule = WorkSchedule::where('user_id', $staffId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_day_off', false)
            ->first();

        $start = $schedule ? $this->extractTime($schedule->start_time) : null;
        $end = $schedule ? $this->extractTime($schedule->end_time) : null;

        if ($exception && $exception->type === 'custom_hours') {
            $start = $this->extractTime($exception->start_time);
            $end = $this->extractTime($exception->end_time);
        }

        if (!$start || !$end) {
            return null;
        }

        // The post-shift extension exists ONLY for Right Now walk-ins.
        // Normal bookings, reschedules and assignments must use the real shift
        // so availability and final validation agree.
        if ($allowCheckedInExtension && $this->isCheckedIn($staffId, $date)) {
            $end = self::CHECKED_IN_END;
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Bulk variant used by the month availability endpoint. Exactly one query
     * per related table, regardless of the number of staff or dates.
     *
     * @return array<string, array{start: string, end: string}> keyed by "staffId|date"
     */
    public function workWindowsForRange(
        array $staffIds,
        string $startDate,
        string $endDate,
        bool $allowCheckedInExtension = false
    ): array {
        $staffIds = array_values(array_unique(array_map('intval', $staffIds)));
        if (empty($staffIds)) {
            return [];
        }

        $exceptions = ScheduleException::whereIn('user_id', $staffIds)
            ->whereDate('exception_date', '>=', $startDate)
            ->whereDate('exception_date', '<=', $endDate)
            ->get()
            ->keyBy(fn($e) => $e->user_id . '|' . Carbon::parse($e->exception_date)->toDateString());

        $schedules = WorkSchedule::whereIn('user_id', $staffIds)
            ->where('is_day_off', false)
            ->get()
            ->keyBy(fn($s) => $s->user_id . '|' . $s->day_of_week);

        $today = Carbon::today(self::TZ)->toDateString();
        $checkedIn = $today >= $startDate && $today <= $endDate
            ? Attendance::whereIn('user_id', $staffIds)
                ->whereDate('date', $today)
                ->whereNotIn('status', ['absent', 'on_leave', 'holiday'])
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->orderBy('id', 'desc')
                ->get()
                ->keyBy('user_id')
            : collect();

        $windows = [];
        $cursor = Carbon::parse($startDate);
        $last = Carbon::parse($endDate);

        while ($cursor->lte($last)) {
            $date = $cursor->toDateString();
            $dayOfWeek = $cursor->dayOfWeek;

            foreach ($staffIds as $staffId) {
                $exception = $exceptions->get($staffId . '|' . $date);

                if ($exception && in_array(
                    $exception->type,
                    ['day_off', 'holiday', 'sick_leave', 'urgent_leave'],
                    true
                )) {
                    continue;
                }

                $schedule = $schedules->get($staffId . '|' . $dayOfWeek);
                $start = $schedule ? $this->extractTime($schedule->start_time) : null;
                $end = $schedule ? $this->extractTime($schedule->end_time) : null;

                if ($exception && $exception->type === 'custom_hours') {
                    $start = $this->extractTime($exception->start_time);
                    $end = $this->extractTime($exception->end_time);
                }

                if (!$start || !$end) {
                    continue;
                }

                if ($allowCheckedInExtension && $date === $today && $checkedIn->has($staffId)) {
                    $end = self::CHECKED_IN_END;
                }

                $windows[$staffId . '|' . $date] = ['start' => $start, 'end' => $end];
            }

            $cursor->addDay();
        }

        return $windows;
    }

    private function extractTime($value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('H:i');
        }

        if (is_string($value) && str_contains($value, ' ')) {
            return substr($value, 11, 5);
        }

        if (is_string($value) && strlen($value) >= 5 && str_contains($value, ':')) {
            return substr($value, 0, 5);
        }

        return null;
    }
}
