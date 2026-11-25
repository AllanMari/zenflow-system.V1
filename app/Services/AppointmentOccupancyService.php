<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Single source of truth for resource occupancy.
 *
 * Treatment time stays exactly as recorded: appointment start/end and
 * AppointmentService.service_duration are never modified. Preparation time is
 * a COMPUTED interval added after the whole treatment, for both the assigned
 * therapist(s) and the assigned room.
 */
class AppointmentOccupancyService
{
    public const PREPARATION_MINUTES = 5;

    /** Appointment statuses that reserve therapist and room resources. */
    public const BLOCKING_STATUSES = ['pending', 'confirmed', 'completed'];

    public function appointmentStart(Appointment $appointment): Carbon
    {
        return Carbon::parse(
            $this->dateString($appointment->appointment_date) . ' ' . $this->timeString($appointment->start_time)
        );
    }

    /**
     * Actual treatment end, reconstructed from booked services when possible.
     * Falls back to the stored appointment end for legacy/incomplete records.
     */
    public function treatmentEnd(Appointment $appointment): Carbon
    {
        $start = $this->appointmentStart($appointment);
        $storedEnd = $this->storedEnd($appointment);

        if (!$appointment->relationLoaded('services')) {
            $appointment->load('services');
        }

        $minutes = 0;
        foreach ($appointment->services as $service) {
            $minutes += (int) ($service->pivot->service_duration ?? $service->duration_minutes ?? 0);
        }

        if ($minutes <= 0) {
            return $storedEnd;
        }

        $reconstructed = $start->copy()->addMinutes($minutes);

        return $reconstructed->greaterThan($storedEnd) ? $reconstructed : $storedEnd;
    }

    /** Stored treatment end (never includes preparation). */
    public function storedEnd(Appointment $appointment): Carbon
    {
        $start = $this->appointmentStart($appointment);
        $end = Carbon::parse(
            $this->dateString($appointment->appointment_date) . ' ' . $this->timeString($appointment->end_time)
        );

        // Legacy appointments crossing midnight.
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return $end;
    }

    /** Resource becomes free five minutes after treatment ends. */
    public function resourceEnd(Appointment $appointment): Carbon
    {
        return $this->treatmentEnd($appointment)->copy()->addMinutes(self::PREPARATION_MINUTES);
    }

    /**
     * Occupancy window [start, treatment end + preparation] for an appointment.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function appointmentWindow(Appointment $appointment): array
    {
        return [$this->appointmentStart($appointment), $this->resourceEnd($appointment)];
    }

    /** Occupancy window for a requested slot; the candidate carries its own buffer. */
    public function requestWindow($date, $startTime, $endTime): array
    {
        $start = Carbon::parse($this->dateString($date) . ' ' . $this->timeString($startTime));
        $end = Carbon::parse($this->dateString($date) . ' ' . $this->timeString($endTime));
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return [$start, $end->copy()->addMinutes(self::PREPARATION_MINUTES)];
    }

    /** Exclusive-boundary overlap test. Touching endpoints are NOT a conflict. */
    public function overlaps(Carbon $aStart, Carbon $aEnd, Carbon $bStart, Carbon $bEnd): bool
    {
        return $aStart->lessThan($bEnd) && $aEnd->greaterThan($bStart);
    }

    /**
     * Appointments on a date that occupy a therapist during the requested window.
     * Relationship-aware: includes both primary (user_id) and assisting pivot rows.
     */
    public function staffConflicts(
        int $staffId,
        string $date,
        $startTime,
        $endTime,
        ?int $excludeAppointmentId = null,
        bool $lockForUpdate = false
    ): Collection {
        [$requestStart, $requestEnd] = $this->requestWindow($date, $startTime, $endTime);

        $query = $this->baseQuery($date, $excludeAppointmentId);
        $this->whereOccupiesStaff($query, $staffId);

        // Under InnoDB REPEATABLE READ a plain SELECT can return an older
        // snapshot. Write paths call this AFTER acquiring resource locks and
        // must see appointments committed while they were waiting, so use a
        // current (locking) read there.
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query
            ->with('services')
            ->get()
            ->filter(function (Appointment $appointment) use ($requestStart, $requestEnd) {
                [$existingStart, $existingEnd] = $this->appointmentWindow($appointment);

                return $this->overlaps($existingStart, $existingEnd, $requestStart, $requestEnd);
            })
            ->values();
    }

    public function hasStaffConflict(
        int $staffId,
        string $date,
        $startTime,
        $endTime,
        ?int $excludeAppointmentId = null,
        bool $lockForUpdate = false
    ): bool {
        return $this->staffConflicts(
            $staffId,
            $date,
            $startTime,
            $endTime,
            $excludeAppointmentId,
            $lockForUpdate
        )->isNotEmpty();
    }

