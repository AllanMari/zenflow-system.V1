<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentPackageSelection;
use App\Models\PackageChoiceGroup;
use App\Models\PackageChoiceOption;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuDataAndPackageChoicesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $receptionist;
    protected User $staff;
    protected ServiceCategory $massageCat;
    protected ServiceCategory $faceBodyCat;
    protected ServiceCategory $packagesCat;
    protected ServiceCategory $coupleCat;
    protected RoomCategory $standardRoomCat;
    protected RoomCategory $coupleRoomCat;
    protected RoomCategory $vipRoomCat;
    protected Room $coupleRoom;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->receptionist = User::factory()->create();
        $this->receptionist->roles()->attach($receptionistRole);

        $this->staff = User::factory()->create(['is_active' => true]);
        $this->staff->roles()->attach($staffRole);

        $staff2 = User::factory()->create(['is_active' => true]);
        $staff2->roles()->attach($staffRole);

        foreach ([$this->staff, $staff2] as $st) {
            for ($i = 0; $i <= 6; $i++) {
                \App\Models\WorkSchedule::create([
                    'user_id' => $st->id,
                    'day_of_week' => $i,
                    'start_time' => '08:00:00',
                    'end_time' => '21:00:00',
                    'is_day_off' => false,
                ]);
            }
        }

        $this->massageCat = ServiceCategory::create(['name' => 'Massage Services', 'is_active' => true, 'show_on_landing' => true]);
        $this->faceBodyCat = ServiceCategory::create(['name' => 'Face & Body', 'is_active' => true, 'show_on_landing' => true]);
        $this->packagesCat = ServiceCategory::create(['name' => 'Packages', 'is_active' => true, 'show_on_landing' => true]);
        $this->coupleCat = ServiceCategory::create(['name' => 'Couple Spa', 'is_active' => true, 'show_on_landing' => true]);

        $this->standardRoomCat = RoomCategory::create(['name' => 'Standard Massage Room', 'code' => 'SMR', 'is_active' => true]);
        $this->coupleRoomCat = RoomCategory::create(['name' => 'Couple Room', 'code' => 'CR', 'is_active' => true]);
        $this->vipRoomCat = RoomCategory::create(['name' => 'VIP Suite', 'code' => 'VIP', 'is_active' => true]);

        $this->coupleRoom = Room::create([
            'room_category_id' => $this->coupleRoomCat->id,
            'name' => 'Couple Suite 1',
            'room_number' => 'CS1',
            'is_active' => true,
            'status' => 'available',
        ]);
        $this->coupleRoom->roomCategories()->attach($this->coupleRoomCat->id);
    }

    public function test_draft_service_without_duration_is_inactive_and_hidden_from_booking(): void
    {
        // 1. Create a Face & Body draft service without duration
        $draft = Service::create([
            'category_id' => $this->faceBodyCat->id,
            'name' => 'Body Scrub',
            'duration_minutes' => null,
            'price' => 650.00,
            'is_package' => false,
            'is_active' => false,
            'show_on_landing' => false,
        ]);

        $this->assertNull($draft->duration_minutes);
        $this->assertFalse((bool) $draft->is_active);

        // 2. Draft service must NOT appear on public landing page
        $landingRes = $this->get(route('landing'));
        $landingRes->assertStatus(200);
        $landingRes->assertDontSee('Body Scrub');

        // 3. Draft service must NOT be bookable directly
        $bookingRes = $this->postJson(route('booking.store'), [
            'services' => [$draft->id],
            'appointment_date' => Carbon::tomorrow('Asia/Manila')->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '15:00',
            'guest_first_name' => 'Juan',
            'guest_phone' => '09123456789',
            'privacy_consented' => '1',
        ]);

        $bookingRes->assertStatus(422);
        $bookingRes->assertJsonFragment(['success' => false]);
    }

    public function test_admin_cannot_activate_service_without_duration(): void
    {
        $draft = Service::create([
            'category_id' => $this->faceBodyCat->id,
            'name' => 'Foot Spa',
            'duration_minutes' => null,
            'price' => 500.00,
            'is_package' => false,
            'is_active' => false,
            'show_on_landing' => false,
        ]);

        // 1. Attempt to activate via quick status toggle without duration
        $resToggle = $this->actingAs($this->admin)->put(route('admin.services.update', $draft), [
            'is_active' => 1,
        ]);
        $resToggle->assertSessionHas('error');
        $this->assertFalse((bool) $draft->fresh()->is_active);

        // 2. Attempt to activate via full edit form without duration -> auto-forces draft
        $resFull = $this->actingAs($this->admin)->put(route('admin.services.update', $draft), [
            'category_id' => $this->faceBodyCat->id,
            'name' => 'Foot Spa',
            'price' => 500.00,
            'is_active' => 1,
            'duration_minutes' => null,
            'participants_required' => 1,
            'therapists_required' => 1,
        ]);
        $this->assertFalse((bool) $draft->fresh()->is_active);
        $this->assertNull($draft->fresh()->duration_minutes);

        // Update with valid duration and activate
        $resSuccess = $this->actingAs($this->admin)->put(route('admin.services.update', $draft), [
            'category_id' => $this->faceBodyCat->id,
            'name' => 'Foot Spa',
            'price' => 500.00,
            'is_active' => 1,
            'duration_minutes' => 45,
            'show_on_landing' => 1,
            'participants_required' => 1,
            'therapists_required' => 1,
        ]);

        $resSuccess->assertSessionHas('success');
        $this->assertTrue((bool) $draft->fresh()->is_active);
        $this->assertEquals(45, $draft->fresh()->duration_minutes);
    }

    public function test_packages_use_exact_prices_and_authoritative_durations(): void
    {
        $bodyScrub = Service::create([
            'category_id' => $this->faceBodyCat->id,
            'name' => 'Body Scrub',
            'duration_minutes' => null,
            'price' => 650.00,
            'is_package' => false,
            'is_active' => false,
        ]);

        $facial = Service::create([
            'category_id' => $this->faceBodyCat->id,
            'name' => 'Facial Cleansing',
            'duration_minutes' => null,
            'price' => 450.00,
            'is_package' => false,
            'is_active' => false,
        ]);

        // Package 1: 90 minutes, ₱860. Inclusions Body Scrub + Facial Cleansing
        $pkg1 = Service::create([
            'category_id' => $this->packagesCat->id,
            'name' => 'Package 1',
            'duration_minutes' => 90,
            'price' => 860.00,
            'is_package' => true,
            'included_services' => [$bodyScrub->id, $facial->id],
            'is_active' => true,
            'show_on_landing' => true,
        ]);

        $this->assertEquals(90, $pkg1->duration_minutes);
        $this->assertEquals(860.00, (float) $pkg1->price);
        $this->assertEquals([$bodyScrub->id, $facial->id], $pkg1->included_services);
    }

    public function test_couple_package_choice_group_validation_and_persistence(): void
    {
        $alexandria = Service::create([
            'category_id' => $this->massageCat->id,
            'name' => 'Alexandria Massage',
            'duration_minutes' => 60,
            'price' => 550.00,
            'is_package' => false,
            'is_active' => true,
        ]);

        $swedish = Service::create([
            'category_id' => $this->massageCat->id,
            'name' => 'Swedish Massage',
            'duration_minutes' => 60,
            'price' => 530.00,
            'is_package' => false,
            'is_active' => true,
        ]);

        $footSpa = Service::create([
            'category_id' => $this->faceBodyCat->id,
            'name' => 'Foot Spa',
            'duration_minutes' => null,
            'price' => 500.00,
            'is_package' => false,
            'is_active' => false,
        ]);

        // Couple 1: ₱2,299, 120m, 2 participants, 2 therapists, Couple Room
        $couple1 = Service::create([
            'category_id' => $this->coupleCat->id,
            'name' => 'Couple 1',
            'duration_minutes' => 120,
            'price' => 2299.00,
            'is_package' => true,
            'requires_room' => true,
            'room_category_id' => $this->coupleRoomCat->id,
            'participants_required' => 2,
            'therapists_required' => 2,
            'included_services' => [$footSpa->id],
            'is_active' => true,
            'show_on_landing' => true,
        ]);

        $group = PackageChoiceGroup::create([
            'service_id' => $couple1->id,
            'label' => 'Choose Your Massage',
            'is_required' => true,
            'max_selections' => 1,
        ]);

        $optAlexandria = PackageChoiceOption::create([
            'choice_group_id' => $group->id,
            'service_id' => $alexandria->id,
            'sort_order' => 1,
        ]);

        $optSwedish = PackageChoiceOption::create([
            'choice_group_id' => $group->id,
            'service_id' => $swedish->id,
            'sort_order' => 2,
        ]);

        $targetDate = Carbon::tomorrow('Asia/Manila')->format('Y-m-d');

        // 1. Attempt booking Couple 1 without choice selection -> must fail validation
        $failRes = $this->postJson(route('booking.store'), [
            'services' => [$couple1->id],
            'appointment_date' => $targetDate,
            'start_time' => '13:00',
            'end_time' => '15:00',
            'guest_first_name' => 'Maria',
            'guest_phone' => '09123456789',
            'privacy_consented' => '1',
        ]);

        $failRes->assertStatus(422);
        $failRes->assertJsonFragment([
            'message' => "Please select an option for 'Choose Your Massage' in Couple 1.",
        ]);

        // 2. Attempt booking with invalid choice option -> must fail
        $invalidRes = $this->postJson(route('booking.store'), [
            'services' => [$couple1->id],
            'package_choices' => [
                $group->id => 99999,
            ],
            'appointment_date' => $targetDate,
            'start_time' => '13:00',
            'end_time' => '15:00',
            'guest_first_name' => 'Maria',
            'guest_phone' => '09123456789',
            'privacy_consented' => '1',
        ]);

        $invalidRes->assertStatus(422);

        // 3. Successful booking with Alexandria Massage option
        $successRes = $this->postJson(route('booking.store'), [
            'services' => [$couple1->id],
            'package_choices' => [
                $group->id => $optAlexandria->id,
            ],
            'appointment_date' => $targetDate,
            'start_time' => '13:00',
            'end_time' => '15:00',
            'guest_first_name' => 'Maria',
            'guest_phone' => '09123456789',
            'privacy_consented' => '1',
        ]);

        $successRes->assertStatus(200);

        $appointment = Appointment::latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals(2299.00, (float) $appointment->total_price);

        // Verify package selection was persisted with snapshot
        $selection = AppointmentPackageSelection::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($selection);
        $this->assertEquals($group->id, $selection->choice_group_id);
        $this->assertEquals($alexandria->id, $selection->selected_service_id);
        $this->assertEquals('Choose Your Massage', $selection->group_label_snapshot);
        $this->assertEquals('Alexandria Massage', $selection->selected_service_name_snapshot);
    }

    public function test_receptionist_and_staff_dashboards_render_package_choices_without_error(): void
    {
        $pkg = Service::create([
            'category_id' => $this->coupleCat->id,
            'name' => 'Couple 2',
            'duration_minutes' => 90,
            'price' => 1449.00,
            'is_package' => true,
            'is_active' => true,
        ]);

        $group = PackageChoiceGroup::create([
            'service_id' => $pkg->id,
            'label' => 'Choose Your Massage',
            'is_required' => true,
        ]);

        $optService = Service::create([
            'category_id' => $this->massageCat->id,
            'name' => 'Ventosa Massage',
            'duration_minutes' => 60,
            'price' => 620.00,
            'is_package' => false,
            'is_active' => true,
        ]);

        $customer = \App\Models\Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone_number' => '09123456789',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $this->staff->id,
            'total_price' => 1449.00,
            'appointment_date' => Carbon::today('Asia/Manila')->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'status' => 'confirmed',
        ]);

        $appointment->services()->attach($pkg->id, [
            'service_name' => $pkg->name,
            'price_at_booking' => 1449.00,
        ]);

        AppointmentPackageSelection::create([
            'appointment_id' => $appointment->id,
            'choice_group_id' => $group->id,
            'selected_service_id' => $optService->id,
            'group_label_snapshot' => 'Choose Your Massage',
            'selected_service_name_snapshot' => 'Ventosa Massage',
        ]);

        // 1. Receptionist active appointments view
        $recActiveRes = $this->actingAs($this->receptionist)->get(route('receptionist.active'));
        $recActiveRes->assertStatus(200);
        $recActiveRes->assertSee('Choose Your Massage');
        $recActiveRes->assertSee('Ventosa Massage');

        // 2. Staff appointments view
        $staffRes = $this->actingAs($this->staff)->get(route('staff.appointments'));
        $staffRes->assertStatus(200);
        $staffRes->assertSee('Choose Your Massage');
        $staffRes->assertSee('Ventosa Massage');
    }

    public function test_landing_page_renders_choice_groups_and_guest_counts_for_packages(): void
    {
        $pkg = Service::create([
            'category_id' => $this->coupleCat->id,
            'name' => 'Couple 3',
            'duration_minutes' => 90,
            'price' => 1799.00,
            'is_package' => true,
            'participants_required' => 2,
            'therapists_required' => 2,
            'is_active' => true,
            'show_on_landing' => true,
        ]);

        $group = PackageChoiceGroup::create([
            'service_id' => $pkg->id,
            'label' => 'Choose Your Massage',
            'is_required' => true,
        ]);

        $s1 = Service::create([
            'category_id' => $this->massageCat->id,
            'name' => 'Alexandria Massage',
            'duration_minutes' => 60,
            'price' => 550.00,
            'is_package' => false,
            'is_active' => true,
        ]);

        $s2 = Service::create([
            'category_id' => $this->massageCat->id,
            'name' => 'Swedish Massage',
            'duration_minutes' => 60,
            'price' => 530.00,
            'is_package' => false,
            'is_active' => true,
        ]);

        PackageChoiceOption::create(['choice_group_id' => $group->id, 'service_id' => $s1->id, 'sort_order' => 1]);
        PackageChoiceOption::create(['choice_group_id' => $group->id, 'service_id' => $s2->id, 'sort_order' => 2]);

        $response = $this->get(route('landing'));
        $response->assertStatus(200);
        $response->assertSee('Couple 3');
        $response->assertSee('Choose Your Massage');
        $response->assertSee('Alexandria Massage OR Swedish Massage');
        $response->assertSee('2 Guests');
        $response->assertSee('2 Therapists');
    }

    public function test_admin_can_sync_package_choices_via_ajax_without_page_reload(): void
    {
        $pkg = Service::create([
            'category_id' => $this->coupleCat->id,
            'name' => 'Couple 1',
            'duration_minutes' => 120,
            'price' => 2299.00,
            'is_package' => true,
            'is_active' => true,
        ]);

        $s1 = Service::create([
            'category_id' => $this->massageCat->id,
            'name' => 'Alexandria Massage',
            'duration_minutes' => 60,
            'price' => 550.00,
            'is_package' => false,
            'is_active' => true,
        ]);

        $s2 = Service::create([
            'category_id' => $this->massageCat->id,
            'name' => 'Swedish Massage',
            'duration_minutes' => 60,
            'price' => 530.00,
            'is_package' => false,
            'is_active' => true,
        ]);

        // 1. Sync 2 choices via AJAX
        $res = $this->actingAs($this->admin)->postJson(route('admin.choice-groups.sync', $pkg), [
            'enabled' => 1,
            'label' => 'Choose Your Massage',
            'is_required' => 1,
            'option_ids' => [$s1->id, $s2->id],
        ]);

        $res->assertStatus(200);
        $res->assertJsonFragment([
            'success' => true,
            'has_choices' => true,
            'formatted_text' => 'Alexandria Massage OR Swedish Massage',
        ]);

        $this->assertDatabaseHas('package_choice_groups', [
            'service_id' => $pkg->id,
            'label' => 'Choose Your Massage',
            'is_required' => 1,
        ]);
        $this->assertEquals(2, $pkg->fresh()->choiceGroups->first()->options()->count());

        // 2. Disable choices
        $resDisable = $this->actingAs($this->admin)->postJson(route('admin.choice-groups.sync', $pkg), [
            'enabled' => 0,
        ]);
        $resDisable->assertStatus(200);
        $resDisable->assertJsonFragment([
            'success' => true,
            'has_choices' => false,
        ]);
        $this->assertDatabaseMissing('package_choice_groups', [
            'service_id' => $pkg->id,
        ]);
    }

    public function test_vip_packages_configuration_and_requirements(): void
    {
        $vipCat = ServiceCategory::create(['name' => 'VIP Packages', 'is_active' => true, 'show_on_landing' => true]);
        $alexandria = Service::create([
            'category_id' => $this->massageCat->id,
            'name' => 'Alexandria Massage',
            'duration_minutes' => 60,
            'price' => 550.00,
            'is_package' => false,
            'is_active' => true,
        ]);
        $bodyScrub = Service::create([
            'category_id' => $this->faceBodyCat->id,
            'name' => 'Body Scrub',
            'duration_minutes' => 60,
            'price' => 650.00,
            'is_package' => false,
            'is_active' => true,
        ]);
        $facial = Service::create([
            'category_id' => $this->faceBodyCat->id,
            'name' => 'Facial Cleansing',
            'duration_minutes' => 60,
            'price' => 450.00,
            'is_package' => false,
            'is_active' => true,
        ]);

        $vipRoom = Room::create([
            'room_category_id' => $this->vipRoomCat->id,
            'name' => 'VIP Room',
            'room_number' => 'VIP1',
            'is_active' => true,
            'status' => 'available',
        ]);
        $vipRoom->roomCategories()->attach($this->vipRoomCat->id);

        $packages = [
            [
                'name' => 'Royal Vip Package',
                'duration' => 150,
                'price' => 3899.00,
                'inclusions' => [$alexandria->id],
            ],
            [
                'name' => 'Deluxe Vip Package',
                'duration' => 120,
                'price' => 3399.00,
                'inclusions' => [$bodyScrub->id, $alexandria->id],
            ],
            [
                'name' => 'Signature Vip Package',
                'duration' => 120,
                'price' => 3199.00,
                'inclusions' => [$facial->id, $alexandria->id],
            ],
            [
                'name' => 'Refreshing Vip Package',
                'duration' => 90,
                'price' => 1999.00,
                'inclusions' => [$alexandria->id],
            ],
        ];

        foreach ($packages as $pkgInfo) {
            $service = Service::create([
                'category_id' => $vipCat->id,
                'name' => $pkgInfo['name'],
                'duration_minutes' => $pkgInfo['duration'],
                'price' => $pkgInfo['price'],
                'is_package' => true,
                'included_services' => $pkgInfo['inclusions'],
                'participants_required' => 2,
                'therapists_required' => 2,
                'requires_room' => true,
                'room_category_id' => $this->vipRoomCat->id,
                'is_active' => true,
                'show_on_landing' => true,
            ]);

            $this->assertEquals(2, $service->participants_required);
            $this->assertEquals(2, $service->therapists_required);
            $this->assertEquals($pkgInfo['duration'], $service->duration_minutes);
            $this->assertEquals($pkgInfo['price'], $service->price);
            $this->assertEquals($this->vipRoomCat->id, $service->room_category_id);
            $this->assertTrue($service->is_package);
        }

        // Verify landing page displays all 4 VIP packages
        $landingRes = $this->get(route('landing'));
        $landingRes->assertStatus(200);
        $landingRes->assertSee('Royal Vip Package');
        $landingRes->assertSee('Deluxe Vip Package');
        $landingRes->assertSee('Signature Vip Package');
        $landingRes->assertSee('Refreshing Vip Package');
        $landingRes->assertSee('3,899');
        $landingRes->assertSee('3,399');
        $landingRes->assertSee('3,199');
        $landingRes->assertSee('1,999');
    }

    public function test_booking_wizard_renders_expandable_service_details(): void
    {
        $service = Service::create([
            'category_id' => $this->massageCat->id,
            'name' => 'Alexa\'s Stone Massage',
            'duration_minutes' => 60,
            'price' => 650.00,
            'description' => "Warm stones and soothing strokes to melt deep tension and restore balance.",
            'is_package' => false,
            'is_active' => true,
            'room_category_id' => $this->standardRoomCat->id,
            'participants_required' => 1,
            'therapists_required' => 1,
        ]);

        $res = $this->get(route('booking.wizard'));
        $res->assertStatus(200);

        // Verify service details modal module and icon auto-observer
        $res->assertSee('openServiceDetails');
        $res->assertSee('closeServiceDetails');
        $res->assertSee('activeDetailService');
        $res->assertSee('initIconObserver');
        $res->assertSee('View full details');
        $res->assertSee('Service Description & Amenities', false);
        $res->assertSee('Standard Massage Room');
        $res->assertSee('Warm stones and soothing strokes to melt deep tension and restore balance.');
    }
}
