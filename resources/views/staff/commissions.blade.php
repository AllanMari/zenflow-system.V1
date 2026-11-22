@extends('layouts.staff')

@section('title', 'My Commissions')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Dark mode Flatpickr overrides */
    .dark .flatpickr-calendar {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
        color: #e2e8f0 !important;
    }
    .dark .flatpickr-calendar .flatpickr-months,
    .dark .flatpickr-calendar .flatpickr-weekdays,
    .dark .flatpickr-calendar .flatpickr-month {
        background: #1e293b !important;
        color: #e2e8f0 !important;
        fill: #e2e8f0 !important;
    }
    .dark .flatpickr-calendar .flatpickr-weekday {
        color: #94a3b8 !important;
    }
    .dark .flatpickr-calendar .flatpickr-day {
        color: #cbd5e1 !important;
    }
    .dark .flatpickr-calendar .flatpickr-day:hover,
    .dark .flatpickr-calendar .flatpickr-day.prevMonthDay:hover,
    .dark .flatpickr-calendar .flatpickr-day.nextMonthDay:hover {
        background: #334155 !important;
        border-color: #334155 !important;
    }
    .dark .flatpickr-calendar .flatpickr-day.selected,
    .dark .flatpickr-calendar .flatpickr-day.startRange,
    .dark .flatpickr-calendar .flatpickr-day.endRange {
        background: #0d9488 !important;
        border-color: #0d9488 !important;
        color: #ffffff !important;
    }
    .dark .flatpickr-calendar .flatpickr-day.inRange {
        background: #134e4a !important;
        border-color: #134e4a !important;
        color: #ccfbf1 !important;
    }
</style>
@endpush

