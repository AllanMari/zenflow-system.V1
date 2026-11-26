<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceConfirmationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2 receptionist attendance confirmation tests.
 *
 * These tests run on SQLite (:memory:). They prove route authorization,
 * validation, event recording, and state/history rendering. They do not prove
 * MySQL InnoDB row-lock concurrency.
 */
class ReceptionistAttendanceConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'receptionist']);

        $this->receptionist = User::factory()->create([
            'first_name' => 'Rita',
            'last_name' => 'Reception',
        ]);
        $this->receptionist->roles()->attach($role);
    }

    public function test_receptionist_can_confirm_attendance_by_phone(): void
    {
        $appointment = $this->makeAppointment();
        $before = $this->bookingSnapshot($appointment);

        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.attendance.confirm', $appointment),
            ['note' => 'Customer confirmed by phone.']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->receptionist->id,
            'action' => AttendanceConfirmationService::ACTION_CONFIRMED_RECEPTIONIST,
            'entity_type' => Appointment::class,
            'entity_id' => $appointment->id,
        ]);

        $details = $this->detailsFor($appointment, AttendanceConfirmationService::ACTION_CONFIRMED_RECEPTIONIST);

        $this->assertSame('receptionist', $details['source']);
        $this->assertSame($this->receptionist->id, $details['actor_id']);
        $this->assertSame($this->receptionist->full_name, $details['actor_name']);
        $this->assertSame('Customer confirmed by phone.', $details['note']);

        $this->assertBookingUnchanged($appointment, $before);
    }

    public function test_receptionist_confirmation_records_authenticated_user_not_request_supplied_id(): void
    {
        $appointment = $this->makeAppointment();
        $otherUser = User::factory()->create();

        $this->actingAs($this->receptionist)->post(
            route('receptionist.attendance.confirm', $appointment),
            ['receptionist_id' => $otherUser->id, 'user_id' => $otherUser->id]
        )->assertSessionHas('success');

        $event = ActivityLog::query()
            ->where('entity_id', $appointment->id)
            ->where('action', AttendanceConfirmationService::ACTION_CONFIRMED_RECEPTIONIST)
            ->firstOrFail();

        $this->assertSame($this->receptionist->id, $event->user_id);
    }

    public function test_receptionist_can_confirm_guest_attendance_by_phone(): void
    {
        $appointment = $this->makeGuestAppointment();

        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.attendance.confirm', $appointment),
            ['note' => 'Guest confirmed by phone.']
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->receptionist->id,
            'action' => AttendanceConfirmationService::ACTION_CONFIRMED_RECEPTIONIST,
            'entity_id' => $appointment->id,
        ]);
    }

    public function test_receptionist_can_record_no_answer_with_optional_note(): void
    {
        $appointment = $this->makeAppointment();
        $before = $this->bookingSnapshot($appointment);

        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.attendance.no-answer', $appointment),
            ['note' => 'Called twice, no answer.']
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->receptionist->id,
            'action' => AttendanceConfirmationService::ACTION_CONTACT_NO_ANSWER,
            'entity_id' => $appointment->id,
        ]);

        $details = $this->detailsFor($appointment, AttendanceConfirmationService::ACTION_CONTACT_NO_ANSWER);

        $this->assertSame('no_answer', $details['outcome']);
        $this->assertSame('Called twice, no answer.', $details['note']);

        $this->assertBookingUnchanged($appointment, $before);
    }

    public function test_receptionist_can_record_unable_to_reach_with_optional_note(): void
    {
        $appointment = $this->makeAppointment();
        $before = $this->bookingSnapshot($appointment);

        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.attendance.unable-to-reach', $appointment),
            ['note' => 'Phone switched off.']
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->receptionist->id,
            'action' => AttendanceConfirmationService::ACTION_CONTACT_UNREACHABLE,
            'entity_id' => $appointment->id,
        ]);

        $details = $this->detailsFor($appointment, AttendanceConfirmationService::ACTION_CONTACT_UNREACHABLE);

        $this->assertSame('unreachable', $details['outcome']);
        $this->assertSame('Phone switched off.', $details['note']);

        $this->assertBookingUnchanged($appointment, $before);
    }

    public function test_notes_are_optional_for_contact_attempts(): void
    {
        $appointment = $this->makeAppointment();

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.attendance.no-answer', $appointment))
            ->assertSessionHas('success');

        $details = $this->detailsFor($appointment, AttendanceConfirmationService::ACTION_CONTACT_NO_ANSWER);

        $this->assertArrayHasKey('note', $details);
        $this->assertNull($details['note']);
    }

    public function test_contact_history_and_state_are_displayed_on_active_page(): void
    {
        $appointment = $this->makeAppointment([
            'appointment_date' => Carbon::tomorrow()->toDateString(),
        ]);

        $this->actingAs($this->receptionist)->post(
            route('receptionist.attendance.no-answer', $appointment),
            ['note' => 'Called twice, no answer.']
        )->assertSessionHas('success');

        $response = $this->actingAs($this->receptionist)->get(route('receptionist.active'));

        $response->assertOk();
        $response->assertSee('Contact history');
        $response->assertSee('Called twice, no answer.');
        $response->assertSee('No answer');
        $response->assertSee('Confirm Attendance by Phone');
    }

    public function test_confirmed_state_and_history_are_displayed_on_active_page(): void
    {
        $appointment = $this->makeAppointment([
            'appointment_date' => Carbon::tomorrow()->toDateString(),
        ]);

        $this->actingAs($this->receptionist)->post(
            route('receptionist.attendance.confirm', $appointment),
            ['note' => 'Confirmed by phone.']
        )->assertSessionHas('success');

        $response = $this->actingAs($this->receptionist)->get(route('receptionist.active'));

        $response->assertOk();
        $response->assertSee('Confirmed via Receptionist (phone)');
        $response->assertSee('Attendance confirmed');
        $response->assertSee('Confirmed by phone.');
    }

    public function test_unauthorized_roles_cannot_record_attendance(): void
    {
        $appointment = $this->makeAppointment();

        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $customerUser = User::factory()->create();
        $customerUser->roles()->attach($customerRole);

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staffUser = User::factory()->create();
        $staffUser->roles()->attach($staffRole);

        $this->actingAs($customerUser)
            ->post(route('receptionist.attendance.confirm', $appointment))
            ->assertStatus(403);

        $this->actingAs($staffUser)
            ->post(route('receptionist.attendance.no-answer', $appointment))
            ->assertStatus(403);

        $this->actingAs($staffUser)
            ->post(route('receptionist.attendance.unable-to-reach', $appointment))
            ->assertStatus(403);

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_attendance_cannot_be_recorded_for_ineligible_appointments(): void
    {
        foreach (['pending', 'cancelled', 'completed', 'no_show'] as $status) {
            $appointment = $this->makeAppointment(['status' => $status]);

            $response = $this->actingAs($this->receptionist)->post(
                route('receptionist.attendance.confirm', $appointment)
            );

            $response->assertRedirect();
            $response->assertSessionHas('error');

            $this->assertSame(
                0,
                ActivityLog::query()->where('entity_id', $appointment->id)->count(),
                "Attendance event was created for a {$status} appointment."
            );
        }
    }

    public function test_confirmation_prevents_further_contact_attempts(): void
    {
        $appointment = $this->makeAppointment();

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.attendance.confirm', $appointment))
            ->assertSessionHas('success');

        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.attendance.no-answer', $appointment)
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame(1, ActivityLog::query()->where('entity_id', $appointment->id)->count());
        $this->assertSame(
            0,
            ActivityLog::query()
                ->where('entity_id', $appointment->id)
                ->whereIn('action', AttendanceConfirmationService::CONTACT_ACTIONS)
                ->count()
        );
        $this->assertTrue(app(AttendanceConfirmationService::class)->isAttendanceConfirmed($appointment));
    }

    public function test_repeated_confirmation_is_idempotent(): void
    {
        $appointment = $this->makeAppointment();

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.attendance.confirm', $appointment))
            ->assertSessionHas('success');

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.attendance.confirm', $appointment))
            ->assertSessionHas('error');

        $this->assertSame(
            1,
            ActivityLog::query()
                ->where('entity_id', $appointment->id)
                ->whereIn('action', AttendanceConfirmationService::CONFIRMATION_ACTIONS)
                ->count()
        );
    }

    public function test_past_unresolved_appointment_still_allows_phone_confirmation(): void
    {
        $appointment = $this->makeAppointment([
            'appointment_date' => Carbon::yesterday()->toDateString(),
        ]);

        $response = $this->actingAs($this->receptionist)->get(route('receptionist.active'));

        $response->assertOk();
        $response->assertSee('Appointment time passed');
        $response->assertSee('Confirm Attendance by Phone');

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.attendance.confirm', $appointment))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'action' => AttendanceConfirmationService::ACTION_CONFIRMED_RECEPTIONIST,
            'entity_id' => $appointment->id,
        ]);
    }

    public function test_contact_attempts_remain_repeatable(): void
    {
        $appointment = $this->makeAppointment();

        $this->actingAs($this->receptionist)->post(
            route('receptionist.attendance.no-answer', $appointment),
            ['note' => 'First call.']
        )->assertSessionHas('success');

        Carbon::setTestNow(Carbon::now()->addMinute());

        $this->actingAs($this->receptionist)->post(
            route('receptionist.attendance.unable-to-reach', $appointment),
            ['note' => 'Second call.']
        )->assertSessionHas('success');

        $this->assertSame(
            2,
            ActivityLog::query()
                ->where('entity_id', $appointment->id)
                ->whereIn('action', AttendanceConfirmationService::CONTACT_ACTIONS)
                ->count()
        );

        Carbon::setTestNow();
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
            'guest_phone' => '09170000000',
        ], $overrides));
    }

    private function detailsFor(Appointment $appointment, string $action): array
    {
        $event = ActivityLog::query()
            ->where('entity_type', Appointment::class)
            ->where('entity_id', $appointment->id)
            ->where('action', $action)
            ->firstOrFail();

        return json_decode($event->details, true) ?: [];
    }

    private function bookingSnapshot(Appointment $appointment): array
    {
        $appointment->refresh();

        return [
            'status' => $appointment->status,
            'confirmed_at' => $appointment->confirmed_at?->toDateTimeString(),
            'confirmed_by' => $appointment->confirmed_by,
            'cancellation_reason' => $appointment->cancellation_reason,
            'no_show_reason' => $appointment->no_show_reason,
        ];
    }

    private function assertBookingUnchanged(Appointment $appointment, array $before): void
    {
        $appointment->refresh();

        $this->assertSame($before['status'], $appointment->status);
        $this->assertSame($before['confirmed_at'], $appointment->confirmed_at?->toDateTimeString());
        $this->assertSame($before['confirmed_by'], $appointment->confirmed_by);
        $this->assertSame($before['cancellation_reason'], $appointment->cancellation_reason);
        $this->assertSame($before['no_show_reason'], $appointment->no_show_reason);
    }
}
