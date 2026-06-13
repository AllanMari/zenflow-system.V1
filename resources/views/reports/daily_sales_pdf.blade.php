<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Daily Sales Report - {{ $dateDisplay }}</title>
    <style>
        @page { margin: 20px; size: A4 portrait; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'DejaVu Sans', 'Inter', Arial, sans-serif; 
            font-size: 11px; 
            color: #1f2937;
            line-height: 1.4;
            padding: 20px;
        }
        
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #0d9488; }
        .header h1 { font-size: 20px; font-weight: 800; letter-spacing: 1px; margin-bottom: 4px; }
        .header .subtitle { font-size: 13px; font-weight: 700; margin-top: 4px; }
        .header .date-line { margin-top: 10px; font-size: 12px; font-weight: 600; }
        .header .date-line span { border-bottom: 1px solid #000; display: inline-block; min-width: 100px; text-align: center; padding: 0 8px; }
        
        table.main-table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 11px; 
            margin-top: 10px;
        }
        table.main-table th { 
            background: #f3f4f6; 
            border: 1px solid #6b7280; 
            padding: 6px 5px; 
            text-align: center; 
            font-size: 10px; 
            font-weight: 700; 
            color: #374151;
        }
        table.main-table td { 
            border: 1px solid #9ca3af; 
            padding: 5px 6px; 
            text-align: center; 
            vertical-align: middle;
        }
        table.main-table td:nth-child(2) { text-align: left; }
        table.main-table td:nth-child(4),
        table.main-table td:nth-child(5),
        table.main-table td:nth-child(6) { text-align: right; }
        
        .total-row { background: #f3f4f6; font-weight: 800; }
        .total-row td { border-top: 2px solid #6b7280; border-bottom: 2px solid #6b7280; }
        
        .signatures { margin-top: 30px; width: 100%; }
        .signatures td { width: 50%; padding: 0 20px; vertical-align: top; border: none; }
        .signatures .line { border-bottom: 1px solid #374151; height: 30px; }
        .signatures .label { font-size: 9px; text-align: center; text-transform: uppercase; font-weight: 700; margin-top: 3px; }
        .signatures .name { font-size: 10px; text-align: center; margin-top: 2px; }
        
        .footer { margin-top: 16px; font-size: 9px; color: #9ca3af; text-align: right; }
        
        /* Hide ALL mobile/responsive elements */
        .no-print, .mobile-only, .md\\:hidden, .sm\\:hidden, 
        .lg\\:hidden, .xl\\:hidden, .hidden, [class*="hidden"] {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SPA ALEXANDRIA</h1>
        <div class="subtitle">{{ $reportTitle }}</div>
        <div class="date-line">
            {{ $dateLabel }} <span>{{ $dateDisplay }}</span>
        </div>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 7%;">CODE</th>
                <th style="width: 38%; text-align: left;">SERVICES</th>
                <th style="width: 10%;">TOTAL<br>SERVICES</th>
                <th style="width: 15%; text-align: right;">GROSS<br>AMOUNT</th>
                <th style="width: 12%; text-align: right;">DEDUCTIONS<br>DISCOUNT</th>
                <th style="width: 15%; text-align: right;">NET</th>
            </tr>
        </thead>
        <tbody>
            @forelse($printServiceSummary as $row)
            <tr>
                <td style="font-weight: 700;">{{ $row['code'] }}</td>
                <td style="text-align: left;">{{ $row['name'] }}</td>
                <td>{{ $row['count'] }}</td>
                <td style="text-align: right;">{{ number_format($row['gross'], 2) }}</td>
                <td style="text-align: right;">{{ number_format($row['discount'], 2) }}</td>
                <td style="text-align: right; font-weight: 600;">{{ number_format($row['net'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #6b7280; padding: 20px;">
                    No service sales for this period.
                </td>
            </tr>
            @endforelse

            <tr class="total-row">
                <td style="text-align: center;">TOTAL</td>
                <td></td>
                <td style="text-align: center;">{{ $pTotalCount }}</td>
                <td style="text-align: right;">{{ number_format($pTotalGross, 2) }}</td>
                <td style="text-align: right;">{{ number_format($pTotalDiscount, 2) }}</td>
                <td style="text-align: right;">{{ number_format($pTotalNet, 2) }}</td>
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
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated {{ $generatedAt }} &bull; {{ $preparedBy }}
    </div>
</body>
</html>