<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\AppointmentOccupancyService;
use App\Services\AttendanceConfirmationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Manual receptionist cancellation of confirmed bookings, including those
 * whose attendance is already confirmed. SQLite does not prove MySQL locking.
 */
class ReceptionistConfirmedCancellationTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    private User $staff;

    private Room $room;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Manila'));

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $this->receptionist = User::factory()->create([
            'first_name' => 'Rita', 'last_name' => 'Reception', 'is_active' => true,
        ]);
        $this->receptionist->roles()->attach($receptionistRole);

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $this->staff = User::factory()->create(['is_active' => true]);
        $this->staff->roles()->attach($staffRole);

        $this->room = Room::create(['name' => 'Cancel Room', 'status' => 'occupied', 'is_active' => true]);

        $category = ServiceCategory::create(['name' => 'Cancel Category', 'is_active' => true]);
        $this->service = Service::create([
            'name' => 'Cancel Massage', 'category_id' => $category->id,
            'duration_minutes' => 60, 'price' => 1000,
            'requires_room' => true, 'is_active' => true,
        ]);

        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $customerUser = User::factory()->create(['first_name' => 'Cora', 'last_name' => 'Customer']);
        $customerUser->roles()->attach($customerRole);
        $this->customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Cora', 'last_name' => 'Customer',
            'email' => $customerUser->email,
            'phone_number' => '09171234567',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_confirmed_booking_can_be_cancelled_with_attendance_unconfirmed(): void
    {
        $appointment = $this->makeAppointment();
        Cache::put('avail:version', 5, now()->addDay());

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.cancel', $appointment), ['reason' => 'staff_unavailable'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->room->refresh();

        $this->assertSame('cancelled', $appointment->status);
        $this->assertSame('staff_unavailable', $appointment->cancellation_reason);
        $this->assertStringContainsString('Receptionist Cancellation', $appointment->notes);
        $this->assertStringContainsString('Rita Reception', $appointment->notes);
        $this->assertSame('available', $this->room->status);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->receptionist->id,
            'action' => 'appointment_cancelled_receptionist',
            'entity_type' => Appointment::class,
            'entity_id' => $appointment->id,
        ]);
        $this->assertSame(6, Cache::get('avail:version'));
    }

    public function test_attendance_confirmed_booking_can_still_be_cancelled(): void
    {
        $appointment = $this->makeAppointment();
        app(AttendanceConfirmationService::class)->confirmByReceptionist($appointment, $this->receptionist);

        $this->assertTrue(app(AttendanceConfirmationService::class)->isAttendanceConfirmed($appointment));

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.cancel', $appointment), ['reason' => 'customer_cancelled'])
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertTrue(app(AttendanceConfirmationService::class)->isAttendanceConfirmed($appointment));
    }

    public function test_cancelled_booking_disappears_from_confirmed_booking_tab_and_remains_in_history(): void
    {
        $appointment = $this->makeAppointment('2026-12-05');

        $this->actingAs($this->receptionist)->get(route('receptionist.active'))
            ->assertSee('Cora Customer');

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.cancel', $appointment), ['reason' => 'staff_unavailable'])
            ->assertRedirect();

        $this->actingAs($this->receptionist)->get(route('receptionist.active'))
            ->assertDontSee('Cora Customer');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'staff_unavailable',
        ]);
    }

    public function test_room_and_therapist_capacity_are_released(): void
    {
        $appointment = $this->makeAppointment();

        $this->assertTrue(app(AppointmentOccupancyService::class)->hasStaffConflict(
            $this->staff->id, '2026-12-01', '10:00', '11:00'
        ));

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.cancel', $appointment), ['reason' => 'staff_unavailable'])
            ->assertRedirect();

        $this->assertFalse(app(AppointmentOccupancyService::class)->hasStaffConflict(
            $this->staff->id, '2026-12-01', '10:00', '11:00'
        ));
        $this->assertSame('available', $this->room->fresh()->status);
    }

    public function test_completed_cancelled_and_no_show_appointments_cannot_be_cancelled(): void
    {
        foreach (['completed', 'cancelled', 'no_show'] as $status) {
            $appointment = $this->makeAppointment('2026-12-05', $status);

            $this->actingAs($this->receptionist)
                ->post(route('receptionist.cancel', $appointment), ['reason' => 'staff_unavailable'])
                ->assertRedirect();

            $this->assertSame($status, $appointment->fresh()->status);
        }
    }

    public function test_repeated_cancellation_does_not_create_another_event(): void
    {
        $appointment = $this->makeAppointment();

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.cancel', $appointment), ['reason' => 'staff_unavailable']);
        $this->actingAs($this->receptionist)
            ->post(route('receptionist.cancel', $appointment), ['reason' => 'staff_unavailable'])
            ->assertSessionHas('info');

        $this->assertSame(1, ActivityLog::query()
            ->where('entity_id', $appointment->id)
            ->where('action', 'appointment_cancelled_receptionist')
            ->count());
    }

    public function test_unauthorized_users_cannot_cancel(): void
    {
        $appointment = $this->makeAppointment();

        foreach (['customer', 'staff'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $user = User::factory()->create();
            $user->roles()->attach($role);

            $this->actingAs($user)
                ->post(route('receptionist.cancel', $appointment), ['reason' => 'staff_unavailable'])
                ->assertStatus(403);
        }

        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_customer_cancellation_workflow_still_works(): void
    {
        $appointment = $this->makeAppointment('2026-12-05');
        $customerUser = Customer::whereKey($this->customer->id)->value('user_id');

        $this->actingAs(User::findOrFail($customerUser))
            ->post(route('customer.appointments.cancel', $appointment), [
                'cancellation_note' => 'Schedule conflict',
            ])
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertSame('available', $this->room->fresh()->status);
    }

    public function test_past_unresolved_and_attendance_confirmation_remain_unchanged(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:30:00', 'Asia/Manila'));
        $appointment = $this->makeAppointment('2026-12-01');

        $content = $this->actingAs($this->receptionist)->get(route('receptionist.active'))->getContent();

        $this->assertStringContainsString('id="appointment-' . $appointment->id . '"', $content);
        $this->assertStringContainsString('15+ minutes late', $content);

        app(AttendanceConfirmationService::class)->confirmByCustomer(
            $appointment,
            User::findOrFail(Customer::whereKey($this->customer->id)->value('user_id'))
        );

        $content = $this->actingAs($this->receptionist)->get(route('receptionist.active'))->getContent();

        $this->assertStringContainsString('id="appointment-' . $appointment->id . '"', $content);
        $this->assertStringContainsString('Attendance confirmed', $content);
        $this->assertSame('confirmed', $appointment->fresh()->status);
    }

    public function test_confirmed_bookings_card_exposes_cancel_action_and_modal(): void
    {
        $appointment = $this->makeAppointment('2026-12-05');

        $content = $this->actingAs($this->receptionist)->get(route('receptionist.active'))->getContent();

        $this->assertStringContainsString("openCancel(", $content);
        $this->assertStringContainsString("modal === 'cancel'", $content);
        $this->assertStringContainsString('staff_unavailable', $content);
        $this->assertStringContainsString('name="other_reason"', $content);
        $this->assertStringContainsString('Cancel Booking', $content);
    }

    public function test_other_reason_is_validated_and_recorded(): void
    {
        $appointment = $this->makeAppointment();

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.cancel', $appointment), ['reason' => 'other'])
            ->assertSessionHasErrors('other_reason');

        $this->assertSame('confirmed', $appointment->fresh()->status);

        $this->actingAs($this->receptionist)
            ->post(route('receptionist.cancel', $appointment), [
                'reason' => 'other',
                'other_reason' => 'Customer requested a different date.',
            ])
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame('other', $appointment->cancellation_reason);
        $this->assertStringContainsString('Customer requested a different date.', $appointment->notes);
    }

    private function makeAppointment(string $date = '2026-12-01', string $status = 'confirmed'): Appointment
    {
        $approver = User::factory()->create();

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'room_id' => $this->room->id,
            'appointment_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => $status,
            'total_price' => 1000,
            'confirmed_at' => Carbon::now()->subDay(),
            'confirmed_by' => $approver->id,
        ]);
        $appointment->services()->attach($this->service->id, [
            'price_at_booking' => 1000,
            'service_duration' => 60,
        ]);
        $appointment->syncStaff($this->staff->id, []);

        return $appointment;
    }
}
