<?php

namespace App\Services;

use App\Http\Controllers\NotificationController;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Scheduled attendance reminders (T-60 / T-30 / T-15).
 *
 * Zero schema changes: duplicate prevention and reminder history reuse
 * activity_logs. Notifications reuse the existing notifications table through
 * NotificationController::sendTo(). Attendance state is always derived from
 * AttendanceConfirmationService, never from booking approval fields.
 */
class AttendanceReminderService
{
    public const TZ = 'Asia/Manila';

    public const STAGES = [60, 30, 15];

    public const STAGE_DAY = 'day';

    /** Scheduler runs every minute; a small window avoids backfill. */
    public const WINDOW_MINUTES = 6;

    public const AUDIENCE_CUSTOMER = 'customer';

    public const AUDIENCE_RECEPTIONIST = 'receptionist';

    public const ENTITY_TYPE = Appointment::class;

    /**
     * Send every reminder stage currently due. Each appointment is processed
     * inside its own row-locked transaction so overlapping scheduler runs can
     * never create duplicate notifications.
     *
     * @return array{processed:int, skipped:int, sent:int}
     */
    public function sendDueReminders(?Carbon $now = null): array
    {
        $now = ($now ?: Carbon::now(self::TZ))->copy()->timezone(self::TZ);
        $stats = ['processed' => 0, 'skipped' => 0, 'sent' => 0];

        Appointment::query()
            ->where('status', 'confirmed')
            ->whereDate('appointment_date', '>=', $now->copy()->subDay()->toDateString())
            ->whereDate('appointment_date', '<=', $now->copy()->addDay()->toDateString())
            ->orderBy('id')
            ->chunkById(50, function ($appointments) use ($now, &$stats) {
                foreach ($appointments as $appointment) {
                    $result = $this->processAppointment($appointment, $now);
                    $stats['processed']++;
                    if (!($result['eligible'] ?? false)) {
                        $stats['skipped']++;
                    }
                    $stats['sent'] += (int) ($result['sent'] ?? 0);
                }
            });

        return $stats;
    }

    /**
     * Appointment-day reminders are due at exactly 00:00 Asia/Manila on the
     * appointment date. The minute scheduler with a short window tolerates a
     * late cron run without backfilling missed midnight stages.
     *
     * @return array{processed:int, skipped:int, sent:int}
     */
    public function sendDueDayReminders(?Carbon $now = null): array
    {
        $now = ($now ?: Carbon::now(self::TZ))->copy()->timezone(self::TZ);
        $stats = ['processed' => 0, 'skipped' => 0, 'sent' => 0];
        $today = $now->toDateString();
        $dueAt = Carbon::parse($today . ' 00:00:00', self::TZ);

        if ($now->lt($dueAt) || $now->gte($dueAt->copy()->addMinutes(self::WINDOW_MINUTES))) {
            return $stats;
        }

        $receptionists = User::whereHas('roles', fn($q) => $q->where('name', 'receptionist'))
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        Appointment::query()
            ->where('status', 'confirmed')
            ->whereDate('appointment_date', $today)
            ->orderBy('id')
            ->chunkById(50, function ($appointments) use ($now, $dueAt, $receptionists, &$stats) {
                foreach ($appointments as $appointment) {
                    $stats['processed']++;
                    $result = $this->processDayAppointment($appointment, $now, $dueAt, $receptionists);
                    if (!($result['eligible'] ?? false)) {
                        $stats['skipped']++;
                    }
                    $stats['sent'] += (int) ($result['sent'] ?? 0);
                }
            });

        return $stats;
    }

