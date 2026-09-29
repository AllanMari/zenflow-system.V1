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

            // Custom open hours for exception
            return [
                'is_open' => true,
                'reason' => $exception->title,
                'notice_message' => $exception->notice_message,
                'type' => $exception->type,
                'start' => $exception->open_time ? substr($exception->open_time, 0, 5) : '09:00',
                'end' => $exception->close_time ? substr($exception->close_time, 0, 5) : '20:00',
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

        if ($exception && (!empty($exception->notice_message) || $exception->is_closed)) {
            return [
                'title' => $exception->title,
                'type' => $exception->type,
                'is_closed' => (bool) $exception->is_closed,
                'message' => $exception->notice_message ?: ($exception->is_closed ? 'We are closed on ' . $carbonDate->format('F j, Y') . ' for ' . $exception->title . '.' : null),
                'date' => $dateStr,
                'formatted_date' => $carbonDate->format('F j, Y'),
            ];
        }

        return null;
    }
}
