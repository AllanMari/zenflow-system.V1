<?php

namespace App\Services;

use App\Models\BusinessHour;
use App\Models\BusinessException;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingDiagnosticService
{
    /**
     * Diagnose why no time slots are available for a given booking request.
     *
     * @param Collection $services
     * @param string|null $dateStr
     * @param int $duration
     * @param array $slots
     * @return array|null
     */
    public static function diagnose(Collection $services, ?string $dateStr, int $duration, array $slots = []): ?array
    {
        if (empty($dateStr) || $services->isEmpty()) {
            return null;
        }

        $availableSlots = array_filter($slots, fn($s) => !empty($s['room_available']));
        if (!empty($availableSlots)) {
            return null;
        }

        $tz = 'Asia/Manila';
        $date = Carbon::parse($dateStr, $tz);
        $now = Carbon::now($tz);
        $dayOfWeek = $date->dayOfWeek;

        // 1. Check Room Compatibility and Category Conflicts
        $roomReqServices = $services->filter(fn($s) => (bool) $s->requires_room);
        if ($roomReqServices->isNotEmpty()) {
            $categoryIds = $roomReqServices->pluck('room_category_id')->filter()->unique();

            // Multiple conflicting room categories in one booking?
            if ($categoryIds->count() > 1) {
                return [
                    'code' => 'INCOMPATIBLE_ROOM_REQUIREMENTS',
                    'badge' => 'Room Setup Conflict',
                    'title' => 'Selected treatments require different room setups',
                    'message' => 'The services in your cart require different types of treatment rooms (for example, a private single suite and a couple’s suite) that cannot be conducted in the same room at the same time.',
                    'tip' => 'To book both, please submit them as two separate reservations, or choose treatments that share the same room type.',
                    'action_label' => 'Modify Selected Services',
                    'action_step' => 1,
                    'icon' => 'door-closed',
                    'color' => 'amber',
                ];
            }

            // A service requires room category, but no rooms match or none configured?
            $compatibleRooms = RoomCompatibilityService::findCompatibleForServices($services);
            if ($compatibleRooms->isEmpty()) {
                return [
                    'code' => 'NO_COMPATIBLE_ROOMS',
                    'badge' => 'Room Availability',
                    'title' => 'No dedicated room available for this package',
                    'message' => 'This specialty treatment requires a dedicated room category (such as our VIP Couple Suite) which is currently unavailable or being prepared on this date.',
                    'tip' => 'Please select an alternative date, or contact our front desk directly so we can check room arrangements for you.',
                    'action_label' => 'Choose Another Date',
                    'action_step' => 2,
                    'icon' => 'bed-double',
                    'color' => 'teal',
                ];
            }
        }

        // 2. Check Closed Business Day / Exception
        $exception = BusinessException::whereDate('date', $dateStr)->first();
        if ($exception && $exception->is_closed) {
            $reasonTitle = $exception->title ?: 'Holiday / Special Event';
            return [
                'code' => 'CLOSED_FOR_HOLIDAY',
                'badge' => 'Holiday / Special Notice',
                'title' => "Closed for {$reasonTitle}",
                'message' => "Our spa is closed on this date in observance of {$reasonTitle}. Normal operations and appointment bookings will resume on the following day.",
                'tip' => 'Please check our calendar for dates right before or after this holiday.',
                'action_label' => 'Pick Another Date',
                'action_step' => 2,
                'icon' => 'calendar-off',
                'color' => 'rose',
            ];
        }

        $weeklyHour = BusinessHour::where('day_of_week', $dayOfWeek)->first();
        if ($weeklyHour && $weeklyHour->is_closed) {
            $dayName = BusinessHour::getDayName($dayOfWeek);
            return [
                'code' => 'WEEKLY_CLOSED_DAY',
                'badge' => 'Weekly Closed Day',
                'title' => 'Spa Alexandria is closed on this day',
                'message' => "We are closed on {$dayName}s so our team can rest and sanitize all treatment facilities.",
                'tip' => 'We would love to welcome you on any of our regular operating days from Tuesday to Sunday, 9:00 AM to 8:00 PM.',
                'action_label' => 'Select Next Open Date',
                'action_step' => 2,
                'icon' => 'calendar-x',
                'color' => 'slate',
            ];
        }

        // 3. Check On-Duty Staff
        $staffMembers = User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
            ->where('is_active', true)
            ->get();

        if ($staffMembers->isEmpty()) {
            return [
                'code' => 'NO_ON_DUTY_STAFF',
                'badge' => 'Therapist Schedule',
                'title' => 'No therapists scheduled for duty',
                'message' => 'We currently do not have scheduled therapists on duty for this date or all assigned staff are off-duty.',
                'tip' => 'Please pick an adjacent date when our full therapy team is on shift.',
                'action_label' => 'Pick Another Date',
                'action_step' => 2,
                'icon' => 'user-x',
                'color' => 'amber',
            ];
        }

        // Check if any staff has scheduled work hours on this date
        $anyStaffScheduled = false;
        foreach ($staffMembers as $staff) {
            $hasSchedule = WorkSchedule::where('user_id', $staff->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_day_off', false)
                ->exists();
            if ($hasSchedule) {
                $anyStaffScheduled = true;
                break;
            }
        }

        if (!$anyStaffScheduled) {
            return [
                'code' => 'NO_STAFF_ON_DUTY_DAY',
                'badge' => 'Therapist Schedule',
                'title' => 'No therapists scheduled for duty',
                'message' => 'We currently do not have scheduled therapists on duty for this date or all assigned staff are attending training / off-duty.',
                'tip' => 'Please pick an adjacent date when our full therapy team is on shift.',
                'action_label' => 'Pick Another Date',
                'action_step' => 2,
                'icon' => 'user-x',
                'color' => 'amber',
            ];
        }

        // 4. Check Same-Day Lead Time
        if ($date->isToday()) {
            $bizWindow = BusinessScheduleService::getOperatingWindow($dateStr);
            $closeTimeStr = $bizWindow['end'] ?? '20:00';
            $closeTime = Carbon::parse($dateStr . ' ' . $closeTimeStr, $tz);

            if ($now->copy()->addMinutes(30)->gte($closeTime)) {
                return [
                    'code' => 'PAST_LEAD_TIME_TODAY',
                    'badge' => 'Same-Day Booking Cut-off',
                    'title' => 'Online booking for today has closed',
                    'message' => 'To allow our team adequate preparation time, online reservations must be made at least 30 minutes in advance. Remaining slots for today can no longer be booked online.',
                    'tip' => 'Walk-in appointments or immediate phone reservations may still be available! Please give our reception a quick call, or book for tomorrow.',
                    'action_label' => 'Book for Tomorrow',
                    'action_step' => 2,
                    'icon' => 'clock-alert',
                    'color' => 'orange',
                ];
            }
        }

        // 5. Check Duration Overrun
        $bizWindow = BusinessScheduleService::getOperatingWindow($dateStr);
        if ($bizWindow['is_open'] && !empty($bizWindow['start']) && !empty($bizWindow['end'])) {
            $windowStart = Carbon::parse($dateStr . ' ' . $bizWindow['start'], $tz);
            $windowEnd = Carbon::parse($dateStr . ' ' . $bizWindow['end'], $tz);
            $windowMinutes = $windowStart->diffInMinutes($windowEnd);

            if ($duration > $windowMinutes) {
                $hoursText = round($duration / 60, 1);
                return [
                    'code' => 'DURATION_EXCEEDS_HOURS',
                    'badge' => 'Duration Exceeds Hours',
                    'title' => 'Total service duration exceeds remaining hours',
                    'message' => "Your selected treatment package takes {$duration} minutes ({$hoursText} hrs), which cannot be completed before our closing time on this date.",
                    'tip' => 'Choose a date with morning or early afternoon availability so you can enjoy your full treatment without feeling rushed.',
                    'action_label' => 'Choose An Earlier Date',
                    'action_step' => 2,
                    'icon' => 'hourglass',
                    'color' => 'violet',
                ];
            }
        }

        // 6. Default Fallback: Fully Booked
        return [
            'code' => 'FULLY_BOOKED',
            'badge' => 'Fully Booked',
            'title' => 'All appointments are fully booked for this day',
            'message' => 'Every therapist and treatment room is fully reserved on this date. We are so grateful for your interest and apologize for any inconvenience!',
            'tip' => 'We recommend booking 1–2 days in advance or checking our morning slots on weekdays for better availability.',
            'action_label' => 'Check Next Available Day',
            'action_step' => 2,
            'icon' => 'calendar-check',
            'color' => 'amber',
        ];
    }
}
