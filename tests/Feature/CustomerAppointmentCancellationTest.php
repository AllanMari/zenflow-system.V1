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

class CustomerAppointmentCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_own_upcoming_appointment()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $user->email,
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
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1500.00,
            'notes' => 'Initial note',
        ]);

        $response = $this->actingAs($user)->post(route('customer.appointments.cancel', $appointment), [
            'cancellation_note' => 'Schedule conflict',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertEquals('customer_cancelled', $appointment->cancellation_reason);
        $this->assertStringContainsString('Schedule conflict', $appointment->notes);

        $room->refresh();
        $this->assertEquals('available', $room->status);
    }

    public function test_customer_cannot_cancel_other_customer_appointment()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        
        $user1 = User::factory()->create();
        $user1->roles()->attach($role);
        $customer1 = Customer::create([
            'user_id' => $user1->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $user1->email,
            'phone_number' => '1234567890',
        ]);

        $user2 = User::factory()->create();
        $user2->roles()->attach($role);
        $customer2 = Customer::create([
            'user_id' => $user2->id,
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => $user2->email,
            'phone_number' => '0987654321',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer2->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1500.00,
        ]);

        $response = $this->actingAs($user1)->post(route('customer.appointments.cancel', $appointment));

        $response->assertStatus(403);
    }
}

