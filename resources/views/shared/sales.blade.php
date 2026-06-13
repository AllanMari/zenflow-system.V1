@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Sales Report')

@push('styles')
<style>
    .bar-transition { transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1); }

    .kpi-section > div {
        min-width: 0;
        overflow: hidden;
    }
    .kpi-section p {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Hover dropdown buttons */
    .report-btn-group { position: relative; display: inline-block; }
    .report-dropdown {
        position: absolute;
        top: calc(100% + 6px);
        right: 0;
        min-width: 170px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-4px);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 50;
        overflow: hidden;
    }
    .report-btn-group:hover .report-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    .report-dropdown button,
    .report-dropdown a {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 500;
        color: #374151;
        background: none;
        border: none;
        cursor: pointer;
        transition: background 0.15s;
        text-align: left;
        text-decoration: none;
    }
    .report-dropdown button:hover,
    .report-dropdown a:hover { background: #f3f4f6; }
    .dark .report-dropdown { background: #1f2937; border-color: #374151; }
    .dark .report-dropdown button { color: #e5e7eb; }
    .dark .report-dropdown button:hover { background: #374151; }

    .print-only-daily, .print-only-business { display: none !important; }

    @media print {
        body { background: white !important; color: black !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .no-print, .report-btn-group, .period-filters, .smart-suggestions, .charts-section, .peak-hours-section, .analytics-section { display: none !important; }

        body.printing-daily .kpi-section,
        body.printing-daily .charts-section,
        body.printing-daily .peak-hours-section,
        body.printing-daily #transactionLog,
        body.printing-daily .print-only-business,
        body.printing-daily .no-print,
        body.printing-daily .report-btn-group,
        body.printing-daily .period-filters,
        body.printing-daily .smart-suggestions {
            display: none !important;
        }
        body.printing-daily .print-only-daily,
        body.printing-daily #dailyReportWrapper {
            display: block !important;
        }

        body.printing-business .kpi-section,
        body.printing-business .charts-section,
        body.printing-business .peak-hours-section,
        body.printing-business #transactionLog,
        body.printing-business .print-only-daily,
        body.printing-business .no-print,
        body.printing-business .report-btn-group,
        body.printing-business .period-filters,
        body.printing-business .smart-suggestions {
            display: none !important;
        }
        body.printing-business .print-only-business,
        body.printing-business #businessReportWrapper {
            display: block !important;
        }

        .shadow, .shadow-sm, .shadow-lg, .shadow-xl { box-shadow: none !important; }
        .dark\:bg-gray-800, .dark\:bg-gray-700, .dark\:bg-gray-900 { background-color: white !important; }
        .dark\:text-gray-300, .dark\:text-gray-400, .dark\:text-gray-200 { color: #1f2937 !important; }

        table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f3f4f6 !important; font-weight: 700; color: #374151; }
        .fixed.inset-0 { display: none !important; }
    }

    .executive-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        text-align: center;
        background: #fafafa;
    }
    .executive-card .label {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
        font-weight: 700;
        margin-bottom: 6px;
    }
    .executive-card .value {
        font-size: 20px;
        font-weight: 800;
        color: #111827;
    }
    .section-title {
        font-size: 14px;
        font-weight: 700;
        color: #111827;
        border-bottom: 2px solid #0d9488;
        padding-bottom: 6px;
        margin-bottom: 12px;
        margin-top: 24px;
    }
    .executive-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 20px; }
    .executive-table th {
        background: #f3f4f6;
        color: #374151;
        font-weight: 700;
        padding: 10px 12px;
        border-bottom: 2px solid #d1d5db;
        text-align: left;
    }
    .executive-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
        color: #1f2937;
    }
    .executive-table tr:last-child td { border-bottom: 2px solid #d1d5db; }
    .trend-up { color: #059669; }
    .trend-down { color: #dc2626; }
    .print-watermark {
        position: fixed;
        bottom: 20px;
        right: 20px;
        font-size: 9px;
        color: #9ca3af;
    }

    /* Compact outcome diagram for daily report */
    .outcome-diagram {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 12px;
        background: #fafafa;
        width: 170px;
        flex-shrink: 0;
    }
    .outcome-diagram .d-title {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        color: #374151;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 6px;
        margin-bottom: 10px;
    }
    .d-row { margin-bottom: 10px; }
    .d-row:last-child { margin-bottom: 0; }
    .d-meta {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
        margin-bottom: 3px;
    }
    .d-meta .d-name { font-weight: 700; }
    .d-meta .d-pct { color: #6b7280; font-weight: 500; }
    .d-bar-track {
        height: 7px;
        background: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
    }
    .d-bar-fill { height: 100%; border-radius: 4px; }

    /* Appointment Health - FIXED: removed custom border-radius/shadow to match other cards */
    .health-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        border: 1px solid #e5e7eb;
    }
    .health-metric {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .health-metric:last-child { margin-bottom: 0; }
    .health-info { flex: 1; }
    .health-label {
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 2px;
    }
    .health-sub {
        font-size: 11px;
        color: #9ca3af;
    }
    .health-bar-wrap {
        width: 100%;
        height: 8px;
        background: #f3f4f6;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 6px;
    }
    .health-bar {
        height: 100%;
        border-radius: 4px;
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .health-value {
        font-size: 13px;
        font-weight: 800;
        margin-left: 12px;
        min-width: 42px;
        text-align: right;
    }
    .dark .health-card {
        background: #1f2937;
        border-color: #374151;
    }
    .dark .health-label { color: #e5e7eb; }
    .dark .health-bar-wrap { background: #374151; }

    /* Mini stat row */
    .mini-stat-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #f3f4f6;
    }
    .dark .mini-stat-row { border-color: #374151; }
    .mini-stat {
        text-align: center;
    }
    .mini-stat-number {
        font-size: 18px;
        font-weight: 800;
        line-height: 1;
    }
    .mini-stat-label {
        font-size: 10px;
        color: #9ca3af;
        margin-top: 4px;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.03em;
    }

    /* Smart Suggestions */
    .suggestion-card {
        position: relative;
        overflow: hidden;
        border-radius: 10px;
        padding: 16px;
        border-left: 4px solid;
    }
    .suggestion-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 80px;
        height: 80px;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
        border-radius: 50%;
        transform: translate(30%, -30%);
    }
    .suggestion-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        margin-bottom: 10px;
    }
    .suggestion-title {
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 4px;
    }
    .suggestion-text {
        font-size: 12px;
        line-height: 1.5;
        opacity: 0.95;
    }
    .suggestion-meta {
        font-size: 10px;
        margin-top: 8px;
        opacity: 0.75;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    /* Transaction Log - Spa Alexandria Style */
    .tx-spa-table { width: 100%; border-collapse: collapse; font-size: 11px; font-family: 'Segoe UI', Arial, sans-serif; }
    .tx-spa-table th { background: #c8e6c9; border: 1px solid #9e9e9e; padding: 6px 4px; text-align: center; font-size: 10px; font-weight: 700; color: #1b5e20; }
    .tx-spa-table td { border: 1px solid #bdbdbd; padding: 5px 6px; text-align: center; vertical-align: middle; }
    .tx-spa-table .tx-num { text-align: center; font-weight: 700; width: 3%; }
    .tx-spa-table .tx-name { text-align: left; font-weight: 600; width: 10%; }
    .tx-spa-table .tx-room { width: 5%; }
    .tx-spa-table .tx-time { width: 6%; font-size: 10px; }
    .tx-spa-table .tx-therapist { text-align: left; width: 10%; font-size: 10px; }
    .tx-spa-table .tx-hrs { width: 4%; }
    .tx-spa-table .tx-srvc { width: 5%; font-weight: 700; }
    .tx-spa-table .tx-gross { width: 7%; text-align: right; font-weight: 700; }
    .tx-spa-table .tx-discount { width: 8%; text-align: right; }
    .tx-spa-table .tx-net { width: 7%; text-align: right; font-weight: 700; }
    .tx-spa-table .tx-note { width: 4%; }
    .tx-spa-table .tx-pct { width: 4%; }
    .tx-spa-table .tx-com { width: 8%; text-align: right; font-weight: 700; }
    .tx-spa-table .tx-total-row { background: #fff9c4; font-weight: 800; font-size: 12px; }
    .tx-spa-table .tx-total-row td { border-top: 2px solid #424242; border-bottom: 2px solid #424242; }
    .tx-spa-table .tx-discount-badge { background: #ffcdd2; color: #c62828; padding: 1px 4px; border-radius: 3px; font-size: 9px; font-weight: 700; }
    .tx-spa-table .tx-com-badge { background: #e8f5e9; color: #2e7d32; padding: 1px 4px; border-radius: 3px; font-size: 9px; font-weight: 700; }
    .dark .tx-spa-table th { background: #1b5e20; color: #e8f5e9; }
    .dark .tx-spa-table .tx-total-row { background: #f9a825; }

    /* Service dropdown inside table cell */
    .tx-srv-dropdown { position: relative; display: inline-block; }
    .tx-srv-dropdown .tx-srv-menu {
        display: none;
        position: absolute;
        z-index: 50;
        min-width: 160px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        padding: 4px 0;
        top: 100%;
        left: 0;
        margin-top: 2px;
        text-align: left;
    }
    .tx-srv-dropdown:hover .tx-srv-menu { display: block; }
    .tx-srv-menu-item { padding: 3px 8px; font-size: 10px; color: #374151; white-space: nowrap; }
    .tx-srv-menu-item:hover { background: #f3f4f6; }
    .dark .tx-srv-dropdown .tx-srv-menu { background: #1f2937; border-color: #374151; }
    .dark .tx-srv-menu-item { color: #e5e7eb; }
    .dark .tx-srv-menu-item:hover { background: #374151; }
</style>
@endpush

@section('content')
@php
    $currency = '₱';

    /* ─── SAFE RATE CALCULATIONS ─── */
    $safeTotalAppts      = max(intval($totalApptsInPeriod ?? 0), 1);
    $safeCompleted       = intval($completedApptsInPeriod ?? 0);
    $safeCancelled       = intval($cancelledApptsInPeriod ?? 0);
    $safeNoShow          = intval($noShowApptsInPeriod ?? 0);
    $safeTotalCount      = intval($totalCount ?? 0);
    $safeUniqueCustomers = intval($uniqueCustomers ?? 0);
    $safeDeposits        = floatval($deposits ?? 0);
    $safeTotalRevenue    = floatval($totalRevenue ?? 0);
    $safeAvgSale         = floatval($avgSale ?? 0);
    $safeRevPerComp      = floatval($revPerCompletedAppt ?? 0);
    $safeRevenueChange   = floatval($revenueChange ?? 0);
    $safeConversionRate  = min(100, floatval($conversionRate ?? 0));

    $safeCompletionRate   = min(100, round(($safeCompleted / $safeTotalAppts) * 100, 1));
    $safeCancellationRate = min(100, round(($safeCancelled / $safeTotalAppts) * 100, 1));
    $safeNoShowRate       = min(100, round(($safeNoShow / $safeTotalAppts) * 100, 1));
@endphp

<!-- SCREEN HEADER -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 no-print">
    <div>
        <h1 class="text-3xl font-bold text-teal-600 dark:text-teal-400">Spa Sales Analytics</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ $startDate->format('M d, Y') }} — {{ $endDate->format('M d, Y') }}
            <span class="ml-2 px-2 py-0.5 bg-teal-100 dark:bg-teal-900 text-teal-800 dark:text-teal-200 rounded text-xs font-bold uppercase">{{ $label }}</span>
        </p>
    </div>

    <!-- HOVER DROPDOWN BUTTONS -->
    <div class="flex gap-3 no-print">
        <!-- Daily Sales Report -->
        <div class="report-btn-group">
            <button type="button" class="bg-teal-600 text-white px-5 py-2.5 rounded-lg hover:bg-teal-700 transition text-sm font-medium shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Generate Sales Report
            </button>
            <div class="report-dropdown">
                <a href="{{ route($routeName . '.daily-report-pdf', array_merge(request()->only(['period','start_date','end_date','status']), ['action' => 'stream'])) }}" 
                   class="flex items-center gap-2 w-full px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 transition">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    📄 Download PDF
                </a>
                <a href="{{ route($routeName . '.daily-report-pdf', array_merge(request()->only(['period','start_date','end_date','status']), ['action' => 'stream'])) }}" 
                   target="_blank" 
                   class="flex items-center gap-2 w-full px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 transition">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    🖨️ Print Preview
                </a>
            </div>
        </div>

        <!-- Business Report -->
        <div class="report-btn-group">
            <button type="button" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg hover:bg-indigo-700 transition text-sm font-medium shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Generate Business Report
            </button>
            <div class="report-dropdown">
                <a href="{{ route($routeName . '.business-report-pdf', array_merge(request()->only(['period','start_date','end_date','status']), ['action' => 'stream'])) }}" 
                   class="flex items-center gap-2 w-full px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 transition">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    📄 Download PDF
                </a>
                <a href="{{ route($routeName . '.business-report-pdf', array_merge(request()->only(['period','start_date','end_date','status']), ['action' => 'stream'])) }}" 
                    target="_blank" 
                    class="flex items-center gap-2 w-full px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 transition">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    🖨️ Print Preview
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Period Filters -->
<div class="period-filters bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6 no-print border dark:border-gray-700">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Period</label>
            <select name="period" onchange="this.form.submit()" class="border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="daily"   {{ $period=='daily'   ? 'selected' : '' }}>Daily (Today)</option>
                <option value="weekly"  {{ $period=='weekly'  ? 'selected' : '' }}>Weekly</option>
                <option value="monthly" {{ $period=='monthly' ? 'selected' : '' }}>Monthly</option>
                <option value="yearly"  {{ $period=='yearly'  ? 'selected' : '' }}>Yearly</option>
                <option value="custom"  {{ $period=='custom'  ? 'selected' : '' }}>Custom Range</option>
            </select>
        </div>

        @if($period === 'custom')
        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">From</label>
            <input type="date" name="start_date" value="{{ request('start_date', $today->format('Y-m-d')) }}" class="border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">To</label>
            <input type="date" name="end_date" value="{{ request('end_date', $today->format('Y-m-d')) }}" class="border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
        </div>
        <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700 transition text-sm font-medium">Apply</button>
        @endif

        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif

        <a href="{{ route($routeName) }}" class="text-sm text-gray-500 hover:text-teal-600 dark:hover:text-teal-400 underline ml-auto">Reset Filters</a>
    </form>
</div>

<!-- EXECUTIVE KPI CARDS -->
<div class="kpi-section grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-teal-500">
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Revenue</p>
        <p class="text-xl font-bold text-teal-600 dark:text-teal-400 mt-1">{{ $currency }}{{ number_format($safeTotalRevenue, 2) }}</p>
        <p class="text-[10px] text-gray-400 mt-1">Completion + Add-on + Full</p>
    </div>
    <div onclick="openTxModal()" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-blue-500 cursor-pointer hover:shadow-md transition relative group no-print">
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider flex items-center gap-1">
            Transactions
            <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        </p>
        <p class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $safeTotalCount }}</p>
        <p class="text-[10px] text-blue-400 mt-1 group-hover:underline">Click for analytics</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Avg Ticket</p>
        <p class="text-xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $currency }}{{ number_format($safeAvgSale, 2) }}</p>
        <p class="text-[10px] text-gray-400 mt-1">Per transaction</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-indigo-500">
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Unique Clients</p>
        <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $safeUniqueCustomers }}</p>
        <p class="text-[10px] text-gray-400 mt-1">Distinct payers</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-amber-500">
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Deposits Held</p>
        <p class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $currency }}{{ number_format($safeDeposits, 2) }}</p>
        <p class="text-[10px] text-gray-400 mt-1">Not yet revenue</p>
    </div>
    <div onclick="openNoShowModal()" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-red-500 cursor-pointer hover:shadow-md transition relative group no-print">
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider flex items-center gap-1">
            No Shows
            <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        </p>
        <p class="text-xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $safeNoShow }}</p>
        <p class="text-[10px] text-red-400 mt-1 group-hover:underline">Click for details</p>
    </div>
</div>

<!-- SECONDARY KPIs -->
<div class="kpi-section grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3 border-t-4 border-emerald-500 text-center">
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Rev / Completed</p>
        <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $currency }}{{ number_format($safeRevPerComp, 2) }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3 border-t-4 border-green-500 text-center">
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Completion Rate</p>
        <p class="text-lg font-bold text-green-600 dark:text-green-400 mt-1">{{ $safeCompletionRate }}%</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3 border-t-4 border-cyan-500 text-center">
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Deposit Conversion</p>
        <p class="text-lg font-bold text-cyan-600 dark:text-cyan-400 mt-1">{{ $safeConversionRate }}%</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3 border-t-4 border-rose-500 text-center">
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Cancellation Rate</p>
        <p class="text-lg font-bold text-rose-600 dark:text-rose-400 mt-1">{{ $safeCancellationRate }}%</p>
    </div>
</div>

<!-- SMART SUGGESTIONS ENGINE -->
@if(count($suggestions) > 0)
<div class="smart-suggestions mb-6 no-print">
    <div class="flex items-center gap-2 mb-3">
        <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        <h2 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Smart Insights & Recommendations</h2>
        <!-- AI STATUS INDICATOR -->
        <span class="flex items-center gap-1.5 text-[10px] px-2 py-0.5 rounded-full font-bold ml-auto {{ $aiOnline ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' }}">
            <span class="w-1.5 h-1.5 rounded-full {{ $aiOnline ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></span>
            {{ $aiOnline ? 'AI Online' : 'AI Offline — Fallback Active' }}
        </span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($suggestions as $s)
        <div class="suggestion-card {{ $s['bg'] }} {{ $s['type'] === 'danger' ? 'border-red-500' : ($s['type'] === 'warning' ? 'border-amber-500' : ($s['type'] === 'success' ? 'border-green-500' : 'border-blue-500')) }}">
            <div class="suggestion-icon {{ $s['iconBg'] }}">{{ $s['icon'] }}</div>
            <div class="suggestion-title text-gray-800 dark:text-gray-100">{{ $s['title'] }}</div>
            <div class="suggestion-text text-gray-600 dark:text-gray-300">{{ $s['text'] }}</div>
            <div class="suggestion-meta {{ $s['type'] === 'danger' ? 'text-red-600 dark:text-red-400' : ($s['type'] === 'warning' ? 'text-amber-600 dark:text-amber-400' : ($s['type'] === 'success' ? 'text-green-600 dark:text-green-400' : 'text-blue-600 dark:text-blue-400')) }}">
                {{ $s['meta'] }}
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- CHARTS ROW 1 -->
<div class="charts-section grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 no-print">
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 border dark:border-gray-700">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Revenue Trend</h3>
            <span class="text-xs text-gray-400">{{ $label }}</span>
        </div>
        <div class="relative h-64">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 border dark:border-gray-700">
        <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Payment Methods</h3>
        <div class="relative h-48 flex items-center justify-center">
            <canvas id="methodChart"></canvas>
        </div>
    </div>
</div>

<!-- FIXED ANALYTICS GRID: Consistent card styling -->
<div class="analytics-section grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Revenue Growth -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 border dark:border-gray-700 border-t-4 {{ $safeRevenueChange >= 0 ? 'border-t-green-500' : 'border-t-red-500' }}">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider mb-1">Revenue Growth</p>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-bold {{ $safeRevenueChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $revenueChangeLabel }}%
            </span>
            <span class="text-sm text-gray-500">vs previous {{ strtolower($label) }}</span>
        </div>
        <div class="mt-3 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div class="h-full {{ $safeRevenueChange >= 0 ? 'bg-green-500' : 'bg-red-500' }} bar-transition" style="width: {{ min(abs($safeRevenueChange), 100) }}%"></div>
        </div>
    </div>

    <!-- FIXED: Appointment Health — consistent card classes -->
    <div class="health-card rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Appointment Health</p>
            <span class="text-[10px] px-2 py-0.5 rounded-full {{ $safeCompletionRate >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : ($safeCompletionRate >= 60 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300') }} font-bold">
                {{ $safeCompletionRate >= 80 ? 'Healthy' : ($safeCompletionRate >= 60 ? 'At Risk' : 'Critical') }}
            </span>
        </div>

        <div class="health-metric">
            <div class="health-info">
                <div class="health-label dark:text-gray-200">Completion Rate</div>
                <div class="health-bar-wrap">
                    <div class="health-bar bg-green-500" style="width: {{ $safeCompletionRate }}%"></div>
                </div>
            </div>
            <div class="health-value text-green-600 dark:text-green-400">{{ $safeCompletionRate }}%</div>
        </div>

        <div class="health-metric">
            <div class="health-info">
                <div class="health-label dark:text-gray-200">No-Show Rate</div>
                <div class="health-bar-wrap">
                    <div class="health-bar bg-red-500" style="width: {{ $safeNoShowRate }}%"></div>
                </div>
            </div>
            <div class="health-value text-red-600 dark:text-red-400">{{ $safeNoShowRate }}%</div>
        </div>

        <div class="health-metric">
            <div class="health-info">
                <div class="health-label dark:text-gray-200">Cancellation Rate</div>
                <div class="health-bar-wrap">
                    <div class="health-bar bg-rose-500" style="width: {{ $safeCancellationRate }}%"></div>
                </div>
            </div>
            <div class="health-value text-rose-600 dark:text-rose-400">{{ $safeCancellationRate }}%</div>
        </div>

        <div class="mini-stat-row" style="grid-template-columns: repeat(4, 1fr);">
            <div class="mini-stat">
                <div class="mini-stat-number text-gray-800 dark:text-gray-200">{{ $safeTotalAppts }}</div>
                <div class="mini-stat-label">Total</div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-number text-green-600 dark:text-green-400">{{ $safeCompleted }}</div>
                <div class="mini-stat-label">Completed</div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-number text-rose-600 dark:text-rose-400">{{ $safeCancelled }}</div>
                <div class="mini-stat-label">Cancelled</div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-number text-red-600 dark:text-red-400">{{ $safeNoShow }}</div>
                <div class="mini-stat-label">No-Show</div>
            </div>
        </div>
    </div>

    <!-- Top Services -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 border dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider mb-4">Top Services by Revenue</p>
        <div class="space-y-4">
            @forelse($topServices as $name => $amount)
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="dark:text-gray-300 truncate max-w-[60%]" title="{{ $name }}">{{ $name }}</span>
                    <span class="font-bold text-teal-600 dark:text-teal-400">{{ $currency }}{{ number_format($amount, 2) }}</span>
                </div>
                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-teal-500 rounded-full bar-transition" style="width: {{ $maxSvc > 0 ? ($amount / $maxSvc) * 100 : 0 }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-8">No service data for this period</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Top Staff -->
<div class="analytics-section grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 border dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider mb-4">Top Staff by Revenue</p>
        <div class="space-y-4">
            @forelse($topStaff as $name => $amount)
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="dark:text-gray-300 truncate max-w-[60%]" title="{{ $name }}">{{ $name }}</span>
                    <span class="font-bold text-blue-600 dark:text-blue-400">{{ $currency }}{{ number_format($amount, 2) }}</span>
                </div>
                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 rounded-full bar-transition" style="width: {{ $maxStaff > 0 ? ($amount / $maxStaff) * 100 : 0 }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-8">No staff data for this period</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 border dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider mb-4">Method Breakdown</p>
        <div class="space-y-3 max-h-48 overflow-y-auto pr-1">
            @forelse($methodBreakdown as $method => $data)
            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <span class="text-sm font-medium dark:text-gray-200 capitalize">{{ $method }}</span>
                <div class="text-right">
                    <p class="font-bold text-teal-600 dark:text-teal-400">{{ $currency }}{{ number_format($data['total'], 2) }}</p>
                    <p class="text-xs text-gray-500">{{ $data['count'] }} txns</p>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-4">No data for selected period.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Peak Revenue Hours -->
<div class="peak-hours-section bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 mb-6 border dark:border-gray-700 no-print">
    <div class="flex justify-between items-center mb-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Peak Revenue Hours</p>
        <span class="text-xs text-gray-400">10 AM - 8 PM</span>
    </div>
    @php
        $hasHourlyData = collect($hourlyRevenue)->sum() > 0;
        $hourLabels = [10=>'10a', 11=>'11a', 12=>'12p', 13=>'1p', 14=>'2p', 15=>'3p', 16=>'4p', 17=>'5p', 18=>'6p', 19=>'7p', 20=>'8p'];
    @endphp
    @if($hasHourlyData)
    <div class="flex items-end gap-1 sm:gap-2 h-40 px-2">
        @foreach($hourlyRevenue as $hour => $amount)
        @php
            $barHeight = $maxHourly > 0 ? ($amount / $maxHourly) * 100 : 0;
            $barLabel = $hourLabels[$hour] ?? $hour;
        @endphp
        <div class="flex-1 flex flex-col items-center justify-end h-full gap-1 group cursor-pointer" title="{{ $currency }}{{ number_format($amount, 2) }} at {{ $barLabel }}">
            <span class="text-[9px] text-gray-500 dark:text-gray-400 opacity-0 group-hover:opacity-100 transition font-medium mb-0.5">{{ $currency }}{{ number_format($amount / 1000, 1) }}k</span>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-t relative overflow-hidden" style="height: {{ max($barHeight, 4) }}%">
                <div class="absolute bottom-0 w-full h-full bg-purple-500 group-hover:bg-purple-400 transition-colors"></div>
            </div>
            <span class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 font-medium">{{ $barLabel }}</span>
        </div>
        @endforeach
    </div>
    <p class="text-xs text-gray-400 text-center mt-2">Hover bars for exact amounts</p>
    @else
    <div class="h-40 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
        <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm">No payments recorded between 10 AM - 8 PM</p>
        <p class="text-xs mt-1">Try selecting a wider date range</p>
    </div>
    @endif
</div>

<!-- MONTHLY CUSTOMER SPIKE ANALYTICS -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 mb-6 border dark:border-gray-700 no-print">
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">12-Month Booking Trends</h3>
        </div>
        <div class="flex items-center gap-3 text-xs">
            <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded font-bold">
                Peak: {{ $monthlySpike['peakMonth'] }} ({{ $monthlySpike['peakBookings'] }} bookings)
            </span>
            <span class="px-2 py-1 {{ $monthlySpike['trendDirection'] === 'up' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' }} rounded font-bold">
                Trend: {{ $monthlySpike['trendDirection'] === 'up' ? '+' : '' }}{{ $monthlySpike['trendPercent'] }}%
            </span>
        </div>
    </div>
    <div class="relative h-48">
        <canvas id="monthlySpikeChart"></canvas>
    </div>
    <p class="text-xs text-gray-400 mt-2 text-center">Shows booking volume, completion rate, and no-show rate per month</p>
</div>

<!-- ========================================== -->
<!-- PRINT-ONLY BUSINESS REPORT                 -->
<!-- ========================================== -->
<div id="businessReportWrapper" class="print-only-business">
    <div class="text-center pb-6 border-b-2 border-teal-600 mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">SPA BUSINESS INTELLIGENCE REPORT</h1>
        <p class="text-sm text-gray-600 mt-2 font-medium">Period: {{ $startDate->format('F d, Y') }} — {{ $endDate->format('F d, Y') }}</p>
        <p class="text-xs text-gray-500 mt-1">Generated: {{ now()->format('F d, Y g:i A') }} by {{ auth()->user()->full_name ?? auth()->user()->name }}</p>
        <div class="mt-4 flex justify-center gap-6 text-xs text-gray-500">
            <span><strong>Revenue:</strong> {{ $currency }}{{ number_format($safeTotalRevenue, 2) }}</span>
            <span><strong>Transactions:</strong> {{ $safeTotalCount }}</span>
            <span><strong>Clients:</strong> {{ $safeUniqueCustomers }}</span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px;">
        <div class="executive-card"><div class="label">Total Revenue</div><div class="value" style="color: #0d9488;">{{ $currency }}{{ number_format($safeTotalRevenue, 2) }}</div></div>
        <div class="executive-card"><div class="label">Transactions</div><div class="value" style="color: #2563eb;">{{ $safeTotalCount }}</div></div>
        <div class="executive-card"><div class="label">Unique Clients</div><div class="value" style="color: #7c3aed;">{{ $safeUniqueCustomers }}</div></div>
        <div class="executive-card"><div class="label">Avg Ticket</div><div class="value" style="color: #111827;">{{ $currency }}{{ number_format($safeAvgSale, 2) }}</div></div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px;">
        <div class="executive-card"><div class="label">Completion Rate</div><div class="value {{ $safeCompletionRate >= 80 ? 'trend-up' : 'trend-down' }}">{{ $safeCompletionRate }}%</div></div>
        <div class="executive-card"><div class="label">No-Show Rate</div><div class="value trend-down">{{ $safeNoShowRate }}%</div></div>
        <div class="executive-card"><div class="label">Cancellation Rate</div><div class="value {{ $safeCancellationRate <= 10 ? 'trend-up' : 'trend-down' }}">{{ $safeCancellationRate }}%</div></div>
        <div class="executive-card"><div class="label">Revenue Growth</div><div class="value {{ $safeRevenueChange >= 0 ? 'trend-up' : 'trend-down' }}">{{ $revenueChangeLabel ?? '0' }}%</div></div>
    </div>

    <div class="section-title">Top Performing Services</div>
    <table class="executive-table">
        <thead><tr><th style="width: 60%;">Service</th><th class="text-right" style="width: 20%;">Revenue</th><th class="text-right" style="width: 20%;">Share</th></tr></thead>
        <tbody>
            @php $svcTotal = collect($topServices ?? [])->sum(); @endphp
            @foreach($topServices as $name => $amount)
            <tr><td>{{ $name }}</td><td class="text-right font-bold">{{ $currency }}{{ number_format($amount, 2) }}</td><td class="text-right text-gray-500">{{ $svcTotal > 0 ? round(($amount / $svcTotal) * 100, 1) : 0 }}%</td></tr>
            @endforeach
            <tr style="background: #f9fafb;"><td class="font-bold">Total</td><td class="text-right font-bold">{{ $currency }}{{ number_format($svcTotal, 2) }}</td><td class="text-right font-bold">100%</td></tr>
        </tbody>
    </table>

    <div class="section-title">Top Performing Staff</div>
    <table class="executive-table">
        <thead><tr><th style="width: 60%;">Staff</th><th class="text-right" style="width: 20%;">Revenue</th><th class="text-right" style="width: 20%;">Share</th></tr></thead>
        <tbody>
            @php $staffTotal = collect($topStaff ?? [])->sum(); @endphp
            @foreach($topStaff as $name => $amount)
            <tr><td>{{ $name }}</td><td class="text-right font-bold">{{ $currency }}{{ number_format($amount, 2) }}</td><td class="text-right text-gray-500">{{ $staffTotal > 0 ? round(($amount / $staffTotal) * 100, 1) : 0 }}%</td></tr>
            @endforeach
            <tr style="background: #f9fafb;"><td class="font-bold">Total</td><td class="text-right font-bold">{{ $currency }}{{ number_format($staffTotal, 2) }}</td><td class="text-right font-bold">100%</td></tr>
        </tbody>
    </table>

    <div class="section-title">Appointment Health</div>
    <table class="executive-table">
        <thead><tr><th style="width: 60%;">Metric</th><th class="text-right" style="width: 40%;">Value</th></tr></thead>
        <tbody>
            <tr><td>Total Appointments</td><td class="text-right font-bold">{{ $safeTotalAppts }}</td></tr>
            <tr><td>Completed</td><td class="text-right font-bold text-green-700">{{ $safeCompleted }}</td></tr>
            <tr><td>Completion Rate</td><td class="text-right font-bold text-green-700">{{ $safeCompletionRate }}%</td></tr>
            <tr><td>No-Show Rate</td><td class="text-right font-bold text-red-700">{{ $safeNoShowRate }}%</td></tr>
            <tr><td>Cancellation Rate</td><td class="text-right font-bold text-red-700">{{ $safeCancellationRate }}%</td></tr>
        </tbody>
    </table>

    <div class="section-title">Payment Method Breakdown</div>
    <table class="executive-table">
        <thead><tr><th style="width: 40%;">Method</th><th class="text-right" style="width: 30%;">Transactions</th><th class="text-right" style="width: 30%;">Amount</th></tr></thead>
        <tbody>
            @foreach($methodBreakdown as $method => $data)
            <tr><td class="capitalize">{{ str_replace('_', ' ', $method) }}</td><td class="text-right">{{ $data['count'] }}</td><td class="text-right font-bold">{{ $currency }}{{ number_format($data['total'], 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="print-watermark">Confidential — {{ auth()->user()->full_name ?? auth()->user()->name }} — {{ now()->format('F d, Y g:i A') }}</div>
</div>

<!-- ========================================== -->
<!-- PRINT-ONLY DAILY SALES REPORT              -->
<!-- ========================================== -->
<div id="dailyReportWrapper" class="print-only-daily">
    @php
        $pTotalGross    = collect($printServiceSummary)->sum('gross');
        $pTotalNet      = collect($printServiceSummary)->sum('net');
        $pTotalDiscount = collect($printServiceSummary)->sum('discount');
        $pTotalCount    = collect($printServiceSummary)->sum('count');
    @endphp

    <div style="text-align:center; margin-bottom:14px;">
        <div style="font-size:20px; font-weight:800; letter-spacing:1px;">SPA ALEXANDRIA</div>
        <div style="font-size:13px; font-weight:700; margin-top:4px;">{{ $reportTitle }}</div>
        <div style="margin-top:10px; font-size:12px; font-weight:600;">
            {{ $dateLabel }} <span style="border-bottom:1px solid #000; display:inline-block; min-width:100px; text-align:center;">
                {{ $dateDisplay }}
            </span>
        </div>
    </div>

    <table style="width:100%; border-collapse:collapse; font-size:11px; font-family:Arial, Helvetica, sans-serif;">
        <thead>
            <tr style="background:#f3f4f6;">
                <th style="border:1px solid #6b7280; padding:5px; width:7%;  text-align:center;">CODE</th>
                <th style="border:1px solid #6b7280; padding:5px; width:38%; text-align:left;">SERVICES</th>
                <th style="border:1px solid #6b7280; padding:5px; width:10%; text-align:center;">TOTAL<br>SERVICES</th>
                <th style="border:1px solid #6b7280; padding:5px; width:15%; text-align:right;">GROSS<br>AMOUNT</th>
                <th style="border:1px solid #6b7280; padding:5px; width:12%; text-align:right;">DEDUCTIONS<br>DISCOUNT</th>
                <th style="border:1px solid #6b7280; padding:5px; width:15%; text-align:right;">NET</th>
            </tr>
        </thead>
        <tbody>
            @forelse($printServiceSummary as $row)
            <tr>
                <td style="border:1px solid #9ca3af; padding:4px 6px; text-align:center; font-weight:700;">{{ $row['code'] }}</td>
                <td style="border:1px solid #9ca3af; padding:4px 6px;">{{ $row['name'] }}</td>
                <td style="border:1px solid #9ca3af; padding:4px 6px; text-align:center;">{{ $row['count'] }}</td>
                <td style="border:1px solid #9ca3af; padding:4px 6px; text-align:right;">{{ number_format($row['gross'], 2) }}</td>
                <td style="border:1px solid #9ca3af; padding:4px 6px; text-align:right;">{{ number_format($row['discount'], 2) }}</td>
                <td style="border:1px solid #9ca3af; padding:4px 6px; text-align:right; font-weight:600;">{{ number_format($row['net'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="border:1px solid #9ca3af; padding:8px; text-align:center; color:#6b7280;">No service sales for this period.</td></tr>
            @endforelse

            <tr style="background:#f3f4f6; font-weight:800;">
                <td style="border:1px solid #6b7280; padding:5px; text-align:center;">TOTAL</td>
                <td style="border:1px solid #6b7280; padding:5px;"></td>
                <td style="border:1px solid #6b7280; padding:5px; text-align:center;">{{ $pTotalCount }}</td>
                <td style="border:1px solid #6b7280; padding:5px; text-align:right;">{{ number_format($pTotalGross, 2) }}</td>
                <td style="border:1px solid #6b7280; padding:5px; text-align:right;">{{ number_format($pTotalDiscount, 2) }}</td>
                <td style="border:1px solid #6b7280; padding:5px; text-align:right;">{{ number_format($pTotalNet, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table style="width:100%; border-collapse:collapse; font-size:11px; margin-top:24px;">
        <tr>
            <td style="width:50%; padding-right:20px;">
                <div style="border-bottom:1px solid #374151;"></div>
                <div style="font-size:9px; text-align:center; text-transform:uppercase; font-weight:700; margin-top:3px;">Prepared By</div>
                <div style="font-size:10px; text-align:center; margin-top:2px;">{{ auth()->user()->full_name ?? auth()->user()->name }}</div>
            </td>
            <td style="width:50%; padding-left:20px;">
                <div style="border-bottom:1px solid #374151;"></div>
                <div style="font-size:9px; text-align:center; text-transform:uppercase; font-weight:700; margin-top:3px;">Checked By</div>
            </td>
        </tr>
    </table>

    <div style="margin-top:16px; font-size:9px; color:#9ca3af; text-align:right;">
        Generated {{ now()->format('F d, Y g:i A') }} &bull; {{ auth()->user()->full_name ?? auth()->user()->name }}
    </div>
</div>

<!-- ========================================== -->
<!-- TRANSACTION LOG - SPA ALEXANDRIA STYLE     -->
<!-- FIXED: Added section header & top margin     -->
<!-- ========================================== -->
<div class="mt-8 no-print">
    <div class="flex items-center gap-2 mb-3">
        <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        <h2 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Transaction Log</h2>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden border dark:border-gray-700 mb-6" id="transactionLog">
        @include('shared._transaction_log_table', [
            'txLogData' => $txLogData,
            'currentStatus' => $currentStatus,
            'currency' => '₱',
            'serviceCodeMap' => $serviceCodeMap,
        ])
    </div>
</div>

<!-- Transaction Analytics Modal -->
<div id="txModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center backdrop-blur-sm no-print" aria-hidden="true">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-lg mx-4 shadow-2xl max-h-[80vh] overflow-y-auto border dark:border-gray-700" role="dialog" aria-modal="true" aria-labelledby="txModalTitle">
        <div class="flex justify-between items-center mb-4">
            <h3 id="txModalTitle" class="text-xl font-bold text-gray-800 dark:text-white">Transaction Analytics</h3>
            <button onclick="closeTxModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition" aria-label="Close modal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="space-y-4">
            <div>
                <h4 class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-2">By Payment Type</h4>
                <div class="space-y-2">
                    @forelse($typeBreakdown as $type => $data)
                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="capitalize font-medium dark:text-gray-200 text-sm">{{ $type }}</span>
                        <div class="text-right"><p class="font-bold text-blue-600 dark:text-blue-400">{{ $data['count'] }}</p><p class="text-xs text-gray-500">{{ $currency }}{{ number_format($data['total'], 2) }}</p></div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 text-center py-2">No data</p>
                    @endforelse
                </div>
            </div>
            <div>
                <h4 class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-2">By Payment Method</h4>
                <div class="space-y-2">
                    @forelse($methodBreakdown as $method => $data)
                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="capitalize font-medium dark:text-gray-200 text-sm">{{ $method }}</span>
                        <div class="text-right"><p class="font-bold text-teal-600 dark:text-teal-400">{{ $data['count'] }}</p><p class="text-xs text-gray-500">{{ $currency }}{{ number_format($data['total'], 2) }}</p></div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 text-center py-2">No data</p>
                    @endforelse
                </div>
            </div>
            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">Average Transaction Value</p>
                <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $currency }}{{ number_format($safeAvgSale, 2) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- No Show Details Modal -->
<div id="noShowModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center backdrop-blur-sm no-print" aria-hidden="true">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-lg mx-4 shadow-2xl max-h-[80vh] overflow-y-auto border dark:border-gray-700" role="dialog" aria-modal="true" aria-labelledby="noShowModalTitle">
        <div class="flex justify-between items-center mb-4">
            <h3 id="noShowModalTitle" class="text-xl font-bold text-red-600 dark:text-red-400">No Show Details</h3>
            <button onclick="closeNoShowModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition" aria-label="Close modal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg text-center"><p class="text-xs text-gray-600 dark:text-gray-400 uppercase font-bold tracking-wider">Forfeited</p><p class="text-lg font-bold text-red-600 dark:text-red-400">{{ $currency }}{{ number_format($noShowData['forfeited'] ?? 0, 2) }}</p></div>
            <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg text-center"><p class="text-xs text-gray-600 dark:text-gray-400 uppercase font-bold tracking-wider">Refunded</p><p class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ $currency }}{{ number_format($noShowData['refunded'] ?? 0, 2) }}</p></div>
        </div>
        <div class="space-y-2">
            @forelse($noShowData['list'] ?? [] as $ns)
            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border-l-4 {{ $ns['status'] === 'Refunded' ? 'border-orange-400' : 'border-red-400' }}">
                <div class="min-w-0">
                    <p class="font-medium dark:text-gray-200 text-sm truncate">{{ $ns['customer'] }}</p>
                    <p class="text-xs text-gray-500 font-mono">{{ $ns['phone'] }}</p>
                    <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($ns['date'])->format('M d, Y') }}</p>
                    <p class="text-[10px] text-gray-400">Marked: {{ \Carbon\Carbon::parse($ns['marked_at'])->format('M d, g:i A') }}</p>
                </div>
                <div class="text-right shrink-0 ml-2">
                    <p class="font-bold {{ $ns['status'] === 'Refunded' ? 'text-orange-600 dark:text-orange-400' : 'text-red-600 dark:text-red-400' }}">{{ $currency }}{{ number_format($ns['deposit'], 2) }}</p>
                    <span class="text-xs px-2 py-0.5 rounded {{ $ns['status'] === 'Refunded' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' }}">{{ $ns['status'] }}</span>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-4">No no-shows in this period.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- FLOATING AI SALES ASSISTANT -->
<div id="aiChatWidget" class="fixed bottom-6 right-6 z-50 no-print">
    <!-- Toggle Button -->
    <button onclick="toggleAiChat()" class="bg-teal-600 hover:bg-teal-700 text-white rounded-full p-4 shadow-lg transition-all hover:scale-110 flex items-center gap-2" aria-label="Open AI Assistant">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        <span class="text-sm font-bold">AI Advisor</span>
    </button>

    <!-- Chat Panel -->
    <div id="aiChatPanel" class="hidden absolute bottom-16 right-0 w-80 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border dark:border-gray-700 overflow-hidden" style="max-height: 500px;">
        <div class="bg-teal-600 p-3 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full {{ $aiOnline ? 'bg-green-400 animate-pulse' : 'bg-red-400' }}"></span>
                <span class="text-white text-sm font-bold">Spa AI Advisor</span>
                <span class="text-[10px] text-teal-200">{{ $aiOnline ? 'Online' : 'Offline' }}</span>
            </div>
            <button onclick="toggleAiChat()" class="text-white hover:text-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="aiChatMessages" class="p-4 h-80 overflow-y-auto space-y-3">
            <div class="bg-teal-50 dark:bg-teal-900/20 p-3 rounded-lg text-xs text-gray-600 dark:text-gray-300">
                <p class="font-bold text-teal-700 dark:text-teal-300 mb-1">👋 Hello! I'm your AI business advisor.</p>
                <p>Ask me about pricing, staffing, or trends. Example: "Should I offer discounts today?"</p>
            </div>
        </div>

        <div class="p-3 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <form onsubmit="sendAiQuestion(event)" class="flex gap-2">
                <input type="text" id="aiQuestionInput" placeholder="Ask about sales, pricing, trends..." 
                    class="flex-1 text-sm border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"
                    maxlength="200" autocomplete="off">
                <button type="submit" class="bg-teal-600 text-white p-2 rounded-lg hover:bg-teal-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.05)';
    const textColor = isDark ? '#9ca3af' : '#6b7280';
    const tooltipBg = isDark ? 'rgba(31,41,55,0.95)' : 'rgba(255,255,255,0.95)';
    const tooltipText = isDark ? '#e5e7eb' : '#1f2937';

    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

    const ctx1 = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Revenue',
                data: @json($chartValues),
                backgroundColor: isDark ? '#14b8a6' : '#0d9488',
                borderRadius: 6,
                hoverBackgroundColor: isDark ? '#2dd4bf' : '#14b8a6',
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: tooltipBg,
                    titleColor: tooltipText,
                    bodyColor: tooltipText,
                    borderColor: gridColor,
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: {
                        color: textColor,
                        callback: v => '₱' + (v >= 1000 ? (v/1000).toFixed(1) + 'k' : v.toLocaleString())
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: textColor, font: { size: 11 } }
                }
            }
        }
    });

    const ctx2 = document.getElementById('methodChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: @json($methodBreakdown->keys()->values()),
            datasets: [{
                data: @json($methodBreakdown->pluck('total')->values()),
                backgroundColor: ['#0d9488', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#10b981'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: textColor, boxWidth: 12, padding: 10, font: { size: 11 } }
                },
                tooltip: {
                    backgroundColor: tooltipBg,
                    titleColor: tooltipText,
                    bodyColor: tooltipText,
                    borderColor: gridColor,
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            const val = context.parsed;
                            const total = context.dataset.data.reduce((a,b) => a+b, 0);
                            const pct = total > 0 ? Math.round((val/total)*100) : 0;
                            return context.label + ': ₱' + val.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });

    /* ---------- STATUS FILTER ---------- */
    function updateStatusFilter() {
        const val = document.getElementById('statusFilter').value;
        const url = new URL(window.location.href);
        url.searchParams.set('status', val);
        url.searchParams.delete('page');

        const isAdmin = window.location.pathname.includes('/admin/');
        const endpoint = isAdmin
            ? '{{ route("admin.sales.tx-log") }}'
            : '{{ route("receptionist.sales.tx-log") }}';

        const params = new URLSearchParams(url.search);
        const queryString = params.toString();

        const container = document.getElementById('transactionLog');
        container.style.opacity = '0.5';

        fetch(`${endpoint}?${queryString}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(r => {
            if (!r.ok) throw new Error('Failed to load');
            return r.text();
        })
        .then(html => {
            container.innerHTML = html;
            container.style.opacity = '1';
            window.history.replaceState({}, '', url.toString());
        })
        .catch(err => {
            container.style.opacity = '1';
            console.error('TX log fetch failed:', err);
            window.location.href = url.toString();
        });
    }

    function fetchTxPage(pageUrl) {
        const container = document.getElementById('transactionLog');
        container.style.opacity = '0.5';

        fetch(pageUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(r => {
            if (!r.ok) throw new Error('Failed to load');
            return r.text();
        })
        .then(html => {
            container.innerHTML = html;
            container.style.opacity = '1';
            const url = new URL(pageUrl);
            window.history.replaceState({}, '', url.toString());
        })
        .catch(err => {
            container.style.opacity = '1';
            console.error('TX page fetch failed:', err);
            window.location.href = pageUrl;
        });
    }

    function openTxModal() {
        const modal = document.getElementById('txModal');
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function closeTxModal() {
        const modal = document.getElementById('txModal');
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
    function openNoShowModal() {
        const modal = document.getElementById('noShowModal');
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function closeNoShowModal() {
        const modal = document.getElementById('noShowModal');
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    window.addEventListener('click', function(e) {
        if (e.target.id === 'txModal') closeTxModal();
        if (e.target.id === 'noShowModal') closeNoShowModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeTxModal();
            closeNoShowModal();
        }
    });

    /* ---------- MONTHLY SPIKE CHART ---------- */
    const ctx3 = document.getElementById('monthlySpikeChart');
    if (ctx3) {
        new Chart(ctx3.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($monthlySpike['labels']),
                datasets: [
                    {
                        label: 'Bookings',
                        data: @json($monthlySpike['bookings']),
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Completion %',
                        data: @json($monthlySpike['completionRates']),
                        borderColor: '#10b981',
                        borderDash: [5, 5],
                        tension: 0.4,
                        pointRadius: 3,
                        yAxisID: 'y1'
                    },
                    // FIXED: Added no-show rate dataset
                    {
                        label: 'No-Show %',
                        data: @json($monthlySpike['noShowRates']),
                        borderColor: '#ef4444',
                        borderDash: [2, 2],
                        tension: 0.4,
                        pointRadius: 3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { labels: { color: textColor, font: { size: 11 } } },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: gridColor,
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    },
                    y1: {
                        position: 'right',
                        min: 0,
                        max: 100,
                        grid: { display: false },
                        ticks: { color: textColor, callback: v => v + '%' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 10 } }
                    }
                }
            }
        });
    }

    /* ---------- AI CHAT ASSISTANT ---------- */
    let aiChatOpen = false;

    function toggleAiChat() {
        const panel = document.getElementById('aiChatPanel');
        aiChatOpen = !aiChatOpen;
        panel.classList.toggle('hidden', !aiChatOpen);
    }

    function sendAiQuestion(e) {
        e.preventDefault();
        const input = document.getElementById('aiQuestionInput');
        const question = input.value.trim();
        if (!question) return;

        addAiMessage('user', question);
        input.value = '';

        const typingId = addAiMessage('typing', 'Analyzing your business data...');

        const isAdmin = window.location.pathname.includes('/admin/');
        const endpoint = isAdmin
            ? '{{ route("admin.sales.ai-chat") }}'
            : '{{ route("receptionist.sales.ai-chat") }}';

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ question: question })
        })
        .then(r => r.json())
        .then(data => {
            removeAiMessage(typingId);
            if (data.type === 'error') {
                addAiMessage('ai', '⚠️ ' + data.text, 'low');
            } else {
                const confidenceBadge = data.confidence === 'high' ? '✅ High confidence' : 
                                       (data.confidence === 'medium' ? '⚡ Medium confidence' : '❓ Low confidence');
                addAiMessage('ai', data.answer + '\n\n🎯 ' + data.action, data.confidence, confidenceBadge);
            }
        })
        .catch(err => {
            removeAiMessage(typingId);
            addAiMessage('ai', '⚠️ Connection error. Is Ollama running on your laptop?', 'low');
            console.error('AI chat error:', err);
        });
    }

    function addAiMessage(type, text, confidence = null, badge = null) {
        const container = document.getElementById('aiChatMessages');
        const id = 'msg_' + Date.now();
        const div = document.createElement('div');
        div.id = id;
        div.className = 'text-xs';

        if (type === 'user') {
            div.innerHTML = `<div class="bg-gray-100 dark:bg-gray-700 p-3 rounded-lg ml-8 text-gray-800 dark:text-gray-200">${escapeHtml(text)}</div>`;
        } else if (type === 'typing') {
            div.innerHTML = `<div class="bg-teal-50 dark:bg-teal-900/20 p-3 rounded-lg mr-8 flex items-center gap-2 text-teal-700 dark:text-teal-300">
                <span class="w-2 h-2 bg-teal-500 rounded-full animate-bounce"></span>
                <span class="w-2 h-2 bg-teal-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></span>
                <span class="w-2 h-2 bg-teal-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                <span class="ml-1">${escapeHtml(text)}</span>
            </div>`;
        } else {
            const borderColor = confidence === 'high' ? 'border-green-500' : 
                               (confidence === 'medium' ? 'border-amber-500' : 'border-red-500');
            div.innerHTML = `<div class="bg-white dark:bg-gray-700 p-3 rounded-lg mr-8 border-l-4 ${borderColor} shadow-sm">
                <div class="text-gray-700 dark:text-gray-200 whitespace-pre-line">${escapeHtml(text)}</div>
                ${badge ? `<div class="mt-2 text-[10px] font-bold text-gray-400">${badge}</div>` : ''}
            </div>`;
        }

        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
        return id;
    }

    function removeAiMessage(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
@endpush