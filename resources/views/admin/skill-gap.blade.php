@extends('layouts.admin')

@section('title', 'Skill Gap Analytics')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>
    .skill-gap-page {
        max-width: 1600px;
        margin: 0 auto;
    }

    .skill-gap-card {
        border: 1px solid rgb(229 231 235);
        background: #fff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    }

    .dark .skill-gap-card {
        border-color: rgba(51, 65, 85, .7);
        background: #1e293b;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .18);
    }

    .summary-icon {
        width: 38px;
        height: 38px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #f0fdfa;
        color: #0d9488;
    }

    .dark .summary-icon {
        background: rgba(20, 184, 166, .12);
        color: #5eead4;
    }

    .metric-value {
        line-height: 1;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 9999px;
        flex-shrink: 0;
    }

    .status-covered {
        background: rgb(16 185 129);
    }

    .status-moderate {
        background: rgb(245 158 11);
    }

    .status-high {
        background: rgb(249 115 22);
    }

    .status-critical {
        background: rgb(239 68 68);
    }

    .status-none {
        background: rgb(156 163 175);
    }

    .staff-avatar {
        width: 28px;
        height: 28px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f0fdfa;
        color: #0d9488;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .dark .staff-avatar {
        background: rgba(20, 184, 166, .14);
        color: #5eead4;
    }

    .table-scroll {
        overflow-x: auto;
        scrollbar-width: thin;
    }

    .table-scroll::-webkit-scrollbar {
        height: 6px;
    }

    .table-scroll::-webkit-scrollbar-thumb {
        background: rgb(156 163 175);
        border-radius: 9999px;
    }

    .dark .table-scroll::-webkit-scrollbar-thumb {
        background: #475569;
    }

    .mobile-separator + .mobile-separator {
        border-top: 1px solid rgb(229 231 235);
    }

    .dark .mobile-separator + .mobile-separator {
        border-color: rgba(51, 65, 85, .65);
    }

    .flatpickr-calendar {
        font-family: inherit;
    }

    .dark .flatpickr-calendar {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .35);
    }

    .dark .flatpickr-calendar .flatpickr-months,
    .dark .flatpickr-calendar .flatpickr-weekdays {
        background: #1e293b;
    }

    .dark .flatpickr-calendar .flatpickr-month,
    .dark .flatpickr-calendar .flatpickr-weekday,
    .dark .flatpickr-calendar .flatpickr-current-month,
    .dark .flatpickr-calendar .flatpickr-current-month input.cur-year {
        color: #e2e8f0;
        fill: #e2e8f0;
    }

    .dark .flatpickr-calendar .flatpickr-monthDropdown-months {
        background: #1e293b;
        color: #e2e8f0;
    }

    .dark .flatpickr-calendar .flatpickr-months .flatpickr-prev-month,
    .dark .flatpickr-calendar .flatpickr-months .flatpickr-next-month {
        color: #94a3b8;
        fill: #94a3b8;
    }

    .dark .flatpickr-calendar .flatpickr-months .flatpickr-prev-month:hover,
    .dark .flatpickr-calendar .flatpickr-months .flatpickr-next-month:hover {
        color: #5eead4;
        fill: #5eead4;
    }

    .dark .flatpickr-calendar .flatpickr-weekday {
        color: #94a3b8;
    }

    .dark .flatpickr-calendar .flatpickr-day {
        color: #cbd5e1;
        border-color: transparent;
    }

    .dark .flatpickr-calendar .flatpickr-day:hover {
        background: #334155;
        border-color: #334155;
    }

    .dark .flatpickr-calendar .flatpickr-day.selected,
    .dark .flatpickr-calendar .flatpickr-day.startRange,
    .dark .flatpickr-calendar .flatpickr-day.endRange {
        background: #0d9488;
        border-color: #0d9488;
        color: #fff;
    }

    .dark .flatpickr-calendar .flatpickr-day.inRange {
        background: rgba(20, 184, 166, .18);
        border-color: transparent;
        box-shadow: -5px 0 0 rgba(20, 184, 166, .18),
                    5px 0 0 rgba(20, 184, 166, .18);
    }

    .dark .flatpickr-calendar .flatpickr-day.today {
        border-color: #2dd4bf;
    }

    .dark .flatpickr-calendar .flatpickr-day.prevMonthDay,
    .dark .flatpickr-calendar .flatpickr-day.nextMonthDay {
        color: #64748b;
    }

    .dark .flatpickr-calendar .flatpickr-day.disabled,
    .dark .flatpickr-calendar .flatpickr-day.disabled:hover {
        color: #475569;
    }

    .dark .flatpickr-calendar .flatpickr-time {
        border-color: #334155;
    }

    .dark .flatpickr-calendar .flatpickr-time input,
    .dark .flatpickr-calendar .flatpickr-time .flatpickr-am-pm {
        color: #e2e8f0;
        background: #1e293b;
    }

    .dark .flatpickr-calendar .flatpickr-time input:hover,
    .dark .flatpickr-calendar .flatpickr-time .flatpickr-am-pm:hover {
        background: #334155;
    }

    .quick-filter-active {
        background: #0d9488;
        border-color: #0d9488;
        color: #fff;
    }

    .quick-filter-active:hover {
        background: #0f766e;
        border-color: #0f766e;
    }
