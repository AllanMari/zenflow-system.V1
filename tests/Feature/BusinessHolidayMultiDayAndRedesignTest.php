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

class BusinessHolidayMultiDayAndRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName, array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create(array_merge(['is_active' => true], $attributes));
        $user->roles()->attach($role);

        return $user;
    }

    private function createService(int $duration = 60, float $price = 500.00): Service
    {
        $category = ServiceCategory::firstOrCreate(['name' => 'General Massage']);

        return Service::create([
            'category_id' => $category->id,
            'name' => 'Swedish Massage ' . $duration . 'm',
            'duration_minutes' => $duration,
            'price' => $price,
            'is_active' => true,
        ]);
    }

    // =========================================================================
    // 1. Single-day holiday creation via date_range or date input
    // =========================================================================

    public function test_single_day_holiday_creation(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $targetDate = '2026-11-15';

        $response = $this->actingAs($admin)->post(route('business-exceptions.store'), [
            'date_range' => $targetDate,
            'title' => 'Single Day Holiday',
            'type' => 'holiday',
            'is_closed' => '1',
            'notice_message' => 'Closed for special event.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $record = BusinessException::whereDate('date', $targetDate)->first();
        $this->assertNotNull($record);
        $this->assertEquals('Single Day Holiday', $record->title);
        $this->assertTrue((bool)$record->is_closed);
        $this->assertEquals('holiday', $record->type);
    }

    // =========================================================================
    // 2. Multi-day inclusive range creation creates records for every date
    // =========================================================================

    public function test_multi_day_holiday_creates_records_for_every_date_in_range(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $rangeInput = '2026-10-30 to 2026-11-01'; // 3 consecutive days: Oct 30, Oct 31, Nov 01

        $response = $this->actingAs($admin)->post(route('business-exceptions.store'), [
            'date_range' => $rangeInput,
            'title' => 'All Saints Long Weekend',
            'type' => 'holiday',
            'is_closed' => '1',
            'notice_message' => 'Closed for Undas holidays.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertNotNull(BusinessException::whereDate('date', '2026-10-30')->first());
        $this->assertNotNull(BusinessException::whereDate('date', '2026-10-31')->first());
        $this->assertNotNull(BusinessException::whereDate('date', '2026-11-01')->first());
        $this->assertNull(BusinessException::whereDate('date', '2026-11-02')->first());
    }

    // =========================================================================
    // 3. Multi-day holiday rejects bookings across all dates in the range
    // =========================================================================

    public function test_booking_rejected_on_every_date_in_multi_day_holiday_range(): void
    {
        $service = $this->createService(60);
        $staff = $this->makeUserWithRole('staff');

        $now = Carbon::parse('2026-10-25 10:00:00', 'Asia/Manila');
        Carbon::setTestNow($now);

        try {
            for ($dow = 0; $dow <= 6; $dow++) {
                WorkSchedule::create([
                    'user_id' => $staff->id,
                    'day_of_week' => $dow,
                    'start_time' => '08:00:00',
                    'end_time' => '20:00:00',
                    'is_day_off' => false,
                ]);
            }

            // Create multi-day holiday for Oct 29 through Oct 31
            foreach (['2026-10-29', '2026-10-30', '2026-10-31'] as $d) {
                BusinessException::create([
                    'date' => $d,
                    'title' => 'Maintenance Break',
                    'type' => 'holiday',
                    'is_closed' => true,
                    'notice_message' => 'Closed for scheduled maintenance.',
                ]);
            }

            foreach (['2026-10-29', '2026-10-30', '2026-10-31'] as $targetDate) {
                $response = $this->post(route('booking.store'), [
                    'services' => [$service->id],
                    'appointment_date' => $targetDate,
                    'start_time' => '10:00:00',
                    'guest_first_name' => 'Range',
                    'guest_last_name' => 'Tester',
                    'guest_phone' => '09123456789',
                    'guest_email' => 'range@example.com',
                ]);

                $response->assertSessionHas('error');
                $this->assertDatabaseMissing('appointments', [
                    'appointment_date' => $targetDate,
                ]);
            }
        } finally {
            Carbon::setTestNow(null);
        }
    }

    // =========================================================================
    // 4. Upcoming notices properly display dates within horizon for multi-day
    // =========================================================================

    public function test_upcoming_notices_reflect_all_future_dates_in_multi_day_holiday(): void
    {
        $now = Carbon::parse('2026-10-28 10:00:00', 'Asia/Manila');
        Carbon::setTestNow($now);

        try {
            // Days +2, +3, +4
            foreach (['2026-10-30', '2026-10-31', '2026-11-01'] as $d) {
                BusinessException::create([
                    'date' => $d,
                    'title' => 'Long Weekend Break',
                    'type' => 'holiday',
                    'is_closed' => true,
                ]);
            }

            $notices = BusinessScheduleService::getUpcomingNotices(7, $now);

            $this->assertCount(3, $notices);
            $this->assertEquals(['2026-10-30', '2026-10-31', '2026-11-01'], $notices->pluck('date')->all());
        } finally {
            Carbon::setTestNow(null);
        }
    }

    // =========================================================================
    // 5. Updating an existing exception via PUT route
    // =========================================================================

    public function test_update_exception_modifies_record_without_duplication(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $exc = BusinessException::create([
            'date' => '2026-11-20',
            'title' => 'Original Title',
            'type' => 'holiday',
            'is_closed' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('business-exceptions.update', $exc), [
            'date' => '2026-11-20',
            'title' => 'Updated Custom Hours Title',
            'type' => 'custom_hours',
            'is_closed' => '0',
            'open_time' => '10:00',
            'close_time' => '16:00',
            'notice_message' => 'Open with limited staff',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $exc->fresh();
        $this->assertEquals('Updated Custom Hours Title', $fresh->title);
        $this->assertEquals('custom_hours', $fresh->type);
        $this->assertFalse($fresh->is_closed);
        $this->assertEquals('10:00', substr($fresh->open_time, 0, 5));
        $this->assertEquals('16:00', substr($fresh->close_time, 0, 5));
    }

    // =========================================================================
    // 6. Deleting one date does not affect other dates
    // =========================================================================

    public function test_deleting_one_date_exception_preserves_other_dates(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $exc1 = BusinessException::create([
            'date' => '2026-12-24',
            'title' => 'Christmas Eve',
            'type' => 'holiday',
            'is_closed' => true,
        ]);
        $exc2 = BusinessException::create([
            'date' => '2026-12-25',
            'title' => 'Christmas Day',
            'type' => 'holiday',
            'is_closed' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('business-exceptions.destroy', $exc1));
        $response->assertRedirect();

        $this->assertDatabaseMissing('business_exceptions', ['id' => $exc1->id]);
        $this->assertDatabaseHas('business_exceptions', ['id' => $exc2->id]);
    }

    // =========================================================================
    // 7. Multi-day range conflict detection warns for appointments on ANY date
    // =========================================================================

    public function test_multi_day_save_warns_about_appointments_across_all_affected_dates(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $staff = $this->makeUserWithRole('staff');
        $customer = Customer::create([
            'first_name' => 'Multi',
            'last_name' => 'Conflicter',
            'phone_number' => '09123456789',
        ]);
        $service = $this->createService(60);

        // Appointment 1: On 2026-11-05
        $appt1 = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $staff->id,
            'appointment_date' => '2026-11-05',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 500.00,
        ]);
        $appt1->services()->attach($service->id, ['price_at_booking' => 500.00]);

        // Appointment 2: On 2026-11-07
        $appt2 = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $staff->id,
            'appointment_date' => '2026-11-07',
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'pending',
            'total_price' => 500.00,
        ]);
        $appt2->services()->attach($service->id, ['price_at_booking' => 500.00]);

        // Save closure for 2026-11-05 to 2026-11-07 (3 days)
        $response = $this->actingAs($admin)->post(route('business-exceptions.store'), [
            'date_range' => '2026-11-05 to 2026-11-07',
            'title' => 'Renovation Block',
            'type' => 'holiday',
            'is_closed' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('warning');

        $warning = session('warning');
        $this->assertStringContainsString('2 existing appointment(s)', $warning);

        // Ensure neither appointment was modified or cancelled
        $this->assertEquals('confirmed', $appt1->fresh()->status);
        $this->assertEquals('pending', $appt2->fresh()->status);
    }

    // =========================================================================
    // 8. Invalid date range (end date earlier than start date) rejected
    // =========================================================================

    public function test_invalid_date_range_is_rejected(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->post(route('business-exceptions.store'), [
            'date_range' => '2026-11-10 to 2026-11-05', // End before start
            'title' => 'Inverted Dates',
            'type' => 'holiday',
            'is_closed' => '1',
        ]);

        $response->assertSessionHasErrors(['date_end']);
        $this->assertDatabaseMissing('business_exceptions', [
            'title' => 'Inverted Dates',
        ]);
    }

    // =========================================================================
    // 9. Unauthorized users cannot update or store exceptions
    // =========================================================================

    public function test_unauthorized_staff_cannot_store_or_update_exceptions(): void
    {
        $staff = $this->makeUserWithRole('staff');
        $exc = BusinessException::create([
            'date' => '2026-11-12',
            'title' => 'Admin Holiday',
            'type' => 'holiday',
            'is_closed' => true,
        ]);

        $postRes = $this->actingAs($staff)->post(route('business-exceptions.store'), [
            'date' => '2026-11-13',
            'title' => 'Staff Hack',
            'type' => 'holiday',
            'is_closed' => '1',
        ]);
        $postRes->assertForbidden();

        $putRes = $this->actingAs($staff)->put(route('business-exceptions.update', $exc), [
            'date' => '2026-11-12',
            'title' => 'Staff Modification',
            'type' => 'holiday',
            'is_closed' => '1',
        ]);
        $putRes->assertForbidden();
    }

    // =========================================================================
    // 10. Overlapping range cannot silently overwrite existing exceptions
    // =========================================================================

    public function test_overlapping_range_does_not_silently_overwrite_existing_exception(): void
    {
        $admin = $this->makeUserWithRole('admin');

        // Pre-existing exception on 2026-11-25
        BusinessException::create([
            'date' => '2026-11-25',
            'title' => 'Existing Company Day',
            'type' => 'event',
            'is_closed' => false,
        ]);

        // Attempt to create a multi-day range 2026-11-24 to 2026-11-26 that covers 11-25
        $response = $this->actingAs($admin)->post(route('business-exceptions.store'), [
            'date_range' => '2026-11-24 to 2026-11-26',
            'title' => 'New Range Closure',
            'type' => 'holiday',
            'is_closed' => '1',
        ]);

        $response->assertSessionHasErrors(['date_range']);
        $this->assertStringContainsString('Existing Company Day', session('errors')->first('date_range'));

        // Existing exception was not overwritten
        $existing = BusinessException::whereDate('date', '2026-11-25')->first();
        $this->assertEquals('Existing Company Day', $existing->title);
        $this->assertFalse((bool)$existing->is_closed);

        // Surrounding dates were not inserted
        $this->assertNull(BusinessException::whereDate('date', '2026-11-24')->first());
        $this->assertNull(BusinessException::whereDate('date', '2026-11-26')->first());
    }

    // =========================================================================
    // 11. Special event announcement does not close the spa
    // =========================================================================

    public function test_special_event_announcement_does_not_close_spa_or_block_booking(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $staff = $this->makeUserWithRole('staff');
        $service = $this->createService(60);
        $eventDate = '2026-11-18';
        $dow = Carbon::parse($eventDate)->dayOfWeek;

        for ($i = 0; $i < 7; $i++) {
            BusinessHour::create([
                'day_of_week' => $i,
                'open_time' => '09:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false
            ]);
        }

        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $dow,
            'start_time' => '09:00:00',
            'end_time' => '20:00:00',
            'is_day_off' => false,
        ]);
        Room::create(['name' => 'Room 1', 'status' => 'available', 'is_active' => true]);

        // Create Special Event
        $response = $this->actingAs($admin)->post(route('business-exceptions.store'), [
            'date_range' => $eventDate,
            'title' => 'Spa Anniversary Celebration',
            'type' => 'event',
            'is_closed' => '0',
            'notice_message' => 'Join us for anniversary surprises!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $window = BusinessScheduleService::getOperatingWindow($eventDate);
        $this->assertTrue($window['is_open']);
        $this->assertEquals('Spa Anniversary Celebration', $window['reason']);

        // Booking on event date should succeed (not be blocked)
        $bookingRes = $this->post(route('booking.store'), [
            'services' => [$service->id],
            'appointment_date' => $eventDate,
            'start_time' => '10:00',
            'guest_first_name' => 'Event',
            'guest_phone' => '09123456789',
            'source' => 'public',
        ]);

        $bookingRes->assertSessionHasNoErrors();
        $this->assertNotNull(Appointment::whereDate('appointment_date', $eventDate)->where('guest_first_name', 'Event')->first());
    }

    // =========================================================================
    // 12. Filtering exceptions by status, type, and search query
    // =========================================================================

    public function test_exceptions_filtering_and_search(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $today = Carbon::today('Asia/Manila')->toDateString();
        $pastDate = Carbon::yesterday('Asia/Manila')->toDateString();
        $futureDate = Carbon::tomorrow('Asia/Manila')->toDateString();

        BusinessException::create([
            'date' => $pastDate,
            'title' => 'Past Maintenance Closure',
            'type' => 'holiday',
            'is_closed' => true,
        ]);

        BusinessException::create([
            'date' => $futureDate,
            'title' => 'Future Gala Evening',
            'type' => 'event',
            'is_closed' => false,
        ]);

        // Filter upcoming: only the upcoming record should be in the table
        $resUpcoming = $this->actingAs($admin)->get(route('business-hours.index', ['tab' => 'exceptions', 'filter' => 'upcoming']));
        $resUpcoming->assertStatus(200);
        $resUpcoming->assertViewHas('exceptions', function ($exceptions) {
            return $exceptions->pluck('title')->contains('Future Gala Evening')
                && !$exceptions->pluck('title')->contains('Past Maintenance Closure');
        });

        // Filter past: only the past record should be in the table
        $resPast = $this->actingAs($admin)->get(route('business-hours.index', ['tab' => 'exceptions', 'filter' => 'past']));
        $resPast->assertStatus(200);
        $resPast->assertViewHas('exceptions', function ($exceptions) {
            return $exceptions->pluck('title')->contains('Past Maintenance Closure')
                && !$exceptions->pluck('title')->contains('Future Gala Evening');
        });

        // Filter by type: only 'event' type exceptions
        $resType = $this->actingAs($admin)->get(route('business-hours.index', ['tab' => 'exceptions', 'type_filter' => 'event']));
        $resType->assertStatus(200);
        $resType->assertViewHas('exceptions', function ($exceptions) {
            return $exceptions->pluck('title')->contains('Future Gala Evening')
                && !$exceptions->pluck('title')->contains('Past Maintenance Closure');
        });

        // Search query: matching search text
        $resSearch = $this->actingAs($admin)->get(route('business-hours.index', ['tab' => 'exceptions', 'search' => 'Gala']));
        $resSearch->assertStatus(200);
        $resSearch->assertViewHas('exceptions', function ($exceptions) {
            return $exceptions->pluck('title')->contains('Future Gala Evening')
                && !$exceptions->pluck('title')->contains('Past Maintenance Closure');
        });
    }

    // =========================================================================
    // 13. Multiple exceptions management across separate dates
    // =========================================================================

    public function test_multiple_exceptions_can_coexist_on_separate_dates(): void
    {
        $admin = $this->makeUserWithRole('admin');

        // Exception 1: Holiday
        $this->actingAs($admin)->post(route('business-exceptions.store'), [
            'date_range' => '2026-12-24',
            'title' => 'Christmas Eve Closure',
            'type' => 'holiday',
            'is_closed' => '1',
            'description' => 'Staff holiday party',
        ]);

        // Exception 2: Custom Hours
        $this->actingAs($admin)->post(route('business-exceptions.store'), [
            'date_range' => '2026-12-28',
            'title' => 'Post-Holiday Half Day',
            'type' => 'custom_hours',
            'open_time' => '10:00',
            'close_time' => '14:00',
            'description' => 'Inventory count',
        ]);

        // Exception 3: Event
        $this->actingAs($admin)->post(route('business-exceptions.store'), [
            'date_range' => '2026-12-31',
            'title' => 'New Year Eve Celebration',
            'type' => 'event',
            'notice_message' => 'Complimentary herbal tea for all guests!',
        ]);

        $this->assertNotNull(BusinessException::whereDate('date', '2026-12-24')->first());
        $this->assertNotNull(BusinessException::whereDate('date', '2026-12-28')->first());
        $this->assertNotNull(BusinessException::whereDate('date', '2026-12-31')->first());

        // Verify description is saved internally
        $dec24 = BusinessException::whereDate('date', '2026-12-24')->first();
        $this->assertEquals('Staff holiday party', $dec24->description);
    }

    // =========================================================================
    // 14. Fragment requests return only table partial without full layout
    // =========================================================================

    public function test_fragment_request_returns_only_exceptions_partial(): void
    {
        $admin = $this->makeUserWithRole('admin');

        BusinessException::create([
            'date' => '2026-12-15',
            'title' => 'Partial Test Holiday',
            'type' => 'holiday',
            'is_closed' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('business-hours.index', [
            'tab' => 'exceptions',
            'fragment' => 'exceptions',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertViewIs('shared._business_exceptions_table');
        $response->assertSee('Partial Test Holiday');
        // Must NOT render outer HTML layout / header
        $response->assertDontSee('<!DOCTYPE html>');
        $response->assertDontSee('Weekly Schedule');
    }

    // =========================================================================
    // 15. Server-rendered initial tab is exceptions when ?tab=exceptions requested
    // =========================================================================

    public function test_exceptions_tab_is_server_rendered_as_active_when_requested(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->get(route('business-hours.index', ['tab' => 'exceptions']));
        $response->assertStatus(200);
        $response->assertSee("window.__initialBusinessHoursTab = 'exceptions'", false);
        // Exceptions tab container is NOT cloaked
        $response->assertSee("x-show=\"tab === 'exceptions'\"", false);
        // Weekly hours tab container IS cloaked when exceptions is active
        $response->assertSee("x-show=\"tab === 'hours'\" class=\"space-y-6\" x-cloak", false);
    }

    public function test_weekly_hours_tab_is_server_rendered_as_active_by_default(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->get(route('business-hours.index'));
        $response->assertStatus(200);
        $response->assertSee("window.__initialBusinessHoursTab = 'hours'", false);
        // Weekly hours tab container is NOT cloaked
        $response->assertSee("x-show=\"tab === 'hours'\" class=\"space-y-6\" ", false);
        // Exceptions tab container IS cloaked
        $response->assertSee("x-show=\"tab === 'exceptions'\" class=\"space-y-6\" x-cloak", false);
    }

    public function test_invalid_tab_query_falls_back_safely_to_hours(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $response = $this->actingAs($admin)->get(route('business-hours.index', ['tab' => 'invalid_tab_name']));
        $response->assertStatus(200);
        $response->assertSee("window.__initialBusinessHoursTab = 'hours'", false);
    }
}
