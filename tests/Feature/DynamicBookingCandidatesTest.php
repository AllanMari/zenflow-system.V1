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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicBookingCandidatesTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;
    protected Room $room;
    protected Service $service;
    protected string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'customer']);
        Role::firstOrCreate(['name' => 'receptionist']);

        $this->staff = User::factory()->create(['is_active' => true]);
        $this->staff->roles()->attach($staffRole);
        $this->date = Carbon::tomorrow()->toDateString();

        WorkSchedule::create([
            'user_id' => $this->staff->id,
            'day_of_week' => Carbon::tomorrow()->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_day_off' => false,
        ]);

        for ($i = 0; $i < 7; $i++) {
            BusinessHour::create([
                'day_of_week' => $i,
                'open_time' => '08:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false,
            ]);
        }

        $category = ServiceCategory::create(['name' => 'Dynamic', 'is_active' => true]);
        $roomCategory = RoomCategory::create(['name' => 'Dynamic Room', 'code' => 'dynamic_room']);
        $this->service = Service::create([
            'name' => 'Dynamic Massage', 'category_id' => $category->id,
            'room_category_id' => $roomCategory->id, 'duration_minutes' => 60,
            'price' => 1000, 'requires_room' => true, 'is_active' => true,
        ]);
        $this->room = Room::create(['name' => 'Dynamic Room 1', 'status' => 'available', 'is_active' => true]);
        $this->room->roomCategories()->attach($roomCategory->id);
    }

    private function addAppointment(string $start, string $end): void
    {
        $customer = Customer::create([
            'first_name' => 'Existing', 'last_name' => 'Booking',
            'phone_number' => '0912345' . random_int(1000, 9999),
            'customer_type' => 'regular',
        ]);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $this->staff->id,
            'room_id' => $this->room->id,
            'appointment_date' => $this->date,
            'start_time' => $start . ':00',
            'end_time' => $end . ':00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);
        $appointment->services()->attach($this->service->id, [
            'price_at_booking' => 1000, 'service_duration' => 60,
        ]);
        $appointment->syncStaff($this->staff->id, []);
    }

    private function slots(): array
    {
        $response = $this->getJson(route('booking.slots', [
            'date' => $this->date,
            'duration' => 60,
            'services' => [$this->service->id],
        ]));

        $response->assertOk();

        return collect($response->json('slots', $response->json()))->pluck('time')->all();
    }

    public function test_exact_release_time_is_offered_and_duplicates_are_removed(): void
    {
        $this->addAppointment('13:00', '14:00');

        $times = $this->slots();

        $this->assertContains('14:05', $times);
        $this->assertContains('14:30', $times);
        $this->assertNotContains('14:00', $times);
        $this->assertSame($times, array_values(array_unique($times)));
        $sorted = $times;
        sort($sorted);
        $this->assertSame($sorted, $times);
    }

    public function test_irregular_candidate_with_only_five_minute_gain_is_hidden(): void
    {
        $this->addAppointment('13:20', '14:20');

        $times = $this->slots();

        // 14:25 is only 5 minutes earlier than the valid 14:30 standard slot.
        $this->assertNotContains('14:25', $times);
        $this->assertContains('14:30', $times);
    }
}
