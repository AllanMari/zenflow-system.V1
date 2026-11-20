<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\DataImportService;
use App\Services\SalesAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MultiTherapistCommissionAndImportTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $staffRole;
    protected ServiceCategory $serviceCat;
    protected User $admin;
    protected User $therapistA;
    protected User $therapistB;
    protected User $therapistC;
    protected User $therapistD;
    protected Service $standardService;
    protected Service $packageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->staffRole = Role::firstOrCreate(['name' => 'staff']);

        $this->admin = User::factory()->create([
            'username' => 'admin_jane',
            'first_name' => 'Jane',
            'last_name' => 'Admin',
            'is_active' => true,
        ]);
        $this->admin->roles()->attach($this->adminRole);

        $this->therapistA = User::factory()->create([
            'username' => 'therapist_ana',
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'is_active' => true,
        ]);
        $this->therapistA->roles()->attach($this->staffRole);

        $this->therapistB = User::factory()->create([
            'username' => 'therapist_maria',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'is_active' => true,
        ]);
        $this->therapistB->roles()->attach($this->staffRole);

        $this->therapistC = User::factory()->create([
            'username' => 'therapist_clara',
            'first_name' => 'Clara',
            'last_name' => 'Cruz',
            'is_active' => true,
        ]);
        $this->therapistC->roles()->attach($this->staffRole);

        $this->therapistD = User::factory()->create([
            'username' => 'therapist_dina',
            'first_name' => 'Dina',
            'last_name' => 'Diaz',
            'is_active' => true,
        ]);
        $this->therapistD->roles()->attach($this->staffRole);

        $this->serviceCat = ServiceCategory::create([
            'name' => 'Therapy',
            'is_active' => true,
        ]);

        $this->standardService = Service::create([
            'category_id' => $this->serviceCat->id,
            'name' => 'Swedish Massage',
            'price' => 1000,
            'duration_minutes' => 60,
            'is_active' => true,
            'is_package' => false,
        ]);

        $this->packageService = Service::create([
            'category_id' => $this->serviceCat->id,
            'name' => 'Couple Spa Package',
            'price' => 3000,
            'duration_minutes' => 90,
            'is_active' => true,
            'is_package' => true,
        ]);
    }

    protected function createCompletedAppointmentWithPayment(
        Service $service,
        float $amount,
        array $therapists,
        string $status = 'completed',
        ?string $cancelReason = null
    ): Appointment {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Client',
            'phone_number' => '0917' . rand(1000000, 9999999),
            'customer_type' => 'regular',
        ]);

        $today = Carbon::today()->toDateString();
        $primaryId = !empty($therapists) ? $therapists[0]->id : null;

        $appt = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $primaryId,
            'appointment_date' => $today,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => $status,
            'cancellation_reason' => $cancelReason,
            'total_price' => $amount,
            'created_by' => $this->admin->id,
        ]);

        $appt->services()->attach($service->id, [
            'price_at_booking' => $amount,
            'service_name' => $service->name,
            'service_duration' => $service->duration_minutes,
            'is_extra' => false,
        ]);

        if (!empty($therapists)) {
            $assistingIds = collect($therapists)->skip(1)->pluck('id')->toArray();
            $appt->syncStaff($primaryId, $assistingIds);
        }

        if ($status === 'completed' || $amount > 0) {
            Payment::create([
                'appointment_id' => $appt->id,
                'payment_method' => 'cash',
                'amount' => $amount,
                'type' => 'full',
                'paid_at' => Carbon::today()->setHour(10),
            ]);
        }

        return $appt;
    }

    /* ─── 1. COMMISSION & REVENUE DISTRIBUTION TESTS ─── */

    public function test_one_therapist_receives_full_revenue_credit_and_30_percent_commission(): void
    {
        $this->createCompletedAppointmentWithPayment(
            $this->standardService,
            1000.00,
            [$this->therapistA]
        );

        $analyticsService = app(SalesAnalyticsService::class);
        $data = $analyticsService->getDashboardData(new Request(['status' => 'completed']));
        $staffPerf = $data['staffPerformance'];

        $recordA = collect($staffPerf)->firstWhere('id', $this->therapistA->id);
        $this->assertNotNull($recordA);
        $this->assertEquals(1000.00, $recordA['revenue']);
        $this->assertEquals(300.00, $recordA['commission']); // 30% of 1000
        $this->assertEquals(1, $recordA['completed']);
    }

    public function test_two_therapists_receive_equal_revenue_and_15_percent_commission_each(): void
    {
        $this->createCompletedAppointmentWithPayment(
            $this->standardService,
            2000.00,
            [$this->therapistA, $this->therapistB]
        );

        $analyticsService = app(SalesAnalyticsService::class);
        $data = $analyticsService->getDashboardData(new Request(['status' => 'completed']));
        $staffPerf = $data['staffPerformance'];

        $recordA = collect($staffPerf)->firstWhere('id', $this->therapistA->id);
        $recordB = collect($staffPerf)->firstWhere('id', $this->therapistB->id);

        $this->assertNotNull($recordA);
        $this->assertNotNull($recordB);

        // 50% revenue share each
        $this->assertEquals(1000.00, $recordA['revenue']);
        $this->assertEquals(1000.00, $recordB['revenue']);

        // 15% commission share each (total pool ₱600.00 = ₱300.00 each)
        $this->assertEquals(300.00, $recordA['commission']);
        $this->assertEquals(300.00, $recordB['commission']);

        // Sum across staff reconciles exactly to total revenue and total commission
        $this->assertEquals(2000.00, array_sum(array_column($staffPerf, 'revenue')));
        $this->assertEquals(600.00, array_sum(array_column($staffPerf, 'commission')));
    }

    public function test_three_therapists_receive_equal_distribution_with_deterministic_remainder_cent(): void
    {
        // ₱1000 net revenue / 3 therapists = ₱333.33 each with ₱0.01 remainder to primary therapist
        // Total commission pool = ₱1000 × 30% = ₱300.00 / 3 therapists = ₱100.00 each
        $this->createCompletedAppointmentWithPayment(
            $this->standardService,
            1000.00,
            [$this->therapistA, $this->therapistB, $this->therapistC]
        );

        $analyticsService = app(SalesAnalyticsService::class);
        $data = $analyticsService->getDashboardData(new Request(['status' => 'completed']));
        $staffPerf = $data['staffPerformance'];

        $recordA = collect($staffPerf)->firstWhere('id', $this->therapistA->id);
        $recordB = collect($staffPerf)->firstWhere('id', $this->therapistB->id);
        $recordC = collect($staffPerf)->firstWhere('id', $this->therapistC->id);

        // Remainder cent goes to primary therapist A
        $this->assertEquals(333.34, $recordA['revenue']);
        $this->assertEquals(333.33, $recordB['revenue']);
        $this->assertEquals(333.33, $recordC['revenue']);

        // Total revenue equals exactly ₱1000.00
        $this->assertEquals(1000.00, round(array_sum(array_column($staffPerf, 'revenue')), 2));

        // Commission pool = ₱300 / 3 = ₱100.00 each
        $this->assertEquals(100.00, $recordA['commission']);
        $this->assertEquals(100.00, $recordB['commission']);
        $this->assertEquals(100.00, $recordC['commission']);
        $this->assertEquals(300.00, array_sum(array_column($staffPerf, 'commission')));
    }

    public function test_four_therapists_receive_7_point_5_percent_commission_and_25_percent_revenue_each(): void
    {
        $this->createCompletedAppointmentWithPayment(
            $this->standardService,
            4000.00,
            [$this->therapistA, $this->therapistB, $this->therapistC, $this->therapistD]
        );

        $analyticsService = app(SalesAnalyticsService::class);
        $data = $analyticsService->getDashboardData(new Request(['status' => 'completed']));
        $staffPerf = $data['staffPerformance'];

        foreach ([$this->therapistA, $this->therapistB, $this->therapistC, $this->therapistD] as $therapist) {
            $record = collect($staffPerf)->firstWhere('id', $therapist->id);
            $this->assertNotNull($record);
            $this->assertEquals(1000.00, $record['revenue'], "Therapist {$therapist->id} should receive 25% revenue");
            $this->assertEquals(300.00, $record['commission'], "Therapist {$therapist->id} should receive 7.5% commission (₱300)");
        }

        $this->assertEquals(4000.00, array_sum(array_column($staffPerf, 'revenue')));
        $this->assertEquals(1200.00, array_sum(array_column($staffPerf, 'commission')));
    }

    public function test_packages_with_multiple_therapists_distribute_commission_and_revenue_equally(): void
    {
        $this->createCompletedAppointmentWithPayment(
            $this->packageService,
            3000.00,
            [$this->therapistA, $this->therapistB]
        );

        $analyticsService = app(SalesAnalyticsService::class);
        $data = $analyticsService->getDashboardData(new Request(['status' => 'completed']));
        $staffPerf = $data['staffPerformance'];

        $recordA = collect($staffPerf)->firstWhere('id', $this->therapistA->id);
        $recordB = collect($staffPerf)->firstWhere('id', $this->therapistB->id);

        $this->assertEquals(1500.00, $recordA['revenue']);
        $this->assertEquals(1500.00, $recordB['revenue']);
        $this->assertEquals(450.00, $recordA['commission']);
        $this->assertEquals(450.00, $recordB['commission']);
    }

    public function test_cancelled_and_no_show_appointments_yield_zero_commission_for_all_assigned_therapists(): void
    {
        $this->createCompletedAppointmentWithPayment(
            $this->standardService,
            0.00,
            [$this->therapistA, $this->therapistB],
            'cancelled'
        );

        $this->createCompletedAppointmentWithPayment(
            $this->standardService,
            0.00,
            [$this->therapistC, $this->therapistD],
            'no_show'
        );

        $analyticsService = app(SalesAnalyticsService::class);
        $data = $analyticsService->getDashboardData(new Request(['status' => 'all']));
        $staffPerf = $data['staffPerformance'];

        foreach ($staffPerf as $staffRecord) {
            $this->assertEquals(0.00, $staffRecord['commission'], "Cancelled or no-show appointment must yield 0 commission.");
            $this->assertEquals(0.00, $staffRecord['revenue'], "Cancelled or no-show appointment must yield 0 revenue.");
        }
    }

    public function test_unassigned_appointments_yield_zero_commission(): void
    {
        $this->createCompletedAppointmentWithPayment(
            $this->standardService,
            1000.00,
            [] // No staff
        );

        $analyticsService = app(SalesAnalyticsService::class);
        $data = $analyticsService->getDashboardData(new Request(['status' => 'completed']));
        $staffPerf = $data['staffPerformance'];

        // Staff performance array should be empty since no staff was assigned
        $this->assertEmpty($staffPerf);

        // Transaction log row should display Unassigned and 0.00 commission
        $txRows = $data['txLogData']['rows'];
        $this->assertCount(1, $txRows);
        $this->assertEquals('Unassigned', $txRows[0]['staffName']);
        $this->assertEquals('0.00', $txRows[0]['therapistCom']);
    }

    public function test_business_wide_revenue_is_counted_only_once_for_multiple_therapists(): void
    {
        $this->createCompletedAppointmentWithPayment(
            $this->standardService,
            2500.00,
            [$this->therapistA, $this->therapistB, $this->therapistC]
        );

        $analyticsService = app(SalesAnalyticsService::class);
        $data = $analyticsService->getDashboardData(new Request(['status' => 'completed']));

        // Business total metrics must count the appointment amount only once (₱2,500.00)
        $this->assertEquals(2500.00, (float) $data['totalRevenue']);
        $this->assertEquals(1, $data['totalCount']);
    }

    public function test_transaction_log_displays_all_assigned_therapists_and_total_pool_commission(): void
    {
        $this->createCompletedAppointmentWithPayment(
            $this->standardService,
            2000.00,
            [$this->therapistA, $this->therapistB]
        );

        $analyticsService = app(SalesAnalyticsService::class);
        $data = $analyticsService->getDashboardData(new Request(['status' => 'completed']));
        $txRows = $data['txLogData']['rows'];

        $this->assertCount(1, $txRows, 'Exactly one transaction row must be produced');
        $row = $txRows[0];

        // Shows comma-separated therapist names
        $this->assertStringContainsString('Ana Reyes', $row['staffName']);
        $this->assertStringContainsString('Maria Santos', $row['staffName']);

        // Row displays 30% pool rate and full appointment commission (₱600.00)
        $this->assertEquals(30, $row['comPct']);
        $this->assertEquals('600.00', $row['therapistCom']);
        $this->assertEquals('2,000.00', $row['grossAmount']);
        $this->assertEquals('2,000.00', $row['netAmount']);
    }

    /* ─── 2. OFFLINE SALES IMPORT TESTS ─── */

    public function test_offline_import_with_single_therapist_assigns_primary_role(): void
    {
        $importService = app(DataImportService::class);

        $csv = "Offline Reference,Transaction Date,Transaction Time,Service or Package Name,Amount Paid,Payment Method,Customer Name,Customer Phone,Staff / Therapist,Treatment Notes,Notes\n"
             . "OFF-TEST-001,2026-11-19,10:30 AM,Swedish Massage,1000.00,cash,Juan Dela Cruz,09171234567,Ana Reyes,Shoulder knot,Power outage\n";

        $file = UploadedFile::fake()->createWithContent('offline_sales.csv', $csv);
        $parseResult = $importService->parseAndValidate('offline_sales', $file);

        $this->assertTrue($parseResult['success']);
        $this->assertEquals(1, $parseResult['validCount']);
        $this->assertEquals(0, $parseResult['invalidCount']);

        $executeResult = $importService->executeImport('offline_sales', $parseResult['validRecords']);
        $this->assertEquals(1, $executeResult['imported']);

        $appt = Appointment::where('notes', 'like', '%[Offline Ref: OFF-TEST-001]%')->first();
        $this->assertNotNull($appt);
        $this->assertEquals($this->therapistA->id, $appt->user_id);

        $assigned = $appt->assignedStaff;
        $this->assertCount(1, $assigned);
        $this->assertEquals('primary', $assigned[0]->pivot->role);
        $this->assertEquals($this->therapistA->id, $assigned[0]->id);
    }

    public function test_offline_import_with_multiple_therapists_assigns_primary_and_assisting_roles(): void
    {
        $importService = app(DataImportService::class);

        // Two therapists separated by comma
        $csv = "Offline Reference,Transaction Date,Transaction Time,Service or Package Name,Amount Paid,Payment Method,Customer Name,Customer Phone,Staff / Therapist,Treatment Notes,Notes\n"
             . "OFF-TEST-002,2026-11-19,02:00 PM,Couple Spa Package,3000.00,gcash,Maria Santos,09181234567,\"Ana Reyes, Maria Santos\",Couple session,Holiday promo\n";

        $file = UploadedFile::fake()->createWithContent('offline_sales.csv', $csv);
        $parseResult = $importService->parseAndValidate('offline_sales', $file);

        $this->assertTrue($parseResult['success']);
        $this->assertEquals(1, $parseResult['validCount']);
        $this->assertEquals(0, $parseResult['invalidCount']);

        $record = $parseResult['validRecords'][0];
        $this->assertEquals($this->therapistA->id, $record['staff_id']);
        $this->assertEquals([$this->therapistB->id], $record['assisting_staff_ids']);
        $this->assertStringContainsString('Ana Reyes', $record['staff_names']);
        $this->assertStringContainsString('Maria Santos', $record['staff_names']);

        $executeResult = $importService->executeImport('offline_sales', $parseResult['validRecords']);
        $this->assertEquals(1, $executeResult['imported']);

        $appt = Appointment::where('notes', 'like', '%[Offline Ref: OFF-TEST-002]%')->first();
        $this->assertNotNull($appt);
        $this->assertEquals($this->therapistA->id, $appt->user_id);

        $assigned = $appt->assignedStaff()->orderBy('appointment_staff.role', 'desc')->get();
        $this->assertCount(2, $assigned);

        $primaryStaff = $assigned->firstWhere('pivot.role', 'primary');
        $assistingStaff = $assigned->firstWhere('pivot.role', 'assisting');

        $this->assertNotNull($primaryStaff);
        $this->assertNotNull($assistingStaff);
        $this->assertEquals($this->therapistA->id, $primaryStaff->id);
        $this->assertEquals($this->therapistB->id, $assistingStaff->id);

        // Verify single payment created
        $payments = Payment::where('appointment_id', $appt->id)->get();
        $this->assertCount(1, $payments);
        $this->assertEquals(3000.00, $payments[0]->amount);
    }

    public function test_offline_import_rejects_row_when_any_therapist_name_is_invalid(): void
    {
        $importService = app(DataImportService::class);

        // One valid therapist, one completely unknown therapist
        $csv = "Offline Reference,Transaction Date,Transaction Time,Service or Package Name,Amount Paid,Payment Method,Customer Name,Customer Phone,Staff / Therapist,Treatment Notes,Notes\n"
             . "OFF-TEST-003,2026-11-19,03:30 PM,Swedish Massage,1000.00,cash,Guest,09191234567,\"Ana Reyes, NonExistentTherapist\",Session notes,Remark\n";

        $file = UploadedFile::fake()->createWithContent('offline_sales.csv', $csv);
        $parseResult = $importService->parseAndValidate('offline_sales', $file);

        $this->assertTrue($parseResult['success']);
        $this->assertEquals(0, $parseResult['validCount']);
        $this->assertEquals(1, $parseResult['invalidCount']);

        $invalidRecord = $parseResult['invalidRecords'][0];
        $this->assertStringContainsString('NonExistentTherapist', $invalidRecord['reason']);
        $this->assertStringContainsString('was not found in active accounts', $invalidRecord['reason']);
    }

    public function test_offline_import_preserves_duplicate_offline_reference_safeguard(): void
    {
        $importService = app(DataImportService::class);

        // Create an existing appointment with reference OFF-DUP-001
        $this->createCompletedAppointmentWithPayment(
            $this->standardService,
            1000.00,
            [$this->therapistA]
        );
        Appointment::latest()->first()->update(['notes' => '[Offline Ref: OFF-DUP-001]']);

        // Upload CSV containing duplicate reference (case-insensitive)
        $csv = "Offline Reference,Transaction Date,Transaction Time,Service or Package Name,Amount Paid,Payment Method,Customer Name,Customer Phone,Staff / Therapist,Treatment Notes,Notes\n"
             . "off-dup-001,2026-11-19,11:00 AM,Swedish Massage,1000.00,cash,Guest,09191234567,Ana Reyes,Notes,Remark\n";

        $file = UploadedFile::fake()->createWithContent('offline_sales.csv', $csv);
        $parseResult = $importService->parseAndValidate('offline_sales', $file);

        $this->assertTrue($parseResult['success']);
        $this->assertEquals(0, $parseResult['validCount']);
        $this->assertEquals(1, $parseResult['duplicateCount']);
        $this->assertStringContainsString('already been imported into ZenFlow', $parseResult['duplicateRecords'][0]['reason']);
    }
}
