<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingReservationSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected Service $service;
    protected Room $room;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'customer']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'receptionist']);

        $this->staff = User::factory()->create(['is_active' => true]);
        $this->staff->roles()->attach($staffRole);
        WorkSchedule::create([
            'user_id' => $this->staff->id,
            'day_of_week' => Carbon::tomorrow()->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_day_off' => false,
        ]);

        for ($i = 0; $i < 7; $i++) {
            BusinessHour::create([
                'day_of_week' => $i,
                'open_time' => '08:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false,
            ]);
        }

        $category = ServiceCategory::create(['name' => 'Reservation Test', 'is_active' => true]);
        $roomCategory = RoomCategory::create(['name' => 'Reservation Room', 'code' => 'reservation_room']);
        $this->service = Service::create([
            'name' => 'Reservation Massage', 'category_id' => $category->id,
            'room_category_id' => $roomCategory->id, 'duration_minutes' => 60,
            'price' => 1000, 'requires_room' => true, 'is_active' => true,
        ]);
        $this->room = Room::create(['name' => 'Reservation Room 1', 'status' => 'available', 'is_active' => true]);
        $this->room->roomCategories()->attach($roomCategory->id);
    }

    private function payload(string $phone, string $time = '13:00'): array
    {
        return [
            'services' => [$this->service->id],
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => $time,
            'guest_first_name' => 'Reservation',
            'guest_phone' => $phone,
            'source' => 'public',
        ];
    }

    public function test_customer_booking_creates_pending_unassigned_appointment_with_reserved_room(): void
    {
        $response = $this->post(route('booking.store'), $this->payload('09123450001'));

        $response->assertRedirect();
        $appointment = Appointment::where('guest_phone', '09123450001')->firstOrFail();
        $this->assertEquals('pending', $appointment->status);
        $this->assertNull($appointment->user_id);
        $this->assertCount(0, $appointment->assignedStaff);
        $this->assertEquals($this->room->id, $appointment->room_id);
        $this->assertFalse($this->room->fresh()->isAvailableFor(
            $appointment->appointment_date->toDateString(), '13:00', '14:00'
        ));
    }

    public function test_multiple_pending_bookings_do_not_create_hidden_therapist_assignments(): void
    {
        $this->post(route('booking.store'), $this->payload('09123450002', '13:00'));
        $this->post(route('booking.store'), $this->payload('09123450003', '15:00'));

        $appointments = Appointment::whereIn('guest_phone', ['09123450002', '09123450003'])->get();
        $this->assertCount(2, $appointments);
        $appointments->each(function ($appointment) {
            $this->assertNull($appointment->user_id);
            $this->assertCount(0, $appointment->assignedStaff);
        });
    }

    public function test_failed_confirmation_keeps_appointment_pending_and_unassigned(): void
    {
        $this->post(route('booking.store'), $this->payload('09123450004'));
        $appointment = Appointment::where('guest_phone', '09123450004')->firstOrFail();

        // Occupy the only therapist at the appointment time before confirmation.
        $blockerCustomer = Customer::create([
            'first_name' => 'Blocker', 'last_name' => 'Test',
            'phone_number' => '09123459999', 'customer_type' => 'regular',
        ]);
        $blocker = Appointment::create([
            'customer_id' => $blockerCustomer->id,
            'user_id' => $this->staff->id,
            'room_id' => $this->room->id,
            'appointment_date' => $appointment->appointment_date->toDateString(),
            'start_time' => '13:00:00', 'end_time' => '14:00:00',
            'status' => 'confirmed', 'total_price' => 1000,
        ]);
        $blocker->services()->attach($this->service->id, ['price_at_booking' => 1000, 'service_duration' => 60]);
        $blocker->syncStaff($this->staff->id, []);

        $receptionist = User::factory()->create(['is_active' => true]);
        $receptionist->roles()->attach(Role::where('name', 'receptionist')->first());

        $response = $this->actingAs($receptionist)->post(
            route('receptionist.confirm', $appointment->id),
            ['staff_id' => $this->staff->id, 'room_id' => $this->room->id, 'payment_type' => 'cash_on_site']
        );

        $response->assertRedirect();
        $appointment->refresh();
        $this->assertEquals('pending', $appointment->status);
        $this->assertNull($appointment->user_id);
        $this->assertCount(0, $appointment->assignedStaff);
    }
}
