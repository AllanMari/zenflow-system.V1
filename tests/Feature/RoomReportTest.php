<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BusinessException;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Services\RoomUtilizationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RoomReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin = User::firstOrCreate(
            ['username' => 'test_admin_room_report'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Test',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        $this->admin = $admin;
    }

    public function test_admin_can_generate_room_report_pdf_preview()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.rooms.report-pdf') . '?action=stream');

        $response->assertStatus(200);
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'inline',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_admin_can_generate_room_report_pdf_download()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.rooms.report-pdf') . '?action=download');

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_receptionist_cannot_access_room_report_pdf()
    {
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist'], ['label' => 'Receptionist']);
        $receptionist = User::firstOrCreate(
            ['username' => 'test_rec_room_report'],
            [
                'first_name' => 'Rec',
                'last_name' => 'Test',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $receptionist->roles()->syncWithoutDetaching([$receptionistRole->id]);

        $this->actingAs($receptionist)
            ->get(route('admin.rooms.report-pdf') . '?action=stream')
            ->assertStatus(403);

        $this->actingAs($receptionist)
            ->get(route('admin.rooms.report-pdf') . '?action=download')
            ->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login_for_room_report_pdf()
    {
        $this->get(route('admin.rooms.report-pdf') . '?action=stream')
            ->assertRedirect(route('login'));
    }

    public function test_admin_rooms_index_stays_focused_on_management_and_room_tracking_contains_utilization_and_report_actions()
    {
        $roomsResponse = $this->actingAs($this->admin)->get(route('admin.rooms.index'));
        $roomsResponse->assertStatus(200);
        $roomsResponse->assertSee('Add Room');
        $roomsResponse->assertDontSee('Room &amp; Resource Utilization', false);
        $roomsResponse->assertDontSee('Preview PDF');
        $roomsResponse->assertDontSee('Export CSV');

        $trackingResponse = $this->actingAs($this->admin)->get(route('admin.room-tracking'));
        $trackingResponse->assertStatus(200);
        $trackingResponse->assertSee('Room &amp; Resource Utilization', false);
        $trackingResponse->assertSee('Completed Occupancy');
        $trackingResponse->assertSee('Scheduled Occupancy');
        $trackingResponse->assertSee('5-Min Prep Buffer');
        $trackingResponse->assertSee('Overall Utilization');
        $trackingResponse->assertSee('room-utilization-date-range', false);
        $trackingResponse->assertSee('flatpickr', false);
        $trackingResponse->assertSee('Preview PDF');
        $trackingResponse->assertSee('Download PDF');
        $trackingResponse->assertSee('Export CSV');
        $trackingResponse->assertSee('Export XLSX');
    }

    public function test_room_report_generation_does_not_modify_room_records()
    {
        $room = Room::create([
            'name' => 'Suite 1',
            'status' => 'available',
            'is_active' => true,
            'notes' => 'Needs deep cleaning before next session',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.rooms.report-pdf') . '?action=stream');

        $response->assertStatus(200);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'name' => 'Suite 1',
            'status' => 'available',
            'is_active' => true,
        ]);
        $this->assertEquals(1, Room::count());
    }

    private function createCustomer(): Customer
    {
        return Customer::create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'phone_number' => '09171234567',
            'customer_type' => 'regular',
        ]);
    }

    public function test_room_utilization_calculates_occupancy_excludes_cancelled_no_show_and_separates_buffer(): void
    {
        $date = Carbon::today('Asia/Manila');
        $dateStr = $date->toDateString();

        // Explicit 10-hour operating window (09:00 - 19:00 = 600 min) for this day
        BusinessHour::create([
            'day_of_week' => $date->dayOfWeek,
            'open_time' => '09:00:00',
            'close_time' => '19:00:00',
            'is_closed' => false,
        ]);

        $roomA = Room::create(['name' => 'Room Alpha', 'status' => 'available', 'is_active' => true]);
        $roomB = Room::create(['name' => 'Room Beta', 'status' => 'available', 'is_active' => true]);
        $customer = $this->createCustomer();

        // 1. Completed (60m)
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $roomA->id,
            'appointment_date' => $dateStr,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 1000.00,
        ]);

        // 2. Confirmed (90m)
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $roomA->id,
            'appointment_date' => $dateStr,
            'start_time' => '12:00:00',
            'end_time' => '13:30:00',
            'status' => 'confirmed',
            'total_price' => 1500.00,
        ]);

        // 3. Valid Pending (30m) — created now so created_at + 24h is in the future
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $roomA->id,
            'appointment_date' => $dateStr,
            'start_time' => '14:00:00',
            'end_time' => '14:30:00',
            'status' => 'pending',
            'total_price' => 500.00,
        ]);

        // 4. Cancelled (excluded from occupancy)
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $roomA->id,
            'appointment_date' => $dateStr,
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => 'cancelled',
            'total_price' => 1000.00,
        ]);

        // 5. No-show (excluded from occupancy)
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $roomA->id,
            'appointment_date' => $dateStr,
            'start_time' => '16:30:00',
            'end_time' => '17:30:00',
            'status' => 'no_show',
            'total_price' => 1000.00,
        ]);

        $req = Request::create('/rooms', 'GET', [
            'period' => 'custom',
            'start_date' => $dateStr,
            'end_date' => $dateStr,
        ]);

        $report = app(RoomUtilizationService::class)->buildReport($req);
        $rows = $report['roomRows']->keyBy('room_id');

        $alpha = $rows[$roomA->id];
        $beta = $rows[$roomB->id];

        // Room Alpha: 60m completed, 90m confirmed, 30m pending => 180m scheduled (buffer 3 * 5m = 15m separate)
        $this->assertSame(1, $alpha['completed_count']);
        $this->assertSame(1, $alpha['confirmed_count']);
        $this->assertSame(1, $alpha['pending_count']);
        $this->assertSame(2, $alpha['cancelled_no_show_count']);
        $this->assertSame(60, $alpha['completed_minutes']);
        $this->assertSame(180, $alpha['scheduled_occupancy_minutes']);
        $this->assertSame(3, $alpha['buffer_gaps_count']);
        $this->assertSame(15, $alpha['buffer_minutes']);
        $this->assertSame(600, $alpha['available_minutes']);
        $this->assertSame(30.0, $alpha['utilization_percent']); // 180 / 600 * 100
        $this->assertSame(10.0, $alpha['fulfilled_utilization_percent']); // 60 / 600 * 100

        // Room Beta: empty room still has 600 available minutes and 0.0% utilization
        $this->assertSame(0, $beta['scheduled_occupancy_minutes']);
        $this->assertSame(0, $beta['buffer_minutes']);
        $this->assertSame(600, $beta['available_minutes']);
        $this->assertSame(0.0, $beta['utilization_percent']);
    }

    public function test_maintenance_inactive_rooms_and_business_closures_affect_denominator(): void
    {
        $day1 = Carbon::today('Asia/Manila');
        $day2 = $day1->copy()->addDay();

        // Both days open 09:00-17:00 (480m/day), but day2 has a closed BusinessException
        BusinessHour::create(['day_of_week' => $day1->dayOfWeek, 'open_time' => '09:00:00', 'close_time' => '17:00:00', 'is_closed' => false]);
        BusinessHour::create(['day_of_week' => $day2->dayOfWeek, 'open_time' => '09:00:00', 'close_time' => '17:00:00', 'is_closed' => false]);
        BusinessException::create(['date' => $day2->toDateString(), 'title' => 'Holiday', 'type' => 'holiday', 'is_closed' => true]);

        $opRoom = Room::create(['name' => 'Open Room', 'status' => 'available', 'is_active' => true]);
        $maintRoom = Room::create(['name' => 'Maint Room', 'status' => 'maintenance', 'is_active' => true]);
        $inactRoom = Room::create(['name' => 'Inactive Room', 'status' => 'available', 'is_active' => false]);

        $req = Request::create('/rooms', 'GET', [
            'period' => 'custom',
            'start_date' => $day1->toDateString(),
            'end_date' => $day2->toDateString(),
        ]);

        $report = app(RoomUtilizationService::class)->buildReport($req);
        $rows = $report['roomRows']->keyBy('room_id');

        $this->assertSame(1, $report['summary']['open_days_count']);
        $this->assertSame(480, $rows[$opRoom->id]['available_minutes']);
        $this->assertSame(0, $rows[$maintRoom->id]['available_minutes']);
        $this->assertNull($rows[$maintRoom->id]['utilization_percent']);
        $this->assertSame(0, $rows[$inactRoom->id]['available_minutes']);
        $this->assertNull($rows[$inactRoom->id]['utilization_percent']);
    }

    public function test_room_csv_xlsx_and_pdf_exports_match_date_filter_and_restrict_non_admins(): void
    {
        $date = Carbon::today('Asia/Manila');
        $otherDate = $date->copy()->subDays(10);
        BusinessHour::create(['day_of_week' => $date->dayOfWeek, 'open_time' => '09:00:00', 'close_time' => '19:00:00', 'is_closed' => false]);

        $room = Room::create(['name' => 'Lotus Suite', 'status' => 'available', 'is_active' => true]);
        $customer = $this->createCustomer();

        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => $date->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 1000.00,
        ]);

        // Outside selected range (must be excluded)
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => $otherDate->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'completed',
            'total_price' => 2000.00,
        ]);

        $params = [
            'period' => 'custom',
            'start_date' => $date->toDateString(),
            'end_date' => $date->toDateString(),
        ];

        $trackingRes = $this->actingAs($this->admin)->get(route('admin.room-tracking', $params));
        $trackingRes->assertOk()->assertSee('Lotus Suite')->assertSee('1h 0m')->assertSee('10.0%');

        $csvRes = $this->actingAs($this->admin)->get(route('admin.rooms.report-csv', $params));
        $csvRes->assertOk();
        $csvContent = $csvRes->streamedContent();
        $this->assertStringContainsString('Lotus Suite', $csvContent);
        $this->assertStringContainsString('10.0%', $csvContent);
        $this->assertStringContainsString('60', $csvContent);

        $xlsxRes = $this->actingAs($this->admin)->get(route('admin.rooms.report-xlsx', $params));
        $xlsxRes->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $xlsxRes->headers->get('Content-Type'));

        $pdfRes = $this->actingAs($this->admin)->get(route('admin.rooms.report-pdf', array_merge($params, ['action' => 'download'])));
        $pdfRes->assertOk();
        $this->assertStringStartsWith('%PDF-', (string) $pdfRes->getContent());

        // Non-admin check
        $recRole = Role::firstOrCreate(['name' => 'receptionist'], ['label' => 'Receptionist']);
        $rec = User::factory()->create(['is_active' => true]);
        $rec->roles()->attach($recRole);

        $this->actingAs($rec)->get(route('admin.rooms.report-csv', $params))->assertForbidden();
        $this->actingAs($rec)->get(route('admin.rooms.report-xlsx', $params))->assertForbidden();
    }

    public function test_expired_pending_and_legacy_customer_no_show_are_excluded_using_authoritative_rules(): void
    {
        $today = Carbon::today('Asia/Manila');
        $todayStr = $today->toDateString();

        BusinessHour::create([
            'day_of_week' => $today->dayOfWeek,
            'open_time' => '09:00:00',
            'close_time' => '17:00:00',
            'is_closed' => false,
        ]);

        $room = Room::create(['name' => 'Orchid Room', 'status' => 'available', 'is_active' => true]);
        $customer = $this->createCustomer();

        // Expired pending (created 25 hours ago -> exceeds 24h rule)
        $expiredPending = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => $todayStr,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
            'total_price' => 800.00,
        ]);
        Appointment::where('id', $expiredPending->id)->update([
            'created_at' => Carbon::now(config('app.timezone', 'UTC'))->subHours(25),
        ]);

        // Legacy customer no-show (status=cancelled + cancellation_reason=customer_no_show)
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => $todayStr,
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => 'cancelled',
            'cancellation_reason' => 'customer_no_show',
            'total_price' => 800.00,
        ]);

        $report = app(RoomUtilizationService::class)->buildReport(Request::create('/rooms', 'GET', [
            'period' => 'custom',
            'start_date' => $todayStr,
            'end_date' => $todayStr,
        ]));

        $row = $report['roomRows']->firstWhere('room_id', $room->id);
        $this->assertSame(0, $row['pending_count']);
        $this->assertSame(0, $row['scheduled_occupancy_minutes']);
        $this->assertSame(0, $row['buffer_minutes']);
        $this->assertSame(1, $row['cancelled_no_show_count']);
    }

    public function test_overlapping_and_zero_length_appointments_and_overall_utilization_denominator_alignment(): void
    {
        $today = Carbon::today('Asia/Manila');
        $todayStr = $today->toDateString();

        // 09:00-17:00 = 480m
        BusinessHour::create([
            'day_of_week' => $today->dayOfWeek,
            'open_time' => '09:00:00',
            'close_time' => '17:00:00',
            'is_closed' => false,
        ]);

        $opRoom = Room::create(['name' => 'Active Suite', 'status' => 'available', 'is_active' => true]);
        $maintRoom = Room::create(['name' => 'Maint Suite', 'status' => 'maintenance', 'is_active' => true]);
        $customer = $this->createCustomer();

        // Two overlapping appointments on Active Suite: 10:00-11:00 and 10:30-11:30 -> merged = 90m (not 120m)
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $opRoom->id,
            'appointment_date' => $todayStr,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 1000.00,
        ]);
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $opRoom->id,
            'appointment_date' => $todayStr,
            'start_time' => '10:30:00',
            'end_time' => '11:30:00',
            'status' => 'completed',
            'total_price' => 1000.00,
        ]);

        // Zero-length appointment (00:00-00:00) must yield 0m, not 1440m
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $opRoom->id,
            'appointment_date' => $todayStr,
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
            'status' => 'completed',
            'total_price' => 0.00,
        ]);

        // Historical appointment on currently-maintenance room (240m) must NOT inflate overall_utilization
        Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $maintRoom->id,
            'appointment_date' => $todayStr,
            'start_time' => '12:00:00',
            'end_time' => '16:00:00',
            'status' => 'completed',
            'total_price' => 2000.00,
        ]);

        $report = app(RoomUtilizationService::class)->buildReport(Request::create('/rooms', 'GET', [
            'period' => 'custom',
            'start_date' => $todayStr,
            'end_date' => $todayStr,
        ]));

        $rows = $report['roomRows']->keyBy('room_id');
        $this->assertSame(90, $rows[$opRoom->id]['scheduled_occupancy_minutes']);
        $this->assertSame(18.8, $rows[$opRoom->id]['utilization_percent']); // 90 / 480 * 100 = 18.75 -> 18.8
        $this->assertNull($rows[$maintRoom->id]['utilization_percent']);
        // Overall utilization uses operational numerator (90) / operational denominator (480) = 18.8%, NOT (90+240)/480 = 68.8%
        $this->assertSame(18.8, $report['summary']['overall_utilization']);
        $this->assertSame(330, $report['summary']['scheduled_occupancy_minutes']);
    }

    public function test_empty_reporting_period_and_custom_business_exception_hours(): void
    {
        $date = Carbon::today('Asia/Manila');
        $dateStr = $date->toDateString();

        // Custom exception shortens operating window to 10:00-14:00 (240m)
        BusinessHour::create([
            'day_of_week' => $date->dayOfWeek,
            'open_time' => '09:00:00',
            'close_time' => '19:00:00',
            'is_closed' => false,
        ]);
        BusinessException::create([
            'date' => $dateStr,
            'title' => 'Half Day',
            'type' => 'custom_hours',
            'is_closed' => false,
            'open_time' => '10:00:00',
            'close_time' => '14:00:00',
        ]);

        $room = Room::create(['name' => 'Zen Room', 'status' => 'available', 'is_active' => true]);

        $report = app(RoomUtilizationService::class)->buildReport(Request::create('/rooms', 'GET', [
            'period' => 'custom',
            'start_date' => $dateStr,
            'end_date' => $dateStr,
        ]));

        $row = $report['roomRows']->firstWhere('room_id', $room->id);
        $this->assertSame(240, $row['available_minutes']);
        $this->assertSame(0, $row['scheduled_occupancy_minutes']);
        $this->assertSame(0.0, $row['utilization_percent']);
        $this->assertNull($report['bottlenecks']['peak_day']);
    }
}
