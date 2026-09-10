@php
    $primaryKpis = [
        [
            'label' => 'Revenue',
            'value' => $currency . number_format($safeTotalRevenue, 2),
            'sub' => 'Net after refunds',
            'color' => 'brand',
            'action' => null,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
        ],
        [
            'label' => 'Transactions',
            'value' => $safeTotalCount,
            'sub' => 'View transaction log',
            'color' => 'blue',
            'action' => 'openTxModal()',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>'
        ],
        [
            'label' => 'Avg Ticket',
            'value' => $currency . number_format($safeAvgSale, 2),
            'sub' => 'Per transaction',
            'color' => 'purple',
            'action' => null,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>'
        ],
        [
            'label' => 'Unique Clients',
            'value' => $safeUniqueCustomers,
            'sub' => 'Distinct payers',
            'color' => 'indigo',
            'action' => null,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>'
        ],
        [
            'label' => 'Deposits Held',
            'value' => $currency . number_format($safeDeposits, 2),
            'sub' => 'Not yet recognized as revenue',
            'color' => 'amber',
            'action' => null,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>'
        ],
        [
            'label' => 'No Shows',
            'value' => $safeNoShow,
            'sub' => 'View no-show details',
            'color' => 'red',
            'action' => 'openNoShowModal()',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
        ]
    ];

    $kpiColors = [
        'brand' => [
            'icon' => 'bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400',
            'value' => 'text-brand-700 dark:text-brand-300'
        ],
        'blue' => [
            'icon' => 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
            'value' => 'text-blue-700 dark:text-blue-300'
        ],
        'purple' => [
            'icon' => 'bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
            'value' => 'text-purple-700 dark:text-purple-300'
        ],
        'indigo' => [
            'icon' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400',
            'value' => 'text-indigo-700 dark:text-indigo-300'
        ],
        'amber' => [
            'icon' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
            'value' => 'text-amber-700 dark:text-amber-300'
        ],
        'red' => [
            'icon' => 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400',
            'value' => 'text-red-700 dark:text-red-300'
        ]
    ];
@endphp


{{-- =========================================================
     KPI GRID
========================================================= --}}
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">

    @foreach($primaryKpis as $kpi)

        @php
            $kpiStyle = $kpiColors[$kpi['color']];
        @endphp

        <div
            @if($kpi['action'])
                onclick="{{ $kpi['action'] }}"
            @endif
            class="sales-card group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800
                {{ $kpi['action'] ? 'cursor-pointer' : '' }}"
        >

            <div class="flex items-start justify-between gap-2">

                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400 dark:text-neutral-500">
                        {{ $kpi['label'] }}
                    </p>

                    <p class="mt-2 text-xl font-extrabold tracking-tight {{ $kpiStyle['value'] }}">
                        {{ $kpi['value'] }}
                    </p>
                </div>

                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $kpiStyle['icon'] }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $kpi['icon'] !!}
                    </svg>
                </span>

            </div>

            <p class="mt-2 text-[10px] font-medium text-gray-400 dark:text-neutral-500">
                {{ $kpi['sub'] }}
            </p>

            @if($kpi['action'])
                <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-current opacity-0 group-hover:opacity-100 {{ $kpiStyle['value'] }}"></div>
            @endif

        </div>

    @endforeach

</div>


{{-- =========================================================
     SMART INSIGHTS
========================================================= --}}
@if(count($suggestions ?? []) > 0)

    <section class="mb-6">

        <div class="flex items-center justify-between mb-3">

            <div>
                <div class="flex items-center gap-2">

                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </span>

                    <div>
                        <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">
                            Smart Insights
                        </h2>

                        <p class="text-[10px] text-gray-400 dark:text-neutral-500">
                            Highlights from the selected period
                        </p>
                    </div>

                </div>
            </div>

            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold
                {{ $aiOnline
                    ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                    : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'
                }}"
            >
                <span class="w-1.5 h-1.5 rounded-full {{ $aiOnline ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></span>

                {{ $aiOnline ? 'AI Online' : 'AI Offline' }}
            </span>

        </div>


        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">

            @foreach($suggestions as $s)

                <div class="sales-card rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

                    <div class="flex items-start gap-3">

                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $s['iconBg'] }} text-lg">
                            {{ $s['icon'] }}
                        </span>

                        <div class="min-w-0">

                            <div class="flex flex-wrap items-center gap-2">

                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $s['title'] }}
                                </h3>

                                <span class="text-[9px] font-bold uppercase tracking-wider text-gray-400 dark:text-neutral-500">
                                    {{ $s['meta'] }}
                                </span>

                            </div>

                            <p class="mt-2 text-xs leading-relaxed text-gray-500 dark:text-neutral-300">
                                {{ $s['text'] }}
                            </p>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    </section>

@endif


{{-- =========================================================
     MAIN CHARTS
========================================================= --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">

    {{-- Revenue --}}
    <section class="xl:col-span-2 sales-card rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

        <div class="flex items-center justify-between px-5 pt-5">

            <div>
                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">
                    Revenue Trend
                </h3>

                <p class="mt-0.5 text-[11px] text-gray-400 dark:text-neutral-500">
                    Revenue across the selected period
                </p>
            </div>

            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-[10px] font-bold text-brand-700 dark:bg-brand-900/20 dark:text-brand-300">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>
                Revenue
            </span>

        </div>

        <div class="h-72 p-5">
            <canvas id="salesChart"></canvas>
        </div>

    </section>


    {{-- Payment Methods --}}
    <section class="sales-card rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

        <div class="px-5 pt-5">

            <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">
                Payment Methods
            </h3>

            <p class="mt-0.5 text-[11px] text-gray-400 dark:text-neutral-500">
                Distribution of recorded payments
            </p>

        </div>

        <div class="h-64 p-5">
            <canvas id="methodChart"></canvas>
        </div>

    </section>

</div>


{{-- =========================================================
     QUICK METRICS
========================================================= --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-3">

    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-neutral-700 dark:bg-neutral-800/60">

        <div class="flex items-center gap-3">

            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M5 13l4 4L19 7"/>
                </svg>
            </span>

            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-neutral-500">
                    Completion Rate
                </p>

                <p class="text-lg font-extrabold text-gray-900 dark:text-white">
                    {{ $safeCompletionRate }}%
                </p>
            </div>

        </div>

    </div>


    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-neutral-700 dark:bg-neutral-800/60">

        <div class="flex items-center gap-3">

            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M12 9v2m0 4h.01M10.29 3.86l-7.5 13A2 2 0 004.54 20h14.92a2 2 0 001.75-3l-7.5-13a2 2 0 00-3.42 0z"/>
                </svg>
            </span>

            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-neutral-500">
                    No-Show Rate
                </p>

                <p class="text-lg font-extrabold text-gray-900 dark:text-white">
                    {{ $safeNoShowRate }}%
                </p>
            </div>

        </div>

    </div>


    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-neutral-700 dark:bg-neutral-800/60">

        <div class="flex items-center gap-3">

            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>

            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-neutral-500">
                    Revenue / Completed
                </p>

                <p class="text-lg font-extrabold text-gray-900 dark:text-white">
                    {{ $currency }}{{ number_format($safeRevPerComp, 2) }}
                </p>
            </div>

        </div>

    </div>

</div>