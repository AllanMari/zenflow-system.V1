<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Payment;
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

class ActiveBookingsActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $receptionist;
    protected User $staff;
    protected Room $room;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach(Role::firstOrCreate(['name' => 'receptionist']));
        $this->staff = User::factory()->create(['is_active' => true]);
        $this->staff->roles()->attach(Role::firstOrCreate(['name' => 'staff']));

        for ($i = 0; $i < 7; $i++) {
            BusinessHour::create([
                'day_of_week' => $i,
                'open_time' => '08:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false,
            ]);
        }

        WorkSchedule::create([
            'user_id' => $this->staff->id,
            'day_of_week' => Carbon::tomorrow()->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_day_off' => false,
        ]);

        $category = ServiceCategory::create(['name' => 'Active Test', 'is_active' => true]);
        $roomCategory = RoomCategory::create(['name' => 'Active Test Room', 'code' => 'active_test_room']);
        $this->service = Service::create([
            'name' => 'Active Test Service', 'category_id' => $category->id,
            'room_category_id' => $roomCategory->id, 'duration_minutes' => 60,
            'price' => 1000, 'requires_room' => true, 'is_active' => true,
        ]);
        $this->room = Room::create(['name' => 'Active Test Room', 'status' => 'available', 'is_active' => true]);
        $this->room->roomCategories()->attach($roomCategory->id);
    }

    private function appointment(string $start, string $end, string $status = 'confirmed'): Appointment
    {
        $customer = Customer::create([
            'first_name' => 'Active', 'last_name' => 'Customer',
            'phone_number' => '0912345678' . random_int(10, 99),
            'customer_type' => 'regular',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $this->staff->id,
            'room_id' => $this->room->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => $start . ':00',
            'end_time' => $end . ':00',
            'status' => $status,
            'total_price' => 1000,
        ]);
        $appointment->services()->attach($this->service->id, [
            'price_at_booking' => 1000,
            'service_duration' => 60,
        ]);
        $appointment->syncStaff($this->staff->id, []);

        return $appointment;
    }

    public function test_no_show_action_marks_no_show_without_financial_changes(): void
    {
        $appointment = $this->appointment('13:00', '14:00');
        Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => 1000,
            'type' => 'full',
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.no-show', $appointment->id),
            ['action' => 'forfeit', 'no_show_reason' => 'Customer did not arrive']
        );

        $response->assertRedirect();
        $appointment->refresh();
        $this->assertEquals('no_show', $appointment->status);
        $this->assertEquals('Customer did not arrive', $appointment->no_show_reason);
        $this->assertEquals(1, $appointment->payments()->count());
        $this->assertNull($appointment->payments()->where('type', 'refund')->first());
    }

    public function test_reschedule_rejects_time_inside_preparation_window(): void
    {
        $first = $this->appointment('13:00', '14:00');
        $second = $this->appointment('14:00', '15:00');

        $response = $this->actingAs($this->receptionist)->post(
            route('receptionist.reschedule', $second->id),
            [
                'appointment_date' => Carbon::tomorrow()->toDateString(),
                'start_time' => '14:00',
                'room_id' => $this->room->id,
                'exclude_appointment_id' => $second->id,
            ]
        );

        // The action returns back() with an error rather than saving an overlapping booking.
        $response->assertRedirect();
        $second->refresh();
        $this->assertEquals('14:00:00', $second->start_time);
        $this->assertNotNull(session('error'));
    }
}
