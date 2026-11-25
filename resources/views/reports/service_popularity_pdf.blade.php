<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle ?? 'Service Popularity Breakdown Report' }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 12mm 14mm 12mm;
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

        .filter-table,
        .summary-table,
        .report-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 8px;
        }

        .filter-table td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            font-size: 8px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .filter-label {
            display: block;
            font-size: 6.8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #4b5563;
            margin-bottom: 1px;
        }

        .filter-value {
            font-weight: bold;
            color: #000000;
        }

        .summary-table {
            page-break-inside: avoid;
        }

        .summary-table th {
            background: #f3f4f6;
            color: #111111;
            border: 1px solid #d1d5db;
            border-bottom: 1.5px solid #111111;
            padding: 4px 5px;
            font-size: 6.8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: center;
        }

        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 5px 4px;
            font-size: 9.5px;
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
            padding: 5px 6px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
        }

        .report-table td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
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

        .muted {
            color: #4b5563;
            font-size: 7.4px;
        }

        .included-list {
            margin-top: 2px;
            font-size: 7.4px;
            color: #374151;
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
    $reportTitle = $reportTitle ?? 'SERVICE POPULARITY BREAKDOWN REPORT';
    $dateText = $dateDisplay ?? ($dateRange ?? 'All Dates');
    $categoryText = $selectedCategoryName ?? 'All Categories';
    $preparedByName = $preparedBy ?? 'Administrator';
    $reviewedByName = !empty($reviewedBy) ? $reviewedBy : null;
    $generatedText = $generatedAt instanceof \Carbon\CarbonInterface
        ? $generatedAt->format('F j, Y g:i A')
        : ($generatedAt ?? now('Asia/Manila')->format('F j, Y g:i A'));

    $summaryData = is_array($summary ?? null) ? $summary : [];
    $completedAppointments = (int) ($summaryData['completed_appointments'] ?? 0);
    $servicesCompleted = (int) ($summaryData['services_completed'] ?? 0);
    $servicesRepresented = (int) ($summaryData['services_represented'] ?? 0);
    $serviceRevenue = (float) ($summaryData['service_revenue'] ?? 0);
    $packagesBooked = (int) ($summaryData['packages_booked'] ?? 0);
    $packageRevenue = (float) ($summaryData['package_revenue'] ?? 0);

    $servicesList = collect($serviceBreakdown ?? []);
    $packagesList = collect($packageBreakdown ?? []);
    $categoriesList = collect($categoryBreakdown ?? []);
@endphp

@include('partials.report_header', [
    'reportTitle' => $reportTitle,
    'dateRange' => $dateText,
    'referenceNumber' => $referenceNumber ?? null,
    'generatedAt' => $generatedText,
    'preparedBy' => $preparedByName,
])

<table class="filter-table">
    <tr>
        <td style="width: 40%;">
            <span class="filter-label">Period / Date Range</span>
            <span class="filter-value">{{ $dateText }}</span>
        </td>
        <td style="width: 35%;">
            <span class="filter-label">Category Filter</span>
            <span class="filter-value">{{ $categoryText }}</span>
        </td>
        <td style="width: 25%;">
            <span class="filter-label">Prepared By</span>
            <span class="filter-value">{{ $preparedByName }}</span>
        </td>
    </tr>
</table>

<table class="summary-table">
    <thead>
        <tr>
            <th>Completed Appts</th>
            <th>Services Rendered</th>
            <th>Catalog Services</th>
            <th>Service Revenue</th>
            <th>Packages Booked</th>
            <th>Package Revenue</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ number_format($completedAppointments) }}</td>
            <td>{{ number_format($servicesCompleted) }}</td>
            <td>{{ number_format($servicesRepresented) }}</td>
            <td>&#8369;{{ number_format($serviceRevenue, 2) }}</td>
            <td>{{ number_format($packagesBooked) }}</td>
            <td>&#8369;{{ number_format($packageRevenue, 2) }}</td>
        </tr>
    </tbody>
</table>

