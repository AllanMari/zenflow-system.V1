<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\ScheduleException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleExceptionAttendanceLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_exception_update_logs_old_and_new_status()
    {
        $staff = User::factory()->create();

        $exception = ScheduleException::create([
            'user_id' => $staff->id,
            'exception_date' => now()->toDateString(),
            'type' => 'day_off',
            'reason' => 'Day Off',
        ]);

        $exception->update([
            'type' => 'sick_leave',
            'reason' => 'Sick Leave Update',
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $staff->id,
            'schedule_exception_id' => $exception->id,
            'old_status' => 'Day off',
            'new_status' => 'Sick leave',
            'reason' => 'Sick Leave Update',
            'change_type' => 'Leave Updated',
        ]);
    }

    public function test_leave_add_and_remove_creates_audit_log_with_correct_action()
    {
        $admin = User::factory()->create();
        $staff = User::factory()->create();

        $this->actingAs($admin);

        $exception = ScheduleException::create([
            'user_id' => $staff->id,
            'exception_date' => now()->toDateString(),
            'type' => 'sick_leave',
            'reason' => 'Feeling sick',
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $staff->id,
            'schedule_exception_id' => $exception->id,
            'change_type' => 'Leave Added',
            'new_status' => 'Sick leave',
        ]);

        $exception->delete();

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $staff->id,
            'schedule_exception_id' => null,
            'change_type' => 'Leave Removed',
        ]);
    }

    public function test_attendance_check_in_and_correction_audit_logs()
    {
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $staffRole = \App\Models\Role::firstOrCreate(['name' => 'staff']);
        $staff = User::factory()->create();
        $staff->roles()->attach($staffRole);

        $dayOfWeek = now()->dayOfWeek;
        \App\Models\WorkSchedule::create([
            'user_id' => $staff->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'is_off' => false,
        ]);

        $this->actingAs($admin);

        $response = $this->postJson(route('attendance.quick-checkin', $staff));
        $response->assertSuccessful();

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $staff->id,
            'change_type' => 'Check In',
        ]);

        $correctionResponse = $this->postJson(route('attendance.correct', $staff), [
            'type' => 'check_in',
            'time' => '08:30',
            'reason' => 'Manual override by admin',
        ]);
        $correctionResponse->assertSuccessful();

        $this->assertDatabaseHas('attendance_logs', [
            'user_id' => $staff->id,
            'change_type' => 'Attendance Correction',
            'reason' => 'Manual override by admin',
        ]);
    }
}