    /**
     * @return array{eligible:bool, sent:int, reason:?string}
     */
    private function processDayAppointment(
        Appointment $appointment,
        Carbon $now,
        Carbon $dueAt,
        $receptionists
    ): array {
        return DB::transaction(function () use ($appointment, $now, $dueAt, $receptionists) {
            $locked = Appointment::whereKey($appointment->getKey())
                ->lockForUpdate()
                ->with(['customer.user'])
                ->firstOrFail();

            if ($locked->status !== 'confirmed') {
                return $this->ineligible('appointment_not_confirmed');
            }
            if (app(AttendanceConfirmationService::class)->isAttendanceConfirmed($locked)) {
                return $this->ineligible('attendance_confirmed');
            }

            $confirmedAt = $locked->confirmed_at
                ? ($locked->confirmed_at instanceof Carbon
                    ? $locked->confirmed_at->copy()->timezone(self::TZ)
                    : Carbon::parse($locked->confirmed_at, self::TZ))
                : null;
            if ($confirmedAt && $confirmedAt->gt($dueAt)) {
                return $this->ineligible('approved_after_due');
            }

            $start = Carbon::parse(
                $locked->appointment_date->format('Y-m-d') . ' ' . $locked->start_time,
                self::TZ
            );
            $customerName = $locked->client_name ?: 'Customer';
            $dateLabel = $start->format('D, M j, Y');
            $timeLabel = $start->format('g:i A');
            $isGuest = $locked->customer?->isGuest() ?? true;
            $reason = $isGuest
                ? 'Guest booking — contact the guest at ' . ($locked->client_phone ?: 'the number on file') . '.'
                : 'Appointment-day reminder. Attendance has not been confirmed yet.';

            $sent = 0;
            $customerUser = $isGuest ? null : $locked->customer?->user;

            if ($customerUser) {
                if ($this->sendOnce($locked, self::STAGE_DAY, self::AUDIENCE_CUSTOMER, $customerUser, [
                    'title' => "Appointment today — {$dateLabel} at {$timeLabel}",
                    'message' => "Your appointment with Spa Alexandria is today at {$timeLabel}. {$reason}",
                    'url' => $this->relativeUrl(route('customer.bookings')) . '#booking-' . $locked->getKey(),
                    'reason' => $reason,
                ])) {
                    $sent++;
                }
            } else {
                foreach ($receptionists as $receptionist) {
                    if ($this->sendOnce($locked, self::STAGE_DAY, self::AUDIENCE_RECEPTIONIST, $receptionist, [
                        'title' => "Appointment today — {$customerName} at {$timeLabel}",
                        'message' => "Guest appointment today at {$timeLabel}. {$reason}",
                        'url' => $this->relativeUrl(route('receptionist.active')) . '#appointment-' . $locked->getKey(),
                        'reason' => $reason,
                    ])) {
                        $sent++;
                    }
                }
            }

            return ['eligible' => true, 'sent' => $sent, 'reason' => null];
        }, 3);
    }

    /**
     * @return array{eligible:bool, sent:int, reason:?string}
     */
    public function processAppointment(Appointment $appointment, Carbon $now): array
    {
        return DB::transaction(function () use ($appointment, $now) {
            $locked = Appointment::whereKey($appointment->getKey())
                ->lockForUpdate()
                ->with(['customer.user', 'services'])
                ->firstOrFail();

            if ($locked->status !== 'confirmed') {
                return $this->ineligible('appointment_not_confirmed');
            }

            $start = Carbon::parse(
                $locked->appointment_date->format('Y-m-d') . ' ' . $locked->start_time,
                self::TZ
            );

            // Never remind for a session that has already started, and never
            // remind once attendance has been confirmed by either side.
            if ($start->lte($now)) {
                return $this->ineligible('appointment_started');
            }
            if (app(AttendanceConfirmationService::class)->isAttendanceConfirmed($locked)) {
                return $this->ineligible('attendance_confirmed');
            }

            $confirmedAt = $locked->confirmed_at
                ? ($locked->confirmed_at instanceof Carbon
                    ? $locked->confirmed_at->copy()->timezone(self::TZ)
                    : Carbon::parse($locked->confirmed_at, self::TZ))
                : null;
            $customerName = $locked->client_name ?: 'Customer';
            $dateLabel = $start->format('D, M j, Y');
            $timeLabel = $start->format('g:i A');
            $isGuest = $locked->customer?->isGuest() ?? false;
            $phone = $locked->client_phone;

            $receptionists = User::whereHas('roles', fn($q) => $q->where('name', 'receptionist'))
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            $sent = 0;

            foreach (self::STAGES as $stage) {
                $dueAt = $start->copy()->subMinutes($stage);

                // No backfill: skip if this stage was not yet due when the
                // booking was approved, or if its processing window has passed.
                if ($now->lt($dueAt) || $now->gte($dueAt->copy()->addMinutes(self::WINDOW_MINUTES))) {
                    continue;
                }
                if ($confirmedAt && $confirmedAt->gt($dueAt)) {
                    continue;
                }

                $stageLabel = $this->stageLabel($stage);
                $reason = $isGuest
                    ? 'Guest booking — contact the guest at ' . ($phone ?: 'the number on file') . '.'
                    : 'Attendance has not been confirmed yet.';

                // Registered customers get an in-app reminder; guests cannot.
                $customerUser = $isGuest ? null : $locked->customer?->user;
                if ($customerUser) {
                    if ($this->sendOnce($locked, $stage, self::AUDIENCE_CUSTOMER, $customerUser, [
                        'title' => "Appointment reminder — {$stageLabel}",
                        'message' => "Your appointment on {$dateLabel} at {$timeLabel} is in {$stageLabel}. Please arrive on time.",
                        'url' => $this->relativeUrl(route('customer.bookings')) . '#booking-' . $locked->getKey(),
                        'reason' => $reason,
                    ])) {
                        $sent++;
                    }
                }

                foreach ($receptionists as $receptionist) {
                    if ($this->sendOnce($locked, $stage, self::AUDIENCE_RECEPTIONIST, $receptionist, [
                        'title' => "Attendance reminder — {$customerName} ({$stageLabel})",
                        'message' => "{$customerName}'s appointment is {$dateLabel} at {$timeLabel} ({$stageLabel} away). {$reason}",
                        'url' => $this->relativeUrl(route('receptionist.active')) . '#appointment-' . $locked->getKey(),
                        'reason' => $reason,
                    ])) {
                        $sent++;
                    }
                }
            }

            return ['eligible' => true, 'sent' => $sent, 'reason' => null];
        }, 3);
    }

