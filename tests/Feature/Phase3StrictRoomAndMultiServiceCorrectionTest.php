<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\RoomCompatibilityService;
use App\Services\TherapistAssignmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class Phase3StrictRoomAndMultiServiceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $receptionistRole;
    protected Role $staffRole;
    protected Role $customerRole;
    protected ServiceCategory $serviceCat;
    protected RoomCategory $roomCatA;
    protected RoomCategory $roomCatB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $this->staffRole = Role::firstOrCreate(['name' => 'staff']);
        $this->customerRole = Role::firstOrCreate(['name' => 'customer']);

        $this->serviceCat = ServiceCategory::create(['name' => 'Therapy', 'is_active' => true]);
        $this->roomCatA = RoomCategory::create(['name' => 'Dry Massage Suite', 'code' => 'DRY-SUITE', 'is_active' => true]);
        $this->roomCatB = RoomCategory::create(['name' => 'Wet/Hydrotherapy Room', 'code' => 'WET-ROOM', 'is_active' => true]);
    }

    protected function createTherapist(string $name = 'Therapist'): User
    {
        $user = User::factory()->create([
            'first_name' => $name,
            'last_name' => 'Staff',
            'is_active' => true,
        ]);
        $user->roles()->attach($this->staffRole);

        for ($i = 0; $i <= 6; $i++) {
            WorkSchedule::create([
                'user_id' => $user->id,
                'day_of_week' => $i,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'is_day_off' => false,
            ]);
        }

        return $user;
    }

    protected function createCustomer(): Customer
    {
        $user = User::factory()->create();
        $user->roles()->attach($this->customerRole);

        return Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone_number' => '09123456789',
            'customer_type' => 'regular',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Strict Room Compatibility Tests
    |--------------------------------------------------------------------------
    */

    public function test_unclassified_room_without_room_categories_is_never_compatible()
    {
        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->roomCatA->id,
            'name' => 'Deep Tissue Massage',
            'duration_minutes' => 60,
            'price' => 1200,
            'requires_room' => true,
            'is_active' => true,
        ]);

        // Room with no assigned room categories (unclassified)
        $unclassifiedRoom = Room::create([
            'name' => 'Unclassified Room 99',
            'status' => 'available',
            'is_active' => true,
        ]);

        // Room with proper room category assigned
        $compatibleRoom = Room::create([
            'name' => 'Dry Suite 1',
            'status' => 'available',
            'is_active' => true,
        ]);
        $compatibleRoom->roomCategories()->attach($this->roomCatA->id);

        $compatible = RoomCompatibilityService::findCompatibleForServices(collect([$service]));

        $this->assertFalse($compatible->contains('id', $unclassifiedRoom->id), 'Unclassified rooms must never be treated as compatible');
        $this->assertTrue($compatible->contains('id', $compatibleRoom->id));
    }

    public function test_legacy_category_id_on_rooms_is_not_treated_as_room_category()
    {
        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->roomCatA->id,
            'name' => 'Shiatsu Massage',
            'duration_minutes' => 60,
            'price' => 1000,
            'requires_room' => true,
            'is_active' => true,
        ]);

        // Room with legacy category_id set matching the service category, but NO roomCategories pivot attached
        $legacyRoom = Room::create([
            'name' => 'Legacy Room 5',
            'category_id' => $this->serviceCat->id,
            'status' => 'available',
            'is_active' => true,
        ]);

        $compatible = RoomCompatibilityService::findCompatibleForServices(collect([$service]));

        $this->assertFalse($compatible->contains('id', $legacyRoom->id), 'Legacy category_id on rooms must not substitute for roomCategories');
    }

    public function test_service_requiring_room_with_null_room_category_yields_zero_compatible_rooms()
    {
        // Service requires room, but room_category_id was left null (unresolved admin configuration)
        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => null,
            'name' => 'Unconfigured Room Requirement Service',
            'duration_minutes' => 45,
            'price' => 800,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $room = Room::create([
            'name' => 'Dry Suite 1',
            'status' => 'available',
            'is_active' => true,
        ]);
        $room->roomCategories()->attach($this->roomCatA->id);

        $unclassifiedRoom = Room::create([
            'name' => 'Unclassified Room',
            'status' => 'available',
            'is_active' => true,
        ]);

        $compatible = RoomCompatibilityService::findCompatibleForServices(collect([$service]));

        // Strict: No general-room policy is invented; zero rooms are compatible until configured
        $this->assertTrue($compatible->isEmpty(), 'Services with requires_room=true and null room_category_id must not match any room');
    }

    public function test_room_under_maintenance_or_inactive_is_excluded()
    {
        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->roomCatA->id,
            'name' => 'Swedish Massage',
            'duration_minutes' => 60,
            'price' => 1000,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $maintenanceRoom = Room::create([
            'name' => 'Dry Suite Maintenance',
            'status' => 'maintenance',
            'is_active' => true,
        ]);
        $maintenanceRoom->roomCategories()->attach($this->roomCatA->id);

        $inactiveRoom = Room::create([
            'name' => 'Dry Suite Inactive',
            'status' => 'available',
            'is_active' => false,
        ]);
        $inactiveRoom->roomCategories()->attach($this->roomCatA->id);

        $activeRoom = Room::create([
            'name' => 'Dry Suite Active',
            'status' => 'available',
            'is_active' => true,
        ]);
        $activeRoom->roomCategories()->attach($this->roomCatA->id);

        $compatible = RoomCompatibilityService::findCompatibleForServices(collect([$service]));

        $this->assertFalse($compatible->contains('id', $maintenanceRoom->id));
        $this->assertFalse($compatible->contains('id', $inactiveRoom->id));
        $this->assertTrue($compatible->contains('id', $activeRoom->id));
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Multiple-Service Therapist Requirements Tests
    |--------------------------------------------------------------------------
    */

    public function test_package_therapists_required_is_authoritative_over_included_services()
    {
        $customer = $this->createCustomer();

        // Standalone service included in package requiring 2 therapists
        $incService = Service::create([
            'category_id' => $this->serviceCat->id,
            'name' => 'Inclusion Service',
            'duration_minutes' => 60,
            'price' => 1500,
            'therapists_required' => 2,
            'participants_required' => 2,
            'requires_room' => false,
            'is_package' => false,
            'is_active' => true,
        ]);

        // Package itself requires 1 therapist
        $package = Service::create([
            'category_id' => $this->serviceCat->id,
            'name' => 'Solo Pamper Package',
            'duration_minutes' => 120,
            'price' => 2500,
            'therapists_required' => 1,
            'participants_required' => 1,
            'requires_room' => false,
            'is_package' => true,
            'included_services' => [$incService->id],
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'pending',
            'total_price' => 2500,
        ]);
        $appointment->services()->attach($package->id, ['price_at_booking' => 2500]);

        // Resolved requirement must be 1 (package's own requirement), not 2 from included services
        $resolved = TherapistAssignmentService::resolveRequiredCount($appointment);
        $this->assertEquals(1, $resolved);
        $this->assertEquals(1, $appointment->therapists_required);
    }

    public function test_multiple_standalone_services_resolve_to_peak_concurrent_requirement()
    {
        $customer = $this->createCustomer();

        $service1 = Service::create([
            'category_id' => $this->serviceCat->id,
            'name' => 'Body Scrub (1 therapist)',
            'duration_minutes' => 45,
            'price' => 800,
            'therapists_required' => 1,
            'participants_required' => 1,
            'requires_room' => false,
            'is_active' => true,
        ]);

        $service2 = Service::create([
            'category_id' => $this->serviceCat->id,
            'name' => 'Four-Hands Massage (2 therapists)',
            'duration_minutes' => 60,
            'price' => 2000,
            'therapists_required' => 2,
            'participants_required' => 1,
            'requires_room' => false,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:45:00',
            'status' => 'pending',
            'total_price' => 2800,
        ]);
        $appointment->services()->attach([
            $service1->id => ['price_at_booking' => 800],
            $service2->id => ['price_at_booking' => 2000],
        ]);

        // Peak concurrent staffing resolves to max(1, 2) = 2 therapists
        $resolved = TherapistAssignmentService::resolveRequiredCount($appointment);
        $this->assertEquals(2, $resolved);
        $this->assertEquals(2, $appointment->therapists_required);
    }

    public function test_therapist_assignment_enforces_resolved_requirement_strictly()
    {
        $customer = $this->createCustomer();
        $primary = $this->createTherapist('Primary');
        $assisting = $this->createTherapist('Assisting');

        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->roomCatA->id,
            'name' => 'Couples Scrub',
            'duration_minutes' => 60,
            'price' => 1800,
            'therapists_required' => 2,
            'participants_required' => 2,
            'requires_room' => false,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'pending',
            'total_price' => 1800,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 1800]);

        // 1. Providing only 1 therapist throws InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Appointment requires exactly 2 therapist(s), but 1 were provided.');
        TherapistAssignmentService::assignStaff($appointment, $primary->id, []);
    }
}
