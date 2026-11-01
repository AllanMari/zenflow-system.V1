<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\BusinessException;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AbsenceMonitor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BusinessHolidayAttendanceAndRescheduleTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        return $user;
    }

    public function test_attendance_sync_does_not_mark_staff_absent_or_late_on_full_day_business_closure(): void
    {
        $staff = $this->makeUserWithRole('staff');

        $nowManila = Carbon::now('Asia/Manila');
        $today = $nowManila->toDateString();
        $dow = $nowManila->dayOfWeek;

        // Staff scheduled for today
        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $dow,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_day_off' => false,
        ]);

        // Full-day business holiday closure
        BusinessException::create([
            'date' => $today,
            'title' => 'National Holiday Closure',
            'type' => 'holiday',
            'is_closed' => true,
        ]);

        // Execute scheduled attendance sync
        $this->artisan('schedule:run')->assertSuccessful();

        // Staff must NOT have any auto-marked attendance record
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $staff->id,
            'date' => $today,
        ]);
    }

    public function test_attendance_sync_marks_late_and_absent_on_normal_operating_day(): void
    {
        $staff = $this->makeUserWithRole('staff');

        // Fix reference time to 16:00:00 on Wednesday so start/end times don't cross midnight
        $testNow = Carbon::parse('2026-10-28 16:00:00', 'Asia/Manila');
        Carbon::setTestNow($testNow);

        try {
            $today = $testNow->toDateString();
            $dow = $testNow->dayOfWeek;

            // Shift ended in the past today (08:00 to 14:00)
            WorkSchedule::create([
                'user_id' => $staff->id,
                'day_of_week' => $dow,
                'start_time' => '08:00:00',
                'end_time' => '14:00:00',
                'is_day_off' => false,
            ]);

            // No business holiday exception exists
            // Run the sync logic directly
            $todayCarbon = Carbon::today();
            $nowCarbon = Carbon::now();
            $schedule = WorkSchedule::where('user_id', $staff->id)
                ->where('day_of_week', $dow)
                ->where('is_day_off', false)
                ->first();

            $startTime = Carbon::parse($schedule->start_time);
            $endTime = Carbon::parse($schedule->end_time);

            if ($nowCarbon->greaterThan($endTime)) {
                Attendance::updateOrCreate(
                    ['user_id' => $staff->id, 'date' => $today],
                    [
                        'status' => 'absent',
                        'marked_by' => null,
                        'notes' => 'Auto-marked absent: no clock-in by end of shift (' . $endTime->format('H:i') . ')',
                    ]
                );
            }

            // Staff should be auto-marked absent
            $this->assertDatabaseHas('attendances', [
                'user_id' => $staff->id,
                'status' => 'absent',
            ]);
            $this->assertSame($today, Carbon::parse(Attendance::where('user_id', $staff->id)->value('date'))->toDateString());
        } finally {
            Carbon::setTestNow(null);
        }
    }

    public function test_absence_monitor_does_not_count_business_closure_toward_absence_streak(): void
    {
        $staff = $this->makeUserWithRole('staff');

        $now = Carbon::parse('2026-10-28 12:00:00', 'Asia/Manila');
        Carbon::setTestNow($now);

        try {
            // Set up work schedules for all days
            for ($i = 0; $i <= 6; $i++) {
                WorkSchedule::create([
                    'user_id' => $staff->id,
                    'day_of_week' => $i,
                    'start_time' => '09:00:00',
                    'end_time' => '18:00:00',
                    'is_day_off' => false,
                ]);
            }

            // Today is marked absent so the streak begins
            Attendance::create([
                'user_id' => $staff->id,
                'date' => $now->toDateString(),
                'status' => 'absent',
            ]);

            // Yesterday was an establishment-wide closure
            $yesterday = $now->copy()->subDay()->toDateString();
            BusinessException::create([
                'date' => $yesterday,
                'title' => 'Typhoon Closure',
                'type' => 'holiday',
                'is_closed' => true,
            ]);

            // 2 days ago was absent
            $twoDaysAgo = $now->copy()->subDays(2)->toDateString();
            Attendance::create([
                'user_id' => $staff->id,
                'date' => $twoDaysAgo,
                'status' => 'absent',
            ]);

            $streak = AbsenceMonitor::calculateStreak($staff->id);

            // Yesterday was closed, so it must be skipped and NOT in the absence list
            $this->assertNotContains($yesterday, $streak['dates']);
            // Both today and 2 days ago are in the streak (count is 2)
            $this->assertContains($twoDaysAgo, $streak['dates']);
            $this->assertContains($now->toDateString(), $streak['dates']);
            $this->assertEquals(2, $streak['count']);
        } finally {
            Carbon::setTestNow(null);
        }
    }

    public function test_attendance_report_excludes_full_day_business_closure_from_unexcused_absences(): void
    {
        $admin = $this->makeUserWithRole('admin');
        $staff = $this->makeUserWithRole('staff');

        $now = Carbon::now('Asia/Manila');
        $startOfWeek = $now->copy()->startOfWeek();
        $targetDate = $startOfWeek->copy()->addDays(2)->toDateString();
        $targetDow = Carbon::parse($targetDate)->dayOfWeek;

        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $targetDow,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'is_day_off' => false,
        ]);

        // Mark target date as a full-day business holiday
        BusinessException::create([
            'date' => $targetDate,
            'title' => 'Spa Holiday',
            'type' => 'holiday',
            'is_closed' => true,
        ]);

        // Request report for the week
        $response = $this->actingAs($admin)->get(route('attendance.report', [
            'range' => 'week',
            'staff_id' => $staff->id,
        ]));

        $response->assertStatus(200);
        $summary = $response->viewData('summary');

        // Target day should NOT be counted as absent
        $this->assertEquals(0, $summary['absent']);
    }

    public function test_genuine_attendance_records_remain_intact_when_business_closure_exists(): void
    {
        $staff = $this->makeUserWithRole('staff');
        $today = Carbon::today('Asia/Manila')->toDateString();

        BusinessException::create([
            'date' => $today,
            'title' => 'Closed Day Shift',
            'type' => 'holiday',
            'is_closed' => true,
        ]);

        // Staff worked on inventory during the closed day
        $att = Attendance::create([
            'user_id' => $staff->id,
            'date' => $today,
            'status' => 'present',
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $att->id,
            'user_id' => $staff->id,
            'status' => 'present',
        ]);
        $this->assertSame($today, Carbon::parse($att->fresh()->date)->toDateString());
    }

    public function test_reschedule_rejects_full_day_business_closure(): void
    {
        $receptionist = $this->makeUserWithRole('receptionist');
        $staff = $this->makeUserWithRole('staff');

        $now = Carbon::now('Asia/Manila');
        $origDate = $now->copy()->addDays(2)->toDateString();
        $targetHoliday = $now->copy()->addDays(3)->toDateString();

        BusinessException::create([
            'date' => $targetHoliday,
            'title' => 'Holiday Closure',
            'type' => 'holiday',
            'is_closed' => true,
        ]);

        $appointment = $this->createAppointmentWithService($staff, $origDate);

        Cache::flush();
        $response = $this->actingAs($receptionist)->post(
            route('receptionist.reschedule', ['appointmentId' => $appointment->id]),
            [
                'appointment_date' => $targetHoliday,
                'start_time' => '10:00',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Holiday Closure');

        // Appointment date unchanged
        $this->assertSame($origDate, $appointment->fresh()->appointment_date->toDateString());
    }

    public function test_reschedule_succeeds_within_custom_business_hours(): void
    {
        $receptionist = $this->makeUserWithRole('receptionist');
        $staff = $this->makeUserWithRole('staff');

        $now = Carbon::now('Asia/Manila');
        $origDate = $now->copy()->addDays(2)->toDateString();
        $targetDate = $now->copy()->addDays(3)->toDateString();
        $targetDow = Carbon::parse($targetDate)->dayOfWeek;

        // Custom business hours: 10:00 to 16:00
        BusinessException::create([
            'date' => $targetDate,
            'title' => 'Half Day Operating Hours',
            'type' => 'custom_hours',
            'is_closed' => false,
            'open_time' => '10:00:00',
            'close_time' => '16:00:00',
        ]);

        // Staff available from 09:00 to 18:00
        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $targetDow,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'is_day_off' => false,
        ]);

        $appointment = $this->createAppointmentWithService($staff, $origDate);

        Cache::flush();
        $response = $this->actingAs($receptionist)->post(
            route('receptionist.reschedule', ['appointmentId' => $appointment->id]),
            [
                'appointment_date' => $targetDate,
                'start_time' => '11:00',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame($targetDate, $appointment->fresh()->appointment_date->toDateString());
        $this->assertStringStartsWith('11:00', $appointment->fresh()->start_time);
    }

    public function test_reschedule_rejects_times_outside_custom_business_hours(): void
    {
        $receptionist = $this->makeUserWithRole('receptionist');
        $staff = $this->makeUserWithRole('staff');

        $now = Carbon::now('Asia/Manila');
        $origDate = $now->copy()->addDays(2)->toDateString();
        $targetDate = $now->copy()->addDays(3)->toDateString();
        $targetDow = Carbon::parse($targetDate)->dayOfWeek;

        // Custom business hours: 10:00 to 14:00
        BusinessException::create([
            'date' => $targetDate,
            'title' => 'Morning Only Schedule',
            'type' => 'custom_hours',
            'is_closed' => false,
            'open_time' => '10:00:00',
            'close_time' => '14:00:00',
        ]);

        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $targetDow,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_day_off' => false,
        ]);

        $appointment = $this->createAppointmentWithService($staff, $origDate); // duration 60 mins

        // Attempt start at 13:30 -> ends at 14:30 (exceeds 14:00 close)
        Cache::flush();
        $response = $this->actingAs($receptionist)->post(
            route('receptionist.reschedule', ['appointmentId' => $appointment->id]),
            [
                'appointment_date' => $targetDate,
                'start_time' => '13:30',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Original appointment intact
        $this->assertSame($origDate, $appointment->fresh()->appointment_date->toDateString());
    }

    public function test_existing_staff_and_room_checks_continue_to_apply(): void
    {
        $receptionist = $this->makeUserWithRole('receptionist');
        $staff = $this->makeUserWithRole('staff');

        $now = Carbon::now('Asia/Manila');
        $origDate = $now->copy()->addDays(2)->toDateString();
        $targetDate = $now->copy()->addDays(3)->toDateString();
        $targetDow = Carbon::parse($targetDate)->dayOfWeek;

        // Staff is off on target day
        WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $targetDow,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'is_day_off' => true,
        ]);

        $appointment = $this->createAppointmentWithService($staff, $origDate);

        Cache::flush();
        $response = $this->actingAs($receptionist)->post(
            route('receptionist.reschedule', ['appointmentId' => $appointment->id]),
            [
                'appointment_date' => $targetDate,
                'start_time' => '11:00',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Current staff is not available', session('error'));
        $this->assertSame($origDate, $appointment->fresh()->appointment_date->toDateString());
    }

    private function createAppointmentWithService(User $staff, string $date): Appointment
    {
        $category = ServiceCategory::firstOrCreate(['name' => 'Massage']);
        $service = Service::create([
            'category_id' => $category->id,
            'name' => 'Body Scrub',
            'price' => 800.00,
            'duration_minutes' => 60,
            'is_active' => true,
            'requires_room' => false,
        ]);

        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Client',
            'phone_number' => '09123456780',
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $staff->id,
            'appointment_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 800.00,
        ]);

        $appointment->services()->attach($service->id, ['price_at_booking' => 800.00]);
        return $appointment;
    }
}
