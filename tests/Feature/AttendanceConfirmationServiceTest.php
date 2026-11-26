<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use App\Services\AttendanceConfirmationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Phase 1 tests for the attendance event foundation.
 *
 * These tests run on SQLite (phpunit.xml uses :memory:). SQLite does not
 * implement SELECT ... FOR UPDATE, so these tests prove sequential idempotency
 * and state derivation only. They do not prove MySQL InnoDB row-lock behaviour;
 * that requires the MySQL staging concurrency tests described in the plan.
 */
class AttendanceConfirmationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceConfirmationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AttendanceConfirmationService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_state_is_awaiting_when_no_events_exist(): void
    {
        $appointment = $this->makeAppointment();

        $state = $this->service->stateFor($appointment);

        $this->assertSame(AttendanceConfirmationService::STATE_AWAITING, $state['state']);
        $this->assertFalse($state['confirmed']);
        $this->assertSame(0, $state['attempts']);
        $this->assertNull($state['source']);
        $this->assertNull($state['confirmed_at']);
        $this->assertNull($state['last_outcome']);
    }

    public function test_customer_confirmation_records_event_and_confirmed_state(): void
    {
        $appointment = $this->makeAppointment();
        $customerUser = $appointment->customer->user;

        $result = $this->service->confirmByCustomer(
            $appointment,
            $customerUser,
            'Coming with a friend',
            '127.0.0.1'
        );

        $this->assertTrue($result['recorded']);
        $this->assertFalse($result['already_confirmed']);
        $this->assertSame(AttendanceConfirmationService::STATE_CONFIRMED, $result['state']);
        $this->assertSame(AttendanceConfirmationService::SOURCE_CUSTOMER, $result['source']);
        $this->assertSame($customerUser->id, $result['confirmed_by']);
        $this->assertSame($customerUser->full_name, $result['confirmed_by_name']);
        $this->assertNotNull($result['confirmed_at']);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $customerUser->id,
            'action' => AttendanceConfirmationService::ACTION_CONFIRMED_CUSTOMER,
            'entity_type' => Appointment::class,
            'entity_id' => $appointment->id,
            'ip_address' => '127.0.0.1',
        ]);

        $event = ActivityLog::query()
            ->where('action', AttendanceConfirmationService::ACTION_CONFIRMED_CUSTOMER)
            ->where('entity_id', $appointment->id)
            ->firstOrFail();

        $details = json_decode($event->details, true);

        $this->assertSame('Coming with a friend', $details['note']);
        $this->assertSame(AttendanceConfirmationService::SOURCE_CUSTOMER, $details['source']);
        $this->assertSame($customerUser->id, $details['actor_id']);
        $this->assertSame($customerUser->full_name, $details['actor_name']);
    }

    public function test_receptionist_confirmation_records_event_and_confirmed_state(): void
    {
        $appointment = $this->makeAppointment();
        $receptionist = $this->makeReceptionist();

        $result = $this->service->confirmByReceptionist($appointment, $receptionist, 'Confirmed by phone');

        $this->assertTrue($result['recorded']);
        $this->assertSame(AttendanceConfirmationService::STATE_CONFIRMED, $result['state']);
        $this->assertSame(AttendanceConfirmationService::SOURCE_RECEPTIONIST, $result['source']);
        $this->assertSame($receptionist->id, $result['confirmed_by']);
        $this->assertSame($receptionist->full_name, $result['confirmed_by_name']);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $receptionist->id,
            'action' => AttendanceConfirmationService::ACTION_CONFIRMED_RECEPTIONIST,
            'entity_type' => Appointment::class,
            'entity_id' => $appointment->id,
        ]);
    }

    public function test_no_answer_attempt_records_no_answer_state(): void
    {
        $appointment = $this->makeAppointment();
        $receptionist = $this->makeReceptionist();

        $result = $this->service->recordNoAnswer($appointment, $receptionist, 'No answer after two rings');

        $this->assertTrue($result['recorded']);
        $this->assertFalse($result['confirmed']);
        $this->assertSame(AttendanceConfirmationService::STATE_NO_ANSWER, $result['state']);
        $this->assertSame(1, $result['attempts']);
        $this->assertSame(AttendanceConfirmationService::STATE_NO_ANSWER, $result['last_outcome']);
        $this->assertSame($receptionist->id, $result['last_attempt_by']);
        $this->assertSame($receptionist->full_name, $result['last_attempt_by_name']);
        $this->assertNotNull($result['last_attempt_at']);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $receptionist->id,
            'action' => AttendanceConfirmationService::ACTION_CONTACT_NO_ANSWER,
            'entity_type' => Appointment::class,
            'entity_id' => $appointment->id,
        ]);
    }

    public function test_unable_to_reach_attempt_records_unreachable_state(): void
    {
        $appointment = $this->makeAppointment();
        $receptionist = $this->makeReceptionist();

        $result = $this->service->recordUnableToReach($appointment, $receptionist, 'Phone switched off');

        $this->assertTrue($result['recorded']);
        $this->assertFalse($result['confirmed']);
        $this->assertSame(AttendanceConfirmationService::STATE_UNREACHABLE, $result['state']);
        $this->assertSame(1, $result['attempts']);
        $this->assertSame(AttendanceConfirmationService::STATE_UNREACHABLE, $result['last_outcome']);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $receptionist->id,
            'action' => AttendanceConfirmationService::ACTION_CONTACT_UNREACHABLE,
            'entity_type' => Appointment::class,
            'entity_id' => $appointment->id,
        ]);
    }

    public function test_confirmation_takes_precedence_over_contact_attempts(): void
    {
        $appointment = $this->makeAppointment();
        $receptionist = $this->makeReceptionist();
        $customerUser = $appointment->customer->user;

        $this->service->recordNoAnswer($appointment, $receptionist, 'First attempt');
        $result = $this->service->confirmByCustomer($appointment, $customerUser);

        $this->assertSame(AttendanceConfirmationService::STATE_CONFIRMED, $result['state']);
        $this->assertTrue($result['confirmed']);
        $this->assertSame(AttendanceConfirmationService::SOURCE_CUSTOMER, $result['source']);
        $this->assertSame(1, $result['attempts']);
        $this->assertSame(AttendanceConfirmationService::STATE_NO_ANSWER, $result['last_outcome']);
    }

    public function test_latest_contact_attempt_determines_displayed_outcome(): void
    {
        $appointment = $this->makeAppointment();
        $receptionist = $this->makeReceptionist();

        Carbon::setTestNow('2026-01-01 09:00:00');
        $this->service->recordNoAnswer($appointment, $receptionist, 'First attempt');

        Carbon::setTestNow('2026-01-01 09:10:00');
        $result = $this->service->recordUnableToReach($appointment, $receptionist, 'Second attempt');

        $this->assertSame(AttendanceConfirmationService::STATE_UNREACHABLE, $result['state']);
        $this->assertSame(2, $result['attempts']);
        $this->assertSame(AttendanceConfirmationService::STATE_UNREACHABLE, $result['last_outcome']);
    }

    public function test_multiple_contact_attempts_are_counted(): void
    {
        $appointment = $this->makeAppointment();
        $receptionist = $this->makeReceptionist();

        Carbon::setTestNow('2026-01-01 09:00:00');
        $this->service->recordNoAnswer($appointment, $receptionist, 'First attempt');

        Carbon::setTestNow('2026-01-01 09:10:00');
        $this->service->recordUnableToReach($appointment, $receptionist, 'Second attempt');

        Carbon::setTestNow('2026-01-01 09:20:00');
        $result = $this->service->recordNoAnswer($appointment, $receptionist, 'Third attempt');

        $this->assertSame(3, $result['attempts']);
        $this->assertSame(AttendanceConfirmationService::STATE_NO_ANSWER, $result['last_outcome']);
    }

    public function test_duplicate_confirmation_is_idempotent(): void
    {
        $appointment = $this->makeAppointment();
        $customerUser = $appointment->customer->user;
        $receptionist = $this->makeReceptionist();

        $first = $this->service->confirmByCustomer($appointment, $customerUser);
        $second = $this->service->confirmByReceptionist($appointment, $receptionist);

        $this->assertTrue($first['recorded']);
        $this->assertFalse($second['recorded']);
        $this->assertTrue($second['already_confirmed']);
        $this->assertSame($first['event_id'], $second['event_id']);
        $this->assertSame(AttendanceConfirmationService::SOURCE_CUSTOMER, $second['source']);

        $this->assertSame(
            1,
            ActivityLog::query()
                ->where('entity_type', Appointment::class)
                ->where('entity_id', $appointment->id)
                ->whereIn('action', AttendanceConfirmationService::CONFIRMATION_ACTIONS)
                ->count()
        );
    }

    public function test_confirmation_does_not_change_booking_or_workflow_fields(): void
    {
        $appointment = $this->makeAppointment([
            'cancellation_reason' => 'original cancellation',
            'no_show_reason' => 'original no-show',
        ]);

        $before = [
            'status' => $appointment->status,
            'confirmed_at' => $appointment->confirmed_at->toDateTimeString(),
            'confirmed_by' => $appointment->confirmed_by,
            'cancellation_reason' => $appointment->cancellation_reason,
            'no_show_reason' => $appointment->no_show_reason,
        ];

        $this->service->confirmByCustomer($appointment, $appointment->customer->user);

        $appointment->refresh();

        $this->assertSame($before['status'], $appointment->status);
        $this->assertSame($before['confirmed_at'], $appointment->confirmed_at->toDateTimeString());
        $this->assertSame($before['confirmed_by'], $appointment->confirmed_by);
        $this->assertSame($before['cancellation_reason'], $appointment->cancellation_reason);
        $this->assertSame($before['no_show_reason'], $appointment->no_show_reason);
    }

    public function test_contact_attempt_does_not_change_booking_or_workflow_fields(): void
    {
        $appointment = $this->makeAppointment([
            'cancellation_reason' => 'original cancellation',
            'no_show_reason' => 'original no-show',
        ]);
        $receptionist = $this->makeReceptionist();

        $before = [
            'status' => $appointment->status,
            'confirmed_at' => $appointment->confirmed_at->toDateTimeString(),
            'confirmed_by' => $appointment->confirmed_by,
            'cancellation_reason' => $appointment->cancellation_reason,
            'no_show_reason' => $appointment->no_show_reason,
        ];

        $this->service->recordNoAnswer($appointment, $receptionist, 'No answer');

        $appointment->refresh();

        $this->assertSame($before['status'], $appointment->status);
        $this->assertSame($before['confirmed_at'], $appointment->confirmed_at->toDateTimeString());
        $this->assertSame($before['confirmed_by'], $appointment->confirmed_by);
        $this->assertSame($before['cancellation_reason'], $appointment->cancellation_reason);
        $this->assertSame($before['no_show_reason'], $appointment->no_show_reason);
    }

    public function test_bulk_state_loading_returns_correct_results_for_multiple_appointments(): void
    {
        $awaiting = $this->makeAppointment();
        $confirmed = $this->makeAppointment();
        $contacted = $this->makeAppointment();

        $receptionist = $this->makeReceptionist();

        $this->service->confirmByCustomer($confirmed, $confirmed->customer->user);
        $this->service->recordNoAnswer($contacted, $receptionist);

        $states = $this->service->statesFor(collect([$awaiting, $confirmed, $contacted]));

        $this->assertCount(3, $states);
        $this->assertSame(AttendanceConfirmationService::STATE_AWAITING, $states[$awaiting->id]['state']);
        $this->assertSame(AttendanceConfirmationService::STATE_CONFIRMED, $states[$confirmed->id]['state']);
        $this->assertSame(AttendanceConfirmationService::STATE_NO_ANSWER, $states[$contacted->id]['state']);
    }

    public function test_bulk_state_loading_handles_empty_collection(): void
    {
        $this->assertSame([], $this->service->statesFor(collect()));
        $this->assertSame([], $this->service->statesFor(new Collection()));
    }

    public function test_event_records_appointment_identity_and_responsible_user(): void
    {
        $appointment = $this->makeAppointment();
        $receptionist = $this->makeReceptionist();

        $result = $this->service->recordUnableToReach($appointment, $receptionist, 'Phone switched off');
        $event = ActivityLog::findOrFail($result['event_id']);

        $this->assertSame(Appointment::class, $event->entity_type);
        $this->assertSame($appointment->id, $event->entity_id);
        $this->assertSame($receptionist->id, $event->user_id);
        $this->assertNotNull($event->created_at);

        $details = json_decode($event->details, true);

        $this->assertSame(AttendanceConfirmationService::STATE_UNREACHABLE, $details['outcome']);
        $this->assertSame('Phone switched off', $details['note']);
        $this->assertSame($receptionist->id, $details['actor_id']);
        $this->assertSame($receptionist->full_name, $details['actor_name']);
    }

    public function test_identical_contact_attempt_inside_dedupe_window_is_not_recorded_twice(): void
    {
        $appointment = $this->makeAppointment();
        $receptionist = $this->makeReceptionist();

        Carbon::setTestNow('2026-01-01 09:00:00');

        $first = $this->service->recordNoAnswer($appointment, $receptionist, 'No answer');
        $second = $this->service->recordNoAnswer($appointment, $receptionist, 'No answer');

        $this->assertTrue($first['recorded']);
        $this->assertFalse($second['recorded']);
        $this->assertTrue($second['duplicate']);
        $this->assertSame($first['event_id'], $second['event_id']);

        $this->assertSame(1, $this->contactAttemptCount($appointment));

        Carbon::setTestNow('2026-01-01 09:00:10');

        $third = $this->service->recordNoAnswer($appointment, $receptionist, 'No answer');

        $this->assertTrue($third['recorded']);
        $this->assertSame(2, $this->contactAttemptCount($appointment));
    }

    public function test_confirmation_is_rejected_when_appointment_is_not_confirmed(): void
    {
        $appointment = $this->makeAppointment(['status' => 'pending']);

        $result = $this->service->confirmByCustomer($appointment, $appointment->customer->user);

        $this->assertFalse($result['recorded']);
        $this->assertSame('appointment_not_confirmed', $result['reason']);
        $this->assertSame(0, ActivityLog::count());
    }

    public function test_contact_attempt_is_rejected_after_attendance_is_confirmed(): void
    {
        $appointment = $this->makeAppointment();
        $receptionist = $this->makeReceptionist();

        $this->service->confirmByCustomer($appointment, $appointment->customer->user);
        $result = $this->service->recordNoAnswer($appointment, $receptionist);

        $this->assertFalse($result['recorded']);
        $this->assertSame('already_confirmed', $result['reason']);
        $this->assertSame(1, ActivityLog::count());
    }

    public function test_is_attendance_confirmed_reflects_confirmation_events(): void
    {
        $appointment = $this->makeAppointment();
        $receptionist = $this->makeReceptionist();

        $this->assertFalse($this->service->isAttendanceConfirmed($appointment));

        $this->service->confirmByReceptionist($appointment, $receptionist);

        $this->assertTrue($this->service->isAttendanceConfirmed($appointment));
    }

    public function test_appointment_owner_can_confirm_own_approved_appointment(): void
    {
        $appointment = $this->makeAppointment();
        $owner = $appointment->customer->user;

        $result = $this->service->confirmByCustomer($appointment, $owner);

        $this->assertTrue($result['recorded']);
        $this->assertSame(AttendanceConfirmationService::STATE_CONFIRMED, $result['state']);
        $this->assertSame(AttendanceConfirmationService::SOURCE_CUSTOMER, $result['source']);
        $this->assertSame($owner->id, $result['confirmed_by']);
        $this->assertSame(1, $this->confirmationEventCount($appointment));
    }

    public function test_different_customer_cannot_confirm_appointment(): void
    {
        $appointment = $this->makeAppointment();
        $otherUser = User::factory()->create();

        try {
            $this->service->confirmByCustomer($appointment, $otherUser);
            $this->fail('Expected customer confirmation by a non-owner to be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame(0, $this->confirmationEventCount($appointment));
    }

    public function test_guest_appointment_cannot_be_confirmed_through_customer_method(): void
    {
        $appointment = $this->makeGuestAppointment();
        $unrelatedUser = User::factory()->create();

        try {
            $this->service->confirmByCustomer($appointment, $unrelatedUser);
            $this->fail('Expected guest appointment to reject customer confirmation.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame(0, $this->confirmationEventCount($appointment));
    }

    public function test_unauthorized_customer_confirmation_creates_no_event(): void
    {
        $appointment = $this->makeAppointment();
        $otherUser = User::factory()->create();

        try {
            $this->service->confirmByCustomer($appointment, $otherUser, 'Attempted by non-owner');
            $this->fail('Expected customer confirmation by a non-owner to be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame(0, ActivityLog::count());
        $this->assertSame(
            AttendanceConfirmationService::STATE_AWAITING,
            $this->service->stateFor($appointment)['state']
        );
    }

    public function test_receptionist_confirmation_remains_unchanged_for_owned_appointments(): void
    {
        $appointment = $this->makeAppointment();
        $receptionist = $this->makeReceptionist();

        $result = $this->service->confirmByReceptionist($appointment, $receptionist, 'Confirmed by phone');

        $this->assertTrue($result['recorded']);
        $this->assertSame(AttendanceConfirmationService::STATE_CONFIRMED, $result['state']);
        $this->assertSame(AttendanceConfirmationService::SOURCE_RECEPTIONIST, $result['source']);
        $this->assertSame(1, $this->confirmationEventCount($appointment));
    }

    public function test_receptionist_can_confirm_guest_appointment(): void
    {
        $appointment = $this->makeGuestAppointment();
        $receptionist = $this->makeReceptionist();

        $result = $this->service->confirmByReceptionist($appointment, $receptionist, 'Confirmed by phone');

        $this->assertTrue($result['recorded']);
        $this->assertSame(AttendanceConfirmationService::STATE_CONFIRMED, $result['state']);
        $this->assertSame(AttendanceConfirmationService::SOURCE_RECEPTIONIST, $result['source']);
        $this->assertSame(1, $this->confirmationEventCount($appointment));
    }

    private function makeAppointment(array $overrides = []): Appointment
    {
        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.' . uniqid() . '@example.com',
            'phone_number' => '09171234567',
        ]);

        $approver = User::factory()->create();

        return Appointment::create(array_merge([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1500,
            'confirmed_at' => Carbon::now()->subDay(),
            'confirmed_by' => $approver->id,
        ], $overrides));
    }

    private function makeGuestAppointment(array $overrides = []): Appointment
    {
        $customer = Customer::create([
            'user_id' => null,
            'first_name' => 'Guest',
            'last_name' => 'Visitor',
            'email' => null,
            'phone_number' => '09170000000',
        ]);

        $approver = User::factory()->create();

        return Appointment::create(array_merge([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1500,
            'confirmed_at' => Carbon::now()->subDay(),
            'confirmed_by' => $approver->id,
        ], $overrides));
    }

    private function makeReceptionist(): User
    {
        return User::factory()->create([
            'first_name' => 'Rita',
            'last_name' => 'Reception',
        ]);
    }

    private function contactAttemptCount(Appointment $appointment): int
    {
        return ActivityLog::query()
            ->where('entity_type', Appointment::class)
            ->where('entity_id', $appointment->id)
            ->whereIn('action', AttendanceConfirmationService::CONTACT_ACTIONS)
            ->count();
    }

    private function confirmationEventCount(Appointment $appointment): int
    {
        return ActivityLog::query()
            ->where('entity_type', Appointment::class)
            ->where('entity_id', $appointment->id)
            ->whereIn('action', AttendanceConfirmationService::CONFIRMATION_ACTIONS)
            ->count();
    }
}
