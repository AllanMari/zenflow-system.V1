<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle ?? 'Staff Utilization Report' }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 12mm 14mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.38;
            color: #000000;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #000000;
            margin: 12px 0 5px;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #111111;
            page-break-after: avoid;
        }

        .summary-table,
        .report-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 8px;
        }

        .summary-table {
            page-break-inside: avoid;
        }

        .summary-table th {
            background: #f3f4f6;
            color: #111111;
            border: 1px solid #d1d5db;
            border-bottom: 1.5px solid #111111;
            padding: 4px 4px;
            font-size: 6.8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            text-align: center;
        }

        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 5px 4px;
            font-size: 9.2px;
            font-weight: bold;
            text-align: center;
            color: #000000;
        }

        .report-table thead {
            display: table-header-group;
        }

        .report-table tr {
            page-break-inside: avoid;
        }

        .report-table th {
            background: #f3f4f6;
            color: #111111;
            border: 1px solid #d1d5db;
            border-bottom: 1.5px solid #111111;
            padding: 5px 5px;
            font-size: 7.4px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
        }

        .report-table td {
            border: 1px solid #d1d5db;
            padding: 5px 5px;
            font-size: 8.2px;
            color: #000000;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .report-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .report-table tfoot td {
            background: #f3f4f6;
            border-top: 1.5px solid #111111;
            font-weight: bold;
            color: #000000;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .empty-state {
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            text-align: center;
            color: #4b5563;
            font-size: 8.2px;
            margin-bottom: 8px;
        }

        .signature-table {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .signature-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 24px;
            border: 0;
        }

        .signature-label {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #4b5563;
            margin-bottom: 18px;
        }

        .signature-line {
            width: 82%;
            border-top: 1px solid #111111;
            padding-top: 3px;
            font-size: 8.5px;
            font-weight: bold;
            color: #000000;
        }

        .signature-role {
            font-size: 7.2px;
            color: #4b5563;
        }

        .doc-footer {
            margin-top: 12px;
            padding-top: 5px;
            border-top: 1px solid #d1d5db;
            font-size: 7.2px;
            color: #4b5563;
            text-align: right;
        }
    </style>

    @include('partials.pdf_page_styles')
</head>
<body>
@php
    $reportTitle = $reportTitle ?? 'STAFF UTILIZATION REPORT';
    $dateRangeText = $dateRange ?? ($dateDisplay ?? 'All Dates');
    $preparedByName = $preparedBy ?? 'Administrator';
    $checkedByName = !empty($checkedBy) ? $checkedBy : null;
    $generatedText = $generatedAt instanceof \Carbon\CarbonInterface
        ? $generatedAt->format('F j, Y g:i A')
        : ($generatedAt ?? now('Asia/Manila')->format('F j, Y g:i A'));

    $summaryData = is_array($summary ?? null) ? $summary : [];
    $staffCount = (int) ($summaryData['staff_count'] ?? 0);
    $totalAppts = (int) ($summaryData['appointments'] ?? 0);
    $totalCompleted = (int) ($summaryData['completed'] ?? 0);
    $totalServiceHours = (float) ($summaryData['service_hours'] ?? 0);
    $totalClockedHours = (float) ($summaryData['clocked_hours'] ?? 0);
    $overallUtilization = (float) ($summaryData['overall_utilization'] ?? 0);
    $totalRevenue = (float) ($summaryData['revenue'] ?? 0);
    $overallRevPerHour = (float) ($summaryData['revenue_per_hour'] ?? 0);

    $rawStaff = $staff ?? [];
    if ($rawStaff instanceof \Illuminate\Support\Collection || is_array($rawStaff)) {
        $staffRows = collect($rawStaff)
            ->filter(fn ($row) => is_array($row) || is_object($row))
            ->values();
    } else {
        $staffRows = collect([]);
    }
@endphp

@include('partials.report_header', [
    'reportTitle' => $reportTitle,
    'dateRange' => $dateRangeText,
    'referenceNumber' => $referenceNumber ?? null,
    'generatedAt' => $generatedText,
    'preparedBy' => $preparedByName,
])

