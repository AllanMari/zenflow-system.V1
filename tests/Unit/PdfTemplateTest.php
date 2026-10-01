<?php

namespace Tests\Unit;

use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class PdfTemplateTest extends TestCase
{
    public function test_all_report_templates_render_successfully(): void
    {
        $dummyData = [
            'reportTitle' => 'Test Report',
            'referenceNumber' => 'REF-12345',
            'dateRange' => 'October 2026',
            'generatedAt' => now(),
            'preparedBy' => 'Admin User',
            'total' => 1000,
            'totalPaid' => 1000,
            'paymentMethodText' => 'Cash',
            'appointments' => collect([]),
            'attendances' => collect([]),
            'printServiceSummary' => [],
            'pTotalCount' => 0,
            'pTotalGross' => 0,
            'pTotalNet' => 0,
            'pTotalDiscount' => 0,
            'summary' => ['present' => 0, 'late' => 0, 'on_leave' => 0, 'absent' => 0, 'day_off' => 0, 'holiday' => 0, 'worked_hours' => 0, 'overtime' => 0, 'completed_appointments' => 0, 'services_completed' => 0, 'services_represented' => 0, 'packages_booked' => 0, 'package_revenue' => 0, 'service_revenue' => 0],
            'sales' => collect([]),
            'services' => collect([]),
            'packages' => collect([]),
            'categoryBreakdown' => [],
            'serviceBreakdown' => [],
            'packageBreakdown' => [],
            'staffSchedules' => [],
            'weekLabel' => 'This Week',
            'mode' => 'weekly',
            'timeline' => collect([]),
            'allStaff' => [],
            'startDate' => now(),
            'endDate' => now(),
            'customerName' => 'John Doe',
            'customer' => (object) ['phone_number' => '123456789'],
            'appointment' => (object) [
                'appointment_date' => \Carbon\Carbon::parse('2026-10-01'),
                'start_time' => '10:00 AM',
                'end_time' => '11:00 AM',
            ],
            'appointmentDate' => '2026-10-01',
            'startTime' => '10:00 AM',
            'endTime' => '11:00 AM',
            'serviceName' => 'Massage',
            'absenceRecords' => [],
            'exceptionRecords' => [],
            'paymentMethods' => collect([]),
            'dateLabel' => 'October 2026',
            'dateDisplay' => 'October 2026',
            'statusLabel' => 'All',
            'staffLabel' => 'All',
            'searchLabel' => 'None',
            'selectedCategoryName' => 'All',
            'totalAppointments' => 0,
            'completed' => 0,
            'confirmed' => 0,
            'pending' => 0,
            'cancelled' => 0,
            'noShow' => 0,
            'totalRevenue' => 0,
            'search' => null,
            'paidPayments' => collect([]),
            'refundPayments' => collect([]),
            'totalRefunded' => 0,
            'netPaid' => 0,
            'balance' => 0,
            'payments' => collect([]),
            'room' => (object) ['name' => 'Room 1'],
            'staff' => (object) ['name' => 'Staff 1'],
            'recordedValue' => 0,
            'paidValue' => 0,
            'outstandingValue' => 0,
            'safeTotalRevenue' => 0,
            'safeAvgSale' => 0,
            'safeUniqueCustomers' => 0,
            'safeDeposits' => 0,
            'safeCompletionRate' => 0,
            'safeNoShowRate' => 0,
            'safeCancellationRate' => 0,
            'revenueChangeLabel' => '0',
            'safeRevenueChange' => 0,
            'safeTotalAppts' => 0,
            'topServices' => [],
            'topStaff' => [],
            'therapistRetentionData' => [],
            'peakBusinessHours' => [],
            'methodBreakdown' => [],
            'safeTotalCount' => 0,
            'safeCompleted' => 0,
            'safeCancelled' => 0,
            'safeNoShow' => 0,
        ];

        $templates = [
            'reports.appointment-invoice-pdf',
            'reports.appointments_report_pdf',
            'reports.attendance-report',
            'reports.business_report_pdf',
            'reports.daily_sales_pdf',
            'reports.service_popularity_pdf',
            'reports.staff-weekly-schedule-pdf',
        ];

        foreach ($templates as $template) {
            try {
                $pdf = Pdf::loadView($template, $dummyData);
                $output = $pdf->output();
                $this->assertNotEmpty($output, "Template {$template} failed to produce output.");
            } catch (\Throwable $e) {
                $this->fail("Template {$template} threw an exception: " . $e->getMessage());
            }
        }
    }
}

