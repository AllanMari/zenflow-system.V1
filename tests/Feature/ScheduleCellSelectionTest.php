<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AttendanceLog;
use App\Models\BusinessException;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AppointmentAvailabilityService;
use App\Services\TherapistAssignmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ScheduleCellSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $receptionist;
    protected User $viewOnly;
    protected User $therapistA;
    protected User $therapistB;
    protected User $therapistC;
    protected string $date;
    protected string $date2;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'customer']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole);

        $this->receptionist = User::factory()->create(['is_active' => true, 'can_manage_schedules' => true]);
        $this->receptionist->roles()->attach($receptionistRole);

        $this->viewOnly = User::factory()->create(['is_active' => true, 'can_manage_schedules' => false]);
        $this->viewOnly->roles()->attach($receptionistRole);

        $this->therapistA = User::factory()->create(['is_active' => true]);
        $this->therapistB = User::factory()->create(['is_active' => true]);
        $this->therapistC = User::factory()->create(['is_active' => true]);
        foreach ([$this->therapistA, $this->therapistB, $this->therapistC] as $staff) {
            $staff->roles()->attach($staffRole);
            for ($day = 0; $day <= 6; $day++) {
                WorkSchedule::create([
                    'user_id' => $staff->id,
                    'day_of_week' => $day,
                    'start_time' => '08:00:00',
                    'end_time' => '17:00:00',
                    'is_day_off' => false,
                ]);
            }
        }

        for ($day = 0; $day <= 6; $day++) {
            BusinessHour::create([
                'day_of_week' => $day,
                'open_time' => '08:00:00',
                'close_time' => '20:00:00',
                'is_closed' => false,
            ]);
        }

        $category = ServiceCategory::create(['name' => 'Cell Selection', 'is_active' => true]);
        $roomCategory = RoomCategory::create(['name' => 'Cell Room', 'code' => 'cell_room']);
        Service::create([
            'name' => 'Cell 60', 'category_id' => $category->id,
            'room_category_id' => $roomCategory->id, 'duration_minutes' => 60,
            'price' => 1000, 'requires_room' => true, 'is_active' => true,
        ]);
        $room = Room::create(['name' => 'Cell Room 1', 'status' => 'available', 'is_active' => true]);
        $room->roomCategories()->attach($roomCategory->id);

        $this->date = Carbon::today('Asia/Manila')->addDay()->toDateString();
        $this->date2 = Carbon::today('Asia/Manila')->addDays(2)->toDateString();
    }

    private function payload(int $userId, string $date, string $type = 'custom_hours', ?string $start = '09:00', ?string $end = '17:00'): array
    {
        return [
            'user_id' => $userId,
            'exception_date' => $date,
            'date' => $date,
            'exception_type' => $type,
            'start_time' => $start,
            'end_time' => $end,
            'reason' => 'cell edit',
        ];
    }

    // ------------------------------------------------------------------ UI / routes

    public function test_existing_schedule_view_exposes_select_control_and_composite_cell_keys(): void
    {
        $page = $this->actingAs($this->admin)->get(route('admin.schedules'));
        $page->assertOk()
            ->assertSee('>Select<', false)
            ->assertSee('Edit Selected', false)
            ->assertSee('openBulkEdit()', false)
            ->assertDontSee('Bulk Shift Assignment', false);

        foreach ([$this->therapistA, $this->therapistB] as $staff) {
            $page->assertSee($staff->id . '|' . $this->date, false);
        }

        $this->actingAs($this->receptionist)->get(route('receptionist.schedules'))->assertOk()->assertSee('>Select<', false);
        $this->actingAs($this->viewOnly)->get(route('receptionist.schedules'))->assertOk()->assertDontSee('>Select<', false);
    }

    public function test_removed_bulk_module_routes_and_page_are_gone(): void
    {
        foreach (['admin.schedules.bulk-create', 'receptionist.schedules.bulk-create',
                  'admin.schedules.bulk-preview', 'receptionist.schedules.bulk-preview',
                  'admin.schedules.bulk-store', 'receptionist.schedules.bulk-store'] as $name) {
            $this->assertFalse(\Illuminate\Support\Facades\Route::has($name));
        }

        $this->actingAs($this->admin)->get('/admin/schedules/bulk-create')->assertNotFound();
        $this->actingAs($this->admin)->post('/admin/schedules/bulk-preview', [])->assertNotFound();
        $this->actingAs($this->admin)->post('/admin/schedules/bulk-store', [])->assertNotFound();
        $this->assertFileDoesNotExist(resource_path('views/shared/schedule-bulk-create.blade.php'));
    }

    public function test_existing_time_picker_uses_thirty_minute_intervals(): void
    {
        $source = file_get_contents(resource_path('views/shared/schedule-management.blade.php'));
        $this->assertStringContainsString('for (let m = 0; m < 60; m += 30)', $source);
        $this->assertStringContainsString('function timePicker(model, property)', $source);
    }

    public function test_permissions_unchanged_for_schedule_endpoints(): void
    {
        $payload = $this->payload($this->therapistA->id, $this->date);

        $this->actingAs($this->admin)->postJson(route('admin.schedule-exception.store'), $payload)->assertOk();
        $this->actingAs($this->receptionist)->postJson(route('receptionist.schedule-exception.store'), $payload)->assertOk();
        $this->actingAs($this->viewOnly)->postJson(route('receptionist.schedule-exception.store'), $payload)->assertForbidden();
        $this->actingAs($this->therapistA)->postJson(route('receptionist.schedule-exception.store'), $payload)->assertForbidden();
    }

    // ------------------------------------------------------------------ single-cell editor

    public function test_existing_single_cell_edit_still_updates_one_cell(): void
    {
        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.store'),
            $this->payload($this->therapistA->id, $this->date)
        )->assertOk();

        $this->assertDatabaseHas('schedule_exceptions', [
            'user_id' => $this->therapistA->id,
            'exception_date' => $this->date . ' 00:00:00',
            'type' => 'custom_hours',
        ]);
        $this->assertEquals(1, ScheduleException::count());
    }

    public function test_existing_date_range_still_applies_across_dates(): void
    {
        $payload = $this->payload($this->therapistA->id, $this->date, 'day_off', null, null);
        $payload['end_date'] = $this->date2;

        $this->actingAs($this->receptionist)->postJson(route('receptionist.schedule-exception.store'), $payload)->assertOk();

        $this->assertEquals(2, ScheduleException::where('user_id', $this->therapistA->id)->count());
    }

    public function test_backend_rejects_non_thirty_minute_intervals(): void
    {
        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.store'),
            $this->payload($this->therapistA->id, $this->date, 'custom_hours', '09:15', '17:15')
        )->assertStatus(422);

        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.bulk-store'),
            ['exceptions' => [$this->payload($this->therapistB->id, $this->date, 'custom_hours', '09:10', '17:00')]]
        )->assertStatus(422);

        $this->assertEquals(0, ScheduleException::count());
    }

    // ------------------------------------------------------------------ multi-cell edit

    public function test_multi_cell_edit_updates_only_selected_therapist_day_combinations(): void
    {
        $exceptions = [
            $this->payload($this->therapistA->id, $this->date),
            $this->payload($this->therapistA->id, $this->date2),
            $this->payload($this->therapistB->id, $this->date),
            $this->payload($this->therapistC->id, $this->date2),
        ];

        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.bulk-store'),
            ['exceptions' => $exceptions]
        )->assertOk();

        $this->assertEquals(4, ScheduleException::count());
        $this->assertEquals(2, ScheduleException::where('user_id', $this->therapistA->id)->count());
        $this->assertEquals(1, ScheduleException::where('user_id', $this->therapistB->id)->count());
        $this->assertEquals(1, ScheduleException::where('user_id', $this->therapistC->id)->count());

        $this->assertEquals(0, ScheduleException::where('user_id', $this->therapistB->id)
            ->whereDate('exception_date', $this->date2)->count());
        $this->assertEquals(0, ScheduleException::where('user_id', $this->therapistC->id)
            ->whereDate('exception_date', $this->date)->count());
    }

    public function test_multi_cell_edit_rolls_back_every_write_on_failure(): void
    {
        $exceptions = [
            $this->payload($this->therapistA->id, $this->date),
            $this->payload($this->therapistB->id, $this->date),
        ];

        Event::listen('eloquent.saving: ' . ScheduleException::class, function ($model) {
            if ((int) $model->user_id === $this->therapistB->id) {
                throw new \RuntimeException('Forced failure');
            }
        });

        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.bulk-store'),
            ['exceptions' => $exceptions]
        )->assertStatus(500);

        $this->assertEquals(0, ScheduleException::count());
    }

    public function test_protected_exceptions_are_not_overwritten_by_multi_cell_edit(): void
    {
        foreach (['day_off', 'holiday', 'sick_leave', 'urgent_leave'] as $type) {
            ScheduleException::create([
                'user_id' => $this->therapistA->id,
                'exception_date' => $this->date,
                'type' => $type,
                'reason' => 'protected',
            ]);

            $this->actingAs($this->receptionist)->postJson(
                route('receptionist.schedule-exception.bulk-store'),
                ['exceptions' => [$this->payload($this->therapistA->id, $this->date)]]
            )->assertStatus(422);

            $this->assertDatabaseHas('schedule_exceptions', [
                'user_id' => $this->therapistA->id,
                'exception_date' => $this->date . ' 00:00:00',
                'type' => $type,
            ]);

            ScheduleException::where('user_id', $this->therapistA->id)->delete();
        }
    }

    public function test_existing_attendance_log_side_effects_are_preserved(): void
    {
        $countBefore = AttendanceLog::count();

        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.bulk-store'),
            ['exceptions' => [$this->payload($this->therapistA->id, $this->date, 'day_off', null, null)]]
        )->assertOk();

        $this->assertGreaterThan($countBefore, AttendanceLog::count());
    }

    public function test_view_tracks_selection_source_and_guards_date_range_to_pure_whole_therapist(): void
    {
        $page = $this->actingAs($this->admin)->get(route('admin.schedules'));
        $page->assertOk()
            ->assertSee('wholeTherapistIds', false)
            ->assertSee('hasManualCellSelection', false)
            ->assertSee('isPureWholeTherapistSelection', false)
            ->assertSee('popover.isBulk && isPureWholeTherapistSelection', false);
    }

    public function test_whole_therapist_date_range_expands_independently_and_skips_past_dates(): void
    {
        $today = Carbon::today('Asia/Manila');
        $pastDate = $today->copy()->subDays(2)->toDateString();
        $futureA = $today->copy()->addDay();
        $futureB = $today->copy()->addDays(2);

        // Therapist A only works on futureA's dayOfWeek; Therapist B only works on futureB's dayOfWeek
        WorkSchedule::where('user_id', $this->therapistA->id)
            ->where('day_of_week', '!=', $futureA->dayOfWeek)
            ->update(['is_day_off' => true]);
        WorkSchedule::where('user_id', $this->therapistB->id)
            ->where('day_of_week', '!=', $futureB->dayOfWeek)
            ->update(['is_day_off' => true]);

        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.bulk-store'),
            [
                'whole_therapist_ids' => [$this->therapistA->id, $this->therapistB->id],
                'date_range' => [
                    'start_date' => $pastDate,
                    'end_date' => $futureB->toDateString(),
                ],
                'exception_type' => 'custom_hours',
                'start_time' => '10:00',
                'end_time' => '16:00',
                'reason' => 'Range test',
            ]
        )->assertOk();

        // Past dates are skipped
        $this->assertEquals(0, ScheduleException::whereDate('exception_date', $pastDate)->count());

        // Therapist A updated only on futureA's weekday, Therapist B only on futureB's weekday
        $this->assertDatabaseHas('schedule_exceptions', [
            'user_id' => $this->therapistA->id,
            'exception_date' => $futureA->toDateString() . ' 00:00:00',
            'type' => 'custom_hours',
        ]);
        $this->assertDatabaseMissing('schedule_exceptions', [
            'user_id' => $this->therapistA->id,
            'exception_date' => $futureB->toDateString() . ' 00:00:00',
        ]);
        $this->assertDatabaseHas('schedule_exceptions', [
            'user_id' => $this->therapistB->id,
            'exception_date' => $futureB->toDateString() . ' 00:00:00',
            'type' => 'custom_hours',
        ]);

        // Recurring WorkSchedule remains unchanged
        $this->assertDatabaseHas('work_schedules', [
            'user_id' => $this->therapistA->id,
            'day_of_week' => $futureA->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_day_off' => false,
        ]);
    }

    public function test_incomplete_date_range_is_rejected_without_writes(): void
    {
        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.bulk-store'),
            [
                'whole_therapist_ids' => [$this->therapistA->id],
                'date_range' => [
                    'start_date' => $this->date,
                    'end_date' => null,
                ],
                'exception_type' => 'day_off',
            ]
        )->assertStatus(422);

        $this->assertEquals(0, ScheduleException::count());
    }

    public function test_whole_therapist_date_range_rejects_entire_batch_on_protected_exception(): void
    {
        ScheduleException::create([
            'user_id' => $this->therapistA->id,
            'exception_date' => $this->date2,
            'type' => 'sick_leave',
            'reason' => 'Sick',
        ]);

        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.bulk-store'),
            [
                'whole_therapist_ids' => [$this->therapistA->id],
                'date_range' => [
                    'start_date' => $this->date,
                    'end_date' => $this->date2,
                ],
                'exception_type' => 'custom_hours',
                'start_time' => '09:00',
                'end_time' => '17:00',
            ]
        )->assertStatus(422);

        // No partial writes for $this->date
        $this->assertDatabaseMissing('schedule_exceptions', [
            'user_id' => $this->therapistA->id,
            'exception_date' => $this->date . ' 00:00:00',
        ]);
    }

    public function test_custom_hours_excluding_existing_appointment_and_preparation_buffer_are_rejected(): void
    {
        $customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'phone_number' => '09171234567']);
        $service = Service::first();
        $room = Room::first();

        // 60-min appointment 16:00–17:00 -> occupied until 17:05 with 5-min preparation buffer
        $appt = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $this->therapistA->id,
            'room_id' => $room->id,
            'appointment_date' => $this->date,
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);
        $appt->services()->attach($service->id, ['service_duration' => 60, 'price_at_booking' => 1000]);

        // 08:00–17:00 excludes the 5-minute preparation buffer (17:00–17:05) -> rejected in both bulk and single-cell
        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.bulk-store'),
            ['exceptions' => [$this->payload($this->therapistA->id, $this->date, 'custom_hours', '08:00', '17:00')]]
        )->assertStatus(422);

        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.store'),
            $this->payload($this->therapistA->id, $this->date, 'custom_hours', '08:00', '17:00')
        )->assertStatus(422);

        // 08:00–17:30 covers 16:00–17:05 -> succeeds
        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.bulk-store'),
            ['exceptions' => [$this->payload($this->therapistA->id, $this->date, 'custom_hours', '08:00', '17:30')]]
        )->assertOk();
    }

    public function test_day_off_rejected_for_primary_and_assisting_therapist_and_rejects_entire_batch(): void
    {
        $customer = Customer::create(['first_name' => 'John', 'last_name' => 'Smith', 'phone_number' => '09179876543']);
        $service = Service::first();
        $room = Room::first();

        $appt = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $this->therapistA->id,
            'room_id' => $room->id,
            'appointment_date' => $this->date2,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);
        $appt->services()->attach($service->id, ['service_duration' => 60, 'price_at_booking' => 1000]);
        $appt->assignedStaff()->sync([$this->therapistA->id, $this->therapistB->id]);

        // Assisting therapist B cannot be marked day_off on $this->date2, and $this->date must not be partially saved
        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.bulk-store'),
            [
                'exceptions' => [
                    $this->payload($this->therapistB->id, $this->date, 'day_off', null, null),
                    $this->payload($this->therapistB->id, $this->date2, 'day_off', null, null),
                ],
            ]
        )->assertStatus(422);

        $this->assertEquals(0, ScheduleException::count());
    }

    public function test_pending_unassigned_booking_does_not_block_therapist_exception(): void
    {
        $customer = Customer::create(['first_name' => 'Pat', 'last_name' => 'Lee', 'phone_number' => '09175551234']);
        $room = Room::first();

        Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => null,
            'room_id' => $room->id,
            'appointment_date' => $this->date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1000,
        ]);

        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.bulk-store'),
            ['exceptions' => [$this->payload($this->therapistA->id, $this->date, 'day_off', null, null)]]
        )->assertOk();
    }

    public function test_non_staff_therapist_id_is_rejected(): void
    {
        $this->actingAs($this->admin)->postJson(
            route('admin.schedule-exception.bulk-store'),
            [
                'whole_therapist_ids' => [$this->receptionist->id],
                'date_range' => ['start_date' => $this->date, 'end_date' => $this->date2],
                'exception_type' => 'day_off',
            ]
        )->assertStatus(422);

        $this->actingAs($this->admin)->postJson(
            route('admin.schedule-exception.bulk-store'),
            ['exceptions' => [$this->payload($this->receptionist->id, $this->date, 'day_off', null, null)]]
        )->assertStatus(422);
    }

    public function test_bulk_store_locks_multiple_therapist_rows_in_ascending_id_order(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->admin)->postJson(
            route('admin.schedule-exception.bulk-store'),
            [
                'exceptions' => [
                    $this->payload($this->therapistC->id, $this->date, 'day_off', null, null),
                    $this->payload($this->therapistA->id, $this->date, 'day_off', null, null),
                ],
            ]
        )->assertOk();

        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $userLockQuery = $queries->first(fn ($q) => str_contains($q, 'from "users"') && str_contains($q, 'order by "id" asc'));
        $this->assertNotNull($userLockQuery, 'Expected users row lock query ordered by id asc.');
    }

    public function test_committed_exception_blocks_assignment_and_committed_assignment_blocks_exception(): void
    {
        $customer = Customer::create(['first_name' => 'Alex', 'last_name' => 'Rivera', 'phone_number' => '09171112233']);
        $service = Service::first();
        $room = Room::first();

        // 1. Committed day_off blocks subsequent TherapistAssignmentService::assignStaff()
        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.store'),
            $this->payload($this->therapistA->id, $this->date, 'day_off', null, null)
        )->assertOk();

        $apptA = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => null,
            'room_id' => $room->id,
            'appointment_date' => $this->date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 1000,
        ]);
        $apptA->services()->attach($service->id, ['service_duration' => 60, 'price_at_booking' => 1000]);

        $this->expectException(\InvalidArgumentException::class);
        TherapistAssignmentService::assignStaff($apptA, $this->therapistA->id);
    }

    public function test_committed_assignment_blocks_single_and_bulk_exception(): void
    {
        $customer = Customer::create(['first_name' => 'Sam', 'last_name' => 'Cruz', 'phone_number' => '09172223344']);
        $service = Service::first();
        $room = Room::first();

        $apptB = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => null,
            'room_id' => $room->id,
            'appointment_date' => $this->date2,
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => 'confirmed',
            'total_price' => 1000,
        ]);
        $apptB->services()->attach($service->id, ['service_duration' => 60, 'price_at_booking' => 1000]);

        TherapistAssignmentService::assignStaff($apptB, $this->therapistB->id);

        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.store'),
            $this->payload($this->therapistB->id, $this->date2, 'day_off', null, null)
        )->assertStatus(422);

        $this->actingAs($this->receptionist)->postJson(
            route('receptionist.schedule-exception.bulk-store'),
            ['exceptions' => [$this->payload($this->therapistB->id, $this->date2, 'day_off', null, null)]]
        )->assertStatus(422);

        $this->assertEquals(0, ScheduleException::count());
    }

    public function test_whole_therapist_range_expands_across_month_boundary_and_beyond_visible_week(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 25, 9, 0, 0, 'Asia/Manila'));

        try {
            // Therapist A works Mondays (1) and Wednesdays (3); Therapist B works Thursdays (4)
            WorkSchedule::where('user_id', $this->therapistA->id)
                ->whereNotIn('day_of_week', [1, 3])
                ->update(['is_day_off' => true]);
            WorkSchedule::where('user_id', $this->therapistB->id)
                ->where('day_of_week', '!=', 4)
                ->update(['is_day_off' => true]);

            // Range spans Nov 23 (past) through Dec 3 (across month boundary and next week)
            $this->actingAs($this->admin)->postJson(
                route('admin.schedule-exception.bulk-store'),
                [
                    'whole_therapist_ids' => [$this->therapistA->id, $this->therapistB->id],
                    'date_range' => [
                        'start_date' => '2026-11-23',
                        'end_date' => '2026-12-03',
                    ],
                    'exception_type' => 'custom_hours',
                    'start_time' => '10:00',
                    'end_time' => '16:00',
                    'reason' => 'Month boundary test',
                ]
            )->assertOk();

            // Nov 23 (past Monday) skipped; Nov 25 (Wed), Nov 30 (Mon), Dec 2 (Wed) updated for Therapist A
            $this->assertDatabaseMissing('schedule_exceptions', [
                'user_id' => $this->therapistA->id,
                'exception_date' => '2026-11-23 00:00:00',
            ]);
            foreach (['2026-11-25', '2026-11-30', '2026-12-02'] as $expectedDate) {
                $this->assertDatabaseHas('schedule_exceptions', [
                    'user_id' => $this->therapistA->id,
                    'exception_date' => $expectedDate . ' 00:00:00',
                    'type' => 'custom_hours',
                ]);
            }

            // Nov 26 (Thu) and Dec 3 (Thu) updated for Therapist B
            foreach (['2026-11-26', '2026-12-03'] as $expectedDate) {
                $this->assertDatabaseHas('schedule_exceptions', [
                    'user_id' => $this->therapistB->id,
                    'exception_date' => $expectedDate . ' 00:00:00',
                    'type' => 'custom_hours',
                ]);
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_genuinely_empty_applicable_range_is_rejected_and_view_has_no_max_date_limit(): void
    {
        // Therapist C has all days off in WorkSchedule
        WorkSchedule::where('user_id', $this->therapistC->id)->update(['is_day_off' => true]);

        $this->actingAs($this->admin)->postJson(
            route('admin.schedule-exception.bulk-store'),
            [
                'whole_therapist_ids' => [$this->therapistC->id],
                'date_range' => [
                    'start_date' => $this->date,
                    'end_date' => $this->date2,
                ],
                'exception_type' => 'day_off',
            ]
        )->assertStatus(422);

        $this->actingAs($this->admin)->get(route('admin.schedules'))
            ->assertOk()
            ->assertSee('maxDate: null', false);
    }

    public function test_zero_work_schedule_therapist_receives_custom_hours_across_range_and_mixed_selection(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 25, 9, 0, 0, 'Asia/Manila'));

        try {
            $staffRole = Role::where('name', 'staff')->first();

            // Realistic therapist with ZERO WorkSchedule rows (like staff id 3)
            $zeroScheduleTherapist = User::factory()->create(['is_active' => true]);
            $zeroScheduleTherapist->roles()->attach($staffRole);

            // Therapist A works ONLY Wednesdays (dow 3: Nov 25, Dec 2)
            WorkSchedule::where('user_id', $this->therapistA->id)
                ->where('day_of_week', '!=', 3)
                ->update(['is_day_off' => true]);

            // Therapist B has existing rows all marked is_day_off = true -> must NOT get fallback
            WorkSchedule::where('user_id', $this->therapistB->id)
                ->update(['is_day_off' => true]);

            $this->actingAs($this->admin)->postJson(
                route('admin.schedule-exception.bulk-store'),
                [
                    'whole_therapist_ids' => [
                        $zeroScheduleTherapist->id,
                        $this->therapistA->id,
                        $this->therapistB->id,
                    ],
                    'date_range' => [
                        'start_date' => '2026-11-23', // past dates Nov 23, 24 must be skipped
                        'end_date' => '2026-12-03',
                    ],
                    'exception_type' => 'custom_hours',
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                    'reason' => 'Zero-schedule fallback test',
                ]
            )->assertOk();

            // Past dates skipped
            $this->assertDatabaseMissing('schedule_exceptions', [
                'user_id' => $zeroScheduleTherapist->id,
                'exception_date' => '2026-11-23 00:00:00',
            ]);
            $this->assertDatabaseMissing('schedule_exceptions', [
                'user_id' => $zeroScheduleTherapist->id,
                'exception_date' => '2026-11-24 00:00:00',
            ]);

            // Zero-schedule therapist receives custom_hours for all 9 dates Nov 25..Dec 3
            $expectedDates = [
                '2026-11-25', '2026-11-26', '2026-11-27', '2026-11-28', '2026-11-29',
                '2026-11-30', '2026-12-01', '2026-12-02', '2026-12-03',
            ];
            foreach ($expectedDates as $d) {
                $this->assertDatabaseHas('schedule_exceptions', [
                    'user_id' => $zeroScheduleTherapist->id,
                    'exception_date' => $d . ' 00:00:00',
                    'type' => 'custom_hours',
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                ]);
            }

            // Therapist A only receives Nov 25 and Dec 2 (Wednesdays)
            $this->assertEquals(2, ScheduleException::where('user_id', $this->therapistA->id)->count());
            $this->assertDatabaseHas('schedule_exceptions', [
                'user_id' => $this->therapistA->id,
                'exception_date' => '2026-11-25 00:00:00',
            ]);
            $this->assertDatabaseHas('schedule_exceptions', [
                'user_id' => $this->therapistA->id,
                'exception_date' => '2026-12-02 00:00:00',
            ]);

            // Therapist B (all days off) receives ZERO exceptions
            $this->assertEquals(0, ScheduleException::where('user_id', $this->therapistB->id)->count());

            // Zero-schedule therapist still has ZERO WorkSchedule rows (no recurring rows created)
            $this->assertEquals(0, WorkSchedule::where('user_id', $zeroScheduleTherapist->id)->count());

            // Non-custom_hours (e.g., day_off) on zero-schedule therapist does NOT trigger fallback
            $zeroScheduleTherapist2 = User::factory()->create(['is_active' => true]);
            $zeroScheduleTherapist2->roles()->attach($staffRole);

            $this->actingAs($this->admin)->postJson(
                route('admin.schedule-exception.bulk-store'),
                [
                    'whole_therapist_ids' => [$zeroScheduleTherapist2->id],
                    'date_range' => [
                        'start_date' => '2026-11-25',
                        'end_date' => '2026-12-03',
                    ],
                    'exception_type' => 'day_off',
                ]
            )->assertStatus(422);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_zero_work_schedule_fallback_respects_spa_closed_days_holidays_and_open_exceptions(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 25, 9, 0, 0, 'Asia/Manila'));

        try {
            $staffRole = Role::where('name', 'staff')->first();
            $zeroScheduleTherapist = User::factory()->create(['is_active' => true]);
            $zeroScheduleTherapist->roles()->attach($staffRole);

            // Configure Sunday (dow 0: 2026-11-29) as regularly closed
            BusinessHour::where('day_of_week', 0)->update(['is_closed' => true]);

            // Configure 2026-11-30 (Monday) as an explicit holiday closure
            BusinessException::create([
                'date' => '2026-11-30',
                'title' => 'Bonifacio Day',
                'type' => 'holiday',
                'is_closed' => true,
            ]);

            // Configure 2026-12-01 (Tuesday) as an explicit special-event open exception
            BusinessException::create([
                'date' => '2026-12-01',
                'title' => 'Special Open Day',
                'type' => 'special_hours',
                'is_closed' => false,
                'open_time' => '09:00:00',
                'close_time' => '18:00:00',
            ]);

            $this->actingAs($this->admin)->postJson(
                route('admin.schedule-exception.bulk-store'),
                [
                    'whole_therapist_ids' => [$zeroScheduleTherapist->id],
                    'date_range' => [
                        'start_date' => '2026-11-25',
                        'end_date' => '2026-12-03',
                    ],
                    'exception_type' => 'custom_hours',
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                ]
            )->assertOk();

            // Open dates generated: Nov 25, 26, 27, 28, Dec 1 (open exception), Dec 2, Dec 3
            foreach (['2026-11-25', '2026-11-26', '2026-11-27', '2026-11-28', '2026-12-01', '2026-12-02', '2026-12-03'] as $openDate) {
                $this->assertDatabaseHas('schedule_exceptions', [
                    'user_id' => $zeroScheduleTherapist->id,
                    'exception_date' => $openDate . ' 00:00:00',
                    'type' => 'custom_hours',
                ]);
            }

            // Closed dates skipped: Nov 29 (Sunday closed) and Nov 30 (holiday closed)
            foreach (['2026-11-29', '2026-11-30'] as $closedDate) {
                $this->assertDatabaseMissing('schedule_exceptions', [
                    'user_id' => $zeroScheduleTherapist->id,
                    'exception_date' => $closedDate . ' 00:00:00',
                ]);
            }

            $this->assertEquals(0, WorkSchedule::where('user_id', $zeroScheduleTherapist->id)->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_initial_date_sunday_saturday_header_and_week_day_switching(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 25, 9, 0, 0, 'Asia/Manila'));

        try {
            // Week view initializes to Nov 22 – Nov 28, 2026 with Today link on Sunday Nov 22
            $this->actingAs($this->admin)->get(route('admin.schedules'))
                ->assertOk()
                ->assertSee('Nov 22 – Nov 28, 2026', false)
                ->assertDontSee('Nov 22 – Nov 22, 2026', false)
                ->assertSee('?week_start=2026-11-22&view=week', false)
                ->assertSee('?date=2026-11-25&view=day', false);

            // Day view initializes to Wednesday, November 25, 2026
            $this->actingAs($this->admin)->get(route('admin.schedules', ['view' => 'day']))
                ->assertOk()
                ->assertSee('Wednesday, November 25, 2026', false);

            // Navigating to a specific date preserves it when switching Day <-> Week
            $this->actingAs($this->admin)->get(route('admin.schedules', ['view' => 'day', 'date' => '2026-11-27']))
                ->assertOk()
                ->assertSee('Friday, November 27, 2026', false)
                ->assertSee('?week_start=2026-11-22&date=2026-11-27&view=week', false);

            // Prev / Next week navigation remains Sunday-based; Today week_start keeps Day link on Nov 25
            $this->actingAs($this->admin)->get(route('admin.schedules', ['week_start' => '2026-11-22', 'view' => 'week']))
                ->assertOk()
                ->assertSee('?week_start=2026-11-15&view=week', false)
                ->assertSee('?week_start=2026-11-29&view=week', false)
                ->assertSee('?date=2026-11-25&view=day', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_business_hour_closure_and_exceptions_override_therapist_shift_and_invalidate_cache(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 25, 9, 0, 0, 'Asia/Manila'));

        try {
            // Saturday Nov 28, 2026: Therapist A has WorkSchedule working, BusinessHour closes Saturday
            BusinessHour::where('day_of_week', 6)->update(['is_closed' => true]);

            // Week view & Day view both show Spa Closed on Saturday, while WorkSchedule remains intact
            $this->actingAs($this->admin)->get(route('admin.schedules'))
                ->assertOk()
                ->assertSee('Spa Closed');

            $this->actingAs($this->admin)->get(route('admin.schedules', ['view' => 'day', 'date' => '2026-11-28']))
                ->assertOk()
                ->assertSee('Spa Closed');

            $this->assertDatabaseHas('work_schedules', [
                'user_id' => $this->therapistA->id,
                'day_of_week' => 6,
                'is_day_off' => false,
            ]);

            // Appointment availability rejects closed Saturday
            $service = Service::first();
            $slots = app(AppointmentAvailabilityService::class)->availableTimes([
                'date' => '2026-11-28',
                'services' => collect([$service]),
                'staff_ids' => [$this->therapistA->id],
            ]);
            $this->assertEmpty($slots['slots'] ?? []);

            // Special opening BusinessException reopens Saturday Nov 28
            $specialOpen = BusinessException::create([
                'date' => '2026-11-28',
                'title' => 'Weekend Promo',
                'type' => 'special_hours',
                'is_closed' => false,
                'open_time' => '09:00:00',
                'close_time' => '18:00:00',
            ]);

            $this->actingAs($this->admin)->get(route('admin.schedules', ['view' => 'day', 'date' => '2026-11-28']))
                ->assertOk()
                ->assertDontSee('Spa Closed');

            $specialOpen->delete();

            // Special closure BusinessException closes an otherwise open Thursday Nov 26
            BusinessException::create([
                'date' => '2026-11-26',
                'title' => 'Maintenance Day',
                'type' => 'holiday',
                'is_closed' => true,
            ]);

            $this->actingAs($this->admin)->get(route('admin.schedules', ['view' => 'day', 'date' => '2026-11-26']))
                ->assertOk()
                ->assertSee('Spa Closed')
                ->assertSee('Maintenance Day');

            // BusinessScheduleController mutations increment avail:version cache
            Cache::put('avail:version', 10, now()->addDay());
            $hoursPayload = [];
            for ($d = 0; $d <= 6; $d++) {
                $hoursPayload[$d] = ['open_time' => '09:00', 'close_time' => '20:00', 'is_closed' => $d === 6 ? '1' : '0'];
            }
            $this->actingAs($this->admin)->post(route('business-hours.update'), ['hours' => $hoursPayload])->assertRedirect();
            $this->assertGreaterThan(10, Cache::get('avail:version'));

            $vAfterHours = Cache::get('avail:version');
            $this->actingAs($this->admin)->post(route('business-exceptions.store'), [
                'date' => '2026-12-04',
                'title' => 'Holiday',
                'type' => 'holiday',
                'is_closed' => '1',
            ])->assertRedirect();
            $this->assertGreaterThan($vAfterHours, Cache::get('avail:version'));

            $exc = BusinessException::whereDate('date', '2026-12-04')->first();
            $vAfterStore = Cache::get('avail:version');
            $this->actingAs($this->admin)->delete(route('business-exceptions.destroy', $exc))->assertRedirect();
            $this->assertGreaterThan($vAfterStore, Cache::get('avail:version'));
        } finally {
            Carbon::setTestNow();
        }
    }
}
