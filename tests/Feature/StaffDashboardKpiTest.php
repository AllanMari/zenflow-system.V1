<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardKpiTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $otherStaff;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'staff']);

        $this->staff = User::factory()->create(['is_active' => true]);
        $this->staff->roles()->attach($role);

        $this->otherStaff = User::factory()->create(['is_active' => true]);
        $this->otherStaff->roles()->attach($role);

        $this->customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Client',
            'email' => 'client@example.com',
            'phone_number' => '09171234567',
        ]);
    }

    public function test_remaining_sessions_excludes_confirmed_appointment_whose_end_time_has_passed()
    {
        // Fix time to 2:00 PM Manila time
        Carbon::setTestNow(Carbon::parse('2026-10-16 14:00:00', 'Asia/Manila'));

        // Appointment earlier in the day: 9:00 AM - 10:00 AM (already ended)
        Appointment::create([
            'user_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-16',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'confirmed',
            'total_price' => 1000.00,
        ]);

        $response = $this->actingAs($this->staff)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertViewHas('remainingConfirmedCount', 0);
    }

    public function test_remaining_sessions_includes_future_confirmed_appointment()
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 14:00:00', 'Asia/Manila'));

        // Future appointment: 3:00 PM - 4:00 PM
        Appointment::create([
            'user_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-16',
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => 'confirmed',
            'total_price' => 1000.00,
        ]);

        $response = $this->actingAs($this->staff)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertViewHas('remainingConfirmedCount', 1);
    }

    public function test_remaining_sessions_includes_confirmed_appointment_currently_in_progress()
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 14:30:00', 'Asia/Manila'));

        // In progress: 2:00 PM - 3:00 PM
        Appointment::create([
            'user_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-16',
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'confirmed',
            'total_price' => 1000.00,
        ]);

        $response = $this->actingAs($this->staff)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertViewHas('remainingConfirmedCount', 1);
    }

    public function test_remaining_sessions_excludes_pending_cancelled_and_completed_appointments()
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 14:00:00', 'Asia/Manila'));

        // Future pending
        Appointment::create([
            'user_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-16',
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => 'pending',
            'total_price' => 1000.00,
        ]);

        // Future completed (e.g. advance checked-out)
        Appointment::create([
            'user_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-16',
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'status' => 'completed',
            'total_price' => 1000.00,
        ]);

        // Future cancelled
        Appointment::create([
            'user_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-16',
            'start_time' => '17:00:00',
            'end_time' => '18:00:00',
            'status' => 'cancelled',
            'total_price' => 1000.00,
        ]);

        $response = $this->actingAs($this->staff)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertViewHas('remainingConfirmedCount', 0);
    }

    public function test_weekly_completed_list_includes_completed_appointments_within_monday_to_sunday()
    {
        // Reference date: Friday, Oct 16, 2026 (week is Oct 12 Mon to Oct 18 Sun)
        Carbon::setTestNow(Carbon::parse('2026-10-16 12:00:00', 'Asia/Manila'));

        $apptMon = Appointment::create([
            'user_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-12',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 500.00,
        ]);

        $apptSun = Appointment::create([
            'user_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-18',
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'completed',
            'total_price' => 500.00,
        ]);

        $response = $this->actingAs($this->staff)->get(route('staff.appointments', [
            'status' => 'completed',
            'date_from' => '2026-10-12',
            'date_to' => '2026-10-18',
        ]));

        $response->assertOk();
        $appointments = $response->viewData('appointments');
        $this->assertEquals(2, $appointments->total());
        $this->assertTrue($appointments->contains('id', $apptMon->id));
        $this->assertTrue($appointments->contains('id', $apptSun->id));
    }

    public function test_weekly_completed_list_excludes_completed_appointments_outside_that_week()
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 12:00:00', 'Asia/Manila'));

        // Prior week (Sunday, Oct 11)
        $priorWeekAppt = Appointment::create([
            'user_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-11',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 500.00,
        ]);

        // Next week (Monday, Oct 19)
        $nextWeekAppt = Appointment::create([
            'user_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-19',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 500.00,
        ]);

        $response = $this->actingAs($this->staff)->get(route('staff.appointments', [
            'status' => 'completed',
            'date_from' => '2026-10-12',
            'date_to' => '2026-10-18',
        ]));

        $response->assertOk();
        $appointments = $response->viewData('appointments');
        $this->assertEquals(0, $appointments->total());
    }

    public function test_weekly_list_never_includes_another_therapists_appointments()
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 12:00:00', 'Asia/Manila'));

        // Other therapist's completed appointment in the same week
        Appointment::create([
            'user_id' => $this->otherStaff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-15',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 500.00,
        ]);

        $response = $this->actingAs($this->staff)->get(route('staff.appointments', [
            'status' => 'completed',
            'date_from' => '2026-10-12',
            'date_to' => '2026-10-18',
        ]));

        $response->assertOk();
        $appointments = $response->viewData('appointments');
        $this->assertEquals(0, $appointments->total());
    }

    public function test_existing_single_date_and_status_filtering_continues_to_work()
    {
        $targetAppt = Appointment::create([
            'user_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-16',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 500.00,
        ]);

        $otherDateAppt = Appointment::create([
            'user_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-17',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 500.00,
        ]);

        // Single date filter
        $response = $this->actingAs($this->staff)->get(route('staff.appointments', [
            'date' => '2026-10-16',
            'status' => 'confirmed',
        ]));

        $response->assertOk();
        $appointments = $response->viewData('appointments');
        $this->assertEquals(1, $appointments->total());
        $this->assertTrue($appointments->contains('id', $targetAppt->id));
        $this->assertFalse($appointments->contains('id', $otherDateAppt->id));

        // When both single date and date_from/date_to are provided, single date takes precedence
        $responsePrecedence = $this->actingAs($this->staff)->get(route('staff.appointments', [
            'date' => '2026-10-16',
            'date_from' => '2026-10-17',
            'date_to' => '2026-10-17',
            'status' => 'confirmed',
        ]));

        $responsePrecedence->assertOk();
        $precedenceAppointments = $responsePrecedence->viewData('appointments');
        $this->assertEquals(1, $precedenceAppointments->total());
        $this->assertTrue($precedenceAppointments->contains('id', $targetAppt->id));
    }

    public function test_invalid_or_incomplete_date_range_input_handled_safely()
    {
        $appt = Appointment::create([
            'user_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2026-10-16',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 500.00,
        ]);

        // Inverted range (date_from > date_to) should safely ignore range and return default query
        $responseInverted = $this->actingAs($this->staff)->get(route('staff.appointments', [
            'date_from' => '2026-10-20',
            'date_to' => '2026-10-10',
        ]));
        $responseInverted->assertOk();
        $this->assertTrue($responseInverted->viewData('appointments')->contains('id', $appt->id));

        // Incomplete range (missing date_to)
        $responseIncomplete = $this->actingAs($this->staff)->get(route('staff.appointments', [
            'date_from' => '2026-10-16',
        ]));
        $responseIncomplete->assertOk();
        $this->assertTrue($responseIncomplete->viewData('appointments')->contains('id', $appt->id));

        // Malformed date strings
        $responseMalformed = $this->actingAs($this->staff)->get(route('staff.appointments', [
            'date_from' => 'not-a-date',
            'date_to' => 'invalid',
        ]));
        $responseMalformed->assertOk();
        $this->assertTrue($responseMalformed->viewData('appointments')->contains('id', $appt->id));
    }
}
