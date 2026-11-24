<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickBookAvailabilityDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $receptionist;
    protected User $staff;
    protected Service $service;
    protected RoomCategory $roomCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach($receptionistRole);
        $this->staff = User::factory()->create(['is_active' => true]);
        $this->staff->roles()->attach($staffRole);

        for ($i = 0; $i < 7; $i++) {
            BusinessHour::create([
                'day_of_week' => $i,
                'open_time' => '08:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false,
            ]);
        }

        WorkSchedule::create([
            'user_id' => $this->staff->id,
            'day_of_week' => Carbon::today()->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'is_day_off' => false,
        ]);

        $category = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
        $this->roomCategory = RoomCategory::create(['name' => 'Diagnostic Room', 'code' => 'diag_room']);
        $this->service = Service::create([
            'name' => 'Diagnostic Service',
            'category_id' => $category->id,
            'room_category_id' => $this->roomCategory->id,
            'duration_minutes' => 60,
            'price' => 1000,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $room = Room::create(['name' => 'Diagnostic Room 1', 'status' => 'available', 'is_active' => true]);
        $room->roomCategories()->attach($this->roomCategory->id);
    }

    private function nextSlots()
    {
        return $this->actingAs($this->receptionist)->getJson(route('api.next-slots', [
            'date' => Carbon::today()->toDateString(),
            'duration' => 60,
            'buffer_minutes' => 5,
            'services' => [$this->service->id],
        ]));
    }

    public function test_scheduled_but_not_checked_in_reports_accurate_diagnostic(): void
    {
        $response = $this->nextSlots();

        $response->assertOk();
        $this->assertEmpty($response->json('slots'));
        $this->assertTrue($response->json('diagnostics.staff_not_checked_in'));
        $this->assertFalse($response->json('diagnostics.no_staff_scheduled'));
        $this->assertFalse($response->json('diagnostics.no_compatible_rooms'));
    }

    public function test_checked_in_staff_can_receive_a_walk_in_slot(): void
    {
        Attendance::create([
            'user_id' => $this->staff->id,
            'date' => Carbon::today()->toDateString(),
            'status' => 'present',
            'check_in' => Carbon::now('Asia/Manila')->subHours(1)->format('H:i:s'),
            'check_out' => null,
        ]);

        $response = $this->nextSlots();

        $response->assertOk();
        $this->assertNotEmpty($response->json('slots'));
    }

    public function test_missing_room_configuration_is_not_reported_as_staff_shortage(): void
    {
        Attendance::create([
            'user_id' => $this->staff->id,
            'date' => Carbon::today()->toDateString(),
            'status' => 'present',
            'check_in' => Carbon::now('Asia/Manila')->subHours(1)->format('H:i:s'),
            'check_out' => null,
        ]);

        $unconfigured = Service::create([
            'name' => 'No Room Category',
            'category_id' => $this->service->category_id,
            'room_category_id' => null,
            'duration_minutes' => 60,
            'price' => 1000,
            'requires_room' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->receptionist)->getJson(route('api.next-slots', [
            'date' => Carbon::today()->toDateString(),
            'duration' => 60,
            'services' => [$unconfigured->id],
        ]));

        $response->assertOk();
        $this->assertTrue($response->json('diagnostics.no_compatible_rooms'));
        $this->assertFalse($response->json('diagnostics.staff_not_checked_in'));
    }

    public function test_therapist_status_endpoint_reports_verified_reasons(): void
    {
        $response = $this->actingAs($this->receptionist)->getJson(route('api.therapist-statuses', [
            'date' => Carbon::today()->toDateString(),
            'duration' => 60,
            'services' => [$this->service->id],
        ]));

        $response->assertOk();
        $statuses = collect($response->json('statuses'));
        $status = $statuses->firstWhere('staff_id', $this->staff->id);

        $this->assertNotNull($status);
        $this->assertEquals('not_checked_in', $status['status']);
        $this->assertEquals('Not Checked In', $status['label']);
    }

    public function test_status_endpoint_excludes_the_appointment_being_reassigned(): void
    {
        $date = Carbon::tomorrow()->toDateString();
        WorkSchedule::create([
            'user_id' => $this->staff->id,
            'day_of_week' => Carbon::tomorrow()->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'is_day_off' => false,
        ]);

        $customer = Customer::create([
            'first_name' => 'Status', 'last_name' => 'Test',
            'phone_number' => '09123456780', 'customer_type' => 'regular',
        ]);
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $this->staff->id,
            'appointment_date' => $date,
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);
        $appointment->services()->attach($this->service->id, [
            'price_at_booking' => 1000, 'service_duration' => 60,
        ]);
        $appointment->syncStaff($this->staff->id, []);

        $response = $this->actingAs($this->receptionist)->getJson(route('api.therapist-statuses', [
            'appointment_id' => $appointment->id,
        ]));

        $response->assertOk();
        $status = collect($response->json('statuses'))->firstWhere('staff_id', $this->staff->id);
        $this->assertEquals('available', $status['status']);
    }

    public function test_approved_leave_status_is_reported(): void
    {
        ScheduleException::create([
            'user_id' => $this->staff->id,
            'exception_date' => Carbon::today()->toDateString(),
            'type' => 'urgent_leave',
            'reason' => 'Family matter',
        ]);

        $response = $this->actingAs($this->receptionist)->getJson(route('api.therapist-statuses', [
            'date' => Carbon::today()->toDateString(),
            'duration' => 60,
            'services' => [$this->service->id],
        ]));

        $response->assertOk();
        $status = collect($response->json('statuses'))->firstWhere('staff_id', $this->staff->id);

        $this->assertEquals('on_leave', $status['status']);
        $this->assertEquals('On Leave', $status['label']);
        $this->assertEquals('Family matter', $status['reason']);
    }
}
