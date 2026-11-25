<?php
namespace Tests\Feature;
use App\Models\{Appointment, BusinessHour, Customer, Role, Room, RoomCategory, Service, ServiceCategory, User, WorkSchedule};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomAwareBookingTest extends TestCase {
    use RefreshDatabase;
    protected function setUp(): void {
        parent::setUp();
        Role::firstOrCreate(['name' => 'customer']);
        Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'admin']);
        
        $staff = User::factory()->create(['is_active' => true]);
        $staff->roles()->attach(Role::where('name', 'staff')->first());
        
        // Business Hours: Open 8:00 - 20:00 every day
        for ($i = 0; $i < 7; $i++) {
            BusinessHour::create([
                'day_of_week' => $i,
                'open_time' => '08:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false,
            ]);
        }
        
        // Staff Schedule: Open 08:00 - 20:00
        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => Carbon::tomorrow()->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_day_off' => false,
        ]);
    }

    public function test_get_slots_returns_free_rooms() {
        $cat = ServiceCategory::create(['name' => 'M', 'is_active' => true]);
        $roomCat = RoomCategory::create(['name' => 'M Room', 'code' => 'm_room']);
        $srv = Service::create(['name' => 'S', 'category_id' => $cat->id, 'room_category_id' => $roomCat->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room = Room::create(['name' => 'Room A', 'status' => 'available', 'is_active' => true]);
        $room->roomCategories()->attach($roomCat->id);
        
        $res = $this->getJson(route('booking.slots', ['date' => Carbon::tomorrow()->toDateString(), 'duration' => 60, 'services' => [$srv->id]]));
        
        $res->assertStatus(200);
        $data = $res->json();
        
        $slots = [];
        if (is_array($data)) {
            foreach ($data as $date => $daySlots) {
                if (is_array($daySlots)) {
                    foreach ($daySlots as $s) {
                        $slots[] = $s;
                    }
                }
            }
        }
        
        $slot = collect($slots)->first(fn($s) => isset($s['room_available']) && $s['room_available'] === true);
        $this->assertNotNull($slot, 'No slot found with room_available = true. Slots: ' . json_encode($slots));
        $this->assertNotEmpty($slot['free_rooms'], 'free_rooms is empty');
        $this->assertEquals($room->id, $slot['free_rooms'][0]['id']);
    }


    public function test_room_filtering_rules() {
        $cat1 = ServiceCategory::create(['name' => 'M', 'is_active' => true]);
        $cat2 = ServiceCategory::create(['name' => 'F', 'is_active' => true]);
        $rc1 = RoomCategory::create(['name' => 'RC1', 'code' => 'rc1']);
        $rc2 = RoomCategory::create(['name' => 'RC2', 'code' => 'rc2']);
        $s1 = Service::create(['name' => 'S1', 'category_id' => $cat1->id, 'room_category_id' => $rc1->id, 'duration_minutes' => 30, 'price' => 50, 'requires_room' => true, 'is_active' => true]);
        $s2 = Service::create(['name' => 'S2', 'category_id' => $cat2->id, 'room_category_id' => $rc2->id, 'duration_minutes' => 30, 'price' => 50, 'requires_room' => true, 'is_active' => true]);
        $room1 = Room::create(['name' => 'R1', 'status' => 'available', 'is_active' => true]);
        $room1->roomCategories()->attach($rc1->id);
        $room2 = Room::create(['name' => 'R2', 'status' => 'available', 'is_active' => true]);
        $room2->roomCategories()->attach([$rc1->id, $rc2->id]);
        $unclassifiedRoom = Room::create(['name' => 'R_Unclassified', 'status' => 'available', 'is_active' => true]);

        $comp = Room::findCompatibleForServices(collect([$s1]));
        $this->assertTrue($comp->contains('id', $room1->id));
        $this->assertTrue($comp->contains('id', $room2->id));
        $this->assertFalse($comp->contains('id', $unclassifiedRoom->id));

        $comp2 = Room::findCompatibleForServices(collect([$s1, $s2]));
        $this->assertFalse($comp2->contains('id', $room1->id));
        $this->assertTrue($comp2->contains('id', $room2->id));
        $this->assertFalse($comp2->contains('id', $unclassifiedRoom->id));
    }

    public function test_room_availability_and_blocking() {
        $user = User::factory()->create();
        $customer = Customer::create(['user_id' => $user->id, 'first_name' => 'Test', 'last_name' => 'C', 'email' => $user->email, 'phone_number' => '1234567890']);
        $room = Room::create(['name' => 'R', 'status' => 'available', 'is_active' => true]);
        $date = Carbon::tomorrow()->toDateString();
        
        Appointment::create([
            'customer_id' => $customer->id, 
            'total_price' => 100, 
            'appointment_date' => $date, 
            'start_time' => '14:00:00', 
            'end_time' => '15:00:00', 
            'room_id' => $room->id, 
            'status' => 'pending'
        ]);
        
        $this->assertFalse($room->isAvailableFor($date, '14:00:00', '15:00:00'));
    }

    public function test_booking_preference_and_validation() {
        $user = User::factory()->create();
        $role = Role::where('name', 'customer')->first();
        $user->roles()->attach($role);
        $customer = Customer::create(['user_id' => $user->id, 'first_name' => 'Test', 'last_name' => 'C', 'email' => $user->email, 'phone_number' => '1234567890']);
        
        $cat = ServiceCategory::create(['name' => 'G', 'is_active' => true]);
        $rc = RoomCategory::create(['name' => 'G Room', 'code' => 'g_room']);
        $srv = Service::create(['name' => 'S', 'category_id' => $cat->id, 'room_category_id' => $rc->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room = Room::create(['name' => 'R', 'status' => 'available', 'is_active' => true]);
        $room->roomCategories()->attach($rc->id);
        $date = Carbon::tomorrow()->toDateString();

        $res = $this->actingAs($user)->post(route('booking.store'), [
            'services' => [$srv->id], 
            'appointment_date' => $date, 
            'start_time' => '10:00', 
            'room_id' => $room->id,
            'customer_id' => $customer->id,
            'guest_first_name' => 'G', 
            'guest_phone' => '09123456789', 
            'payment_method' => 'cash', 
            'source' => 'public',
        ]);
        
        $res->assertStatus(302);
        $this->assertDatabaseHas('appointments', ['room_id' => $room->id, 'status' => 'pending']);
    }

    public function test_stale_room_rejection() {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'customer')->first());
        $customer = Customer::create(['user_id' => $user->id, 'first_name' => 'T', 'last_name' => 'C', 'email' => $user->email, 'phone_number' => '1234567890']);
        
        $cat = ServiceCategory::create(['name' => 'G', 'is_active' => true]);
        $rc = RoomCategory::create(['name' => 'G Room', 'code' => 'g_room']);
        $srv = Service::create(['name' => 'S', 'category_id' => $cat->id, 'room_category_id' => $rc->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room = Room::create(['name' => 'R', 'status' => 'available', 'is_active' => true]);
        $room->roomCategories()->attach($rc->id);
        $date = Carbon::tomorrow()->toDateString();
        
        // 1. Another appointment takes Room
        Appointment::create(['customer_id' => $customer->id, 'total_price' => 100, 'appointment_date' => $date, 'start_time' => '10:00:00', 'end_time' => '11:00:00', 'room_id' => $room->id, 'status' => 'pending']);
        
        // 2. Customer submits stale Room selection
        $res = $this->actingAs($user)->post(route('booking.store'), [
            'services' => [$srv->id], 
            'appointment_date' => $date, 
            'start_time' => '10:00', 
            'room_id' => $room->id,
            'customer_id' => $customer->id,
            'guest_first_name' => 'G', 
            'guest_phone' => '09123456789', 
            'payment_method' => 'cash', 
            'source' => 'public',
        ]);
        
        // 3. Assert rejection
        $res->assertSessionHas('error');
    }

    public function test_explicit_room_preference_persisted_correctly() {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'customer')->first());
        $customer = Customer::create(['user_id' => $user->id, 'first_name' => 'Test', 'last_name' => 'C', 'email' => $user->email, 'phone_number' => '1234567890']);
        
        $cat = ServiceCategory::create(['name' => 'Category 1', 'is_active' => true]);
        $rc = RoomCategory::create(['name' => 'Cat 1 Room', 'code' => 'cat1_room']);
        $srv = Service::create(['name' => 'Service 1', 'category_id' => $cat->id, 'room_category_id' => $rc->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room2 = Room::create(['name' => 'Room 2', 'status' => 'available', 'is_active' => true]);
        $room2->roomCategories()->attach($rc->id);
        $date = Carbon::tomorrow()->toDateString();

        $res = $this->actingAs($user)->post(route('booking.store'), [
            'services' => [$srv->id], 
            'appointment_date' => $date, 
            'start_time' => '10:00', 
            'room_id' => $room2->id,
            'customer_id' => $customer->id,
            'guest_first_name' => 'G', 
            'guest_phone' => '09123456789', 
            'payment_method' => 'cash', 
            'source' => 'public',
        ]);
        
        $res->assertStatus(302);
        $this->assertDatabaseHas('appointments', [
            'room_id' => $room2->id, 
            'requested_room_id' => $room2->id, 
            'status' => 'pending'
        ]);
    }

    public function test_no_preference_persisted_correctly() {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'customer')->first());
        $customer = Customer::create(['user_id' => $user->id, 'first_name' => 'Test', 'last_name' => 'C', 'email' => $user->email, 'phone_number' => '1234567890']);
        
        $cat = ServiceCategory::create(['name' => 'Category 1', 'is_active' => true]);
        $rc = RoomCategory::create(['name' => 'Cat 1 Room', 'code' => 'cat1_room']);
        $srv = Service::create(['name' => 'Service 1', 'category_id' => $cat->id, 'room_category_id' => $rc->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room1 = Room::create(['name' => 'Room 1', 'status' => 'available', 'is_active' => true]);
        $room1->roomCategories()->attach($rc->id);
        $date = Carbon::tomorrow()->toDateString();

        // room_id not provided / no preference
        $res = $this->actingAs($user)->post(route('booking.store'), [
            'services' => [$srv->id], 
            'appointment_date' => $date, 
            'start_time' => '10:00', 
            'customer_id' => $customer->id,
            'guest_first_name' => 'G', 
            'guest_phone' => '09123456789', 
            'payment_method' => 'cash', 
            'source' => 'public',
        ]);
        
        $res->assertStatus(302);
        $appointment = Appointment::latest('id')->first();
        $this->assertNull($appointment->requested_room_id);
        $this->assertEquals($room1->id, $appointment->room_id);
    }

    public function test_receptionist_reassignment_preserves_requested_room() {
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($receptionistRole);
        
        $cat = ServiceCategory::create(['name' => 'Cat', 'is_active' => true]);
        $rc = RoomCategory::create(['name' => 'Cat Room', 'code' => 'cat_room']);
        $srv = Service::create(['name' => 'Srv', 'category_id' => $cat->id, 'room_category_id' => $rc->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room2 = Room::create(['name' => 'Room 2', 'status' => 'available', 'is_active' => true]);
        $room2->roomCategories()->attach($rc->id);
        $room1 = Room::create(['name' => 'Room 1', 'status' => 'available', 'is_active' => true]);
        $room1->roomCategories()->attach($rc->id);
        $staff = User::factory()->create();
        $staff->roles()->attach(Role::where('name', 'staff')->first());
        $staff->is_active = true;
        $staff->save();

        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => Carbon::tomorrow()->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_day_off' => false,
        ]);

        $customer = Customer::create(['first_name' => 'C', 'last_name' => 'D', 'phone_number' => '09123456789']);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room2->id,
            'requested_room_id' => $room2->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 100,
        ]);
        $appointment->services()->attach($srv->id, ['price_at_booking' => 100]);

        // Receptionist confirms / changes assigned room to room 1
        $res = $this->actingAs($receptionist)->post(route('receptionist.confirm', $appointment->id), [
            'staff_id' => $staff->id,
            'room_id' => $room1->id,
            'payment_method' => 'cash',
            'payment_type' => 'full',
            'amount' => 100,
        ]);

        $res->assertStatus(302);
        $appointment->refresh();

        $this->assertEquals($room2->id, $appointment->requested_room_id);
        $this->assertEquals($room1->id, $appointment->room_id);
    }

    public function test_receptionist_pending_view_displays_room_preference() {
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach($receptionistRole);

        $cat = ServiceCategory::create(['name' => 'Cat', 'is_active' => true]);
        $rc = RoomCategory::create(['name' => 'Cat Room', 'code' => 'cat_room']);
        $srv = Service::create(['name' => 'Srv', 'category_id' => $cat->id, 'room_category_id' => $rc->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room = Room::create(['name' => 'Special Room', 'status' => 'available', 'is_active' => true]);
        $room->roomCategories()->attach($rc->id);
        $customer = Customer::create(['first_name' => 'C', 'last_name' => 'D', 'phone_number' => '09123456789']);

        // 1. Appointment with explicit preference
        $appPref = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'requested_room_id' => $room->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 100,
        ]);
        $appPref->services()->attach($srv->id, ['price_at_booking' => 100]);

        // 2. Appointment with NO preference
        $appNoPref = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => null,
            'requested_room_id' => null,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'pending',
            'total_price' => 100,
        ]);
        $appNoPref->services()->attach($srv->id, ['price_at_booking' => 100]);

        $res = $this->actingAs($receptionist)->get(route('receptionist.pending'));
        $res->assertStatus(200);
        $res->assertSee('Reserved Room:</strong> Special Room', false);
        $res->assertSee('Reserved Room:</strong> No room reserved', false);
        $res->assertSee('Save Room Assignment');
        $res->assertDontSee('Change Reserved Room');
        $res->assertSee('Temporarily Reserved Therapist:');
        $res->assertSee('Booking IP:');
        $res->assertSee('Confirm Booking');
    }

    public function test_receptionist_can_reassign_pending_reserved_room_and_release_old_room()
    {
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach(Role::firstOrCreate(['name' => 'receptionist'])->id);

        $therapist = User::factory()->create();
        $therapist->roles()->attach(Role::firstOrCreate(['name' => 'staff'])->id);

        $cat = RoomCategory::create(['name' => 'Massage', 'code' => 'MASSAGE']);
        $roomA = Room::create(['name' => 'Room A', 'is_active' => true, 'status' => 'available']);
        $roomB = Room::create(['name' => 'Room B', 'is_active' => true, 'status' => 'available']);
        $roomA->roomCategories()->attach($cat->id);
        $roomB->roomCategories()->attach($cat->id);

        $srvCat = ServiceCategory::create(['name' => 'Massage']);
        $srv = Service::create([
            'category_id' => $srvCat->id,
            'room_category_id' => $cat->id,
            'name' => 'Swedish Massage',
            'duration_minutes' => 60,
            'price' => 500,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create(['first_name' => 'Ana', 'last_name' => 'Cruz', 'phone_number' => '09171112222']);
        $date = Carbon::tomorrow()->toDateString();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $roomA->id,
            'requested_room_id' => $roomA->id,
            'appointment_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 500,
        ]);
        $appointment->services()->attach($srv->id, ['price_at_booking' => 500]);
        $appointment->preferredStaff()->attach($therapist->id);

        $res = $this->actingAs($receptionist)
            ->postJson(route('receptionist.reassign-room', $appointment), [
                'room_id' => $roomB->id,
            ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'room' => ['id' => $roomB->id, 'name' => 'Room B'],
            ]);

        $appointment->refresh();
        $this->assertEquals('pending', $appointment->status);
        $this->assertEquals($roomB->id, $appointment->room_id);
        $this->assertNull($appointment->user_id);
        $this->assertEquals([$therapist->id], $appointment->preferredStaff()->pluck('users.id')->all());
        $this->assertTrue($roomA->fresh()->isAvailableFor($date, '10:00:00', '11:00:00'));
        $this->assertFalse($roomB->fresh()->isAvailableFor($date, '10:00:00', '11:00:00'));
    }

    public function test_pending_room_reassignment_rejects_incompatible_inactive_maintenance_occupied_and_buffer_conflicts()
    {
        $receptionist = User::factory()->create();
        $receptionist->roles()->attach(Role::firstOrCreate(['name' => 'receptionist'])->id);

        $catMassage = RoomCategory::create(['name' => 'Massage', 'code' => 'MASSAGE']);
        $catFacial = RoomCategory::create(['name' => 'Facial', 'code' => 'FACIAL']);

        $roomValid = Room::create(['name' => 'Valid Room', 'is_active' => true, 'status' => 'available']);
        $roomIncompatible = Room::create(['name' => 'Facial Only', 'is_active' => true, 'status' => 'available']);
        $roomInactive = Room::create(['name' => 'Inactive Room', 'is_active' => false, 'status' => 'available']);
        $roomMaintenance = Room::create(['name' => 'Maintenance Room', 'is_active' => true, 'status' => 'maintenance']);
        $roomOccupied = Room::create(['name' => 'Occupied Room', 'is_active' => true, 'status' => 'available']);
        $roomBufferConflict = Room::create(['name' => 'Buffer Room', 'is_active' => true, 'status' => 'available']);

        $roomValid->roomCategories()->attach($catMassage->id);
        $roomIncompatible->roomCategories()->attach($catFacial->id);
        $roomInactive->roomCategories()->attach($catMassage->id);
        $roomMaintenance->roomCategories()->attach($catMassage->id);
        $roomOccupied->roomCategories()->attach($catMassage->id);
        $roomBufferConflict->roomCategories()->attach($catMassage->id);

        $srvCat = ServiceCategory::create(['name' => 'Massage']);
        $srv = Service::create([
            'category_id' => $srvCat->id,
            'room_category_id' => $catMassage->id,
            'name' => 'Deep Tissue',
            'duration_minutes' => 60,
            'price' => 600,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create(['first_name' => 'Ben', 'last_name' => 'Lim', 'phone_number' => '09173334444']);
        $date = Carbon::tomorrow()->toDateString();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $roomValid->id,
            'appointment_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 600,
        ]);
        $appointment->services()->attach($srv->id, ['price_at_booking' => 600]);

        // Another appointment occupying $roomOccupied at 10:00-11:00
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $roomOccupied->id,
            'appointment_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 600,
        ]);

        // Another appointment at 11:03-12:03 on $roomBufferConflict (inside 5-min prep buffer of 10:00-11:00 -> 11:05)
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $roomBufferConflict->id,
            'appointment_date' => $date,
            'start_time' => '11:03:00',
            'end_time' => '12:03:00',
            'status' => 'confirmed',
            'total_price' => 600,
        ]);

        foreach ([$roomIncompatible, $roomInactive, $roomMaintenance, $roomOccupied, $roomBufferConflict] as $badRoom) {
            $res = $this->actingAs($receptionist)
                ->postJson(route('receptionist.reassign-room', $appointment), [
                    'room_id' => $badRoom->id,
                ]);

            $res->assertStatus(422)->assertJson(['success' => false]);
            $this->assertEquals($roomValid->id, $appointment->fresh()->room_id);
        }

        // compatible_only=1 endpoint must exclude incompatible, inactive, maintenance, occupied, and buffer-conflict rooms
        $roomsRes = $this->actingAs($receptionist)
            ->getJson(route('receptionist.rooms.available', ['appointment' => $appointment->id, 'compatible_only' => 1]));
        $roomsRes->assertStatus(200);
        $returnedIds = collect($roomsRes->json('rooms'))->pluck('id')->all();
        $this->assertContains($roomValid->id, $returnedIds);
        $this->assertNotContains($roomIncompatible->id, $returnedIds);
        $this->assertNotContains($roomInactive->id, $returnedIds);
        $this->assertNotContains($roomMaintenance->id, $returnedIds);
        $this->assertNotContains($roomOccupied->id, $returnedIds);
        $this->assertNotContains($roomBufferConflict->id, $returnedIds);
    }

    public function test_non_receptionist_cannot_reassign_pending_reserved_room()
    {
        $customerUser = User::factory()->create();
        $customerUser->roles()->attach(Role::firstOrCreate(['name' => 'customer'])->id);

        $cat = RoomCategory::create(['name' => 'Massage', 'code' => 'MASSAGE']);
        $roomA = Room::create(['name' => 'Room A', 'is_active' => true, 'status' => 'available']);
        $roomB = Room::create(['name' => 'Room B', 'is_active' => true, 'status' => 'available']);

        $customer = Customer::create(['user_id' => $customerUser->id, 'first_name' => 'Cathy', 'last_name' => 'Tan', 'phone_number' => '09175556666']);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $roomA->id,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => 'pending',
            'total_price' => 500,
        ]);

        $this->postJson(route('receptionist.reassign-room', $appointment), ['room_id' => $roomB->id])
            ->assertStatus(401);

        $this->actingAs($customerUser)
            ->postJson(route('receptionist.reassign-room', $appointment), ['room_id' => $roomB->id])
            ->assertStatus(403);

        $this->assertEquals($roomA->id, $appointment->fresh()->room_id);
    }
}
