<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_customer_online_booking_submits_and_notifies_customer_only()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user1 = User::factory()->create();
        $user1->roles()->attach($role);
        $customer1 = Customer::create([
            'user_id' => $user1->id,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => $user1->email,
            'phone_number' => '1112223333',
        ]);

        $user2 = User::factory()->create();
        $user2->roles()->attach($role);
        $customer2 = Customer::create([
            'user_id' => $user2->id,
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'email' => $user2->email,
            'phone_number' => '4445556666',
        ]);

        $category = \App\Models\ServiceCategory::create([
            'name' => 'Massages',
            'is_active' => true,
        ]);

        $service = Service::create([
            'name' => 'Massage',
            'category_id' => $category->id,
            'duration_minutes' => 60,
            'price' => 1000.00,
            'is_active' => true,
        ]);

        $room = Room::create([
            'name' => 'Room 1',
            'status' => 'available',
            'is_active' => true,
        ]);

        $tomorrow = Carbon::tomorrow();
        \App\Models\BusinessHour::create([
            'day_of_week' => $tomorrow->dayOfWeek,
            'open_time' => '08:00:00',
            'close_time' => '22:00:00',
            'is_closed' => false,
        ]);

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staff = User::factory()->create(['is_active' => true]);
        $staff->roles()->attach($staffRole);

        \App\Models\WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $tomorrow->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '22:00:00',
            'is_day_off' => false,
        ]);

        $bookingData = [
            'services' => [$service->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => 'Alice',
            'guest_last_name' => 'Smith',
            'guest_phone' => '09123456789',
            'source' => 'public',
        ];

        $response = $this->actingAs($user1)->post(route('booking.store'), $bookingData);

        if (session('error')) {
            $this->fail('Booking failed with error: ' . session('error'));
        }

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        
        $appointment = Appointment::latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('pending', $appointment->status);
        $this->assertEquals($customer1->id, $appointment->customer_id);

        // Check user1 notification
        $this->assertCount(1, $user1->notifications);
        $notification = $user1->notifications->first();
        $this->assertEquals('Booking Request Submitted', $notification->data['title']);
        $this->assertEquals('booking', $notification->data['type']);
        $this->assertEquals('info', $notification->data['severity']);
        $this->assertStringContainsString('Your booking request for', $notification->data['message']);
        $this->assertStringContainsString('has been received and is awaiting confirmation.', $notification->data['message']);
        $this->assertEquals(route('customer.bookings'), $notification->data['action_url']);
        $this->assertEquals('View Bookings', $notification->data['action_text']);

        // Check user2 did NOT receive notification
        $this->assertCount(0, $user2->notifications);
    }

    public function test_customer_cancellation_notifies_authenticated_customer_only()
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user1 = User::factory()->create();
        $user1->roles()->attach($role);
        $customer1 = Customer::create([
            'user_id' => $user1->id,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => $user1->email,
            'phone_number' => '1112223333',
        ]);

        $user2 = User::factory()->create();
        $user2->roles()->attach($role);
        $customer2 = Customer::create([
            'user_id' => $user2->id,
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'email' => $user2->email,
            'phone_number' => '4445556666',
        ]);

        $room = Room::create([
            'name' => 'Room 1',
            'status' => 'occupied',
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer1->id,
            'room_id' => $room->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1500.00,
        ]);

        $response = $this->actingAs($user1)->post(route('customer.appointments.cancel', $appointment), [
            'cancellation_note' => 'Change of plans',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertEquals('customer_cancelled', $appointment->cancellation_reason);

        // Check user1 notification
        $this->assertCount(1, $user1->notifications);
        $notification = $user1->notifications->first();
        $this->assertEquals('Appointment Cancelled', $notification->data['title']);
        $this->assertEquals('booking', $notification->data['type']);
        $this->assertEquals('warning', $notification->data['severity']);
        $this->assertStringContainsString('Your appointment on', $notification->data['message']);
        $this->assertStringContainsString('has been cancelled.', $notification->data['message']);
        $this->assertEquals(route('customer.bookings'), $notification->data['action_url']);
        $this->assertEquals('View Bookings', $notification->data['action_text']);

        // Check user2 received no notification
        $this->assertCount(0, $user2->notifications);
    }

    public function test_booking_wizard_navigation_for_customer_and_guest()
    {
        // Guest check
        $responseGuest = $this->get(route('booking.wizard'));
        $responseGuest->assertStatus(200);
        $responseGuest->assertSee('Back to Home');
        $responseGuest->assertSee(url('/'));

        // Customer check
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $responseCustomer = $this->actingAs($user)->get(route('booking.wizard'));
        $responseCustomer->assertStatus(200);
        $responseCustomer->assertSee('Back to Dashboard');
        $responseCustomer->assertSee(route('customer-dashboard'));
    }
}
