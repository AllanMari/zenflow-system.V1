<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle ?? 'Therapist Commission Statement' }}</title>

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

        .meta-bar {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 8px;
        }

        .meta-bar td {
            border: 1px solid #d1d5db;
            background: #f9fafb;
            padding: 4px 7px;
            font-size: 8px;
            color: #111111;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #000000;
            margin: 10px 0 5px;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #111111;
            page-break-after: avoid;
        }

        .summary-table,
        .report-table {
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
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .sub-note {
            display: block;
            font-size: 7px;
            color: #4b5563;
            margin-top: 1px;
        }

        .empty-state {
            border: 1px solid #d1d5db;
            padding: 12px;
            text-align: center;
            color: #4b5563;
            font-size: 8.5px;
            margin-bottom: 8px;
        }

        .doc-footer {
            margin-top: 10px;
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
    $reportTitle = $reportTitle ?? 'THERAPIST COMMISSION STATEMENT';
    $dateRangeText = $dateRange ?? 'All Dates';
    $staffNameText = $staffName ?? ($preparedBy ?? 'Therapist');
    $filterText = $filterLabel ?? 'This Month';
    $generatedText = $generatedAt instanceof \Carbon\CarbonInterface
        ? $generatedAt->format('F j, Y g:i A')
        : ($generatedAt ?? now('Asia/Manila')->format('F j, Y g:i A'));

    $summaryData = is_array($summary ?? null) ? $summary : [];
    $completedCount = (int) ($summaryData['completed_count'] ?? 0);
    $soloCount = (int) ($summaryData['solo_count'] ?? 0);
    $multiCount = (int) ($summaryData['multi_count'] ?? 0);
    $totalNet = (float) ($summaryData['total_net'] ?? 0);
    $totalCommission = (float) ($summaryData['total_commission'] ?? 0);
    $avgCommission = (float) ($summaryData['avg_commission'] ?? 0);

    $commissionRows = collect($rows ?? []);
@endphp

@include('partials.report_header', [
    'reportTitle' => $reportTitle,
    'dateRange' => $dateRangeText,
    'referenceNumber' => $referenceNumber ?? null,
    'generatedAt' => $generatedText,
    'preparedBy' => $staffNameText,
])

<table class="meta-bar">
    <tr>
        <td style="width: 36%;"><strong>Therapist:</strong> {{ $staffNameText }}</td>
        <td style="width: 24%;"><strong>Filter:</strong> {{ $filterText }}</td>
        <td style="width: 40%;"><strong>Date Range:</strong> {{ $dateRangeText }}</td>
    </tr>
</table>

<div class="section-title">Summary</div>
<table class="summary-table">
    <thead>
        <tr>
            <th>Completed Sessions</th>
            <th>Solo / Shared</th>
            <th>Commissionable Amount</th>
            <th>Base Pool Rate</th>
            <th>Average / Session</th>
            <th>Total Commission</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $completedCount }}</td>
            <td>{{ $soloCount }} / {{ $multiCount }}</td>
            <td>PHP {{ number_format($totalNet, 2) }}</td>
            <td>30.0%</td>
            <td>PHP {{ number_format($avgCommission, 2) }}</td>
            <td>PHP {{ number_format($totalCommission, 2) }}</td>
        </tr>
    </tbody>
</table>

<div class="section-title">Commission Breakdown</div>
@if($commissionRows->isEmpty())
    <div class="empty-state">
        No completed sessions found for {{ $staffNameText }} within {{ $dateRangeText }} ({{ $filterText }}).
    </div>
@else
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 11%;">Reference #</th>
                <th style="width: 14%;">Completion Date</th>
                <th style="width: 24%;">Service / Package</th>
                <th style="width: 15%;">Client</th>
                <th style="width: 11%;">Assignment</th>
                <th style="width: 9%;" class="text-right">Commissionable</th>
                <th style="width: 7%;" class="text-center">Rate</th>
                <th style="width: 9%;" class="text-right">Commission</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commissionRows as $row)
                @php
                    $sharePct = (float) ($row['share_percent'] ?? 100);
                    $effectiveRate = round(30 * ($sharePct / 100), 1);
                @endphp
                <tr>
                    <td>#{{ $row['ref'] ?? $row['id'] }}</td>
                    <td>
                        {{ $row['date'] ?? 'N/A' }}
                        <span class="sub-note">{{ $row['time'] ?? '' }}</span>
                    </td>
                    <td>
                        {{ $row['service_name'] ?? 'Spa Session' }}
                        @if(!empty($row['room_name']))
                            <span class="sub-note">Room: {{ $row['room_name'] }}</span>
                        @endif
                    </td>
                    <td>{{ $row['client_name'] ?? 'Walk-in Guest' }}</td>
                    <td>
                        {{ $row['assignment_type'] ?? 'Solo' }}
                        <span class="sub-note">{{ number_format($sharePct, 1) }}% share</span>
                    </td>
                    <td class="text-right">PHP {{ number_format((float) ($row['net_amount'] ?? 0), 2) }}</td>
                    <td class="text-center">{{ number_format($effectiveRate, 1) }}%</td>
                    <td class="text-right"><strong>PHP {{ number_format((float) ($row['my_commission'] ?? 0), 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right">TOTAL ({{ $completedCount }} {{ $completedCount === 1 ? 'session' : 'sessions' }})</td>
                <td class="text-right">PHP {{ number_format($totalNet, 2) }}</td>
                <td class="text-center">—</td>
                <td class="text-right">PHP {{ number_format($totalCommission, 2) }}</td>
            </tr>
        </tfoot>
    </table>
@endif

<div class="doc-footer">
    Generated on {{ $generatedText }} &middot; ZenFlow Spa Management System
</div>
</body>
</html>
