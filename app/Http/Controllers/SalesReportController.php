<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SalesAnalyticsService;
use App\Services\GeminiInsightService;
use App\Services\ReportPdfService;
use App\Services\CsvExportService;

class SalesReportController extends Controller
{
    private SalesAnalyticsService $analyticsService;

    public function __construct(SalesAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        $data = $this->analyticsService->getDashboardData($request);
        $data['routeName'] = Auth::user()->isAdmin() ? 'admin.sales' : 'receptionist.sales';

        $data['reportTitle'] = match ($data['period']) {
            'daily'   => 'SUMMARY OF DAILY SALES REPORT',
            'weekly'  => 'SUMMARY OF WEEKLY SALES REPORT',
            'monthly' => 'SUMMARY OF MONTHLY SALES REPORT',
            'yearly'  => 'SUMMARY OF YEARLY SALES REPORT',
            'custom'  => 'SUMMARY OF CUSTOM SALES REPORT',
            default   => 'SUMMARY OF SALES REPORT',
        };
        $data['dateLabel'] = $data['period'] === 'daily' ? 'DATE:' : 'PERIOD:';
        $data['dateDisplay'] = match ($data['period']) {
            'daily'   => $data['startDate']->format('n/j/Y'),
            'weekly'  => $data['startDate']->format('M j') . ' — ' . $data['endDate']->format('M j, Y'),
            'monthly' => $data['startDate']->format('F Y'),
            'yearly'  => $data['startDate']->format('Y'),
            'custom'  => $data['startDate']->format('M j') . ' — ' . $data['endDate']->format('M j, Y'),
            default   => $data['startDate']->format('n/j/Y') . ' — ' . $data['endDate']->format('n/j/Y'),
        };

        return view('shared.sales', $data);
    }

    public function transactionLogFragment(Request $request)
    {
        $data = $this->analyticsService->getDashboardData($request);

        return view('shared._transaction_log_table', [
            'txLogData'      => $data['txLogData'],
            'currentStatus'  => $data['currentStatus'],
            'currency'       => '₱',
            'serviceCodeMap' => $data['serviceCodeMap'],
        ]);
    }

    public function aiChat(Request $request, GeminiInsightService $gemini)
    {
        $request->validate([
            'question' => 'required|string|max:200',
            'history'  => 'nullable|array',
            'history.*.role'    => 'required|in:user,assistant',
            'history.*.content' => 'required|string',
        ]);

        $data = $this->analyticsService->getDashboardData($request);

        return response()->json($gemini->chat(
            $request->input('question'), 
            $data['insightPayload'], 
            $request->input('history', [])
        ));
    }

public function dailyReportPdf(Request $request, ReportPdfService $pdfService)
    {
        $data = $this->analyticsService->getDashboardData($request);
        $period = $data['period'];

        // Dynamic title and label based on user's active view filter
        $reportTitle = match ($period) {
            'daily'   => 'SUMMARY OF DAILY SALES REPORT',
            'weekly'  => 'SUMMARY OF WEEKLY SALES REPORT',
            'monthly' => 'SUMMARY OF MONTHLY SALES REPORT',
            'yearly'  => 'SUMMARY OF YEARLY SALES REPORT',
            'custom'  => 'SUMMARY OF CUSTOM SALES REPORT',
            default   => 'SUMMARY OF SALES REPORT',
        };

        $dateDisplay = match ($period) {
            'daily'   => $data['startDate']->format('n/j/Y'),
            'weekly'  => $data['startDate']->format('M j') . ' — ' . $data['endDate']->format('M j, Y'),
            'monthly' => $data['startDate']->format('F Y'),
            'yearly'  => $data['startDate']->format('Y'),
            'custom'  => $data['startDate']->format('M j') . ' — ' . $data['endDate']->format('M j, Y'),
            default   => $data['startDate']->format('n/j/Y') . ' — ' . $data['endDate']->format('n/j/Y'),
        };

        $filename = strtolower(str_replace(' ', '-', $period)) . '-sales-report.pdf';

        $pdfData = [
            'printServiceSummary' => $data['printServiceSummary'] ?? [],
            'pTotalGross'         => collect($data['printServiceSummary'] ?? [])->sum('gross'),
            'pTotalNet'           => collect($data['printServiceSummary'] ?? [])->sum('net'),
            'pTotalDiscount'      => collect($data['printServiceSummary'] ?? [])->sum('discount'),
            'pTotalCount'         => collect($data['printServiceSummary'] ?? [])->sum('count'),
            'reportTitle'         => $reportTitle,
            'referenceNumber'     => 'SAL-' . strtoupper($period) . '-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
            'dateLabel'           => $period === 'daily' ? 'DATE:' : 'PERIOD:',
            'dateDisplay'         => $dateDisplay,
            'preparedBy'          => Auth::user()->full_name ?? Auth::user()->name,
            'checkedBy'           => $request->input('checked_by'),
            'generatedAt'         => now()->format('F d, Y g:i A'),
        ];

        $action = $request->get('action', 'download');
        return $action === 'stream'
            ? $pdfService->streamPdf('reports.daily_sales_pdf', $pdfData, $filename)
            : $pdfService->generatePdf('reports.daily_sales_pdf', $pdfData, $filename);
    }

