@extends('layouts.master', [
'roleLabel' => Auth::user()->isAdmin() ? 'Admin Panel' : 'Receptionist Panel',
'userRole' => Auth::user()->isAdmin() ? 'Administrator' : 'Receptionist',
'settingsRoute' => Auth::user()->isAdmin()
? 'admin.profile.update'
: 'receptionist.profile.update',
])

@section('title', 'Service Popularity')

@php
$isAdmin = Auth::user()->isAdmin();


$reportRoute = $isAdmin
    ? 'admin.service-popularity'
    : 'receptionist.service-popularity';

$pdfRoute = $isAdmin
    ? 'admin.service-popularity.pdf'
    : 'receptionist.service-popularity.pdf';


@endphp

@section('logo-icon') <i
     data-lucide="sparkles"
     class="h-5 w-5 text-white"
 ></i>
@endsection

{{-- =========================================================
SIDEBAR
========================================================= --}}
@section('sidebar-nav')


<p class="nav-text mb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-600">
    Overview
</p>

<a
    href="{{ route($isAdmin ? 'admin-dashboard' : 'receptionist.dashboard') }}"
    data-label="Dashboard"
    class="nav-item mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-200"
>
    <i
        data-lucide="layout-dashboard"
        class="h-[18px] w-[18px] shrink-0"
    ></i>

    <span class="nav-text whitespace-nowrap">
        Dashboard
    </span>
</a>


@if ($isAdmin)

    <a
        href="{{ route('admin.users.index') }}"
        data-label="Users"
        class="nav-item mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-200"
    >
        <i
            data-lucide="users"
            class="h-[18px] w-[18px] shrink-0"
        ></i>

        <span class="nav-text whitespace-nowrap">
            Users
        </span>
    </a>


    <p class="nav-text mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-600">
        Management
    </p>


    <a
        href="{{ route('admin.services.index') }}"
        data-label="Services"
        class="nav-item mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-200"
    >
        <i
            data-lucide="sparkles"
            class="h-[18px] w-[18px] shrink-0"
        ></i>

        <span class="nav-text whitespace-nowrap">
            Services
        </span>
    </a>


    <a
        href="{{ route('admin.rooms.index') }}"
        data-label="Rooms"
        class="nav-item mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-200"
    >
        <i
            data-lucide="door-open"
            class="h-[18px] w-[18px] shrink-0"
        ></i>

        <span class="nav-text whitespace-nowrap">
            Rooms
        </span>
    </a>


    <a
        href="{{ route('admin.landing.editor') }}"
        data-label="Landing Page"
        class="nav-item mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-200"
    >
        <i
            data-lucide="house"
            class="h-[18px] w-[18px] shrink-0"
        ></i>

        <span class="nav-text whitespace-nowrap">
            Landing Page
        </span>
    </a>

@endif


{{-- Reports --}}
<p class="nav-text mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-600">
    Reports
</p>


@if ($isAdmin)

    <a
        href="{{ route('admin.appointments') }}"
        data-label="Appointment Report"
        class="nav-item mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-200"
    >
        <i
            data-lucide="calendar-clock"
            class="h-[18px] w-[18px] shrink-0"
        ></i>

        <span class="nav-text whitespace-nowrap">
            Appointment Report
        </span>
    </a>


    <a
        href="{{ route('admin.schedules') }}"
        data-label="Therapist Shift"
        class="nav-item mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-200"
    >
        <i
            data-lucide="calendar-days"
            class="h-[18px] w-[18px] shrink-0"
        ></i>

        <span class="nav-text whitespace-nowrap">
            Therapist Shift
        </span>
    </a>


    <a
        href="{{ route('attendance.today') }}"
        data-label="Attendance"
        class="nav-item mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-200"
    >
        <i
            data-lucide="clipboard-check"
            class="h-[18px] w-[18px] shrink-0"
        ></i>

        <span class="nav-text whitespace-nowrap">
            Attendance
        </span>
    </a>


    <a
        href="{{ route('admin.sales') }}"
        data-label="Sales Report"
        class="nav-item mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-200"
    >
        <i
            data-lucide="chart-column"
            class="h-[18px] w-[18px] shrink-0"
        ></i>

        <span class="nav-text whitespace-nowrap">
            Sales Report
        </span>
    </a>


    <a
        href="{{ route('admin.room-tracking') }}"
        data-label="Room Tracking"
        class="nav-item mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-200"
    >
        <i
            data-lucide="door-open"
            class="h-[18px] w-[18px] shrink-0"
        ></i>

        <span class="nav-text whitespace-nowrap">
            Room Tracking
        </span>
    </a>


    <a
        href="{{ route('admin.skill-gap') }}"
        data-label="Skill Gap Analytics"
        class="nav-item mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/50 dark:hover:text-gray-200"
    >
        <i
            data-lucide="chart-no-axes-combined"
            class="h-[18px] w-[18px] shrink-0"
        ></i>

        <span class="nav-text whitespace-nowrap">
            Skill Gap Analytics
        </span>
    </a>

