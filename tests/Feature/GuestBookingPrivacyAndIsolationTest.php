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

class GuestBookingPrivacyAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function setupBookingEnvironment($date)
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
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_day_off' => false,
        ]);

        $cat = ServiceCategory::create(['name' => 'Massages', 'is_active' => true]);
        $roomCat = RoomCategory::firstOrCreate(['name' => 'Standard Room', 'code' => 'std_room']);
        $service = Service::create([
            'name' => 'Swedish Massage',
            'category_id' => $cat->id,
            'room_category_id' => $roomCat->id,
            'duration_minutes' => 60,
            'price' => 1000.00,
            'is_active' => true,
            'requires_room' => true,
            'therapists_required' => 1,
            'participants_required' => 1,
        ]);

        $room = Room::create(['name' => 'Room 1', 'status' => 'available', 'is_active' => true]);
        $room->roomCategories()->attach($roomCat->id);

        return ['service' => $service, 'staff' => $staff, 'room' => $room];
    }

    /**
     * Test 1: Guest booking with the same phone number as a registered customer
     * must NEVER link to the registered customer's account or appear in "My Bookings".
     */
    public function test_guest_booking_sharing_phone_never_appears_in_registered_customer_my_bookings(): void
    {
        $sharedPhone = '09178889900';

        // 1. Create a registered customer user
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create([
            'first_name' => 'RegFirst',
            'last_name' => 'RegLast',
        ]);
        $user->roles()->attach($customerRole);

        $registeredCustomer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'RegFirst',
            'last_name' => 'RegLast',
            'phone_number' => $sharedPhone,
            'customer_type' => 'vip',
            'medical_notes' => 'Permanent allergy: Almond Oil',
        ]);

        // 2. A guest books with the EXACT SAME phone number
        $tomorrow = Carbon::tomorrow();
        $env = $this->setupBookingEnvironment($tomorrow);

        $response = $this->post(route('booking.store'), [
            'services' => [$env['service']->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => 'Guest Visitor',
            'guest_phone' => $sharedPhone,
            'treatment_notes' => 'Temporary shoulder knot',
            'privacy_consented' => '1',
            'source' => 'public',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // 3. Verify the appointment was created and points to a GUEST customer profile, NOT the registered customer
        $guestAppt = Appointment::where('guest_first_name', 'Guest Visitor')->first();
        $this->assertNotNull($guestAppt);
        $this->assertNotEquals($registeredCustomer->id, $guestAppt->customer_id);

        $linkedCustomer = Customer::find($guestAppt->customer_id);
        $this->assertNotNull($linkedCustomer);
        $this->assertNull($linkedCustomer->user_id, 'Guest appointment must link to a guest customer profile with user_id = null');

        // 4. Verify Registered Customer's profile was completely preserved
        $registeredCustomer->refresh();
        $this->assertEquals('RegFirst', $registeredCustomer->first_name);
        $this->assertEquals('RegLast', $registeredCustomer->last_name);
        $this->assertEquals('Permanent allergy: Almond Oil', $registeredCustomer->medical_notes);

        // 5. Verify the registered customer's "My Bookings" page does NOT contain the guest booking
        $bookingsResponse = $this->actingAs($user)->get(route('customer.bookings'));
        $bookingsResponse->assertStatus(200);
        $bookingsResponse->assertDontSee('Guest Visitor');
        $bookingsResponse->assertDontSee('Temporary shoulder knot');

        // 6. Verify the registered customer's dashboard does NOT count the guest booking
        $dashboardResponse = $this->actingAs($user)->get(route('customer-dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertDontSee('Guest Visitor');

        // 7. Verify the registered customer CANNOT cancel the guest booking (Unauthorized 403)
        $this->actingAs($user)
            ->post(route('customer.appointments.cancel', $guestAppt), [
                'cancellation_note' => 'I do not know this booking',
            ])
            ->assertStatus(403);
    }

    /**
     * Test 2: Two different guests sharing the same phone number get distinct names preserved.
     */
    public function test_multiple_guests_sharing_phone_preserve_their_respective_names(): void
    {
        $sharedPhone = '09223334455';
        $tomorrow = Carbon::tomorrow();
        $env = $this->setupBookingEnvironment($tomorrow);

        // Guest A books
        $this->post(route('booking.store'), [
            'services' => [$env['service']->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => 'Alice Family',
            'guest_phone' => $sharedPhone,
            'privacy_consented' => '1',
            'source' => 'public',
        ])->assertSessionHasNoErrors();

        // Guest B books with same phone but different name
        $this->post(route('booking.store'), [
            'services' => [$env['service']->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '14:00',
            'guest_first_name' => 'Bob Family',
            'guest_phone' => $sharedPhone,
            'privacy_consented' => '1',
            'source' => 'public',
        ])->assertSessionHasNoErrors();

        $apptA = Appointment::where('start_time', '10:00:00')->first();
        $apptB = Appointment::where('start_time', '14:00:00')->first();

        $this->assertNotNull($apptA);
        $this->assertNotNull($apptB);

        $this->assertEquals('Alice Family', $apptA->client_name);
        $this->assertEquals('Bob Family', $apptB->client_name);
        $this->assertEquals($sharedPhone, $apptA->client_phone);
        $this->assertEquals($sharedPhone, $apptB->client_phone);
    }
}