    public function businessReportPdf(Request $request, ReportPdfService $pdfService)
    {
        $data = $this->analyticsService->getDashboardData($request);
        $filename = 'business-report.pdf';

        $pdfData = [
            'reportTitle'           => 'BUSINESS INTELLIGENCE REPORT',
            'referenceNumber'       => 'BUS-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
            'startDate'             => $data['startDate'],
            'endDate'               => $data['endDate'],
            'safeTotalRevenue'      => $data['totalRevenue'],
            'safeTotalCount'        => $data['totalCount'],
            'safeAvgSale'           => $data['avgSale'],
            'safeUniqueCustomers'   => $data['uniqueCustomers'],
            'safeCompletionRate'    => $data['completionRate'],
            'safeNoShowRate'        => $data['noShowRate'],
            'safeCancellationRate'  => $data['cancellationRate'],
            'safeTotalAppts'        => $data['totalApptsInPeriod'],
            'safeCompleted'         => $data['completedApptsInPeriod'],
            'safeCancelled'         => $data['cancelledApptsInPeriod'],
            'revenueChangeLabel'    => $data['revenueChangeLabel'],
            'safeRevenueChange'     => $data['revenueChange'],
            'topServices'           => $data['topServices'],
            'topStaff'              => $data['topStaff'],
            'methodBreakdown'       => $data['methodBreakdown'],
            'therapistRetentionData'=> $data['therapistRetentionData'] ?? [],
            'peakBusinessHours'     => $data['peakBusinessHours'] ?? [],
            'preparedBy'            => Auth::user()->full_name ?? Auth::user()->name,
            'checkedBy'             => $request->input('checked_by'),
            'generatedAt'           => now()->format('F d, Y g:i A'),
            'safeNoShow'            => $data['noShowApptsInPeriod'],
        ];

        $action = $request->get('action', 'download');
        return $action === 'stream'
            ? $pdfService->streamPdf('reports.business_report_pdf', $pdfData, $filename)
            : $pdfService->generatePdf('reports.business_report_pdf', $pdfData, $filename);
    }

