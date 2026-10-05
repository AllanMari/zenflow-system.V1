<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffNoShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_mark_appointment_as_no_show_with_forfeit()
    {
        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($role);

        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone_number' => '1234567890',
        ]);

        $room = Room::create([
            'name' => 'VIP Room 1',
            'status' => 'occupied',
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1500.00,
        ]);

        $response = $this->actingAs($receptionist)->post(route('receptionist.no-show', $appointment), [
            'action' => 'forfeit',
            'no_show_reason' => 'did_not_arrive',
        ]);

        $response->assertRedirect();

        $appointment->refresh();
        $this->assertEquals('no_show', $appointment->status);
        $this->assertNull($appointment->cancellation_reason);
        $this->assertEquals('did_not_arrive', $appointment->no_show_reason);

        $room->refresh();
        $this->assertEquals('available', $room->status);
    }

    public function test_receptionist_can_mark_appointment_as_no_show_with_refund()
    {
        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($role);

        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone_number' => '1234567890',
        ]);

        $room = Room::create([
            'name' => 'VIP Room 1',
            'status' => 'occupied',
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1500.00,
        ]);

        $response = $this->actingAs($receptionist)->post(route('receptionist.no-show', $appointment), [
            'action' => 'refund',
            'no_show_reason' => 'did_not_arrive',
        ]);

        $response->assertRedirect();

        $appointment->refresh();
        $this->assertEquals('no_show', $appointment->status);
        $this->assertNull($appointment->cancellation_reason);
        $this->assertEquals('did_not_arrive', $appointment->no_show_reason);
        $this->assertEquals($receptionist->id, $appointment->refunded_by);

        $room->refresh();
        $this->assertEquals('available', $room->status);
    }
}
