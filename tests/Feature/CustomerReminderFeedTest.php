<?php

namespace Tests\Feature;

use App\Http\Controllers\NotificationController;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Customer-side floating reminder feed and UI integration. Browser-level
 * rendering still needs manual verification on Hostinger/staging.
 */
class CustomerReminderFeedTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-12-01 13:00:00', 'Asia/Manila'));

        $role = Role::firstOrCreate(['name' => 'customer']);
        $this->customer = User::factory()->create([
            'first_name' => 'Cora', 'last_name' => 'Customer',
        ]);
        $this->customer->roles()->attach($role);

        NotificationController::sendTo(
            $this->customer,
            'Appointment reminder — 60 minutes',
            'Your appointment on Tue, Dec 1, 2026 at 2:00 PM is in 60 minutes.',
            'attendance', 'info',
            '/customer/bookings#booking-123',
            'Open Appointment',
            ['stage' => 60, 'appointment_id' => 123]
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_customer_feed_returns_reminder_and_bell_count(): void
    {
        $this->actingAs($this->customer)
            ->getJson(route('api.notifications.customer-attendance-feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.stage', 60)
            ->assertJsonPath('notifications.0.appointment_id', 123)
            ->assertJsonPath('notifications.0.action_url', '/customer/bookings#booking-123');
    }

    public function test_receptionist_cannot_use_customer_feed(): void
    {
        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->getJson(route('api.notifications.customer-attendance-feed'))
            ->assertStatus(403);
    }

    public function test_customer_pages_have_floating_toast_polling_without_notification_panel(): void
    {
        foreach ([route('customer.bookings'), route('customer-dashboard')] as $url) {
            $content = $this->actingAs($this->customer)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('zenflow.customer.reminders.seen', $content);
            $this->assertStringContainsString('showToast', $content);
            $this->assertStringContainsString('POLL_MS = 10000', $content);
            $this->assertStringContainsString('customer-attendance-feed', $content);
            $this->assertStringNotContainsString('attendanceActionPanel', $content);
            $this->assertStringNotContainsString('Action Needed', $content);
        }
    }

    public function test_dismissing_toast_does_not_delete_notification_history(): void
    {
        $notification = $this->customer->unreadNotifications()->firstOrFail();

        $this->actingAs($this->customer)
            ->post(route('api.notifications.read', $notification->id))
            ->assertOk();

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
        $this->assertNotNull($this->customer->notifications()->findOrFail($notification->id)->read_at);

        $this->actingAs($this->customer)
            ->getJson(route('api.notifications'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }
}
