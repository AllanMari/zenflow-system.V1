<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Attendance confirmation event store.
 *
 * Attendance is intentionally separate from booking approval, cancellation and
 * no-show handling. This service only appends rows to activity_logs and derives
 * state from those rows; it never updates the appointment itself.
 *
 * Phase 1 scope: confirmation and contact-attempt events only. Reminder events,
 * scheduler integration and HTTP/UI entry points belong to later phases.
 */
class AttendanceConfirmationService
{
    public const ENTITY_TYPE = Appointment::class;

    public const ACTION_CONFIRMED_CUSTOMER = 'attendance_confirmed_customer';
    public const ACTION_CONFIRMED_RECEPTIONIST = 'attendance_confirmed_receptionist';
    public const ACTION_CONTACT_NO_ANSWER = 'attendance_contact_no_answer';
    public const ACTION_CONTACT_UNREACHABLE = 'attendance_contact_unable_to_reach';

    public const STATE_AWAITING = 'awaiting';
    public const STATE_NO_ANSWER = 'no_answer';
    public const STATE_UNREACHABLE = 'unreachable';
    public const STATE_CONFIRMED = 'confirmed';

    public const SOURCE_CUSTOMER = 'customer';
    public const SOURCE_RECEPTIONIST = 'receptionist';

    public const CONFIRMATION_ACTIONS = [
        self::ACTION_CONFIRMED_CUSTOMER,
        self::ACTION_CONFIRMED_RECEPTIONIST,
    ];

    public const CONTACT_ACTIONS = [
        self::ACTION_CONTACT_NO_ANSWER,
        self::ACTION_CONTACT_UNREACHABLE,
    ];

    public const ATTENDANCE_ACTIONS = [
        ...self::CONFIRMATION_ACTIONS,
        ...self::CONTACT_ACTIONS,
    ];

    /**
     * A repeated identical contact attempt inside this window is treated as an
     * accidental double submission. This is an application-level guard only; it
     * is not a database uniqueness guarantee.
     */
    private const CONTACT_DEDUPE_WINDOW_SECONDS = 5;

    /**
     * Record attendance confirmation for the customer who owns the appointment.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function confirmByCustomer(
        Appointment $appointment,
        User $user,
        ?string $note = null,
        ?string $ipAddress = null
    ): array {
        return $this->recordConfirmation(
            $appointment,
            $user,
            self::ACTION_CONFIRMED_CUSTOMER,
            self::SOURCE_CUSTOMER,
            $note,
            $ipAddress
        );
    }

    public function confirmByReceptionist(
        Appointment $appointment,
        User $user,
        ?string $note = null,
        ?string $ipAddress = null
    ): array {
        return $this->recordConfirmation(
            $appointment,
            $user,
            self::ACTION_CONFIRMED_RECEPTIONIST,
            self::SOURCE_RECEPTIONIST,
            $note,
            $ipAddress
        );
    }

    public function recordNoAnswer(
        Appointment $appointment,
        User $user,
        ?string $note = null,
        ?string $ipAddress = null
    ): array {
        return $this->recordContactAttempt(
            $appointment,
            $user,
            self::STATE_NO_ANSWER,
            $note,
            $ipAddress
        );
    }

    public function recordUnableToReach(
        Appointment $appointment,
        User $user,
        ?string $note = null,
        ?string $ipAddress = null
    ): array {
        return $this->recordContactAttempt(
            $appointment,
            $user,
            self::STATE_UNREACHABLE,
            $note,
            $ipAddress
        );
    }

    /**
     * Record a contact attempt. Genuine repeated attempts remain recordable; only
     * an identical attempt by the same user inside the short dedupe window is
     * treated as an accidental duplicate.
     *
     * @param string $outcome One of: no_answer, unreachable
     */
    public function recordContactAttempt(
        Appointment $appointment,
        User $user,
        string $outcome,
        ?string $note = null,
        ?string $ipAddress = null,
        int $dedupeWindowSeconds = self::CONTACT_DEDUPE_WINDOW_SECONDS
    ): array {
        $action = match ($outcome) {
            self::STATE_NO_ANSWER => self::ACTION_CONTACT_NO_ANSWER,
            self::STATE_UNREACHABLE => self::ACTION_CONTACT_UNREACHABLE,
            default => throw new \InvalidArgumentException("Unsupported contact outcome: {$outcome}"),
        };

        return DB::transaction(function () use (
            $appointment,
            $user,
            $action,
            $outcome,
            $note,
            $ipAddress,
            $dedupeWindowSeconds
        ) {
            $locked = Appointment::whereKey($appointment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'confirmed') {
                return array_merge($this->stateFor($locked), [
                    'recorded' => false,
                    'already_confirmed' => false,
                    'duplicate' => false,
                    'reason' => 'appointment_not_confirmed',
                    'event_id' => null,
                ]);
            }

            if ($this->isAttendanceConfirmed($locked)) {
                return array_merge($this->stateFor($locked), [
                    'recorded' => false,
                    'already_confirmed' => true,
                    'duplicate' => false,
                    'reason' => 'already_confirmed',
                    'event_id' => null,
                ]);
            }

            $normalizedNote = $this->normalizeNote($note);

            $duplicate = $this->findRecentDuplicateContactAttempt(
                $locked,
                $action,
                $user,
                $normalizedNote,
                $dedupeWindowSeconds
            );

            if ($duplicate) {
                return array_merge($this->stateFor($locked), [
                    'recorded' => false,
                    'already_confirmed' => false,
                    'duplicate' => true,
                    'reason' => 'duplicate_contact_attempt',
                    'event_id' => $duplicate->id,
                ]);
            }

            $event = $this->createEvent($locked, $action, $user, [
                'actor_id' => $user->id,
                'actor_name' => $user->full_name,
                'outcome' => $outcome,
                'note' => $normalizedNote,
            ], $ipAddress);

            return array_merge($this->stateFor($locked), [
                'recorded' => true,
                'already_confirmed' => false,
                'duplicate' => false,
                'reason' => null,
                'event_id' => $event->id,
            ]);
        }, 3);
    }

