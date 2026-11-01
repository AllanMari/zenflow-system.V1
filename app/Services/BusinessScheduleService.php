<?php

namespace App\Services;

use App\Models\BusinessHour;
use App\Models\BusinessException;
use Carbon\Carbon;

class BusinessScheduleService
{
    /**
     * Determine the operating window and status for a given date.
     *
     * @param string|Carbon $date
     * @return array
     */
    public static function getOperatingWindow($date): array
    {
        $carbonDate = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $dateStr = $carbonDate->format('Y-m-d');
        $dayOfWeek = $carbonDate->dayOfWeek; // 0 = Sunday, ..., 6 = Saturday

        // 1. Check for specific date exception (holiday, event, or special hours)
        $exception = BusinessException::whereDate('date', $dateStr)->first();

        if ($exception) {
            if ($exception->is_closed) {
                return [
                    'is_open' => false,
                    'reason' => $exception->title ?: 'Closed for special event/holiday',
                    'notice_message' => $exception->notice_message,
                    'type' => $exception->type,
                    'start' => null,
                    'end' => null,
                    'exception' => $exception,
                ];
            }

            // Custom open hours or special event for exception
            // If open_time or close_time are empty (e.g. Special Event announcement), fall back to weekly schedule hours
            $open = $exception->open_time ? substr($exception->open_time, 0, 5) : null;
            $close = $exception->close_time ? substr($exception->close_time, 0, 5) : null;

            if (!$open || !$close) {
                $weeklyHour = BusinessHour::where('day_of_week', $dayOfWeek)->first();
                $open = $open ?: ($weeklyHour && $weeklyHour->open_time ? substr($weeklyHour->open_time, 0, 5) : '09:00');
                $close = $close ?: ($weeklyHour && $weeklyHour->close_time ? substr($weeklyHour->close_time, 0, 5) : '20:00');
            }

            return [
                'is_open' => true,
                'reason' => $exception->title,
                'notice_message' => $exception->notice_message,
                'type' => $exception->type,
                'start' => $open,
                'end' => $close,
                'exception' => $exception,
            ];
        }

        // 2. Check regular weekly business hours
        $hour = BusinessHour::where('day_of_week', $dayOfWeek)->first();

        if ($hour) {
            if ($hour->is_closed) {
                return [
                    'is_open' => false,
                    'reason' => 'Closed on ' . BusinessHour::getDayName($dayOfWeek) . 's',
                    'notice_message' => null,
                    'type' => 'weekly_closed',
                    'start' => null,
                    'end' => null,
                    'exception' => null,
                ];
            }

            return [
                'is_open' => true,
                'reason' => null,
                'notice_message' => null,
                'type' => 'regular',
                'start' => $hour->open_time ? substr($hour->open_time, 0, 5) : '09:00',
                'end' => $hour->close_time ? substr($hour->close_time, 0, 5) : '20:00',
                'exception' => null,
            ];
        }

        // Fallback default if not seeded/configured yet
        return [
            'is_open' => true,
            'reason' => null,
            'notice_message' => null,
            'type' => 'default',
            'start' => '09:00',
            'end' => '20:00',
            'exception' => null,
        ];
    }

    /**
     * Get active notice/away message for a given date (or current date).
     *
     * @param string|Carbon|null $date
     * @return array|null
     */
    public static function getActiveNotice($date = null): ?array
    {
        $carbonDate = $date ? ($date instanceof Carbon ? $date->copy() : Carbon::parse($date)) : now('Asia/Manila');
        $dateStr = $carbonDate->format('Y-m-d');

        $exception = BusinessException::whereDate('date', $dateStr)->first();

        if ($exception && (!empty($exception->notice_message) || $exception->is_closed || $exception->type === 'custom_hours')) {
            $formattedHours = null;
            if (!$exception->is_closed && !empty($exception->open_time) && !empty($exception->close_time)) {
                $formattedHours = Carbon::parse($exception->open_time)->format('g:i A') . ' - ' . Carbon::parse($exception->close_time)->format('g:i A');
            }

            return [
                'title' => $exception->title,
                'type' => $exception->type,
                'is_closed' => (bool) $exception->is_closed,
                'open_time' => $exception->open_time,
                'close_time' => $exception->close_time,
                'formatted_hours' => $formattedHours,
                'message' => $exception->notice_message ?: ($exception->is_closed
                    ? 'We are closed on ' . $carbonDate->format('F j, Y') . ' for ' . $exception->title . '.'
                    : ($formattedHours ? 'Special operating hours today: ' . $formattedHours . '.' : null)),
                'date' => $dateStr,
                'formatted_date' => $carbonDate->format('F j, Y'),
            ];
        }

        return null;
    }

    /**
     * Get upcoming notices for the next N days (default 7 days), excluding today.
     *
     * @param int $daysAhead
     * @param Carbon|null $fromDate
     * @return \Illuminate\Support\Collection
     */
    public static function getUpcomingNotices(int $daysAhead = 7, ?Carbon $fromDate = null): \Illuminate\Support\Collection
    {
        $tz = 'Asia/Manila';
        $today = $fromDate ? $fromDate->copy()->timezone($tz)->startOfDay() : now($tz)->startOfDay();
        $startDate = $today->copy()->addDay();
        $endDate = $today->copy()->addDays($daysAhead)->endOfDay();

        $exceptions = BusinessException::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date', 'asc')
            ->get();

        return $exceptions->map(function ($exc) {
            $carbonDate = Carbon::parse($exc->date, 'Asia/Manila');
            $formattedHours = null;
            if (!$exc->is_closed && !empty($exc->open_time) && !empty($exc->close_time)) {
                $formattedHours = Carbon::parse($exc->open_time)->format('g:i A') . ' - ' . Carbon::parse($exc->close_time)->format('g:i A');
            }

            return [
                'id' => $exc->id,
                'title' => $exc->title,
                'type' => $exc->type,
                'is_closed' => (bool) $exc->is_closed,
                'open_time' => $exc->open_time,
                'close_time' => $exc->close_time,
                'formatted_hours' => $formattedHours,
                'notice_message' => $exc->notice_message,
                'date' => $carbonDate->toDateString(),
                'formatted_date' => $carbonDate->format('F j, Y'),
                'day_name' => $carbonDate->format('l'),
            ];
        });
    }
}
