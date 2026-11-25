<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Shared candidate generation and single-slot validation for every scheduling
 * workflow (booking wizard, quick book, confirmation, reassign, add service and
 * reschedule).
 *
 * All resource windows are the treatment window PLUS the single post-treatment
 * preparation interval from AppointmentOccupancyService. Treatment end times
 * are never modified: the buffer only makes the therapist and room unavailable
 * to the *next* booking.
 */
class AppointmentAvailabilityService
{
    public const TZ = 'Asia/Manila';
    public const GRID_MINUTES = 30;
    public const EXACT_STEP_MINUTES = 5;
    public const MIN_IMPROVEMENT_MINUTES = 10;

    public function __construct(
        private AppointmentOccupancyService $occupancy,
        private ScheduleWindowService $windows
    ) {
    }

    /**
     * Derive duration, staffing and room requirements from the actual booked
     * services. When an appointment is supplied, snapshot service_duration is
     * authoritative so added services extend the treatment correctly.
     */
    public function requirements(Collection $services, ?Appointment $appointment = null): array
    {
        if ($appointment) {
            if (!$appointment->relationLoaded('services')) {
                $appointment->load('services');
            }
            $services = $appointment->services;
        }

        $duration = 0;
        foreach ($services as $service) {
            $minutes = $appointment
                ? ($service->pivot->service_duration ?? $service->duration_minutes ?? 0)
                : ($service->duration_minutes ?? 0);
            $duration += (int) $minutes;
        }

        return [
            'services' => $services,
            'duration' => $duration,
            // Therapists work simultaneously: an appointment needs the highest
            // requirement among its services, never fewer than one.
            'therapists_required' => max(1, (int) $services->max('therapists_required')),
            'requires_room' => $services->contains(fn($s) => (bool) $s->requires_room),
        ];
    }

    /**
     * Generate valid time cards for a date.
     *
     * Supported options:
     *  date, services, duration, staff_ids, team_size, any_staff, room_id,
     *  exclude_appointment_id, min_start (Carbon), appointments (Collection),
     *  biz_window, staff_windows, compatible_rooms, max_results.
     *
     * @return array<int, array{time: string, end_time: string, display: string, free_rooms: array, room_available: bool}>
     */
    public function availableTimes(array $options): array
    {
        $context = $this->buildContext($options);
        if (!$context['usable']) {
            return [];
        }

        $cards = [];
        foreach ($context['candidates'] as $candidate) {
            $check = $this->checkCandidate($candidate, $context);
            if ($check['available']) {
                $cards[] = $check['card'];
            }
        }

        $cards = $this->applyImprovementThreshold($cards);

        if (!empty($options['max_results'])) {
            $cards = array_slice($cards, 0, (int) $options['max_results']);
        }

        return $cards;
    }

    /**
     * Authoritative single-window check used by write endpoints. Returns a
     * human-readable reason so the receptionist is told *why* a slot is invalid.
     */
    public function isWindowAvailable(array $options): array
    {
        $context = $this->buildContext($options);
        if (!$context['usable']) {
            return [
                'available' => false,
                'reason' => $context['reason'] ?: 'This appointment cannot be placed on the selected date.',
                'free_rooms' => [],
            ];
        }

        $start = $options['start'] ?? null;
        $end = $options['end'] ?? null;
        if (!$start || !$end) {
            return ['available' => false, 'reason' => 'A start and end time are required.', 'free_rooms' => []];
        }

        $dateStr = $context['date'];
        $parse = function ($value) use ($dateStr) {
            if ($value instanceof Carbon) {
                return $value->copy();
            }
            $value = (string) $value;
            if (str_contains($value, ' ')) {
                return Carbon::parse($value, self::TZ);
            }
            if (strlen($value) === 5 || strlen($value) === 8) {
                return Carbon::parse($dateStr . ' ' . $value, self::TZ);
            }

            return Carbon::parse($value, self::TZ);
        };

        $check = $this->checkCandidate($parse($start), $context, $parse($end));

        return [
            'available' => $check['available'],
            'reason' => $check['reason'],
            'free_rooms' => $check['card']['free_rooms'] ?? [],
        ];
    }