    /**
     * Occupancy intervals for a therapist on a date, sorted by start.
     * Used by gap/time-card generation so busy periods include preparation.
     *
     * @return array<int, array{start: string, end: string}>
     */
    public function staffIntervals(int $staffId, string $date, ?int $excludeAppointmentId = null): array
    {
        $dayEnd = Carbon::parse($this->dateString($date) . ' 23:59:59');

        $query = $this->baseQuery($date, $excludeAppointmentId);
        $this->whereOccupiesStaff($query, $staffId);

        return $query
            ->with('services')
            ->get()
            ->map(function (Appointment $appointment) use ($dayEnd) {
                [$start, $end] = $this->appointmentWindow($appointment);

                return [
                    'start' => $start->format('H:i'),
                    'end' => $end->greaterThan($dayEnd) ? '23:59' : $end->format('H:i'),
                ];
            })
            ->sortBy('start')
            ->values()
            ->all();
    }

    /** Appointments on a date that occupy a specific room during the requested window. */
    public function roomConflicts(
        int $roomId,
        string $date,
        $startTime,
        $endTime,
        ?int $excludeAppointmentId = null,
        bool $lockForUpdate = false
    ): Collection {
        [$requestStart, $requestEnd] = $this->requestWindow($date, $startTime, $endTime);

        $query = $this->baseQuery($date, $excludeAppointmentId)
            ->where('room_id', $roomId);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query
            ->with('services')
            ->get()
            ->filter(function (Appointment $appointment) use ($requestStart, $requestEnd) {
                [$existingStart, $existingEnd] = $this->appointmentWindow($appointment);

                return $this->overlaps($existingStart, $existingEnd, $requestStart, $requestEnd);
            })
            ->values();
    }

    public function hasRoomConflict(
        int $roomId,
        string $date,
        $startTime,
        $endTime,
        ?int $excludeAppointmentId = null,
        bool $lockForUpdate = false
    ): bool {
        return $this->roomConflicts(
            $roomId,
            $date,
            $startTime,
            $endTime,
            $excludeAppointmentId,
            $lockForUpdate
        )->isNotEmpty();
    }

    /**
     * Build occupancy intervals from an already loaded appointment collection.
     * Used by bulk slot generation to avoid per-candidate database queries.
     *
     * @return \Illuminate\Support\Collection<int, array{0: Carbon, 1: Carbon}>
     */
    public function intervalsFrom(
        Collection $appointments,
        ?int $staffId = null,
        ?int $roomId = null,
        ?int $excludeAppointmentId = null
    ): Collection {
        return $appointments->filter(function ($appointment) use ($staffId, $roomId, $excludeAppointmentId) {
            if (!($appointment instanceof Appointment ? $appointment->isOccupyingResource() : in_array($appointment->status, self::BLOCKING_STATUSES, true))) {
                return false;
            }

            if ($excludeAppointmentId && (int) $appointment->id === $excludeAppointmentId) {
                return false;
            }

            if ($roomId && (int) $appointment->room_id !== $roomId) {
                return false;
            }

            if ($staffId) {
                $isStaff = (int) $appointment->user_id === $staffId
                    || ($appointment->relationLoaded('assignedStaff')
                        && $appointment->assignedStaff->contains('id', $staffId))
                    || ($appointment->status === 'pending'
                        && $appointment->relationLoaded('preferredStaff')
                        && $appointment->preferredStaff->contains('id', $staffId));

                if (!$isStaff) {
                    return false;
                }
            }

            return true;
        })->map(fn($appointment) => $this->appointmentWindow($appointment))->values();
    }

    /** Check a requested window against precomputed intervals (exclusive boundaries). */
    public function overlapsAny(Collection $intervals, Carbon $start, Carbon $end): bool
    {
        return $intervals->contains(
            fn(array $interval) => $this->overlaps($interval[0], $interval[1], $start, $end)
        );
    }

    /** Round a time UP to the next five-minute boundary. */
    public function ceilToFive(Carbon $time): Carbon
    {
        $minutes = (int) $time->format('i');
        $remainder = $minutes % 5;

        return $remainder === 0
            ? $time->copy()
            : $time->copy()->addMinutes(5 - $remainder)->startOfMinute();
    }

    private function baseQuery(string $date, ?int $excludeAppointmentId)
    {
        return Appointment::query()
            ->whereDate('appointment_date', $this->dateString($date))
            ->occupyingResources()
            ->when($excludeAppointmentId, function ($q) use ($excludeAppointmentId) {
                $q->where('id', '!=', $excludeAppointmentId);
            });
    }

    private function whereOccupiesStaff($query, int $staffId): void
    {
        $query->where(function ($q) use ($staffId) {
            $q->where('user_id', $staffId)
                ->orWhereHas('assignedStaff', function ($sub) use ($staffId) {
                    $sub->where('users.id', $staffId);
                })
                ->orWhere(function ($pendingPref) use ($staffId) {
                    $pendingPref->where('status', 'pending')
                        ->whereHas('preferredStaff', function ($sub) use ($staffId) {
                            $sub->where('users.id', $staffId);
                        });
                });
        });
    }

    private function dateString($value): string
    {
        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        return substr((string) $value, 0, 10);
    }

    private function timeString($value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('H:i:s');
        }

        $value = (string) $value;
        if (str_contains($value, ' ')) {
            return substr($value, 11, 8);
        }
        if (!str_contains($value, ':')) {
            return '00:00:00';
        }

        $parts = explode(':', $value);

        return sprintf('%02d:%02d:%02d', (int) $parts[0], (int) ($parts[1] ?? 0), (int) ($parts[2] ?? 0));
    }
}