<div class="section-title">Overall Utilization Summary</div>
<table class="summary-table">
    <thead>
        <tr>
            <th>Active Staff</th>
            <th>Appointments</th>
            <th>Completed</th>
            <th>Service Hours</th>
            <th>Clocked Hours</th>
            <th>Overall Utilization</th>
            <th>Revenue</th>
            <th>Revenue / Hour</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ number_format($staffCount) }}</td>
            <td>{{ number_format($totalAppts) }}</td>
            <td>{{ number_format($totalCompleted) }}</td>
            <td>{{ number_format($totalServiceHours, 2) }}h</td>
            <td>{{ number_format($totalClockedHours, 2) }}h</td>
            <td>{{ number_format($overallUtilization, 1) }}%</td>
            <td>&#8369;{{ number_format($totalRevenue, 2) }}</td>
            <td>&#8369;{{ number_format($overallRevPerHour, 2) }}</td>
        </tr>
    </tbody>
</table>

<div class="section-title">Staff Productivity &amp; Utilization Breakdown</div>
@if($staffRows->isNotEmpty())
    <table class="report-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 6%;" class="text-center">Rank</th>
                <th style="width: 22%;">Staff Name</th>
                <th style="width: 10%;" class="text-right">Appts</th>
                <th style="width: 10%;" class="text-right">Completed</th>
                <th style="width: 11%;" class="text-right">Service Hrs</th>
                <th style="width: 11%;" class="text-right">Clocked Hrs</th>
                <th style="width: 10%;" class="text-right">Utilization</th>
                <th style="width: 11%;" class="text-right">Revenue</th>
                <th style="width: 9%;" class="text-right">Rev / Hr</th>
            </tr>
        </thead>
        <tbody>
            @foreach($staffRows as $index => $row)
                @php
                    $rankVal = data_get($row, 'rank', $index + 1);
                    $nameVal = data_get($row, 'name', 'N/A');
                    $apptsVal = (int) data_get($row, 'appointments', 0);
                    $completedVal = (int) data_get($row, 'completed', 0);
                    $serviceHrsVal = (float) data_get($row, 'service_hours', 0);
                    $clockedHrsVal = (float) data_get($row, 'clocked_hours', 0);
                    $utilVal = (float) data_get($row, 'utilization', 0);
                    $revVal = (float) data_get($row, 'revenue', 0);
                    $revHrVal = (float) data_get($row, 'revenue_per_hour', 0);
                @endphp
                <tr>
                    <td class="text-center">{{ $rankVal }}</td>
                    <td class="bold">{{ $nameVal }}</td>
                    <td class="text-right">{{ number_format($apptsVal) }}</td>
                    <td class="text-right">{{ number_format($completedVal) }}</td>
                    <td class="text-right">{{ number_format($serviceHrsVal, 2) }}</td>
                    <td class="text-right">{{ number_format($clockedHrsVal, 2) }}</td>
                    <td class="text-right bold">{{ number_format($utilVal, 1) }}%</td>
                    <td class="text-right">&#8369;{{ number_format($revVal, 2) }}</td>
                    <td class="text-right">&#8369;{{ number_format($revHrVal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total / Overall ({{ $staffRows->count() }} staff)</td>
                <td class="text-right">{{ number_format($totalAppts) }}</td>
                <td class="text-right">{{ number_format($totalCompleted) }}</td>
                <td class="text-right">{{ number_format($totalServiceHours, 2) }}</td>
                <td class="text-right">{{ number_format($totalClockedHours, 2) }}</td>
                <td class="text-right">{{ number_format($overallUtilization, 1) }}%</td>
                <td class="text-right">&#8369;{{ number_format($totalRevenue, 2) }}</td>
                <td class="text-right">&#8369;{{ number_format($overallRevPerHour, 2) }}</td>
            </tr>
        </tfoot>
    </table>
@else
    <div class="empty-state">No staff utilization data recorded for this period.</div>
@endif

<table class="signature-table">
    <tr>
        <td>
            <div class="signature-label">Prepared by</div>
            <div class="signature-line">{{ $preparedByName }}</div>
            <div class="signature-role">Staff / Administrator</div>
        </td>
        <td>
            <div class="signature-label">Checked by</div>
            <div class="signature-line">{{ $checkedByName ?: '____________________________' }}</div>
            <div class="signature-role">Manager / Owner</div>
        </td>
    </tr>
</table>

<div class="doc-footer">
    ZenFlow Spa Management System &bull; {{ $reportTitle }} &bull; Generated {{ $generatedText }}
</div>
</body>
</html>
