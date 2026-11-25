<?php

namespace Tests\Feature;

use App\Services\ReportPdfService;
use App\Support\Pdf\PdfLayoutResolver;
use App\Support\Pdf\PdfPresetRegistry;
use Carbon\Carbon;
use Tests\TestCase;

class PdfFoundationTest extends TestCase
{
    protected ReportPdfService $pdfService;
    protected PdfLayoutResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdfService = app(ReportPdfService::class);
        $this->resolver = new PdfLayoutResolver(new PdfPresetRegistry());
    }

    public function test_all_nine_templates_preserve_legacy_output_when_layout_is_null(): void
    {
        $data = $this->deterministicReportData();

        $templates = [
            ['reports.appointment-invoice-pdf', 'portrait', 595.28, 841.89],
            ['reports.daily_sales_pdf', 'portrait', 595.28, 841.89],
            ['reports.appointments_report_pdf', 'portrait', 595.28, 841.89],
            ['reports.attendance-report', 'landscape', 841.89, 595.28],
            ['reports.business_report_pdf', 'portrait', 595.28, 841.89],
            ['reports.room_report_pdf', 'portrait', 595.28, 841.89],
            ['reports.service_popularity_pdf', 'portrait', 595.28, 841.89],
            ['reports.staff_utilization_pdf', 'landscape', 841.89, 595.28],
            // Legacy staff-weekly-schedule-pdf has hardcoded @page size: A4 portrait in CSS when layout is null
            ['reports.staff-weekly-schedule-pdf', 'landscape', 595.28, 841.89],
        ];

        foreach ($templates as [$view, $orientation, $expectedW, $expectedH]) {
            $response = $this->pdfService->streamPdf($view, $data, 'test.pdf', $orientation);
            $bytes = $response->getContent();

            $this->assertSame(200, $response->getStatusCode(), $view);
            $this->assertStringStartsWith('%PDF-', $bytes, $view);

            [$w, $h] = $this->extractMediaBox($bytes);
            $this->assertEqualsWithDelta($expectedW, $w, 0.5, "{$view} width");
            $this->assertEqualsWithDelta($expectedH, $h, 0.5, "{$view} height");
            $this->assertGreaterThanOrEqual(1, $this->countPages($bytes), $view);
        }
    }

    public function test_all_nine_templates_respect_authoritative_layout_resolver_dimensions(): void
    {
        $data = $this->deterministicReportData();

        $cases = [
            ['appointment_invoice', 'reports.appointment-invoice-pdf', 'compact_100x150', 283.46, 425.20],
            ['daily_sales', 'reports.daily_sales_pdf', 'standard_report', 595.28, 841.89],
            ['appointment_report', 'reports.appointments_report_pdf', 'wide_report', 841.89, 595.28],
            ['attendance_report', 'reports.attendance-report', 'wide_report', 841.89, 595.28],
            ['room_report', 'reports.room_report_pdf', 'standard_report', 595.28, 841.89],
            ['service_popularity', 'reports.service_popularity_pdf', 'standard_report', 595.28, 841.89],
            ['staff_utilization', 'reports.staff_utilization_pdf', 'wide_report', 841.89, 595.28],
            ['weekly_schedule', 'reports.staff-weekly-schedule-pdf', 'wide_report', 841.89, 595.28],
            ['business_report', 'reports.business_report_pdf', 'wide_report', 841.89, 595.28],
        ];

        foreach ($cases as [$reportKey, $view, $preset, $expectedW, $expectedH]) {
            $layout = $this->resolver->resolve($reportKey, ['preset' => $preset]);
            $response = $this->pdfService->streamPdf($view, $data, "{$reportKey}.pdf", $layout->orientation, $layout);
            $bytes = $response->getContent();

            $this->assertStringStartsWith('%PDF-', $bytes, $reportKey);
            [$w, $h] = $this->extractMediaBox($bytes);
            $this->assertEqualsWithDelta($expectedW, $w, 0.5, "{$reportKey} width");
            $this->assertEqualsWithDelta($expectedH, $h, 0.5, "{$reportKey} height");
        }
    }

    protected function extractMediaBox(string $pdfBytes): array
    {
        preg_match('/\/MediaBox\s*\[\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdfBytes, $m);
        $this->assertCount(5, $m, 'MediaBox not found in PDF output.');

        return [(float) $m[3], (float) $m[4]];
    }

    protected function countPages(string $pdfBytes): int
    {
        preg_match_all('/\/Type\s*\/Page\b(?!s)/', $pdfBytes, $m);

        return count($m[0]);
    }

    protected function deterministicReportData(): array
    {
        return [
            'reportTitle' => 'Deterministic Phase 1 Test',
            'referenceNumber' => 'REF-PHASE1',
            'dateRange' => 'Nov 25, 2026',
            'dateLabel' => 'DATE:',
            'dateDisplay' => '11/25/2026',
            'generatedAt' => Carbon::parse('2026-11-25 18:00:00'),
            'appointmentDate' => '2026-11-25',
            'preparedBy' => 'System Admin',
            'checkedBy' => null,
            'statusLabel' => 'All',
            'staffLabel' => 'All',
            'searchLabel' => 'None',
            'selectedCategoryName' => 'All Categories',
            'appointments' => collect([]),
            'attendances' => collect([]),
            'rooms' => collect([]),
            'printServiceSummary' => [
                ['code' => 'SVC1', 'name' => 'Swedish Massage', 'count' => 2, 'gross' => 1000, 'discount' => 0, 'net' => 1000],
            ],
            'pTotalCount' => 2,
            'pTotalGross' => 1000,
            'pTotalDiscount' => 0,
            'pTotalNet' => 1000,
            'summary' => [
                'present' => 0, 'late' => 0, 'on_leave' => 0, 'absent' => 0, 'day_off' => 0, 'holiday' => 0,
                'worked_hours' => 0, 'overtime' => 0, 'completed_appointments' => 0, 'services_completed' => 0,
                'services_represented' => 0, 'packages_booked' => 0, 'package_revenue' => 0, 'service_revenue' => 0,
                'total_rooms' => 0, 'active_rooms' => 0, 'available_rooms' => 0, 'occupied_rooms' => 0,
                'maintenance_rooms' => 0, 'inactive_rooms' => 0,
                'staff_count' => 0, 'appointments' => 0, 'completed' => 0, 'service_hours' => 0,
                'clocked_hours' => 0, 'overall_utilization' => 0, 'revenue' => 0, 'revenue_per_hour' => 0,
                'total_booked_hours' => 0, 'total_scheduled_hours' => 0, 'total_completed' => 0, 'total_revenue' => 0,
            ],
            'staff' => collect([]),
            'services' => collect([]),
            'packages' => collect([]),
            'categoryBreakdown' => [],
            'serviceBreakdown' => [],
            'packageBreakdown' => [],
            'staffSchedules' => [],
            'weekLabel' => 'Nov 23 – Nov 29, 2026',
            'mode' => 'weekly',
            'timeline' => collect([]),
            'allStaff' => [],
            'startDate' => Carbon::parse('2026-11-25'),
            'endDate' => Carbon::parse('2026-11-25'),
            'clientName' => 'Jane Doe',
            'clientPhone' => '09171234567',
            'staffNames' => 'Therapist A',
            'customer' => (object) ['phone_number' => '09171234567'],
            'appointment' => (object) [
                'id' => 1,
                'appointment_date' => Carbon::parse('2026-11-25'),
                'start_time' => '10:00 AM',
                'end_time' => '11:00 AM',
                'treatment_notes' => null,
            ],
            'room' => (object) ['name' => 'Room 1'],
            'payments' => collect([]),
            'paidPayments' => collect([]),
            'refundPayments' => collect([]),
            'paymentMethods' => collect(['Cash']),
            'total' => 1000.0,
            'originalTotal' => 1000.0,
            'hasDiscount' => false,
            'discountType' => null,
            'discountLabel' => null,
            'shortDiscountBadge' => null,
            'discountPercent' => 0.0,
            'discountAmount' => 0.0,
            'totalPaid' => 1000.0,
            'totalRefunded' => 0.0,
            'netPaid' => 1000.0,
            'balance' => 0.0,
            'totalAppointments' => 0,
            'completed' => 0,
            'confirmed' => 0,
            'pending' => 0,
            'cancelled' => 0,
            'noShow' => 0,
            'totalRevenue' => 0,
            'recordedValue' => 1000,
            'paidValue' => 1000,
            'outstandingValue' => 0,
            'paymentMethodText' => 'Cash',
            'search' => null,
            'safeTotalRevenue' => 0,
            'safeTotalCount' => 0,
            'safeAvgSale' => 0,
            'safeUniqueCustomers' => 0,
            'safeCompletionRate' => 0,
            'safeNoShowRate' => 0,
            'safeCancellationRate' => 0,
            'revenueChangeLabel' => '0%',
            'safeRevenueChange' => 0,
            'safeTotalAppts' => 0,
            'safeCompleted' => 0,
            'safeCancelled' => 0,
            'safeNoShow' => 0,
            'topServices' => [],
            'topStaff' => [],
            'therapistRetentionData' => [],
            'peakBusinessHours' => [],
            'methodBreakdown' => [],
        ];
    }
}