    /**
     * Send one reminder and record its event atomically. If notification
     * creation fails the transaction rolls back, so no success event is kept.
     */
    private function sendOnce(Appointment $appointment, $stage, string $audience, User $recipient, array $payload): bool
    {
        $action = $this->actionFor($stage, $audience);

        $alreadySent = ActivityLog::query()
            ->where('entity_type', self::ENTITY_TYPE)
            ->where('entity_id', $appointment->getKey())
            ->where('action', $action)
            ->where('user_id', $recipient->getKey())
            ->exists();

        if ($alreadySent) {
            return false;
        }

        NotificationController::sendTo(
            $recipient,
            $payload['title'],
            $payload['message'],
            'attendance',
            'warning',
            $payload['url'],
            'Open Appointment',
            [
                'stage' => $stage,
                'appointment_id' => $appointment->getKey(),
            ]
        );

        ActivityLog::create([
            'user_id' => $recipient->getKey(),
            'action' => $action,
            'entity_type' => self::ENTITY_TYPE,
            'entity_id' => $appointment->getKey(),
            'details' => json_encode([
                'stage' => $stage,
                'audience' => $audience,
                'reason' => $payload['reason'],
                'appointment_date' => $appointment->appointment_date->format('Y-m-d'),
                'start_time' => substr((string) $appointment->start_time, 0, 5),
            ], JSON_UNESCAPED_UNICODE),
        ]);

        return true;
    }

    /**
     * Unresolved attendance follow-ups for the receptionist Action Needed panel.
     * Derived from appointments + attendance events only: reading or dismissing
     * a notification never changes this list.
     *
     * @return array<int, array<string, mixed>>
     */
    public function followUps(?Carbon $now = null): array
    {
        $now = ($now ?: Carbon::now(self::TZ))->copy()->timezone(self::TZ);
        $today = $now->toDateString();
        $attendance = app(AttendanceConfirmationService::class);

        $appointments = Appointment::query()
            ->where('status', 'confirmed')
            ->whereDate('appointment_date', $today)
            ->with(['customer', 'services', 'staff', 'assignedStaff', 'room'])
            ->orderBy('start_time')
            ->get();

        return $appointments
            ->filter(function (Appointment $appointment) use ($now, $attendance) {
                $start = Carbon::parse(
                    $appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->start_time,
                    self::TZ
                );

                return $start->lte($now->copy()->addMinutes(60))
                    && $start->gte($now->copy()->subHours(2))
                    && !$attendance->isAttendanceConfirmed($appointment);
            })
            ->map(function (Appointment $appointment) use ($now, $attendance) {
                $state = $attendance->stateFor($appointment);
                $start = Carbon::parse(
                    $appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->start_time,
                    self::TZ
                );
                $latestStage = $this->latestReminderStage($appointment);

                return [
                    'appointment_id' => $appointment->getKey(),
                    'customer' => $appointment->client_name ?: 'Customer',
                    'time' => $start->format('g:i A'),
                    'stage' => $latestStage ? $this->stageLabel($latestStage) : 'No reminder sent',
                    'attendance_state' => $state['state'] ?? 'awaiting',
                    'attendance_label' => match ($state['state'] ?? 'awaiting') {
                        'no_answer' => 'No answer',
                        'unreachable' => 'Unable to reach',
                        default => 'Awaiting confirmation',
                    },
                    'needs_call' => $start->lt($now)
                        || ($state['state'] ?? 'awaiting') === 'no_answer'
                        || ($state['state'] ?? 'awaiting') === 'unreachable'
                        || ($appointment->customer?->isGuest() ?? false),
                    'action_url' => $this->relativeUrl(route('receptionist.active')) . '#appointment-' . $appointment->getKey(),
                ];
            })
            ->values()
            ->all();
    }

    private function latestReminderStage(Appointment $appointment): ?int
    {
        $actions = [];
        foreach (self::STAGES as $stage) {
            $actions[] = $this->actionFor($stage, self::AUDIENCE_RECEPTIONIST);
        }

        $log = ActivityLog::query()
            ->where('entity_type', self::ENTITY_TYPE)
            ->where('entity_id', $appointment->getKey())
            ->whereIn('action', $actions)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (!$log) {
            return null;
        }

        $details = json_decode($log->details, true) ?: [];

        return isset($details['stage']) && is_numeric($details['stage']) ? (int) $details['stage'] : null;
    }

    private function actionFor($stage, string $audience): string
    {
        return $stage === self::STAGE_DAY
            ? 'attendance_reminder_day_' . $audience
            : 'attendance_reminder_t' . $stage . '_' . $audience;
    }

    private function stageLabel($stage): string
    {
        return $stage === self::STAGE_DAY ? 'Appointment day' : $stage . ' minutes';
    }

    private function relativeUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        return $path ?: $url;
    }

    private function ineligible(string $reason): array
    {
        return ['eligible' => false, 'sent' => 0, 'reason' => $reason];
    }
}
