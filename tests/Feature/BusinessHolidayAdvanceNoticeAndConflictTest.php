<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BusinessException;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\BusinessScheduleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessHolidayAdvanceNoticeAndConflictTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        return $user;
    }

    private function createService(int $duration = 60, float $price = 500.00): Service
    {
        $category = ServiceCategory::firstOrCreate(['name' => 'General Massage']);
        return Service::create([
            'category_id' => $category->id,
            'name' => 'Aromatherapy ' . uniqid(),
            'price' => $price,
            'duration_minutes' => $duration,
            'is_active' => true,
            'requires_room' => false,
        ]);
    }

    // =========================================================================
    // 1. Direct booking request on a closed date
    // =========================================================================

    public function test_direct_booking_request_on_closed_date_is_rejected(): void
    {
        $service = $this->createService(60);
        $staff = $this->makeUserWithRole('staff');

        $now = Carbon::parse('2026-10-28 10:00:00', 'Asia/Manila');
        Carbon::setTestNow($now);

        try {
            $closedDate = $now->copy()->addDays(2)->toDateString();
            $dow = Carbon::parse($closedDate)->dayOfWeek;

            // Therapist has scheduled work
            WorkSchedule::create([
                'user_id' => $staff->id,
                'day_of_week' => $dow,
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'is_day_off' => false,
            ]);

            // Business exception: closed for full day holiday
            BusinessException::create([
                'date' => $closedDate,
                'title' => 'National Holiday Closure',
                'type' => 'holiday',
                'is_closed' => true,
                'notice_message' => 'Closed for national holiday.',
            ]);

            $response = $this->post(route('booking.store'), [
                'services' => [$service->id],
                'appointment_date' => $closedDate,
                'start_time' => '10:00:00',
                'guest_first_name' => 'Direct',
                'guest_last_name' => 'Tester',
                'guest_phone' => '09123456789',
                'guest_email' => 'direct@example.com',
            ]);

            $response->assertSessionHas('error');
            $this->assertDatabaseMissing('appointments', [
                'appointment_date' => $closedDate,
            ]);
        } finally {
            Carbon::setTestNow(null);
        }
    }

    // =========================================================================
    // 2. Complete appointment duration exceeds custom closing time
    // =========================================================================

    public function test_booking_request_exceeding_custom_closing_time_is_rejected(): void
    {
        $service = $this->createService(90); // 90 minutes
        $staff = $this->makeUserWithRole('staff');

        $now = Carbon::parse('2026-10-28 10:00:00', 'Asia/Manila');
        Carbon::setTestNow($now);

        try {
            $customDate = $now->copy()->addDays(3)->toDateString();
            $dow = Carbon::parse($customDate)->dayOfWeek;

            WorkSchedule::create([
                'user_id' => $staff->id,
                'day_of_week' => $dow,
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'is_day_off' => false,
            ]);

            // Business exception: opens at 10:00, closes at 14:00
            BusinessException::create([
                'date' => $customDate,
                'title' => 'Half-day Renovation',
                'type' => 'custom_hours',
                'is_closed' => false,
                'open_time' => '10:00:00',
                'close_time' => '14:00:00',
            ]);

            // Request start at 13:00 -> 90 min duration ends at 14:30 (exceeds 14:00 closing)
            $response = $this->post(route('booking.store'), [
                'services' => [$service->id],
                'appointment_date' => $customDate,
                'start_time' => '13:00:00',
                'guest_first_name' => 'Boundary',
                'guest_last_name' => 'Tester',
                'guest_phone' => '09123456788',
                'guest_email' => 'boundary@example.com',
            ]);

            $response->assertSessionHas('error');
            $this->assertDatabaseMissing('appointments', [
                'appointment_date' => $customDate,
                'start_time' => '13:00:00',
            ]);
        } finally {
            Carbon::setTestNow(null);
        }
    }

    // =========================================================================
    // 3. Advance notices
    // =========================================================================

    public function test_upcoming_notices_appear_for_future_dates_within_horizon(): void
    {
        $now = Carbon::parse('2026-10-28 10:00:00', 'Asia/Manila');
        Carbon::setTestNow($now);

        try {
            // Future exception 3 days away
            $futureDate = $now->copy()->addDays(3)->toDateString();
            BusinessException::create([
                'date' => $futureDate,
                'title' => 'Upcoming Holiday',
                'type' => 'holiday',
                'is_closed' => true,
                'notice_message' => 'Advance notice of holiday closure.',
            ]);

            $notices = BusinessScheduleService::getUpcomingNotices(7, $now);
            $this->assertCount(1, $notices);
            $this->assertSame('Upcoming Holiday', $notices->first()['title']);
            $this->assertTrue($notices->first()['is_closed']);

            // Landing page shows the notice
            $res = $this->get(route('landing'));
            $res->assertStatus(200);
            $res->assertSee('Upcoming Schedule Notices');
            $res->assertSee('Upcoming Holiday');
            $res->assertSee('Advance notice of holiday closure.');

            // Wizard page shows the notice
            $resWizard = $this->get(route('booking.wizard'));
            $resWizard->assertStatus(200);
            $resWizard->assertSee('Upcoming Schedule Notices');
            $resWizard->assertSee('Upcoming Holiday');
        } finally {
            Carbon::setTestNow(null);
        }
    }

    public function test_past_exceptions_do_not_appear_as_upcoming_notices(): void
    {
        $now = Carbon::parse('2026-10-28 10:00:00', 'Asia/Manila');
        Carbon::setTestNow($now);

        try {
            $pastDate = $now->copy()->subDays(2)->toDateString();
            BusinessException::create([
                'date' => $pastDate,
                'title' => 'Old Expired Notice',
                'type' => 'holiday',
                'is_closed' => true,
                'notice_message' => 'This was in the past.',
            ]);

            $notices = BusinessScheduleService::getUpcomingNotices(7, $now);
            $this->assertCount(0, $notices);

            $res = $this->get(route('landing'));
            $res->assertDontSee('Old Expired Notice');
        } finally {
            Carbon::setTestNow(null);
        }
    }

    public function test_todays_closure_is_distinguished_from_future_closure(): void
    {
        $now = Carbon::parse('2026-10-28 10:00:00', 'Asia/Manila');
        Carbon::setTestNow($now);

        try {
            $todayStr = $now->toDateString();
            BusinessException::create([
                'date' => $todayStr,
                'title' => 'Closure Today',
                'type' => 'holiday',
                'is_closed' => true,
                'notice_message' => 'Closed today for inspection.',
            ]);

            $activeNotice = BusinessScheduleService::getActiveNotice($now);
            $this->assertNotNull($activeNotice);
            $this->assertSame('Closure Today', $activeNotice['title']);

            // Today must NOT be included in getUpcomingNotices
            $upcoming = BusinessScheduleService::getUpcomingNotices(7, $now);
            $this->assertCount(0, $upcoming);

            $res = $this->get(route('landing'));
            $res->assertSee('Closed Today');
            $res->assertSee('Closure Today');
        } finally {
            Carbon::setTestNow(null);
        }
    }

    // =========================================================================
    // 4. Existing appointment conflicts
    // =========================================================================

    public function test_saving_full_day_closure_warns_about_existing_active_appointments(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $staff = $this->makeUserWithRole('staff');
        $service = $this->createService(60);

        $now = Carbon::parse('2026-10-28 10:00:00', 'Asia/Manila');
        Carbon::setTestNow($now);

        try {
            $targetDate = $now->copy()->addDays(4)->toDateString();

            // Create pre-existing confirmed appointment
            $customer = Customer::create([
                'first_name' => 'Existing',
                'last_name' => 'Customer',
                'phone_number' => '09123456787',
            ]);

            $appt = Appointment::create([
                'customer_id' => $customer->id,
                'user_id' => $staff->id,
                'appointment_date' => $targetDate,
                'start_time' => '11:00:00',
                'end_time' => '12:00:00',
                'status' => 'confirmed',
                'total_price' => 500.00,
            ]);
            $appt->services()->attach($service->id, ['price_at_booking' => 500.00]);

            // Admin saves full-day closure on that date
            $response = $this->actingAs($admin)->post(route('business-exceptions.store'), [
                'date' => $targetDate,
                'title' => 'Emergency Closure',
                'type' => 'holiday',
                'is_closed' => '1',
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success');
            $response->assertSessionHas('warning');
            $this->assertStringContainsString('1 existing appointment(s)', session('warning'));

            // The appointment was NOT cancelled or rescheduled automatically
            $this->assertSame('confirmed', $appt->fresh()->status);
            $this->assertSame($targetDate, $appt->fresh()->appointment_date->toDateString());
        } finally {
            Carbon::setTestNow(null);
        }
    }

    public function test_custom_hours_warns_for_conflicting_appointments_only(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $staff = $this->makeUserWithRole('staff');
        $service = $this->createService(60);

        $now = Carbon::parse('2026-10-28 10:00:00', 'Asia/Manila');
        Carbon::setTestNow($now);

        try {
            $targetDate = $now->copy()->addDays(5)->toDateString();

            $customer = Customer::create([
                'first_name' => 'Custom',
                'last_name' => 'Customer',
                'phone_number' => '09123456786',
            ]);

            // Appt 1: 10:00 to 11:00 (Fits inside 10:00 - 15:00 window)
            $appt1 = Appointment::create([
                'customer_id' => $customer->id,
                'user_id' => $staff->id,
                'appointment_date' => $targetDate,
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'status' => 'confirmed',
                'total_price' => 500.00,
            ]);
            $appt1->services()->attach($service->id, ['price_at_booking' => 500.00]);

            // Appt 2: 15:00 to 16:00 (Ends at 16:00, outside 15:00 window -> Conflicts)
            $appt2 = Appointment::create([
                'customer_id' => $customer->id,
                'user_id' => $staff->id,
                'appointment_date' => $targetDate,
                'start_time' => '15:00:00',
                'end_time' => '16:00:00',
                'status' => 'confirmed',
                'total_price' => 500.00,
            ]);
            $appt2->services()->attach($service->id, ['price_at_booking' => 500.00]);

            // Admin saves custom hours: 10:00 - 15:00
            $response = $this->actingAs($admin)->post(route('business-exceptions.store'), [
                'date' => $targetDate,
                'title' => 'Afternoon Early Close',
                'type' => 'custom_hours',
                'is_closed' => '0',
                'open_time' => '10:00',
                'close_time' => '15:00',
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success');
            $response->assertSessionHas('warning');
            // Exactly 1 conflicting appointment detected (appt 2)
            $this->assertStringContainsString('1 existing appointment(s)', session('warning'));

            // Both appointments preserved
            $this->assertSame('confirmed', $appt1->fresh()->status);
            $this->assertSame('confirmed', $appt2->fresh()->status);
        } finally {
            Carbon::setTestNow(null);
        }
    }

    public function test_non_conflicting_save_does_not_produce_warning(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $targetDate = Carbon::now('Asia/Manila')->addDays(6)->toDateString();

        $response = $this->actingAs($admin)->post(route('business-exceptions.store'), [
            'date' => $targetDate,
            'title' => 'Clean Slate Holiday',
            'type' => 'holiday',
            'is_closed' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionMissing('warning');
    }

    public function test_unauthorized_users_cannot_access_business_exceptions(): void
    {
        $customer = $this->makeUserWithRole('customer');
        $staff = $this->makeUserWithRole('staff');
        $unauthRecep = $this->makeUserWithRole('receptionist');
        $unauthRecep->can_manage_business_hours = false;
        $unauthRecep->save();

        $payload = [
            'date' => now()->addDays(5)->toDateString(),
            'title' => 'Unauthorized Attempt',
            'type' => 'holiday',
            'is_closed' => '1',
        ];

        $this->actingAs($customer)->post(route('business-exceptions.store'), $payload)->assertStatus(403);
        $this->actingAs($staff)->post(route('business-exceptions.store'), $payload)->assertStatus(403);
        $this->actingAs($unauthRecep)->post(route('business-exceptions.store'), $payload)->assertStatus(403);
    }
}
