@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Sales Report')

@push('styles')

    {{-- Flatpickr --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        .sales-card {
            transition:
                transform 180ms ease,
                box-shadow 180ms ease,
                border-color 180ms ease;
        }

        .sales-card:hover {
            transform: translateY(-1px);
            box-shadow:
                0 12px 30px -12px rgba(15, 23, 42, 0.16),
                0 4px 12px -6px rgba(15, 23, 42, 0.08);
        }

        .dark .sales-card:hover {
            box-shadow:
                0 12px 30px -12px rgba(0, 0, 0, 0.5),
                0 4px 12px -6px rgba(0, 0, 0, 0.3);
        }

        .sales-tab {
            position: relative;
            transition: color 180ms ease;
        }

        .sales-tab::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -1px;
            height: 2px;
            border-radius: 999px;
            background: currentColor;
            transform: scaleX(0);
            transition: transform 180ms ease;
        }

        .sales-tab.active::after {
            transform: scaleX(1);
        }

        .sales-tab.active {
            color: #0d9488;
        }

        .dark .sales-tab.active {
            color: #2dd4bf;
        }

        .metric-bar {
            transition: width 700ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sales-flatpickr {
            min-width: 250px;
        }

        .flatpickr-calendar {
            border-radius: 14px !important;
            box-shadow:
                0 20px 45px -12px rgba(15, 23, 42, 0.25) !important;
            border: 1px solid #e5e7eb !important;
            overflow: hidden;
        }

        .dark .flatpickr-calendar {
            background: #262626 !important;
            border-color: #404040 !important;
            color: #e5e5e5 !important;
        }

        .dark .flatpickr-months,
        .dark .flatpickr-weekdays {
            background: #262626 !important;
        }

        .dark .flatpickr-current-month,
        .dark .flatpickr-weekday,
        .dark .flatpickr-day {
            color: #e5e5e5 !important;
        }

        .dark .flatpickr-day:hover {
            background: #404040 !important;
        }

        .dark .flatpickr-day.prevMonthDay,
        .dark .flatpickr-day.nextMonthDay {
            color: #737373 !important;
        }

        .dark .flatpickr-time input {
            color: #e5e5e5 !important;
        }

        .dark .numInputWrapper span {
            border-color: #404040 !important;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
@endpush


@section('content')

@php
    $currency = '₱';

    $safeTotalAppts = max((int) ($totalApptsInPeriod ?? 0), 1);
    $safeCompleted = (int) ($completedApptsInPeriod ?? 0);
    $safeCancelled = (int) ($cancelledApptsInPeriod ?? 0);
    $safeNoShow = (int) ($noShowApptsInPeriod ?? 0);

    $safeTotalCount = (int) ($totalCount ?? 0);
    $safeUniqueCustomers = (int) ($uniqueCustomers ?? 0);

    $safeDeposits = (float) ($deposits ?? 0);
    $safeTotalRevenue = (float) ($totalRevenue ?? 0);
    $safeAvgSale = (float) ($avgSale ?? 0);
    $safeRevPerComp = (float) ($revPerCompletedAppt ?? 0);
    $safeRevenueChange = (float) ($revenueChange ?? 0);

    $safeConversionRate = min(
        100,
        max(0, (float) ($conversionRate ?? 0))
    );

    $safeCompletionRate = min(
        100,
        max(
            0,
            round(($safeCompleted / $safeTotalAppts) * 100, 1)
        )
    );

    $safeCancellationRate = min(
        100,
        max(
            0,
            round(($safeCancelled / $safeTotalAppts) * 100, 1)
        )
    );

    $safeNoShowRate = min(
        100,
        max(
            0,
            round(($safeNoShow / $safeTotalAppts) * 100, 1)
        )
    );

    $periodLabel = match ($period ?? 'daily') {
        'daily' => 'Today',
        'weekly' => 'This Week',
        'monthly' => 'This Month',
        'yearly' => 'This Year',
        'custom' => 'Custom Range',
        default => ucfirst($period ?? 'daily'),
    };
@endphp


<div
    x-data="{
        activeTab: 'overview',
        reportMenu: false,
        businessMenu: false
    }"
    class="min-w-0"
>

    {{-- =========================================================
         REPORT CONTROLS
         Header removed because it already exists in master layout.
    ========================================================== --}}

    <div class="mb-6">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

            {{-- LEFT: PERIOD / DATE PICKER --}}
            <div class="flex flex-wrap items-center gap-2">

                <form
                    method="GET"
                    id="salesPeriodForm"
                    class="flex flex-wrap items-center gap-2"
                >

                    {{-- Period Selector --}}
                    <div class="relative">

                        <select
                            name="period"
                            id="salesPeriod"
                            onchange="handleSalesPeriodChange(this)"
                            class="appearance-none h-10 pl-10 pr-9 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 shadow-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200"
                        >

                            <option
                                value="daily"
                                {{ ($period ?? '') === 'daily' ? 'selected' : '' }}
                            >
                                Daily
                            </option>

                            <option
                                value="weekly"
                                {{ ($period ?? '') === 'weekly' ? 'selected' : '' }}
                            >
                                Weekly
                            </option>

                            <option
                                value="monthly"
                                {{ ($period ?? '') === 'monthly' ? 'selected' : '' }}
                            >
                                Monthly
                            </option>

                            <option
                                value="yearly"
                                {{ ($period ?? '') === 'yearly' ? 'selected' : '' }}
                            >
                                Yearly
                            </option>

                            <option
                                value="custom"
                                {{ ($period ?? '') === 'custom' ? 'selected' : '' }}
                            >
                                Custom Range
                            </option>

                        </select>

                        {{-- Calendar Icon --}}
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>

                        {{-- Dropdown Icon --}}
                        <svg
                            class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m6 9 6 6 6-6"
                            />
                        </svg>

                    </div>


                    {{-- CUSTOM DATE RANGE --}}
                    @if(($period ?? '') === 'custom')

                        <div class="relative">

                            {{-- Calendar Icon --}}
                            <svg
                                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 z-10"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2v-4"
                                />
                            </svg>

                            <input
                                type="text"
                                id="salesDateRange"
                                class="sales-flatpickr h-10 pl-10 pr-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 shadow-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200"
                                placeholder="Select date range"
                                autocomplete="off"
                                value="{{ request('start_date') && request('end_date') ? request('start_date') . ' to ' . request('end_date') : '' }}"
                            >

                            <input
                                type="hidden"
                                name="start_date"
                                id="startDate"
                                value="{{ request('start_date', $today->format('Y-m-d')) }}"
                            >

                            <input
                                type="hidden"
                                name="end_date"
                                id="endDate"
                                value="{{ request('end_date', $today->format('Y-m-d')) }}"
                            >

                        </div>


                        {{-- APPLY --}}
                        <button
                            type="submit"
                            class="h-10 px-4 inline-flex items-center gap-2 rounded-xl bg-brand-600 text-white text-sm font-bold shadow-sm hover:bg-brand-700 transition"
                        >
                            Apply
                        </button>


                        {{-- CLEAR --}}
                        <a
                            href="{{ route($routeName) }}"
                            class="h-10 px-3 inline-flex items-center justify-center rounded-xl text-sm font-semibold text-gray-500 hover:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-800 transition"
                        >
                            Clear
                        </a>

                    @else

                        {{-- CURRENT PERIOD --}}
                        <div class="hidden sm:flex h-10 items-center px-3.5 rounded-xl border border-gray-200 bg-white text-xs font-bold text-gray-600 shadow-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">

                            {{ $startDate->format('M d') }}

                            <span class="mx-1.5 text-gray-300 dark:text-neutral-600">
                                —
                            </span>

                            {{ $endDate->format('M d, Y') }}

                        </div>

                    @endif


                    {{-- PRESERVE STATUS FILTER --}}
                    @if(request('status'))

                        <input
                            type="hidden"
                            name="status"
                            value="{{ request('status') }}"
                        >

                    @endif

                </form>

            </div>


            {{-- RIGHT: REPORT BUTTONS --}}
            <div class="flex flex-wrap items-center gap-2">

                {{-- DAILY / PERIOD REPORT --}}
                <div class="relative">

                    <button
                        type="button"
                        @click="reportMenu = !reportMenu; businessMenu = false"
                        class="h-10 px-3.5 inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700 transition"
                    >

                        <svg
                            class="w-4 h-4 text-brand-600 dark:text-brand-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>

                        {{ $periodLabel }} Report

                        <svg
                            class="w-3.5 h-3.5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m6 9 6 6 6-6"
                            />
                        </svg>

                    </button>


                    <div
                        x-show="reportMenu"
                        x-transition
                        @click.away="reportMenu = false"
                        x-cloak
                        class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl z-50 dark:border-neutral-700 dark:bg-neutral-800"
                    >

                        <div class="px-3 py-2 border-b border-gray-100 dark:border-neutral-700">

                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
                                {{ $periodLabel }}
                            </p>

                        </div>


                        {{-- DOWNLOAD PDF --}}
                        <a
                            href="{{ route($routeName . '.daily-report-pdf', array_merge(request()->all(), ['action' => 'download'])) }}"
                            class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:text-neutral-200 dark:hover:bg-neutral-700"
                        >

                            <span class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center dark:bg-red-900/20 dark:text-red-400">

                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"
                                    />
                                </svg>

                            </span>

                            Download PDF

                        </a>


                        {{-- PRINT PREVIEW --}}
                        <a
                            href="{{ route($routeName . '.daily-report-pdf', array_merge(request()->all(), ['action' => 'stream'])) }}"
                            target="_blank"
                            class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-gray-700 border-t border-gray-100 hover:bg-gray-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-700"
                        >

                            <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center dark:bg-blue-900/20 dark:text-blue-400">

                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />

                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="3"
                                    />
                                </svg>

                            </span>

                            Print Preview

                        </a>

                    </div>

                </div>


                {{-- BUSINESS REPORT --}}
                <div class="relative">

                    <button
                        type="button"
                        @click="businessMenu = !businessMenu; reportMenu = false"
                        class="h-10 px-3.5 inline-flex items-center gap-2 rounded-xl bg-gray-900 text-white text-sm font-bold shadow-sm hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-neutral-200 transition"
                    >

                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4-4 4m0 0-4-4m4 4V4"
                            />
                        </svg>

                        Business Report

                        <svg
                            class="w-3.5 h-3.5 opacity-70"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m6 9 6 6 6-6"
                            />
                        </svg>

                    </button>


                    <div
                        x-show="businessMenu"
                        x-transition
                        @click.away="businessMenu = false"
                        x-cloak
                        class="absolute right-0 mt-2 w-52 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl z-50 dark:border-neutral-700 dark:bg-neutral-800"
                    >

                        {{-- DOWNLOAD REPORT --}}
                        <a
                            href="{{ route($routeName . '.business-report-pdf', array_merge(request()->all(), ['action' => 'download'])) }}"
                            class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:text-neutral-200 dark:hover:bg-neutral-700"
                        >

                            <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center dark:bg-brand-900/20 dark:text-brand-400">

                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"
                                    />
                                </svg>

                            </span>

                            Download Report

                        </a>


                        {{-- PRINT PREVIEW --}}
                        <a
                            href="{{ route($routeName . '.business-report-pdf', array_merge(request()->all(), ['action' => 'stream'])) }}"
                            target="_blank"
                            class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-gray-700 border-t border-gray-100 hover:bg-gray-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-700"
                        >

                            <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center dark:bg-blue-900/20 dark:text-blue-400">

                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />

                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="3"
                                    />

                                </svg>

                            </span>

                            Print Preview

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         PERIOD SUMMARY
    ========================================================== --}}

    <div class="mb-6 flex flex-wrap items-center gap-x-3 gap-y-2 text-xs">

        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-50 text-brand-700 font-bold dark:bg-brand-900/20 dark:text-brand-300">

            <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>

            {{ $periodLabel }}

        </span>

        <span class="text-gray-400 dark:text-neutral-500">

            {{ $startDate->format('M d, Y') }}

            <span class="mx-1">
                →
            </span>

            {{ $endDate->format('M d, Y') }}

        </span>

        <span class="hidden sm:inline text-gray-300 dark:text-neutral-700">
            •
        </span>

        <span class="text-gray-500 dark:text-neutral-400">

            {{ number_format($safeTotalAppts) }} appointments

        </span>

    </div>


    {{-- =========================================================
         TABS
    ========================================================== --}}

    <div class="border-b border-gray-200 dark:border-neutral-700 mb-6 overflow-x-auto">

        <nav class="flex gap-6 min-w-max">

            {{-- Overview --}}
            <button
                type="button"
                @click="activeTab = 'overview'"
                :class="{ 'active': activeTab === 'overview' }"
                class="sales-tab py-3 text-sm font-bold text-gray-500 hover:text-gray-800 dark:text-neutral-400 dark:hover:text-neutral-200"
            >
                Overview
            </button>


            {{-- Analytics --}}
            <button
                type="button"
                @click="activeTab = 'analytics'; setTimeout(() => window.dispatchEvent(new Event('resize')), 50)"
                :class="{ 'active': activeTab === 'analytics' }"
                class="sales-tab py-3 text-sm font-bold text-gray-500 hover:text-gray-800 dark:text-neutral-400 dark:hover:text-neutral-200"
            >
                Analytics
            </button>


            {{-- Transactions --}}
            <button
                type="button"
                @click="activeTab = 'transactions'"
                :class="{ 'active': activeTab === 'transactions' }"
                class="sales-tab py-3 text-sm font-bold text-gray-500 hover:text-gray-800 dark:text-neutral-400 dark:hover:text-neutral-200"
            >
                Transactions
            </button>

        </nav>

    </div>


    {{-- =========================================================
         OVERVIEW
    ========================================================== --}}

    <div
        x-show="activeTab === 'overview'"
        x-transition.opacity
    >

        @include('shared.sales_partials.tab-overview')

    </div>


    {{-- =========================================================
         ANALYTICS
    ========================================================== --}}

    <div
        x-show="activeTab === 'analytics'"
        x-cloak
        x-transition.opacity
    >

        @include('shared.sales_partials.tab-analytics')

    </div>


    {{-- =========================================================
         TRANSACTIONS
         DO NOT REDESIGN / CHANGE THE TRANSACTION PARTIAL
    ========================================================== --}}

    <div
        x-show="activeTab === 'transactions'"
        x-cloak
        x-transition.opacity
    >

        <div
            id="transactionLog"
            class="bg-white dark:bg-neutral-800 rounded-2xl border border-gray-200 dark:border-neutral-700 shadow-sm overflow-hidden"
        >

            @include('shared._transaction_log_table', [
                'txLogData' => $txLogData,
                'currentStatus' => $currentStatus,
                'currency' => $currency
            ])

        </div>

    </div>


    {{-- =========================================================
         EXISTING MODALS
    ========================================================== --}}

    @include('shared.sales_partials.modals')


    {{-- =========================================================
         EXISTING AI ASSISTANT
    ========================================================== --}}

    @include('shared.sales_partials.ai-chat')

