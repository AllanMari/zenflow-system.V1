<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BusinessException;
use App\Models\Customer;
use App\Models\Role;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PendingStaffAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;
    private Customer $customer;
    private ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $recRole = Role::firstOrCreate(['name' => 'receptionist']);
        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach($recRole);

        $custRole = Role::firstOrCreate(['name' => 'customer']);
        $custUser = User::factory()->create(['is_active' => true]);
        $custUser->roles()->attach($custRole);

        $this->customer = Customer::create([
            'user_id' => $custUser->id,
            'first_name' => 'Maya',
            'last_name' => 'Cruz',
            'phone_number' => '09171112222',
            'customer_type' => 'regular',
        ]);

        $this->category = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeStaff(int $dayOfWeek, string $start = '09:00:00', string $end = '18:00:00'): User
    {
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staff = User::factory()->create(['is_active' => true]);
        $staff->roles()->attach($staffRole);

        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => $start,
            'end_time' => $end,
            'is_day_off' => false,
        ]);

        return $staff;
    }

    private function makePendingAppointment(
        Carbon $date,
        string $startTime,
        string $endTime,
        int $therapistsRequired = 1
    ): Appointment {
        $service = Service::create([
            'category_id' => $this->category->id,
            'name' => 'Test Massage ' . uniqid(),
            'duration_minutes' => 60,
            'price' => 800.00,
            'requires_room' => false,
            'requires_prepayment' => false,
            'therapists_required' => $therapistsRequired,
            'is_active' => true,
        ]);

        $appt = Appointment::create([
            'customer_id' => $this->customer->id,
            'appointment_date' => $date->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'pending',
            'total_price' => 800.00,
        ]);

        $appt->services()->attach($service->id, [
            'price_at_booking' => 800.00,
            'service_name' => $service->name,
            'service_duration' => 60,
        ]);

        return $appt;
    }

    public function test_appointment_ending_exactly_at_shift_end_is_available(): void
    {
        $future = Carbon::now('Asia/Manila')->addDays(2);
        // Shift 9:00 AM - 8:00 PM; Appointment 7:00 PM - 8:00 PM => Available
        $staff = $this->makeStaff($future->dayOfWeek, '09:00:00', '20:00:00');
        $appt = $this->makePendingAppointment($future, '19:00:00', '20:00:00');

        $res = $this->actingAs($this->receptionist)->get(route('receptionist.pending'));
        $res->assertOk()
            ->assertDontSee('No Staff Available');

        $avail = $res->viewData('staffAvailability')[$appt->id];
        $this->assertTrue($avail[$staff->id]['available']);
        $this->assertSame('Available', $avail[$staff->id]['status_label']);
    }

    public function test_appointment_extending_beyond_shift_end_is_unavailable(): void
    {
        $future = Carbon::now('Asia/Manila')->addDays(2);
        // Shift 9:00 AM - 8:00 PM; Appointment 7:00 PM - 8:05 PM => Outside Hours
        $staff = $this->makeStaff($future->dayOfWeek, '09:00:00', '20:00:00');
        $appt = $this->makePendingAppointment($future, '19:00:00', '20:05:00');

        $res = $this->actingAs($this->receptionist)->get(route('receptionist.pending'));
        $res->assertOk()
            ->assertSee('No Staff Available');

        $avail = $res->viewData('staffAvailability')[$appt->id];
        $this->assertFalse($avail[$staff->id]['available']);
        $this->assertSame('Outside Hours', $avail[$staff->id]['status_label']);
    }

    public function test_five_minute_preparation_buffer_still_blocks_subsequent_appointment(): void
    {
        $future = Carbon::now('Asia/Manila')->addDays(2);
        $staff = $this->makeStaff($future->dayOfWeek, '09:00:00', '20:00:00');

        // Existing confirmed appointment 10:00 - 11:00 (occupies until 11:05 with 5m buffer)
        $confirmed = $this->makePendingAppointment($future, '10:00:00', '11:00:00');
        $confirmed->update(['status' => 'confirmed', 'user_id' => $staff->id]);
        $confirmed->assignedStaff()->attach($staff->id, ['role' => 'primary']);

        // Pending appointment starting at 11:03 falls inside the 5-minute buffer -> Double Booked
        $pending = $this->makePendingAppointment($future, '11:03:00', '12:03:00');

        $res = $this->actingAs($this->receptionist)->get(route('receptionist.pending'));
        $res->assertOk();

        $avail = $res->viewData('staffAvailability')[$pending->id];
        $this->assertFalse($avail[$staff->id]['available']);
        $this->assertSame('Double Booked', $avail[$staff->id]['status_label']);
    }

    public function test_future_appointment_when_current_time_is_outside_operating_hours_uses_requested_schedule(): void
    {
        $nowNight = Carbon::today('Asia/Manila')->setTime(23, 30, 0);
        Carbon::setTestNow($nowNight);

        $future = $nowNight->copy()->addDays(2);
        $staff = $this->makeStaff($future->dayOfWeek, '09:00:00', '18:00:00');
        $appt = $this->makePendingAppointment($future, '10:00:00', '11:00:00');

        $res = $this->actingAs($this->receptionist)->get(route('receptionist.pending'));
        $res->assertOk()
            ->assertDontSee('No Staff Available');

        $avail = $res->viewData('staffAvailability')[$appt->id];
        $this->assertTrue($avail[$staff->id]['available']);
    }

    public function test_current_pending_booking_excludes_its_own_hold_and_blocks_overlapping_pending_booking(): void
    {
        $future = Carbon::now('Asia/Manila')->addDays(2);
        $staff = $this->makeStaff($future->dayOfWeek, '09:00:00', '18:00:00');

        $holdingAppt = $this->makePendingAppointment($future, '10:00:00', '11:00:00');
        $holdingAppt->preferredStaff()->attach($staff->id);

        $competingAppt = $this->makePendingAppointment($future, '10:30:00', '11:30:00');

        $res = $this->actingAs($this->receptionist)->get(route('receptionist.pending'));
        $res->assertOk();

        $availability = $res->viewData('staffAvailability');

        // Holding appointment excludes its own ID -> preferred therapist is Available
        $this->assertTrue($availability[$holdingAppt->id][$staff->id]['available']);

        // Competing appointment is blocked by the preferred-therapist hold
        $this->assertFalse($availability[$competingAppt->id][$staff->id]['available']);
        $this->assertSame('Double Booked', $availability[$competingAppt->id][$staff->id]['status_label']);
    }

    public function test_service_requiring_two_therapists_with_only_one_available_shows_insufficient_staff(): void
    {
        $future = Carbon::now('Asia/Manila')->addDays(2);
        $staff1 = $this->makeStaff($future->dayOfWeek, '09:00:00', '18:00:00');
        $staff2 = $this->makeStaff($future->dayOfWeek, '09:00:00', '18:00:00');

        // Put staff2 on day_off so only 1 therapist is available
        ScheduleException::create([
            'user_id' => $staff2->id,
            'exception_date' => $future->toDateString(),
            'type' => 'day_off',
        ]);

        $appt = $this->makePendingAppointment($future, '10:00:00', '11:00:00', 2);

        $res = $this->actingAs($this->receptionist)->get(route('receptionist.pending'));
        $res->assertOk()
            ->assertSee('Requires 2 Therapists')
            ->assertSee('Insufficient Staff (1/2 available)')
            ->assertSee('Insufficient Staff Available:');

        $avail = $res->viewData('staffAvailability')[$appt->id];
        $this->assertTrue($avail[$staff1->id]['available']);
        $this->assertFalse($avail[$staff2->id]['available']);
    }

    public function test_weekly_day_off_and_missing_schedule_overridden_by_custom_hours_in_both_interfaces(): void
    {
        $future = Carbon::now('Asia/Manila')->addDays(2);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        // Staff A: recurring WorkSchedule is marked is_day_off = true, overridden by custom_hours
        $staffDayOff = User::factory()->create(['is_active' => true]);
        $staffDayOff->roles()->attach($staffRole);
        WorkSchedule::create([
            'user_id' => $staffDayOff->id,
            'day_of_week' => $future->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'is_day_off' => true,
        ]);
        ScheduleException::create([
            'user_id' => $staffDayOff->id,
            'exception_date' => $future->toDateString(),
            'type' => 'custom_hours',
            'start_time' => '10:00:00',
            'end_time' => '16:00:00',
        ]);

        // Staff B: no WorkSchedule row at all, overridden by custom_hours
        $staffMissing = User::factory()->create(['is_active' => true]);
        $staffMissing->roles()->attach($staffRole);
        ScheduleException::create([
            'user_id' => $staffMissing->id,
            'exception_date' => $future->toDateString(),
            'type' => 'custom_hours',
            'start_time' => '10:00:00',
            'end_time' => '16:00:00',
        ]);

        $appt = $this->makePendingAppointment($future, '15:00:00', '16:00:00');
        $serviceId = $appt->services->first()->id;

        // 1. Booking Wizard API
        $wizardRes = $this->getJson(route('api.booking.preferred-therapists', [
            'date' => $future->toDateString(),
            'start_time' => '15:00',
            'services' => [$serviceId],
        ]));
        $wizardRes->assertOk();
        $wizardMap = collect($wizardRes->json('therapists'))->keyBy('id');
        $this->assertSame('available', $wizardMap[$staffDayOff->id]['status']);
        $this->assertSame('available', $wizardMap[$staffMissing->id]['status']);

        // 2. Receptionist Pending Bookings
        $pendingRes = $this->actingAs($this->receptionist)->get(route('receptionist.pending'));
        $pendingRes->assertOk();
        $avail = $pendingRes->viewData('staffAvailability')[$appt->id];
        $this->assertTrue($avail[$staffDayOff->id]['available']);
        $this->assertSame('10:00-16:00', $avail[$staffDayOff->id]['hours']);
        $this->assertTrue($avail[$staffMissing->id]['available']);
        $this->assertSame('10:00-16:00', $avail[$staffMissing->id]['hours']);
    }

    public function test_explicit_date_specific_day_off_still_blocks_both_interfaces(): void
    {
        $future = Carbon::now('Asia/Manila')->addDays(2);
        $staff = $this->makeStaff($future->dayOfWeek, '09:00:00', '18:00:00');

        ScheduleException::create([
            'user_id' => $staff->id,
            'exception_date' => $future->toDateString(),
            'type' => 'day_off',
            'reason' => 'Approved day off',
        ]);

        $appt = $this->makePendingAppointment($future, '10:00:00', '11:00:00');
        $serviceId = $appt->services->first()->id;

        $wizardMap = collect($this->getJson(route('api.booking.preferred-therapists', [
            'date' => $future->toDateString(),
            'start_time' => '10:00',
            'services' => [$serviceId],
        ]))->json('therapists'))->keyBy('id');
        $this->assertSame('day_off', $wizardMap[$staff->id]['status']);

        $pendingRes = $this->actingAs($this->receptionist)->get(route('receptionist.pending'));
        $avail = $pendingRes->viewData('staffAvailability')[$appt->id];
        $this->assertFalse($avail[$staff->id]['available']);
        $this->assertSame('blocked', $avail[$staff->id]['status']);
    }

    public function test_zero_length_schedule_is_unavailable_in_both_interfaces(): void
    {
        $future = Carbon::now('Asia/Manila')->addDays(2);
        $staff = $this->makeStaff($future->dayOfWeek, '00:00:00', '00:00:00');
        $appt = $this->makePendingAppointment($future, '10:00:00', '11:00:00');
        $serviceId = $appt->services->first()->id;

        $wizardMap = collect($this->getJson(route('api.booking.preferred-therapists', [
            'date' => $future->toDateString(),
            'start_time' => '10:00',
            'services' => [$serviceId],
        ]))->json('therapists'))->keyBy('id');
        $this->assertNotSame('available', $wizardMap[$staff->id]['status']);

        $pendingRes = $this->actingAs($this->receptionist)->get(route('receptionist.pending'));
        $avail = $pendingRes->viewData('staffAvailability')[$appt->id];
        $this->assertFalse($avail[$staff->id]['available']);
        $this->assertSame('Outside Hours', $avail[$staff->id]['status_label']);
    }

    public function test_legitimate_overnight_shift_and_exact_shift_end_are_available_in_both_interfaces(): void
    {
        $future = Carbon::now('Asia/Manila')->addDays(2);
        $overnightStaff = $this->makeStaff($future->dayOfWeek, '22:00:00', '06:00:00');
        $dayStaff = $this->makeStaff($future->dayOfWeek, '09:00:00', '20:00:00');

        // Overnight appointment 23:00 - 00:00 (within 22:00-06:00)
        $overnightAppt = $this->makePendingAppointment($future, '23:00:00', '00:00:00');
        $serviceId = $overnightAppt->services->first()->id;

        $wizardOvernight = collect($this->getJson(route('api.booking.preferred-therapists', [
            'date' => $future->toDateString(),
            'start_time' => '23:00',
            'services' => [$serviceId],
        ]))->json('therapists'))->keyBy('id');
        $this->assertSame('available', $wizardOvernight[$overnightStaff->id]['status']);

        // Exact shift-end appointment 19:00 - 20:00 (dayStaff shift ends at 20:00)
        $exactEndAppt = $this->makePendingAppointment($future, '19:00:00', '20:00:00');
        $wizardExact = collect($this->getJson(route('api.booking.preferred-therapists', [
            'date' => $future->toDateString(),
            'start_time' => '19:00',
            'services' => [$serviceId],
        ]))->json('therapists'))->keyBy('id');
        $this->assertSame('available', $wizardExact[$dayStaff->id]['status']);

        $pendingRes = $this->actingAs($this->receptionist)->get(route('receptionist.pending'));
        $pendingAvail = $pendingRes->viewData('staffAvailability');
        $this->assertTrue($pendingAvail[$overnightAppt->id][$overnightStaff->id]['available']);
        $this->assertTrue($pendingAvail[$exactEndAppt->id][$dayStaff->id]['available']);
    }

    public function test_business_closure_marks_staff_unavailable_in_both_interfaces(): void
    {
        $future = Carbon::now('Asia/Manila')->addDays(2);
        $staff = $this->makeStaff($future->dayOfWeek, '09:00:00', '18:00:00');
        BusinessException::create([
            'date' => $future->toDateString(),
            'title' => 'Company Holiday',
            'type' => 'holiday',
            'is_closed' => true,
        ]);

        $appt = $this->makePendingAppointment($future, '10:00:00', '11:00:00');
        $serviceId = $appt->services->first()->id;

        $wizardMap = collect($this->getJson(route('api.booking.preferred-therapists', [
            'date' => $future->toDateString(),
            'start_time' => '10:00',
            'services' => [$serviceId],
        ]))->json('therapists'))->keyBy('id');
        $this->assertSame('not_scheduled', $wizardMap[$staff->id]['status']);

        $pendingRes = $this->actingAs($this->receptionist)->get(route('receptionist.pending'));
        $avail = $pendingRes->viewData('staffAvailability')[$appt->id];
        $this->assertFalse($avail[$staff->id]['available']);
        $this->assertSame('Spa Closed', $avail[$staff->id]['status_label']);
    }
}
