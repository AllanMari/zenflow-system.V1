<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Room;
use App\Models\User;
use App\Services\AppointmentOccupancyService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TherapistAssignmentService
{
    /**
     * Resolves the required number of therapists for an appointment.
     *
     * Rule:
     * - Uses the appointment's booked service/package therapists_required.
     * - Packages are authoritative; included services are informational only.
     * - For multiple separately selected services on a single appointment:
     *   Since therapists work simultaneously during the session window,
     *   the appointment's staffing requirement is the maximum therapists_required
     *   among the selected services (defaults to 1 if empty or unset).
     *
     * @param Appointment $appointment
     * @return int
     */
    public static function resolveRequiredCount(Appointment $appointment): int
    {
        if (!$appointment->relationLoaded('services')) {
            $appointment->load('services');
        }

        $services = $appointment->services;

        if ($services->isEmpty()) {
            return 1;
        }

        $maxRequired = $services->max('therapists_required');

        return max(1, (int) $maxRequired);
    }

    /**
     * Checks if a therapist is eligible, active, scheduled, and conflict-free for the entire appointment.
     *
     * @param User|int $therapist
     * @param string $date (Y-m-d)
     * @param string $startTime (H:i or H:i:s)
     * @param string $endTime (H:i or H:i:s)
     * @param int|null $excludeAppointmentId
     * @return array [
     *     'available' => bool,
     *     'reason' => string|null,
     *     'status' => string
     * ]
     */
    public static function checkTherapistAvailability(
        $therapist,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeAppointmentId = null,
        bool $lockForUpdate = false
    ): array {
        $user = $therapist instanceof User ? $therapist : User::find($therapist);

        if (!$user) {
            return [
                'available' => false,
                'status' => 'not_found',
                'reason' => 'User does not exist.',
            ];
        }

        // 1. Must be active
        if (!$user->is_active) {
            return [
                'available' => false,
                'status' => 'inactive',
                'reason' => "Therapist {$user->full_name} is inactive.",
            ];
        }

        // 2. Must be an eligible therapist (has role 'staff')
        if (!$user->hasRole('staff')) {
            return [
                'available' => false,
                'status' => 'ineligible',
                'reason' => "User {$user->full_name} does not hold an active staff/therapist role.",
            ];
        }

        $carbonDate = Carbon::parse($date);
        $dateStr = $carbonDate->toDateString();
        $dayOfWeek = $carbonDate->dayOfWeek;
        $isToday = $dateStr === Carbon::today('Asia/Manila')->toDateString();

        $startStr = substr($startTime, 0, 5);
        $endStr = substr($endTime, 0, 5);

        // Standardize time for query with seconds
        $startSeconds = strlen($startTime) === 5 ? $startTime . ':00' : $startTime;
        $endSeconds = strlen($endTime) === 5 ? $endTime . ':00' : $endTime;

        // The therapist stays occupied for the whole preparation interval after
        // treatment. Shift checks must therefore include that interval; the
        // stored end time itself is never changed.
        $resourceEndStr = Carbon::parse($dateStr . ' ' . $endSeconds)
            ->addMinutes(AppointmentOccupancyService::PREPARATION_MINUTES)
            ->format('H:i');

        // 3. Check individual ScheduleException for this date
        $exception = $user->scheduleExceptions()
            ->whereDate('exception_date', $dateStr)
            ->first();

        if ($exception && in_array($exception->type, ['day_off', 'holiday', 'sick_leave', 'urgent_leave'], true)) {
            $label = ucfirst(str_replace('_', ' ', $exception->type));
            return [
                'available' => false,
                'status' => 'blocked',
                'reason' => "Therapist {$user->full_name} is on {$label} on {$dateStr}." . ($exception->reason ? " ({$exception->reason})" : ''),
            ];
        }

        // 4. Custom hours exception override (applies even if no recurring WorkSchedule exists)
        if ($exception && $exception->type === 'custom_hours') {
            $exStart = substr($exception->start_time, 0, 5);
            $exEnd = substr($exception->end_time, 0, 5);

            if ($startStr < $exStart || $resourceEndStr > $exEnd) {
                return [
                    'available' => false,
                    'status' => 'outside_hours',
                    'reason' => "Therapist {$user->full_name} has custom hours ({$exStart}-{$exEnd}) on this date.",
                ];
            }
        } else {
            // Check WorkSchedule for the day of week
            $schedule = $user->workSchedules()->where('day_of_week', $dayOfWeek)->first();

            if (!$schedule || $schedule->is_day_off) {
                return [
                    'available' => false,
                    'status' => 'day_off',
                    'reason' => "Therapist {$user->full_name} has a scheduled day off on " . Carbon::getDays()[$dayOfWeek] . '.',
                ];
            }

            $schStart = substr($schedule->start_time, 0, 5);
            $schEnd = substr($schedule->end_time, 0, 5);

            if ($startStr < $schStart || $resourceEndStr > $schEnd) {
                return [
                    'available' => false,
                    'status' => 'outside_hours',
                    'reason' => "Appointment time ({$startStr}-{$resourceEndStr}, including preparation) falls outside {$user->full_name}'s scheduled shift ({$schStart}-{$schEnd}).",
                ];
            }
        }

        // 5. Today's attendance blockers (absent, on_leave, holiday)
        if ($isToday) {
            $attendanceBlocker = $user->attendances()
                ->whereDate('date', $dateStr)
                ->whereIn('status', ['absent', 'on_leave', 'holiday'])
                ->first();

            if ($attendanceBlocker) {
                return [
                    'available' => false,
                    'status' => 'attendance_blocked',
                    'reason' => "Therapist {$user->full_name} is marked as {$attendanceBlocker->status} today.",
                ];
            }
        }

        // 6. Existing appointment conflicts across BOTH appointment_staff pivot AND appointments.user_id
        // Blocking statuses are defined by AppointmentOccupancyService and
        // include pending reservations; cancelled/no_show are non-blocking.
        $conflictExists = app(AppointmentOccupancyService::class)->hasStaffConflict(
            (int) $user->id,
            $dateStr,
            $startSeconds,
            $endSeconds,
            $excludeAppointmentId,
            $lockForUpdate
        );

        if ($conflictExists) {
            return [
                'available' => false,
                'status' => 'conflict',
                'reason' => "Therapist {$user->full_name} has a conflicting appointment during this time window ({$startStr}-{$endStr}).",
            ];
        }

        return [
            'available' => true,
            'status' => 'available',
            'reason' => null,
        ];
    }

    /**
     * Customer-safe therapist availability list for a selected date, start time, and duration.
     * Exposes only id, first_name, sanitized status, and generic label.
     *
     * @return array<int, array{id: int, first_name: string, status: string, label: string}>
     */
    public static function customerAvailability(
        string $date,
        string $startTime,
        int $durationMinutes
    ): array {
        $tz = 'Asia/Manila';
        $dateStr = Carbon::parse($date, $tz)->toDateString();
        $startStr = substr($startTime, 0, 5);
        $endStr = Carbon::parse("{$dateStr} {$startStr}:00", $tz)
            ->addMinutes(max(1, $durationMinutes))
            ->format('H:i');

        $bizWindow = BusinessScheduleService::getOperatingWindow($dateStr);
        $isBizOpen = (bool) ($bizWindow['is_open'] ?? true);

        $staffMembers = User::whereHas('roles', fn ($q) => $q->where('name', 'staff'))
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'is_active']);

        if (!$isBizOpen) {
            return $staffMembers->map(fn (User $u) => [
                'id' => (int) $u->id,
                'first_name' => (string) $u->first_name,
                'status' => 'not_scheduled',
                'label' => 'Not Scheduled',
            ])->values()->all();
        }

        $dayOfWeek = Carbon::parse($dateStr, $tz)->dayOfWeek;

        return $staffMembers->map(function (User $u) use ($dateStr, $startStr, $endStr, $dayOfWeek) {
            $check = self::checkTherapistAvailability($u, $dateStr, $startStr, $endStr);

            if ($check['available']) {
                return [
                    'id' => (int) $u->id,
                    'first_name' => (string) $u->first_name,
                    'status' => 'available',
                    'label' => 'Available',
                ];
            }

            $status = 'not_scheduled';
            $label = 'Not Scheduled';

            switch ($check['status']) {
                case 'conflict':
                    $status = 'occupied';
                    $label = 'Occupied';
                    break;
                case 'blocked':
                    $ex = $u->scheduleExceptions()->whereDate('exception_date', $dateStr)->first();
                    if ($ex && in_array($ex->type, ['sick_leave', 'urgent_leave'], true)) {
                        $status = 'on_leave';
                        $label = 'On Leave';
                    } else {
                        $status = 'day_off';
                        $label = 'Day Off';
                    }
                    break;
                case 'attendance_blocked':
                    $status = 'on_leave';
                    $label = 'On Leave';
                    break;
                case 'day_off':
                    $hasRow = $u->workSchedules()->where('day_of_week', $dayOfWeek)->exists();
                    if ($hasRow) {
                        $status = 'day_off';
                        $label = 'Day Off';
                    } else {
                        $status = 'not_scheduled';
                        $label = 'Not Scheduled';
                    }
                    break;
                case 'outside_hours':
                default:
                    $status = 'not_scheduled';
                    $label = 'Not Scheduled';
                    break;
            }

            return [
                'id' => (int) $u->id,
                'first_name' => (string) $u->first_name,
                'status' => $status,
                'label' => $label,
            ];
        })->values()->all();
    }

    /**
     * Centralized, transactional, concurrency-safe therapist assignment.
     *
     * Concurrency Protocol:
     * 1. Begins database transaction.
     * 2. Sorts all therapist IDs ascending to prevent deadlocks and locks user records via lockForUpdate().
     * 3. Rechecks each therapist's eligibility, active status, working hours, leave, and appointment conflicts while holding the locks.
     * 4. If a room is involved, locks the room record via lockForUpdate() after therapist locks.
     * 5. Rechecks room availability and status while holding the lock.
     * 6. Calls Appointment::syncStaff() to synchronize pivot and legacy appointments.user_id.
     * 7. Commits transaction and returns true.
     *
     * @param Appointment $appointment
     * @param int $primaryTherapistId
     * @param array<int> $assistingTherapistIds
     * @param int|null $roomId Optional room to lock and verify
     * @return void
     *
     * @throws InvalidArgumentException on validation or availability failure
     */
    public static function assignStaff(
        Appointment $appointment,
        int $primaryTherapistId,
        array $assistingTherapistIds = [],
        ?int $roomId = null
    ): void {
        // Collect and deduplicate assisting IDs
        $uniqueAssisting = array_values(array_unique(array_filter($assistingTherapistIds)));

        // Invariant: Primary therapist cannot be assisting
        if (in_array($primaryTherapistId, $uniqueAssisting, true)) {
            throw new InvalidArgumentException('A primary therapist cannot also be assigned as an assisting therapist on the same appointment.');
        }

        $allTherapistIds = array_merge([$primaryTherapistId], $uniqueAssisting);
        $totalAssigned = count($allTherapistIds);

        // Required count validation
        $requiredCount = self::resolveRequiredCount($appointment);
        if ($totalAssigned !== $requiredCount) {
            throw new InvalidArgumentException(
                "Appointment requires exactly {$requiredCount} therapist(s), but {$totalAssigned} were provided."
            );
        }

        // Concurrency Protocol: sort IDs ascending to guarantee consistent lock acquisition order across all threads
        sort($allTherapistIds, SORT_NUMERIC);

        // Snapshot-based treatment end is authoritative: a legacy stored
        // end_time may be too short and must not shrink the validation window.
        $occupancy = app(AppointmentOccupancyService::class);
        $treatmentEnd = $occupancy->treatmentEnd($appointment);
        $treatmentEndStr = $treatmentEnd->format('H:i:s');

        DB::transaction(function () use (
            $appointment,
            $primaryTherapistId,
            $uniqueAssisting,
            $allTherapistIds,
            $roomId,
            $treatmentEndStr
        ) {
            // 1. Lock all selected therapist records in ascending user ID order
            $lockedTherapists = User::whereIn('id', $allTherapistIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($lockedTherapists->count() !== count($allTherapistIds)) {
                throw new InvalidArgumentException('One or more selected therapists could not be found.');
            }

            // 2. Validate availability and eligibility for every therapist while holding locks
            foreach ($allTherapistIds as $therapistId) {
                $therapist = $lockedTherapists->get($therapistId);

                // Locked/current read: sees appointments committed while this
                // transaction waited for the therapist row locks.
                $check = self::checkTherapistAvailability(
                    $therapist,
                    $appointment->appointment_date,
                    $appointment->start_time,
                    $treatmentEndStr,
                    $appointment->id,
                    true
                );

                if (!$check['available']) {
                    throw new InvalidArgumentException($check['reason'] ?? "Therapist {$therapist->full_name} is not available.");
                }
            }

            // 3. Lock room record if specified
            $targetRoomId = $roomId ?? $appointment->room_id;
            if ($targetRoomId) {
                $lockedRoom = Room::where('id', $targetRoomId)->lockForUpdate()->first();
                if (!$lockedRoom) {
                    throw new InvalidArgumentException('Selected room could not be found.');
                }

                if (!$lockedRoom->isAvailableFor(
                    $appointment->appointment_date,
                    $appointment->start_time,
                    $treatmentEndStr,
                    $appointment->id,
                    true
                )) {
                    throw new InvalidArgumentException("Room [{$lockedRoom->name}] is not available for this appointment slot.");
                }
            }

            // 4. Perform appointment staff synchronization
            $appointment->syncStaff($primaryTherapistId, $uniqueAssisting);

            // Synchronize room_id if explicitly provided
            if ($roomId && $appointment->room_id !== $roomId) {
                $appointment->update(['room_id' => $roomId]);
            }
        });
    }
}
