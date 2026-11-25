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
            color: #000000;
            background: #ffffff;
            font-size: 9px;
            margin: 0;
            line-height: 1.35;
        }

        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 12px 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #111111;
            color: #000000;
        }

        table.summary-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 12px;
        }

        table.summary-table th {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            font-size: 7.5px;
            font-weight: 700;
            color: #333333;
            text-transform: uppercase;
            text-align: center;
        }

        table.summary-table td {
            border: 1px solid #d1d5db;
            padding: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #000000;
            text-align: center;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 12px;
        }

        table.report-table thead th {
            background: #f3f4f6;
            color: #000000;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: left;
            padding: 6px 6px;
            border-top: 1px solid #111111;
            border-bottom: 1px solid #111111;
            border-left: 1px solid #d1d5db;
            border-right: 1px solid #d1d5db;
        }

        table.report-table td {
            font-size: 8.5px;
            padding: 5.5px 6px;
            border: 1px solid #d1d5db;
            vertical-align: top;
            color: #000000;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .empty {
            text-align: center;
            padding: 18px;
            border: 1px solid #d1d5db;
            color: #4b5563;
        }

        .footer {
            border-top: 1px solid #d1d5db;
            margin-top: 16px;
            padding-top: 6px;
            font-size: 7.5px;
            color: #4b5563;
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

    <div class="section-title">Inventory Summary</div>
    <table class="summary-table">
        <tr>
            <th>Total Rooms</th>
            <th>Active</th>
            <th>Available</th>
            <th>Occupied</th>
            <th>Maintenance</th>
            <th>Inactive</th>
        </tr>
        <tr>
            <td>{{ $summary['total_rooms'] }}</td>
            <td>{{ $summary['active_rooms'] }}</td>
            <td>{{ $summary['available_rooms'] }}</td>
            <td>{{ $summary['occupied_rooms'] }}</td>
            <td>{{ $summary['maintenance_rooms'] }}</td>
            <td>{{ $summary['inactive_rooms'] }}</td>
        </tr>
    </table>

    <div class="section-title">Room Inventory</div>
    @if($rooms->isNotEmpty())
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
                        <td style="text-align: center;">{{ $room->is_active ? 'Yes' : 'No' }}</td>
                        <td>{{ $room->notes ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No rooms found.</div>
    @endif

    <div class="footer">
        Generated {{ $generatedAt }} &bull; {{ $preparedBy }} &bull; ZenFlow System — Spa Alexandria
    </div>
</body>
</html>
