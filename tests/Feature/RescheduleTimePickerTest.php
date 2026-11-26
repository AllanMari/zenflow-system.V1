<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BusinessException;
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

/**
 * Phase 3.5 reschedule time-picker tests.
 *
 * The grouping is presentation-only; server-side availability and validation
 * are exercised through the existing endpoints to confirm nothing was weakened.
 */
class RescheduleTimePickerTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    private User $therapist;

    private Room $room;

    private Service $service;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'customer']);

        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach($receptionistRole);

        $this->therapist = User::factory()->create(['is_active' => true]);
        $this->therapist->roles()->attach($staffRole);

        $this->date = Carbon::today('Asia/Manila')->addDay()->toDateString();

        for ($day = 0; $day < 7; $day++) {
            BusinessHour::create([
                'day_of_week' => $day,
                'open_time' => '08:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false,
            ]);
        }

        WorkSchedule::create([
            'user_id' => $this->therapist->id,
            'day_of_week' => Carbon::parse($this->date)->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_day_off' => false,
        ]);

        $category = ServiceCategory::create(['name' => 'Reschedule Picker', 'is_active' => true]);
        $roomCategory = RoomCategory::create(['name' => 'Picker Room', 'code' => 'picker_room']);

        $this->service = Service::create([
            'name' => '60 min massage',
            'category_id' => $category->id,
            'room_category_id' => $roomCategory->id,
            'duration_minutes' => 60,
            'price' => 1000,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $this->room = Room::create([
            'name' => 'Picker Room A',
            'status' => 'available',
            'is_active' => true,
        ]);
        $this->room->roomCategories()->attach($roomCategory->id);
    }

    public function test_reschedule_availability_still_returns_available_times(): void
    {
        $appointment = $this->makeAppointment('11:00', '12:00');

        $response = $this->actingAs($this->receptionist)->getJson(
            route('receptionist.reschedule.availability', $appointment->id) . '?' . http_build_query([
                'date' => $this->date,
            ])
        );

        $response->assertOk();

        $times = collect($response->json('times'))->pluck('time')->all();

        $this->assertContains('09:00', $times);
        $this->assertContains('11:00', $times, 'The appointment must not conflict with itself.');
        $this->assertSame(60, $response->json('duration'));
        $this->assertSame(1, $response->json('therapists_required'));
        $this->assertSame('11:00', $response->json('current_time'));
    }

    public function test_preparation_buffer_still_excludes_following_hour(): void
    {
        $appointment = $this->makeAppointment('11:00', '12:00');
        $this->makeAppointment('13:00', '14:00');

        $response = $this->actingAs($this->receptionist)->getJson(
            route('receptionist.reschedule.availability', $appointment->id) . '?' . http_build_query([
                'date' => $this->date,
            ])
        );

        $response->assertOk();

        $times = collect($response->json('times'))->pluck('time')->all();

        $this->assertNotContains('14:00', $times);
        $this->assertContains('14:05', $times);
    }

    public function test_active_page_renders_compact_grouped_time_picker(): void
    {
        $appointment = $this->makeAppointment('11:00', '12:00');

        $response = $this->actingAs($this->receptionist)->get(route('receptionist.active'));

        $response->assertOk();
        $response->assertSee('Reschedule Customer');
        $response->assertSee('buildRescheduleGroups');
        $response->assertSee('Morning');
        $response->assertSee('Afternoon');
        $response->assertSee('Evening');
        $response->assertSee('max-h-56 overflow-y-auto', false);
        $response->assertSee('Selected', false);
        $response->assertSee('Unavailable', false);
    }

    public function test_empty_availability_is_handled(): void
    {
        BusinessException::create([
            'date' => $this->date,
            'title' => 'Closed for testing',
            'type' => 'holiday',
            'is_closed' => true,
        ]);

        $appointment = $this->makeAppointment('11:00', '12:00');

        $response = $this->actingAs($this->receptionist)->getJson(
            route('receptionist.reschedule.availability', $appointment->id) . '?' . http_build_query([
                'date' => $this->date,
            ])
        );

        $response->assertOk();
        $response->assertJsonCount(0, 'times');
        $this->assertNotEmpty($response->json('message'));

        $page = $this->actingAs($this->receptionist)->get(route('receptionist.active'));
        $page->assertOk();
        $page->assertSee('No valid time with the current therapist team and room on this date.');
    }

    public function test_reschedule_server_validation_is_unchanged(): void
    {
        $appointment = $this->makeAppointment('11:00', '12:00');

        $this->actingAs($this->receptionist)->post(
            route('receptionist.reschedule', $appointment->id),
            [
                'appointment_date' => $this->date,
                'start_time' => '22:00',
            ]
        )->assertSessionHas('error');

        $appointment->refresh();

        $this->assertSame($this->date, $appointment->appointment_date->toDateString());
        $this->assertSame('11:00:00', $appointment->start_time);
        $this->assertSame('confirmed', $appointment->status);
    }

    private function makeAppointment(string $start, string $end): Appointment
    {
        $customer = Customer::create([
            'first_name' => 'Reschedule',
            'last_name' => 'Customer',
            'phone_number' => '0916' . random_int(100000, 999999),
            'customer_type' => 'regular',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $this->therapist->id,
            'room_id' => $this->room->id,
            'appointment_date' => $this->date,
            'start_time' => $start . ':00',
            'end_time' => $end . ':00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);

        $appointment->services()->attach($this->service->id, [
            'price_at_booking' => $this->service->price,
            'service_duration' => $this->service->duration_minutes,
        ]);
        $appointment->syncStaff($this->therapist->id, []);

        return $appointment;
    }
}
