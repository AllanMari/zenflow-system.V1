@extends('layouts.admin')

@section('title', 'Appointments')

@push('styles')
{{-- Flatpickr --}} <link rel="stylesheet"
       href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

{{-- Tom Select --}}
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.css">

<style>
    .flatpickr-calendar {
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.2);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
    }

    .ts-wrapper {
        min-width: 180px;
    }

    .ts-control {
        min-height: 42px !important;
        border-radius: 0.75rem !important;
        border: 1px solid rgb(226 232 240) !important;
        background: rgb(248 250 252) !important;
        padding: 0.55rem 0.75rem !important;
        box-shadow: none !important;
    }

    .dark .ts-control {
        background: rgb(30 41 59) !important;
        border-color: rgb(71 85 105) !important;
        color: white !important;
    }

    .dark .ts-dropdown {
        background: rgb(30 41 59);
        border-color: rgb(71 85 105);
        color: white;
    }

    .dark .ts-dropdown .option {
        color: white;
    }

    .dark .ts-dropdown .active {
        background: rgba(99, 102, 241, 0.2);
    }

    .dark .ts-control input {
        color: white !important;
    }

    .appointment-row {
        transition: background-color 0.15s ease,
                    box-shadow 0.15s ease;
    }

    .appointment-row:hover {
        box-shadow: inset 3px 0 0 rgb(99 102 241);
    }
</style>
@endpush

@section('content')

<div class="max-w-[1600px] mx-auto space-y-5">

{{-- =========================================================
     PAGE INTRO
========================================================== --}}
<div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">

    <div>
        <div class="flex items-center gap-2 text-xs font-semibold text-gray-400 dark:text-gray-500 mb-1">

            <span>Operations</span>

            <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                      d="M7.21 14.77a.75.75 0 01.02-1.06L10.94 10 7.23 6.29a.75.75 0 111.06-1.06l4.24 4.24a.75.75 0 010 1.06l-4.24 4.24a.75.75 0 01-1.08 0z"
                      clip-rule="evenodd"/>
            </svg>

            <span>Appointments</span>

        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            Manage bookings, customer schedules, staff assignments, and appointment status.
        </p>
    </div>

    <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">

        <svg class="w-4 h-4"
             viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="1.8">

            <rect x="3" y="4" width="18" height="18" rx="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>

        </svg>

        <span>{{ now()->format('l, F j, Y') }}</span>

    </div>

</div>