    /**
     * Bulk month availability for the reschedule calendar. Only a handful of
     * queries are executed regardless of how many dates the month contains.
     *
     * @return array{dates: array<int, string>, duration: int, therapists_required: int, reason: ?string}
     */
    public function availableDatesForAppointment(Appointment $appointment, string $month): array
    {
        $req = $this->requirements($appointment->services, $appointment);
        $staffIds = $this->appointmentStaffIds($appointment);
        $date = Carbon::parse($month . '-01', self::TZ);
        $startDate = $date->copy()->startOfMonth()->toDateString();
        $endDate = $date->copy()->endOfMonth()->toDateString();
        $today = Carbon::today(self::TZ)->toDateString();

        $appointments = Appointment::whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->occupyingResources()
            ->with(['services', 'assignedStaff', 'preferredStaff'])
            ->get()
            ->groupBy(fn($a) => $a->appointment_date->toDateString());

        $bizWindows = BusinessScheduleService::getOperatingWindowsForRange($startDate, $endDate);
        $staffWindows = $this->windows->workWindowsForRange($staffIds, $startDate, $endDate);
        $compatibleRooms = $req['requires_room']
            ? RoomCompatibilityService::findCompatibleForServices($req['services'])
            : collect();

        $availableDates = [];
        $reason = null;

        for ($cursor = $date->copy()->startOfMonth(); $cursor->toDateString() <= $endDate; $cursor->addDay()) {
            $dateStr = $cursor->toDateString();
            if ($dateStr < $today) {
                continue;
            }

            $bizWindow = $bizWindows[$dateStr] ?? null;
            $dayWindows = [];
            foreach ($staffIds as $staffId) {
                $window = $staffWindows[$staffId . '|' . $dateStr] ?? null;
                if ($window) {
                    $dayWindows[$staffId] = $window;
                }
            }

            $times = $this->availableTimes([
                'date' => $dateStr,
                'services' => $req['services'],
                'duration' => $req['duration'],
                'staff_ids' => $staffIds,
                'team_size' => $req['therapists_required'],
                'room_id' => $appointment->room_id,
                'exclude_appointment_id' => $appointment->id,
                'min_start' => $dateStr === $today ? Carbon::now(self::TZ) : null,
                'appointments' => $appointments->get($dateStr, collect()),
                'biz_window' => $bizWindow,
                'staff_windows' => $dayWindows,
                'compatible_rooms' => $compatibleRooms,
            ]);

            if (!empty($times)) {
                $availableDates[] = $dateStr;
            } elseif (!$reason && $bizWindow && !($bizWindow['is_open'] ?? false)) {
                $reason = $bizWindow['reason'] ?? 'The spa is closed on this date.';
            }
        }

        return [
            'dates' => $availableDates,
            'duration' => $req['duration'],
            'therapists_required' => $req['therapists_required'],
            'reason' => $reason,
        ];
    }

    /** Primary + assisting therapist IDs, including the legacy user_id fallback. */
    public function appointmentStaffIds(Appointment $appointment): array
    {
        if (!$appointment->relationLoaded('assignedStaff')) {
            $appointment->load('assignedStaff');
        }

        $ids = $appointment->assignedStaff->pluck('id')->map(fn($id) => (int) $id)->all();
        if (empty($ids) && $appointment->user_id) {
            $ids = [(int) $appointment->user_id];
        }

        return array_values(array_unique($ids));
    }

