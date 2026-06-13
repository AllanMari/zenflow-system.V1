<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Business Intelligence Report - {{ $startDate->format('F d, Y') }}</title>
    <style>
        @page { 
            margin: 0; 
            size: A4 portrait; 
        }
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        body { 
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif; 
            font-size: 10px; 
            color: #1e293b;
            line-height: 1.5;
            background: #ffffff;
        }

        /* ===== HERO HEADER ===== */
        .hero-header {
            background: #0f766e;
            color: white;
            padding: 30px 35px 25px;
            position: relative;
        }
        .hero-header .gold-line {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #fbbf24;
        }
        .hero-top {
            margin-bottom: 20px;
        }
        .hero-top::after {
            content: '';
            display: table;
            clear: both;
        }
        .brand {
            float: left;
        }
        .brand h1 {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .brand .tagline {
            font-size: 9px;
            opacity: 0.7;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 3px;
        }
        .report-badge {
            float: right;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            padding: 8px 16px;
            text-align: center;
        }
        .report-badge .label {
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.7;
            margin-bottom: 2px;
        }
        .report-badge .value {
            font-size: 11px;
            font-weight: 700;
        }

        .hero-period {
            text-align: center;
            clear: both;
            padding-top: 10px;
        }
        .hero-period .date-range {
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .hero-period .meta {
            font-size: 9px;
            opacity: 0.6;
            margin-top: 5px;
        }

        /* ===== STATS BAR ===== */
        .stats-bar {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            width: 100%;
        }
        .stats-bar td {
            width: 33.33%;
            padding: 14px 20px;
            text-align: center;
            border-right: 1px solid #e2e8f0;
        }
        .stats-bar td:last-child {
            border-right: none;
        }
        .stats-bar .stat-label {
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .stats-bar .stat-value {
            font-size: 18px;
            font-weight: 800;
            color: #0f766e;
        }

        /* ===== CONTENT ===== */
        .content {
            padding: 20px 30px 30px;
        }

        /* ===== SECTION TITLE ===== */
        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #64748b;
            margin-bottom: 12px;
            margin-top: 20px;
            border-left: 3px solid #0d9488;
            padding-left: 10px;
        }

        /* ===== KPI ROWS (DomPDF compatible - uses tables) ===== */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin-bottom: 10px;
        }
        .kpi-table td {
            width: 25%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 10px;
            text-align: center;
            vertical-align: top;
        }
        .kpi-table .kpi-icon {
            font-size: 20px;
            margin-bottom: 6px;
        }
        .kpi-table .kpi-label {
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .kpi-table .kpi-value {
            font-size: 16px;
            font-weight: 800;
            color: #1e293b;
        }
        .kpi-table .kpi-sub {
            font-size: 8px;
            color: #94a3b8;
            margin-top: 3px;
        }
        .kpi-table .kpi-trend {
            font-size: 8px;
            font-weight: 700;
            margin-top: 4px;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
        }
        .kpi-trend.up {
            background: #ecfdf5;
            color: #059669;
        }
        .kpi-trend.down {
            background: #fff1f2;
            color: #e11d48;
        }

        /* Color top borders for KPI cards */
        .kpi-table td.teal { border-top: 3px solid #0d9488; }
        .kpi-table td.blue { border-top: 3px solid #2563eb; }
        .kpi-table td.purple { border-top: 3px solid #7c3aed; }
        .kpi-table td.amber { border-top: 3px solid #d97706; }
        .kpi-table td.rose { border-top: 3px solid #e11d48; }
        .kpi-table td.emerald { border-top: 3px solid #059669; }
        .kpi-table td.cyan { border-top: 3px solid #0891b2; }
        .kpi-table td.indigo { border-top: 3px solid #4f46e5; }

        /* ===== HEALTH ROW ===== */
        .health-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin-bottom: 10px;
        }
        .health-table td {
            width: 20%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 10px;
            text-align: center;
            vertical-align: top;
        }
        .health-table .health-icon {
            font-size: 18px;
            margin-bottom: 6px;
        }
        .health-table .health-label {
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .health-table .health-value {
            font-size: 18px;
            font-weight: 800;
            color: #1e293b;
        }
        .health-table .health-status {
            font-size: 8px;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
            margin-top: 5px;
            font-weight: 600;
        }
        .health-status.excellent { background: #ecfdf5; color: #059669; }
        .health-status.good { background: #f0fdfa; color: #0d9488; }
        .health-status.warning { background: #fffbeb; color: #d97706; }
        .health-status.critical { background: #fff1f2; color: #e11d48; }

        /* ===== TABLES ===== */
        .table-wrap {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 9.5px;
        }
        .data-table thead {
            background: #f8fafc;
        }
        .data-table th { 
            color: #475569; 
            font-weight: 700; 
            padding: 10px 12px; 
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            font-size: 7px;
            letter-spacing: 1px;
        }
        .data-table td { 
            padding: 9px 12px; 
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        .data-table .rank {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: inline-block;
            text-align: center;
            line-height: 22px;
            font-size: 9px;
            font-weight: 800;
            color: white;
        }
        .rank.gold { background: #f59e0b; }
        .rank.silver { background: #94a3b8; }
        .rank.bronze { background: #ea580c; }
        .rank.other { background: #e2e8f0; color: #64748b; }

        .data-table .bar-bg {
            width: 100%;
            height: 5px;
            background: #f1f5f9;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 4px;
        }
        .data-table .bar-fill {
            height: 100%;
            border-radius: 3px;
            background: #0d9488;
        }
        .data-table .bar-fill.staff {
            background: #2563eb;
        }

        .data-table .amount {
            font-weight: 700;
            color: #0f766e;
            font-size: 10px;
        }
        .data-table .pct {
            color: #94a3b8;
            font-size: 9px;
            font-weight: 600;
        }
        .data-table .total-row {
            background: #f8fafc;
            font-weight: 700;
        }
        .data-table .total-row td {
            border-top: 2px solid #cbd5e1;
            color: #1e293b;
            font-size: 10px;
        }

        /* ===== FOOTER ===== */
        .footer-bar {
            background: #0f766e;
            color: white;
            padding: 12px 30px;
            font-size: 8px;
        }
        .footer-bar::after {
            content: '';
            display: table;
            clear: both;
        }
        .footer-left {
            float: left;
        }
        .footer-right {
            float: right;
            background: rgba(255,255,255,0.1);
            padding: 3px 10px;
            border-radius: 4px;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .text-right { text-align: right; }
        .capitalize { text-transform: capitalize; }
    </style>
</head>
<body>
    <!-- ===== HERO HEADER ===== -->
    <div class="hero-header">
        <div class="hero-top">
            <div class="brand">
                <h1>Spa Alexandria</h1>
                <div class="tagline">Business Intelligence Report</div>
            </div>
            <div class="report-badge">
                <div class="label">Report Type</div>
                <div class="value">Executive Summary</div>
            </div>
        </div>
        <div class="hero-period">
            <div class="date-range">{{ $startDate->format('F d, Y') }} — {{ $endDate->format('F d, Y') }}</div>
            <div class="meta">Generated {{ $generatedAt }} &bull; Prepared by {{ $preparedBy }}</div>
        </div>
        <div class="gold-line"></div>
    </div>

    <!-- ===== STATS BAR ===== -->
    <table class="stats-bar">
        <tr>
            <td>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">₱{{ number_format($safeTotalRevenue, 2) }}</div>
            </td>
            <td>
                <div class="stat-label">Transactions</div>
                <div class="stat-value" style="color: #1e293b;">{{ $safeTotalCount }}</div>
            </td>
            <td>
                <div class="stat-label">Unique Clients</div>
                <div class="stat-value" style="color: #1e293b;">{{ $safeUniqueCustomers }}</div>
            </td>
        </tr>
    </table>

    <!-- ===== CONTENT ===== -->
    <div class="content">

        <!-- KPI Grid -->
        <div class="section-title">Key Performance Indicators</div>
        <table class="kpi-table">
            <tr>
                <td class="teal">
                    <div class="kpi-icon">💰</div>
                    <div class="kpi-label">Total Revenue</div>
                    <div class="kpi-value">₱{{ number_format($safeTotalRevenue, 0) }}</div>
                    <div class="kpi-sub">Net collections</div>
                </td>
                <td class="blue">
                    <div class="kpi-icon">🧾</div>
                    <div class="kpi-label">Avg Ticket</div>
                    <div class="kpi-value">₱{{ number_format($safeAvgSale, 0) }}</div>
                    <div class="kpi-sub">Per transaction</div>
                </td>
                <td class="purple">
                    <div class="kpi-icon">👥</div>
                    <div class="kpi-label">Unique Clients</div>
                    <div class="kpi-value">{{ $safeUniqueCustomers }}</div>
                    <div class="kpi-sub">Distinct customers</div>
                </td>
                <td class="amber">
                    <div class="kpi-icon">🔒</div>
                    <div class="kpi-label">Deposits Held</div>
                    <div class="kpi-value">₱{{ number_format($safeDeposits ?? 0, 0) }}</div>
                    <div class="kpi-sub">Pending realization</div>
                </td>
            </tr>
        </table>
        <table class="kpi-table">
            <tr>
                <td class="emerald">
                    <div class="kpi-icon">✅</div>
                    <div class="kpi-label">Completion Rate</div>
                    <div class="kpi-value">{{ $safeCompletionRate }}%</div>
                    <div class="kpi-trend {{ $safeCompletionRate >= 80 ? 'up' : 'down' }}">
                        {{ $safeCompletionRate >= 80 ? 'Excellent' : 'Needs Work' }}
                    </div>
                </td>
                <td class="rose">
                    <div class="kpi-icon">🚫</div>
                    <div class="kpi-label">No-Show Rate</div>
                    <div class="kpi-value">{{ $safeNoShowRate }}%</div>
                    <div class="kpi-trend {{ $safeNoShowRate <= 10 ? 'up' : 'down' }}">
                        {{ $safeNoShowRate <= 10 ? 'Healthy' : 'Critical' }}
                    </div>
                </td>
                <td class="cyan">
                    <div class="kpi-icon">📉</div>
                    <div class="kpi-label">Cancellation Rate</div>
                    <div class="kpi-value">{{ $safeCancellationRate }}%</div>
                    <div class="kpi-trend {{ $safeCancellationRate <= 15 ? 'up' : 'down' }}">
                        {{ $safeCancellationRate <= 15 ? 'Acceptable' : 'High Risk' }}
                    </div>
                </td>
                <td class="indigo">
                    <div class="kpi-icon">📈</div>
                    <div class="kpi-label">Revenue Growth</div>
                    <div class="kpi-value">{{ $revenueChangeLabel ?? '0' }}%</div>
                    <div class="kpi-trend {{ ($safeRevenueChange ?? 0) >= 0 ? 'up' : 'down' }}">
                        {{ ($safeRevenueChange ?? 0) >= 0 ? 'Growing' : 'Declining' }}
                    </div>
                </td>
            </tr>
        </table>

        <!-- Appointment Health -->
        <div class="section-title">Appointment Health</div>
        <table class="health-table">
            <tr>
                <td>
                    <div class="health-icon">📋</div>
                    <div class="health-label">Total</div>
                    <div class="health-value">{{ $safeTotalAppts }}</div>
                </td>
                <td>
                    <div class="health-icon">✅</div>
                    <div class="health-label">Completed</div>
                    <div class="health-value" style="color: #059669;">{{ $safeCompleted }}</div>
                    <div class="health-status {{ $safeCompletionRate >= 80 ? 'excellent' : ($safeCompletionRate >= 60 ? 'good' : 'warning') }}">
                        {{ $safeCompletionRate }}%
                    </div>
                </td>
                <td>
                    <div class="health-icon">❌</div>
                    <div class="health-label">Cancelled</div>
                    <div class="health-value" style="color: #e11d48;">{{ $safeCancelled }}</div>
                    <div class="health-status {{ $safeCancellationRate <= 10 ? 'good' : 'warning' }}">
                        {{ $safeCancellationRate }}%
                    </div>
                </td>
                <td>
                    <div class="health-icon">⏰</div>
                    <div class="health-label">No-Shows</div>
                    <div class="health-value" style="color: #d97706;">{{ $safeNoShow }}</div>
                    <div class="health-status {{ $safeNoShowRate <= 8 ? 'excellent' : ($safeNoShowRate <= 15 ? 'good' : 'critical') }}">
                        {{ $safeNoShowRate }}%
                    </div>
                </td>
                <td>
                    <div class="health-icon">💵</div>
                    <div class="health-label">Rev / Appt</div>
                    <div class="health-value" style="color: #2563eb;">₱{{ number_format($safeTotalAppts > 0 ? $safeTotalRevenue / $safeTotalAppts : 0, 0) }}</div>
                    <div class="health-status good">Average</div>
                </td>
            </tr>
        </table>

        <!-- Top Services -->
        <div class="section-title">Top Performing Services</div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 45%;">Service</th>
                        <th style="width: 25%;" class="text-right">Revenue</th>
                        <th style="width: 25%;" class="text-right">Market Share</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $svcTotal = collect($topServices ?? [])->sum();
                        $rank = 0;
                    @endphp
                    @foreach($topServices as $name => $amount)
                    @php
                        $rank++;
                        $share = $svcTotal > 0 ? round(($amount / $svcTotal) * 100, 1) : 0;
                        $rankClass = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : 'other'));
                    @endphp
                    <tr>
                        <td><span class="rank {{ $rankClass }}">{{ $rank }}</span></td>
                        <td>
                            <strong>{{ $name }}</strong>
                            <div class="bar-bg">
                                <div class="bar-fill" style="width: {{ $share }}%"></div>
                            </div>
                        </td>
                        <td class="text-right amount">₱{{ number_format($amount, 2) }}</td>
                        <td class="text-right pct">{{ $share }}%</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td></td>
                        <td>Total Service Revenue</td>
                        <td class="text-right">₱{{ number_format($svcTotal, 2) }}</td>
                        <td class="text-right">100%</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Top Staff -->
        <div class="section-title">Top Performing Staff</div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 45%;">Staff Member</th>
                        <th style="width: 25%;" class="text-right">Revenue</th>
                        <th style="width: 25%;" class="text-right">Contribution</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $staffTotal = collect($topStaff ?? [])->sum();
                        $rank = 0;
                    @endphp
                    @foreach($topStaff as $name => $amount)
                    @php
                        $rank++;
                        $share = $staffTotal > 0 ? round(($amount / $staffTotal) * 100, 1) : 0;
                        $rankClass = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : 'other'));
                    @endphp
                    <tr>
                        <td><span class="rank {{ $rankClass }}">{{ $rank }}</span></td>
                        <td>
                            <strong>{{ $name }}</strong>
                            <div class="bar-bg">
                                <div class="bar-fill staff" style="width: {{ $share }}%"></div>
                            </div>
                        </td>
                        <td class="text-right amount">₱{{ number_format($amount, 2) }}</td>
                        <td class="text-right pct">{{ $share }}%</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td></td>
                        <td>Total Staff Revenue</td>
                        <td class="text-right">₱{{ number_format($staffTotal, 2) }}</td>
                        <td class="text-right">100%</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Payment Methods -->
        <div class="section-title">Payment Method Breakdown</div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Method</th>
                        <th class="text-right">Transactions</th>
                        <th class="text-right">Volume</th>
                        <th class="text-right">Share</th>
                    </tr>
                </thead>
                <tbody>
                    @php $methodTotal = collect($methodBreakdown)->sum('total'); @endphp
                    @foreach($methodBreakdown as $method => $data)
                    @php
                        $share = $methodTotal > 0 ? round(($data['total'] / $methodTotal) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td class="capitalize"><strong>{{ str_replace('_', ' ', $method) }}</strong></td>
                        <td class="text-right">{{ $data['count'] }}</td>
                        <td class="text-right amount">₱{{ number_format($data['total'], 2) }}</td>
                        <td class="text-right pct">{{ $share }}%</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Total</td>
                        <td class="text-right">{{ collect($methodBreakdown)->sum('count') }}</td>
                        <td class="text-right">₱{{ number_format($methodTotal, 2) }}</td>
                        <td class="text-right">100%</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    <!-- ===== FOOTER ===== -->
    <div class="footer-bar">
        <div class="footer-left">
            Spa Alexandria &nbsp;|&nbsp; {{ $startDate->format('M d, Y') }} — {{ $endDate->format('M d, Y') }}
        </div>
        <div class="footer-right">CONFIDENTIAL</div>
    </div>
</body>
</html>