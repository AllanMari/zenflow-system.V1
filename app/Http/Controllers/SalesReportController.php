<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SalesAnalyticsService;
use App\Services\OllamaInsightService;
use App\Services\ReportPdfService;

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

    public function aiChat(Request $request, OllamaInsightService $ollama)
    {
        $request->validate([
            'question' => 'required|string|max:200',
            'history'  => 'nullable|array',
            'history.*.role'    => 'required|in:user,assistant',
            'history.*.content' => 'required|string',
        ]);

        $data = $this->analyticsService->getDashboardData($request);

        return response()->json($ollama->chat(
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

        $filename = strtolower(str_replace(' ', '-', $period)) . '-sales-report-' . now()->format('Y-m-d') . '.pdf';

        $pdfData = [
            'printServiceSummary' => $data['printServiceSummary'] ?? [],
            'pTotalGross'         => collect($data['printServiceSummary'] ?? [])->sum('gross'),
            'pTotalNet'           => collect($data['printServiceSummary'] ?? [])->sum('net'),
            'pTotalDiscount'      => collect($data['printServiceSummary'] ?? [])->sum('discount'),
            'pTotalCount'         => collect($data['printServiceSummary'] ?? [])->sum('count'),
            'reportTitle'         => $reportTitle,
            'dateLabel'           => $period === 'daily' ? 'DATE:' : 'PERIOD:',
            'dateDisplay'         => $dateDisplay,
            'preparedBy'          => Auth::user()->full_name ?? Auth::user()->name,
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
        $filename = 'business-report-' . strtolower($data['label']) . '-' . now()->format('Y-m-d') . '.pdf';

        $pdfData = [
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
            'preparedBy'            => Auth::user()->full_name ?? Auth::user()->name,
            'generatedAt'           => now()->format('F d, Y g:i A'),
            'safeNoShow'            => $data['noShowApptsInPeriod'],
        ];

        $action = $request->get('action', 'download');
        return $action === 'stream'
            ? $pdfService->streamPdf('reports.business_report_pdf', $pdfData, $filename)
            : $pdfService->generatePdf('reports.business_report_pdf', $pdfData, $filename);
    }
}