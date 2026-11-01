<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingConfirmationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function setupBookingEnvironment($tomorrow)
    {
        for ($i = 0; $i < 7; $i++) {
            BusinessHour::create([
                'day_of_week' => $i,
                'open_time' => '08:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false,
            ]);
        }

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staff = User::factory()->create(['is_active' => true]);
        $staff->roles()->attach($staffRole);

        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $tomorrow->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_day_off' => false,
        ]);

        $cat = ServiceCategory::create(['name' => 'Massages', 'is_active' => true]);
        $service = Service::create([
            'name' => 'Swedish Massage',
            'category_id' => $cat->id,
            'duration_minutes' => 60,
            'price' => 1000.00,
            'is_active' => true,
        ]);
        Room::create(['name' => 'Room 1', 'status' => 'available', 'is_active' => true]);

        return $service;
    }

    public function test_guest_booking_redirects_and_allows_confirmation_access()
    {
        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        $response = $this->post(route('booking.store'), [
            'services' => [$service->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => 'Alice Guest',
            'guest_phone' => '09123456789',
            'source' => 'public',
        ]);

        $response->assertSessionHasNoErrors();
        $appointment = Appointment::latest('id')->first();
        $this->assertNotNull($appointment);

        $response->assertRedirect(route('booking.confirmation', $appointment->id));

        // Follow redirect with session
        $confirmationResponse = $this->get(route('booking.confirmation', $appointment->id));
        $confirmationResponse->assertStatus(200);
        $confirmationResponse->assertSee('Alice Guest');
    }

    public function test_unauthenticated_visitor_cannot_access_arbitrary_appointment_confirmation()
    {
        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        // Someone else's appointment created in database
        $customer = Customer::create([
            'first_name' => 'Secret',
            'last_name' => 'Person',
            'phone_number' => '09999999999',
        ]);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1000.00,
            'guest_first_name' => 'Secret Person',
            'guest_phone' => '09999999999',
        ]);

        // Attempting to visit without session authorization
        $response = $this->get(route('booking.confirmation', $appointment->id));
        $response->assertStatus(403);
    }

    public function test_registered_customer_can_access_their_own_confirmation()
    {
        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($customerRole);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Registered',
            'last_name' => 'Customer',
            'phone_number' => '09112223344',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1000.00,
        ]);
        $appointment->services()->attach($service->id, [
            'price_at_booking' => 1000.00,
            'service_name' => $service->name,
            'service_duration' => 60,
            'is_extra' => false,
        ]);

        // Accessing as the owner
        $response = $this->actingAs($user)->get(route('booking.confirmation', $appointment->id));
        $response->assertStatus(200);
        $response->assertSee('Registered Customer');
    }

    public function test_registered_customer_cannot_access_another_customers_confirmation()
    {
        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $user1 = User::factory()->create();
        $user1->roles()->attach($customerRole);
        $user2 = User::factory()->create();
        $user2->roles()->attach($customerRole);

        $customer1 = Customer::create([
            'user_id' => $user1->id,
            'first_name' => 'Victim',
            'last_name' => 'Customer',
            'phone_number' => '09111111111',
        ]);
        $customer2 = Customer::create([
            'user_id' => $user2->id,
            'first_name' => 'Attacker',
            'last_name' => 'Customer',
            'phone_number' => '09222222222',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer1->id,
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1000.00,
        ]);

        // User2 attempts to access User1's confirmation
        $response = $this->actingAs($user2)->get(route('booking.confirmation', $appointment->id));
        $response->assertStatus(403);
    }

    public function test_staff_or_receptionist_can_view_confirmation()
    {
        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($receptionistRole);

        $customer = Customer::create([
            'first_name' => 'Client',
            'last_name' => 'One',
            'phone_number' => '09333333333',
        ]);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1000.00,
        ]);
        $appointment->services()->attach($service->id, [
            'price_at_booking' => 1000.00,
            'service_name' => $service->name,
            'service_duration' => 60,
            'is_extra' => false,
        ]);

        $response = $this->actingAs($receptionist)->get(route('booking.confirmation', $appointment->id));
        $response->assertStatus(200);
        $response->assertSee('Client One');
    }
}
