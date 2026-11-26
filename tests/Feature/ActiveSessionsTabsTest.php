<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceConfirmationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Corrected Phase 3.5: one "Confirmed Bookings" sidebar entry pointing to the
 * existing Active Sessions page, whose four tabs split today / future / past.
 */
class ActiveSessionsTabsTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Manila'));

        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $this->receptionist = User::factory()->create([
            'first_name' => 'Rita',
            'last_name' => 'Reception',
        ]);
        $this->receptionist->roles()->attach($role);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_only_one_sidebar_entry_is_labeled_confirmed_bookings_and_links_to_active(): void
    {
        $content = $this->actingAs($this->receptionist)
            ->get(route('receptionist.active'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($content, 'data-label="Confirmed Bookings"'));
        $this->assertSame(0, substr_count($content, 'data-label="Active Sessions"'));
        $this->assertStringContainsString('href="' . route('receptionist.active') . '"', $content);
        $this->assertFalse(Route::has('receptionist.confirmed'));
        $this->assertStringNotContainsString('receptionist/confirmed', $content);
    }

    public function test_tabs_split_today_future_and_past_appointments(): void
    {
        $today = $this->makeAppointment('Today Tessa', [
            'appointment_date' => Carbon::today('Asia/Manila')->toDateString(),
        ]);
        $future = $this->makeAppointment('Future Fiona', [
            'appointment_date' => Carbon::today('Asia/Manila')->addDays(3)->toDateString(),
        ]);
        $past = $this->makeAppointment('Past Paula', [
            'appointment_date' => Carbon::today('Asia/Manila')->subDay()->toDateString(),
        ]);

        $content = $this->actingAs($this->receptionist)
            ->get(route('receptionist.active'))
            ->assertOk()
            ->getContent();

        $activeStart = strpos($content, 'x-show="activeTab === \'active\'"');
        $confirmedStart = strpos($content, 'x-show="activeTab === \'confirmed\'"');
        $unresolvedStart = strpos($content, 'x-show="activeTab === \'unresolved\'"');

        $this->assertNotFalse($activeStart);
        $this->assertNotFalse($confirmedStart);
        $this->assertNotFalse($unresolvedStart);
        $this->assertLessThan($confirmedStart, $activeStart);
        $this->assertLessThan($unresolvedStart, $confirmedStart);

        $this->assertStringContainsString('Today Tessa', $this->slice($content, $activeStart, $confirmedStart));
        $this->assertStringNotContainsString('Future Fiona', $this->slice($content, $activeStart, $confirmedStart));
        $this->assertStringNotContainsString('Past Paula', $this->slice($content, $activeStart, $confirmedStart));

        $this->assertStringContainsString('Future Fiona', $this->slice($content, $confirmedStart, $unresolvedStart));
        $this->assertStringNotContainsString('Today Tessa', $this->slice($content, $confirmedStart, $unresolvedStart));

        $this->assertStringContainsString('Past Paula', $this->slice($content, $unresolvedStart));
        $this->assertStringContainsString('Appointment time passed', $content);
    }

    public function test_pending_and_terminal_statuses_are_excluded_from_every_tab(): void
    {
        foreach (['pending', 'cancelled', 'completed', 'no_show'] as $status) {
            $this->makeAppointment('Excluded ' . ucfirst($status), [
                'status' => $status,
                'appointment_date' => Carbon::today('Asia/Manila')->addDays(2)->toDateString(),
            ]);
        }

        $content = $this->actingAs($this->receptionist)
            ->get(route('receptionist.active'))
            ->assertOk()
            ->getContent();

        foreach (['pending', 'cancelled', 'completed', 'no_show'] as $status) {
            $this->assertStringNotContainsString('Excluded ' . ucfirst($status), $content);
        }
    }

    public function test_calendar_still_contains_today_and_future_confirmed_appointments(): void
    {
        $this->makeAppointment('Calendar Today', [
            'appointment_date' => Carbon::today('Asia/Manila')->toDateString(),
        ]);
        $this->makeAppointment('Calendar Future', [
            'appointment_date' => Carbon::today('Asia/Manila')->addDays(4)->toDateString(),
        ]);

        $content = $this->actingAs($this->receptionist)
            ->get(route('receptionist.active'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('activeSessionsCalendar', $content);
        $this->assertStringContainsString('Calendar Today', $content);
        $this->assertStringContainsString('Calendar Future', $content);
    }

    public function test_attendance_state_and_receptionist_actions_remain_available(): void
    {
        $future = $this->makeAppointment('Future Attendance', [
            'appointment_date' => Carbon::today('Asia/Manila')->addDays(2)->toDateString(),
        ]);

        app(AttendanceConfirmationService::class)->confirmByReceptionist($future, $this->receptionist);

        $content = $this->actingAs($this->receptionist)
            ->get(route('receptionist.active'))
            ->assertOk()
            ->getContent();

        $confirmedStart = strpos($content, 'x-show="activeTab === \'confirmed\'"');
        $unresolvedStart = strpos($content, 'x-show="activeTab === \'unresolved\'"');
        $confirmedSection = $this->slice($content, $confirmedStart, $unresolvedStart);

        $this->assertStringContainsString('Future Attendance', $confirmedSection);
        $this->assertStringContainsString('Attendance confirmed', $confirmedSection);
        $this->assertStringContainsString('Receptionist (phone)', $confirmedSection);
        $this->assertStringContainsString('Confirm Attendance by Phone', $content);
        $this->assertStringContainsString('No Answer', $content);
        $this->assertStringContainsString('Unable to Reach', $content);
        $this->assertStringContainsString('buildRescheduleGroups', $content);
    }

    private function slice(string $content, int $start, ?int $end = null): string
    {
        return $end === null ? substr($content, $start) : substr($content, $start, $end - $start);
    }

    private function makeAppointment(string $customerName, array $overrides = []): Appointment
    {
        $parts = explode(' ', $customerName);
        $user = User::factory()->create([
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? 'Customer',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? 'Customer',
            'email' => $user->email,
            'phone_number' => '0917' . random_int(100000, 999999),
        ]);

        $approver = User::factory()->create();

        return Appointment::create(array_merge([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today('Asia/Manila')->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1500,
            'confirmed_at' => Carbon::now()->subDay(),
            'confirmed_by' => $approver->id,
        ], $overrides));
    }
}
