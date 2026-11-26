<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\AttendanceConfirmationService;
use App\Services\AttendanceReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Scheduled attendance reminder tests. Frozen clock in Asia/Manila keeps the
 * T-60/T-30/T-15 assertions deterministic. SQLite does not prove MySQL locking.
 */
class AttendanceReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    private User $customerUser;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-12-01 13:00:00', 'Asia/Manila'));

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $this->receptionist = User::factory()->create([
            'first_name' => 'Rita', 'last_name' => 'Reception', 'is_active' => true,
        ]);
        $this->receptionist->roles()->attach($receptionistRole);

        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $this->customerUser = User::factory()->create([
            'first_name' => 'Cora', 'last_name' => 'Customer',
        ]);
        $this->customerUser->roles()->attach($customerRole);

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'first_name' => 'Cora',
            'last_name' => 'Customer',
            'email' => $this->customerUser->email,
            'phone_number' => '09171234567',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_t60_reminder_goes_to_customer_and_receptionist(): void
    {
        $appointment = $this->makeAppointment('14:00', '12:00');

        $result = app(AttendanceReminderService::class)->sendDueReminders();

        $this->assertSame(2, $result['sent']);
        $this->assertNotification($this->customerUser, 60, $appointment);
        $this->assertNotification($this->receptionist, 60, $appointment);
        $this->assertDatabaseMissing('activity_logs', [
            'entity_id' => $appointment->id,
            'action' => 'attendance_reminder_t30_customer',
        ]);
    }

    public function test_t30_reminder_is_sent_without_backfilling_t60(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 13:30:00', 'Asia/Manila'));
        $appointment = $this->makeAppointment('14:00', '12:00');

        app(AttendanceReminderService::class)->sendDueReminders();

        $this->assertNotification($this->customerUser, 30, $appointment);
        $this->assertNotification($this->receptionist, 30, $appointment);
        $this->assertDatabaseMissing('activity_logs', [
            'entity_id' => $appointment->id,
            'action' => 'attendance_reminder_t60_customer',
        ]);
    }

    public function test_t15_reminder_is_sent_without_backfilling_earlier_stages(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 13:45:00', 'Asia/Manila'));
        $appointment = $this->makeAppointment('14:00', '12:00');

        app(AttendanceReminderService::class)->sendDueReminders();

        $this->assertNotification($this->customerUser, 15, $appointment);
        $this->assertNotification($this->receptionist, 15, $appointment);
        $this->assertSame(0, ActivityLog::query()
            ->where('entity_id', $appointment->id)
            ->whereIn('action', ['attendance_reminder_t60_customer', 'attendance_reminder_t30_customer'])
            ->count());
    }

    public function test_guest_booking_notifies_only_receptionist_with_phone(): void
    {
        $guest = Customer::create([
            'user_id' => null,
            'first_name' => 'Guest',
            'last_name' => 'Visitor',
            'email' => null,
            'phone_number' => '09170000000',
        ]);

        $appointment = $this->makeAppointment('14:00', '12:00', ['customer_id' => $guest->id]);

        app(AttendanceReminderService::class)->sendDueReminders();

        $this->assertSame(0, ActivityLog::query()
            ->where('entity_id', $appointment->id)
            ->where('action', 'attendance_reminder_t60_customer')
            ->count());

        $notification = $this->receptionist->notifications()
            ->where('data->type', 'attendance')
            ->where('data->stage', 60)
            ->firstOrFail();

        $this->assertStringContainsString('09170000000', $notification->data['message']);
    }

    public function test_approval_close_to_appointment_sends_no_missed_reminders(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 13:55:00', 'Asia/Manila'));
        $appointment = $this->makeAppointment('14:00', '13:50');

        $result = app(AttendanceReminderService::class)->sendDueReminders();

        $this->assertSame(0, $result['sent']);
        $this->assertSame(0, ActivityLog::query()->where('entity_id', $appointment->id)->count());
        $this->assertSame(0, $this->receptionist->notifications()->where('data->type', 'attendance')->count());
    }

    public function test_repeated_scheduler_runs_do_not_duplicate_notifications(): void
    {
        $appointment = $this->makeAppointment('14:00', '12:00');

        $first = app(AttendanceReminderService::class)->sendDueReminders();
        $second = app(AttendanceReminderService::class)->sendDueReminders();

        $this->assertSame(2, $first['sent']);
        $this->assertSame(0, $second['sent']);
        $this->assertSame(1, ActivityLog::query()
            ->where('entity_id', $appointment->id)
            ->where('action', 'attendance_reminder_t60_customer')
            ->count());
        $this->assertSame(1, $this->customerUser->notifications()->where('data->type', 'attendance')->count());
    }

    public function test_attendance_confirmation_suppresses_future_reminders(): void
    {
        $appointment = $this->makeAppointment('14:00', '12:00');

        app(AttendanceConfirmationService::class)->confirmByCustomer($appointment, $this->customerUser);

        Carbon::setTestNow(Carbon::parse('2026-12-01 13:30:00', 'Asia/Manila'));
        app(AttendanceReminderService::class)->sendDueReminders();

        $this->assertSame(0, ActivityLog::query()
            ->where('entity_id', $appointment->id)
            ->where('action', 'like', 'attendance_reminder_%')
            ->count());
    }

    public function test_non_eligible_statuses_and_started_appointments_are_excluded(): void
    {
        foreach (['pending', 'cancelled', 'completed', 'no_show'] as $status) {
            $appointment = $this->makeAppointment('14:00', '12:00', ['status' => $status]);
            app(AttendanceReminderService::class)->sendDueReminders();
            $this->assertSame(0, ActivityLog::query()->where('entity_id', $appointment->id)->count());
        }

        Carbon::setTestNow(Carbon::parse('2026-12-01 14:05:00', 'Asia/Manila'));
        $started = $this->makeAppointment('14:00', '12:00');
        app(AttendanceReminderService::class)->sendDueReminders();
        $this->assertSame(0, ActivityLog::query()->where('entity_id', $started->id)->count());
    }

    public function test_midnight_crossing_uses_asia_manila(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-02 00:05:00', 'Asia/Manila'));
        $appointment = $this->makeAppointment('00:30', '2026-12-01 20:00:00', [
            'appointment_date' => '2026-12-02',
        ]);

        app(AttendanceReminderService::class)->sendDueReminders();

        $this->assertNotification($this->receptionist, 30, $appointment);
    }

    public function test_follow_ups_are_derived_from_appointment_state(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 13:45:00', 'Asia/Manila'));
        $appointment = $this->makeAppointment('14:00', '12:00');

        app(AttendanceReminderService::class)->sendDueReminders();

        $followUps = app(AttendanceReminderService::class)->followUps();

        $this->assertCount(1, $followUps);
        $this->assertSame($appointment->id, $followUps[0]['appointment_id']);
        $this->assertSame('Cora Customer', $followUps[0]['customer']);
        $this->assertSame('15 minutes', $followUps[0]['stage']);
        $this->assertSame('Awaiting confirmation', $followUps[0]['attendance_label']);

        $this->receptionist->unreadNotifications()->update(['read_at' => now()]);
        $this->assertCount(1, app(AttendanceReminderService::class)->followUps());

        app(AttendanceConfirmationService::class)->confirmByCustomer($appointment, $this->customerUser);
        $this->assertCount(0, app(AttendanceReminderService::class)->followUps());
    }

    public function test_customer_receives_appointment_day_notification_at_midnight(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 00:03:00', 'Asia/Manila'));
        $appointment = $this->makeAppointment('14:00', '2026-11-30 12:00');

        $result = app(AttendanceReminderService::class)->sendDueDayReminders();

        $this->assertSame(1, $result['sent']);
        $notification = $this->customerUser->notifications()
            ->where('data->type', 'attendance')
            ->where('data->stage', AttendanceReminderService::STAGE_DAY)
            ->firstOrFail();
        $this->assertSame($appointment->id, $notification->data['appointment_id']);
        $this->assertStringContainsString('today', $notification->data['message']);
        $this->assertDatabaseHas('activity_logs', [
            'entity_id' => $appointment->id,
            'action' => 'attendance_reminder_day_customer',
        ]);
    }

    public function test_day_notification_is_not_backfilled_before_or_after_midnight_window(): void
    {
        $appointment = $this->makeAppointment('14:00', '2026-11-30 12:00');

        Carbon::setTestNow(Carbon::parse('2026-11-30 23:58:00', 'Asia/Manila'));
        app(AttendanceReminderService::class)->sendDueDayReminders();

        Carbon::setTestNow(Carbon::parse('2026-12-01 00:10:00', 'Asia/Manila'));
        app(AttendanceReminderService::class)->sendDueDayReminders();

        $this->assertSame(0, ActivityLog::query()
            ->where('entity_id', $appointment->id)
            ->where('action', 'attendance_reminder_day_customer')
            ->count());
    }

    public function test_day_notification_is_not_sent_when_approved_after_midnight(): void
    {
        $appointment = $this->makeAppointment('14:00', '2026-12-01 01:00');
        Carbon::setTestNow(Carbon::parse('2026-12-01 00:03:00', 'Asia/Manila'));

        app(AttendanceReminderService::class)->sendDueDayReminders();

        $this->assertSame(0, ActivityLog::query()->where('entity_id', $appointment->id)->count());
    }

    public function test_day_notification_is_not_duplicated_and_stops_after_confirmation(): void
    {
        $appointment = $this->makeAppointment('14:00', '2026-11-30 12:00');
        Carbon::setTestNow(Carbon::parse('2026-12-01 00:03:00', 'Asia/Manila'));
        $service = app(AttendanceReminderService::class);

        $this->assertSame(1, $service->sendDueDayReminders()['sent']);
        $this->assertSame(0, $service->sendDueDayReminders()['sent']);

        app(AttendanceConfirmationService::class)->confirmByCustomer($appointment, $this->customerUser);

        Carbon::setTestNow(Carbon::parse('2026-12-01 13:00:00', 'Asia/Manila'));
        $service->sendDueReminders();

        $this->assertSame(1, ActivityLog::query()
            ->where('entity_id', $appointment->id)
            ->where('action', 'like', 'attendance_reminder_%')
            ->count());
    }

    public function test_guest_day_notification_goes_to_receptionist_with_phone(): void
    {
        $guest = Customer::create([
            'user_id' => null,
            'first_name' => 'Guest',
            'last_name' => 'Visitor',
            'email' => null,
            'phone_number' => '09170000000',
        ]);

        $this->makeAppointment('14:00', '2026-11-30 12:00', ['customer_id' => $guest->id]);
        Carbon::setTestNow(Carbon::parse('2026-12-01 00:03:00', 'Asia/Manila'));

        app(AttendanceReminderService::class)->sendDueDayReminders();

        $this->assertSame(0, $this->customerUser->notifications()->where('data->type', 'attendance')->count());
        $notification = $this->receptionist->notifications()
            ->where('data->type', 'attendance')
            ->where('data->stage', AttendanceReminderService::STAGE_DAY)
            ->firstOrFail();
        $this->assertStringContainsString('09170000000', $notification->data['message']);
    }

    private function makeAppointment(string $start, string $confirmedAt, array $overrides = []): Appointment
    {
        $approver = User::factory()->create();
        $startCarbon = Carbon::parse($start, 'Asia/Manila');

        return Appointment::create(array_merge([
            'customer_id' => $this->customer->id,
            'appointment_date' => Carbon::parse('2026-12-01', 'Asia/Manila')->toDateString(),
            'start_time' => $startCarbon->format('H:i:s'),
            'end_time' => $startCarbon->copy()->addMinutes(30)->format('H:i:s'),
            'status' => 'confirmed',
            'total_price' => 1500,
            'confirmed_at' => Carbon::parse($confirmedAt, 'Asia/Manila'),
            'confirmed_by' => $approver->id,
        ], $overrides));
    }

    private function assertNotification(User $user, int $stage, Appointment $appointment): void
    {
        $notification = $user->notifications()
            ->where('data->type', 'attendance')
            ->where('data->stage', $stage)
            ->where('data->appointment_id', $appointment->id)
            ->firstOrFail();

        $this->assertSame(SystemNotification::class, $notification->type);
        $this->assertNotNull($notification->data['action_url'] ?? null);
        $this->assertStringStartsWith('/', $notification->data['action_url']);
        $this->assertNull($notification->read_at);
    }
}
