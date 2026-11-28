<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\SalesAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SecondPanelistReportingEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_sales_report_supports_quarterly_period_in_ui_service_and_exports(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.sales', ['period' => 'quarterly']));
        $response->assertStatus(200);
        $response->assertSee('Quarterly');
        $response->assertSee('value="quarterly"', false);

        $service = app(SalesAnalyticsService::class);
        $data = $service->getDashboardData(Request::create('/admin/sales', 'GET', ['period' => 'quarterly']));

        $expectedStart = Carbon::today()->startOfQuarter()->toDateString();
        $expectedEnd = Carbon::today()->endOfQuarter()->toDateString();

        $this->assertSame('quarterly', $data['period']);
        $this->assertSame('This Quarter', $data['label']);
        $this->assertSame($expectedStart, $data['startDate']->toDateString());
        $this->assertSame($expectedEnd, $data['endDate']->toDateString());

        $pdfResponse = $this->actingAs($admin)->get(route('admin.sales.daily-report-pdf', [
            'period' => 'quarterly',
            'action' => 'stream',
        ]));
        $pdfResponse->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfResponse->getContent());
    }

    public function test_transaction_log_filters_by_bir_invoice_number(): void
    {
        $admin = $this->createAdmin();
        $cat = ServiceCategory::create(['name' => 'Massage', 'is_active' => true]);
        $svc = Service::create([
            'category_id' => $cat->id,
            'name' => 'Swedish Massage',
            'price' => 800.00,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
        $room = Room::create(['name' => 'Room 1', 'status' => 'available', 'is_active' => true]);
        $customer = Customer::create([
            'first_name' => 'Elena',
            'last_name' => 'Reyes',
            'phone_number' => '09171234567',
        ]);

        $today = Carbon::today();

        $appt1 = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => $today->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 800.00,
            'bir_invoice_number' => 'BIR-2026-0011',
        ]);
        $appt1->services()->attach($svc->id, [
            'price_at_booking' => 800.00,
            'service_name' => $svc->name,
            'service_duration' => 60,
            'is_extra' => false,
        ]);
        Payment::create([
            'appointment_id' => $appt1->id,
            'amount' => 800.00,
            'payment_method' => 'cash',
            'type' => 'full',
            'paid_at' => $today->copy()->setTime(11, 0),
        ]);

        $appt2 = Appointment::create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'appointment_date' => $today->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => 'completed',
            'total_price' => 800.00,
            'bir_invoice_number' => 'BIR-2026-9999',
        ]);
        $appt2->services()->attach($svc->id, [
            'price_at_booking' => 800.00,
            'service_name' => $svc->name,
            'service_duration' => 60,
            'is_extra' => false,
        ]);
        Payment::create([
            'appointment_id' => $appt2->id,
            'amount' => 800.00,
            'payment_method' => 'cash',
            'type' => 'full',
            'paid_at' => $today->copy()->setTime(14, 0),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.sales.tx-log', [
            'period' => 'daily',
            'status' => 'completed',
            'bir_search' => '0011',
        ]));

        $response->assertStatus(200);
        $response->assertSee('BIR-2026-0011');
        $response->assertDontSee('BIR-2026-9999');
    }

    public function test_appointments_report_surfaces_rescheduled_and_bir_invoice_in_csv_and_pdf(): void
    {
        $admin = $this->createAdmin();
        $customer = Customer::create([
            'first_name' => 'Marco',
            'last_name' => 'Cruz',
            'phone_number' => '09178889999',
        ]);

        Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => 'confirmed',
            'total_price' => 1000.00,
            'bir_invoice_number' => 'BIR-INV-777',
            'rescheduled_at' => Carbon::now(),
        ]);

        $csvResponse = $this->actingAs($admin)->get(route('admin.appointments.report-csv'));
        $csvResponse->assertStatus(200);
        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString('BIR Invoice No', $csvContent);
        $this->assertStringContainsString('Rescheduled', $csvContent);
        $this->assertStringContainsString('BIR-INV-777', $csvContent);

        $pdfResponse = $this->actingAs($admin)->get(route('admin.appointments.report-pdf', ['action' => 'stream']));
        $pdfResponse->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfResponse->getContent());
    }

    public function test_business_report_pdf_renders_heatmap_and_customer_retention_sections(): void
    {
        $admin = $this->createAdmin();
        $customer = Customer::create([
            'first_name' => 'Liza',
            'last_name' => 'Dizon',
            'phone_number' => '09175554444',
        ]);

        Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today()->subDays(5)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'total_price' => 900.00,
        ]);

        $todayAppt = Appointment::create([
            'customer_id' => $customer->id,
            'appointment_date' => Carbon::today()->toDateString(),
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => 'completed',
            'total_price' => 900.00,
        ]);

        Payment::create([
            'appointment_id' => $todayAppt->id,
            'amount' => 900.00,
            'payment_method' => 'cash',
            'type' => 'full',
            'paid_at' => Carbon::today()->setTime(16, 0),
        ]);

        $html = view('reports.business_report_pdf', [
            'reportTitle' => 'BUSINESS INTELLIGENCE REPORT',
            'referenceNumber' => 'BUS-TEST',
            'startDate' => Carbon::today()->startOfMonth(),
            'endDate' => Carbon::today()->endOfMonth(),
            'safeTotalRevenue' => 900.00,
            'safeTotalCount' => 1,
            'safeAvgSale' => 900.00,
            'safeUniqueCustomers' => 1,
            'safeCompletionRate' => 100.0,
            'safeNoShowRate' => 0.0,
            'safeCancellationRate' => 0.0,
            'safeTotalAppts' => 1,
            'safeCompleted' => 1,
            'safeCancelled' => 0,
            'safeNoShow' => 0,
            'revenueChangeLabel' => '0%',
            'safeRevenueChange' => 0,
            'topServices' => [],
            'topStaff' => [],
            'staffPerformance' => [],
            'methodBreakdown' => [],
            'therapistRetentionData' => [],
            'customerRetention' => [
                'customers_served' => 1,
                'returning_customers' => 1,
                'new_customers' => 0,
                'retention_rate' => 100.0,
            ],
            'peakBusinessHours' => ['hours' => [], 'peakLabel' => 'N/A', 'peakCount' => 0],
            'heatmapTopSlots' => [
                ['day' => 'Friday', 'hour' => '15:00', 'count' => 1],
            ],
            'heatmapDemandMax' => 1,
            'heatmapDemandTotal' => 1,
            'preparedBy' => 'Admin',
            'generatedAt' => now()->format('F d, Y g:i A'),
        ])->render();

        $this->assertStringContainsString('Appointment Demand Heatmap', $html);
        $this->assertStringContainsString('Friday', $html);
        $this->assertStringContainsString('15:00', $html);
        $this->assertStringContainsString('Customer Retention Summary', $html);
        $this->assertStringContainsString('100.0%', $html);

        $pdfResponse = $this->actingAs($admin)->get(route('admin.sales.business-report-pdf', ['action' => 'stream']));
        $pdfResponse->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfResponse->getContent());
    }
}