</style>
@endpush

@section('content')
<div class="skill-gap-page space-y-5">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-500 dark:text-slate-400">
                Compare completed service demand with assigned staff, service time, and scheduled workforce hours.
            </p>
        </div>

        <button
            type="button"
            id="skillGapInfoBtn"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
        >
            <i data-lucide="circle-help" class="size-4"></i>
            What does this report show?
        </button>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">

        <div class="skill-gap-card rounded-xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-400">
                        Total Services
                    </p>
                    <p class="metric-value mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ number_format($summary['total_services']) }}
                    </p>
                </div>

                <div class="summary-icon">
                    <i data-lucide="layers-3" class="size-5"></i>
                </div>
            </div>
        </div>

        <div class="skill-gap-card rounded-xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-400">
                        With Demand
                    </p>
                    <p class="metric-value mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ number_format($summary['services_with_demand']) }}
                    </p>
                </div>

                <div class="summary-icon">
                    <i data-lucide="chart-column" class="size-5"></i>
                </div>
            </div>
        </div>

        <div class="skill-gap-card rounded-xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-400">
                        No Staff
                    </p>
                    <p class="metric-value mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">
                        {{ number_format($summary['services_without_staff']) }}
                    </p>
                </div>

                <div class="summary-icon">
                    <i data-lucide="user-round-x" class="size-5"></i>
                </div>
            </div>
        </div>

        <div class="skill-gap-card rounded-xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-400">
                        Bottlenecks
                    </p>
                    <p class="metric-value mt-2 text-2xl font-semibold text-orange-600 dark:text-orange-400">
                        {{ number_format($summary['bottleneck_services']) }}
                    </p>
                </div>

                <div class="summary-icon">
                    <i data-lucide="triangle-alert" class="size-5"></i>
                </div>
            </div>
        </div>

        <div class="skill-gap-card rounded-xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-400">
                        Demand Hours
                    </p>
                    <p class="metric-value mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ number_format($summary['total_demand_hours'], 2) }}
                    </p>
                </div>

                <div class="summary-icon">
                    <i data-lucide="clock-3" class="size-5"></i>
                </div>
            </div>
        </div>

        <div class="skill-gap-card rounded-xl p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-slate-400">
                        Scheduled Hours
                    </p>
                    <p class="metric-value mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ number_format($summary['total_scheduled_hours'], 2) }}
                    </p>
                </div>

                <div class="summary-icon">
                    <i data-lucide="calendar-clock" class="size-5"></i>
                </div>
            </div>
        </div>

    </div>

    <div class="skill-gap-card overflow-hidden rounded-xl">

        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">

                <div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="scan-search" class="size-5 text-teal-600 dark:text-teal-400"></i>

                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                            Service Skill Gap Analysis
                        </h2>
                    </div>

                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                        Completed service demand compared with assigned staff and their scheduled working hours.
                    </p>
                </div>

                <form
                    method="GET"
                    action="{{ route('admin.skill-gap') }}"
                    id="skillGapFilterForm"
                    class="flex flex-col gap-2"
                >

                    <div class="flex flex-wrap items-center gap-1.5">

                        <button
                            type="button"
                            data-range="today"
                            class="quick-filter inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            <i data-lucide="calendar-days" class="size-3.5"></i>
                            Today
                        </button>

                        <button
                            type="button"
                            data-range="week"
                            class="quick-filter inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            <i data-lucide="calendar-range" class="size-3.5"></i>
                            This Week
                        </button>

                        <button
                            type="button"
                            data-range="month"
                            class="quick-filter inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            <i data-lucide="calendar" class="size-3.5"></i>
                            This Month
                        </button>

                        <button
                            type="button"
                            data-range="year"
                            class="quick-filter inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            <i data-lucide="calendar-clock" class="size-3.5"></i>
                            This Year
                        </button>

                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">

                        <div class="relative flex-1">
                            <i
                                data-lucide="calendar-range"
                                class="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-gray-400 dark:text-slate-500"
                            ></i>

                            <input
                                type="text"
                                id="date_range"
                                class="block w-full rounded-lg border-gray-200 bg-white py-2 pl-9 pr-3 text-sm text-gray-700 shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 sm:w-64"
                                placeholder="Select date range"
                                autocomplete="off"
                            >

                            <input
                                type="hidden"
                                name="date_from"
                                id="date_from"
                                value="{{ $dateFrom }}"
                            >

                            <input
                                type="hidden"
                                name="date_to"
                                id="date_to"
                                value="{{ $dateTo }}"
                            >
                        </div>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                        >
                            <i data-lucide="filter" class="size-4"></i>
                            Apply
                        </button>

                        <a
                            href="{{ route('admin.skill-gap') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            <i data-lucide="rotate-ccw" class="size-4"></i>
                            Reset
                        </a>

                    </div>

                </form>

            </div>
        </div>

        <div class="hidden md:block">
            <div class="table-scroll">
                <table class="w-full min-w-[1450px]">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-900/50">
                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                Service
                            </th>

                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                Completed Demand
                            </th>

                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                Assigned Staff
                            </th>

                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                Scheduled Staff
                            </th>

                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                Duration
                            </th>

                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                Demand Hours
                            </th>

                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                Scheduled Hours
                            </th>

                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                Schedule Gap
                            </th>

                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                Demand / Staff
                            </th>

                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                Gap Status
                            </th>

                            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                Assigned Staff
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700/70">
                        @forelse($analytics as $item)

                            @php
                                $statusClass = match($item['gap_level']) {
                                    'critical' => 'status-critical',
                                    'high' => 'status-high',
                                    'moderate' => 'status-moderate',
                                    'covered' => 'status-covered',
                                    default => 'status-none',
                                };

                                $scheduleStatusClass = match($item['schedule_level']) {
                                    'critical' => 'text-red-600 dark:text-red-400',
                                    'high' => 'text-orange-600 dark:text-orange-400',
                                    'covered' => 'text-emerald-600 dark:text-emerald-400',
                                    default => 'text-gray-500 dark:text-slate-400',
                                };
                            @endphp

                            <tr class="transition hover:bg-gray-50/70 dark:hover:bg-slate-800/50">

                                <td class="px-5 py-4">
                                    <div class="max-w-[220px]">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $item['name'] }}
                                        </p>

                                        <p class="mt-0.5 text-[11px] text-gray-500 dark:text-slate-500">
                                            Service qualification
                                        </p>
                                    </div>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($item['demand']) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <span class="text-sm font-medium text-gray-700 dark:text-slate-300">
                                        {{ number_format($item['assigned_staff']) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <span class="text-sm font-medium text-gray-700 dark:text-slate-300">
                                        {{ number_format($item['scheduled_staff']) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <span class="text-sm text-gray-700 dark:text-slate-300">
                                        {{ $item['duration_minutes'] > 0 ? $item['duration_minutes'] . ' min' : '—' }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($item['demand_hours'], 2) }}h
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <span class="text-sm font-medium text-gray-700 dark:text-slate-300">
                                        {{ number_format($item['scheduled_hours'], 2) }}h
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    @if($item['schedule_gap_hours'] > 0)
                                        <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">
                                            {{ number_format($item['schedule_gap_hours'], 2) }}h
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-slate-600">
                                            —
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <span class="text-sm font-medium text-gray-700 dark:text-slate-300">
                                        {{ $item['demand_per_staff'] !== null ? number_format($item['demand_per_staff'], 2) : '—' }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="status-dot {{ $statusClass }}"></span>

                                            <span class="text-xs font-medium text-gray-700 dark:text-slate-300">
                                                {{ $item['gap_status'] }}
                                            </span>
                                        </div>

                                        <span class="text-[11px] {{ $scheduleStatusClass }}">
                                            {{ $item['schedule_status'] }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-5 py-4">
                                    @if(count($item['staff']))
                                        <div class="flex max-w-[270px] flex-wrap gap-1.5">
                                            @foreach($item['staff'] as $staffName)
                                                @php
                                                    $staffInitials = collect(explode(' ', trim($staffName)))
                                                        ->filter()
                                                        ->take(2)
                                                        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                                        ->implode('');
                                                @endphp

                                                <div class="flex items-center gap-1.5 rounded-full bg-gray-50 px-2 py-1 dark:bg-slate-800">
                                                    <span class="staff-avatar">
                                                        {{ $staffInitials ?: 'S' }}
                                                    </span>

                                                    <span class="max-w-[120px] truncate text-[11px] font-medium text-gray-700 dark:text-slate-300">
                                                        {{ $staffName }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-slate-600">
                                            No staff assigned
                                        </span>
                                    @endif
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="11" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <div class="flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500">
                                            <i data-lucide="search-x" class="size-6"></i>
                                        </div>

                                        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">
                                            No services found
                                        </h3>

                                        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                                            There are no active non-package services available for this report.
                                        </p>
                                    </div>
                                </td>
                            </tr>

                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="md:hidden">
            @forelse($analytics as $item)

                @php
                    $statusClass = match($item['gap_level']) {
                        'critical' => 'status-critical',
                        'high' => 'status-high',
                        'moderate' => 'status-moderate',
                        'covered' => 'status-covered',
                        default => 'status-none',
                    };

                    $scheduleStatusClass = match($item['schedule_level']) {
                        'critical' => 'text-red-600 dark:text-red-400',
                        'high' => 'text-orange-600 dark:text-orange-400',
                        'covered' => 'text-emerald-600 dark:text-emerald-400',
                        default => 'text-gray-500 dark:text-slate-400',
                    };
                @endphp

                <div class="mobile-separator p-4">
                    <div class="flex items-start justify-between gap-3">

                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $item['name'] }}
                            </p>

                            <p class="mt-0.5 text-[11px] text-gray-500 dark:text-slate-500">
                                Service qualification
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5">
                            <span class="status-dot {{ $statusClass }}"></span>

                            <span class="text-[11px] font-medium text-gray-600 dark:text-slate-300">
                                {{ $item['gap_status'] }}
                            </span>
                        </div>

                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3">

                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                Demand
                            </p>

                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ number_format($item['demand']) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                Assigned Staff
                            </p>

                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ number_format($item['assigned_staff']) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                Scheduled Staff
                            </p>

                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ number_format($item['scheduled_staff']) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                Duration
                            </p>

                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $item['duration_minutes'] > 0 ? $item['duration_minutes'] . ' min' : '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                Demand Hours
                            </p>

                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ number_format($item['demand_hours'], 2) }}h
                            </p>
                        </div>

                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                Scheduled Hours
                            </p>

                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ number_format($item['scheduled_hours'], 2) }}h
                            </p>
                        </div>

                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                Schedule Gap
                            </p>

                            <p class="mt-1 text-sm font-semibold {{ $item['schedule_gap_hours'] > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $item['schedule_gap_hours'] > 0 ? number_format($item['schedule_gap_hours'], 2) . 'h' : '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                Demand / Staff
                            </p>

                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $item['demand_per_staff'] !== null ? number_format($item['demand_per_staff'], 2) : '—' }}
                            </p>
                        </div>

                    </div>

                    <div class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar-clock" class="size-4 text-gray-400 dark:text-slate-500"></i>

                                <span class="text-xs font-medium text-gray-600 dark:text-slate-300">
                                    Schedule status
                                </span>
                            </div>

                            <span class="text-xs font-medium {{ $scheduleStatusClass }}">
                                {{ $item['schedule_status'] }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400 dark:text-slate-500">
                                Assigned Staff
                            </p>

                            <span class="text-[10px] text-gray-400 dark:text-slate-500">
                                {{ count($item['staff']) }}
                            </span>
                        </div>

                        @if(count($item['staff']))
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($item['staff'] as $staffName)
                                    @php
                                        $staffInitials = collect(explode(' ', trim($staffName)))
                                            ->filter()
                                            ->take(2)
                                            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                            ->implode('');
                                    @endphp

                                    <div class="flex items-center gap-1.5 rounded-full bg-gray-50 px-2 py-1 dark:bg-slate-800">
                                        <span class="staff-avatar">
                                            {{ $staffInitials ?: 'S' }}
                                        </span>

                                        <span class="max-w-[140px] truncate text-[11px] font-medium text-gray-700 dark:text-slate-300">
                                            {{ $staffName }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 dark:text-slate-600">
                                No staff assigned.
                            </p>
                        @endif
                    </div>

                </div>

            @empty

                <div class="px-5 py-16 text-center">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500">
                        <i data-lucide="search-x" class="size-6"></i>
                    </div>

                    <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">
                        No services found
                    </h3>

                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                        There are no active non-package services available for this report.
                    </p>
                </div>

            @endforelse
        </div>

        <div class="border-t border-gray-200 px-4 py-3 dark:border-slate-700 sm:px-5">
            <div class="flex flex-col gap-3 text-[11px] text-gray-500 dark:text-slate-400 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <div class="flex items-center gap-1.5">
                        <span class="status-dot status-covered"></span>
                        Covered
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="status-dot status-moderate"></span>
                        Limited coverage
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="status-dot status-high"></span>
                        Bottleneck
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="status-dot status-critical"></span>
                        No staff / scheduled staff
                    </div>
                </div>

                <div>
                    {{ number_format($summary['total_completed_demand']) }}
                    completed service demand ·
                    {{ number_format($summary['total_demand_hours'], 2) }}h demand ·
                    {{ number_format($summary['total_scheduled_hours'], 2) }}h scheduled
                </div>

            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) {
            lucide.createIcons();
        }

        const form = document.getElementById('skillGapFilterForm');
        const dateRange = document.getElementById('date_range');
        const dateFrom = document.getElementById('date_from');
        const dateTo = document.getElementById('date_to');
        const quickFilters = document.querySelectorAll('.quick-filter');

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        function setQuickRange(type) {
            const today = new Date();

            let from;
            let to;

            switch (type) {
                case 'today':
                    from = new Date(today);
                    to = new Date(today);
                    break;

                case 'week':
                    from = new Date(today);
                    to = new Date(today);

                    const day = today.getDay();

                    from.setDate(today.getDate() - day);
                    to.setDate(today.getDate() + (6 - day));
                    break;

                case 'month':
                    from = new Date(
                        today.getFullYear(),
                        today.getMonth(),
                        1
                    );

                    to = new Date(
                        today.getFullYear(),
                        today.getMonth() + 1,
                        0
                    );
                    break;

                case 'year':
                    from = new Date(
                        today.getFullYear(),
                        0,
                        1
                    );

                    to = new Date(
                        today.getFullYear(),
                        11,
                        31
                    );
                    break;

                default:
                    return;
            }

            dateFrom.value = formatDate(from);
            dateTo.value = formatDate(to);

            if (dateRange && dateRange._flatpickr) {
                dateRange._flatpickr.setDate(
                    [from, to],
                    true
                );
            }

            quickFilters.forEach(button => {
                button.classList.remove('quick-filter-active');

                if (button.dataset.range === type) {
                    button.classList.add('quick-filter-active');
                }
            });

            if (form) {
                form.submit();
            }
        }

        if (dateRange && dateFrom && dateTo) {
            const fromValue = dateFrom.value;
            const toValue = dateTo.value;

            flatpickr(dateRange, {
                mode: 'range',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'M d, Y',
                defaultDate: [fromValue, toValue],
                allowInput: false,
                onChange: function (selectedDates) {
                    quickFilters.forEach(button => {
                        button.classList.remove('quick-filter-active');
                    });

                    if (selectedDates.length === 1) {
                        dateFrom.value = flatpickr.formatDate(
                            selectedDates[0],
                            'Y-m-d'
                        );

                        dateTo.value = flatpickr.formatDate(
                            selectedDates[0],
                            'Y-m-d'
                        );
                    }

                    if (selectedDates.length === 2) {
                        dateFrom.value = flatpickr.formatDate(
                            selectedDates[0],
                            'Y-m-d'
                        );

                        dateTo.value = flatpickr.formatDate(
                            selectedDates[1],
                            'Y-m-d'
                        );
                    }
                }
            });

            const currentFrom = dateFrom.value;
            const currentTo = dateTo.value;

            const today = new Date();

            const weekStart = new Date(today);
            weekStart.setDate(today.getDate() - today.getDay());

            const weekEnd = new Date(today);
            weekEnd.setDate(today.getDate() + (6 - today.getDay()));

            const monthStart = new Date(
                today.getFullYear(),
                today.getMonth(),
                1
            );

            const monthEnd = new Date(
                today.getFullYear(),
                today.getMonth() + 1,
                0
            );

            const yearStart = new Date(
                today.getFullYear(),
                0,
                1
            );

            const yearEnd = new Date(
                today.getFullYear(),
                11,
                31
            );

            const quickRanges = {
                today: [
                    formatDate(today),
                    formatDate(today)
                ],
                week: [
                    formatDate(weekStart),
                    formatDate(weekEnd)
                ],
                month: [
                    formatDate(monthStart),
                    formatDate(monthEnd)
                ],
                year: [
                    formatDate(yearStart),
                    formatDate(yearEnd)
                ]
            };

            Object.entries(quickRanges).forEach(([type, range]) => {
                if (
                    currentFrom === range[0] &&
                    currentTo === range[1]
                ) {
                    const button = document.querySelector(
                        `.quick-filter[data-range="${type}"]`
                    );

                    if (button) {
                        button.classList.add('quick-filter-active');
                    }
                }
            });
        }

        quickFilters.forEach(button => {
            button.addEventListener('click', function () {
                setQuickRange(this.dataset.range);
            });
        });

        const infoButton = document.getElementById('skillGapInfoBtn');

        if (infoButton) {
            infoButton.addEventListener('click', function () {
                const isDark = document.documentElement.classList.contains('dark');

                Swal.fire({
                    title: 'What Does This Report Show?',
                    html: `
                        <div style="text-align:left;font-size:14px;line-height:1.6;color:${isDark ? '#cbd5e1' : '#4b5563'}">
                            <p style="margin-bottom:12px">
                                This report compares completed service demand with the staff assigned to each service and their scheduled working hours.
                            </p>

                            <div style="display:flex;flex-direction:column;gap:12px">
                                <div>
                                    <strong style="color:${isDark ? '#f8fafc' : '#111827'}">
                                        Completed Demand
                                    </strong>
                                    <p>
                                        Number of completed appointments containing the service within the selected date range.
                                    </p>
                                </div>

                                <div>
                                    <strong style="color:${isDark ? '#f8fafc' : '#111827'}">
                                        Demand Hours
                                    </strong>
                                    <p>
                                        Estimated service time represented by completed demand, based on the service duration.
                                    </p>
                                </div>

                                <div>
                                    <strong style="color:${isDark ? '#f8fafc' : '#111827'}">
                                        Assigned Staff
                                    </strong>
                                    <p>
                                        Active staff currently assigned to provide the service.
                                    </p>
                                </div>

                                <div>
                                    <strong style="color:${isDark ? '#f8fafc' : '#111827'}">
                                        Scheduled Hours
                                    </strong>
                                    <p>
                                        Working hours from the assigned staff's schedules during the selected period. Day-offs, leave, holidays, and custom schedule exceptions are considered.
                                    </p>
                                </div>

                                <div>
                                    <strong style="color:${isDark ? '#f8fafc' : '#111827'}">
                                        Schedule Gap
                                    </strong>
                                    <p>
                                        The difference when estimated demand hours are greater than scheduled hours.
                                    </p>
                                </div>

                                <div>
                                    <strong style="color:${isDark ? '#f8fafc' : '#111827'}">
                                        Gap Status
                                    </strong>
                                    <p>
                                        A service-level indicator based on completed demand and assigned staff. It is intended to identify potential workforce gaps, not to represent actual capacity utilization.
                                    </p>
                                </div>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'Got it',
                    confirmButtonColor: '#0d9488',
                    background: isDark ? '#1e293b' : '#ffffff',
                    color: isDark ? '#f8fafc' : '#374151',
                    width: 620,
                    customClass: {
                        popup: 'rounded-xl',
                        confirmButton: 'rounded-lg px-4 py-2 text-sm font-medium'
                    }
                });
            });
        }
    });
</script>
@endpush