    /**
     * Derive attendance state for one appointment.
     */
    public function stateFor(Appointment $appointment): array
    {
        $states = $this->statesFor(collect([$appointment]));

        return $states[$appointment->getKey()] ?? $this->emptyState();
    }

    /**
     * Bulk-load attendance state for a collection of appointments.
     *
     * @return array<int, array<string, mixed>> Keyed by appointment ID.
     */
    public function statesFor(Collection $appointments): array
    {
        $ids = $appointments
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $logs = ActivityLog::query()
            ->where('entity_type', self::ENTITY_TYPE)
            ->whereIn('entity_id', $ids)
            ->whereIn('action', self::ATTENDANCE_ACTIONS)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'user_id', 'action', 'entity_id', 'details', 'created_at'])
            ->groupBy('entity_id');

        $states = [];

        foreach ($appointments as $appointment) {
            $id = $appointment->getKey();

            if (!$id || array_key_exists($id, $states)) {
                continue;
            }

            $states[$id] = $this->deriveState($logs->get($id, collect()));
        }

        return $states;
    }

    public function isAttendanceConfirmed(Appointment $appointment): bool
    {
        return ActivityLog::query()
            ->where('entity_type', self::ENTITY_TYPE)
            ->where('entity_id', $appointment->getKey())
            ->whereIn('action', self::CONFIRMATION_ACTIONS)
            ->exists();
    }

    private function recordConfirmation(
        Appointment $appointment,
        User $user,
        string $action,
        string $source,
        ?string $note,
        ?string $ipAddress
    ): array {
        return DB::transaction(function () use (
            $appointment,
            $user,
            $action,
            $source,
            $note,
            $ipAddress
        ) {
            $locked = Appointment::whereKey($appointment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($source === self::SOURCE_CUSTOMER) {
                $this->assertCustomerOwnsAppointment($locked, $user);
            }

            if ($locked->status !== 'confirmed') {
                return array_merge($this->stateFor($locked), [
                    'recorded' => false,
                    'already_confirmed' => false,
                    'duplicate' => false,
                    'reason' => 'appointment_not_confirmed',
                    'event_id' => null,
                ]);
            }

            $existing = $this->confirmationEventFor($locked);

            if ($existing) {
                return array_merge($this->stateFor($locked), [
                    'recorded' => false,
                    'already_confirmed' => true,
                    'duplicate' => false,
                    'reason' => 'already_confirmed',
                    'event_id' => $existing->id,
                ]);
            }

            $event = $this->createEvent($locked, $action, $user, [
                'actor_id' => $user->id,
                'actor_name' => $user->full_name,
                'source' => $source,
                'note' => $this->normalizeNote($note),
            ], $ipAddress);

            return array_merge($this->stateFor($locked), [
                'recorded' => true,
                'already_confirmed' => false,
                'duplicate' => false,
                'reason' => null,
                'event_id' => $event->id,
            ]);
        }, 3);
    }

    private function createEvent(
        Appointment $appointment,
        string $action,
        User $user,
        array $details,
        ?string $ipAddress
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'entity_type' => self::ENTITY_TYPE,
            'entity_id' => $appointment->getKey(),
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Customer confirmation is only valid for the account that owns the
     * appointment. Guest appointments (customers.user_id is NULL) and
     * mismatched accounts are rejected before any event is written.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function assertCustomerOwnsAppointment(Appointment $appointment, User $user): void
    {
        $ownerUserId = Customer::query()
            ->whereKey($appointment->customer_id)
            ->value('user_id');

        if ($ownerUserId === null || (int) $ownerUserId !== (int) $user->id) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function confirmationEventFor(Appointment $appointment): ?ActivityLog
    {
        return ActivityLog::query()
            ->where('entity_type', self::ENTITY_TYPE)
            ->where('entity_id', $appointment->getKey())
            ->whereIn('action', self::CONFIRMATION_ACTIONS)
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();
    }

    private function findRecentDuplicateContactAttempt(
        Appointment $appointment,
        string $action,
        User $user,
        ?string $note,
        int $dedupeWindowSeconds
    ): ?ActivityLog {
        if ($dedupeWindowSeconds <= 0) {
            return null;
        }

        $since = Carbon::now()->subSeconds($dedupeWindowSeconds);

        return ActivityLog::query()
            ->where('entity_type', self::ENTITY_TYPE)
            ->where('entity_id', $appointment->getKey())
            ->where('action', $action)
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->first(fn (ActivityLog $log) => $this->noteFromDetails($log->details) === $note);
    }

    private function deriveState(Collection $logs): array
    {
        $state = $this->emptyState();
        $confirmation = null;
        $latestContact = null;
        $attempts = 0;

        foreach ($logs as $log) {
            if (in_array($log->action, self::CONFIRMATION_ACTIONS, true)) {
                $confirmation ??= $log;
                continue;
            }

            if (in_array($log->action, self::CONTACT_ACTIONS, true)) {
                $attempts++;
                $latestContact = $log;
            }
        }

        $state['attempts'] = $attempts;
        $state['history'] = $this->historyFromLogs($logs);

        if ($latestContact) {
            $state['last_outcome'] = $this->outcomeFromAction($latestContact->action);
            $state['last_attempt_at'] = $latestContact->created_at;
            $state['last_attempt_by'] = $latestContact->user_id;
            $state['last_attempt_by_name'] = $this->actorNameFromDetails($latestContact->details);
        }

        if ($confirmation) {
            $state['state'] = self::STATE_CONFIRMED;
            $state['confirmed'] = true;
            $state['source'] = $this->sourceFromAction($confirmation->action);
            $state['confirmed_at'] = $confirmation->created_at;
            $state['confirmed_by'] = $confirmation->user_id;
            $state['confirmed_by_name'] = $this->actorNameFromDetails($confirmation->details);

            return $state;
        }

        if ($latestContact) {
            $state['state'] = $state['last_outcome'];
        }

        return $state;
    }

    private function emptyState(): array
    {
        return [
            'state' => self::STATE_AWAITING,
            'confirmed' => false,
            'source' => null,
            'confirmed_at' => null,
            'confirmed_by' => null,
            'confirmed_by_name' => null,
            'attempts' => 0,
            'last_outcome' => null,
            'last_attempt_at' => null,
            'last_attempt_by' => null,
            'last_attempt_by_name' => null,
            'history' => [],
        ];
    }

    /**
     * Build a display-ready, latest-first history from attendance events.
     *
     * The returned array is for presentation only; state derivation never reads
     * back from it.
     *
     * @return array<int, array<string, mixed>>
     */
    private function historyFromLogs(Collection $logs): array
    {
        return $logs
            ->map(function (ActivityLog $log) {
                $isConfirmation = in_array($log->action, self::CONFIRMATION_ACTIONS, true);

                return [
                    'action' => $log->action,
                    'kind' => $isConfirmation ? 'confirmation' : 'contact',
                    'outcome' => $isConfirmation
                        ? self::STATE_CONFIRMED
                        : $this->outcomeFromAction($log->action),
                    'source' => $this->sourceFromAction($log->action),
                    'actor_id' => $log->user_id,
                    'actor_name' => $this->actorNameFromDetails($log->details),
                    'note' => $this->noteFromDetails($log->details),
                    'at' => $log->created_at,
                ];
            })
            ->reverse()
            ->values()
            ->all();
    }

    private function sourceFromAction(string $action): ?string
    {
        return match ($action) {
            self::ACTION_CONFIRMED_CUSTOMER => self::SOURCE_CUSTOMER,
            self::ACTION_CONFIRMED_RECEPTIONIST => self::SOURCE_RECEPTIONIST,
            default => null,
        };
    }

    private function outcomeFromAction(string $action): ?string
    {
        return match ($action) {
            self::ACTION_CONTACT_NO_ANSWER => self::STATE_NO_ANSWER,
            self::ACTION_CONTACT_UNREACHABLE => self::STATE_UNREACHABLE,
            default => null,
        };
    }

    private function actorNameFromDetails(?string $details): ?string
    {
        $decoded = $this->decodeDetails($details);
        $name = $decoded['actor_name'] ?? null;

        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }

    private function noteFromDetails(?string $details): ?string
    {
        $decoded = $this->decodeDetails($details);
        $note = $decoded['note'] ?? null;

        return is_string($note) && trim($note) !== '' ? trim($note) : null;
    }

    private function decodeDetails(?string $details): array
    {
        if ($details === null || $details === '') {
            return [];
        }

        $decoded = json_decode($details, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeNote(?string $note): ?string
    {
        if ($note === null) {
            return null;
        }

        $note = trim($note);

        return $note === '' ? null : $note;
    }
}