    private function buildContext(array $options): array
    {
        $date = Carbon::parse($options['date'] ?? now()->toDateString(), self::TZ)->toDateString();
        $services = $options['services'] ?? collect();
        $services = $services instanceof Appointment ? $services->services : collect($services);
        $req = $this->requirements($services);
        $duration = (int) ($options['duration'] ?? $req['duration']);

        $staffIds = array_values(array_unique(array_map('intval', $options['staff_ids'] ?? [])));
        $teamSize = max(1, (int) ($options['team_size'] ?? $req['therapists_required']));
        $anyStaff = (bool) ($options['any_staff'] ?? false);
        $roomId = !empty($options['room_id']) ? (int) $options['room_id'] : null;
        $exclude = !empty($options['exclude_appointment_id']) ? (int) $options['exclude_appointment_id'] : null;
        $minStart = $options['min_start'] ?? null;

        $bizWindow = $options['biz_window'] ?? BusinessScheduleService::getOperatingWindow($date);
        if (!($bizWindow['is_open'] ?? false)) {
            return $this->unusable($bizWindow['reason'] ?? 'The spa is closed on this date.');
        }

        if (empty($staffIds) && !$anyStaff) {
            return $this->unusable('No therapist is assigned to this appointment.');
        }

        $windows = $options['staff_windows'] ?? null;
        if ($windows === null) {
            $windows = [];
            foreach ($staffIds as $staffId) {
                $window = $this->windows->workWindow($staffId, $date);
                if ($window) {
                    $windows[$staffId] = $window;
                }
            }
        }

        if (empty($windows)) {
            return $this->unusable('The assigned therapist(s) have no working shift on this date.');
        }

        $windowStart = Carbon::parse($date . ' ' . min(array_column($windows, 'start')), self::TZ);
        $windowEnd = Carbon::parse($date . ' ' . max(array_column($windows, 'end')), self::TZ);
        $bizOpen = !empty($bizWindow['start'])
            ? Carbon::parse($date . ' ' . $bizWindow['start'], self::TZ)
            : $windowStart->copy();
        $bizClose = !empty($bizWindow['end'])
            ? Carbon::parse($date . ' ' . $bizWindow['end'], self::TZ)
            : $windowEnd->copy();

        if ($windowStart->lt($bizOpen)) {
            $windowStart = $bizOpen->copy();
        }
        if ($windowEnd->gt($bizClose) && !$anyStaff) {
            // Right Now sessions can continue past business close while a
            // therapist is checked in; the staff window already reflects that.
            // For all other workflows the business window is authoritative.
            if (!($options['allow_past_close'] ?? false)) {
                $windowEnd = $bizClose->copy();
            }
        }

        if ($windowStart->gte($windowEnd)) {
            return $this->unusable('The treatment does not fit inside the working hours on this date.');
        }

        $dayAppointments = $options['appointments'] ?? null;
        if ($dayAppointments === null) {
            $dayAppointments = Appointment::whereDate('appointment_date', $date)
                ->occupyingResources()
                ->when($exclude, fn($q) => $q->where('id', '!=', $exclude))
                ->with(['services', 'assignedStaff', 'preferredStaff'])
                ->get();
        } else {
            $dayAppointments = collect($dayAppointments)
                ->when($exclude, fn($items) => $items->where('id', '!=', $exclude));
        }

        $busyByStaff = [];
        foreach (array_keys($windows) as $staffId) {
            $busyByStaff[$staffId] = $this->occupancy->intervalsFrom($dayAppointments, (int) $staffId, null, $exclude);
        }

        $requiresRoom = $req['requires_room'];
        $compatibleRooms = $options['compatible_rooms'] ?? null;
        if ($compatibleRooms === null) {
            $compatibleRooms = $requiresRoom
                ? RoomCompatibilityService::findCompatibleForServices($services)
                : collect();
        }
        $compatibleRooms = collect($compatibleRooms);

        if ($requiresRoom && $compatibleRooms->isEmpty() && !($options['allow_no_room'] ?? false)) {
            return $this->unusable('No room is configured for the selected service combination.');
        }

        $roomsToCheck = $roomId
            ? $compatibleRooms->filter(fn($room) => (int) $room->id === $roomId)->values()
            : $compatibleRooms;

        if ($requiresRoom && $roomId && $roomsToCheck->isEmpty()) {
            return $this->unusable('The current room is not compatible with all selected services.');
        }

        $busyByRoom = [];
        foreach ($roomsToCheck as $room) {
            $busyByRoom[$room->id] = $this->occupancy->intervalsFrom($dayAppointments, null, (int) $room->id, $exclude);
        }

        $candidates = collect();
        $grid = $windowStart->copy();
        $grid->minute(intdiv((int) $grid->format('i'), self::GRID_MINUTES) * self::GRID_MINUTES)->second(0);
        if ($grid->lt($windowStart)) {
            $grid->addMinutes(self::GRID_MINUTES);
        }
        while ($grid->lte($windowEnd)) {
            $candidates->push($grid->copy());
            $grid->addMinutes(self::GRID_MINUTES);
        }

        foreach ($dayAppointments as $appointment) {
            $candidates->push($this->occupancy->ceilToFive($this->occupancy->resourceEnd($appointment)));
        }
        $candidates->push($windowStart->copy());
        if ($duration > 0) {
            $candidates->push($this->occupancy->ceilToFive($bizClose->copy()->subMinutes($duration)));
        }

        $unique = $candidates->unique(fn($time) => $time->format('Y-m-d H:i'))->values();

        return [
            'usable' => true,
            'reason' => null,
            'date' => $date,
            'duration' => $duration,
            'staff_ids' => $staffIds,
            'team_size' => $teamSize,
            'any_staff' => $anyStaff,
            'windows' => $windows,
            'window_start' => $windowStart,
            'window_end' => $windowEnd,
            'biz_close' => $bizClose,
            'min_start' => $minStart,
            'requires_room' => $requiresRoom,
            'allow_no_room' => (bool) ($options['allow_no_room'] ?? false),
            'room_id' => $roomId,
            'rooms' => $roomsToCheck,
            'busy_by_staff' => $busyByStaff,
            'busy_by_room' => $busyByRoom,
            'candidates' => $unique,
        ];
    }

