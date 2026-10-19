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

class GuestBookingNameRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function setupBookingEnvironment($tomorrow)
    {
        for ($i = 0; $i < 7; $i++) {
            BusinessHour::create(['day_of_week' => $i, 'open_time' => '08:00:00', 'close_time' => '20:00:00', 'is_closed' => false]);
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
        $service = Service::create(['name' => 'Massage', 'category_id' => $cat->id, 'duration_minutes' => 60, 'price' => 1000.00, 'is_active' => true]);
        Room::create(['name' => 'Room 1', 'status' => 'available', 'is_active' => true]);

        return $service;
    }

    public function test_existing_customer_phone_collision_preserves_customer_profile_and_displays_guest_name()
    {
        $existing = Customer::create(['first_name' => 'Original', 'last_name' => 'Customer', 'phone_number' => '09123456789']);
        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        $response = $this->post(route('booking.store'), [
            'services' => [$service->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => 'Jane Doe',
            'guest_phone' => '09123456789',
            'source' => 'public',
        ]);

        if (session('error')) {
            $this->fail('Booking error: ' . session('error'));
        }

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $appointment = Appointment::latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('Jane Doe', $appointment->guest_first_name);
        $this->assertEquals('Jane Doe', $appointment->client_name);

        $existing->refresh();
        $this->assertEquals('Original', $existing->first_name);
        $this->assertEquals('Customer', $existing->last_name);
        $this->assertEquals('09123456789', $existing->phone_number);
    }

    public function test_pending_bookings_view_displays_guest_name_instead_of_customer_name()
    {
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create(['is_active' => true]);
        $receptionist->roles()->attach($receptionistRole);

        $existing = Customer::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'phone_number' => '09123456789',
        ]);

        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        $this->post(route('booking.store'), [
            'services' => [$service->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => 'Ana Marie De la Cruz',
            'guest_phone' => '09123456789',
            'source' => 'public',
        ]);

        $response = $this->actingAs($receptionist)->get(route('receptionist.pending'));
        $response->assertStatus(200);
        $response->assertSee('Ana Marie De la Cruz');
        $response->assertDontSee('Maria Santos');

        $existing->refresh();
        $this->assertEquals('Maria', $existing->first_name);
        $this->assertEquals('Santos', $existing->last_name);
    }

    public function test_various_name_formats_preserved_accurately()
    {
        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        $names = [
            'Ana Marie De la Cruz',
            'Mary Jane van der Berg',
            'Jean-Luc Picard',
            'Cher',
            'Dr. John Watson-Smith, Jr.',
        ];

        foreach ($names as $idx => $name) {
            $phone = '09' . str_pad($idx + 100, 9, '0', STR_PAD_LEFT);
            $response = $this->post(route('booking.store'), [
                'services' => [$service->id],
                'appointment_date' => $tomorrow->toDateString(),
                'start_time' => sprintf('%02d:00', 8 + $idx),
                'guest_first_name' => $name,
                'guest_phone' => $phone,
                'source' => 'public',
            ]);

            $response->assertSessionHasNoErrors();
            $appointment = Appointment::where('guest_phone', $phone)->first();
            $this->assertNotNull($appointment, "Appointment not found for phone {$phone}");
            $this->assertEquals($name, $appointment->guest_first_name);
            $this->assertEquals($name, $appointment->client_name);
        }
    }

    public function test_exact_100_character_name_is_accepted_and_persisted()
    {
        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        // Generate exactly 100 characters: "Ana " + 96 'a's
        $exact100Name = 'Ana ' . str_repeat('a', 96);
        $this->assertEquals(100, strlen($exact100Name));

        $response = $this->post(route('booking.store'), [
            'services' => [$service->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => $exact100Name,
            'guest_phone' => '09777777777',
            'source' => 'public',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $appointment = Appointment::where('guest_phone', '09777777777')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals($exact100Name, $appointment->guest_first_name);
        $this->assertEquals($exact100Name, $appointment->client_name);
    }

    public function test_over_100_character_name_is_rejected_with_validation_error()
    {
        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        // 101 characters
        $overlengthName = str_repeat('A', 101);
        $this->assertEquals(101, strlen($overlengthName));

        $response = $this->post(route('booking.store'), [
            'services' => [$service->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => $overlengthName,
            'guest_phone' => '09777777778',
            'source' => 'public',
        ]);

        $response->assertSessionHasErrors(['guest_first_name']);
        $this->assertNull(Appointment::where('guest_phone', '09777777778')->first());
    }

    public function test_multiple_spaces_handled_consistently()
    {
        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        $input = "  Juan    Carlos  ";
        $response = $this->post(route('booking.store'), [
            'services' => [$service->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => $input,
            'guest_phone' => '09888888888',
            'source' => 'public',
        ]);

        $response->assertSessionHasNoErrors();
        $appointment = Appointment::where('guest_phone', '09888888888')->first();
        $this->assertNotNull($appointment);
        // Trims outer whitespace while preserving inner string
        $this->assertEquals(trim($input), $appointment->guest_first_name);
        $this->assertEquals(trim($input), $appointment->client_name);
    }

    public function test_new_guest_phone_number_works_correctly()
    {
        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        $response = $this->post(route('booking.store'), [
            'services' => [$service->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => 'New Guest',
            'guest_phone' => '09987654321',
            'source' => 'public',
        ]);

        if (session('error')) {
            $this->fail('Booking error: ' . session('error'));
        }

        $response->assertSessionHasNoErrors();
        $appointment = Appointment::latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('New Guest', $appointment->client_name);
    }

    public function test_historical_appointment_falls_back_to_customer_name()
    {
        $customer = Customer::create(['first_name' => 'History', 'last_name' => 'User', 'phone_number' => '09111111111']);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::yesterday()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 500,
        ]);

        $this->assertNull($appointment->guest_first_name);
        $this->assertEquals('History User', $appointment->client_name);
    }

    public function test_registered_customer_booking_displays_correct_name()
    {
        $user = User::factory()->create();
        $customer = Customer::create(['user_id' => $user->id, 'first_name' => 'Registered', 'last_name' => 'User', 'phone_number' => '09222222222']);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 600,
        ]);

        $this->assertEquals('Registered User', $appointment->client_name);
    }

    public function test_receptionist_quick_book_preserves_guest_name()
    {
        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        $response = $this->postJson(route('booking.store'), [
            'source' => 'receptionist',
            'services' => [$service->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'guest_first_name' => 'Walkin Guest QuickBook',
            'guest_phone' => '09555555555',
            'payment_method' => 'cash',
            'payment_amount' => 1000.00,
            'payment_type' => 'full',
            'walk_in_now' => false,
        ]);

        $response->assertJson(['success' => true]);
        $appointment = Appointment::where('guest_phone', '09555555555')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('Walkin Guest QuickBook', $appointment->guest_first_name);
        $this->assertEquals('Walkin Guest QuickBook', $appointment->client_name);
    }

    public function test_financial_and_transaction_integrity()
    {
        $customer = Customer::create(['first_name' => 'Finance', 'last_name' => 'Test', 'phone_number' => '09333333333']);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 1000.00,
            'guest_first_name' => 'Guest Name',
        ]);

        $appointment->payments()->create(['amount' => 1000.00, 'payment_method' => 'cash']);
        $this->assertEquals(1000.00, $appointment->total_price);
        $this->assertEquals(1000.00, $appointment->total_paid);
        $this->assertEquals(0.00, $appointment->remaining_balance);
        $this->assertEquals('Guest Name', $appointment->client_name);
    }
}
