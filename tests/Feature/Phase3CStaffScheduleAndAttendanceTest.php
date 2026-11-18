<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Attendance;
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

class Phase3CStaffScheduleAndAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected Role $staffRole;
    protected ServiceCategory $serviceCat;
    protected RoomCategory $roomCat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffRole = Role::firstOrCreate(['name' => 'staff']);
        $this->serviceCat = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
        $this->roomCat = RoomCategory::create([
            'name' => 'Standard Suite',
            'code' => 'STD-SUITE',
            'is_active' => true,
        ]);
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
        $custRole = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($custRole);

        return Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone_number' => '09123456789',
            'customer_type' => 'regular',
        ]);
    }

    public function test_staff_dashboard_shows_appointments_for_primary_and_assisting_therapists()
    {
        $primary = $this->createTherapist('Primary');
        $assisting = $this->createTherapist('Assisting');
        $unrelated = $this->createTherapist('Unrelated');
        $customer = $this->createCustomer();

        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'name' => 'Couples Treatment',
            'duration_minutes' => 60,
            'price' => 2000,
            'therapists_required' => 2,
            'participants_required' => 2,
            'requires_room' => false,
            'is_active' => true,
        ]);

        $today = Carbon::today('Asia/Manila')->toDateString();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $today,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'confirmed',
            'total_price' => 2000,
            'client_name' => $customer->full_name,
            'client_phone' => $customer->phone_number,
            'user_id' => $primary->id,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 2000]);
        $appointment->syncStaff($primary->id, [$assisting->id]);

        // Primary therapist accesses staff dashboard
        $responsePrimary = $this->actingAs($primary)->get(route('staff.dashboard'));
        $responsePrimary->assertOk();
        $responsePrimary->assertSee('Couples Treatment');
        $responsePrimary->assertSee('Primary');

        // Assisting therapist accesses staff dashboard
        $responseAssisting = $this->actingAs($assisting)->get(route('staff.dashboard'));
        $responseAssisting->assertOk();
        $responseAssisting->assertSee('Couples Treatment');
        $responseAssisting->assertSee('Assisting');

        // Unrelated therapist does NOT see this appointment
        $responseUnrelated = $this->actingAs($unrelated)->get(route('staff.dashboard'));
        $responseUnrelated->assertOk();
        $responseUnrelated->assertDontSee('Couples Treatment');
    }

    public function test_my_appointments_shows_appointments_for_both_primary_and_assisting()
    {
        $primary = $this->createTherapist('Primary');
        $assisting = $this->createTherapist('Assisting');
        $customer = $this->createCustomer();

        $service = Service::create([
            'category_id' => $this->serviceCat->id,
            'name' => 'Couples Massage',
            'duration_minutes' => 60,
            'price' => 2000,
            'therapists_required' => 2,
            'participants_required' => 2,
            'requires_room' => false,
            'is_active' => true,
        ]);

        $date = Carbon::now('Asia/Manila')->addDays(1)->toDateString();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 2000,
            'client_name' => $customer->full_name,
            'client_phone' => $customer->phone_number,
            'user_id' => $primary->id,
        ]);
        $appointment->services()->attach($service->id, ['price_at_booking' => 2000]);
        $appointment->syncStaff($primary->id, [$assisting->id]);

        // Primary therapist checks appointments list
        $resPrimary = $this->actingAs($primary)->get(route('staff.appointments'));
        $resPrimary->assertOk();
        $resPrimary->assertSee('Couples Massage');
        $resPrimary->assertSee('Primary');

        // Assisting therapist checks appointments list
        $resAssisting = $this->actingAs($assisting)->get(route('staff.appointments'));
        $resAssisting->assertOk();
        $resAssisting->assertSee('Couples Massage');
        $resAssisting->assertSee('Assisting');
    }

    public function test_attendance_records_remain_independent_per_staff_member()
    {
        $primary = $this->createTherapist('Primary');
        $assisting = $this->createTherapist('Assisting');
        $today = Carbon::today('Asia/Manila')->toDateString();

        // Both staff members check in independently
        $attPrimary = Attendance::create([
            'user_id' => $primary->id,
            'date' => $today,
            'check_in' => '08:55:00',
            'status' => 'present',
        ]);

        $attAssisting = Attendance::create([
            'user_id' => $assisting->id,
            'date' => $today,
            'check_in' => '09:05:00',
            'status' => 'present',
        ]);

        $this->assertNotEquals($attPrimary->id, $attAssisting->id);
        $this->assertEquals($primary->id, $attPrimary->user_id);
        $this->assertEquals($assisting->id, $attAssisting->user_id);
        $this->assertEquals('08:55:00', $attPrimary->check_in);
        $this->assertEquals('09:05:00', $attAssisting->check_in);

        // Verify distinct count
        $this->assertEquals(2, Attendance::whereDate('date', $today)->count());
    }
}