@endif


{{-- Active Report --}}
<a
    href="{{ route($reportRoute) }}"
    data-label="Service Popularity"
    class="nav-item active mb-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-brand-600 dark:text-brand-400"
>
    <i
        data-lucide="sparkles"
        class="h-[18px] w-[18px] shrink-0"
    ></i>

    <span class="nav-text whitespace-nowrap">
        Service Popularity
    </span>
</a>


@endsection

{{-- =========================================================
MOBILE SIDEBAR
========================================================= --}}
@section('sidebar-nav-mobile')


<p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-600">
    Reports
</p>


@if ($isAdmin)

    <a
        href="{{ route('admin.appointments') }}"
        class="mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold text-gray-600 dark:text-gray-400"
    >
        <i
            data-lucide="calendar-clock"
            class="h-[18px] w-[18px]"
        ></i>

        Appointment Report
    </a>


    <a
        href="{{ route('admin.schedules') }}"
        class="mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold text-gray-600 dark:text-gray-400"
    >
        <i
            data-lucide="calendar-days"
            class="h-[18px] w-[18px]"
        ></i>

        Therapist Shift
    </a>


    <a
        href="{{ route('attendance.today') }}"
        class="mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold text-gray-600 dark:text-gray-400"
    >
        <i
            data-lucide="clipboard-check"
            class="h-[18px] w-[18px]"
        ></i>

        Attendance
    </a>


    <a
        href="{{ route('admin.sales') }}"
        class="mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold text-gray-600 dark:text-gray-400"
    >
        <i
            data-lucide="chart-column"
            class="h-[18px] w-[18px]"
        ></i>

        Sales Report
    </a>


    <a
        href="{{ route('admin.room-tracking') }}"
        class="mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold text-gray-600 dark:text-gray-400"
    >
        <i
            data-lucide="door-open"
            class="h-[18px] w-[18px]"
        ></i>

        Room Tracking
    </a>


    <a
        href="{{ route('admin.skill-gap') }}"
        class="mb-1 flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold text-gray-600 dark:text-gray-400"
    >
        <i
            data-lucide="chart-no-axes-combined"
            class="h-[18px] w-[18px]"
        ></i>

        Skill Gap Analytics
    </a>

@endif


<a
    href="{{ route($reportRoute) }}"
    class="mb-1 flex items-center gap-3 rounded-xl bg-brand-50 px-3 py-3 text-[13px] font-semibold text-brand-600 dark:bg-brand-900/20 dark:text-brand-400"
>
    <i
        data-lucide="sparkles"
        class="h-[18px] w-[18px]"
    ></i>

    Service Popularity
</a>


@endsection

{{-- =========================================================
STYLES
========================================================= --}}
@push('styles')


<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"
>

<style>
    .flatpickr-calendar {
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow:
            0 20px 25px -5px rgb(0 0 0 / 0.10),
            0 8px 10px -6px rgb(0 0 0 / 0.10);
    }

    .dark .flatpickr-calendar {
        background: #1f2937;
        border-color: #374151;
    }

    .dark .flatpickr-months,
    .dark .flatpickr-weekdays {
        background: #1f2937;
    }

    .dark .flatpickr-day,
    .dark .flatpickr-weekday,
    .dark .flatpickr-current-month {
        color: #f9fafb;
    }

    .dark .flatpickr-day:hover {
        background: #374151;
        border-color: #374151;
    }

    .flatpickr-input {
        cursor: pointer;
    }
</style>


@endpush

{{-- =========================================================
CONTENT
========================================================= --}}
@section('content')

<div class="space-y-6">


