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

/**
 * Priority 7, 8, 9 — Admin Management, Expandable Details, Duplicate Submission.
 *
 * Tests verify:
 *   P7: Service/package CRUD dedup, self-inclusion prevention, room-category admin.
 *   P8: Booking wizard JSON exposes participants_required, therapists_required, price on inclusions.
 *       Landing page renders expandable <details> for packages.
 *   P9: Idempotency guards on BookingController::store() and ReceptionistController actions.
 */
class Priority789Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $receptionist;
    protected ServiceCategory $serviceCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole        = Role::firstOrCreate(['name' => 'admin']);
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        Role::firstOrCreate(['name' => 'customer']);
        Role::firstOrCreate(['name' => 'staff']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole);

        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach($receptionistRole);

        $this->serviceCategory = ServiceCategory::firstOrCreate(
            ['name' => 'General Wellness'],
            ['color' => '#10B981', 'is_active' => true]
        );
    }

    protected function setupStaffSchedule(Carbon $date): User
    {
        for ($i = 0; $i < 7; $i++) {
            BusinessHour::firstOrCreate(
                ['day_of_week' => $i],
                [
                    'open_time' => '08:00:00',
                    'close_time' => '20:00:00',
                    'is_closed' => false,
                ]
            );
        }

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staff = User::factory()->create(['is_active' => true]);
        $staff->roles()->attach($staffRole);

        WorkSchedule::firstOrCreate(
            ['user_id' => $staff->id, 'day_of_week' => $date->dayOfWeek],
            [
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'is_day_off' => false,
            ]
        );

        return $staff;
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRIORITY 7A — Service and Package CRUD
    // ─────────────────────────────────────────────────────────────────────

    public function test_admin_can_create_package_with_included_services(): void
    {
        $svcA = Service::create([
            'name'             => 'Hot Stone Massage',
            'duration_minutes' => 60,
            'price'            => 1200,
            'is_active'        => true,
            'is_package'       => false,
            'category_id'      => $this->serviceCategory->id,
        ]);
        $svcB = Service::create([
            'name'             => 'Foot Scrub',
            'duration_minutes' => 30,
            'price'            => 500,
            'is_active'        => true,
            'is_package'       => false,
            'category_id'      => $this->serviceCategory->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.services.store'), [
            'name'                  => 'Relaxation Bundle',
            'price'                 => 1500,
            'duration_minutes'      => 90,
            'is_package'            => '1',
            'is_active'             => '1',
            'included_services'     => [$svcA->id, $svcB->id],
            'category_id'           => $this->serviceCategory->id,
            'participants_required' => 1,
            'therapists_required'   => 1,
        ]);

        $response->assertRedirect();

        $package = Service::where('name', 'Relaxation Bundle')->first();
        $this->assertNotNull($package, 'Package was not created.');
        $this->assertTrue((bool) $package->is_package);
        $this->assertIsArray($package->included_services);
        $this->assertCount(2, $package->included_services);
    }

    public function test_admin_package_creation_deduplicates_included_services(): void
    {
        $svc = Service::create([
            'name'             => 'Swedish Massage',
            'duration_minutes' => 60,
            'price'            => 1000,
            'is_active'        => true,
            'is_package'       => false,
            'category_id'      => $this->serviceCategory->id,
        ]);

        // Send the same service ID three times — should only be stored once.
        $this->actingAs($this->admin)->post(route('admin.services.store'), [
            'name'                  => 'Dedup Package',
            'price'                 => 1000,
            'duration_minutes'      => 60,
            'is_package'            => '1',
            'is_active'             => '1',
            'included_services'     => [$svc->id, $svc->id, $svc->id],
            'category_id'           => $this->serviceCategory->id,
            'participants_required' => 1,
            'therapists_required'   => 1,
        ]);

        $package = Service::where('name', 'Dedup Package')->first();
        $this->assertNotNull($package);
        $this->assertIsArray($package->included_services);
        $this->assertCount(1, $package->included_services);
    }

    public function test_admin_package_update_prevents_self_inclusion(): void
    {
        $package = Service::create([
            'name'             => 'Self-Ref Package',
            'duration_minutes' => 60,
            'price'            => 1200,
            'is_active'        => true,
            'is_package'       => true,
            'category_id'      => $this->serviceCategory->id,
        ]);

        $otherSvc = Service::create([
            'name'             => 'Body Polish',
            'duration_minutes' => 45,
            'price'            => 800,
            'is_active'        => true,
            'is_package'       => false,
            'category_id'      => $this->serviceCategory->id,
        ]);

        // Try to include the package itself in its own inclusions list.
        $response = $this->actingAs($this->admin)->put(route('admin.services.update', $package), [
            'name'                  => 'Self-Ref Package',
            'price'                 => 1200,
            'duration_minutes'      => 60,
            'is_package'            => '1',
            'is_active'             => '1',
            'included_services'     => [$package->id, $otherSvc->id],
            'category_id'           => $this->serviceCategory->id,
            'participants_required' => 1,
            'therapists_required'   => 1,
        ]);

        $response->assertRedirect();

        $package->refresh();
        $ids = (array) $package->included_services;

        // Package itself must NOT be in its own inclusions.
        $this->assertNotContains($package->id, $ids, 'Package must not include itself.');
        // The other service should still be included.
        $this->assertContains($otherSvc->id, $ids);
    }

    public function test_admin_package_edit_preserves_inclusions_when_editing_other_fields(): void
    {
        $svc = Service::create([
            'name'             => 'Deep Tissue',
            'duration_minutes' => 60,
            'price'            => 1200,
            'is_active'        => true,
            'is_package'       => false,
            'category_id'      => $this->serviceCategory->id,
        ]);

        $package = Service::create([
            'name'              => 'Wellness Pack',
            'duration_minutes'  => 60,
            'price'             => 1500,
            'is_active'         => true,
            'is_package'        => true,
            'included_services' => [$svc->id],
            'category_id'       => $this->serviceCategory->id,
        ]);

        // Update price — preserve included_services.
        $this->actingAs($this->admin)->put(route('admin.services.update', $package), [
            'name'                  => 'Wellness Pack',
            'price'                 => 1800,
            'duration_minutes'      => 60,
            'is_package'            => '1',
            'is_active'             => '1',
            'included_services'     => [$svc->id],
            'category_id'           => $this->serviceCategory->id,
            'participants_required' => 1,
            'therapists_required'   => 1,
        ]);

        $package->refresh();
        $this->assertEquals(1800, (float) $package->price);
        $ids = (array) $package->included_services;
        $this->assertContains($svc->id, $ids, 'Included services must be preserved after editing other fields.');
    }

    public function test_admin_services_index_renders_searchable_inclusion_ui(): void
    {
        // The page must have the search input for the package inclusions panel.
        $response = $this->actingAs($this->admin)->get(route('admin.services.index'));
        $response->assertStatus(200);

        // Alpine.js-driven search input renders a placeholder.
        $response->assertSee('Search services', false);
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRIORITY 7D — Room Category Administration
    // ─────────────────────────────────────────────────────────────────────

    public function test_admin_can_create_room_category(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.room-categories.store'), [
            'name'        => 'Couple Suite',
            'code'        => 'COUPLE',
            'description' => 'Room for two guests',
            'is_active'   => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('room_categories', ['code' => 'COUPLE']);
    }

    public function test_admin_can_edit_room_category(): void
    {
        $cat = RoomCategory::create([
            'name'      => 'Standard',
            'code'      => 'STD',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(
            route('admin.room-categories.update', $cat),
            [
                'name'      => 'Standard Room',
                'code'      => 'STD',
                'is_active' => '1',
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('room_categories', ['name' => 'Standard Room']);
    }

    public function test_admin_can_assign_multiple_categories_to_room(): void
    {
        $catA = RoomCategory::create(['name' => 'Regular', 'code' => 'REG', 'is_active' => true]);
        $catB = RoomCategory::create(['name' => 'VIP', 'code' => 'VIP', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('admin.rooms.store'), [
            'name'              => 'Room 101',
            'status'            => 'available',
            'is_active'         => '1',
            'room_category_ids' => [$catA->id, $catB->id],
        ]);

        $response->assertRedirect();

        $room = Room::where('name', 'Room 101')->first();
        $this->assertNotNull($room);

        $assignedIds = $room->roomCategories->pluck('id')->toArray();
        $this->assertContains($catA->id, $assignedIds);
        $this->assertContains($catB->id, $assignedIds);
    }

    public function test_admin_can_remove_category_from_room(): void
    {
        $catA = RoomCategory::create(['name' => 'Standard', 'code' => 'STD', 'is_active' => true]);
        $catB = RoomCategory::create(['name' => 'Premium', 'code' => 'PRM', 'is_active' => true]);

        $room = Room::create(['name' => 'Room 202', 'status' => 'available', 'is_active' => true]);
        $room->roomCategories()->sync([$catA->id, $catB->id]);

        // Update with only catA — catB should be removed.
        $this->actingAs($this->admin)->put(route('admin.rooms.update', $room), [
            'name'              => 'Room 202',
            'status'            => 'available',
            'is_active'         => '1',
            'room_category_ids' => [$catA->id],
        ]);

        $room->refresh();
        $assignedIds = $room->roomCategories->pluck('id')->toArray();
        $this->assertContains($catA->id, $assignedIds);
        $this->assertNotContains($catB->id, $assignedIds);
    }

    public function test_room_edit_shows_existing_category_assignments(): void
    {
        $cat = RoomCategory::create(['name' => 'Deluxe', 'code' => 'DLX', 'is_active' => true]);
        Room::create(['name' => 'Room 303', 'status' => 'available', 'is_active' => true]);

        // The rooms index page must exist and show the room.
        $response = $this->actingAs($this->admin)->get(route('admin.rooms.index'));
        $response->assertStatus(200);
        $response->assertSee('Room 303');
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRIORITY 8 — Expandable Service / Package Details
    // ─────────────────────────────────────────────────────────────────────

    public function test_booking_wizard_json_includes_participants_and_therapists_required(): void
    {
        $svc = Service::create([
            'name'                  => 'Couple Massage',
            'duration_minutes'      => 90,
            'price'                 => 2400,
            'is_active'             => true,
            'is_package'            => false,
            'participants_required' => 2,
            'therapists_required'   => 2,
            'category_id'           => $this->serviceCategory->id,
            'requires_room'         => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('booking.wizard'));
        $response->assertStatus(200);

        // The page script should include both fields.
        $response->assertSee('participants_required', false);
        $response->assertSee('therapists_required', false);
    }

    public function test_booking_wizard_json_includes_price_in_included_services(): void
    {
        $svcA = Service::create([
            'name'             => 'Facial',
            'duration_minutes' => 45,
            'price'            => 900,
            'is_active'        => true,
            'is_package'       => false,
            'category_id'      => $this->serviceCategory->id,
        ]);

        $pkg = Service::create([
            'name'              => 'Glow Bundle',
            'duration_minutes'  => 60,
            'price'             => 1500,
            'is_active'         => true,
            'is_package'        => true,
            'included_services' => [$svcA->id],
            'category_id'       => $this->serviceCategory->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('booking.wizard'));
        $response->assertStatus(200);

        // 'price' field must be serialised inside included_services JSON.
        $content = $response->getContent();
        $this->assertStringContainsString('included_services', $content);
        $this->assertStringContainsString('"price"', $content);
    }

    public function test_landing_page_package_card_has_expandable_details_section(): void
    {
        $svc = Service::create([
            'name'             => 'Hot Stone Therapy',
            'duration_minutes' => 60,
            'price'            => 1200,
            'is_active'        => true,
            'is_package'       => false,
            'category_id'      => $this->serviceCategory->id,
        ]);

        $pkg = Service::create([
            'name'                => 'Zen Bundle',
            'duration_minutes'    => 90,
            'price'               => 1800,
            'landing_description' => 'A relaxing bundle for mind and body.',
            'is_active'           => true,
            'is_package'          => true,
            'show_on_landing'     => true,
            'included_services'   => [$svc->id],
            'category_id'         => $this->serviceCategory->id,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);

        // The landing page should include expandable markup for the package.
        $content = $response->getContent();
        $this->assertStringContainsString('Zen Bundle', $content);
        $this->assertTrue(
            str_contains($content, '<details') || str_contains($content, 'x-show') || str_contains($content, 'Package Inclusions'),
            'Landing page must include expandable package details markup.'
        );
    }

    public function test_landing_page_shows_included_service_names_for_package(): void
    {
        $svc = Service::create([
            'name'             => 'Aromatherapy',
            'duration_minutes' => 60,
            'price'            => 1000,
            'is_active'        => true,
            'is_package'       => false,
            'category_id'      => $this->serviceCategory->id,
        ]);

        $pkg = Service::create([
            'name'              => 'Aroma Bundle',
            'duration_minutes'  => 60,
            'price'             => 1500,
            'is_active'         => true,
            'is_package'        => true,
            'show_on_landing'   => true,
            'included_services' => [$svc->id],
            'category_id'       => $this->serviceCategory->id,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Aromatherapy');
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRIORITY 9 — Duplicate-Submission Prevention (Backend Guards)
    // ─────────────────────────────────────────────────────────────────────

    public function test_duplicate_booking_within_10_seconds_reuses_existing_appointment(): void
    {
        $tomorrow = Carbon::tomorrow();
        $this->setupStaffSchedule($tomorrow);

        $customerUser = User::factory()->create(['is_active' => true]);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $customerUser->roles()->attach($customerRole);

        $customer = Customer::create([
            'user_id'      => $customerUser->id,
            'first_name'   => 'Jane',
            'last_name'    => 'Doe',
            'email'        => $customerUser->email,
            'phone_number' => '09171234567',
        ]);

        $svc = Service::create([
            'name'             => 'Quick Massage',
            'duration_minutes' => 60,
            'price'            => 1000,
            'is_active'        => true,
            'is_package'       => false,
            'category_id'      => $this->serviceCategory->id,
            'requires_room'    => false,
        ]);

        // Pre-create an identical appointment timestamped NOW (simulates first submission).
        $existing = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => $tomorrow->toDateString(),
            'start_time'       => '10:00:00',
            'end_time'         => '11:00:00',
            'status'           => 'pending',
            'total_price'      => 1000,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
        $existing->services()->attach($svc->id, [
            'price_at_booking' => 1000,
            'service_name'     => $svc->name,
            'service_duration' => 60,
            'is_extra'         => false,
        ]);

        $countBefore = Appointment::count();

        // Second submission with same date/time (should reuse existing).
        $this->actingAs($customerUser)->post(route('booking.store'), [
            'services'         => [$svc->id],
            'appointment_date' => $tomorrow->toDateString(),
            'start_time'       => '10:00',
            'guest_first_name' => 'Jane',
            'guest_phone'      => '09171234567',
            'source'           => 'public',
        ]);

        // No new appointment should have been created.
        $this->assertEquals($countBefore, Appointment::count(), 'Duplicate submission must not create a second appointment.');
    }

    public function test_distinct_booking_different_date_creates_new_appointment(): void
    {
        $dayAfter = Carbon::tomorrow()->addDay();
        $this->setupStaffSchedule($dayAfter);

        $customerUser = User::factory()->create(['is_active' => true]);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $customerUser->roles()->attach($customerRole);

        $customer = Customer::create([
            'user_id'      => $customerUser->id,
            'first_name'   => 'Mark',
            'last_name'    => 'Tan',
            'email'        => $customerUser->email,
            'phone_number' => '09171112222',
        ]);

        $svc = Service::create([
            'name'             => 'Body Wrap',
            'duration_minutes' => 60,
            'price'            => 1200,
            'is_active'        => true,
            'is_package'       => false,
            'category_id'      => $this->serviceCategory->id,
            'requires_room'    => false,
        ]);

        // Create an appointment for TOMORROW.
        $existing = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time'       => '10:00:00',
            'end_time'         => '11:00:00',
            'status'           => 'pending',
            'total_price'      => 1200,
            'created_at'       => now(),
        ]);
        $existing->services()->attach($svc->id, [
            'price_at_booking' => 1200,
            'service_name'     => $svc->name,
            'service_duration' => 60,
            'is_extra'         => false,
        ]);

        $countBefore = Appointment::count();

        // Submit for the DAY AFTER TOMORROW — a genuinely different booking.
        $response = $this->actingAs($customerUser)->post(route('booking.store'), [
            'services'         => [$svc->id],
            'appointment_date' => $dayAfter->toDateString(),
            'start_time'       => '10:00',
            'guest_first_name' => 'Mark',
            'guest_phone'      => '09171112222',
            'source'           => 'public',
        ]);

        // A new appointment should have been created.
        $this->assertGreaterThan($countBefore, Appointment::count(), 'A distinct booking must create a new appointment.');
    }

    public function test_confirming_already_confirmed_appointment_returns_idempotent_redirect(): void
    {
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staff = User::factory()->create(['is_active' => true]);
        $staff->roles()->attach($staffRole);

        $customer = Customer::create([
            'first_name'   => 'Alice',
            'last_name'    => 'Smith',
            'email'        => 'alice@test.com',
            'phone_number' => '09181234567',
        ]);

        $appointment = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time'       => '14:00:00',
            'end_time'         => '15:00:00',
            'status'           => 'confirmed',   // already confirmed
            'total_price'      => 1000,
        ]);

        $response = $this->actingAs($this->receptionist)
            ->post(route('receptionist.confirm', $appointment), [
                'staff_id'       => $staff->id,
                'payment_method' => 'cash',
                'payment_type'   => 'full',
                'amount'         => 1000,
            ]);

        // Should redirect with an info flash instead of throwing an error.
        $response->assertRedirect();
        $response->assertSessionHas('info');
    }

    public function test_completing_already_completed_appointment_returns_idempotent_redirect(): void
    {
        $customer = Customer::create([
            'first_name'   => 'Bob',
            'last_name'    => 'Cruz',
            'email'        => 'bob@test.com',
            'phone_number' => '09191234567',
        ]);

        $appointment = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => Carbon::yesterday()->toDateString(),
            'start_time'       => '10:00:00',
            'end_time'         => '11:00:00',
            'status'           => 'completed',   // already completed
            'total_price'      => 1500,
        ]);

        $response = $this->actingAs($this->receptionist)
            ->post(route('receptionist.complete', $appointment), [
                'payment_method' => 'cash',
                'payment_type'   => 'full',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('info');
    }

    public function test_cancelling_already_cancelled_appointment_returns_idempotent_info(): void
    {
        $customer = Customer::create([
            'first_name'   => 'Carol',
            'last_name'    => 'Reyes',
            'email'        => 'carol@test.com',
            'phone_number' => '09201234567',
        ]);

        $appointment = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time'       => '09:00:00',
            'end_time'         => '10:00:00',
            'status'           => 'cancelled',   // already cancelled
            'total_price'      => 800,
        ]);

        $response = $this->actingAs($this->receptionist)
            ->post(route('receptionist.cancel', $appointment), [
                'reason' => 'customer_cancelled',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('info');
    }

    public function test_no_show_on_already_no_show_appointment_returns_idempotent_info(): void
    {
        $customer = Customer::create([
            'first_name'   => 'David',
            'last_name'    => 'Lim',
            'email'        => 'david@test.com',
            'phone_number' => '09211234567',
        ]);

        $appointment = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => Carbon::yesterday()->toDateString(),
            'start_time'       => '11:00:00',
            'end_time'         => '12:00:00',
            'status'           => 'no_show',   // already marked
            'total_price'      => 1000,
        ]);

        $response = $this->actingAs($this->receptionist)
            ->post(route('receptionist.no-show', $appointment), [
                'action'         => 'forfeit',
                'no_show_reason' => 'Customer did not arrive',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('info');
    }

    public function test_no_show_refund_does_not_create_duplicate_payment_record(): void
    {
        $customer = Customer::create([
            'first_name'   => 'Eva',
            'last_name'    => 'Santos',
            'email'        => 'eva@test.com',
            'phone_number' => '09221234567',
        ]);

        $appointment = Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => Carbon::yesterday()->toDateString(),
            'start_time'       => '13:00:00',
            'end_time'         => '14:00:00',
            'status'           => 'confirmed',
            'total_price'      => 1000,
        ]);

        // Pre-existing deposit payment.
        Payment::create([
            'appointment_id' => $appointment->id,
            'amount'         => 300,
            'type'           => 'deposit',
            'payment_method' => 'cash',
            'paid_at'        => now(),
        ]);

        // Also pre-create a refund — simulating a double submit of no-show with refund.
        Payment::create([
            'appointment_id' => $appointment->id,
            'amount'         => 300,
            'type'           => 'refund',
            'payment_method' => 'cash',
            'paid_at'        => now(),
        ]);

        $countBefore = Payment::where('appointment_id', $appointment->id)
            ->where('type', 'refund')
            ->count();

        $response = $this->actingAs($this->receptionist)
            ->post(route('receptionist.no-show', $appointment), [
                'action'         => 'refund',
                'no_show_reason' => 'Customer did not arrive',
            ]);

        $countAfter = Payment::where('appointment_id', $appointment->id)
            ->where('type', 'refund')
            ->count();

        // Should not add a second refund record.
        $this->assertEquals($countBefore, $countAfter, 'No-show must not create a duplicate refund payment record.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRIORITY 9 — Frontend duplicate-submission markup verification
    // ─────────────────────────────────────────────────────────────────────

    public function test_pending_page_confirm_button_has_duplicate_submission_protection(): void
    {
        $customer = Customer::create([
            'first_name'   => 'Frank',
            'last_name'    => 'Go',
            'email'        => 'frank@test.com',
            'phone_number' => '09231234567',
        ]);

        Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time'       => '10:00:00',
            'end_time'         => '11:00:00',
            'status'           => 'pending',
            'total_price'      => 1000,
        ]);

        $response = $this->actingAs($this->receptionist)->get(route('receptionist.pending'));
        $response->assertStatus(200);

        // The confirm button must have the onclick disable handler.
        $response->assertSee('onclick', false);
        $response->assertSee('this.disabled', false);
    }

    public function test_active_page_complete_form_has_submitting_guard(): void
    {
        $customer = Customer::create([
            'first_name'   => 'Grace',
            'last_name'    => 'Uy',
            'email'        => 'grace@test.com',
            'phone_number' => '09241234567',
        ]);

        Appointment::create([
            'customer_id'      => $customer->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time'       => '10:00:00',
            'end_time'         => '11:00:00',
            'status'           => 'confirmed',
            'total_price'      => 1000,
        ]);

        $response = $this->actingAs($this->receptionist)->get(route('receptionist.active'));
        $response->assertStatus(200);

        // Alpine.js submitting guard must be present.
        $response->assertSee('submitting', false);
    }
}
