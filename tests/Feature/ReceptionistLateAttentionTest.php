<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceConfirmationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Consolidated four-tab receptionist page: 15-minute lateness and elapsed end
 * time share Past Unresolved. Frozen Asia/Manila clock keeps thresholds exact.
 */
class ReceptionistLateAttentionTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-12-01 10:30:00', 'Asia/Manila'));

        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $this->receptionist = User::factory()->create([
            'first_name' => 'Rita', 'last_name' => 'Reception',
        ]);
        $this->receptionist->roles()->attach($role);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_page_has_exactly_four_tabs(): void
    {
        $content = $this->page();

        $this->assertSame(3, substr_count($content, '@click="activeTab = '));
        $this->assertStringContainsString('openCalendar()', $content);
        $this->assertStringContainsString('Active Appointments', $content);
        $this->assertStringContainsString('Confirmed Bookings', $content);
        $this->assertStringContainsString('Past Unresolved', $content);
        $this->assertStringNotContainsString('15-Minute Late / Needs Attention', $content);
        $this->assertStringNotContainsString("activeTab = 'late'", $content);
    }

    public function test_fourteen_minutes_late_stays_in_active_appointments(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:14:00', 'Asia/Manila'));
        $appointment = $this->makeAppointment('Fourteen', '10:00', '10:30');

        $content = $this->page();

        $this->assertSame('active', $this->sectionOf($content, $appointment->id));
        $this->assertStringNotContainsString('15+ minutes late', $this->card($content, $appointment->id));
    }

    public function test_exactly_fifteen_minutes_late_moves_to_past_unresolved(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:15:00', 'Asia/Manila'));
        $appointment = $this->makeAppointment('Exactly', '10:00', '10:30');

        $content = $this->page();
        $card = $this->card($content, $appointment->id);

        $this->assertSame('unresolved', $this->sectionOf($content, $appointment->id));
        $this->assertStringContainsString('15+ minutes late', $card);
        $this->assertStringContainsString('15 min past scheduled start', $card);
        $this->assertStringNotContainsString('Appointment time passed', $card);
    }

    public function test_more_than_fifteen_minutes_late_remains_unresolved_without_status_change(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:45:00', 'Asia/Manila'));
        $appointment = $this->makeAppointment('Overdue', '10:00', '11:30');

        $content = $this->page();

        $this->assertSame('unresolved', $this->sectionOf($content, $appointment->id));
        $this->assertStringContainsString('15+ minutes late', $this->card($content, $appointment->id));

        $appointment->refresh();
        $this->assertSame('confirmed', $appointment->status);
        $this->assertNull($appointment->no_show_reason);
        $this->assertNull($appointment->cancellation_reason);
    }

    public function test_appointment_past_scheduled_end_is_in_past_unresolved(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:45:00', 'Asia/Manila'));
        $appointment = $this->makeAppointment('Elapsed', '09:00', '09:30');

        $content = $this->page();
        $card = $this->card($content, $appointment->id);

        $this->assertSame('unresolved', $this->sectionOf($content, $appointment->id));
        $this->assertStringContainsString('Appointment time passed', $card);
    }

    public function test_attendance_confirmed_appointment_is_excluded_from_follow_up(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:30:00', 'Asia/Manila'));
        $appointment = $this->makeAppointment('Confirmed Attendee', '10:00', '10:30');

        app(AttendanceConfirmationService::class)->confirmByReceptionist($appointment, $this->receptionist);

        $this->assertSame('active', $this->sectionOf($this->page(), $appointment->id));
        $this->assertStringContainsString('Attendance confirmed', $this->card($this->page(), $appointment->id));
    }

    public function test_completed_cancelled_and_no_show_appointments_are_absent(): void
    {
        foreach (['completed', 'cancelled', 'no_show'] as $status) {
            $appointment = $this->makeAppointment('Terminal ' . $status, '09:00', '09:30', $status);
            $this->assertStringNotContainsString('id="appointment-' . $appointment->id . '"', $this->page());
        }
    }

    public function test_future_confirmed_booking_has_no_attendance_contact_actions(): void
    {
        $appointment = $this->makeAppointment('Future Fiona', '10:00', '10:30', 'confirmed', '2026-12-05');

        $content = $this->page();
        $card = $this->card($content, $appointment->id);

        $this->assertSame('confirmed', $this->sectionOf($content, $appointment->id));
        $this->assertStringContainsString('Awaiting confirmation', $card);
        $this->assertStringNotContainsString('openAttendance(', $card);
        $this->assertStringNotContainsString('No Answer', $card);
        $this->assertStringNotContainsString('Unable to Reach', $card);
    }

    public function test_no_appointment_appears_in_more_than_one_tab(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:30:00', 'Asia/Manila'));

        $appointments = [
            $this->makeAppointment('Soon', '11:00', '11:30'),
            $this->makeAppointment('Late', '10:00', '10:30'),
            $this->makeAppointment('Future', '10:00', '10:30', 'confirmed', '2026-12-05'),
        ];

        $content = $this->page();
        foreach ($appointments as $appointment) {
            $this->assertSame(1, substr_count($content, 'id="appointment-' . $appointment->id . '"'));
        }
    }

    public function test_calendar_and_resolution_actions_remain_functional(): void
    {
        $today = $this->makeAppointment('Calendar Today', '11:00', '11:30');
        $future = $this->makeAppointment('Calendar Future', '10:00', '10:30', 'confirmed', '2026-12-05');
        $past = $this->makeAppointment('Past Paula', '09:00', '09:30');

        $content = $this->page();

        $this->assertStringContainsString('activeSessionsCalendar', $content);
        $this->assertStringContainsString('Calendar Today', $content);
        $this->assertStringContainsString('Calendar Future', $content);
        $this->assertSame('unresolved', $this->sectionOf($content, $past->id));
        $this->assertStringContainsString('openNoShow(', $this->card($content, $past->id));
        $this->assertStringContainsString('openComplete(', $this->card($content, $past->id));
        $this->assertStringNotContainsString('openReschedule(', $this->card($content, $past->id));
    }

    private function page(): string
    {
        return $this->actingAs($this->receptionist)
            ->get(route('receptionist.active'))
            ->assertOk()
            ->getContent();
    }

    private function sectionMarker(string $content, string $tab): int
    {
        $pos = strpos($content, 'x-show="activeTab === \'' . $tab . '\'"');

        return $pos === false ? PHP_INT_MAX : $pos;
    }

    private function sectionOf(string $content, int $appointmentId): string
    {
        $pos = strpos($content, 'id="appointment-' . $appointmentId . '"');
        $active = $this->sectionMarker($content, 'active');
        $confirmed = $this->sectionMarker($content, 'confirmed');
        $unresolved = $this->sectionMarker($content, 'unresolved');

        if ($pos === false) {
            return 'none';
        }
        if ($pos > $unresolved) {
            return 'unresolved';
        }
        if ($pos > $confirmed) {
            return 'confirmed';
        }

        return 'active';
    }

    private function card(string $content, int $appointmentId): string
    {
        preg_match('/<article\s+id="appointment-' . $appointmentId . '"[\s\S]*?<\/article>/', $content, $matches);

        return $matches[0] ?? '';
    }

    private function makeAppointment(
        string $customerName,
        string $start,
        string $end,
        string $status = 'confirmed',
        string $date = '2026-12-01'
    ): Appointment {
        $parts = explode(' ', $customerName);
        $user = User::factory()->create([
            'first_name' => $parts[0], 'last_name' => $parts[1] ?? 'Customer',
        ]);
        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? 'Customer',
            'email' => $user->email,
            'phone_number' => '0918' . random_int(100000, 999999),
        ]);
        $approver = User::factory()->create();

        return Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => $date,
            'start_time' => $start . ':00',
            'end_time' => $end . ':00',
            'status' => $status,
            'total_price' => 1500,
            'confirmed_at' => Carbon::now()->subDay(),
            'confirmed_by' => $approver->id,
        ]);
    }
}
