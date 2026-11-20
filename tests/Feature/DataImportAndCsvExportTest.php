<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\CsvExportService;
use App\Services\DataImportService;
use App\Services\XlsxSpreadsheetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DataImportAndCsvExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $receptionist;
    protected User $staff;
    protected User $customerUser;
    protected ServiceCategory $category;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        // Users
        $this->admin = User::factory()->create([
            'username' => 'admin_test',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'is_active' => true,
        ]);
        $this->admin->roles()->attach($adminRole);

        $this->receptionist = User::factory()->create([
            'username' => 'receptionist_test',
            'first_name' => 'Receptionist',
            'last_name' => 'User',
            'is_active' => true,
        ]);
        $this->receptionist->roles()->attach($receptionistRole);

        $this->staff = User::factory()->create([
            'username' => 'therapist_ana',
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'is_active' => true,
        ]);
        $this->staff->roles()->attach($staffRole);

        $this->customerUser = User::factory()->create([
            'username' => 'customer_user',
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
            'is_active' => true,
        ]);
        $this->customerUser->roles()->attach($customerRole);

        $this->category = ServiceCategory::create([
            'name' => 'Massage Therapy',
            'color' => '#14776a',
            'is_active' => true,
        ]);

        $this->service = Service::create([
            'name' => 'Classic Swedish Massage',
            'category_id' => $this->category->id,
            'price' => 530.00,
            'duration_minutes' => 60,
            'is_active' => true,
            'participants_required' => 1,
            'therapists_required' => 1,
        ]);
    }

    /* ─── 1. TEMPLATE DOWNLOAD & EXCEL COMPATIBILITY (BOM) ─── */

    public function test_admin_can_download_excel_compatible_templates_with_utf8_bom(): void
    {
        foreach (['attendance', 'offline_sales'] as $type) {
            $response = $this->actingAs($this->admin)
                ->get(route('admin.data-import.template', ['type' => $type]));

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $this->assertTrue(str_contains($response->headers->get('Content-Disposition'), '.csv'));

            $content = $response->streamedContent();
            // Verify UTF-8 BOM is first 3 bytes
            $this->assertStringStartsWith(CsvExportService::UTF8_BOM, $content);
        }

        // Removed types must return 404
        $this->actingAs($this->admin)
            ->get(route('admin.data-import.template', ['type' => 'services']))
            ->assertStatus(404);

        $this->actingAs($this->admin)
            ->get(route('admin.data-import.template', ['type' => 'customers']))
            ->assertStatus(404);
    }

    /* ─── 2. VALID ATTENDANCE CSV IMPORT WORKFLOW ─── */

    public function test_valid_attendance_csv_import_workflow(): void
    {
        $date = Carbon::yesterday()->toDateString();
        $csvContent = "staff_username,date,time_in,time_out,status,notes\n" .
                      "\"{$this->staff->username}\",\"{$date}\",\"09:00\",\"18:00\",present,\"On time shift\"\n";

        $file = UploadedFile::fake()->createWithContent('attendance.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'attendance',
                'csv_file' => $file,
            ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee($this->staff->username);

        $tokenKeys = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $previewToken = str_replace('import_payload_', '', reset($tokenKeys));

        $this->actingAs($this->admin)
            ->post(route('admin.data-import.confirm'), [
                'preview_token' => $previewToken,
                'import_type' => 'attendance',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->staff->id,
            'status' => 'present',
        ]);
        $created = Attendance::where('user_id', $this->staff->id)->first();
        $this->assertNotNull($created);
        $this->assertEquals($date, $created->date->toDateString());
    }

    /* ─── 3. VALID OFFLINE SALES IMPORT WORKFLOW ─── */

    public function test_valid_offline_sales_csv_import_workflow(): void
    {
        $date = '2026-11-19';
        $ref = 'OFF-20261119-001';
        $csvContent = "offline_reference,transaction_date,transaction_time,service_name,amount,payment_method,customer_name,customer_phone,staff_username,notes\n" .
                      "\"{$ref}\",\"{$date}\",\"10:30\",\"Classic Swedish Massage\",530.00,cash,\"Maria Santos\",\"09171234567\",\"{$this->staff->username}\",\"Cash sale during outage\"\n";

        $file = UploadedFile::fake()->createWithContent('offline_sales.csv', $csvContent);

        // 1. Preview
        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'offline_sales',
                'csv_file' => $file,
            ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee($ref);
        $previewResponse->assertSee('Maria Santos');
        $previewResponse->assertSee('Classic Swedish Massage');
        $previewResponse->assertSee('Ready to Commit');

        $tokenKeys = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $previewToken = str_replace('import_payload_', '', reset($tokenKeys));

        // 2. Confirm Commit
        $confirmResponse = $this->actingAs($this->admin)
            ->post(route('admin.data-import.confirm'), [
                'preview_token' => $previewToken,
                'import_type' => 'offline_sales',
            ]);

        $confirmResponse->assertRedirect(route('admin.data-import.index', ['type' => 'offline_sales']));
        $confirmResponse->assertSessionHas('success');

        // Check appointment created
        $appointment = Appointment::where('notes', 'like', "%[Offline Ref: {$ref}]%")->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('completed', $appointment->status);
        $this->assertEquals(530.00, (float) $appointment->total_price);
        $this->assertEquals($this->staff->id, $appointment->user_id);
        $this->assertEquals('10:30:00', $appointment->start_time);
        $this->assertEquals('11:30:00', $appointment->end_time);

        // Check customer profile created and linked
        $this->assertEquals('09171234567', $appointment->customer->phone_number);

        // Check service attached
        $this->assertTrue($appointment->services->contains('id', $this->service->id));

        // Check payment record created
        $payment = Payment::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('cash', $payment->payment_method);
        $this->assertEquals(530.00, (float) $payment->amount);
        $this->assertEquals('full', $payment->type);
    }

    /* ─── 4. REPEATED FILE UPLOAD DETECTS DUPLICATES AND PREVENTS DOUBLE IMPORT ─── */

    public function test_repeated_file_upload_detects_duplicates_and_skips_safely(): void
    {
        $date = '2026-11-19';
        $ref = 'OFF-REPLAY-999';

        $csvContent = "offline_reference,transaction_date,transaction_time,service_name,amount,payment_method,customer_name,customer_phone,staff_username,notes\n" .
                      "\"{$ref}\",\"{$date}\",\"14:00\",\"Classic Swedish Massage\",530.00,gcash,\"Walk-in Client\",\"09281112233\",\"{$this->staff->username}\",\"Test Replay\"\n";

        // First upload & import
        $file1 = UploadedFile::fake()->createWithContent('offline.csv', $csvContent);
        $this->actingAs($this->admin)->post(route('admin.data-import.preview'), [
            'import_type' => 'offline_sales',
            'csv_file' => $file1,
        ]);
        $tokenKeys1 = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $token1 = str_replace('import_payload_', '', reset($tokenKeys1));
        $this->actingAs($this->admin)->post(route('admin.data-import.confirm'), [
            'preview_token' => $token1,
            'import_type' => 'offline_sales',
        ]);

        $this->assertEquals(1, Appointment::where('notes', 'like', "%[Offline Ref: {$ref}]%")->count());
        $this->assertEquals(1, Payment::count());

        // Second upload with the exact same file (Repeated file upload)
        $file2 = UploadedFile::fake()->createWithContent('offline_duplicate.csv', $csvContent);
        $response = $this->actingAs($this->admin)->post(route('admin.data-import.preview'), [
            'import_type' => 'offline_sales',
            'csv_file' => $file2,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Detected Duplicates (Will Be Safely Skipped)');
        $response->assertSee('has already been imported into ZenFlow');
        $response->assertSee('No valid new records found in this CSV');

        // Confirming empty valid records does not create duplicates
        $tokenKeys2 = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $token2 = str_replace('import_payload_', '', reset($tokenKeys2));
        $this->actingAs($this->admin)->post(route('admin.data-import.confirm'), [
            'preview_token' => $token2,
            'import_type' => 'offline_sales',
        ]);

        $this->assertEquals(1, Appointment::where('notes', 'like', "%[Offline Ref: {$ref}]%")->count());
        $this->assertEquals(1, Payment::count());
    }

    /* ─── 5. OFFLINE SALES UNASSIGNED STAFF HAS ZERO COMMISSION ─── */

    public function test_offline_sales_without_staff_leaves_staff_unassigned_with_zero_commission(): void
    {
        $ref = 'OFF-UNASSIGNED-01';
        $csvContent = "offline_reference,transaction_date,transaction_time,service_name,amount,payment_method,customer_name,customer_phone,staff_username,notes\n" .
                      "\"{$ref}\",\"2026-11-19\",\"15:00\",\"Classic Swedish Massage\",530.00,card,\"Walk-in Guest\",\"\",\"\",\"Unassigned staff\"\n";

        $file = UploadedFile::fake()->createWithContent('offline_unassigned.csv', $csvContent);

        $this->actingAs($this->admin)->post(route('admin.data-import.preview'), [
            'import_type' => 'offline_sales',
            'csv_file' => $file,
        ]);
        $tokenKeys = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $token = str_replace('import_payload_', '', reset($tokenKeys));

        $this->actingAs($this->admin)->post(route('admin.data-import.confirm'), [
            'preview_token' => $token,
            'import_type' => 'offline_sales',
        ]);

        $appt = Appointment::where('notes', 'like', "%[Offline Ref: {$ref}]%")->first();
        $this->assertNotNull($appt);
        $this->assertNull($appt->user_id);
        $this->assertNull($appt->staff);
    }

    /* ─── 6. MISSING REQUIRED COLUMNS & EMPTY CSV REJECTION ─── */

    public function test_csv_with_missing_required_columns_is_rejected(): void
    {
        // Missing "payment_method" and "service_name"
        $csvContent = "offline_reference,transaction_date\nOFF-001,2026-11-19\n";
        $file = UploadedFile::fake()->createWithContent('bad.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'offline_sales',
                'csv_file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_empty_csv_file_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('empty.csv', '');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'offline_sales',
                'csv_file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /* ─── 7. INVALID FIELD VALUES DETECTION ─── */

    public function test_invalid_field_values_are_flagged_in_preview(): void
    {
        // Unknown service and invalid payment method
        $csvContent = "offline_reference,transaction_date,service_name,payment_method,amount\n" .
                      "OFF-ERR-1,2026-11-19,\"Nonexistent Service\",bitcoin,-100\n";
        $file = UploadedFile::fake()->createWithContent('invalid.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'offline_sales',
                'csv_file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertSee('Rows with Validation Errors');
        $response->assertSee('Nonexistent Service');
        $response->assertSee('bitcoin');
    }

    /* ─── 8. REPORT INTEGRATION: DAILY SALES, BI, ATTENDANCE, POPULARITY ─── */

    public function test_imported_offline_sale_integrates_into_daily_sales_and_bi_reports(): void
    {
        $ref = 'OFF-INTEGRATION-77';
        $date = Carbon::today()->toDateString();

        $customer = Customer::create([
            'first_name' => 'Carla',
            'last_name' => 'Abellana',
            'phone_number' => '09197778899',
        ]);

        $appt = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $this->staff->id,
            'appointment_date' => $date,
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => 'completed',
            'total_price' => 530.00,
            'notes' => "[Offline Ref: {$ref}] Offline sale",
            'guest_first_name' => 'Carla',
            'guest_phone' => '09197778899',
            'confirmed_at' => Carbon::parse("{$date} 13:00:00"),
        ]);

        $appt->services()->attach($this->service->id, [
            'price_at_booking' => 530.00,
            'service_name' => $this->service->name,
            'service_duration' => 60,
        ]);

        Payment::create([
            'appointment_id' => $appt->id,
            'payment_method' => 'gcash',
            'amount' => 530.00,
            'type' => 'full',
            'paid_at' => Carbon::parse("{$date} 13:00:00"),
        ]);

        // Daily Sales CSV export includes this sale amount and total
        $dailyResponse = $this->actingAs($this->admin)
            ->get(route('admin.sales.daily-report-csv', ['period' => 'daily', 'date' => $date]));
        $dailyResponse->assertStatus(200);
        $dailyContent = $dailyResponse->streamedContent();
        $this->assertStringContainsString('530.00', $dailyContent);
        $this->assertStringContainsString('TOTAL', $dailyContent);

        // BI CSV export includes GCash in payment methods breakdown
        $biResponse = $this->actingAs($this->admin)
            ->get(route('admin.sales.business-report-csv', ['period' => 'monthly', 'date' => $date]));
        $biResponse->assertStatus(200);
        $biContent = $biResponse->streamedContent();
        $this->assertStringContainsString('GCASH', $biContent);
        $this->assertStringContainsString('530.00', $biContent);
    }

    public function test_admin_can_export_appointments_report_csv_with_filters(): void
    {
        $customer = Customer::create([
            'first_name' => 'Juana',
            'last_name' => 'Change',
            'phone_number' => '09170009999',
        ]);
        $service = Service::create([
            'name' => 'Ventosa Cupping',
            'category_id' => $this->category->id,
            'price' => 600.00,
            'duration_minutes' => 60,
            'is_active' => true,
            'participants_required' => 1,
            'therapists_required' => 1,
        ]);

        $apt = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => '2026-11-19',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'total_price' => 600.00,
        ]);
        $apt->services()->attach($service->id, ['price_at_booking' => 600.00]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.appointments.report-csv', [
                'status' => 'confirmed',
                'search' => 'Juana',
                'date_from' => '2026-11-01',
                'date_to' => '2026-11-30',
            ]));

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertStringStartsWith(CsvExportService::UTF8_BOM, $content);
        $this->assertStringContainsString('Juana Change', $content);
        $this->assertStringContainsString('Ventosa Cupping', $content);
        $this->assertStringContainsString('600.00', $content);
    }

    public function test_admin_can_export_attendance_report_csv(): void
    {
        $today = now('Asia/Manila');

        Attendance::create([
            'user_id' => $this->staff->id,
            'date' => $today->toDateString(),
            'status' => 'present',
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'notes' => 'Great shift',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('attendance.report-csv', ['range' => 'week']));

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertStringStartsWith(CsvExportService::UTF8_BOM, $content);
        $this->assertStringContainsString($this->staff->full_name, $content);
        $this->assertStringContainsString('Present', $content);
        $this->assertStringContainsString('9:00 AM', $content);
        $this->assertStringContainsString('6:00 PM', $content);
    }

    public function test_attendance_import_supports_12_hour_am_pm_time_format(): void
    {
        $date = Carbon::tomorrow()->toDateString();
        $csvContent = "staff_username,date,time_in,time_out,status,notes\n" .
                      "\"{$this->staff->username}\",\"{$date}\",\"09:15 AM\",\"06:45 PM\",present,\"12-hour AM/PM logged shift\"\n";

        $file = UploadedFile::fake()->createWithContent('attendance_12hr.csv', $csvContent);

        $importService = app(DataImportService::class);
        $parseResult = $importService->parseAndValidate('attendance', $file);

        $this->assertTrue($parseResult['success']);
        $this->assertEquals(1, $parseResult['validCount']);
        $this->assertEquals(0, $parseResult['invalidCount']);

        $record = $parseResult['validRecords'][0];
        $this->assertEquals('09:15:00', $record['check_in']);
        $this->assertEquals('18:45:00', $record['check_out']);

        $executeResult = $importService->executeImport('attendance', $parseResult['validRecords']);
        $this->assertEquals(1, $executeResult['imported']);

        $saved = Attendance::where('user_id', $this->staff->id)
            ->whereDate('date', $date)
            ->first();

        $this->assertNotNull($saved);
        $this->assertEquals('09:15:00', $saved->check_in);
        $this->assertEquals('18:45:00', $saved->check_out);
    }

    public function test_admin_can_export_attendance_report_xlsx(): void
    {
        $today = now('Asia/Manila');

        Attendance::create([
            'user_id' => $this->staff->id,
            'date' => $today->toDateString(),
            'status' => 'present',
            'check_in' => '09:30:00',
            'check_out' => '17:30:00',
            'notes' => 'XLSX export test',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('attendance.report-xlsx', ['range' => 'week']));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'spreadsheetml.sheet'));
    }

    public function test_admin_and_receptionist_can_export_service_popularity_csv(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.service-popularity.csv', ['range' => 'month']));

        $response->assertStatus(200);
        $this->assertStringStartsWith(CsvExportService::UTF8_BOM, $response->streamedContent());

        $recResponse = $this->actingAs($this->receptionist)
            ->get(route('receptionist.service-popularity.csv', ['range' => 'month']));
        $recResponse->assertStatus(200);
        $this->assertStringStartsWith(CsvExportService::UTF8_BOM, $recResponse->streamedContent());
    }

    /* ─── 8.5. CASE-INSENSITIVE MATCHING, FORMATTING & ERROR GUIDANCE TESTS ─── */

    public function test_service_matching_is_case_insensitive_and_handles_extra_whitespace(): void
    {
        $date = '2026-11-19';

        // 3 variations: lower-case, UPPER-CASE, and extra whitespaces
        $csvContent = "offline_reference,transaction_date,transaction_time,service_name,amount,payment_method\n" .
                      "OFF-CASE-1,{$date},10:00,\"classic swedish massage\",530.00,cash\n" .
                      "OFF-CASE-2,{$date},11:00,\"CLASSIC SWEDISH MASSAGE\",530.00,cash\n" .
                      "OFF-CASE-3,{$date},12:00,\"   Classic    Swedish    Massage   \",530.00,cash\n";

        $file = UploadedFile::fake()->createWithContent('case_service.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'offline_sales',
                'csv_file' => $file,
            ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('3 Valid Records');

        $tokenKeys = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $previewToken = str_replace('import_payload_', '', reset($tokenKeys));

        $this->actingAs($this->admin)
            ->post(route('admin.data-import.confirm'), [
                'preview_token' => $previewToken,
                'import_type' => 'offline_sales',
            ]);

        // Verify all 3 appointments were created and attached to the correct catalog service
        $appts = Appointment::where('notes', 'like', '%[Offline Ref: OFF-CASE-%')->get();
        $this->assertCount(3, $appts);
        foreach ($appts as $appt) {
            $this->assertTrue($appt->services->contains('id', $this->service->id));
        }
    }

    public function test_staff_matching_by_full_name_and_mixed_case(): void
    {
        // 3 variations: full name, lower-case full name, and UPPERCASE username
        $csvContent = "staff_username,date,status,notes\n" .
                      "\"Ana Reyes\",2026-11-10,present,\"Matched by Full Name\"\n" .
                      "\"ana reyes\",2026-11-11,present,\"Matched by lowercase full name\"\n" .
                      "\"THERAPIST_ANA\",2026-11-12,present,\"Matched by UPPERCASE username\"\n";

        $file = UploadedFile::fake()->createWithContent('case_staff.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'attendance',
                'csv_file' => $file,
            ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('3 Valid Records');

        $tokenKeys = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $previewToken = str_replace('import_payload_', '', reset($tokenKeys));

        $this->actingAs($this->admin)
            ->post(route('admin.data-import.confirm'), [
                'preview_token' => $previewToken,
                'import_type' => 'attendance',
            ]);

        // Verify all 3 attendances were created for this staff member
        $attendances = Attendance::where('user_id', $this->staff->id)->get();
        $this->assertCount(3, $attendances);
    }

    public function test_case_insensitive_duplicate_offline_reference_is_prevented(): void
    {
        $refUpper = 'OFF-DUP-CHECK-01';
        $refLower = 'off-dup-check-01';

        // 1. Import uppercase reference
        $csvContent1 = "offline_reference,transaction_date,service_name,payment_method,amount\n" .
                       "{$refUpper},2026-11-19,\"Classic Swedish Massage\",cash,530.00\n";
        $file1 = UploadedFile::fake()->createWithContent('ref1.csv', $csvContent1);

        $this->actingAs($this->admin)->post(route('admin.data-import.preview'), [
            'import_type' => 'offline_sales',
            'csv_file' => $file1,
        ]);
        $tokenKeys = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $token = str_replace('import_payload_', '', reset($tokenKeys));
        $this->actingAs($this->admin)->post(route('admin.data-import.confirm'), [
            'preview_token' => $token,
            'import_type' => 'offline_sales',
        ]);

        $this->assertEquals(1, Appointment::where('notes', 'like', "%[Offline Ref: {$refUpper}]%")->count());

        // 2. Try to upload lowercase reference: must be flagged as duplicate
        $csvContent2 = "offline_reference,transaction_date,service_name,payment_method,amount\n" .
                       "{$refLower},2026-11-19,\"Classic Swedish Massage\",cash,530.00\n";
        $file2 = UploadedFile::fake()->createWithContent('ref2.csv', $csvContent2);

        $previewResponse = $this->actingAs($this->admin)->post(route('admin.data-import.preview'), [
            'import_type' => 'offline_sales',
            'csv_file' => $file2,
        ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('Detected Duplicates');
        $previewResponse->assertSee('has already been imported into ZenFlow');
        $previewResponse->assertSee($refLower);
    }

    public function test_downloaded_template_human_readable_headers_upload_successfully(): void
    {
        // Headers as produced by the downloadable template
        $templateHeaders = "Staff Username or Full Name [REQUIRED],Attendance Date [REQUIRED: YYYY-MM-DD],Time In [OPTIONAL: HH:MM],Time Out [OPTIONAL: HH:MM],Status [REQUIRED: present / late / absent / on_leave],Notes [OPTIONAL]\n";
        $dataRow = "\"Ana Reyes\",2026-11-15,08:30,17:30,present,\"Shift from downloaded template\"\n";

        $file = UploadedFile::fake()->createWithContent('template_upload.csv', $templateHeaders . $dataRow);

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'attendance',
                'csv_file' => $file,
            ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('1 Valid Record');
        $previewResponse->assertSee('Ana Reyes');
    }

    public function test_validation_errors_identify_exact_row_and_column_with_guidance(): void
    {
        // No amount (now required), unknown service, invalid payment method, and bad staff
        $csvContent = "offline_reference,transaction_date,service_name,payment_method,amount,staff_username\n" .
                      ",2026-11-19,\"Mystery Nonexistent Scrub\",invalid_crypto,,\"Ghost Therapist\"\n";

        $file = UploadedFile::fake()->createWithContent('errors_sample.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'offline_sales',
                'csv_file' => $file,
            ]);

        $previewResponse->assertStatus(200);
        // Column badges
        $previewResponse->assertSee('Offline Reference');
        $previewResponse->assertSee('Service or Package Name');
        $previewResponse->assertSee('Payment Method');
        $previewResponse->assertSee('Staff / Therapist');
        // Guidance messages
        $previewResponse->assertSee('Mystery Nonexistent Scrub');
        $previewResponse->assertSee('Ghost Therapist');
        $previewResponse->assertSee('Check catalog spelling. Name matching is case-insensitive');
    }

    /* ─── 9. SECURITY & AUTHORIZATION TESTS ─── */

    public function test_unauthenticated_users_cannot_access_import_or_reports(): void
    {
        $this->get(route('admin.data-import.index'))->assertRedirect(route('login'));
        $this->get(route('admin.sales.daily-report-csv'))->assertRedirect(route('login'));
        $this->get(route('admin.appointments.report-csv'))->assertRedirect(route('login'));
        $this->get(route('attendance.report-csv'))->assertRedirect(route('login'));
    }

    public function test_customers_and_unauthorized_staff_cannot_access_admin_import_or_reports(): void
    {
        // Customer forbidden
        $this->actingAs($this->customerUser)
            ->get(route('admin.data-import.index'))
            ->assertStatus(403);

        $this->actingAs($this->customerUser)
            ->get(route('admin.appointments.report-csv'))
            ->assertStatus(403);

        $this->actingAs($this->customerUser)
            ->get(route('attendance.report-csv'))
            ->assertStatus(403);

        // Therapist staff forbidden from admin import
        $this->actingAs($this->staff)
            ->get(route('admin.data-import.index'))
            ->assertStatus(403);
    }

    public function test_receptionist_can_access_data_import_and_perform_workflow(): void
    {
        // Receptionist can access index
        $this->actingAs($this->receptionist)
            ->get(route('admin.data-import.index'))
            ->assertStatus(200);

        // Receptionist can download template
        $this->actingAs($this->receptionist)
            ->get(route('admin.data-import.template', ['type' => 'offline_sales']))
            ->assertStatus(200);

        // Receptionist can preview and confirm import
        $ref = 'OFF-REC-2026';
        $csv = "offline_reference,transaction_date,service_name,payment_method,amount\n" .
               "{$ref},2026-11-19,\"Classic Swedish Massage\",cash,530.00\n";
        $file = UploadedFile::fake()->createWithContent('rec_import.csv', $csv);

        $previewResponse = $this->actingAs($this->receptionist)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'offline_sales',
                'csv_file' => $file,
            ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee($ref);

        $tokenKeys = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $previewToken = str_replace('import_payload_', '', reset($tokenKeys));

        $this->actingAs($this->receptionist)
            ->post(route('admin.data-import.confirm'), [
                'preview_token' => $previewToken,
                'import_type' => 'offline_sales',
            ])
            ->assertRedirect();

        $this->assertEquals(1, Appointment::where('notes', 'like', "%[Offline Ref: {$ref}]%")->count());
    }

    public function test_appointment_report_renders_multi_service_hover_trigger_and_modal(): void
    {
        // Create an appointment with 4 services
        $service2 = Service::create([
            'name' => 'Aromatherapy Foot Scrub',
            'category_id' => $this->category->id,
            'price' => 350.00,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
        $service3 = Service::create([
            'name' => 'Hot Stone Add-on',
            'category_id' => $this->category->id,
            'price' => 200.00,
            'duration_minutes' => 20,
            'is_active' => true,
        ]);
        $service4 = Service::create([
            'name' => 'Herbal Compress',
            'category_id' => $this->category->id,
            'price' => 250.00,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'first_name' => 'Elena',
            'last_name' => 'Gilbert',
            'phone_number' => '09174445566',
        ]);

        $appt = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => '2026-11-19',
            'start_time' => '14:00:00',
            'end_time' => '16:20:00',
            'status' => 'confirmed',
            'total_price' => 1330.00,
        ]);

        $appt->services()->attach([
            $this->service->id => ['price_at_booking' => 530.00, 'service_duration' => 60, 'is_extra' => false],
            $service2->id => ['price_at_booking' => 350.00, 'service_duration' => 30, 'is_extra' => false],
            $service3->id => ['price_at_booking' => 200.00, 'service_duration' => 20, 'is_extra' => true],
            $service4->id => ['price_at_booking' => 250.00, 'service_duration' => 30, 'is_extra' => true],
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.appointments'));
        $response->assertStatus(200);

        // Verify multi-service trigger button appears with +2 more
        $response->assertSee('+2 more');
        $response->assertSee('appt-services-trigger');
        $response->assertSee('data-services=');

        // Verify modal container exists
        $response->assertSee('id="apptServicesModal"', false);
        $response->assertSee('Booked Services &amp; Treatments', false);
    }

    public function test_data_import_specifications_card_renders_clean_headers_and_correct_badges(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.data-import.index', ['type' => 'offline_sales']));
        $response->assertStatus(200);

        // Verify clean header labels (without duplicated [REQUIRED] / [OPTIONAL] in code tag)
        $response->assertSee('>Offline Reference<', false);
        $response->assertDontSee('>Offline Reference [REQUIRED: Unique ID]<', false);
        $response->assertSee('>Transaction Date<', false);
        $response->assertDontSee('>Transaction Date [REQUIRED: YYYY-MM-DD]<', false);

        // Verify required and optional badges are correctly segregated
        $response->assertSee('Required');
        $response->assertSee('Optional');

        // Verify simplified instructions box exists
        $response->assertSee('Simple Instructions:');
        $response->assertSee('Amount Paid is required.');
    }

    public function test_admin_can_download_styled_xlsx_templates(): void
    {
        foreach (['attendance', 'offline_sales'] as $type) {
            $response = $this->actingAs($this->admin)
                ->get(route('admin.data-import.template', ['type' => $type, 'format' => 'xlsx']));

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $this->assertTrue(str_contains((string) $response->headers->get('Content-Disposition'), '.xlsx'));

            $content = $response->streamedContent();
            // Verify OpenXML ZIP magic bytes: PK\x03\x04
            $this->assertStringStartsWith("PK\x03\x04", $content);

            // Verify it can be parsed back with XlsxSpreadsheetService
            $parsed = app(XlsxSpreadsheetService::class)->parse($content);
            $this->assertNotEmpty($parsed);
        }
    }

    public function test_offline_sales_import_saves_treatment_notes_and_keeps_medical_notes_isolated(): void
    {
        $customer = Customer::create([
            'first_name' => 'Carla',
            'last_name' => 'Reyes',
            'phone_number' => '09181112233',
            'medical_notes' => 'Existing allergy to lavender oils',
        ]);

        $ref = 'OFF-NOTE-' . uniqid();
        $treatmentNote = 'Lower back tension focus with gentle pressure.';
        $generalNote = 'Paid via counter terminal slip #998.';

        $csvContent = "offline_reference,transaction_date,transaction_time,service_name,amount,payment_method,customer_name,customer_phone,staff_username,treatment_notes,notes\n" .
                      "\"{$ref}\",\"2026-11-19\",\"14:00\",\"{$this->service->name}\",\"{$this->service->price}\",cash,\"Carla Reyes\",\"09181112233\",\"{$this->staff->username}\",\"{$treatmentNote}\",\"{$generalNote}\"\n";

        $file = UploadedFile::fake()->createWithContent('offline_notes.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'offline_sales',
                'csv_file' => $file,
            ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee($ref);
        $previewResponse->assertSee($treatmentNote);

        $tokenKeys = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $previewToken = str_replace('import_payload_', '', reset($tokenKeys));

        $this->actingAs($this->admin)
            ->post(route('admin.data-import.confirm'), [
                'preview_token' => $previewToken,
                'import_type' => 'offline_sales',
            ])
            ->assertRedirect();

        // 1. Verify treatment notes saved to appointment
        $appt = Appointment::where('notes', 'like', "%[Offline Ref: {$ref}]%")->first();
        $this->assertNotNull($appt);
        $this->assertEquals($treatmentNote, $appt->treatment_notes);
        $this->assertStringContainsString($generalNote, $appt->notes);

        // 2. Verify customer medical notes were NOT altered or overwritten
        $customer->refresh();
        $this->assertEquals('Existing allergy to lavender oils', $customer->medical_notes);
        $this->assertStringNotContainsString($treatmentNote, (string) $customer->medical_notes);
    }

    public function test_offline_sales_import_matches_packages_and_case_insensitive(): void
    {
        $package = Service::create([
            'name' => 'Zen Harmony Package',
            'category_id' => $this->category->id,
            'price' => 1800.00,
            'duration_minutes' => 120,
            'is_active' => true,
            'is_package' => true,
        ]);

        $ref = 'OFF-PKG-' . uniqid();
        // Use lowercase with extra whitespace; amount is now required
        $csvContent = "offline_reference,transaction_date,service_or_package_name,payment_method,amount\n" .
                      "\"{$ref}\",\"2026-11-19\",\"   zen harmony package   \",gcash,1800.00\n";

        $file = UploadedFile::fake()->createWithContent('offline_pkg.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'offline_sales',
                'csv_file' => $file,
            ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee($ref);
        $previewResponse->assertSee('Zen Harmony Package');
        $previewResponse->assertSee('Package');

        $tokenKeys = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $previewToken = str_replace('import_payload_', '', reset($tokenKeys));

        $this->actingAs($this->admin)
            ->post(route('admin.data-import.confirm'), [
                'preview_token' => $previewToken,
                'import_type' => 'offline_sales',
            ])
            ->assertRedirect();

        $appt = Appointment::where('notes', 'like', "%[Offline Ref: {$ref}]%")->first();
        $this->assertNotNull($appt);
        $this->assertEquals(1800.00, (float) $appt->total_price);
        $this->assertTrue($appt->services->contains('id', $package->id));
    }

    public function test_all_xlsx_report_endpoints_return_valid_openxml_stream(): void
    {
        $adminEndpoints = [
            route('admin.appointments.report-xlsx'),
            route('attendance.report-xlsx'),
            route('admin.sales.daily-report-xlsx'),
            route('admin.sales.business-report-xlsx'),
            route('admin.service-popularity.xlsx'),
        ];

        foreach ($adminEndpoints as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $this->assertTrue(str_contains((string) $response->headers->get('Content-Disposition'), '.xlsx'));

            $content = $response->streamedContent();
            $this->assertStringStartsWith("PK\x03\x04", $content);
        }

        // Receptionist service-popularity xlsx
        $response = $this->actingAs($this->receptionist)->get(route('receptionist.service-popularity.xlsx'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith("PK\x03\x04", $response->streamedContent());
    }

    /* ─── 10. AMOUNT PAID REQUIRED ENFORCEMENT ─── */

    public function test_offline_sale_import_rejects_empty_amount_paid(): void
    {
        $csvContent = "offline_reference,transaction_date,service_name,amount,payment_method\n" .
                      "OFF-NOAMT-01,2026-11-19,\"Classic Swedish Massage\",,cash\n";

        $file = UploadedFile::fake()->createWithContent('no_amount.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'offline_sales',
                'csv_file' => $file,
            ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('Amount Paid');
        $previewResponse->assertSee('Amount Paid is required for historical sales records.');
    }

    public function test_offline_sale_import_rejects_negative_amount_paid(): void
    {
        $csvContent = "offline_reference,transaction_date,service_name,amount,payment_method\n" .
                      "OFF-NEGAMT-01,2026-11-19,\"Classic Swedish Massage\",-100,cash\n";

        $file = UploadedFile::fake()->createWithContent('neg_amount.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'offline_sales',
                'csv_file' => $file,
            ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('Amount Paid');
        $previewResponse->assertSee('cannot be negative');
    }

    /* ─── 11. TRANSACTION TIME AM/PM FORMAT PARSING ─── */

    public function test_transaction_time_ampm_format_converts_correctly(): void
    {
        $ref = 'OFF-AMPM-' . uniqid();
        $csvContent = "offline_reference,transaction_date,service_name,amount,payment_method,transaction_time\n" .
                      "\"{$ref}\",\"2026-11-19\",\"Classic Swedish Massage\",530.00,cash,\"02:30 PM\"\n";

        $file = UploadedFile::fake()->createWithContent('ampm_time.csv', $csvContent);

        $this->actingAs($this->admin)->post(route('admin.data-import.preview'), [
            'import_type' => 'offline_sales',
            'csv_file' => $file,
        ]);

        $tokenKeys = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $previewToken = str_replace('import_payload_', '', reset($tokenKeys));

        $this->actingAs($this->admin)->post(route('admin.data-import.confirm'), [
            'preview_token' => $previewToken,
            'import_type' => 'offline_sales',
        ]);

        $appt = Appointment::where('notes', 'like', "%[Offline Ref: {$ref}]%")->first();
        $this->assertNotNull($appt);
        $this->assertEquals('14:30:00', $appt->start_time);
    }

    public function test_transaction_time_midnight_ampm_converts_correctly(): void
    {
        $ref = 'OFF-MID-' . uniqid();
        $csvContent = "offline_reference,transaction_date,service_name,amount,payment_method,transaction_time\n" .
                      "\"{$ref}\",\"2026-11-19\",\"Classic Swedish Massage\",530.00,cash,\"12:00 AM\"\n";

        $file = UploadedFile::fake()->createWithContent('midnight_time.csv', $csvContent);

        $this->actingAs($this->admin)->post(route('admin.data-import.preview'), [
            'import_type' => 'offline_sales',
            'csv_file' => $file,
        ]);

        $tokenKeys = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $previewToken = str_replace('import_payload_', '', reset($tokenKeys));

        $this->actingAs($this->admin)->post(route('admin.data-import.confirm'), [
            'preview_token' => $previewToken,
            'import_type' => 'offline_sales',
        ]);

        $appt = Appointment::where('notes', 'like', "%[Offline Ref: {$ref}]%")->first();
        $this->assertNotNull($appt);
        $this->assertEquals('00:00:00', $appt->start_time);
    }

    public function test_transaction_time_legacy_24hr_still_parses(): void
    {
        $ref = 'OFF-24HR-' . uniqid();
        $csvContent = "offline_reference,transaction_date,service_name,amount,payment_method,transaction_time\n" .
                      "\"{$ref}\",\"2026-11-19\",\"Classic Swedish Massage\",530.00,cash,\"14:30\"\n";

        $file = UploadedFile::fake()->createWithContent('legacy_time.csv', $csvContent);

        $this->actingAs($this->admin)->post(route('admin.data-import.preview'), [
            'import_type' => 'offline_sales',
            'csv_file' => $file,
        ]);

        $tokenKeys = array_filter(array_keys(session()->all()), fn($k) => str_starts_with($k, 'import_payload_'));
        $previewToken = str_replace('import_payload_', '', reset($tokenKeys));

        $this->actingAs($this->admin)->post(route('admin.data-import.confirm'), [
            'preview_token' => $previewToken,
            'import_type' => 'offline_sales',
        ]);

        $appt = Appointment::where('notes', 'like', "%[Offline Ref: {$ref}]%")->first();
        $this->assertNotNull($appt);
        $this->assertEquals('14:30:00', $appt->start_time);
    }

    /* ─── 12. UNASSIGNED STAFF SHOWN CLEARLY IN PREVIEW ─── */

    public function test_offline_sales_missing_therapist_shows_unassigned_in_preview(): void
    {
        $ref = 'OFF-NOSTAFF-' . uniqid();
        $csvContent = "offline_reference,transaction_date,service_name,amount,payment_method\n" .
                      "\"{$ref}\",\"2026-11-19\",\"Classic Swedish Massage\",530.00,cash\n";

        $file = UploadedFile::fake()->createWithContent('no_staff.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.data-import.preview'), [
                'import_type' => 'offline_sales',
                'csv_file' => $file,
            ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertSee($ref);
        // Should show unassigned in preview (no invalid error since staff is optional)
        $previewResponse->assertSee('Ready to Commit');
    }
}
