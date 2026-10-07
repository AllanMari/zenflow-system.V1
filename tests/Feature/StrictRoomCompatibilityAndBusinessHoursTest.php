<?php

namespace Tests\Feature;

use App\Models\{BusinessHour, Customer, Role, Room, Service, ServiceCategory, User, WorkSchedule};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrictRoomCompatibilityAndBusinessHoursTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_service_different_categories_no_general_room_yields_zero_compatible_rooms_and_unavailable_slots()
    {
        $catA = ServiceCategory::create(['name' => 'Category A', 'is_active' => true]);
        $catB = ServiceCategory::create(['name' => 'Category B', 'is_active' => true]);

        $srvA = Service::create([
            'name' => 'Service A',
            'category_id' => $catA->id,
            'room_category_id' => $catA->id,
            'duration_minutes' => 60,
            'price' => 100,
            'requires_room' => true,
            'is_active' => true
        ]);
        $srvB = Service::create([
            'name' => 'Service B',
            'category_id' => $catB->id,
            'room_category_id' => $catB->id,
            'duration_minutes' => 60,
            'price' => 100,
            'requires_room' => true,
            'is_active' => true
        ]);

        $room1 = Room::create(['name' => 'Room 1 (A)', 'category_id' => $catA->id, 'status' => 'available', 'is_active' => true]);
        $room2 = Room::create(['name' => 'Room 2 (B)', 'category_id' => $catB->id, 'status' => 'available', 'is_active' => true]);

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staff = User::create([
            'username' => 'staff1',
            'first_name' => 'Staff',
            'last_name' => 'One',
            'email' => 'staff1@example.com',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);
        $staff->roles()->attach($staffRole);

        $tomorrow = Carbon::tomorrow('Asia/Manila');
        $tomorrowStr = $tomorrow->toDateString();
        $dow = $tomorrow->dayOfWeek;

        BusinessHour::create([
            'day_of_week' => $dow,
            'open_time' => '09:00:00',
            'close_time' => '18:00:00',
            'is_closed' => false
        ]);

        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $dow,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'is_day_off' => false
        ]);

        $response = $this->getJson("/api/booking/slots?date={$tomorrowStr}&duration=120&services[]={$srvA->id}&services[]={$srvB->id}");
        $response->assertStatus(200);

        $slots = $response->json();
        $slotsArray = isset($slots['slots']) ? $slots['slots'] : $slots;

        $this->assertNotEmpty($slotsArray);

        // Every slot must have room_available = false and 0 compatible rooms
        foreach ($slotsArray as $slot) {
            $this->assertFalse($slot['room_available'], "Slot {$slot['time']} should have room_available = false");
            $this->assertEmpty($slot['compatible_rooms'] ?? $slot['free_rooms'], "Slot {$slot['time']} should have 0 compatible rooms");
        }
        // Now add a General Room
        $generalRoom = Room::create(['name' => 'General Room', 'category_id' => null, 'status' => 'available', 'is_active' => true]);

        $response2 = $this->getJson("/api/booking/slots?date={$tomorrowStr}&duration=120&services[]={$srvA->id}&services[]={$srvB->id}");
        $response2->assertStatus(200);

        $slots2 = $response2->json();
        $slotsArray2 = isset($slots2['slots']) ? $slots2['slots'] : $slots2;

        $availableSlots = collect($slotsArray2)->filter(fn($s) => $s['room_available'] === true);
        $this->assertNotEmpty($availableSlots, "Slots should be available once General Room is added");

        $firstSlot = $availableSlots->first();
        $this->assertCount(1, $firstSlot['compatible_rooms'] ?? $firstSlot['free_rooms']);
        $this->assertEquals($generalRoom->id, ($firstSlot['compatible_rooms'] ?? $firstSlot['free_rooms'])[0]['id']);
    }

    public function test_business_hours_open_on_sunday_allows_booking()
    {
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staff = User::create([
            'username' => 'staffsun',
            'first_name' => 'Staff',
            'last_name' => 'Sun',
            'email' => 'staffsun@example.com',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);
        $staff->roles()->attach($staffRole);

        // Find upcoming Sunday
        $sunday = Carbon::now('Asia/Manila')->next(Carbon::SUNDAY);
        $sundayStr = $sunday->toDateString();

        BusinessHour::create([
            'day_of_week' => 0, // Sunday
            'open_time' => '10:00:00',
            'close_time' => '16:00:00',
            'is_closed' => false
        ]);

        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => 0,
            'start_time' => '10:00:00',
            'end_time' => '16:00:00',
            'is_day_off' => false
        ]);

        $cat = ServiceCategory::create(['name' => 'General', 'is_active' => true]);
        $srv = Service::create([
            'name' => 'Simple Service',
            'category_id' => $cat->id,
            'room_category_id' => $cat->id,
            'duration_minutes' => 60,
            'price' => 50,
            'requires_room' => true,
            'is_active' => true
        ]);

        $room = Room::create(['name' => 'Room Sun', 'category_id' => $cat->id, 'status' => 'available', 'is_active' => true]);

        $response = $this->getJson("/api/booking/slots?date={$sundayStr}&duration=60&services[]={$srv->id}");
        $response->assertStatus(200);

        $slots = $response->json();
        $slotsArray = isset($slots['slots']) ? $slots['slots'] : $slots;

        $availableSlots = collect($slotsArray)->filter(fn($s) => $s['room_available'] === true);
        $this->assertNotEmpty($availableSlots, "Sunday slots must be generated and available when Business Hours has Sunday open");
    }

    public function test_business_hours_closed_on_sunday_blocks_booking()
    {
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staff = User::create([
            'username' => 'staffsun2',
            'first_name' => 'Staff',
            'last_name' => 'Sun',
            'email' => 'staffsun2@example.com',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);
        $staff->roles()->attach($staffRole);

        // Find upcoming Sunday
        $sunday = Carbon::now('Asia/Manila')->next(Carbon::SUNDAY);
        $sundayStr = $sunday->toDateString();

        BusinessHour::create([
            'day_of_week' => 0, // Sunday
            'open_time' => null,
            'close_time' => null,
            'is_closed' => true
        ]);

        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => 0,
            'start_time' => '10:00:00',
            'end_time' => '16:00:00',
            'is_day_off' => false
        ]);

        $cat = ServiceCategory::create(['name' => 'General', 'is_active' => true]);
        $srv = Service::create([
            'name' => 'Simple Service',
            'category_id' => $cat->id,
            'room_category_id' => $cat->id,
            'duration_minutes' => 60,
            'price' => 50,
            'requires_room' => true,
            'is_active' => true
        ]);

        $room = Room::create(['name' => 'Room Sun', 'category_id' => $cat->id, 'status' => 'available', 'is_active' => true]);

        $response = $this->getJson("/api/booking/slots?date={$sundayStr}&duration=60&services[]={$srv->id}");
        $response->assertStatus(200);

        $slots = $response->json();
        $slotsArray = isset($slots['slots']) ? $slots['slots'] : $slots;

        $this->assertEmpty($slotsArray, "Sunday slots should not be generated when Business Hours has Sunday closed");
    }
}