{{-- =====================================================
     BREADCRUMB + PDF ACTIONS
====================================================== --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

    <div>

        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">

            <a
                href="{{ route($isAdmin ? 'admin-dashboard' : 'receptionist.dashboard') }}"
                class="transition hover:text-brand-600 dark:hover:text-brand-400"
            >
                Dashboard
            </a>

            <i
                data-lucide="chevron-right"
                class="h-3.5 w-3.5"
            ></i>

            <span>
                Reports
            </span>

            <i
                data-lucide="chevron-right"
                class="h-3.5 w-3.5"
            ></i>

            <span class="font-medium text-gray-700 dark:text-gray-200">
                Service Popularity
            </span>

        </div>


        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            View completed services and package activity by period and category.
        </p>

    </div>


    <div class="flex flex-wrap items-center gap-2">

        {{-- Preview PDF --}}
        <a
            href="{{ route($pdfRoute, array_merge(request()->query(), ['action' => 'stream'])) }}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-[#1e293b] dark:text-gray-200 dark:hover:bg-gray-800"
        >
            <i
                data-lucide="eye"
                class="h-4 w-4"
            ></i>

            Preview PDF
        </a>


        {{-- Download PDF --}}
        <a
            href="{{ route($pdfRoute, array_merge(request()->query(), ['action' => 'download'])) }}"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700"
        >
            <i
                data-lucide="download"
                class="h-4 w-4"
            ></i>

            Download PDF
        </a>

    </div>

</div>


{{-- =====================================================
     FILTERS
====================================================== --}}
<div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-[#1e293b]">

    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">

        <div class="flex items-center gap-3">

            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-400">

                <i
                    data-lucide="filter"
                    class="h-4 w-4"
                ></i>

            </div>


            <div>

                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                    Report Filters
                </h2>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Changes are applied automatically.
                </p>

            </div>

        </div>

    </div>


    <form
        id="servicePopularityFilterForm"
        method="GET"
        action="{{ route($reportRoute) }}"
        class="p-5"
    >

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">


            {{-- REPORTING PERIOD --}}
            <div class="lg:col-span-5">

                <label
                    for="range"
                    class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300"
                >
                    Reporting Period
                </label>


                <div class="relative">

                    <i
                        data-lucide="calendar-range"
                        class="pointer-events-none absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-gray-400"
                    ></i>


                    <select
                        id="range"
                        name="range"
                        class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm font-medium text-gray-700 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-[#0f172a] dark:text-gray-200"
                    >

                        <option
                            value="today"
                            @selected($range === 'today')
                        >
                            Today
                        </option>


                        <option
                            value="week"
                            @selected($range === 'week')
                        >
                            This Week
                        </option>


                        <option
                            value="month"
                            @selected($range === 'month')
                        >
                            This Month
                        </option>


                        <option
                            value="year"
                            @selected($range === 'year')
                        >
                            This Year
                        </option>


                        <option
                            value="custom"
                            @selected($range === 'custom')
                        >
                            Custom Range
                        </option>

                    </select>

                </div>

            </div>


            {{-- SERVICE CATEGORY --}}
            <div class="lg:col-span-5">

                <label
                    for="category_id"
                    class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300"
                >
                    Service Category
                </label>


                <div class="relative">

                    <i
                        data-lucide="layers-3"
                        class="pointer-events-none absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-gray-400"
                    ></i>


                    <select
                        id="category_id"
                        name="category_id"
                        class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm font-medium text-gray-700 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-[#0f172a] dark:text-gray-200"
                    >

                        <option value="">
                            All Categories
                        </option>


                        @foreach ($categories as $category)

                            <option
                                value="{{ $category->id }}"
                                @selected((string) $categoryId === (string) $category->id)
                            >
                                {{ $category->name }}
                            </option>

                        @endforeach

                    </select>

                </div>

            </div>


            {{-- RESET --}}
            <div class="flex items-end lg:col-span-2">

                <a
                    href="{{ route($reportRoute) }}"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-[#0f172a] dark:text-gray-300 dark:hover:bg-gray-800"
                >

                    <i
                        data-lucide="rotate-ccw"
                        class="h-4 w-4"
                    ></i>

                    Reset

                </a>

            </div>


            {{-- CUSTOM RANGE --}}
            <div
                id="customDateFields"
                class="{{ $range === 'custom' ? '' : 'hidden' }} border-t border-gray-100 pt-4 dark:border-gray-700 lg:col-span-12"
            >

                <div class="max-w-xl">

                    <label
                        for="custom_date_range"
                        class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300"
                    >
                        Custom Date Range
                    </label>


                    <div class="relative">

                        <i
                            data-lucide="calendar-days"
                            class="pointer-events-none absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-gray-400"
                        ></i>


                        <input
                            id="custom_date_range"
                            type="text"
                            placeholder="Select date range"
                            autocomplete="off"
                            readonly
                            class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm font-medium text-gray-700 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-[#0f172a] dark:text-gray-200"
                        >

                    </div>


                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        Select the start and end date from the calendar.
                    </p>

                </div>

            </div>

        </div>

    </form>

</div>


{{-- =====================================================
     CURRENT REPORT
====================================================== --}}
<div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

    <div>

        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
            Current Report
        </p>


        <div class="mt-1 flex items-center gap-2">

            <i
                data-lucide="calendar-check-2"
                class="h-4 w-4 text-brand-500"
            ></i>


            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                {{ $dateDisplay }}
            </h2>

        </div>


        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">

            Category:

            <span class="font-semibold text-gray-700 dark:text-gray-300">
                {{ $selectedCategoryName ?: 'All Categories' }}
            </span>

        </p>

    </div>

</div>


{{-- =====================================================
     SUMMARY CARDS
====================================================== --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">


    {{-- COMPLETED APPOINTMENTS --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-[#1e293b]">

        <div class="flex items-start justify-between">

            <div>

                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Completed Appointments
                </p>

                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($summary['completed_appointments']) }}
                </p>

            </div>


            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">

                <i
                    data-lucide="calendar-check-2"
                    class="h-5 w-5"
                ></i>

            </div>

        </div>

    </div>


    {{-- SERVICES COMPLETED --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-[#1e293b]">

        <div class="flex items-start justify-between">

            <div>

                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Services Completed
                </p>

                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($summary['services_completed']) }}
                </p>

            </div>


            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-400">

                <i
                    data-lucide="sparkles"
                    class="h-5 w-5"
                ></i>

            </div>

        </div>

    </div>


    {{-- PACKAGES BOOKED --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-[#1e293b]">

        <div class="flex items-start justify-between">

            <div>

                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Packages Booked
                </p>

                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($summary['packages_booked']) }}
                </p>

            </div>


            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400">

                <i
                    data-lucide="package"
                    class="h-5 w-5"
                ></i>

            </div>

        </div>

    </div>


    {{-- SERVICE REVENUE --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-[#1e293b]">

        <div class="flex items-start justify-between">

            <div>

                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Service Revenue
                </p>

                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    ₱{{ number_format($summary['service_revenue'], 2) }}
                </p>

            </div>


            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">

                <i
                    data-lucide="banknote"
                    class="h-5 w-5"
                ></i>

            </div>

        </div>

    </div>

</div>


{{-- =====================================================
     INDIVIDUAL SERVICE POPULARITY
====================================================== --}}
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-[#1e293b]">

    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">

        <div>

            <h2 class="text-base font-bold text-gray-900 dark:text-white">
                Individual Service Popularity
            </h2>

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Completed individual services, including services represented by completed packages.
            </p>

        </div>


        <div class="hidden items-center gap-2 text-xs font-medium text-gray-500 sm:flex dark:text-gray-400">

            <i
                data-lucide="list-checks"
                class="h-4 w-4"
            ></i>

            {{ count($serviceBreakdown) }} services

        </div>

    </div>


    @if (count($serviceBreakdown))

        <div class="overflow-x-auto">

            <table class="w-full min-w-[760px] text-left">

                <thead class="bg-gray-50 dark:bg-gray-900/40">

                    <tr class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">

                        <th class="px-5 py-3">
                            #
                        </th>

                        <th class="px-5 py-3">
                            Service
                        </th>

                        <th class="px-5 py-3">
                            Category
                        </th>

                        <th class="px-5 py-3 text-center">
                            Completed
                        </th>

                        <th class="px-5 py-3 text-right">
                            Share
                        </th>

                        <th class="px-5 py-3 text-right">
                            Revenue
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                    @foreach ($serviceBreakdown as $index => $service)

                        <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30">

                            <td class="px-5 py-4 text-sm text-gray-400">
                                {{ $index + 1 }}
                            </td>


                            <td class="px-5 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-400">

                                        <i
                                            data-lucide="sparkles"
                                            class="h-4 w-4"
                                        ></i>

                                    </div>


                                    <div>

                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $service['name'] }}
                                        </p>


                                        @if (!empty($service['code']))

                                            <p class="text-[11px] text-gray-400">
                                                {{ $service['code'] }}
                                            </p>

                                        @endif

                                    </div>

                                </div>

                            </td>


                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $service['category'] }}
                            </td>


                            <td class="px-5 py-4 text-center">

                                <span class="inline-flex min-w-9 justify-center rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700 dark:bg-brand-900/20 dark:text-brand-300">
                                    {{ $service['completed_count'] }}
                                </span>

                            </td>


                            <td class="px-5 py-4 text-right">

                                <div class="flex items-center justify-end gap-2">

                                    <div class="h-1.5 w-16 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">

                                        <div
                                            class="h-full rounded-full bg-brand-500"
                                            style="width: {{ min(100, max(0, $service['share'])) }}%;"
                                        ></div>

                                    </div>


                                    <span class="w-14 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        {{ number_format($service['share'], 2) }}%
                                    </span>

                                </div>

                            </td>


                            <td class="px-5 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                ₱{{ number_format($service['revenue'], 2) }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @else

        <div class="px-6 py-16 text-center">

            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500">

                <i
                    data-lucide="inbox"
                    class="h-5 w-5"
                ></i>

            </div>


            <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
                No completed services found
            </h3>


            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                No completed services match the selected filters.
            </p>

        </div>

    @endif

</div>


{{-- =====================================================
     PACKAGE BREAKDOWN
====================================================== --}}
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-[#1e293b]">

    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">

        <div class="flex items-center gap-3">

            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400">

                <i
                    data-lucide="package"
                    class="h-4 w-4"
                ></i>

            </div>


            <div>

                <h2 class="text-base font-bold text-gray-900 dark:text-white">
                    Package Breakdown
                </h2>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Completed packages are shown separately.
                </p>

            </div>

        </div>

    </div>


    @if (count($packageBreakdown))

        <div class="overflow-x-auto">

            <table class="w-full min-w-[800px] text-left">

                <thead class="bg-gray-50 dark:bg-gray-900/40">

                    <tr class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">

                        <th class="px-5 py-3">
                            Package
                        </th>

                        <th class="px-5 py-3">
                            Category
                        </th>

                        <th class="px-5 py-3">
                            Included Services
                        </th>

                        <th class="px-5 py-3 text-center">
                            Completed
                        </th>

                        <th class="px-5 py-3 text-right">
                            Revenue
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                    @foreach ($packageBreakdown as $package)

                        <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30">

                            <td class="px-5 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400">

                                        <i
                                            data-lucide="package"
                                            class="h-4 w-4"
                                        ></i>

                                    </div>


                                    <div>

                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $package['name'] }}
                                        </p>


                                        @if (!empty($package['code']))

                                            <p class="text-[11px] text-gray-400">
                                                {{ $package['code'] }}
                                            </p>

                                        @endif

                                    </div>

                                </div>

                            </td>


                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $package['category'] }}
                            </td>


                            <td class="px-5 py-4">

                                <div class="flex flex-wrap gap-1.5">

                                    @forelse ($package['included_services'] as $included)

                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $included['name'] }}
                                        </span>

                                    @empty

                                        <span class="text-xs text-gray-400">
                                            None listed
                                        </span>

                                    @endforelse

                                </div>

                            </td>


                            <td class="px-5 py-4 text-center">

                                <span class="inline-flex min-w-9 justify-center rounded-full bg-purple-50 px-2.5 py-1 text-xs font-bold text-purple-700 dark:bg-purple-900/20 dark:text-purple-300">
                                    {{ $package['completed_count'] }}
                                </span>

                            </td>


                            <td class="px-5 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                ₱{{ number_format($package['revenue'], 2) }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @else

        <div class="px-6 py-16 text-center">

            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500">

                <i
                    data-lucide="package-open"
                    class="h-5 w-5"
                ></i>

            </div>


            <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
                No completed packages found
            </h3>


            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                No package appointments match the selected filters.
            </p>

        </div>

    @endif

</div>


{{-- =====================================================
     CATEGORY BREAKDOWN
====================================================== --}}
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-[#1e293b]">

    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">

        <div class="flex items-center gap-3">

            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">

                <i
                    data-lucide="layers-3"
                    class="h-4 w-4"
                ></i>

            </div>


            <div>

                <h2 class="text-base font-bold text-gray-900 dark:text-white">
                    Category Breakdown
                </h2>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Completed individual services grouped by category.
                </p>

            </div>

        </div>

    </div>


    @if (count($categoryBreakdown))

        <div class="overflow-x-auto">

            <table class="w-full min-w-[650px] text-left">

                <thead class="bg-gray-50 dark:bg-gray-900/40">

                    <tr class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">

                        <th class="px-5 py-3">
                            Category
                        </th>

                        <th class="px-5 py-3 text-center">
                            Services Completed
                        </th>

                        <th class="px-5 py-3 text-right">
                            Revenue
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                    @foreach ($categoryBreakdown as $category)

                        <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30">

                            <td class="px-5 py-4">

                                <div class="flex items-center gap-3">

                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">

                                        <i
                                            data-lucide="layers-3"
                                            class="h-4 w-4"
                                        ></i>

                                    </div>


                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $category['name'] }}
                                    </span>

                                </div>

                            </td>


                            <td class="px-5 py-4 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                {{ number_format($category['completed_count']) }}
                            </td>


                            <td class="px-5 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                ₱{{ number_format($category['revenue'], 2) }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @else

        <div class="px-6 py-16 text-center">

            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500">

                <i
                    data-lucide="layers-3"
                    class="h-5 w-5"
                ></i>

            </div>


            <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
                No category data
            </h3>


            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                No category activity is available for this period.
            </p>

        </div>

    @endif

</div>


</div>

@endsection

{{-- =========================================================
SCRIPTS
========================================================= --}}
@push('scripts')


<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        if (window.lucide) {
            lucide.createIcons();
        }


        const form =
            document.getElementById(
                'servicePopularityFilterForm'
            );

        const range =
            document.getElementById('range');

        const category =
            document.getElementById('category_id');

        const customDateFields =
            document.getElementById(
                'customDateFields'
            );

        const customDateRange =
            document.getElementById(
                'custom_date_range'
            );


        let startDate = @json(
            $range === 'custom' && $startDate
                ? $startDate->format('Y-m-d')
                : ''
        );


        let endDate = @json(
            $range === 'custom' && $endDate
                ? $endDate->format('Y-m-d')
                : ''
        );


        function toggleCustomDates() {

            if (!customDateFields || !range) {
                return;
            }


            if (range.value === 'custom') {

                customDateFields.classList.remove(
                    'hidden'
                );

            } else {

                customDateFields.classList.add(
                    'hidden'
                );
            }
        }


        function submitFilters() {

            if (!form || !range) {
                return;
            }


            const params =
                new URLSearchParams();


            params.set(
                'range',
                range.value
            );


            if (
                category &&
                category.value
            ) {

                params.set(
                    'category_id',
                    category.value
                );
            }


            if (range.value === 'custom') {

                if (
                    !startDate ||
                    !endDate
                ) {
                    return;
                }


                params.set(
                    'start_date',
                    startDate
                );


                params.set(
                    'end_date',
                    endDate
                );
            }


            window.location.href =
                form.action +
                '?' +
                params.toString();
        }


        if (range) {

            range.addEventListener(
                'change',
                function () {

                    toggleCustomDates();


                    if (this.value === 'custom') {

                        if (
                            customDateRange &&
                            customDateRange._flatpickr
                        ) {

                            customDateRange._flatpickr.clear();

                            customDateRange._flatpickr.open();
                        }

                        return;
                    }


                    startDate = '';
                    endDate = '';

                    submitFilters();
                }
            );
        }


        if (category) {

            category.addEventListener(
                'change',
                function () {
                    submitFilters();
                }
            );
        }


        if (
            customDateRange &&
            typeof flatpickr !== 'undefined'
        ) {

            let defaultDates = [];


            if (
                startDate &&
                endDate
            ) {

                defaultDates = [
                    startDate,
                    endDate
                ];
            }


            flatpickr(
                customDateRange,
                {

                    mode: 'range',

                    dateFormat: 'Y-m-d',

                    altInput: true,

                    altFormat: 'F j, Y',

                    allowInput: false,

                    clickOpens: true,

                    defaultDate: defaultDates,


                    onChange: function (
                        selectedDates
                    ) {

                        if (
                            selectedDates.length === 0
                        ) {

                            startDate = '';
                            endDate = '';

                            return;
                        }


                        if (
                            selectedDates.length === 1
                        ) {

                            startDate =
                                flatpickr.formatDate(
                                    selectedDates[0],
                                    'Y-m-d'
                                );

                            endDate = '';

                            return;
                        }


                        if (
                            selectedDates.length === 2
                        ) {

                            startDate =
                                flatpickr.formatDate(
                                    selectedDates[0],
                                    'Y-m-d'
                                );


                            endDate =
                                flatpickr.formatDate(
                                    selectedDates[1],
                                    'Y-m-d'
                                );


                            submitFilters();
                        }
                    }
                }
            );
        }


        toggleCustomDates();

    });
</script>


@endpush