{{-- =========================================================
     SEARCH & FILTERS
========================================================== --}}
<div class="bg-white dark:bg-[#1e293b]
            border border-gray-100 dark:border-gray-700/60
            rounded-2xl shadow-sm overflow-visible">

    <form id="appointmentFilterForm"
          method="GET"
          action="{{ route('admin.appointments') }}">

        <div class="p-4 sm:p-5">

            {{-- Search --}}
            <div class="relative">

                <svg class="absolute left-4 top-1/2 -translate-y-1/2
                            w-4 h-4 text-gray-400 pointer-events-none"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2">

                    <circle cx="11" cy="11" r="7"/>
                    <line x1="20" y1="20" x2="16.5" y2="16.5"/>

                </svg>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search customer name or phone number..."
                    class="w-full h-11 pl-11 pr-4
                           rounded-xl
                           border border-gray-200 dark:border-gray-700
                           bg-gray-50 dark:bg-gray-800/60
                           text-sm text-gray-800 dark:text-white
                           placeholder-gray-400
                           focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20
                           outline-none transition"
                >

            </div>


            {{-- Filter Controls --}}
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">

                {{-- Status --}}
                <div>

                    <label class="block mb-1.5
                                  text-[11px] font-bold uppercase
                                  tracking-wider
                                  text-gray-400 dark:text-gray-500">
                        Status
                    </label>

                    <select
                        id="statusFilter"
                        name="status"
                        class="w-full h-11 px-3 rounded-xl
                               border border-gray-200 dark:border-gray-700
                               bg-gray-50 dark:bg-gray-800/60
                               text-sm text-gray-800 dark:text-white
                               focus:border-brand-500
                               focus:ring-2 focus:ring-brand-500/20
                               outline-none">

                        <option value="">All statuses</option>

                        <option value="pending"
                            {{ request('status') === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>

                        <option value="confirmed"
                            {{ request('status') === 'confirmed' ? 'selected' : '' }}>
                            Confirmed
                        </option>

                        <option value="completed"
                            {{ request('status') === 'completed' ? 'selected' : '' }}>
                            Completed
                        </option>

                        <option value="cancelled"
                            {{ request('status') === 'cancelled' ? 'selected' : '' }}>
                            Cancelled
                        </option>

                        <option value="no_show"
                            {{ request('status') === 'no_show' ? 'selected' : '' }}>
                            No-show
                        </option>

                    </select>

                </div>


                {{-- Staff --}}
                <div>

                    <label class="block mb-1.5
                                  text-[11px] font-bold uppercase
                                  tracking-wider
                                  text-gray-400 dark:text-gray-500">
                        Staff
                    </label>

                    <select
                        id="staffFilter"
                        name="staff_id">

                        <option value="">All staff</option>

                        @foreach($staffList as $staff)

                            <option
                                value="{{ $staff->id }}"
                                {{ (string) request('staff_id') === (string) $staff->id ? 'selected' : '' }}>

                                {{ $staff->first_name }} {{ $staff->last_name }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- Date Range --}}
                <div class="lg:col-span-2">

                    <label class="block mb-1.5
                                  text-[11px] font-bold uppercase
                                  tracking-wider
                                  text-gray-400 dark:text-gray-500">
                        Date range
                    </label>

                    <div class="relative">

                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2
                                    w-4 h-4 text-gray-400
                                    pointer-events-none z-10"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="1.8">

                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>

                        </svg>

                        <input
                            id="dateRangePicker"
                            type="text"
                            placeholder="Select date range"
                            readonly
                            class="w-full h-11 pl-10 pr-4
                                   rounded-xl
                                   border border-gray-200 dark:border-gray-700
                                   bg-gray-50 dark:bg-gray-800/60
                                   text-sm text-gray-800 dark:text-white
                                   cursor-pointer
                                   focus:border-brand-500
                                   focus:ring-2 focus:ring-brand-500/20
                                   outline-none"
                        >

                        <input
                            type="hidden"
                            id="dateFrom"
                            name="date_from"
                            value="{{ request('date_from') }}"
                        >

                        <input
                            type="hidden"
                            id="dateTo"
                            name="date_to"
                            value="{{ request('date_to') }}"
                        >

                    </div>

                </div>


                {{-- Filter Buttons --}}
                <div class="flex items-end gap-2">

                    <button
                        type="submit"
                        class="flex-1 h-11
                               inline-flex items-center justify-center gap-2
                               rounded-xl
                               bg-brand-600 hover:bg-brand-700
                               text-white text-sm font-bold
                               shadow-sm hover:shadow
                               transition">

                        <svg class="w-4 h-4"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">

                            <circle cx="11" cy="11" r="7"/>
                            <line x1="20" y1="20" x2="16.5" y2="16.5"/>

                        </svg>

                        Apply

                    </button>


                    <a
                        href="{{ route('admin.appointments') }}"
                        class="h-11 px-4
                               inline-flex items-center justify-center
                               rounded-xl
                               bg-gray-100 hover:bg-gray-200
                               dark:bg-gray-800 dark:hover:bg-gray-700
                               text-gray-600 dark:text-gray-300
                               text-sm font-bold
                               transition">

                        Clear

                    </a>

                </div>

            </div>


            {{-- Quick Filters --}}
            <div class="mt-4 pt-4
                        border-t border-gray-100 dark:border-gray-700/60">

                <div class="flex flex-wrap items-center gap-2">

                    <span class="text-[11px] font-bold uppercase
                                 tracking-wider
                                 text-gray-400 dark:text-gray-500 mr-1">
                        Quick filter
                    </span>


                    <button
                        type="button"
                        data-status=""
                        class="quick-status px-3 py-1.5
                               rounded-lg text-xs font-semibold
                               border border-gray-200 dark:border-gray-700
                               text-gray-600 dark:text-gray-300
                               hover:border-brand-300
                               hover:text-brand-600
                               transition">
                        All
                    </button>


                    <button
                        type="button"
                        data-status="pending"
                        class="quick-status px-3 py-1.5
                               rounded-lg text-xs font-semibold
                               border border-amber-200 dark:border-amber-800
                               text-amber-700 dark:text-amber-300
                               hover:bg-amber-50
                               dark:hover:bg-amber-900/20
                               transition">
                        Pending
                    </button>


                    <button
                        type="button"
                        data-status="confirmed"
                        class="quick-status px-3 py-1.5
                               rounded-lg text-xs font-semibold
                               border border-blue-200 dark:border-blue-800
                               text-blue-700 dark:text-blue-300
                               hover:bg-blue-50
                               dark:hover:bg-blue-900/20
                               transition">
                        Confirmed
                    </button>


                    <button
                        type="button"
                        data-status="completed"
                        class="quick-status px-3 py-1.5
                               rounded-lg text-xs font-semibold
                               border border-emerald-200 dark:border-emerald-800
                               text-emerald-700 dark:text-emerald-300
                               hover:bg-emerald-50
                               dark:hover:bg-emerald-900/20
                               transition">
                        Completed
                    </button>


                    <button
                        type="button"
                        data-status="no_show"
                        class="quick-status px-3 py-1.5
                               rounded-lg text-xs font-semibold
                               border border-rose-200 dark:border-rose-800
                               text-rose-700 dark:text-rose-300
                               hover:bg-rose-50
                               dark:hover:bg-rose-900/20
                               transition">
                        No-shows
                    </button>

                </div>

            </div>

        </div>


        {{-- Active Filters --}}
        @php

            $selectedStaff = null;

            if (request('staff_id')) {
                $selectedStaff = collect($staffList)
                    ->firstWhere('id', request('staff_id'));
            }

            $hasFilters =
                request()->filled('status')
                || request()->filled('date_from')
                || request()->filled('date_to')
                || request()->filled('staff_id')
                || request()->filled('search');

        @endphp


        @if($hasFilters)

            <div class="px-4 sm:px-5 py-3
                        border-t border-gray-100 dark:border-gray-700/60
                        bg-gray-50/70 dark:bg-gray-800/30">

                <div class="flex flex-wrap items-center gap-2">

                    <span class="text-[11px] font-bold uppercase
                                 tracking-wider
                                 text-gray-400 dark:text-gray-500">
                        Active filters
                    </span>


                    @if(request('search'))

                        <span class="inline-flex items-center gap-1.5
                                     px-2.5 py-1 rounded-lg
                                     bg-white dark:bg-gray-800
                                     border border-gray-200 dark:border-gray-700
                                     text-xs font-semibold
                                     text-gray-600 dark:text-gray-300">

                            Search: "{{ request('search') }}"

                        </span>

                    @endif


                    @if(request('status'))

                        <span class="inline-flex items-center gap-1.5
                                     px-2.5 py-1 rounded-lg
                                     bg-white dark:bg-gray-800
                                     border border-gray-200 dark:border-gray-700
                                     text-xs font-semibold
                                     text-gray-600 dark:text-gray-300">

                            Status:
                            {{ str_replace('_', ' ', ucfirst(request('status'))) }}

                        </span>

                    @endif


                    @if(request('staff_id') && $selectedStaff)

                        <span class="inline-flex items-center gap-1.5
                                     px-2.5 py-1 rounded-lg
                                     bg-white dark:bg-gray-800
                                     border border-gray-200 dark:border-gray-700
                                     text-xs font-semibold
                                     text-gray-600 dark:text-gray-300">

                            Staff:
                            {{ $selectedStaff->first_name }}
                            {{ $selectedStaff->last_name }}

                        </span>

                    @endif


                    @if(request('date_from') || request('date_to'))

                        <span class="inline-flex items-center gap-1.5
                                     px-2.5 py-1 rounded-lg
                                     bg-white dark:bg-gray-800
                                     border border-gray-200 dark:border-gray-700
                                     text-xs font-semibold
                                     text-gray-600 dark:text-gray-300">

                            Dates:
                            {{ request('date_from') ?: 'Any' }}
                            →
                            {{ request('date_to') ?: 'Any' }}

                        </span>

                    @endif

                </div>

            </div>

        @endif

    </form>

</div>


{{-- =========================================================
     APPOINTMENT SCHEDULE
========================================================== --}}
<div class="bg-white dark:bg-[#1e293b]
            border border-gray-100 dark:border-gray-700/60
            rounded-2xl shadow-sm overflow-hidden">


    {{-- Table Heading --}}
    <div class="px-4 sm:px-5 py-4
                border-b border-gray-100 dark:border-gray-700/60">

        <div class="flex flex-col sm:flex-row
                    sm:items-center sm:justify-between gap-2">

            <div>

                <h2 class="text-sm font-black
                           text-gray-900 dark:text-white">
                    Appointment Schedule
                </h2>

                <p class="text-xs text-gray-400
                          dark:text-gray-500 mt-0.5">
                    Booking details, service assignments, and payment status
                </p>

            </div>


            <div class="text-xs font-semibold
                        text-gray-400 dark:text-gray-500">

                {{ method_exists($appointments, 'total')
                    ? number_format($appointments->total())
                    : number_format($appointments->count()) }}

                {{ (method_exists($appointments, 'total')
                    ? $appointments->total()
                    : $appointments->count()) == 1
                    ? 'appointment'
                    : 'appointments' }}

            </div>

        </div>

    </div>


    {{-- Appointment Table --}}
    <div class="overflow-x-auto">

        <table class="w-full min-w-[1100px] text-left">

            <thead>

                <tr class="bg-gray-50/80 dark:bg-gray-800/50
                           border-b border-gray-100 dark:border-gray-700/60">

                    <th class="px-5 py-3
                               text-[10px] font-black uppercase
                               tracking-wider
                               text-gray-400 dark:text-gray-500">
                        Appointment
                    </th>

                    <th class="px-5 py-3
                               text-[10px] font-black uppercase
                               tracking-wider
                               text-gray-400 dark:text-gray-500">
                        Customer
                    </th>

                    <th class="px-5 py-3
                               text-[10px] font-black uppercase
                               tracking-wider
                               text-gray-400 dark:text-gray-500">
                        Services
                    </th>

                    <th class="px-5 py-3
                               text-[10px] font-black uppercase
                               tracking-wider
                               text-gray-400 dark:text-gray-500">
                        Staff / Room
                    </th>

                    <th class="px-5 py-3
                               text-[10px] font-black uppercase
                               tracking-wider
                               text-gray-400 dark:text-gray-500">
                        Payment
                    </th>

                    <th class="px-5 py-3
                               text-[10px] font-black uppercase
                               tracking-wider
                               text-gray-400 dark:text-gray-500
                               text-right">
                        Status
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y divide-gray-100
                         dark:divide-gray-700/60">


                @forelse($appointments as $appt)

                    @php

                        $appointmentDate =
                            \Carbon\Carbon::parse($appt->appointment_date);

                        $startTime =
                            \Carbon\Carbon::parse($appt->start_time);

                        $endTime =
                            \Carbon\Carbon::parse($appt->end_time);

                        $duration =
                            $startTime->diffInMinutes($endTime);


                        /*
                         * Customer initials
                         */
                        $customerName =
                            $appt->customer->full_name ?? 'Walk-in';

                        $customerParts =
                            preg_split('/\s+/', trim($customerName));

                        $customerInitials = '';

                        foreach (array_slice($customerParts, 0, 2) as $part) {

                            if ($part !== '') {
                                $customerInitials .=
                                    strtoupper(substr($part, 0, 1));
                            }

                        }

                        if ($customerInitials === '') {
                            $customerInitials = 'W';
                        }


                        /*
                         * Staff initials
                         */
                        $staffName =
                            $appt->staff->full_name ?? 'Unassigned';

                        $staffParts =
                            preg_split('/\s+/', trim($staffName));

                        $staffInitials = '';

                        foreach (array_slice($staffParts, 0, 2) as $part) {

                            if ($part !== '') {
                                $staffInitials .=
                                    strtoupper(substr($part, 0, 1));
                            }

                        }

                        if ($staffInitials === '') {
                            $staffInitials = '—';
                        }


                        /*
                         * Payment
                         */
                        $paidAmount =
                            $appt->payments->sum('amount');

                        $totalAmount =
                            $appt->total_price ?? 0;

                        $balance =
                            max(0, $totalAmount - $paidAmount);


                        /*
                         * Status
                         */
                        $statusConfig = match($appt->status) {

                            'pending' => [
                                'label' => 'Pending',
                                'class' =>
                                    'bg-amber-50 text-amber-700 border-amber-200
                                     dark:bg-amber-900/20 dark:text-amber-300
                                     dark:border-amber-800',
                                'dot' => 'bg-amber-500',
                            ],

                            'confirmed' => [
                                'label' => 'Confirmed',
                                'class' =>
                                    'bg-blue-50 text-blue-700 border-blue-200
                                     dark:bg-blue-900/20 dark:text-blue-300
                                     dark:border-blue-800',
                                'dot' => 'bg-blue-500',
                            ],

                            'completed' => [
                                'label' => 'Completed',
                                'class' =>
                                    'bg-emerald-50 text-emerald-700 border-emerald-200
                                     dark:bg-emerald-900/20 dark:text-emerald-300
                                     dark:border-emerald-800',
                                'dot' => 'bg-emerald-500',
                            ],

                            'cancelled' => [
                                'label' => 'Cancelled',
                                'class' =>
                                    'bg-red-50 text-red-700 border-red-200
                                     dark:bg-red-900/20 dark:text-red-300
                                     dark:border-red-800',
                                'dot' => 'bg-red-500',
                            ],

                            'no_show' => [
                                'label' => 'No-show',
                                'class' =>
                                    'bg-rose-50 text-rose-700 border-rose-200
                                     dark:bg-rose-900/20 dark:text-rose-300
                                     dark:border-rose-800',
                                'dot' => 'bg-rose-500',
                            ],

                            default => [
                                'label' =>
                                    ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $appt->status
                                        )
                                    ),
                                'class' =>
                                    'bg-gray-50 text-gray-700 border-gray-200
                                     dark:bg-gray-800 dark:text-gray-300
                                     dark:border-gray-700',
                                'dot' => 'bg-gray-400',
                            ],

                        };

                    @endphp


                    <tr class="appointment-row
                               group
                               hover:bg-gray-50/70
                               dark:hover:bg-gray-800/30">


                        {{-- Appointment --}}
                        <td class="px-5 py-4">

                            <div class="flex items-center gap-3">

                                <div class="w-11 h-12 shrink-0
                                            rounded-xl
                                            bg-brand-50
                                            dark:bg-brand-900/20
                                            flex flex-col
                                            items-center
                                            justify-center">

                                    <span class="text-[9px]
                                                 font-black uppercase
                                                 text-brand-500
                                                 dark:text-brand-300">

                                        {{ $appointmentDate->format('M') }}

                                    </span>

                                    <span class="text-lg
                                                 font-black
                                                 leading-none
                                                 text-brand-700
                                                 dark:text-brand-200">

                                        {{ $appointmentDate->format('d') }}

                                    </span>

                                </div>


                                <div>

                                    <p class="font-bold text-sm
                                              text-gray-800
                                              dark:text-gray-100">

                                        {{ $startTime->format('g:i A') }}

                                        <span class="font-normal
                                                     text-gray-400">
                                            –
                                        </span>

                                        {{ $endTime->format('g:i A') }}

                                    </p>


                                    <div class="flex items-center
                                                gap-1.5 mt-1">

                                        <svg class="w-3 h-3 text-gray-400"
                                             viewBox="0 0 24 24"
                                             fill="none"
                                             stroke="currentColor"
                                             stroke-width="2">

                                            <circle cx="12"
                                                    cy="12"
                                                    r="9"/>

                                            <polyline points="12 7 12 12 15 14"/>

                                        </svg>

                                        <span class="text-[11px]
                                                     text-gray-400">

                                            {{ $duration }} min

                                        </span>

                                    </div>

                                </div>

                            </div>

                        </td>


                        {{-- Customer --}}
                        <td class="px-5 py-4">

                            <div class="flex items-center gap-3">

                                <div class="w-9 h-9 shrink-0
                                            rounded-full
                                            bg-gray-100
                                            dark:bg-gray-800
                                            flex items-center
                                            justify-center
                                            text-[11px]
                                            font-black
                                            text-gray-600
                                            dark:text-gray-300">

                                    {{ $customerInitials }}

                                </div>


                                <div class="min-w-0">

                                    <p class="font-bold text-sm
                                              truncate
                                              text-gray-800
                                              dark:text-gray-100
                                              max-w-[180px]">

                                        {{ $customerName }}

                                    </p>


                                    @if(!empty($appt->customer->phone_number))

                                        <p class="text-[11px]
                                                  text-gray-400
                                                  mt-0.5">

                                            {{ $appt->customer->phone_number }}

                                        </p>

                                    @else

                                        <p class="text-[11px]
                                                  text-gray-400
                                                  mt-0.5">

                                            Walk-in customer

                                        </p>

                                    @endif

                                </div>

                            </div>

                        </td>


                        {{-- Services --}}
                        <td class="px-5 py-4">

                            @if($appt->services->count())

                                <div class="flex flex-wrap gap-1.5
                                            max-w-[260px]">

                                    @foreach($appt->services->take(2) as $service)

                                        <span class="inline-flex
                                                     items-center
                                                     px-2.5 py-1
                                                     rounded-lg
                                                     bg-brand-50
                                                     dark:bg-brand-900/20
                                                     border
                                                     border-brand-100
                                                     dark:border-brand-800
                                                     text-[10px]
                                                     font-bold
                                                     text-brand-700
                                                     dark:text-brand-300">

                                            {{ $service->name }}

                                        </span>

                                    @endforeach


                                    @if($appt->services->count() > 2)

                                        <span class="inline-flex
                                                     items-center
                                                     px-2 py-1
                                                     rounded-lg
                                                     bg-gray-100
                                                     dark:bg-gray-800
                                                     text-[10px]
                                                     font-bold
                                                     text-gray-500
                                                     dark:text-gray-400">

                                            +{{ $appt->services->count() - 2 }}

                                        </span>

                                    @endif

                                </div>

                            @else

                                <span class="text-xs text-gray-400">
                                    No service listed
                                </span>

                            @endif

                        </td>


                        {{-- Staff / Room --}}
                        <td class="px-5 py-4">

                            <div class="flex items-center gap-2.5">

                                <div class="w-8 h-8 shrink-0
                                            rounded-lg
                                            bg-gray-100
                                            dark:bg-gray-800
                                            flex items-center
                                            justify-center
                                            text-[10px]
                                            font-black
                                            text-gray-600
                                            dark:text-gray-300">

                                    {{ $staffInitials }}

                                </div>


                                <div>

                                    <p class="text-xs font-bold
                                              text-gray-700
                                              dark:text-gray-200">

                                        {{ $staffName }}

                                    </p>


                                    <div class="flex items-center
                                                gap-1 mt-1">

                                        <svg class="w-3 h-3 text-gray-400"
                                             viewBox="0 0 24 24"
                                             fill="none"
                                             stroke="currentColor"
                                             stroke-width="1.8">

                                            <path d="M3 21h18"/>
                                            <path d="M5 21V5l7-3 7 3v16"/>
                                            <path d="M9 9h1"/>
                                            <path d="M14 9h1"/>
                                            <path d="M9 13h1"/>
                                            <path d="M14 13h1"/>

                                        </svg>


                                        <span class="text-[10px]
                                                     text-gray-400">

                                            {{ $appt->room->name ?? 'No room assigned' }}

                                        </span>

                                    </div>

                                </div>

                            </div>

                        </td>


                        {{-- Payment --}}
                        <td class="px-5 py-4">

                            <p class="text-sm font-black
                                      text-gray-800
                                      dark:text-gray-100">

                                ₱{{ number_format($totalAmount, 2) }}

                            </p>


                            <div class="mt-1 text-[10px]">

                                @if($paidAmount >= $totalAmount && $totalAmount > 0)

                                    <span class="font-bold
                                                 text-emerald-600
                                                 dark:text-emerald-400">
                                        Paid
                                    </span>

                                @elseif($paidAmount > 0)

                                    <span class="font-semibold
                                                 text-amber-600
                                                 dark:text-amber-400">

                                        ₱{{ number_format($paidAmount, 2) }}
                                        paid

                                    </span>

                                @else

                                    <span class="font-semibold
                                                 text-gray-400">
                                        Unpaid
                                    </span>

                                @endif

                            </div>


                            @if($balance > 0)

                                <p class="text-[10px]
                                          text-gray-400
                                          mt-0.5">

                                    ₱{{ number_format($balance, 2) }}
                                    balance

                                </p>

                            @endif

                        </td>


                        {{-- Status --}}
                        <td class="px-5 py-4 text-right">

                            <span class="inline-flex
                                         items-center gap-1.5
                                         px-2.5 py-1.5
                                         rounded-lg
                                         border
                                         text-[10px]
                                         font-black
                                         uppercase
                                         tracking-wide
                                         {{ $statusConfig['class'] }}">

                                <span class="w-1.5 h-1.5
                                             rounded-full
                                             {{ $statusConfig['dot'] }}">
                                </span>

                                {{ $statusConfig['label'] }}

                            </span>

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td colspan="6"
                            class="px-5 py-16 text-center">

                            <div class="mx-auto w-14 h-14
                                        rounded-2xl
                                        bg-gray-100
                                        dark:bg-gray-800
                                        flex items-center
                                        justify-center">

                                <svg class="w-7 h-7 text-gray-400"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="1.6">

                                    <rect x="3"
                                          y="4"
                                          width="18"
                                          height="18"
                                          rx="2"/>

                                    <line x1="16"
                                          y1="2"
                                          x2="16"
                                          y2="6"/>

                                    <line x1="8"
                                          y1="2"
                                          x2="8"
                                          y2="6"/>

                                    <line x1="3"
                                          y1="10"
                                          x2="21"
                                          y2="10"/>

                                </svg>

                            </div>


                            <h3 class="mt-4 text-sm font-black
                                       text-gray-800
                                       dark:text-gray-200">

                                No appointments found

                            </h3>


                            <p class="mt-1 text-xs
                                      text-gray-400
                                      max-w-sm mx-auto">

                                There are no appointments matching
                                your current search and filter settings.

                            </p>


                            @if($hasFilters)

                                <a
                                    href="{{ route('admin.appointments') }}"
                                    class="inline-flex
                                           items-center
                                           justify-center
                                           mt-4
                                           px-4 py-2
                                           rounded-xl
                                           bg-brand-600
                                           hover:bg-brand-700
                                           text-white
                                           text-xs
                                           font-bold
                                           transition">

                                    Clear all filters

                                </a>

                            @endif

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- Pagination --}}
    @if(method_exists($appointments, 'links'))

        <div class="px-5 py-4
                    border-t
                    border-gray-100
                    dark:border-gray-700/60">

            {{ $appointments->withQueryString()->links() }}

        </div>

    @endif

