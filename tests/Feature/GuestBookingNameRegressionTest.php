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

    public function test_session_card_displays_guest_name_on_phone_collision()
    {
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create(['is_active' => true]);
        $receptionist->roles()->attach($receptionistRole);

        // 1. Create a registered customer
        $customer = Customer::create([
            'first_name' => 'Original',
            'last_name' => 'Customer',
            'phone_number' => '09171234567',
            'nickname' => 'OriginalNickname'
        ]);

        // 2. Create an appointment linked to that customer but with a different guest name (collision scenario)
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'guest_first_name' => 'Jane Doe',
            'guest_phone' => '09171234567',
            'appointment_date' => Carbon::today(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000.00,
        ]);

        // 3. Render the session card partial
        $view = $this->actingAs($receptionist)
            ->view('receptionist._session_card', ['appointment' => $appointment]);

        // 4. Assertions
        $view->assertSee('Jane Doe');
        $view->assertDontSee('Original Customer');
        $view->assertDontSee('OriginalNickname');

        // 5. Verify customer profile is unchanged
        $customer->refresh();
        $this->assertEquals('Original', $customer->first_name);
        $this->assertEquals('09171234567', $customer->phone_number);

        // 6. Verify walk-in fallback
        $walkinCustomer = Customer::create([
            'first_name' => 'Walk-in',
            'last_name' => 'Customer',
            'phone_number' => '09121212121',
        ]);
        $walkin = Appointment::create([
            'customer_id' => $walkinCustomer->id,
            'appointment_date' => Carbon::today(),
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => 'confirmed',
            'total_price' => 500.00,
        ]);
        $walkinView = $this->view('receptionist._session_card', ['appointment' => $walkin]);
        $walkinView->assertSee('Walk-in');
    }

    public function test_receptionist_active_calendar_displays_guest_name_on_phone_collision()
    {
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create(['is_active' => true]);
        $receptionist->roles()->attach($receptionistRole);

        $customer = Customer::create([
            'first_name' => 'Original',
            'last_name' => 'Customer',
            'phone_number' => '09171234567',
            'nickname' => 'OriginalNickname'
        ]);

        $room = Room::create(['name' => 'Room A', 'status' => 'available', 'is_active' => true]);

        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'guest_first_name' => 'Jane Doe',
            'guest_phone' => '09171234567',
            'appointment_date' => Carbon::today(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000.00,
        ]);

        $response = $this->actingAs($receptionist)->get(route('receptionist.active'));
        $response->assertStatus(200);
        $response->assertSee('Jane Doe');
        $response->assertDontSee('Original Customer');
        $response->assertDontSee('OriginalNickname');
    }

    public function test_room_tracking_displays_guest_name_on_phone_collision()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($adminRole);

        $customer = Customer::create([
            'first_name' => 'Original',
            'last_name' => 'Customer',
            'phone_number' => '09171234567',
            'nickname' => 'OriginalNickname'
        ]);

        $room = Room::create(['name' => 'Room B', 'status' => 'available', 'is_active' => true]);

        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'guest_first_name' => 'Jane Doe',
            'guest_phone' => '09171234567',
            'appointment_date' => Carbon::today(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000.00,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.room-tracking'));
        $response->assertStatus(200);
        $response->assertSee('Jane Doe');
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

    public function test_invoice_web_and_pdf_display_guest_name_and_phone_on_collision()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->attach($adminRole);

        $customer = Customer::create([
            'first_name' => 'RegisteredFirst',
            'last_name' => 'RegisteredLast',
            'phone_number' => '09171234567',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'guest_first_name' => 'Specific Guest Name',
            'guest_phone' => '09998887777',
            'appointment_date' => Carbon::today(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 1500.00,
        ]);

        $appointment->payments()->create([
            'amount' => 1500.00,
            'payment_method' => 'cash',
            'paid_at' => Carbon::now(),
        ]);

        // 1. Web Invoice Detail View
        $response = $this->actingAs($admin)->get(route('admin.invoice', $appointment));
        $response->assertStatus(200);
        $response->assertSee('Specific Guest Name');
        $response->assertSee('09998887777');
        $response->assertDontSee('RegisteredFirst RegisteredLast');

        // 2. Web Invoice List Table View
        $listResponse = $this->actingAs($admin)->get(route('admin.appointment-invoice'));
        $listResponse->assertStatus(200);
        $listResponse->assertSee('Specific Guest Name');
        $listResponse->assertSee('09998887777');
        $listResponse->assertDontSee('RegisteredFirst RegisteredLast');

        // 3. PDF Invoice Preview View
        $pdfResponse = $this->actingAs($admin)->get(route('admin.invoice.preview', $appointment));
        $pdfResponse->assertStatus(200);

        // 4. Verify template rendering directly for exact HTML assertions
        $pdfView = $this->view('reports.appointment-invoice-pdf', [
            'appointment' => $appointment,
            'customer' => $customer,
            'clientName' => $appointment->client_name,
            'clientPhone' => $appointment->client_phone,
            'referenceNumber' => '#SPA-' . $appointment->id,
            'reportTitle' => 'Appointment Invoice',
            'dateRange' => 'Today',
            'generatedAt' => Carbon::now(),
            'preparedBy' => 'Admin User',
            'paymentMethods' => collect(['cash']),
            'payments' => $appointment->payments,
            'paidPayments' => $appointment->payments,
            'refundPayments' => collect(),
            'total' => 1500.00,
            'totalPaid' => 1500.00,
            'totalRefunded' => 0.00,
            'netPaid' => 1500.00,
            'balance' => 0.00,
            'services' => collect(),
        ]);
        $pdfView->assertSee('Specific Guest Name');
        $pdfView->assertSee('09998887777');
        $pdfView->assertDontSee('RegisteredFirst RegisteredLast');
    }

    public function test_transaction_log_and_cancelled_reports_use_guest_name()
    {
        $customer = Customer::create([
            'first_name' => 'RegisteredFirst',
            'last_name' => 'RegisteredLast',
            'phone_number' => '09171234567',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'guest_first_name' => 'Guest Transactor',
            'guest_phone' => '09991112222',
            'appointment_date' => Carbon::today(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 2000.00,
        ]);

        $appointment->payments()->create([
            'amount' => 2000.00,
            'payment_method' => 'cash',
            'paid_at' => Carbon::now(),
        ]);

        $analytics = app(\App\Services\SalesAnalyticsService::class);
        $request = new \Illuminate\Http\Request(['status' => 'completed']);
        $data = $analytics->getDashboardData($request);

        $this->assertNotEmpty($data['txLogData']['rows']);
        $matchedRow = collect($data['txLogData']['rows'])->firstWhere('customerName', 'Guest Transactor');
        $this->assertNotNull($matchedRow);
        $this->assertNull(collect($data['txLogData']['rows'])->firstWhere('customerName', 'RegisteredFirst RegisteredLast'));
    }

    public function test_guest_booking_does_not_overwrite_registered_customer_medical_notes_on_phone_collision()
    {
        $customer = Customer::create([
            'first_name' => 'RegisteredFirst',
            'last_name' => 'RegisteredLast',
            'phone_number' => '09175556677',
            'medical_notes' => 'Critical Allergy: Penicillin and Shellfish',
        ]);

        $tomorrow = Carbon::tomorrow();
        $service = $this->setupBookingEnvironment($tomorrow);

        // Guest booking with same phone number but different guest medical notes
        $response = $this->post(route('booking.store'), [
            'services' => [$service->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'guest_first_name' => 'Guest Visitor',
            'guest_phone' => '09175556677',
            'medical_notes' => 'Temporary sore shoulder',
            'source' => 'public',
        ]);

        $response->assertRedirect();

        // Ensure the registered customer profile's medical notes were NOT overwritten
        $customer->refresh();
        $this->assertEquals('Critical Allergy: Penicillin and Shellfish', $customer->medical_notes);

        // Ensure the appointment captured the guest's medical notes in appointment notes
        $appointment = Appointment::where('guest_phone', '09175556677')->latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('Temporary sore shoulder', $appointment->notes);
        $this->assertEquals('Guest Visitor', $appointment->client_name);
    }

    public function test_therapist_retention_counts_only_prior_period_returning_clients_to_same_therapist()
    {
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $therapistA = User::factory()->create(['is_active' => true]);
        $therapistA->roles()->attach($staffRole);

        $therapistB = User::factory()->create(['is_active' => true]);
        $therapistB->roles()->attach($staffRole);

        $client1 = Customer::create(['first_name' => 'Client', 'last_name' => 'One', 'phone_number' => '09110000001']);
        $client2 = Customer::create(['first_name' => 'Client', 'last_name' => 'Two', 'phone_number' => '09110000002']);
        $client3 = Customer::create(['first_name' => 'Client', 'last_name' => 'Three', 'phone_number' => '09110000003']);

        $reportingStart = Carbon::parse('2026-10-01');
        $reportingEnd = Carbon::parse('2026-10-31');
        $priorDate = Carbon::parse('2026-09-15');

        // Scenario 1: Client 1 completed visit with Therapist A prior, and returns to Therapist A in period -> Retained for A
        Appointment::create([
            'customer_id' => $client1->id,
            'user_id' => $therapistA->id,
            'appointment_date' => $priorDate,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 500,
        ]);
        Appointment::create([
            'customer_id' => $client1->id,
            'user_id' => $therapistA->id,
            'appointment_date' => Carbon::parse('2026-10-05'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 500,
        ]);

        // Scenario 2: Client 2 completed visit with Therapist A prior, but sees Therapist B in period -> Retained for B? NO.
        Appointment::create([
            'customer_id' => $client2->id,
            'user_id' => $therapistA->id,
            'appointment_date' => $priorDate,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => 'completed',
            'total_price' => 500,
        ]);
        Appointment::create([
            'customer_id' => $client2->id,
            'user_id' => $therapistB->id,
            'appointment_date' => Carbon::parse('2026-10-10'),
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'status' => 'completed',
            'total_price' => 500,
        ]);

        // Scenario 3: Client 3 has NO prior visit with Therapist A, but completes TWO visits with Therapist A during period -> NOT retained
        Appointment::create([
            'customer_id' => $client3->id,
            'user_id' => $therapistA->id,
            'appointment_date' => Carbon::parse('2026-10-12'),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'completed',
            'total_price' => 500,
        ]);
        Appointment::create([
            'customer_id' => $client3->id,
            'user_id' => $therapistA->id,
            'appointment_date' => Carbon::parse('2026-10-20'),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'completed',
            'total_price' => 500,
        ]);

        // Scenario 4: Several completed visits by Client 1 with Therapist A in the period do not increase numerator > 1
        Appointment::create([
            'customer_id' => $client1->id,
            'user_id' => $therapistA->id,
            'appointment_date' => Carbon::parse('2026-10-25'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 500,
        ]);

        // Scenario 5: Cancelled, no-show, confirmed, pending do not count towards numerator or denominator
        Appointment::create([
            'customer_id' => $client1->id,
            'user_id' => $therapistA->id,
            'appointment_date' => Carbon::parse('2026-10-28'),
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'status' => 'cancelled',
            'total_price' => 500,
        ]);
        Appointment::create([
            'customer_id' => $client1->id,
            'user_id' => $therapistA->id,
            'appointment_date' => Carbon::parse('2026-10-29'),
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'status' => 'no_show',
            'total_price' => 500,
        ]);

        $analytics = app(\App\Services\SalesAnalyticsService::class);
        $request = new \Illuminate\Http\Request([
            'period' => 'custom',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);
        $data = $analytics->getDashboardData($request);

        $therapistRetention = collect($data['therapistRetentionData']);

        // Check Therapist A:
        // Unique clients in period: Client 1 and Client 3 = 2 clients
        // Retained clients from prior period: Client 1 only = 1 client
        // Retention rate = (1 / 2) * 100 = 50.0%
        $rowA = $therapistRetention->firstWhere('therapist_id', $therapistA->id);
        $this->assertNotNull($rowA);
        $this->assertEquals(2, $rowA->unique_clients);
        $this->assertEquals(1, $rowA->repeat_clients);
        $this->assertEquals(50.0, $rowA->retention_rate);
        $this->assertEquals(4, $rowA->total_appointments); // 2 from Client 1 (Oct 5, Oct 25) + 2 from Client 3 (Oct 12, Oct 20) = 4 completed.

        // Check Therapist B:
        // Unique clients in period: Client 2 only = 1 client
        // Retained clients from prior period with Therapist B: None (Client 2 saw Therapist A prior) = 0
        // Retention rate = 0.0%
        $rowB = $therapistRetention->firstWhere('therapist_id', $therapistB->id);
        $this->assertNotNull($rowB);
        $this->assertEquals(1, $rowB->unique_clients);
        $this->assertEquals(0, $rowB->repeat_clients);
        $this->assertEquals(0.0, $rowB->retention_rate);
    }

    public function test_therapist_retention_handles_zero_clients_without_division_by_zero()
    {
        $analytics = app(\App\Services\SalesAnalyticsService::class);
        $request = new \Illuminate\Http\Request([
            'period' => 'custom',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]);
        $data = $analytics->getDashboardData($request);

        $this->assertIsArray($data['therapistRetentionData']);
        $this->assertEmpty($data['therapistRetentionData']);
    }
}
