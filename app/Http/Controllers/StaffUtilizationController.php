<?php

namespace App\Http\Controllers;

use App\Services\ReportPdfService;
use App\Services\SalesAnalyticsService;
use App\Support\Pdf\PdfLayoutResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffUtilizationController extends Controller
{
    public function __construct(
        private SalesAnalyticsService $salesAnalyticsService
    ) {
    }

    public function index(Request $request)
    {
        $data = $this->salesAnalyticsService->getStaffUtilizationReport($request);

        $data['routeName'] = Auth::user()->isAdmin()
            ? 'admin.staff-utilization'
            : 'receptionist.staff-utilization';

        $data['reportTitle'] = 'STAFF UTILIZATION REPORT';

        $data['dateDisplay'] = $this->formatDateDisplay(
            $data['period'],
            $data['startDate'],
            $data['endDate']
        );

        return view('shared.staff-utilization', [
            'data' => $data,
        ]);
    }

    public function pdf(Request $request, ReportPdfService $pdfService)
    {
        $data = $this->salesAnalyticsService->getStaffUtilizationReport($request);

        $pdfData = [
            'staff' => $data['staff'],
            'summary' => $data['summary'],
            'reportTitle' => 'STAFF UTILIZATION REPORT',
            'referenceNumber' => 'SU-' . now()->format('YmdHis'),
            'dateRange' => $data['startDate']->format('M d, Y') . ' - ' . $data['endDate']->format('M d, Y'),
            'generatedAt' => now('Asia/Manila')->format('F j, Y g:i A'),
            'preparedBy' => Auth::user()->full_name ?? Auth::user()->name,
            'checkedBy' => $request->input('checked_by'),
        ];

        $layout = app(PdfLayoutResolver::class)->resolveFromRequest('staff_utilization', $request);

        $action = $request->get('action', 'download');

        return $action === 'stream'
            ? $pdfService->streamPdf(
                'reports.staff_utilization_pdf',
                $pdfData,
                'staff-utilization-report.pdf',
                $layout->orientation,
                $layout
            )
            : $pdfService->generatePdf(
                'reports.staff_utilization_pdf',
                $pdfData,
                'staff-utilization-report.pdf',
                $layout->orientation,
                $layout
            );
    }

    private function formatDateDisplay(
        string $period,
        Carbon $startDate,
        Carbon $endDate
    ): string {
        return match ($period) {
            'daily' => $startDate->format('n/j/Y'),
            'weekly' => $startDate->format('M j') . ' — ' . $endDate->format('M j, Y'),
            'monthly' => $startDate->format('F Y'),
            'yearly' => $startDate->format('Y'),
            'custom' => $startDate->format('M j') . ' — ' . $endDate->format('M j, Y'),
            default => $startDate->format('n/j/Y') . ' — ' . $endDate->format('n/j/Y'),
        };
    }
}
