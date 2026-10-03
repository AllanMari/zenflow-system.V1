<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBookingsViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_access_bookings_page()
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

        $response = $this->actingAs($user)->get(route('customer.bookings'));

        $response->assertStatus(200);
        $response->assertSee('My Bookings & Appointments');
    }

    public function test_customer_bookings_page_shows_upcoming_and_past()
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

        // Upcoming
        Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);

        // Past
        Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::yesterday()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 1000,
        ]);

        $response = $this->actingAs($user)->get(route('customer.bookings'));

        $response->assertStatus(200);
        $response->assertSee('Upcoming Appointments');
        $response->assertSee('Past & Cancelled Bookings History');
    }
}
