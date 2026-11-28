<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }}</title>
    <style>
        @page {
            margin: 35px;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            background: #ffffff;
            font-size: 9px;
            margin: 0;
            line-height: 1.4;
        }

        .section-title {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.45px;
            margin: 14px 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #111111;
            color: #111111;
        }

        table.meta-summary-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 10px;
        }

        table.meta-summary-table td {
            font-size: 8.5px;
            padding: 4.5px 6px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            color: #111827;
        }

        table.meta-summary-table td.label-col {
            width: 34%;
            font-weight: 700;
            color: #374151;
        }

        table.meta-summary-table td.val-col {
            width: 16%;
            text-align: right;
            font-weight: 600;
        }

        table.meta-summary-table td.sep-col {
            width: 4%;
            border-bottom: 0;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 10px;
        }

        table.report-table thead {
            display: table-header-group;
        }

        table.report-table tr {
            page-break-inside: avoid;
        }

        table.report-table thead th {
            background: #f3f4f6;
            color: #111111;
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            text-align: left;
            padding: 5.5px 5px;
            border-top: 1.5px solid #111111;
            border-bottom: 1.5px solid #111111;
            border-left: 1px solid #d1d5db;
            border-right: 1px solid #d1d5db;
        }

        table.report-table td {
            font-size: 8.2px;
            padding: 5px 5px;
            border: 1px solid #d1d5db;
            vertical-align: top;
            color: #111827;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.report-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        table.report-table tfoot td {
            background: #f3f4f6;
            border-top: 1.5px solid #111111;
            font-weight: 700;
            font-size: 8.2px;
            color: #111111;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            font-size: 7.2px;
            color: #4b5563;
            font-weight: 400;
        }

        .notes-box {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #d1d5db;
            font-size: 7.6px;
            color: #374151;
            line-height: 1.45;
            page-break-inside: avoid;
        }

        .notes-box ul {
            margin: 3px 0 0 14px;
            padding: 0;
        }

        .notes-box li {
            margin-bottom: 2px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
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
            font-weight: 700;
            text-transform: uppercase;
            color: #4b5563;
            margin-bottom: 18px;
        }

        .signature-line {
            width: 82%;
            border-top: 1px solid #111111;
            padding-top: 3px;
            font-size: 8.5px;
            font-weight: 700;
            color: #111111;
        }

        .signature-role {
            font-size: 7.2px;
            color: #4b5563;
        }

        .empty {
            text-align: center;
            padding: 16px;
            border: 1px solid #d1d5db;
            color: #4b5563;
        }

        .doc-footer {
            border-top: 1px solid #d1d5db;
            margin-top: 12px;
            padding-top: 5px;
            font-size: 7.2px;
            color: #4b5563;
            text-align: right;
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

    @php
        $hasUtilization = isset($roomRows) && count($roomRows) > 0;
        $bottleneckData = $bottlenecks ?? [];
        $highUtilList = !empty($bottleneckData['high_utilization_rooms'])
            ? implode(', ', $bottleneckData['high_utilization_rooms'])
            : 'None';
    @endphp

    <div class="section-title">1. Inventory &amp; Utilization Summary</div>
    <table class="meta-summary-table">
        <tr>
            <td class="label-col">Total Rooms</td>
            <td class="val-col">{{ $summary['total_rooms'] ?? 0 }}</td>
            <td class="sep-col"></td>
            <td class="label-col">Completed Occupancy</td>
            <td class="val-col">{{ $summary['completed_formatted'] ?? '0m' }} ({{ $summary['completed_count'] ?? 0 }})</td>
        </tr>
        <tr>
            <td class="label-col">Currently Operational Rooms</td>
            <td class="val-col">{{ $summary['operational_rooms'] ?? ($summary['active_rooms'] ?? 0) }}</td>
            <td class="sep-col"></td>
            <td class="label-col">Scheduled Occupancy</td>
            <td class="val-col">{{ $summary['scheduled_formatted'] ?? '0m' }} ({{ $summary['scheduled_count'] ?? 0 }})</td>
        </tr>
        <tr>
            <td class="label-col">Open Operating Days in Period</td>
            <td class="val-col">{{ $summary['open_days_count'] ?? 0 }}</td>
            <td class="sep-col"></td>
            <td class="label-col">5-Min Prep Buffer (Separate)</td>
            <td class="val-col">{{ $summary['buffer_minutes'] ?? 0 }}m ({{ $summary['buffer_gaps_count'] ?? 0 }} buffers)</td>
        </tr>
        <tr>
            <td class="label-col">Operational Room Capacity</td>
            <td class="val-col">{{ $summary['available_formatted'] ?? 'N/A' }}</td>
            <td class="sep-col"></td>
            <td class="label-col">Overall Operational Utilization</td>
            <td class="val-col">{{ isset($summary['overall_utilization']) && $summary['overall_utilization'] !== null ? number_format((float) $summary['overall_utilization'], 1) . '%' : 'N/A' }}</td>
        </tr>
    </table>

    @if(!empty($bottleneckData))
        <div class="section-title">2. Room Demand &amp; Bottleneck Analysis</div>
        <table class="meta-summary-table">
            <tr>
                <td class="label-col">Highest Scheduled Room</td>
                <td class="val-col" style="width: 24%;">
                    {{ $bottleneckData['highest_room']['name'] ?? '—' }}
                    @if(!empty($bottleneckData['highest_room']))
                        ({{ $bottleneckData['highest_room']['scheduled_formatted'] ?? '0m' }})
                    @endif
                </td>
                <td class="sep-col"></td>
                <td class="label-col" style="width: 22%;">Peak Demand Day</td>
                <td class="val-col" style="width: 16%;">{{ !empty($bottleneckData['peak_day']) ? $bottleneckData['peak_day'] . ' (' . $bottleneckData['peak_day_count'] . ')' : '—' }}</td>
            </tr>
            <tr>
                <td class="label-col">Lowest Scheduled Room</td>
                <td class="val-col" style="width: 24%;">
                    {{ $bottleneckData['lowest_room']['name'] ?? '—' }}
                    @if(!empty($bottleneckData['lowest_room']))
                        ({{ $bottleneckData['lowest_room']['scheduled_formatted'] ?? '0m' }})
                    @endif
                </td>
                <td class="sep-col"></td>
                <td class="label-col" style="width: 22%;">Peak Hour Slot</td>
                <td class="val-col" style="width: 16%;">{{ !empty($bottleneckData['peak_hour']) ? $bottleneckData['peak_hour'] . ' (' . $bottleneckData['peak_hour_count'] . ')' : '—' }}</td>
            </tr>
            <tr>
                <td class="label-col">High-Utilization Rooms (&ge;75%)</td>
                <td colspan="4" style="text-align: right; font-weight: 600;">{{ $highUtilList }}</td>
            </tr>
        </table>
    @endif

    <div class="section-title">3. Room-by-Room Utilization Breakdown</div>
    @if($hasUtilization)
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 18%;">Room</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 9%; text-align: center;">Comp / Res</th>
                    <th style="width: 8%; text-align: center;">Canc / NS</th>
                    <th style="width: 12%; text-align: right;">Completed Occ.</th>
                    <th style="width: 12%; text-align: right;">Scheduled Occ.</th>
                    <th style="width: 11%; text-align: right;">Prep Buffer</th>
                    <th style="width: 12%; text-align: right;">Available Cap.</th>
                    <th style="width: 8%; text-align: right;">Util %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roomRows as $row)
                    <tr>
                        <td style="font-weight: 700;">
                            {{ $row['name'] }}
                            <div class="muted">{{ $row['categories'] }}</div>
                        </td>
                        <td>{{ !$row['is_active'] ? 'Inactive' : ucfirst($row['status']) }}</td>
                        <td class="text-center">{{ $row['completed_count'] }} / {{ $row['confirmed_count'] + $row['pending_count'] }}</td>
                        <td class="text-center">{{ $row['cancelled_no_show_count'] }}</td>
                        <td class="text-right">{{ $row['completed_formatted'] }}</td>
                        <td class="text-right" style="font-weight: 700;">{{ $row['scheduled_formatted'] }}</td>
                        <td class="text-right">{{ $row['buffer_minutes'] }}m ({{ $row['buffer_gaps_count'] }})</td>
                        <td class="text-right">{{ $row['available_formatted'] }}</td>
                        <td class="text-right" style="font-weight: 700;">{{ $row['utilization_percent'] !== null ? number_format((float) $row['utilization_percent'], 1) . '%' : 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Total / Overall (Operational Capacity)</td>
                    <td class="text-center">{{ $summary['completed_count'] ?? 0 }} / {{ ($summary['confirmed_count'] ?? 0) + ($summary['pending_count'] ?? 0) }}</td>
                    <td class="text-center">{{ $summary['cancelled_no_show_count'] ?? 0 }}</td>
                    <td class="text-right">{{ $summary['completed_formatted'] ?? '0m' }}</td>
                    <td class="text-right">{{ $summary['scheduled_formatted'] ?? '0m' }}</td>
                    <td class="text-right">{{ $summary['buffer_minutes'] ?? 0 }}m ({{ $summary['buffer_gaps_count'] ?? 0 }})</td>
                    <td class="text-right">{{ $summary['available_formatted'] ?? 'N/A' }}</td>
                    <td class="text-right">{{ isset($summary['overall_utilization']) && $summary['overall_utilization'] !== null ? number_format((float) $summary['overall_utilization'], 1) . '%' : 'N/A' }}</td>
                </tr>
            </tfoot>
        </table>
    @elseif($rooms->isNotEmpty())
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Room</th>
                    <th style="width: 22%;">Category</th>
                    <th style="width: 14%;">Status</th>
                    <th style="width: 10%; text-align: center;">Active</th>
                    <th style="width: 34%;">Notes / Maintenance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rooms as $room)
                    <tr>
                        <td style="font-weight: 700;">{{ $room->name }}</td>
                        <td>{{ $room->roomCategories->isNotEmpty() ? ($room->roomCategories->first()->name ?? 'Uncategorized') : ($room->category?->name ?? 'Uncategorized') }}</td>
                        <td>{{ ucfirst($room->status) }}</td>
                        <td class="text-center">{{ $room->is_active ? 'Yes' : 'No' }}</td>
                        <td>{{ $room->notes ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No rooms found.</div>
    @endif

    <div class="notes-box">
        <strong>Metric Definitions &amp; Limitations:</strong>
        <ul>
            <li><strong>Scheduled Occupancy:</strong> Completed + Confirmed + valid non-expired Pending appointment durations (Cancelled, No-Show, legacy customer no-show, and expired Pending reservations excluded; overlapping intervals merged).</li>
            <li><strong>5-Minute Preparation Buffer:</strong> Reported separately as 5 minutes per scheduled booking to reflect scheduling turnaround allowance, not measured physical cleaning time.</li>
            <li><strong>Available Capacity &amp; Utilization %:</strong> Utilization % = Scheduled Occupancy &divide; Available Room Minutes &times; 100, using business operating hours and closures for currently operational rooms. Historical maintenance/deactivation dates are not logged, so currently inactive or maintenance rooms show <code>N/A</code> for utilization % and are excluded from the operational overall utilization denominator.</li>
        </ul>
    </div>

    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-label">Prepared by</div>
                <div class="signature-line">{{ $preparedBy ?? 'Administrator' }}</div>
                <div class="signature-role">Staff / Administrator</div>
            </td>
            <td>
                <div class="signature-label">Checked by</div>
                <div class="signature-line">{{ !empty($checkedBy) ? $checkedBy : '____________________________' }}</div>
                <div class="signature-role">Manager / Owner</div>
            </td>
        </tr>
    </table>

    <div class="doc-footer">
        ZenFlow Spa Management System &bull; Spa Alexandria &bull; Generated {{ $generatedAt }} (Not an official BIR document)
    </div>
</body>
</html>