</div>

@endsection


@push('scripts')

    {{-- =========================================================
         FLATPICKR
    ========================================================== --}}

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


    {{-- =========================================================
         CHART.JS
    ========================================================== --}}

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <script>

        document.addEventListener('DOMContentLoaded', function () {


            /* =====================================================
               FLATPICKR
            ====================================================== */

            const dateRangeInput =
                document.getElementById('salesDateRange');

            const startDateInput =
                document.getElementById('startDate');

            const endDateInput =
                document.getElementById('endDate');


            if (dateRangeInput && window.flatpickr) {

                flatpickr(dateRangeInput, {

                    mode: 'range',

                    dateFormat: 'Y-m-d',

                    altInput: true,

                    altFormat: 'F j, Y',

                    allowInput: false,

                    conjunction: ' to ',

                    defaultDate: [
                        startDateInput?.value || null,
                        endDateInput?.value || null
                    ],


                    onChange: function (selectedDates) {

                        if (!startDateInput || !endDateInput) {
                            return;
                        }


                        if (selectedDates.length >= 1) {

                            const start =
                                selectedDates[0];

                            startDateInput.value =
                                formatFlatpickrDate(start);

                        }


                        if (selectedDates.length >= 2) {

                            const end =
                                selectedDates[1];

                            endDateInput.value =
                                formatFlatpickrDate(end);

                        }

                    }

                });

            }


            function formatFlatpickrDate(date) {

                const year =
                    date.getFullYear();

                const month =
                    String(date.getMonth() + 1)
                        .padStart(2, '0');

                const day =
                    String(date.getDate())
                        .padStart(2, '0');

                return `${year}-${month}-${day}`;

            }



            /* =====================================================
               CHART GLOBALS
            ====================================================== */

            const isDark =
                document.documentElement.classList.contains('dark');

            const gridColor =
                isDark
                    ? 'rgba(255,255,255,0.06)'
                    : 'rgba(0,0,0,0.05)';

            const textColor =
                isDark
                    ? '#a3a3a3'
                    : '#6b7280';

            const tooltipBg =
                isDark
                    ? 'rgba(23,23,23,0.97)'
                    : 'rgba(255,255,255,0.98)';

            const tooltipText =
                isDark
                    ? '#f5f5f5'
                    : '#171717';

            const tooltipBorder =
                isDark
                    ? 'rgba(64,64,64,0.8)'
                    : 'rgba(0,0,0,0.08)';


            if (window.Chart) {

                Chart.defaults.color =
                    textColor;

                Chart.defaults.borderColor =
                    gridColor;

                Chart.defaults.font.family =
                    "'Inter', 'Segoe UI', system-ui, sans-serif";

                Chart.defaults.font.size =
                    11;

            }



            /* =====================================================
               REVENUE TREND
            ====================================================== */

            const salesCanvas =
                document.getElementById('salesChart');


            if (salesCanvas && window.Chart) {

                new Chart(
                    salesCanvas.getContext('2d'),
                    {

                        type: 'bar',

                        data: {

                            labels:
                                @json($chartLabels),

                            datasets: [{

                                label: 'Revenue',

                                data:
                                    @json($chartValues),

                                backgroundColor:
                                    isDark
                                        ? '#14b8a6'
                                        : '#0d9488',

                                borderRadius: 7,

                                borderSkipped: false,

                                barPercentage: 0.55,

                                categoryPercentage: 0.78

                            }]

                        },


                        options: {

                            responsive: true,

                            maintainAspectRatio: false,

                            animation: {
                                duration: 700
                            },


                            plugins: {

                                legend: {
                                    display: false
                                },


                                tooltip: {

                                    backgroundColor:
                                        tooltipBg,

                                    titleColor:
                                        tooltipText,

                                    bodyColor:
                                        tooltipText,

                                    borderColor:
                                        tooltipBorder,

                                    borderWidth: 1,

                                    padding: 12,

                                    cornerRadius: 10,

                                    displayColors: false,


                                    callbacks: {

                                        label: function (ctx) {

                                            return '₱' +
                                                Number(
                                                    ctx.parsed.y || 0
                                                ).toLocaleString(
                                                    undefined,
                                                    {
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2
                                                    }
                                                );

                                        }

                                    }

                                }

                            },


                            scales: {

                                y: {

                                    beginAtZero: true,

                                    grid: {

                                        color:
                                            gridColor,

                                        drawBorder: false

                                    },


                                    ticks: {

                                        color:
                                            textColor,

                                        padding: 8,


                                        callback:
                                            function (value) {

                                                if (value >= 1000) {

                                                    return '₱' +
                                                        (value / 1000).toFixed(1) +
                                                        'k';

                                                }

                                                return '₱' +
                                                    Number(value)
                                                        .toLocaleString();

                                            }

                                    },


                                    border: {
                                        display: false
                                    }

                                },


                                x: {

                                    grid: {
                                        display: false
                                    },


                                    ticks: {

                                        color:
                                            textColor,

                                        padding: 8

                                    },


                                    border: {
                                        display: false
                                    }

                                }

                            }

                        }

                    }
                );

            }



            /* =====================================================
               PAYMENT METHODS
            ====================================================== */

            const methodCanvas =
                document.getElementById('methodChart');


            if (methodCanvas && window.Chart) {

                new Chart(
                    methodCanvas.getContext('2d'),
                    {

                        type: 'doughnut',

                        data: {

                            labels:
                                @json($methodBreakdown->keys()->values()),


                            datasets: [{

                                data:
                                    @json($methodBreakdown->pluck('total')->values()),

                                backgroundColor: [
                                    '#0d9488',
                                    '#3b82f6',
                                    '#f59e0b',
                                    '#ef4444',
                                    '#8b5cf6',
                                    '#ec4899',
                                    '#10b981',
                                    '#06b6d4'
                                ],

                                borderWidth: 0,

                                hoverOffset: 7

                            }]

                        },


                        options: {

                            responsive: true,

                            maintainAspectRatio: false,

                            cutout: '70%',


                            plugins: {

                                legend: {

                                    position: 'bottom',


                                    labels: {

                                        color:
                                            textColor,

                                        boxWidth: 9,

                                        padding: 12,

                                        usePointStyle: true,

                                        pointStyle: 'circle'

                                    }

                                },


                                tooltip: {

                                    backgroundColor:
                                        tooltipBg,

                                    titleColor:
                                        tooltipText,

                                    bodyColor:
                                        tooltipText,

                                    borderColor:
                                        tooltipBorder,

                                    borderWidth: 1,

                                    padding: 10,

                                    cornerRadius: 8,


                                    callbacks: {

                                        label: function (ctx) {

                                            const value =
                                                Number(
                                                    ctx.parsed || 0
                                                );

                                            const total =
                                                ctx.dataset.data.reduce(
                                                    (a, b) =>
                                                        Number(a) +
                                                        Number(b),
                                                    0
                                                );


                                            const percentage =
                                                total > 0
                                                    ? Math.round(
                                                        (value / total) * 100
                                                    )
                                                    : 0;


                                            return ctx.label +
                                                ': ₱' +
                                                value.toLocaleString(
                                                    undefined,
                                                    {
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2
                                                    }
                                                ) +
                                                ' (' +
                                                percentage +
                                                '%)';

                                        }

                                    }

                                }

                            }

                        }

                    }
                );

            }



            /* =====================================================
               MONTHLY TREND
            ====================================================== */

            const monthlyCanvas =
                document.getElementById('monthlySpikeChart');


            if (monthlyCanvas && window.Chart) {

                new Chart(
                    monthlyCanvas.getContext('2d'),
                    {

                        type: 'line',


                        data: {

                            labels:
                                @json($monthlySpike['labels']),


                            datasets: [

                                {

                                    label: 'Bookings',

                                    data:
                                        @json($monthlySpike['bookings']),

                                    borderColor:
                                        '#8b5cf6',

                                    backgroundColor:
                                        'rgba(139, 92, 246, 0.08)',

                                    fill: true,

                                    tension: 0.4,

                                    borderWidth: 2.5,

                                    pointRadius: 2.5,

                                    pointHoverRadius: 5

                                },


                                {

                                    label: 'Completion %',

                                    data:
                                        @json($monthlySpike['completionRates']),

                                    borderColor:
                                        '#10b981',

                                    borderDash: [
                                        6,
                                        4
                                    ],

                                    tension: 0.4,

                                    borderWidth: 2,

                                    pointRadius: 2,

                                    yAxisID: 'y1'

                                },


                                {

                                    label: 'No-Show %',

                                    data:
                                        @json($monthlySpike['noShowRates']),

                                    borderColor:
                                        '#ef4444',

                                    borderDash: [
                                        3,
                                        3
                                    ],

                                    tension: 0.4,

                                    borderWidth: 2,

                                    pointRadius: 2,

                                    yAxisID: 'y1'

                                }

                            ]

                        },


                        options: {

                            responsive: true,

                            maintainAspectRatio: false,


                            interaction: {

                                mode: 'index',

                                intersect: false

                            },


                            plugins: {

                                legend: {

                                    position: 'top',

                                    align: 'end',


                                    labels: {

                                        color:
                                            textColor,

                                        boxWidth: 9,

                                        usePointStyle: true,

                                        pointStyle: 'circle'

                                    }

                                },


                                tooltip: {

                                    backgroundColor:
                                        tooltipBg,

                                    titleColor:
                                        tooltipText,

                                    bodyColor:
                                        tooltipText,

                                    borderColor:
                                        tooltipBorder,

                                    borderWidth: 1,

                                    padding: 10,

                                    cornerRadius: 8

                                }

                            },


                            scales: {

                                y: {

                                    beginAtZero: true,


                                    grid: {

                                        color:
                                            gridColor,

                                        drawBorder: false

                                    },


                                    ticks: {

                                        color:
                                            textColor,

                                        padding: 8

                                    },


                                    border: {

                                        display: false

                                    }

                                },


                                y1: {

                                    position: 'right',

                                    min: 0,

                                    max: 100,


                                    grid: {

                                        display: false

                                    },


                                    ticks: {

                                        color:
                                            textColor,

                                        callback:
                                            value => value + '%',

                                        padding: 8

                                    },


                                    border: {

                                        display: false

                                    }

                                },


                                x: {

                                    grid: {

                                        display: false

                                    },


                                    ticks: {

                                        color:
                                            textColor,

                                        padding: 8

                                    },


                                    border: {

                                        display: false

                                    }

                                }

                            }

                        }

                    }
                );

            }

        });



        /* =========================================================
           PERIOD CHANGE
        ========================================================== */

        function handleSalesPeriodChange(select) {

            const form =
                document.getElementById('salesPeriodForm');


            if (!form) {
                return;
            }


            if (select.value === 'custom') {

                const currentUrl =
                    new URL(window.location.href);


                currentUrl.searchParams.set(
                    'period',
                    'custom'
                );


                currentUrl.searchParams.delete(
                    'page'
                );


                window.location.href =
                    currentUrl.toString();


                return;
            }


            form.submit();

        }



        /* =========================================================
           TRANSACTION STATUS FILTER
        ========================================================== */

        function updateStatusFilter() {

            const filter =
                document.getElementById('statusFilter');


            if (!filter) {
                return;
            }


            const value =
                filter.value;


            const url =
                new URL(window.location.href);


            if (value) {

                url.searchParams.set(
                    'status',
                    value
                );

            } else {

                url.searchParams.delete(
                    'status'
                );

            }


            url.searchParams.delete(
                'page'
            );


            const isAdmin =
                window.location.pathname.includes('/admin/');


            const endpoint =
                isAdmin
                    ? '{{ route("admin.sales.tx-log") }}'
                    : '{{ route("receptionist.sales.tx-log") }}';


            const container =
                document.getElementById('transactionLog');


            if (!container) {

                window.location.href =
                    url.toString();

                return;

            }


            container.style.opacity =
                '0.45';


            fetch(
                `${endpoint}?${url.searchParams.toString()}`,
                {
                    headers: {
                        'X-Requested-With':
                            'XMLHttpRequest',

                        'Accept':
                            'text/html'
                    }
                }
            )

            .then(response => {

                if (!response.ok) {
                    throw new Error(
                        'Request failed'
                    );
                }

                return response.text();

            })


            .then(html => {

                container.innerHTML =
                    html;

                container.style.opacity =
                    '1';

                window.history.replaceState(
                    {},
                    '',
                    url.toString()
                );

            })


            .catch(() => {

                window.location.href =
                    url.toString();

            });

        }



        /* =========================================================
           TRANSACTION PAGINATION
        ========================================================== */

        function fetchTxPage(pageUrl) {

            const container =
                document.getElementById('transactionLog');


            if (!container) {

                window.location.href =
                    pageUrl;

                return;

            }


            container.style.opacity =
                '0.45';


            const urlObj =
                new URL(
                    pageUrl,
                    window.location.origin
                );


            const isAdmin =
                window.location.pathname.includes('/admin/');


            const endpoint =
                isAdmin
                    ? '{{ route("admin.sales.tx-log") }}'
                    : '{{ route("receptionist.sales.tx-log") }}';


            fetch(
                `${endpoint}?${urlObj.searchParams.toString()}`,
                {
                    headers: {
                        'X-Requested-With':
                            'XMLHttpRequest',

                        'Accept':
                            'text/html'
                    }
                }
            )

            .then(response => {

                if (!response.ok) {

                    throw new Error(
                        'Failed to load page'
                    );

                }

                return response.text();

            })


            .then(html => {

                container.innerHTML =
                    html;

                container.style.opacity =
                    '1';

                window.history.replaceState(
                    {},
                    '',
                    urlObj.toString()
                );

            })


            .catch(() => {

                window.location.href =
                    pageUrl;

            });

        }



        /* =========================================================
           MODALS
        ========================================================== */

        function openTxModal() {

            const modal =
                document.getElementById('txModal');


            if (!modal) {
                return;
            }


            modal.classList.remove(
                'hidden'
            );


            document.body.style.overflow =
                'hidden';

        }


        function closeTxModal() {

            const modal =
                document.getElementById('txModal');


            if (!modal) {
                return;
            }


            modal.classList.add(
                'hidden'
            );


            document.body.style.overflow =
                '';

        }


        function openNoShowModal() {

            const modal =
                document.getElementById('noShowModal');


            if (!modal) {
                return;
            }


            modal.classList.remove(
                'hidden'
            );


            document.body.style.overflow =
                'hidden';

        }


        function closeNoShowModal() {

            const modal =
                document.getElementById('noShowModal');


            if (!modal) {
                return;
            }


            modal.classList.add(
                'hidden'
            );


            document.body.style.overflow =
                '';

        }


        window.addEventListener(
            'click',
            function (event) {

                if (
                    event.target?.id ===
                    'txModal'
                ) {

                    closeTxModal();

                }


                if (
                    event.target?.id ===
                    'noShowModal'
                ) {

                    closeNoShowModal();

                }

            }
        );



        /* =========================================================
           AI CHAT
        ========================================================== */

        let aiChatHistory = [];

        let aiChatOpen = false;

        let aiIsTyping = false;



        function toggleAiChat() {

            aiChatOpen =
                !aiChatOpen;


            const panel =
                document.getElementById(
                    'aiChatPanel'
                );


            if (!panel) {
                return;
            }


            panel.classList.toggle(
                'hidden',
                !aiChatOpen
            );


            if (aiChatOpen) {

                setTimeout(() => {

                    document
                        .getElementById(
                            'aiQuestionInput'
                        )
                        ?.focus();

                }, 100);

            }

        }



        function clearAiChat() {

            aiChatHistory = [];

            aiIsTyping = false;


            const container =
                document.getElementById(
                    'aiChatMessages'
                );


            if (!container) {
                return;
            }


            container.innerHTML = `

                <div class="bg-brand-50 dark:bg-brand-900/20 p-4 rounded-xl text-xs text-gray-600 dark:text-neutral-300 border border-brand-100 dark:border-brand-800">

                    <p class="font-bold text-brand-700 dark:text-brand-300 mb-1 text-sm">

                        👋 Hey there! I'm Mari, your spa business advisor.

                    </p>


                    <p class="leading-relaxed">

                        Ask me anything about your sales, appointments, or business strategy. I'm here to help! ✨

                    </p>

                </div>

            `;

        }



        function sendAiQuestion(event) {

            event.preventDefault();


            if (aiIsTyping) {
                return;
            }


            const input =
                document.getElementById(
                    'aiQuestionInput'
                );


            if (!input) {
                return;
            }


            const question =
                input.value.trim();


            if (!question) {
                return;
            }


            addAiMessage(
                'user',
                question
            );


            input.value =
                '';


            aiIsTyping =
                true;


            const typingId =
                addAiMessage(
                    'typing',
                    'Mari is thinking...'
                );


            const params =
                new URLSearchParams(
                    window.location.search
                );


            const endpoint =
                window.location.pathname.includes('/admin/')
                    ? '{{ route("admin.sales.ai-chat") }}'
                    : '{{ route("receptionist.sales.ai-chat") }}';


            fetch(
                endpoint,
                {
                    method: 'POST',

                    headers: {

                        'Content-Type':
                            'application/json',

                        'X-CSRF-TOKEN':
                            '{{ csrf_token() }}',

                        'Accept':
                            'application/json'

                    },

                    body: JSON.stringify({

                        question:
                            question,

                        history:
                            aiChatHistory,

                        period:
                            params.get('period') ||
                            'daily',

                        start_date:
                            params.get('start_date') ||
                            '',

                        end_date:
                            params.get('end_date') ||
                            ''

                    })

                }
            )

            .then(response => response.json())


            .then(data => {

                if (data.type === 'error') {

                    addAiMessage(
                        'ai',
                        data.text,
                        'low'
                    );

                    return;

                }


                addAiMessage(

                    'ai',

                    data.answer,

                    data.confidence,

                    data.confidence === 'high'
                        ? '✅ High confidence'
                        : '⚡ Medium confidence',

                    data.mood === 'celebratory'
                        ? '🎉'
                        : '🤖',

                    data.action

                );


                aiChatHistory.push({

                    role:
                        'user',

                    content:
                        question

                });


                aiChatHistory.push({

                    role:
                        'assistant',

                    content:
                        data.answer

                });


                if (aiChatHistory.length > 20) {

                    aiChatHistory =
                        aiChatHistory.slice(-20);

                }

            })


            .catch(() => {

                addAiMessage(

                    'ai',

                    'Oops! Lost connection to Ollama. 🔌',

                    'low'

                );

            })


            .finally(() => {

                removeAiMessage(
                    typingId
                );

                aiIsTyping =
                    false;

            });

        }



        function addAiMessage(
            type,
            text,
            confidence = null,
            badge = null,
            moodEmoji = null,
            action = null
        ) {

            const container =
                document.getElementById(
                    'aiChatMessages'
                );


            if (!container) {
                return null;
            }


            const id =
                'msg_' + Date.now();


            const div =
                document.createElement(
                    'div'
                );


            div.id =
                id;


            div.className =
                'text-xs animate-fade-in';


            if (type === 'user') {

                div.innerHTML = `

                    <div class="bg-gray-100 dark:bg-neutral-700 p-3 rounded-xl ml-10 text-gray-800 dark:text-neutral-200 text-sm font-medium shadow-sm">

                        ${escapeHtml(text)}

                    </div>

                `;

            }


            else if (type === 'typing') {

                div.innerHTML = `

                    <div class="bg-brand-50 dark:bg-brand-900/20 p-3 rounded-xl mr-10 flex items-center gap-2 text-brand-700 dark:text-brand-300 border border-brand-100 dark:border-brand-800">

                        <span class="w-2 h-2 bg-brand-500 rounded-full animate-bounce"></span>

                        <span class="ml-1 text-xs font-medium">

                            ${escapeHtml(text)}

                        </span>

                    </div>

                `;

            }


            else {

                div.innerHTML = `

                    <div class="bg-white dark:bg-neutral-700 p-3.5 rounded-xl mr-10 border-l-4 border-green-500 shadow-sm">

                        <div class="flex items-center gap-1.5 mb-1.5">

                            <span class="text-base">
                                ${moodEmoji || '🤖'}
                            </span>

                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">

                                Mari

                            </span>

                        </div>


                        <div class="text-gray-700 dark:text-neutral-200 whitespace-pre-line leading-relaxed text-sm">

                            ${escapeHtml(text)}

                        </div>


                        ${
                            action
                                ? `

                                    <div class="mt-2 p-2.5 bg-brand-50 dark:bg-brand-900/30 rounded-lg">

                                        <span class="text-[10px] font-bold text-brand-700 uppercase tracking-wider">

                                            💡 Action

                                        </span>


                                        <p class="text-xs text-brand-800 dark:text-brand-200 mt-0.5">

                                            ${escapeHtml(action)}

                                        </p>

                                    </div>

                                `
                                : ''
                        }

                    </div>

                `;

            }


            container.appendChild(
                div
            );


            container.scrollTop =
                container.scrollHeight;


            return id;

        }



        function removeAiMessage(id) {

            document
                .getElementById(id)
                ?.remove();

        }



        function escapeHtml(text) {

            const element =
                document.createElement(
                    'div'
                );


            element.textContent =
                text ?? '';


            return element.innerHTML;

        }

    </script>

@endpush