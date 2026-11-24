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
                <div class="summary-label">Total Rooms</div>
                <div class="summary-value">{{ $summary['total_rooms'] }}</div>
            </td>
            <td>
                <div class="summary-label">Active Rooms</div>
                <div class="summary-value">{{ $summary['active_rooms'] }}</div>
            </td>
            <td>
                <div class="summary-label">Available Rooms</div>
                <div class="summary-value">{{ $summary['available_rooms'] }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="summary-label">Occupied Rooms</div>
                <div class="summary-value">{{ $summary['occupied_rooms'] }}</div>
            </td>
            <td>
                <div class="summary-label">Maintenance Rooms</div>
                <div class="summary-value">{{ $summary['maintenance_rooms'] }}</div>
            </td>
            <td>
                <div class="summary-label">Inactive Rooms</div>
                <div class="summary-value">{{ $summary['inactive_rooms'] }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Room Inventory</div>
    @if($rooms->isNotEmpty())
        <table class="report-table">
            <thead>
                <tr>
                    <th>Room</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Active</th>
                    <th>Notes / Maintenance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rooms as $room)
                    <tr>
                        <td>{{ $room->name }}</td>
                        <td>{{ $room->roomCategories->isNotEmpty() ? ($room->roomCategories->first()->name ?? 'Uncategorized') : ($room->category?->name ?? 'Uncategorized') }}</td>
                        <td>{{ ucfirst($room->status) }}</td>
                        <td>{{ $room->is_active ? 'Yes' : 'No' }}</td>
                        <td>{{ $room->notes ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No rooms found</div>
    @endif

    <div class="footer">
        Generated {{ $generatedAt }} • {{ $preparedBy }}
        <div>ZenFlow System — Spa Alexandria</div>
    </div>
</body>

</html>