</div>

</div>

@endsection

@push('scripts')

{{-- Flatpickr --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

{{-- Tom Select --}}
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>

{{-- Preline UI --}}
<script src="https://cdn.jsdelivr.net/npm/preline@2/dist/preline.min.js"></script>


<script>
    document.addEventListener('DOMContentLoaded', function () {

        /*
         * ---------------------------------------------------------
         * Preline
         * ---------------------------------------------------------
         */
        if (window.HSStaticMethods) {
            window.HSStaticMethods.autoInit();
        }


        /*
         * ---------------------------------------------------------
         * Tom Select - Staff
         * ---------------------------------------------------------
         */
        const staffElement =
            document.getElementById('staffFilter');

        if (staffElement && window.TomSelect) {

            new TomSelect(staffElement, {
                create: false,
                allowEmptyOption: true,
                maxOptions: 1000,
                placeholder: 'All staff',
                searchField: ['text']
            });

        }


        /*
         * ---------------------------------------------------------
         * Flatpickr - Date Range
         * ---------------------------------------------------------
         */
        const datePicker =
            document.getElementById('dateRangePicker');

        const dateFrom =
            document.getElementById('dateFrom');

        const dateTo =
            document.getElementById('dateTo');


        if (datePicker && window.flatpickr) {

            const existingFrom =
                dateFrom ? dateFrom.value : '';

            const existingTo =
                dateTo ? dateTo.value : '';

            let defaultDates = [];


            if (existingFrom) {
                defaultDates.push(existingFrom);
            }


            if (existingTo && existingTo !== existingFrom) {
                defaultDates.push(existingTo);
            }


            flatpickr(datePicker, {

                mode: 'range',

                dateFormat: 'Y-m-d',

                altInput: true,

                altFormat: 'M j, Y',

                defaultDate: defaultDates,

                allowInput: false,


                onChange: function (
                    selectedDates,
                    dateStr,
                    instance
                ) {

                    if (!dateFrom || !dateTo) {
                        return;
                    }


                    if (selectedDates.length === 0) {

                        dateFrom.value = '';
                        dateTo.value = '';

                    }

                    else if (selectedDates.length === 1) {

                        dateFrom.value =
                            instance.formatDate(
                                selectedDates[0],
                                'Y-m-d'
                            );

                        dateTo.value = '';

                    }

                    else {

                        dateFrom.value =
                            instance.formatDate(
                                selectedDates[0],
                                'Y-m-d'
                            );

                        dateTo.value =
                            instance.formatDate(
                                selectedDates[1],
                                'Y-m-d'
                            );

                    }

                }

            });

        }


        /*
         * ---------------------------------------------------------
         * Quick Status Filters
         * ---------------------------------------------------------
         */
        const quickFilters =
            document.querySelectorAll('.quick-status');

        const statusFilter =
            document.getElementById('statusFilter');

        const filterForm =
            document.getElementById('appointmentFilterForm');


        quickFilters.forEach(function (button) {

            button.addEventListener('click', function () {

                if (!statusFilter || !filterForm) {
                    return;
                }

                statusFilter.value =
                    button.dataset.status || '';

                filterForm.submit();

            });

        });


        /*
         * ---------------------------------------------------------
         * Search Enter Key
         * ---------------------------------------------------------
         */
        const searchInput =
            document.querySelector('input[name="search"]');


        if (searchInput && filterForm) {

            searchInput.addEventListener(
                'keydown',
                function (event) {

                    if (event.key === 'Enter') {

                        event.preventDefault();

                        filterForm.submit();

                    }

                }
            );

        }

    });
</script>

@endpush