<div class="section-title">Individual Service Breakdown</div>
@if($servicesList->isNotEmpty())
    <table class="report-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 7%;" class="text-center">Rank</th>
                <th style="width: 12%;">Code</th>
                <th style="width: 31%;">Service Name</th>
                <th style="width: 18%;">Category</th>
                <th style="width: 10%;" class="text-right">Count</th>
                <th style="width: 10%;" class="text-right">Share</th>
                <th style="width: 12%;" class="text-right">Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach($servicesList as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="muted">{{ data_get($row, 'code') ?: '—' }}</td>
                    <td class="bold">{{ data_get($row, 'name', 'Unnamed Service') }}</td>
                    <td>{{ data_get($row, 'category', 'Uncategorized') }}</td>
                    <td class="text-right bold">{{ number_format((int) data_get($row, 'completed_count', 0)) }}</td>
                    <td class="text-right">{{ number_format((float) data_get($row, 'share', 0), 1) }}%</td>
                    <td class="text-right">&#8369;{{ number_format((float) data_get($row, 'revenue', 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total Individual Services ({{ $servicesList->count() }})</td>
                <td class="text-right">{{ number_format($servicesList->sum(fn ($r) => (int) data_get($r, 'completed_count', 0))) }}</td>
                <td class="text-right">100.0%</td>
                <td class="text-right">&#8369;{{ number_format($servicesList->sum(fn ($r) => (float) data_get($r, 'revenue', 0)), 2) }}</td>
            </tr>
        </tfoot>
    </table>
@else
    <div class="empty-state">No individual service records matched the selected filters.</div>
@endif

<div class="section-title">Package Breakdown</div>
@if($packagesList->isNotEmpty())
    <table class="report-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 7%;" class="text-center">Rank</th>
                <th style="width: 12%;">Code</th>
                <th style="width: 45%;">Package &amp; Included Services</th>
                <th style="width: 14%;">Category</th>
                <th style="width: 10%;" class="text-right">Booked</th>
                <th style="width: 12%;" class="text-right">Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach($packagesList as $index => $pkg)
                @php
                    $includedNames = collect(data_get($pkg, 'included_services', []))
                        ->map(fn ($item) => is_array($item) ? ($item['name'] ?? '') : data_get($item, 'name', ''))
                        ->filter()
                        ->implode(', ');
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="muted">{{ data_get($pkg, 'code') ?: '—' }}</td>
                    <td>
                        <div class="bold">{{ data_get($pkg, 'name', 'Unnamed Package') }}</div>
                        @if($includedNames !== '')
                            <div class="included-list">Includes: {{ $includedNames }}</div>
                        @endif
                    </td>
                    <td>{{ data_get($pkg, 'category', 'Uncategorized') }}</td>
                    <td class="text-right bold">{{ number_format((int) data_get($pkg, 'completed_count', 0)) }}</td>
                    <td class="text-right">&#8369;{{ number_format((float) data_get($pkg, 'revenue', 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total Packages ({{ $packagesList->count() }})</td>
                <td class="text-right">{{ number_format($packagesList->sum(fn ($p) => (int) data_get($p, 'completed_count', 0))) }}</td>
                <td class="text-right">&#8369;{{ number_format($packagesList->sum(fn ($p) => (float) data_get($p, 'revenue', 0)), 2) }}</td>
            </tr>
        </tfoot>
    </table>
@else
    <div class="empty-state">No package bookings recorded for this period.</div>
@endif

<div class="section-title">Category Breakdown</div>
@if($categoriesList->isNotEmpty())
    <table class="report-table pdf-scalable-table">
        <thead>
            <tr>
                <th style="width: 8%;" class="text-center">Rank</th>
                <th style="width: 52%;">Category Name</th>
                <th style="width: 18%;" class="text-right">Completed Count</th>
                <th style="width: 22%;" class="text-right">Attributed Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categoriesList as $index => $cat)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="bold">{{ data_get($cat, 'name', 'Uncategorized') }}</td>
                    <td class="text-right">{{ number_format((int) data_get($cat, 'completed_count', 0)) }}</td>
                    <td class="text-right">&#8369;{{ number_format((float) data_get($cat, 'revenue', 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total Categories ({{ $categoriesList->count() }})</td>
                <td class="text-right">{{ number_format($categoriesList->sum(fn ($c) => (int) data_get($c, 'completed_count', 0))) }}</td>
                <td class="text-right">&#8369;{{ number_format($categoriesList->sum(fn ($c) => (float) data_get($c, 'revenue', 0)), 2) }}</td>
            </tr>
        </tfoot>
    </table>
@else
    <div class="empty-state">No category summary available for this period.</div>
@endif

<table class="signature-table">
    <tr>
        <td>
            <div class="signature-label">Prepared by</div>
            <div class="signature-line">{{ $preparedByName }}</div>
            <div class="signature-role">Staff / Administrator</div>
        </td>
        <td>
            <div class="signature-label">Reviewed by</div>
            <div class="signature-line">{{ $reviewedByName ?: '____________________________' }}</div>
            <div class="signature-role">Manager / Owner</div>
        </td>
    </tr>
</table>

<div class="doc-footer">
    ZenFlow Spa Management System &bull; Service Popularity Breakdown &bull; Generated {{ $generatedText }}
</div>
</body>
</html>
