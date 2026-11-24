<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Attendance;
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
use App\Services\AppointmentOccupancyService;
use App\Services\TherapistAssignmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreparationBufferAndAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffA;
    protected User $staffB;
    protected Room $roomA;
    protected Room $roomB;
    protected Service $service60;
    protected Service $service30;
    protected string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'customer']);
        Role::firstOrCreate(['name' => 'receptionist']);

        $this->staffA = User::factory()->create(['is_active' => true]);
        $this->staffB = User::factory()->create(['is_active' => true]);
        $this->staffA->roles()->attach($staffRole);
        $this->staffB->roles()->attach($staffRole);

        for ($i = 0; $i < 7; $i++) {
            BusinessHour::create([
                'day_of_week' => $i,
                'open_time' => '08:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false,
            ]);
        }

        foreach ([$this->staffA, $this->staffB] as $staff) {
            WorkSchedule::create([
                'user_id' => $staff->id,
                'day_of_week' => Carbon::tomorrow()->dayOfWeek,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'is_day_off' => false,
            ]);
        }

        $category = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
        $roomCategory = RoomCategory::create(['name' => 'Prep Room', 'code' => 'prep_room']);

        $this->service60 = Service::create([
            'name' => '60 min', 'category_id' => $category->id, 'room_category_id' => $roomCategory->id,
            'duration_minutes' => 60, 'price' => 1000, 'requires_room' => true, 'is_active' => true,
        ]);
        $this->service30 = Service::create([
            'name' => '30 min', 'category_id' => $category->id, 'room_category_id' => $roomCategory->id,
            'duration_minutes' => 30, 'price' => 500, 'requires_room' => true, 'is_active' => true,
        ]);

        $this->roomA = Room::create(['name' => 'Room A', 'status' => 'available', 'is_active' => true]);
        $this->roomB = Room::create(['name' => 'Room B', 'status' => 'available', 'is_active' => true]);
        $this->roomA->roomCategories()->attach($roomCategory->id);
        $this->roomB->roomCategories()->attach($roomCategory->id);

        $this->date = Carbon::tomorrow()->toDateString();
    }

    private function createAppointment(User $staff, Room $room, string $start, string $end, $services = null, string $status = 'confirmed'): Appointment
    {
        $customer = Customer::create([
            'first_name' => 'Test', 'last_name' => 'Customer',
            'phone_number' => '0912345678' . random_int(10, 99),
            'customer_type' => 'regular',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $staff->id,
            'room_id' => $room->id,
            'appointment_date' => $this->date,
            'start_time' => $start . ':00',
            'end_time' => $end . ':00',
            'status' => $status,
            'total_price' => 1000,
        ]);

        foreach (($services ?: collect([$this->service60])) as $service) {
            $appointment->services()->attach($service->id, [
                'price_at_booking' => $service->price,
                'service_duration' => $service->duration_minutes,
            ]);
        }
        $appointment->syncStaff($staff->id, []);

        return $appointment;
    }

    public function test_single_service_occupies_therapist_and_room_for_treatment_plus_five_minutes(): void
    {
        $appointment = $this->createAppointment($this->staffA, $this->roomA, '13:00', '14:00');

        $occupancy = app(AppointmentOccupancyService::class);

        $this->assertEquals('14:00', $occupancy->storedEnd($appointment)->format('H:i'));
        $this->assertEquals('14:05', $occupancy->resourceEnd($appointment)->format('H:i'));

        $this->assertTrue($occupancy->hasStaffConflict($this->staffA->id, $this->date, '14:00', '15:00'));
        $this->assertTrue($this->roomA->fresh()->isAvailableFor($this->date, '14:05', '15:05'));
        $this->assertFalse($this->roomA->fresh()->isAvailableFor($this->date, '14:00', '15:00'));
    }

    public function test_multi_service_gets_exactly_one_five_minute_buffer(): void
    {
        $appointment = $this->createAppointment($this->staffA, $this->roomA, '11:00', '12:30', collect([$this->service60, $this->service30]));

        $occupancy = app(AppointmentOccupancyService::class);

        $this->assertEquals('12:30', $occupancy->storedEnd($appointment)->format('H:i'));
        $this->assertEquals('12:35', $occupancy->resourceEnd($appointment)->format('H:i'));
    }

    public function test_resources_are_evaluated_independently(): void
    {
        $this->createAppointment($this->staffA, $this->roomA, '13:00', '14:00');

        // Same therapist, different room: therapist still blocked.
        $this->assertFalse(TherapistAssignmentService::checkTherapistAvailability($this->staffA, $this->date, '14:00', '15:00')['available']);
        $this->assertTrue($this->roomB->fresh()->isAvailableFor($this->date, '14:00', '15:00'));

        // Different therapist, same room: room still blocked.
        $this->assertTrue(TherapistAssignmentService::checkTherapistAvailability($this->staffB, $this->date, '14:00', '15:00')['available']);
        $this->assertFalse($this->roomA->fresh()->isAvailableFor($this->date, '14:00', '15:00'));
    }

    public function test_exact_boundary_is_allowed_and_current_appointment_is_excluded(): void
    {
        $appointment = $this->createAppointment($this->staffA, $this->roomA, '13:00', '14:00');

        $this->assertTrue($this->roomA->fresh()->isAvailableFor($this->date, '14:05', '15:05'));
        $this->assertFalse($this->roomA->fresh()->isAvailableFor($this->date, '14:04', '15:04'));

        $this->assertTrue($this->roomA->fresh()->isAvailableFor($this->date, '13:00', '14:00', $appointment->id));
    }

    public function test_assisting_therapist_is_protected_during_preparation(): void
    {
        $appointment = $this->createAppointment($this->staffA, $this->roomA, '13:00', '14:00');
        $appointment->syncStaff($this->staffA->id, [$this->staffB->id]);

        $this->assertTrue(app(AppointmentOccupancyService::class)->hasStaffConflict($this->staffB->id, $this->date, '14:00', '15:00'));
        $this->assertTrue(app(AppointmentOccupancyService::class)->hasStaffConflict($this->staffB->id, $this->date, '14:04', '15:04'));
        $this->assertFalse(TherapistAssignmentService::checkTherapistAvailability($this->staffB, $this->date, '14:04', '15:04')['available']);
    }

    public function test_pending_appointment_reserves_resources(): void
    {
        $this->createAppointment($this->staffA, $this->roomA, '13:00', '14:00', null, 'pending');

        $this->assertFalse($this->roomA->fresh()->isAvailableFor($this->date, '14:00', '15:00'));
        $this->assertFalse(TherapistAssignmentService::checkTherapistAvailability($this->staffA, $this->date, '14:00', '15:00')['available']);
    }

    public function test_approved_leave_and_day_off_are_not_overridden_by_check_in(): void
    {
        ScheduleException::create([
            'user_id' => $this->staffA->id,
            'exception_date' => $this->date,
            'type' => 'sick_leave',
            'reason' => 'Approved leave',
        ]);
        Attendance::create([
            'user_id' => $this->staffA->id,
            'date' => $this->date,
            'status' => 'present',
            'check_in' => '08:00:00',
            'check_out' => null,
        ]);

        $this->assertFalse(TherapistAssignmentService::checkTherapistAvailability($this->staffA, $this->date, '13:00', '14:00')['available']);
    }

    public function test_stored_duration_and_price_are_unchanged_by_buffer(): void
    {
        $appointment = $this->createAppointment($this->staffA, $this->roomA, '13:00', '14:00');

        $this->assertEquals('14:00:00', $appointment->fresh()->end_time);
        $this->assertEquals(60, (int) $appointment->services()->first()->pivot->service_duration);
        $this->assertEquals(1000, (float) $appointment->total_price);
    }
}