    private function checkCandidate(Carbon $candidate, array $context, ?Carbon $explicitEnd = null): array
    {
        $fail = fn(string $reason) => ['available' => false, 'reason' => $reason, 'card' => null];

        if ($candidate->lt($context['window_start']) || $candidate->gt($context['window_end'])) {
            return $fail('The selected time is outside the working hours for this appointment.');
        }
        if ($context['min_start'] && $candidate->lt($context['min_start'])) {
            return $fail('The selected time is in the past.');
        }

        $treatmentEnd = $explicitEnd ?: $candidate->copy()->addMinutes($context['duration']);
        $resourceEnd = $candidate->copy()->addMinutes($context['duration'] + AppointmentOccupancyService::PREPARATION_MINUTES);
        if ($explicitEnd) {
            $resourceEnd = $explicitEnd->copy()->addMinutes(AppointmentOccupancyService::PREPARATION_MINUTES);
        }

        if ($treatmentEnd->gt($context['biz_close'])) {
            return $fail('The treatment would end after business closing time.');
        }
        if ($resourceEnd->gt($context['window_end'])) {
            return $fail('The preparation time would extend past the therapist’s working hours.');
        }

        $availableStaff = 0;
        foreach ($context['staff_ids'] as $staffId) {
            $window = $context['windows'][$staffId] ?? null;
            if (!$window) {
                continue;
            }
            $staffStart = Carbon::parse($context['date'] . ' ' . $window['start'], self::TZ);
            $staffEnd = Carbon::parse($context['date'] . ' ' . $window['end'], self::TZ);
            if ($candidate->lt($staffStart) || $resourceEnd->gt($staffEnd)) {
                continue;
            }
            if ($this->occupancy->overlapsAny($context['busy_by_staff'][$staffId] ?? collect(), $candidate, $resourceEnd)) {
                continue;
            }
            $availableStaff++;
        }

        // A caller must never be able to weaken the service requirement by
        // supplying fewer therapist IDs than the services actually need.
        $requiredStaff = $context['any_staff']
            ? $context['team_size']
            : max(count($context['staff_ids']), $context['team_size']);
        if ($availableStaff < $requiredStaff) {
            return $fail('The assigned therapist(s) are not available for the full treatment and preparation period.');
        }

        $freeRooms = [];
        if ($context['requires_room']) {
            foreach ($context['rooms'] as $room) {
                if (!$this->occupancy->overlapsAny($context['busy_by_room'][$room->id] ?? collect(), $candidate, $resourceEnd)) {
                    $freeRooms[] = ['id' => (int) $room->id, 'name' => $room->name];
                }
            }
            if (empty($freeRooms) && !$context['allow_no_room']) {
                return $fail('No compatible room is available for the full treatment and preparation period.');
            }
        }

        return [
            'available' => true,
            'reason' => null,
            'card' => [
                'time' => $candidate->format('H:i'),
                'end_time' => $treatmentEnd->format('H:i'),
                'display' => $candidate->format('g:i A') . ' – ' . $treatmentEnd->format('g:i A'),
                'free_rooms' => $freeRooms,
                'room_available' => !$context['requires_room'] || !empty($freeRooms),
            ],
        ];
    }

    /**
     * Keep an irregular time only when it saves at least ten minutes versus
     * the next valid standard-grid time.
     */
    private function applyImprovementThreshold(array $cards): array
    {
        usort($cards, fn($a, $b) => strcmp($a['time'], $b['time']));

        $result = [];
        foreach ($cards as $index => $card) {
            $minute = (int) substr($card['time'], -2);
            if ($minute % self::GRID_MINUTES === 0) {
                $result[] = $card;
                continue;
            }

            $candidate = Carbon::parse($card['time']);
            $nextStandard = null;
            foreach (array_slice($cards, $index + 1) as $later) {
                if ((int) substr($later['time'], -2) % self::GRID_MINUTES === 0) {
                    $nextStandard = Carbon::parse($later['time']);
                    break;
                }
            }

            $diff = $nextStandard ? abs($nextStandard->diffInMinutes($candidate)) : null;
            if (!$nextStandard || $diff >= self::MIN_IMPROVEMENT_MINUTES) {
                $result[] = $card;
            }
        }

        return $result;
    }

    private function unusable(string $reason): array
    {
        return ['usable' => false, 'reason' => $reason];
    }
}
