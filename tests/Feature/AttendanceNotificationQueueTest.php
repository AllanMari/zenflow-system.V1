<?php

namespace Tests\Feature;

use App\Http\Controllers\NotificationController;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Server-side evidence for the receptionist popup queue: every simultaneous
 * reminder is generated and retained with the metadata needed for ordering.
 * Browser queue progression is verified separately.
 */
class AttendanceNotificationQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionistA;

    private User $receptionistB;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-12-01 13:00:00', 'Asia/Manila'));

        $role = Role::firstOrCreate(['name' => 'receptionist']);
        $this->receptionistA = User::factory()->create(['is_active' => true, 'first_name' => 'Rita']);
        $this->receptionistA->roles()->attach($role);
        $this->receptionistB = User::factory()->create(['is_active' => true, 'first_name' => 'Rey']);
        $this->receptionistB->roles()->attach($role);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_three_simultaneous_one_hour_reminders_are_all_retained(): void
    {
        foreach (['Anne', 'Ben', 'Cora'] as $name) {
            $this->makeAppointment($name, '14:00');
        }

        $result = app(AttendanceReminderService::class)->sendDueReminders();

        // 3 customers + 3 appointments x 2 receptionists.
        $this->assertSame(9, $result['sent']);
        $this->assertSame(3, $this->receptionistA->notifications()->where('data->type', 'attendance')->count());
        $this->assertSame(3, $this->receptionistB->notifications()->where('data->type', 'attendance')->count());
        $this->assertSame(6, ActivityLog::query()->where('action', 'attendance_reminder_t60_receptionist')->count());
    }

    public function test_feed_provides_stage_and_appointment_time_for_ordering(): void
    {
        $this->makeAppointment('Early', '14:00'); // T-60 at 13:00
        $this->makeAppointment('Late', '13:15');  // T-15 at 13:00

        app(AttendanceReminderService::class)->sendDueReminders();

        $feed = $this->actingAs($this->receptionistA)
            ->getJson(route('api.notifications.attendance-feed'))
            ->assertOk()
            ->json('notifications');

        $this->assertCount(2, $feed);
        $this->assertEqualsCanonicalizing([60, 15], array_column($feed, 'stage'));
        $this->assertContains('14:00', array_column($feed, 'appointment_time'));
        $this->assertContains('13:15', array_column($feed, 'appointment_time'));
        $this->assertContains('2026-12-01', array_column($feed, 'appointment_date'));
    }

    public function test_followups_are_ordered_by_urgency_then_appointment_time(): void
    {
        $slow = $this->makeAppointment('Slow', '14:00'); // T-60
        $urgent = $this->makeAppointment('Urgent', '13:15'); // T-15

        app(AttendanceReminderService::class)->sendDueReminders();

        $followUps = app(AttendanceReminderService::class)->followUps();

        $this->assertSame($urgent->id, $followUps[0]['appointment_id']);
        $this->assertSame(3, $followUps[0]['stage_priority']);
        $this->assertSame('2026-12-01', $followUps[0]['appointment_date']);
        $this->assertSame('Dec 1, 2026', $followUps[0]['date_label']);
        $this->assertSame($slow->id, $followUps[1]['appointment_id']);
    }

    public function test_repeated_feed_requests_do_not_create_notifications(): void
    {
        $this->makeAppointment('Quiet', '14:00');
        app(AttendanceReminderService::class)->sendDueReminders();
        $before = $this->receptionistA->notifications()->count();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->receptionistA)
                ->getJson(route('api.notifications.attendance-feed'))
                ->assertOk();
        }

        $this->assertSame($before, $this->receptionistA->notifications()->count());
    }

    public function test_feed_returns_unread_attendance_notifications_beyond_twenty(): void
    {
        $ids = [];
        for ($i = 0; $i < 25; $i++) {
            NotificationController::sendTo(
                $this->receptionistA,
                "Attendance reminder {$i}",
                "Reminder {$i}",
                'attendance', 'warning',
                '/receptionist/active#appointment-' . $i,
                'Open Appointment',
                ['stage' => 60, 'appointment_id' => $i]
            );
        }
        $ids = $this->receptionistA->notifications()->pluck('id')->map(fn($id) => (string) $id)->all();

        $notifications = $this->actingAs($this->receptionistA)
            ->getJson(route('api.notifications.attendance-feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 25)
            ->json('notifications');

        $this->assertCount(25, $notifications);
        $this->assertEqualsCanonicalizing($ids, array_column($notifications, 'id'));
    }

    public function test_unrelated_unread_notifications_do_not_crowd_out_attendance_reminders(): void
    {
        for ($i = 0; $i < 30; $i++) {
            NotificationController::sendTo($this->receptionistA, "Booking {$i}", 'Other type', 'booking');
        }
        for ($i = 0; $i < 3; $i++) {
            NotificationController::sendTo(
                $this->receptionistA,
                "Attendance {$i}",
                'Attendance type',
                'attendance', 'warning',
                '/receptionist/active#appointment-' . $i,
                'Open Appointment',
                ['stage' => 15, 'appointment_id' => $i]
            );
        }

        $notifications = $this->actingAs($this->receptionistA)
            ->getJson(route('api.notifications.attendance-feed'))
            ->assertOk()
            ->json('notifications');

        $this->assertCount(3, $notifications);
        $this->assertEqualsCanonicalizing([15, 15, 15], array_column($notifications, 'stage'));
    }

    public function test_script_imports_legacy_ids_and_preserves_new_storage_state(): void
    {
        $content = $this->actingAs($this->receptionistA)
            ->get(route('receptionist.active'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Array.isArray(parsed)', $content);
        $this->assertStringContainsString('cleanIds(parsed)', $content);
        $this->assertStringContainsString('shown.concat(legacy)', $content);
        $this->assertStringContainsString('cleanIds(data.shown)', $content);
        $this->assertStringContainsString('cleanIds(data.queued)', $content);
        $this->assertStringContainsString('zenflow.attendance.seen', $content);
        $this->assertStringContainsString('zenflow.attendance.state', $content);
        $this->assertStringContainsString('stagePriority', $content);
        $this->assertStringContainsString('showNext', $content);
    }

    private function makeAppointment(string $name, string $start): Appointment
    {
        $customerUser = User::factory()->create(['first_name' => $name]);
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => $name,
            'last_name' => 'Customer',
            'email' => $customerUser->email,
            'phone_number' => '0917' . random_int(100000, 999999),
        ]);
        $approver = User::factory()->create();

        return Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => '2026-12-01',
            'start_time' => $start . ':00',
            'end_time' => Carbon::parse($start . ':00')->addMinutes(30)->format('H:i:s'),
            'status' => 'confirmed',
            'total_price' => 1000,
            'confirmed_at' => Carbon::parse('2026-12-01 08:00:00', 'Asia/Manila'),
            'confirmed_by' => $approver->id,
        ]);
    }
}
