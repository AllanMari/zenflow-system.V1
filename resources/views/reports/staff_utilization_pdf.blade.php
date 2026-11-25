<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>{{ $reportTitle }}</title>

    <style>
        @page {
            margin: 35px;
            size: A4 landscape;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 10px;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #0f766e;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .brand {
            font-size: 18px;
            font-weight: bold;
            color: #0f766e;
            margin-bottom: 3px;
        }

        .spa-name {
            font-size: 11px;
            color: #4b5563;
        }

        .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
            margin-top: 14px;
            margin-bottom: 4px;
        }

        .generated {
            font-size: 9px;
            color: #6b7280;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .summary td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
        }

        .summary-label {
            font-size: 7px;
            color: #6b7280;
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 13px;
            font-weight: bold;
            margin-top: 3px;
            color: #111827;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 12px 0 7px 0;
            color: #111827;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.report-table thead {
            background: #0f766e;
            color: #ffffff;
        }

        table.report-table th {
            font-size: 8px;
            font-weight: bold;
            text-align: left;
            padding: 7px 5px;
            border: 1px solid #0f766e;
        }

        table.report-table td {
            font-size: 8px;
            padding: 6px 5px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        table.report-table tr:nth-child(even) {
            background: #f9fafb;
        }

        .muted {
            color: #6b7280;
        }

        .footer {
            border-top: 1px solid #d1d5db;
            margin-top: 20px;
            padding-top: 8px;
            font-size: 8px;
            color: #6b7280;
        }

        .empty {
            text-align: center;
            padding: 25px;
            border: 1px solid #d1d5db;
            color: #6b7280;
        }
    </style>
    @include('partials.pdf_page_styles')
</head>

<body>
    @include('partials.report_header', [
        'reportTitle' => $reportTitle,
        'referenceNumber' => $referenceNumber,
        'dateRange' => $dateRange,
        'generatedAt' => $generatedAt,
        'preparedBy' => $preparedBy,
    ])

    <div class="section-title">Summary</div>
    <table class="summary">
        <tr>
            <td>
                <div class="summary-label">Staff Members</div>
                <div class="summary-value">{{ $summary['staff_count'] }}</div>
            </td>
            <td>
                <div class="summary-label">Appointments</div>
                <div class="summary-value">{{ $summary['appointments'] }}</div>
            </td>
            <td>
                <div class="summary-label">Completed</div>
                <div class="summary-value">{{ $summary['completed'] }}</div>
            </td>
            <td>
                <div class="summary-label">Service Hours</div>
                <div class="summary-value">{{ number_format((float) $summary['service_hours'], 2) }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="summary-label">Clocked Hours</div>
                <div class="summary-value">{{ number_format((float) $summary['clocked_hours'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Overall Utilization</div>
                <div class="summary-value">{{ number_format((float) $summary['overall_utilization'], 1) }}%</div>
            </td>
            <td>
                <div class="summary-label">Revenue</div>
                <div class="summary-value">₱{{ number_format((float) $summary['revenue'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Revenue / Hour</div>
                <div class="summary-value">₱{{ number_format((float) $summary['revenue_per_hour'], 2) }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Staff Utilization Detail</div>
    @if($staff->isNotEmpty())
        <table class="report-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Staff</th>
                    <th>Appointments</th>
                    <th>Completed</th>
                    <th>Service Hours</th>
                    <th>Clocked Hours</th>
                    <th>Utilization</th>
                    <th>Revenue</th>
                    <th>Revenue / Hour</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staff as $staffMember)
                    <tr>
                        <td>{{ $staffMember['rank'] ?? '' }}</td>
                        <td>{{ $staffMember['name'] ?? '' }}</td>
                        <td>{{ $staffMember['appointments'] ?? 0 }}</td>
                        <td>{{ $staffMember['completed'] ?? 0 }}</td>
                        <td>{{ number_format((float) ($staffMember['service_hours'] ?? 0), 2) }}</td>
                        <td>{{ number_format((float) ($staffMember['clocked_hours'] ?? 0), 2) }}</td>
                        <td>{{ number_format((float) ($staffMember['utilization'] ?? 0), 1) }}%</td>
                        <td>₱{{ number_format((float) ($staffMember['revenue'] ?? 0), 2) }}</td>
                        <td>₱{{ number_format((float) ($staffMember['revenue_per_hour'] ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No utilization data</div>
    @endif

    <div class="footer">
        Generated {{ $generatedAt }} • {{ $preparedBy }}
        <div>ZenFlow System — Spa Alexandria</div>
    </div>
</body>

</html>
