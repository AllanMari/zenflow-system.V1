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
 * Phase 3 customer attendance confirmation tests.
 *
 * These tests run on SQLite (:memory:). They prove ownership, eligibility,
 * event recording, and rendering. They do not prove MySQL InnoDB row-lock
 * concurrency.
 */
class CustomerAttendanceConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'customer']);

        $this->user = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);
        $this->user->roles()->attach($role);

        $this->customer = Customer::create([
            'user_id' => $this->user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $this->user->email,
            'phone_number' => '09171234567',
        ]);
    }

    public function test_customer_can_confirm_own_approved_appointment(): void
    {
        $appointment = $this->makeAppointment($this->customer);
        $before = $this->bookingSnapshot($appointment);

        $response = $this->actingAs($this->user)->post(
            route('customer.attendance.confirm', $appointment)
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => AttendanceConfirmationService::ACTION_CONFIRMED_CUSTOMER,
            'entity_type' => Appointment::class,
            'entity_id' => $appointment->id,
        ]);

        $details = $this->detailsFor($appointment, AttendanceConfirmationService::ACTION_CONFIRMED_CUSTOMER);

        $this->assertSame('customer', $details['source']);
        $this->assertSame($this->user->id, $details['actor_id']);
        $this->assertSame($this->user->full_name, $details['actor_name']);

        $this->assertBookingUnchanged($appointment, $before);
    }

    public function test_customer_identity_comes_from_session_not_request_input(): void
    {
        $appointment = $this->makeAppointment($this->customer);
        $otherUser = User::factory()->create();

        $this->actingAs($this->user)->post(
            route('customer.attendance.confirm', $appointment),
            ['customer_id' => $otherUser->id, 'user_id' => $otherUser->id]
        )->assertSessionHas('success');

        $event = ActivityLog::query()
            ->where('entity_type', Appointment::class)
            ->where('entity_id', $appointment->id)
            ->where('action', AttendanceConfirmationService::ACTION_CONFIRMED_CUSTOMER)
            ->firstOrFail();

        $this->assertSame($this->user->id, $event->user_id);
    }

    public function test_another_customer_cannot_confirm_appointment(): void
    {
        $appointment = $this->makeAppointment($this->customer);

        $role = Role::firstOrCreate(['name' => 'customer']);
        $otherUser = User::factory()->create();
        $otherUser->roles()->attach($role);

        Customer::create([
            'user_id' => $otherUser->id,
            'first_name' => 'Other',
            'last_name' => 'Customer',
            'email' => $otherUser->email,
            'phone_number' => '09170000001',
        ]);

        $this->actingAs($otherUser)
            ->post(route('customer.attendance.confirm', $appointment))
            ->assertStatus(403);

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_guest_appointment_cannot_use_customer_confirmation(): void
    {
        $guestCustomer = Customer::create([
            'user_id' => null,
            'first_name' => 'Guest',
            'last_name' => 'Visitor',
            'email' => null,
            'phone_number' => '09170000000',
        ]);

        $appointment = $this->makeAppointment($guestCustomer, [
            'guest_phone' => '09170000000',
        ]);

        $this->actingAs($this->user)
            ->post(route('customer.attendance.confirm', $appointment))
            ->assertStatus(403);

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_pending_appointment_cannot_be_confirmed(): void
    {
        $appointment = $this->makeAppointment($this->customer, [
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->post(
            route('customer.attendance.confirm', $appointment)
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame(0, ActivityLog::query()->where('entity_id', $appointment->id)->count());
    }

    public function test_cancelled_completed_and_no_show_appointments_cannot_be_confirmed(): void
    {
        foreach (['cancelled', 'completed', 'no_show'] as $status) {
            $appointment = $this->makeAppointment($this->customer, [
                'status' => $status,
            ]);

            $response = $this->actingAs($this->user)->post(
                route('customer.attendance.confirm', $appointment)
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

    public function test_repeated_confirmation_is_idempotent(): void
    {
        $appointment = $this->makeAppointment($this->customer);

        $this->actingAs($this->user)
            ->post(route('customer.attendance.confirm', $appointment))
            ->assertSessionHas('success');

        $this->actingAs($this->user)
            ->post(route('customer.attendance.confirm', $appointment))
            ->assertSessionHas('error');

        $this->assertSame(
            1,
            ActivityLog::query()
                ->where('entity_id', $appointment->id)
                ->whereIn('action', AttendanceConfirmationService::CONFIRMATION_ACTIONS)
                ->count()
        );
    }

    public function test_customer_booking_page_shows_awaiting_attendance_state(): void
    {
        $appointment = $this->makeAppointment($this->customer);

        $response = $this->actingAs($this->user)->get(route('customer.bookings'));

        $response->assertOk();
        $response->assertSee('Awaiting attendance confirmation');
        $response->assertSee('onclick="confirmAttendance(', false);
        $response->assertDontSee('Attendance confirmed');
    }

    public function test_customer_booking_page_shows_confirmed_state_with_source_and_timestamp(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:15:00'));

        try {
            $appointment = $this->makeAppointment($this->customer, [
                'appointment_date' => '2026-12-05',
            ]);

            $this->actingAs($this->user)
                ->post(route('customer.attendance.confirm', $appointment))
                ->assertSessionHas('success');

            $response = $this->actingAs($this->user)->get(route('customer.bookings'));

            $response->assertOk();
            $response->assertSee('Attendance confirmed');
            $response->assertSee('Confirmed by you');
            $response->assertSee('Dec 1, 2026 10:15 AM');
            $response->assertDontSee('Awaiting attendance confirmation');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_receptionist_confirmation_is_visible_to_customer_without_customer_action(): void
    {
        $appointment = $this->makeAppointment($this->customer);

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($receptionistRole);

        $result = app(AttendanceConfirmationService::class)
            ->confirmByReceptionist($appointment, $receptionist);

        $this->assertTrue($result['recorded']);

        $response = $this->actingAs($this->user)->get(route('customer.bookings'));

        $response->assertOk();
        $response->assertSee('Attendance confirmed');
        $response->assertSee('Confirmed by our receptionist (phone)');
        $response->assertDontSee('Awaiting attendance confirmation');
        $response->assertDontSee('onclick="confirmAttendance(', false);

        $this->assertSame(
            1,
            ActivityLog::query()
                ->where('entity_id', $appointment->id)
                ->whereIn('action', AttendanceConfirmationService::CONFIRMATION_ACTIONS)
                ->count()
        );
    }

    public function test_customer_page_does_not_expose_receptionist_contact_history_or_notes(): void
    {
        $appointment = $this->makeAppointment($this->customer);

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($receptionistRole);

        app(AttendanceConfirmationService::class)->recordContactAttempt(
            $appointment,
            $receptionist,
            AttendanceConfirmationService::STATE_NO_ANSWER,
            'Internal receptionist note only.'
        );

        $response = $this->actingAs($this->user)->get(route('customer.bookings'));

        $response->assertOk();
        $response->assertDontSee('Internal receptionist note only.');
        $response->assertDontSee('Contact history');
        $response->assertDontSee('No answer');
    }

    public function test_confirmation_does_not_change_booking_status_or_approval_fields(): void
    {
        $appointment = $this->makeAppointment($this->customer);
        $before = $this->bookingSnapshot($appointment);

        $this->actingAs($this->user)
            ->post(route('customer.attendance.confirm', $appointment))
            ->assertSessionHas('success');

        $this->assertBookingUnchanged($appointment, $before);
        $this->assertTrue(app(AttendanceConfirmationService::class)->isAttendanceConfirmed($appointment));
    }

    private function makeAppointment(Customer $customer, array $overrides = []): Appointment
    {
        $approver = User::factory()->create();

        return Appointment::create(array_merge([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1500,
            'confirmed_at' => Carbon::now()->subDay(),
            'confirmed_by' => $approver->id,
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
