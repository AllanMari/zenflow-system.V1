<?php
namespace Tests\Feature;
use App\Models\{Appointment, BusinessHour, Customer, Role, Room, Service, ServiceCategory, User, WorkSchedule};
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
        $srv = Service::create(['name' => 'S', 'category_id' => $cat->id, 'room_category_id' => $cat->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room = Room::create(['name' => 'Room A', 'category_id' => $cat->id, 'status' => 'available', 'is_active' => true]);
        
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
        $s1 = Service::create(['name' => 'S1', 'category_id' => $cat1->id, 'room_category_id' => $cat1->id, 'duration_minutes' => 30, 'price' => 50, 'requires_room' => true, 'is_active' => true]);
        $s2 = Service::create(['name' => 'S2', 'category_id' => $cat2->id, 'room_category_id' => $cat2->id, 'duration_minutes' => 30, 'price' => 50, 'requires_room' => true, 'is_active' => true]);
        $room1 = Room::create(['name' => 'R1', 'category_id' => $cat1->id, 'status' => 'available', 'is_active' => true]);
        $room2 = Room::create(['name' => 'R2', 'category_id' => null, 'status' => 'available', 'is_active' => true]);

        $comp = Room::findCompatibleForServices(collect([$s1]));
        $this->assertTrue($comp->contains('id', $room1->id));
        $this->assertTrue($comp->contains('id', $room2->id));

        $comp2 = Room::findCompatibleForServices(collect([$s1, $s2]));
        $this->assertFalse($comp2->contains('id', $room1->id));
        $this->assertTrue($comp2->contains('id', $room2->id));
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
        $srv = Service::create(['name' => 'S', 'category_id' => $cat->id, 'room_category_id' => $cat->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room = Room::create(['name' => 'R', 'category_id' => $cat->id, 'status' => 'available', 'is_active' => true]);
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
        $srv = Service::create(['name' => 'S', 'category_id' => $cat->id, 'room_category_id' => $cat->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room = Room::create(['name' => 'R', 'category_id' => $cat->id, 'status' => 'available', 'is_active' => true]);
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
        $srv = Service::create(['name' => 'Service 1', 'category_id' => $cat->id, 'room_category_id' => $cat->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room2 = Room::create(['name' => 'Room 2', 'category_id' => $cat->id, 'status' => 'available', 'is_active' => true]);
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
        $srv = Service::create(['name' => 'Service 1', 'category_id' => $cat->id, 'room_category_id' => $cat->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room1 = Room::create(['name' => 'Room 1', 'category_id' => $cat->id, 'status' => 'available', 'is_active' => true]);
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
        $srv = Service::create(['name' => 'Srv', 'category_id' => $cat->id, 'room_category_id' => $cat->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room2 = Room::create(['name' => 'Room 2', 'category_id' => $cat->id, 'status' => 'available', 'is_active' => true]);
        $room1 = Room::create(['name' => 'Room 1', 'category_id' => $cat->id, 'status' => 'available', 'is_active' => true]);
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
        $srv = Service::create(['name' => 'Srv', 'category_id' => $cat->id, 'room_category_id' => $cat->id, 'duration_minutes' => 60, 'price' => 100, 'requires_room' => true, 'is_active' => true]);
        $room = Room::create(['name' => 'Special Room', 'category_id' => $cat->id, 'status' => 'available', 'is_active' => true]);
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
            'room_id' => $room->id,
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
        $res->assertSee('Requested Room:</strong> Special Room', false);
        $res->assertSee('Room Preference:</strong> No preference', false);
    }
}
