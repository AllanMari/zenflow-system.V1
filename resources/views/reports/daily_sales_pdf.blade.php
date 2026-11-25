<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Sales Report - {{ $dateDisplay }}</title>
    <style>
        @page {
            margin: 35px;
            size: A4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: #000000;
            background: #ffffff;
            line-height: 1.35;
        }

        table.main-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9px;
            margin-top: 6px;
        }

        table.main-table th {
            background: #f3f4f6;
            border: 1px solid #111111;
            padding: 6px 5px;
            text-align: center;
            font-size: 8px;
            font-weight: 700;
            color: #000000;
            text-transform: uppercase;
        }

        table.main-table td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            text-align: center;
            vertical-align: middle;
            color: #000000;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.main-table td:nth-child(2) { text-align: left; }
        table.main-table td:nth-child(4),
        table.main-table td:nth-child(5),
        table.main-table td:nth-child(6) { text-align: right; white-space: nowrap; }

        .total-row {
            background: #f3f4f6;
            font-weight: 700;
        }

        .total-row td {
            border-top: 1.5px solid #111111;
            border-bottom: 1.5px solid #111111;
            font-size: 9.5px;
            color: #000000;
        }

        .signatures {
            margin-top: 28px;
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signatures td {
            width: 50%;
            padding: 0 24px;
            vertical-align: top;
            border: none;
        }

        .signatures .line {
            border-bottom: 1px solid #111111;
            height: 26px;
        }

        .signatures .label {
            font-size: 7.5px;
            text-align: center;
            text-transform: uppercase;
            font-weight: 700;
            color: #4b5563;
            margin-top: 3px;
        }

        .signatures .name {
            font-size: 9px;
            text-align: center;
            font-weight: 700;
            color: #000000;
            margin-top: 2px;
        }

        .footer {
            margin-top: 14px;
            padding-top: 6px;
            border-top: 1px solid #d1d5db;
            font-size: 7.5px;
            color: #4b5563;
            text-align: right;
        }

        .no-print, .mobile-only, .md\:hidden, .sm\:hidden,
        .lg\:hidden, .xl\:hidden, .hidden, [class*="hidden"] {
            display: none !important;
        }
    </style>
    @include('partials.pdf_page_styles')
</head>
<body>
    @include('partials.report_header', [
        'reportTitle' => $reportTitle ?? 'Daily Sales Report',
        'dateRange' => $dateDisplay ?? ($dateRange ?? null),
    ])

    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 9%;">CODE</th>
                <th style="width: 38%; text-align: left;">SERVICES</th>
                <th style="width: 11%;">TOTAL<br>SERVICES</th>
                <th style="width: 15%; text-align: right;">GROSS<br>AMOUNT</th>
                <th style="width: 12%; text-align: right;">DISCOUNT</th>
                <th style="width: 15%; text-align: right;">NET</th>
            </tr>
        </thead>
        <tbody>
            @forelse($printServiceSummary as $row)
            <tr>
                <td style="font-weight: 700;">{{ $row['code'] }}</td>
                <td style="text-align: left;">{{ $row['name'] }}</td>
                <td>{{ $row['count'] }}</td>
                <td>₱{{ number_format((float) $row['gross'], 2) }}</td>
                <td>₱{{ number_format((float) $row['discount'], 2) }}</td>
                <td style="font-weight: 700;">₱{{ number_format((float) $row['net'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #4b5563; padding: 18px;">
                    No service sales for this period.
                </td>
            </tr>
            @endforelse

            <tr class="total-row">
                <td style="text-align: center;">TOTAL</td>
                <td></td>
                <td style="text-align: center;">{{ $pTotalCount }}</td>
                <td>₱{{ number_format((float) $pTotalGross, 2) }}</td>
                <td>₱{{ number_format((float) $pTotalDiscount, 2) }}</td>
                <td>₱{{ number_format((float) $pTotalNet, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div class="line"></div>
                <div class="label">Prepared By</div>
                <div class="name">{{ $preparedBy }}</div>
            </td>
            <td>
                <div class="line"></div>
                <div class="label">Checked By</div>
                @if(!empty($checkedBy))
                    <div class="name">{{ $checkedBy }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated {{ $generatedAt }} &bull; {{ $preparedBy }} &bull; ZenFlow System — Spa Alexandria
    </div>
</body>
</html>
