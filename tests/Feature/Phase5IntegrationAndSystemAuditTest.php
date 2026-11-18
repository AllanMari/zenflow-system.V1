<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
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
use Tests\TestCase;

class Phase5IntegrationAndSystemAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $receptionistRole;
    protected Role $staffRole;
    protected Role $customerRole;

    protected User $adminUser;
    protected User $receptionistUser;
    protected User $staffUser;
    protected User $customerUser;
    protected Customer $customerProfile;

    protected RoomCategory $massageCategory;
    protected RoomCategory $facialCategory;
    protected Room $dualRoom;
    protected Room $massageOnlyRoom;
    protected ServiceCategory $serviceCat;
    protected Service $massageService;
    protected Service $facialService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $this->staffRole = Role::firstOrCreate(['name' => 'staff']);
        $this->customerRole = Role::firstOrCreate(['name' => 'customer']);

        // 1. Create Admin
        $this->adminUser = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'is_active' => true,
        ]);
        $this->adminUser->roles()->attach($this->adminRole);

        // 2. Create Receptionist
        $this->receptionistUser = User::factory()->create([
            'first_name' => 'Reception',
            'last_name' => 'User',
            'is_active' => true,
        ]);
        $this->receptionistUser->roles()->attach($this->receptionistRole);

        // 3. Create Staff / Therapist
        $this->staffUser = User::factory()->create([
            'first_name' => 'Staff',
            'last_name' => 'User',
            'is_active' => true,
        ]);
        $this->staffUser->roles()->attach($this->staffRole);

        for ($i = 0; $i <= 6; $i++) {
            WorkSchedule::create([
                'user_id' => $this->staffUser->id,
                'day_of_week' => $i,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'is_day_off' => false,
            ]);
        }

        // 4. Create Customer
        $this->customerUser = User::factory()->create([
            'first_name' => 'Customer',
            'last_name' => 'User',
            'is_active' => true,
        ]);
        $this->customerUser->roles()->attach($this->customerRole);
        $this->customerProfile = Customer::create([
            'user_id' => $this->customerUser->id,
            'first_name' => 'Customer',
            'last_name' => 'User',
            'phone_number' => '09123456789',
            'customer_type' => 'regular',
        ]);

        // Room Categories & Rooms
        $this->massageCategory = RoomCategory::create([
            'name' => 'Massage Suites',
            'code' => 'MASSAGE-SUITE',
            'is_active' => true,
        ]);
        $this->facialCategory = RoomCategory::create([
            'name' => 'Facial Treatment Rooms',
            'code' => 'FACIAL-ROOM',
            'is_active' => true,
        ]);

        $this->dualRoom = Room::create([
            'name' => 'Executive Suite 101',
            'capacity' => 2,
            'is_active' => true,
            'status' => 'available',
        ]);
        $this->dualRoom->roomCategories()->attach([$this->massageCategory->id, $this->facialCategory->id]);

        $this->massageOnlyRoom = Room::create([
            'name' => 'Massage Room 201',
            'capacity' => 1,
            'is_active' => true,
            'status' => 'available',
        ]);
        $this->massageOnlyRoom->roomCategories()->attach([$this->massageCategory->id]);

        $this->serviceCat = ServiceCategory::create(['name' => 'Wellness', 'is_active' => true]);
        $this->massageService = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->massageCategory->id,
            'name' => 'Deep Tissue Massage',
            'duration_minutes' => 60,
            'price' => 1200,
            'requires_room' => true,
            'therapists_required' => 1,
            'participants_required' => 1,
            'is_active' => true,
        ]);

        $this->facialService = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->facialCategory->id,
            'name' => 'Botanical Facial',
            'duration_minutes' => 60,
            'price' => 1500,
            'requires_room' => true,
            'therapists_required' => 1,
            'participants_required' => 1,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Four-Role Authorization Matrix across Dashboards.
     */
    public function test_four_role_authorization_matrix_across_dashboards(): void
    {
        // Unauthenticated guest
        $this->get('/admin-dashboard')->assertRedirect();
        $this->get('/receptionist/dashboard')->assertRedirect();
        $this->get('/staff/dashboard')->assertRedirect();
        $this->get('/customer/dashboard')->assertRedirect();

        // Customer role access
        $this->actingAs($this->customerUser)->get('/customer/dashboard')->assertOk();
        $this->actingAs($this->customerUser)->get('/admin-dashboard')->assertForbidden();
        $this->actingAs($this->customerUser)->get('/receptionist/dashboard')->assertForbidden();
        $this->actingAs($this->customerUser)->get('/staff/dashboard')->assertForbidden();

        // Staff / Therapist role access
        $this->actingAs($this->staffUser)->get('/staff/dashboard')->assertOk();
        $this->actingAs($this->staffUser)->get('/admin-dashboard')->assertForbidden();
        $this->actingAs($this->staffUser)->get('/receptionist/dashboard')->assertForbidden();
        $this->actingAs($this->staffUser)->get('/customer/dashboard')->assertForbidden();

        // Receptionist role access
        $this->actingAs($this->receptionistUser)->get('/receptionist/dashboard')->assertOk();
        $this->actingAs($this->receptionistUser)->get('/admin-dashboard')->assertForbidden();
        $this->actingAs($this->receptionistUser)->get('/staff/dashboard')->assertForbidden();
        $this->actingAs($this->receptionistUser)->get('/customer/dashboard')->assertForbidden();

        // Admin role access
        $this->actingAs($this->adminUser)->get('/admin-dashboard')->assertOk();
        $this->actingAs($this->adminUser)->get('/admin/appointments')->assertOk();
        $this->actingAs($this->adminUser)->get('/admin/services')->assertOk();
        $this->actingAs($this->adminUser)->get('/admin/room-categories')->assertOk();
    }

    /**
     * Test 2: Full End-to-End Booking, Confirmation, Assignment, Completion & Financial Flow.
     */
    public function test_full_booking_confirmation_completion_and_single_financial_transaction_flow(): void
    {
        $coupleService = Service::create([
            'category_id' => $this->serviceCat->id,
            'room_category_id' => $this->massageCategory->id,
            'name' => 'Signature Four-Hands Massage',
            'duration_minutes' => 60,
            'price' => 2400,
            'requires_room' => true,
            'therapists_required' => 2,
            'participants_required' => 1,
            'is_active' => true,
        ]);

        $assistantStaff = User::factory()->create([
            'first_name' => 'Assistant',
            'last_name' => 'Therapist',
            'is_active' => true,
        ]);
        $assistantStaff->roles()->attach($this->staffRole);
        for ($i = 0; $i <= 6; $i++) {
            WorkSchedule::create([
                'user_id' => $assistantStaff->id,
                'day_of_week' => $i,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'is_day_off' => false,
            ]);
        }

        $date = Carbon::tomorrow()->toDateString();

        // Step 1: Customer books appointment with health notes
        $bookingData = [
            'guest_first_name' => 'Customer',
            'guest_last_name' => 'User',
            'guest_email' => 'customer@zenflow.test',
            'guest_phone' => '09123456789',
            'services' => [$coupleService->id],
            'appointment_date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'notes' => 'Customer requests quiet environment.',
            'treatment_notes' => 'Lower back tension; avoid aggressive pressure on cervical area.',
        ];

        $response = $this->actingAs($this->customerUser)->post('/book', $bookingData);
        $response->assertSessionHasNoErrors();

        $appointment = Appointment::latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('pending', $appointment->status);
        $this->assertEquals('Customer requests quiet environment.', $appointment->notes);
        $this->assertEquals('Lower back tension; avoid aggressive pressure on cervical area.', $appointment->treatment_notes);

        \Illuminate\Support\Facades\Cache::flush();

        // Step 2: Receptionist confirms appointment, assigning Room and Primary + Assisting Therapists
        $confirmData = [
            'room_id' => $this->dualRoom->id,
            'staff_id' => $this->staffUser->id,
            'assisting_staff_ids' => [$assistantStaff->id],
            'payment_method' => 'cash',
            'payment_type' => 'cash_on_site',
            'amount' => 0,
            'treatment_notes' => 'Lower back tension; avoid aggressive pressure on cervical area. Confirmed soft pressure requested.',
        ];

        $confirmResponse = $this->actingAs($this->receptionistUser)
            ->post(route('receptionist.confirm', $appointment), $confirmData);
        $confirmResponse->assertSessionHasNoErrors();

        $appointment->refresh();
        $this->assertEquals('confirmed', $appointment->status);
        $this->assertEquals($this->dualRoom->id, $appointment->room_id);
        $this->assertEquals($this->staffUser->id, $appointment->user_id);
        $this->assertTrue($appointment->assignedStaff->contains($assistantStaff->id));
        $this->assertStringContainsString('Confirmed soft pressure requested.', $appointment->treatment_notes);

        // Step 3: Receptionist completes and collects payment
        $completeData = [
            'payment_method' => 'cash',
            'payment_type' => 'full',
            'notes' => 'Service completed smoothly.',
        ];
        $completeResponse = $this->actingAs($this->receptionistUser)
            ->post(route('receptionist.complete', $appointment), $completeData);
        $completeResponse->assertRedirect();

        $appointment->refresh();
        $this->assertEquals('completed', $appointment->status);

        // Verify exactly one payment record is generated
        $payments = Payment::where('appointment_id', $appointment->id)->get();
        $this->assertCount(1, $payments);
        $this->assertEquals($appointment->total_price, $payments->first()->amount);

        // Verify treatment notes remain strictly hidden from serialization
        $json = $appointment->toArray();
        $this->assertArrayNotHasKey('treatment_notes', $json);
    }

    /**
     * Test 3: Concurrent Booking Protection for Therapists and Rooms.
     */
    public function test_concurrent_booking_protection_for_therapists_and_rooms(): void
    {
        $date = Carbon::tomorrow()->toDateString();

        // Create an existing confirmed appointment from 14:00 to 15:00
        $appointment1 = Appointment::create([
            'customer_id' => $this->customerProfile->id,
            'appointment_date' => $date,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'confirmed',
            'total_price' => 1200,
            'client_name' => $this->customerProfile->full_name,
            'client_phone' => $this->customerProfile->phone_number,
            'user_id' => $this->staffUser->id,
            'room_id' => $this->massageOnlyRoom->id,
        ]);
        $appointment1->services()->attach($this->massageService->id, ['price_at_booking' => 1200]);
        $appointment1->syncStaff($this->staffUser->id, []);

        // 1. Therapist availability check for overlapping time window 14:30 - 15:30
        $availability = TherapistAssignmentService::checkTherapistAvailability(
            $this->staffUser,
            $date,
            '14:30',
            '15:30'
        );
        $this->assertFalse($availability['available']);
        $this->assertEquals('conflict', $availability['status']);

        // 2. Room physical availability check for overlapping time window 14:30 - 15:30
        $roomAvailable = $this->massageOnlyRoom->isAvailableFor($date, '14:30', '15:30');
        $this->assertFalse($roomAvailable);

        // 3. Dual room (different room) is available for 14:30 - 15:30
        $dualRoomAvailable = $this->dualRoom->isAvailableFor($date, '14:30', '15:30');
        $this->assertTrue($dualRoomAvailable);

        // 4. Non-overlapping time (15:00 - 16:00) is available for therapist and room
        $availabilityAfter = TherapistAssignmentService::checkTherapistAvailability(
            $this->staffUser,
            $date,
            '15:00',
            '16:00'
        );
        $this->assertTrue($availabilityAfter['available']);
        $this->assertTrue($this->massageOnlyRoom->isAvailableFor($date, '15:00', '16:00'));
    }

    /**
     * Test 4: Multi-Service Room Compatibility requiring multiple categories.
     */
    public function test_multi_service_room_compatibility_requires_all_categories(): void
    {
        $services = collect([$this->massageService, $this->facialService]);

        // DualRoom has both categories; massageOnlyRoom has only MassageCategory
        $compatibleRooms = RoomCompatibilityService::findCompatibleForServices($services);

        $this->assertTrue($compatibleRooms->contains('id', $this->dualRoom->id));
        $this->assertFalse($compatibleRooms->contains('id', $this->massageOnlyRoom->id));
    }
}