    public function dailyReportCsv(Request $request, CsvExportService $csvService)
    {
        $data = $this->analyticsService->getDashboardData($request);
        $period = $data['period'];

        $reportTitle = match ($period) {
            'daily'   => 'SUMMARY OF DAILY SALES REPORT',
            'weekly'  => 'SUMMARY OF WEEKLY SALES REPORT',
            'monthly' => 'SUMMARY OF MONTHLY SALES REPORT',
            'yearly'  => 'SUMMARY OF YEARLY SALES REPORT',
            'custom'  => 'SUMMARY OF CUSTOM SALES REPORT',
            default   => 'SUMMARY OF SALES REPORT',
        };

        $dateDisplay = match ($period) {
            'daily'   => $data['startDate']->format('M d, Y'),
            'weekly'  => $data['startDate']->format('M j') . ' — ' . $data['endDate']->format('M j, Y'),
            'monthly' => $data['startDate']->format('F Y'),
            'yearly'  => $data['startDate']->format('Y'),
            'custom'  => $data['startDate']->format('M j') . ' — ' . $data['endDate']->format('M j, Y'),
            default   => $data['startDate']->format('M d, Y') . ' — ' . $data['endDate']->format('M d, Y'),
        };

        $referenceNumber = 'SAL-' . strtoupper($period) . '-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        $meta = [
            ['ZENFLOW REPORT', $reportTitle],
            ['REFERENCE NUMBER', $referenceNumber],
            ['PERIOD / DATE', $dateDisplay],
            ['GENERATED AT', now()->format('F d, Y g:i A')],
            ['PREPARED BY', Auth::user()->full_name ?? Auth::user()->name],
        ];

        $headers = [
            'Category',
            'Service Name',
            'Count',
            'Gross Sales (PHP)',
            'Discount (PHP)',
            'Net Sales (PHP)',
        ];

        $summary = $data['printServiceSummary'] ?? [];
        $rows = [];
        $totalCount = 0;
        $totalGross = 0;
        $totalDiscount = 0;
        $totalNet = 0;

        foreach ($summary as $item) {
            $totalCount += (int) ($item['count'] ?? 0);
            $totalGross += (float) ($item['gross'] ?? 0);
            $totalDiscount += (float) ($item['discount'] ?? 0);
            $totalNet += (float) ($item['net'] ?? 0);

            $rows[] = [
                $item['category_name'] ?? 'General',
                $item['service_name'] ?? $item['name'] ?? 'N/A',
                $item['count'] ?? 0,
                number_format((float) ($item['gross'] ?? 0), 2, '.', ''),
                number_format((float) ($item['discount'] ?? 0), 2, '.', ''),
                number_format((float) ($item['net'] ?? 0), 2, '.', ''),
            ];
        }

        // Totals Row
        $rows[] = [
            'TOTAL',
            '',
            $totalCount,
            number_format($totalGross, 2, '.', ''),
            number_format($totalDiscount, 2, '.', ''),
            number_format($totalNet, 2, '.', ''),
        ];

        $format = strtolower($request->query('format', 'csv'));
        if ($format === 'xlsx') {
            $xlsxService = app(\App\Services\XlsxSpreadsheetService::class);
            $binary = $xlsxService->generateReport($reportTitle, $headers, $rows, $meta);
            return $xlsxService->downloadResponse('sales-report-' . strtolower($period) . '-' . now()->format('Y-m-d') . '.xlsx', $binary);
        }

        $filename = 'sales-report-' . strtolower($period) . '-' . now()->format('Y-m-d') . '.csv';

