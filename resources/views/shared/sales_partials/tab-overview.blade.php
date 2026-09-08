@php
    $primaryKpis = [
        ['label' => 'Revenue', 'value' => $currency . number_format($safeTotalRevenue, 2), 'sub' => 'Net after refunds', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>', 'color' => 'brand'],
        ['label' => 'Transactions', 'value' => $safeTotalCount, 'sub' => 'Click for analytics →', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>', 'color' => 'blue', 'action' => 'openTxModal()'],
        ['label' => 'Avg Ticket', 'value' => $currency . number_format($safeAvgSale, 2), 'sub' => 'Per transaction', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>', 'color' => 'purple'],
        ['label' => 'Unique Clients', 'value' => $safeUniqueCustomers, 'sub' => 'Distinct payers', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>', 'color' => 'indigo'],
        ['label' => 'Deposits Held', 'value' => $currency . number_format($safeDeposits, 2), 'sub' => 'Not yet revenue', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>', 'color' => 'amber'],
        ['label' => 'No Shows', 'value' => $safeNoShow, 'sub' => 'Click for details →', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>', 'color' => 'red', 'action' => 'openNoShowModal()']
    ];
@endphp

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    @foreach($primaryKpis as $kpi)
    <div {!! isset($kpi['action']) ? 'onclick="' . $kpi['action'] . '"' : '' !!} class="card-hover bg-white dark:bg-neutral-800 rounded-xl border border-gray-200 dark:border-neutral-700 shadow-sm p-5 relative overflow-hidden group {{ isset($kpi['action']) ? 'cursor-pointer' : '' }}">
        <div class="absolute top-0 right-0 w-20 h-20 bg-{{ $kpi['color'] }}-50 dark:bg-{{ $kpi['color'] }}-900/20 rounded-bl-full -mr-4 -mt-4"></div>
        <div class="relative">
            <div class="flex items-center gap-x-2 mb-3">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-{{ $kpi['color'] }}-100 dark:bg-{{ $kpi['color'] }}-900/40 text-{{ $kpi['color'] }}-600 dark:text-{{ $kpi['color'] }}-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $kpi['icon'] !!}</svg>
                </span>
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-neutral-400">{{ $kpi['label'] }}</p>
            </div>
            <p class="text-xl font-extrabold text-{{ $kpi['color'] }}-600 dark:text-{{ $kpi['color'] }}-400 tracking-tight">{{ $kpi['value'] }}</p>
            <p class="text-[11px] text-gray-400 dark:text-neutral-500 mt-1 font-medium {{ isset($kpi['action']) ? 'text-'.$kpi['color'].'-500 group-hover:underline' : '' }}">{{ $kpi['sub'] }}</p>
        </div>
    </div>
    @endforeach
</div>

@if(count($suggestions) > 0)
<div class="mb-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-brand-100 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </span>
            <h2 class="text-sm font-bold text-gray-800 dark:text-neutral-200 uppercase tracking-wider">Smart Insights</h2>
        </div>
        <span class="inline-flex items-center gap-x-1.5 py-1 px-2.5 rounded-full text-[10px] font-bold {{ $aiOnline ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
            <span class="w-1.5 h-1.5 rounded-full {{ $aiOnline ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></span>
            {{ $aiOnline ? 'AI Online' : 'AI Offline' }}
        </span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($suggestions as $s)
        <div class="card-hover {{ $s['bg'] }} border-l-4 rounded-xl p-5 relative overflow-hidden">
            <div class="flex items-center gap-3 mb-3">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg {{ $s['iconBg'] }} text-lg">{{ $s['icon'] }}</span>
                <div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-neutral-100">{{ $s['title'] }}</h3>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-600">{{ $s['meta'] }}</span>
                </div>
            </div>
            <p class="text-xs text-gray-600 dark:text-neutral-300 leading-relaxed">{{ $s['text'] }}</p>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 bg-white dark:bg-neutral-800 rounded-xl border border-gray-200 dark:border-neutral-700 shadow-sm p-6">
        <h3 class="text-xs font-bold text-gray-700 dark:text-neutral-300 uppercase tracking-wider mb-5">Revenue Trend</h3>
        <div class="relative h-64"><canvas id="salesChart"></canvas></div>
    </div>
    <div class="bg-white dark:bg-neutral-800 rounded-xl border border-gray-200 dark:border-neutral-700 shadow-sm p-6">
        <h3 class="text-xs font-bold text-gray-700 dark:text-neutral-300 uppercase tracking-wider mb-5">Payment Methods</h3>
        <div class="relative h-48 flex items-center justify-center"><canvas id="methodChart"></canvas></div>
    </div>
</div>