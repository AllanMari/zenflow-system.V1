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
use App\Services\SalesAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class GuestClassificationRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $receptionist;
    private User $therapist;
    private Service $service;
    private Room $room;
    private string $bookingDate;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $recRole = Role::firstOrCreate(['name' => 'receptionist'], ['label' => 'Receptionist']);
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'Staff']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole);

        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach($recRole);

        $this->therapist = User::factory()->create([
            'first_name' => 'Elena',
            'last_name' => 'Therapist',
            'is_active' => true,
        ]);
        $this->therapist->roles()->attach($staffRole);

        $cat = ServiceCategory::create(['name' => 'Massage']);
        $roomCat = RoomCategory::create(['name' => 'Massage Room', 'code' => 'MR', 'is_active' => true]);
        $this->service = Service::create([
            'name' => 'Swedish Massage',
            'duration_minutes' => 60,
            'price' => 700,
            'category_id' => $cat->id,
            'room_category_id' => $roomCat->id,
            'is_active' => true,
            'therapists_required' => 1,
        ]);

        $this->room = Room::create([
            'name' => 'Room 1',
            'category_id' => $cat->id,
            'status' => 'available',
            'is_active' => true,
        ]);
        $this->room->roomCategories()->attach($roomCat->id);

        // Use tomorrow so after_or_equal:today validation always passes
        $this->bookingDate = Carbon::tomorrow()->toDateString();
        $dayOfWeek = Carbon::tomorrow()->dayOfWeek;

        BusinessHour::create([
            'day_of_week' => $dayOfWeek,
            'open_time' => '09:00:00',
            'close_time' => '21:00:00',
            'is_closed' => false,
        ]);

        WorkSchedule::create([
            'user_id' => $this->therapist->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '21:00:00',
            'is_day_off' => false,
        ]);
    }

    public function test_quick_book_lookup_distinguishes_registered_and_guest_customers(): void
    {
        $regUser = User::factory()->create();
        $registered = Customer::create([
            'user_id' => $regUser->id,
            'first_name' => 'Clara',
            'last_name' => 'Registered',
            'phone_number' => '09171112233',
        ]);

        $guest = Customer::create([
            'user_id' => null,
            'first_name' => 'Gina',
            'last_name' => 'GuestOnly',
            'phone_number' => '09171112244',
        ]);

        $response = $this->actingAs($this->receptionist)
            ->getJson(route('api.customers.lookup', ['phone' => '091711122']));

        $response->assertStatus(200);
        $items = collect($response->json());

        $regItem = $items->firstWhere('id', $registered->id);
        $guestItem = $items->firstWhere('id', $guest->id);

        $this->assertNotNull($regItem);
        $this->assertTrue($regItem['is_registered']);

        $this->assertNotNull($guestItem);
        $this->assertFalse($guestItem['is_registered']);
    }

    public function test_booking_store_for_registered_customer_does_not_set_guest_first_name_or_guest_phone(): void
    {
        $regUser = User::factory()->create([
            'first_name' => 'Maria',
            'last_name' => 'Clara',
        ]);
        $registered = Customer::create([
            'user_id' => $regUser->id,
            'first_name' => 'Maria',
            'last_name' => 'Clara',
            'phone_number' => '09175551111',
        ]);

        $response = $this->actingAs($this->receptionist)->postJson(route('booking.store'), [
            'customer_id' => $registered->id,
            'services' => [$this->service->id],
            'appointment_date' => $this->bookingDate,
            'start_time' => '10:00',
            'guest_first_name' => 'Maria Clara',
            'guest_phone' => '09175551111',
            'staff_id' => $this->therapist->id,
            'room_id' => $this->room->id,
            'source' => 'receptionist',
        ]);

        $response->assertStatus(200);

        $appointment = Appointment::latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals($registered->id, $appointment->customer_id);
        $this->assertNull($appointment->guest_first_name);
        $this->assertNull($appointment->guest_phone);
        $this->assertEquals('Maria Clara', $appointment->client_name);
    }

    public function test_booking_store_for_existing_guest_preserves_guest_classification_and_does_not_create_account(): void
    {
        $guest = Customer::create([
            'user_id' => null,
            'first_name' => 'Pedro',
            'last_name' => 'Walkin',
            'phone_number' => '09175552222',
        ]);

        $userCountBefore = User::count();
        $customerCountBefore = Customer::count();

        $response = $this->actingAs($this->receptionist)->postJson(route('booking.store'), [
            'customer_id' => $guest->id,
            'services' => [$this->service->id],
            'appointment_date' => $this->bookingDate,
            'start_time' => '12:00',
            'guest_first_name' => 'Pedro Walkin',
            'guest_phone' => '09175552222',
            'staff_id' => $this->therapist->id,
            'room_id' => $this->room->id,
        ]);

        $response->assertStatus(200);

        $this->assertEquals($userCountBefore, User::count());
        $this->assertEquals($customerCountBefore, Customer::count());

        $guest->refresh();
        $this->assertNull($guest->user_id);
        $this->assertTrue($guest->isGuest());

        $appointment = Appointment::latest('id')->first();
        $this->assertEquals($guest->id, $appointment->customer_id);
        $this->assertEquals('Pedro Walkin', $appointment->guest_first_name);
        $this->assertEquals('09175552222', $appointment->guest_phone);
        $this->assertEquals('Pedro Walkin', $appointment->client_name);
    }

    public function test_repeat_guest_booking_without_customer_id_reuses_guest_record_without_duplicates(): void
    {
        $existingGuest = Customer::create([
            'user_id' => null,
            'first_name' => 'Marco',
            'last_name' => 'Polo',
            'phone_number' => '09175553333',
        ]);

        $userCountBefore = User::count();
        $customerCountBefore = Customer::count();

        $response = $this->actingAs($this->receptionist)->postJson(route('booking.store'), [
            'customer_id' => null,
            'services' => [$this->service->id],
            'appointment_date' => $this->bookingDate,
            'start_time' => '14:00',
            'guest_first_name' => 'Marco Polo',
            'guest_phone' => '09175553333',
            'staff_id' => $this->therapist->id,
            'room_id' => $this->room->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($userCountBefore, User::count());
        $this->assertEquals($customerCountBefore, Customer::count());

        $appointment = Appointment::latest('id')->first();
        $this->assertEquals($existingGuest->id, $appointment->customer_id);
        $this->assertNull($existingGuest->fresh()->user_id);
    }

    public function test_registered_appointment_uses_customer_name_when_guest_first_name_is_null(): void
    {
        $regUser = User::factory()->create();
        $registered = Customer::create([
            'user_id' => $regUser->id,
            'first_name' => 'Elena',
            'last_name' => 'Vance',
            'phone_number' => '09176661111',
        ]);

        $appt = Appointment::create([
            'customer_id' => $registered->id,
            'appointment_date' => $this->bookingDate,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 700,
            'guest_first_name' => null,
            'guest_phone' => null,
        ]);

        $this->assertEquals('Elena Vance', $appt->client_name);
    }

    public function test_client_management_registered_tab_excludes_guests(): void
    {
        $regUser = User::factory()->create();
        Customer::create([
            'user_id' => $regUser->id,
            'first_name' => 'Alice',
            'last_name' => 'Registered',
            'phone_number' => '09172000001',
        ]);
        Customer::create([
            'user_id' => null,
            'first_name' => 'Bob',
            'last_name' => 'GuestOnly',
            'phone_number' => '09172000002',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.customers.index', ['account_type' => 'registered']));
        $response->assertStatus(200);
        $response->assertSee('Alice Registered');
        $response->assertDontSee('Bob GuestOnly');
    }

    public function test_client_management_guest_tab_excludes_registered_and_shows_notice(): void
    {
        $regUser = User::factory()->create();
        Customer::create([
            'user_id' => $regUser->id,
            'first_name' => 'Alice',
            'last_name' => 'Registered',
            'phone_number' => '09172000001',
        ]);
        Customer::create([
            'user_id' => null,
            'first_name' => 'Bob',
            'last_name' => 'GuestOnly',
            'phone_number' => '09172000002',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.customers.index', ['account_type' => 'guest']));
        $response->assertStatus(200);
        $response->assertSee('Bob GuestOnly');
        $response->assertDontSee('Alice Registered');
        $response->assertSee('booked without creating a registered account');
    }

    public function test_client_management_kpi_counts_registered_and_guest_separately(): void
    {
        $regUser = User::factory()->create();
        Customer::create([
            'user_id' => $regUser->id,
            'first_name' => 'Reg',
            'last_name' => 'One',
            'phone_number' => '09173000001',
        ]);
        Customer::create([
            'user_id' => null,
            'first_name' => 'Guest',
            'last_name' => 'One',
            'phone_number' => '09173000002',
        ]);
        Customer::create([
            'user_id' => null,
            'first_name' => 'Guest',
            'last_name' => 'Two',
            'phone_number' => '09173000003',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.customers.index'));
        $response->assertStatus(200);
        $response->assertViewHas('totalCustomers', 3);
        $response->assertViewHas('registeredCustomersCount', 1);
        $response->assertViewHas('guestCustomersCount', 2);
    }

    public function test_client_management_search_finds_both_registered_and_guest_customers(): void
    {
        $regUser = User::factory()->create();
        Customer::create([
            'user_id' => $regUser->id,
            'first_name' => 'Sophia',
            'last_name' => 'Cruz',
            'phone_number' => '09174000001',
        ]);
        Customer::create([
            'user_id' => null,
            'first_name' => 'Sophia',
            'last_name' => 'Guest',
            'phone_number' => '09174000002',
        ]);

        $response = $this->actingAs($this->receptionist)->get(route('receptionist.customers.index', ['search' => 'Sophia']));
        $response->assertStatus(200);
        $response->assertSee('Sophia Cruz');
        $response->assertSee('Sophia Guest');
    }

    public function test_client_management_show_returns_correct_is_registered_and_guest_booking_history(): void
    {
        $regUser = User::factory()->create();
        $registered = Customer::create([
            'user_id' => $regUser->id,
            'first_name' => 'Reg',
            'last_name' => 'Client',
            'phone_number' => '09175000001',
        ]);
        $guest = Customer::create([
            'user_id' => null,
            'first_name' => 'Guest',
            'last_name' => 'Client',
            'phone_number' => '09175000002',
        ]);

        $appt = Appointment::create([
            'customer_id' => $guest->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'completed',
            'total_price' => 700,
            'guest_first_name' => 'Guest Client',
            'guest_phone' => '09175000002',
        ]);
        $appt->services()->attach($this->service->id, [
            'service_name' => $this->service->name,
            'price_at_booking' => 700,
            'service_duration' => 60,
        ]);

        $regShow = $this->actingAs($this->admin)->getJson(route('admin.customers.show', $registered->id));
        $regShow->assertStatus(200)
            ->assertJsonPath('customer.is_registered', true);

        $guestShow = $this->actingAs($this->admin)->getJson(route('admin.customers.show', $guest->id));
        $guestShow->assertStatus(200)
            ->assertJsonPath('customer.is_registered', false)
            ->assertJsonPath('customer.completed_visits', 1)
            ->assertJsonCount(1, 'appointments');
    }

    public function test_ambiguous_phone_does_not_merge_guest_with_registered_customer(): void
    {
        $regUser1 = User::factory()->create();
        $reg1 = Customer::create([
            'user_id' => $regUser1->id,
            'first_name' => 'Twin',
            'last_name' => 'One',
            'phone_number' => '09179998888',
        ]);

        $regUser2 = User::factory()->create();
        $reg2 = Customer::create([
            'user_id' => $regUser2->id,
            'first_name' => 'Twin',
            'last_name' => 'Two',
            'phone_number' => '09179998888',
        ]);

        // Booking as a guest using the shared phone number must NOT merge with reg1 or reg2
        $response = $this->actingAs($this->receptionist)->postJson(route('booking.store'), [
            'customer_id' => null,
            'services' => [$this->service->id],
            'appointment_date' => $this->bookingDate,
            'start_time' => '16:00',
            'guest_first_name' => 'Guest Caller',
            'guest_phone' => '09179998888',
            'staff_id' => $this->therapist->id,
            'room_id' => $this->room->id,
        ]);

        $response->assertStatus(200);
        $appointment = Appointment::latest('id')->first();
        $this->assertNotEquals($reg1->id, $appointment->customer_id);
        $this->assertNotEquals($reg2->id, $appointment->customer_id);
        $this->assertNull($appointment->customer->user_id);
    }

    public function test_guest_retention_phone_normalization_remains_intact(): void
    {
        $guestPlaceholder = Customer::create([
            'user_id' => null,
            'first_name' => 'Walk-in',
            'last_name' => 'Guest',
            'phone_number' => '09170000000',
        ]);

        Appointment::create([
            'customer_id' => $guestPlaceholder->id,
            'appointment_date' => '2026-10-05',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 500,
            'guest_first_name' => 'Ana Guest',
            'guest_phone' => '+63 917-888-7777',
        ]);

        Appointment::create([
            'customer_id' => $guestPlaceholder->id,
            'appointment_date' => '2026-10-18',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'completed',
            'total_price' => 500,
            'guest_first_name' => 'Ana Guest',
            'guest_phone' => '0917 888 7777',
        ]);

        $service = app(SalesAnalyticsService::class);
        $data = $service->getDashboardData(new Request([
            'period' => 'custom',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));
        $ret = $data['customerRetention'];

        $this->assertEquals(1, $ret['customers_served']);
        $this->assertEquals(1, $ret['guest_customers']);
        $this->assertEquals(1, $ret['returning_customers']);
        $this->assertEquals(1, $ret['repeat_visits']);
    }
}