        return $csvService->stream($filename, $headers, $rows, $meta);
    }

    public function dailyReportXlsx(Request $request, CsvExportService $csvService)
    {
        $request->merge(['format' => 'xlsx']);
        return $this->dailyReportCsv($request, $csvService);
    }

    public function businessReportCsv(Request $request, CsvExportService $csvService)
    {
        $data = $this->analyticsService->getDashboardData($request);
        $referenceNumber = 'BUS-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        $meta = [
            ['ZENFLOW REPORT', 'BUSINESS INTELLIGENCE REPORT'],
            ['REFERENCE NUMBER', $referenceNumber],
            ['PERIOD RANGE', $data['startDate']->format('M d, Y') . ' — ' . $data['endDate']->format('M d, Y')],
            ['GENERATED AT', now()->format('F d, Y g:i A')],
            ['PREPARED BY', Auth::user()->full_name ?? Auth::user()->name],
        ];

        // Section 1: KPI Metrics
        $kpiRows = [
            ['Total Gross Revenue (PHP)', number_format((float) $data['totalRevenue'], 2, '.', '')],
            ['Completed Transactions Count', $data['totalCount']],
            ['Average Sale Value (PHP)', number_format((float) $data['avgSale'], 2, '.', '')],
            ['Unique Customers Served', $data['uniqueCustomers']],
            ['Appointment Completion Rate', number_format((float) $data['completionRate'], 1) . '%'],
            ['Appointment Cancellation Rate', number_format((float) $data['cancellationRate'], 1) . '%'],
            ['No-Show Rate', number_format((float) $data['noShowRate'], 1) . '%'],
            ['Total Appointments Booked in Period', $data['totalApptsInPeriod']],
            ['Completed Appointments', $data['completedApptsInPeriod']],
            ['Cancelled Appointments', $data['cancelledApptsInPeriod']],
            ['No-Show Appointments', $data['noShowApptsInPeriod'] ?? 0],
        ];

        // Section 2: Top Services
        $topServicesRows = [];
        $rank = 1;
        foreach ($data['topServices'] ?? [] as $svc) {
            $topServicesRows[] = [
                $rank++,
                $svc->name ?? 'N/A',
                $svc->total_bookings ?? 0,
                number_format((float) ($svc->total_revenue ?? 0), 2, '.', ''),
            ];
        }

        // Section 3: Staff Performance
        $staffRows = [];
        foreach ($data['topStaff'] ?? [] as $stf) {
            $staffRows[] = [
                $stf->name ?? 'N/A',
                $stf->total_appointments ?? 0,
                number_format((float) ($stf->total_revenue ?? 0), 2, '.', ''),
            ];
        }

        // Section 4: Payment Methods
        $paymentRows = [];
        foreach ($data['methodBreakdown'] ?? [] as $methodKey => $pm) {
            $methodName = is_string($methodKey) && !is_numeric($methodKey)
                ? $methodKey
                : (is_array($pm) ? ($pm['method'] ?? 'OTHER') : ($pm->method ?? 'OTHER'));
            $count = is_array($pm) ? ($pm['count'] ?? 0) : ($pm->count ?? 0);
            $total = is_array($pm) ? ($pm['total'] ?? 0) : ($pm->total ?? 0);

            $paymentRows[] = [
                strtoupper((string) $methodName),
                $count,
                number_format((float) $total, 2, '.', ''),
            ];
        }

        $sections = [
            [
                'title' => 'KEY PERFORMANCE INDICATORS (KPIs)',
                'headers' => ['Metric', 'Value'],
                'rows' => $kpiRows,
            ],
            [
                'title' => 'TOP PERFORMING SERVICES',
                'headers' => ['Rank', 'Service Name', 'Bookings Count', 'Revenue (PHP)'],
                'rows' => $topServicesRows,
            ],
            [
                'title' => 'STAFF / THERAPIST PERFORMANCE',
                'headers' => ['Staff Name', 'Completed Appointments', 'Revenue Generated (PHP)'],
                'rows' => $staffRows,
            ],
            [
                'title' => 'PAYMENT METHODS BREAKDOWN',
                'headers' => ['Payment Method', 'Transactions Count', 'Total Collected (PHP)'],
                'rows' => $paymentRows,
            ],
        ];

        $format = strtolower($request->query('format', 'csv'));
        if ($format === 'xlsx') {
            $xlsxService = app(\App\Services\XlsxSpreadsheetService::class);
            $binary = $xlsxService->generateMultiSectionReport('Business Intelligence Report', $sections, $meta);
            return $xlsxService->downloadResponse('business-intelligence-report-' . now()->format('Y-m-d') . '.xlsx', $binary);
        }

        $filename = 'business-intelligence-report-' . now()->format('Y-m-d') . '.csv';

        return $csvService->streamMultiSection($filename, $sections, $meta);
    }

    public function businessReportXlsx(Request $request, CsvExportService $csvService)
    {
        $request->merge(['format' => 'xlsx']);
        return $this->businessReportCsv($request, $csvService);
    }
}