@section('content')
<div class="max-w-[85rem] px-4 pt-4 pb-10 sm:px-6 lg:px-8 lg:pt-6 lg:pb-14 mx-auto space-y-6">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-5 bg-white p-5 rounded-2xl border border-gray-200 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-neutral-200 flex items-center gap-3">
                <span class="inline-flex items-center justify-center size-10 rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                    <svg class="flex-shrink-0 size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 13.5v-3a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 10.5v3z"/>
                    </svg>
                </span>
                My Earnings & Commissions
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-neutral-400">
                Transparent breakdown of your 30% service commission share from completed treatments.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Export CSV --}}
            <a href="{{ route('staff.commissions.csv', request()->query()) }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Export CSV Statement
            </a>
        </div>
    </div>

    <!-- Date Range & Preset Filtering Toolbar -->
    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        {{-- Preset Pills (Daily, Weekly, Monthly, Yearly) --}}
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-xs font-bold text-gray-500 dark:text-neutral-400 uppercase tracking-wider mr-1">Period:</span>
            
            <a href="{{ route('staff.commissions', ['range' => 'today']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $rangePreset === 'today' ? 'bg-brand-600 text-white shadow-sm' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-neutral-800 dark:hover:bg-neutral-700 dark:text-neutral-300' }}">
                Today (Daily)
            </a>

            <a href="{{ route('staff.commissions', ['range' => 'this_week']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $rangePreset === 'this_week' ? 'bg-brand-600 text-white shadow-sm' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-neutral-800 dark:hover:bg-neutral-700 dark:text-neutral-300' }}">
                This Week
            </a>

            <a href="{{ route('staff.commissions', ['range' => 'this_month']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $rangePreset === 'this_month' ? 'bg-brand-600 text-white shadow-sm' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-neutral-800 dark:hover:bg-neutral-700 dark:text-neutral-300' }}">
                This Month
            </a>

            <a href="{{ route('staff.commissions', ['range' => 'this_year']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $rangePreset === 'this_year' ? 'bg-brand-600 text-white shadow-sm' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-neutral-800 dark:hover:bg-neutral-700 dark:text-neutral-300' }}">
                This Year
            </a>
        </div>

        {{-- Custom Date Range Picker via Flatpickr --}}
        <form id="commissionDateForm" method="GET" action="{{ route('staff.commissions') }}" class="flex items-center gap-2">
            <input type="hidden" name="date_from" id="dateFrom" value="{{ $dateFrom }}">
            <input type="hidden" name="date_to" id="dateTo" value="{{ $dateTo }}">
            
            <div class="relative flex-1 sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>
                    </svg>
                </div>
                <input type="text"
                       id="commissionDatePicker"
                       placeholder="Select date range"
                       class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-gray-200 bg-white dark:bg-neutral-800 dark:border-neutral-700 text-xs font-medium text-gray-800 dark:text-neutral-200 focus:outline-none focus:ring-2 focus:ring-brand-500 shadow-sm cursor-pointer"
                       readonly>
            </div>

            @if($rangePreset === 'custom')
                <a href="{{ route('staff.commissions') }}"
                   class="px-2.5 py-1.5 text-xs font-semibold text-gray-500 hover:text-brand-600 dark:text-neutral-400 transition"
                   title="Reset to current month">
                    Reset
                </a>
            @endif
        </form>

    </div>

    <!-- 4 KPI Summary Metric Tiles -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        
        {{-- Tile 1: Total Commission Earned --}}
        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 rounded-2xl p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                    30% Pool Share
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">₱{{ number_format($summary['total_commission'], 2) }}</p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5">Your Total Commission</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Net earned in selected period</p>
            </div>
        </div>

        {{-- Tile 2: Total Net Service Revenue --}}
        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 rounded-2xl p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/40 flex items-center justify-center text-brand-600 dark:text-brand-400">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.25 2.25L15 6" />
                    </svg>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300">
                    Services Rendered
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">₱{{ number_format($summary['total_net'], 2) }}</p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5">Total Service Volume</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Net collected from treatments</p>
            </div>
        </div>

        {{-- Tile 3: Completed Sessions Count --}}
        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 rounded-2xl p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                    Completed
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">{{ $summary['completed_count'] }}</p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5">Finished Appointments</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Avg: ₱{{ number_format($summary['avg_commission'], 2) }}/session</p>
            </div>
        </div>

        {{-- Tile 4: Solo vs Multi-Therapist Ratio --}}
        <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 rounded-2xl p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/40 flex items-center justify-center text-purple-600 dark:text-purple-400">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">
                    Assignment Split
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">{{ $summary['solo_count'] }} <span class="text-sm font-normal text-gray-500">/ {{ $summary['multi_count'] }}</span></p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5">Solo vs Shared Sessions</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">{{ $summary['solo_count'] }} full (30%) • {{ $summary['multi_count'] }} split</p>
            </div>
        </div>

    </div>

    <!-- Detailed Commissions Table -->
    <div class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 rounded-2xl shadow-sm overflow-hidden">
        
        <div class="px-5 py-4 border-b border-gray-100 dark:border-neutral-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Completed Treatment Sessions</h2>
                <p class="text-xs text-gray-500 dark:text-neutral-400">
                    Showing sessions completed between {{ $startDate->format('M j, Y') }} and {{ $endDate->format('M j, Y') }}.
                </p>
            </div>
            <span class="text-xs font-semibold text-gray-500 dark:text-neutral-400">
                {{ $summary['completed_count'] }} {{ Str::plural('record', $summary['completed_count']) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 dark:bg-neutral-800/60 border-b border-gray-200 dark:border-neutral-700 text-[11px] font-bold uppercase tracking-wider text-gray-600 dark:text-neutral-400">
                        <th class="py-3.5 px-4">Date & Time</th>
                        <th class="py-3.5 px-4">Ref #</th>
                        <th class="py-3.5 px-4">Service / Package</th>
                        <th class="py-3.5 px-4">Client</th>
                        <th class="py-3.5 px-4">Room</th>
                        <th class="py-3.5 px-4 text-right">Net Collected</th>
                        <th class="py-3.5 px-4 text-right">30% Pool</th>
                        <th class="py-3.5 px-4">Assignment / Team</th>
                        <th class="py-3.5 px-4 text-right">Your Commission</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-neutral-800 text-xs">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-neutral-800/40 transition">
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $row['date'] }}</p>
                                <p class="text-[11px] text-gray-400 dark:text-neutral-500">{{ $row['time'] }}</p>
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-mono text-xs font-bold text-brand-700 dark:text-brand-400">
                                    #{{ $row['ref'] }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 font-medium text-gray-800 dark:text-neutral-200">
                                {{ $row['service_name'] }}
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap text-gray-700 dark:text-neutral-300">
                                {{ $row['client_name'] }}
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap text-gray-600 dark:text-neutral-400">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="size-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    {{ $row['room_name'] }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap text-right font-medium text-gray-900 dark:text-white">
                                ₱{{ number_format($row['net_amount'], 2) }}
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap text-right text-gray-500 dark:text-neutral-400">
                                ₱{{ number_format($row['total_pool_commission'], 2) }}
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($row['therapists_count'] === 1)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-neutral-300">
                                        Solo (100% of pool)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800/40"
                                          title="{{ $row['team_description'] }}">
                                        Shared ({{ $row['share_percent'] }}% split)
                                    </span>
                                    <p class="text-[10px] text-gray-400 dark:text-neutral-500 mt-0.5 truncate max-w-[140px]" title="{{ $row['team_description'] }}">
                                        {{ $row['team_description'] }}
                                    </p>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                                    ₱{{ number_format($row['my_commission'], 2) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center">
                                <div class="max-w-sm mx-auto text-center space-y-3">
                                    <div class="size-12 rounded-2xl bg-gray-100 dark:bg-neutral-800 text-gray-400 mx-auto flex items-center justify-center">
                                        <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-800 dark:text-neutral-200">No Commission Records Found</h3>
                                    <p class="text-xs text-gray-500 dark:text-neutral-400 leading-relaxed">
                                        You don't have any completed appointments during this period. Once sessions you are assigned to are marked completed and collected, your 30% commission will automatically appear here.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 dark:border-neutral-800">
                {{ $rows->links() }}
            </div>
        @endif

    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pickerInput = document.getElementById('commissionDatePicker');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const form = document.getElementById('commissionDateForm');

    if (pickerInput && window.flatpickr) {
        const defaultDates = [];
        if (dateFrom && dateFrom.value) defaultDates.push(dateFrom.value);
        if (dateTo && dateTo.value) defaultDates.push(dateTo.value);

        flatpickr(pickerInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'M j, Y',
            defaultDate: defaultDates,
            allowInput: false,
            onChange: function (selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const pad = (n) => String(n).padStart(2, '0');
                    const fmt = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                    dateFrom.value = fmt(selectedDates[0]);
                    dateTo.value = fmt(selectedDates[1]);
                    form.submit();
                }
            }
        });
    }
});
</script>
@endpush
