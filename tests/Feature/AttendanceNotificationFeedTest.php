<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AJAX feed and UI integration tests. Browser-level toast behaviour (visual
 * rendering, sessionStorage dedupe) still requires a manual staging check.
 */
class AttendanceNotificationFeedTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-12-01 13:00:00', 'Asia/Manila'));

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $this->receptionist = User::factory()->create(['is_active' => true]);
        $this->receptionist->roles()->attach($receptionistRole);

        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $customerUser = User::factory()->create();
        $customerUser->roles()->attach($customerRole);
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Cora',
            'last_name' => 'Customer',
            'email' => $customerUser->email,
            'phone_number' => '09171234567',
        ]);

        $approver = User::factory()->create();
        $this->appointment = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => '2026-12-01',
            'start_time' => '14:00:00',
            'end_time' => '14:30:00',
            'status' => 'confirmed',
            'total_price' => 1500,
            'confirmed_at' => Carbon::parse('2026-12-01 12:00:00', 'Asia/Manila'),
            'confirmed_by' => $approver->id,
        ]);

        app(AttendanceReminderService::class)->sendDueReminders();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_feed_returns_notifications_followups_and_bell_count(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->getJson(route('api.notifications.attendance-feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $response->assertJsonPath('notifications.0.stage', 60);
        $response->assertJsonPath('notifications.0.appointment_id', $this->appointment->id);
        $response->assertJsonPath('followups.0.appointment_id', $this->appointment->id);
        $response->assertJsonPath('followups.0.attendance_label', 'Awaiting confirmation');
        $response->assertJsonPath('followups.0.customer', 'Cora Customer');
    }

    public function test_unauthorized_roles_cannot_access_attendance_feed(): void
    {
        $role = Role::firstOrCreate(['name' => 'customer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->getJson(route('api.notifications.attendance-feed'))
            ->assertStatus(403);
    }

    public function test_reading_a_notification_does_not_resolve_the_follow_up(): void
    {
        $notification = $this->receptionist->unreadNotifications()->firstOrFail();

        $before = $this->actingAs($this->receptionist)
            ->getJson(route('api.notifications.attendance-feed'))
            ->json('followups');

        $this->actingAs($this->receptionist)
            ->post(route('api.notifications.read', $notification->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $after = $this->actingAs($this->receptionist)
            ->getJson(route('api.notifications.attendance-feed'))
            ->json('followups');

        $this->assertSame($before, $after);
        $this->assertSame(0, $this->receptionist->unreadNotifications()->count());
    }

    public function test_existing_bell_and_count_endpoints_still_work(): void
    {
        $this->actingAs($this->receptionist)
            ->getJson(route('api.notifications'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $this->actingAs($this->receptionist)
            ->getJson(route('api.notifications.count'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    public function test_receptionist_pages_use_floating_toast_without_action_needed_panel(): void
    {
        $content = $this->actingAs($this->receptionist)
            ->get(route('receptionist.active'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('id="attendanceActionPanel"', $content);
        $this->assertStringNotContainsString('Action Needed — Attendance Follow-ups', $content);
        $this->assertStringNotContainsString('renderFollowUps', $content);
        $this->assertStringContainsString('showToast', $content);
        $this->assertStringContainsString('POLL_MS = 10000', $content);
        $this->assertStringContainsString('setInterval', $content);
        $this->assertStringContainsString('visibilitychange', $content);
        $this->assertStringContainsString('zenflow.attendance.seen', $content);
        $this->assertStringContainsString('attendance-feed', $content);
    }
}
