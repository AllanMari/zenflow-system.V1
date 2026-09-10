@php
    $user = auth()->user();
    $isAdmin = $user->roles->contains('name', 'admin');
@endphp

@extends($isAdmin ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Room Tracking')

@section('content')

@php
    $trackingDateCarbon = \Carbon\Carbon::parse($trackingDate);

    /*
    |--------------------------------------------------------------------------
    | Matrix configuration
    |--------------------------------------------------------------------------
    */
    $businessStart = 10 * 60; // 10:00 AM
    $businessEnd   = 20 * 60; // 8:00 PM
    $interval      = 30;

    $timeColumns = [];

    for ($minutes = $businessStart; $minutes < $businessEnd; $minutes += $interval) {
        $timeColumns[] = $minutes;
    }

    $formatMinutes = function ($minutes) {
        $hour = intdiv($minutes, 60);
        $minute = $minutes % 60;

        $suffix = $hour >= 12 ? 'PM' : 'AM';
        $displayHour = $hour % 12 ?: 12;

        return sprintf('%d:%02d %s', $displayHour, $minute, $suffix);
    };

    $timeToMinutes = function ($time) {
        if (!$time) {
            return null;
        }

        $value = substr((string) $time, 0, 5);
        $parts = explode(':', $value);

        if (count($parts) < 2) {
            return null;
        }

        return ((int) $parts[0] * 60) + (int) $parts[1];
    };

    /*
    |--------------------------------------------------------------------------
    | Summary calculations
    |--------------------------------------------------------------------------
    */
    $allTrackedAppointments = $rooms
        ->flatMap(fn ($room) => $room->appointments)
        ->values();

    $pendingAppointments = $allTrackedAppointments
        ->where('status', 'pending')
        ->count();

    $confirmedAppointments = $allTrackedAppointments
        ->where('status', 'confirmed')
        ->count();

    $completedAppointments = $allTrackedAppointments
        ->where('status', 'completed')
        ->count();

    $activeRooms = $rooms
        ->where('is_active', true)
        ->where('status', '!=', 'maintenance')
        ->count();

    $maintenanceRooms = $rooms
        ->where('status', 'maintenance')
        ->count();

    $inactiveRooms = $rooms
        ->where('is_active', false)
        ->count();

    $occupiedRooms = $rooms
        ->filter(function ($room) {
            return $room->is_active
                && strtolower((string) $room->status) === 'occupied';
        })
        ->count();
@endphp


{{-- =============================================================
     FLATPICKR CSS
============================================================== --}}
<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css"
>


<div
    x-data="roomTrackingPage()"
    x-init="init()"
    class="mx-auto max-w-[1600px] space-y-4 pb-8"
>


    {{-- =========================================================
         DATE NAVIGATION
    ========================================================== --}}
    <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">

        <div class="flex flex-col gap-4 px-4 py-4 sm:px-5 lg:flex-row lg:items-center lg:justify-between">


            {{-- Date information --}}
            <div class="flex min-w-0 items-center gap-3">

                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-300">

                    {{-- Calendar icon --}}
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-5 w-5"
                        aria-hidden="true"
                    >
                        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                        <path d="M16 2v4"></path>
                        <path d="M8 2v4"></path>
                        <path d="M3 9h18"></path>
                    </svg>

                </div>


                <div class="min-w-0">

                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400">
                        Room tracking
                    </p>

                    <div class="flex flex-wrap items-center gap-2">

                        <h2 class="truncate text-sm font-extrabold text-gray-900 dark:text-white sm:text-base">
                            {{ $trackingDateCarbon->format('l, F j, Y') }}
                        </h2>

                        @if($trackingDateCarbon->isToday())

                            <span class="inline-flex rounded-full bg-brand-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-brand-700 dark:bg-brand-900/20 dark:text-brand-300">
                                Today
                            </span>

                        @endif

                    </div>

                </div>

            </div>


            {{-- Date controls --}}
            <div class="flex items-center gap-1.5">

                {{-- Previous day --}}
                <button
                    type="button"
                    @click="changeDate(-1)"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-300 dark:hover:border-brand-700 dark:hover:bg-brand-900/20 dark:hover:text-brand-300"
                    title="Previous day"
                    aria-label="Previous day"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="h-4 w-4"
                        aria-hidden="true"
                    >
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>

                </button>


                {{-- Flatpickr date picker --}}
                <div class="relative">

                    {{-- Calendar icon --}}
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="pointer-events-none absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-gray-400"
                        aria-hidden="true"
                    >
                        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                        <path d="M16 2v4"></path>
                        <path d="M8 2v4"></path>
                        <path d="M3 9h18"></path>
                    </svg>


                    <input
                        id="room-tracking-date"
                        type="text"
                        value="{{ $trackingDateCarbon->format('F j, Y') }}"
                        readonly
                        autocomplete="off"
                        class="room-tracking-date-input h-9 w-[210px] cursor-pointer rounded-lg border border-gray-200 bg-white pl-9 pr-8 text-xs font-semibold text-gray-700 outline-none transition hover:border-gray-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-200 dark:hover:border-slate-600"
                        aria-label="Select tracking date"
                    >


                    {{-- Dropdown arrow --}}
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="pointer-events-none absolute right-3 top-1/2 z-10 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"
                        aria-hidden="true"
                    >
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>

                </div>


                {{-- Next day --}}
                <button
                    type="button"
                    @click="changeDate(1)"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-300 dark:hover:border-brand-700 dark:hover:bg-brand-900/20 dark:hover:text-brand-300"
                    title="Next day"
                    aria-label="Next day"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="h-4 w-4"
                        aria-hidden="true"
                    >
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>

                </button>


                {{-- Today --}}
                <button
                    type="button"
                    @click="goToday()"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-brand-600 px-3 text-xs font-bold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-4 w-4"
                        aria-hidden="true"
                    >
                        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                        <path d="M16 2v4"></path>
                        <path d="M8 2v4"></path>
                        <path d="M3 9h18"></path>
                        <path d="m9 15 2 2 4-4"></path>
                    </svg>

                    Today

                </button>

            </div>

        </div>

    </section>



    {{-- =========================================================
         SUMMARY CARDS
    ========================================================== --}}
    <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">


        {{-- Total Rooms --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">

            <div class="flex items-center gap-3">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-300">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-4 w-4"
                        aria-hidden="true"
                    >
                        <path d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16"></path>
                        <path d="M2 21h20"></path>
                        <path d="M8 7h2"></path>
                        <path d="M14 7h2"></path>
                        <path d="M8 11h2"></path>
                        <path d="M14 11h2"></path>
                        <path d="M8 15h2"></path>
                        <path d="M14 15h2"></path>
                    </svg>

                </div>

                <div>

                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                        Rooms
                    </p>

                    <p class="mt-1 text-lg font-extrabold leading-none text-gray-900 dark:text-white">
                        {{ $rooms->count() }}
                    </p>

                </div>

            </div>

        </div>



        {{-- Scheduled --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">

            <div class="flex items-center gap-3">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-300">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-4 w-4"
                        aria-hidden="true"
                    >
                        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                        <path d="M16 2v4"></path>
                        <path d="M8 2v4"></path>
                        <path d="M3 9h18"></path>
                        <circle cx="12" cy="14" r="3"></circle>
                        <path d="M12 12v2l1.5 1"></path>
                    </svg>

                </div>

                <div>

                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                        Scheduled
                    </p>

                    <p class="mt-1 text-lg font-extrabold leading-none text-gray-900 dark:text-white">
                        {{ $allTrackedAppointments->count() }}
                    </p>

                </div>

            </div>

        </div>



        {{-- Pending --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">

            <div class="flex items-center gap-3">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-300">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-4 w-4"
                        aria-hidden="true"
                    >
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 7v5l3 2"></path>
                    </svg>

                </div>

                <div>

                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                        Pending
                    </p>

                    <p class="mt-1 text-lg font-extrabold leading-none text-gray-900 dark:text-white">
                        {{ $pendingAppointments }}
                    </p>

                </div>

            </div>

        </div>



        {{-- Confirmed --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">

            <div class="flex items-center gap-3">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-300">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-4 w-4"
                        aria-hidden="true"
                    >
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="m8 12 2.5 2.5L16 9"></path>
                    </svg>

                </div>

                <div>

                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                        Confirmed
                    </p>

                    <p class="mt-1 text-lg font-extrabold leading-none text-gray-900 dark:text-white">
                        {{ $confirmedAppointments }}
                    </p>

                </div>

            </div>

        </div>



        {{-- Completed --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">

            <div class="flex items-center gap-3">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-300">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-4 w-4"
                        aria-hidden="true"
                    >
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="m8 12 2.5 2.5L16 9"></path>
                        <path d="M16 16.5A6 6 0 0 1 7.5 8"></path>
                    </svg>

                </div>

                <div>

                    <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                        Completed
                    </p>

                    <p class="mt-1 text-lg font-extrabold leading-none text-gray-900 dark:text-white">
                        {{ $completedAppointments }}
                    </p>

                </div>

            </div>

        </div>

    </section>



    {{-- =========================================================
         ROOM STATUS
    ========================================================== --}}
    <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">

        <div class="flex flex-col gap-3 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">


            <div class="flex items-center gap-2">

                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-gray-300">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-4 w-4"
                        aria-hidden="true"
                    >
                        <path d="M4 5h16"></path>
                        <path d="M4 12h16"></path>
                        <path d="M4 19h16"></path>
                        <circle cx="7" cy="5" r="1"></circle>
                        <circle cx="17" cy="12" r="1"></circle>
                        <circle cx="9" cy="19" r="1"></circle>
                    </svg>

                </div>


                <div>

                    <p class="text-xs font-extrabold text-gray-900 dark:text-white">
                        Room Status
                    </p>

                    <p class="text-[10px] text-gray-400">
                        Overview for {{ $trackingDateCarbon->format('M j, Y') }}
                    </p>

                </div>

            </div>


            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">

                <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-gray-500 dark:text-gray-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Available
                </span>

                <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-gray-500 dark:text-gray-400">
                    <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                    Booked
                </span>

                <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-gray-500 dark:text-gray-400">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    Pending
                </span>

                <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-gray-500 dark:text-gray-400">
                    <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                    Completed
                </span>

                <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-gray-500 dark:text-gray-400">
                    <span class="h-2 w-2 rounded-full bg-red-500"></span>
                    Maintenance
                </span>

            </div>

        </div>


        <div class="grid grid-cols-2 border-t border-gray-100 sm:grid-cols-4 dark:border-slate-700">


            <div class="border-b border-r border-gray-100 px-4 py-3 sm:border-b-0 dark:border-slate-700">

                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                    Active
                </p>

                <p class="mt-1 text-sm font-extrabold text-gray-900 dark:text-white">
                    {{ $activeRooms }}
                </p>

            </div>


            <div class="border-b border-gray-100 px-4 py-3 sm:border-b-0 sm:border-r dark:border-slate-700">

                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                    Occupied
                </p>

                <p class="mt-1 text-sm font-extrabold text-gray-900 dark:text-white">
                    {{ $occupiedRooms }}
                </p>

            </div>


            <div class="border-r border-gray-100 px-4 py-3 dark:border-slate-700">

                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                    Maintenance
                </p>

                <p class="mt-1 text-sm font-extrabold text-gray-900 dark:text-white">
                    {{ $maintenanceRooms }}
                </p>

            </div>


            <div class="px-4 py-3">

                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                    Inactive
                </p>

                <p class="mt-1 text-sm font-extrabold text-gray-900 dark:text-white">
                    {{ $inactiveRooms }}
                </p>

            </div>

        </div>

    </section>



    {{-- =========================================================
         ROOM SCHEDULE
    ========================================================== --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">


        <div class="flex flex-col gap-2 border-b border-gray-100 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700">

            <div class="flex items-center gap-2.5">

                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-300">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-4 w-4"
                        aria-hidden="true"
                    >
                        <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                        <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                        <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                        <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                    </svg>

                </div>


                <div>

                    <h2 class="text-xs font-extrabold text-gray-900 dark:text-white">
                        Room Schedule
                    </h2>

                    <p class="mt-0.5 text-[10px] text-gray-400">
                        10:00 AM – 8:00 PM · 30-minute intervals
                    </p>

                </div>

            </div>


            <div class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-gray-400">

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    class="h-3.5 w-3.5"
                    aria-hidden="true"
                >
                    <path d="M9 4 4 9l5 5"></path>
                    <path d="M4 9h10a6 6 0 0 1 6 6v5"></path>
                </svg>

                Click a booking for details

            </div>

        </div>


        @if($rooms->isEmpty())

            <div class="px-6 py-16 text-center">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 text-gray-400 dark:bg-slate-700 dark:text-gray-500">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        class="h-6 w-6"
                        aria-hidden="true"
                    >
                        <path d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16"></path>
                        <path d="M2 21h20"></path>
                    </svg>

                </div>


                <h3 class="mt-3 text-sm font-bold text-gray-800 dark:text-gray-200">
                    No rooms configured
                </h3>


                <p class="mx-auto mt-1 max-w-sm text-xs text-gray-400">
                    Add rooms from Room Management to start tracking room usage.
                </p>

            </div>

        @else


            {{-- =================================================
                 DESKTOP MATRIX
            ================================================== --}}
            <div class="hidden overflow-x-auto lg:block">

                <table class="min-w-[1300px] w-full border-collapse">

                    <thead>

                        <tr class="bg-gray-50 dark:bg-slate-900/60">

                            <th class="sticky left-0 z-30 w-[215px] min-w-[215px] border-b border-r border-gray-200 bg-gray-50 px-4 py-3 text-left dark:border-slate-700 dark:bg-slate-900">

                                <div class="flex items-center gap-2">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="h-3.5 w-3.5 text-gray-400"
                                        aria-hidden="true"
                                    >
                                        <path d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16"></path>
                                        <path d="M2 21h20"></path>
                                    </svg>

                                    <span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                                        Room
                                    </span>

                                </div>

                            </th>


                            @foreach($timeColumns as $minutes)

                                <th class="w-[67px] min-w-[67px] border-b border-r border-gray-200 px-1 py-3 text-center dark:border-slate-700">

                                    <span class="whitespace-nowrap text-[9px] font-bold text-gray-500 dark:text-gray-400">
                                        {{ $formatMinutes($minutes) }}
                                    </span>

                                </th>

                            @endforeach

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($rooms as $room)

                            @php
                                $roomAppointments = $room->appointments
                                    ->filter(function ($appointment) use ($trackingDate) {
                                        return \Carbon\Carbon::parse($appointment->appointment_date)->toDateString()
                                            === \Carbon\Carbon::parse($trackingDate)->toDateString();
                                    })
                                    ->sortBy('start_time')
                                    ->values();

                                $roomStatus = strtolower((string) $room->status);
                                $roomIsActive = (bool) $room->is_active;
                            @endphp


                            <tr class="border-b border-gray-100 last:border-b-0 dark:border-slate-700">


                                {{-- Room information --}}
                                <td class="sticky left-0 z-20 border-r border-gray-200 bg-white px-4 py-3 align-top dark:border-slate-700 dark:bg-slate-800">

                                    <div class="flex items-center gap-2.5">

                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg
                                            {{
                                                !$roomIsActive
                                                    ? 'bg-gray-100 text-gray-400 dark:bg-slate-700 dark:text-slate-500'
                                                    : ($roomStatus === 'maintenance'
                                                        ? 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-300'
                                                        : ($roomStatus === 'occupied'
                                                            ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-300'
                                                            : 'bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-300'))
                                            }}"
                                        >

                                            @if(!$roomIsActive)

                                                {{-- Eye off --}}
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    class="h-4 w-4"
                                                    aria-hidden="true"
                                                >
                                                    <path d="m3 3 18 18"></path>
                                                    <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                                                    <path d="M9.4 5.3A10.5 10.5 0 0 1 12 5c7 0 10 7 10 7a16 16 0 0 1-3.1 4.2"></path>
                                                    <path d="M6.1 6.1C3.6 7.7 2 12 2 12s3 7 10 7a10 10 0 0 0 3-.5"></path>
                                                </svg>

                                            @elseif($roomStatus === 'maintenance')

                                                {{-- Wrench --}}
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    class="h-4 w-4"
                                                    aria-hidden="true"
                                                >
                                                    <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6.2 6.2a2 2 0 0 0 2.8 2.8l6.2-6.2a4 4 0 0 0 5.4-5.4l-2.2 2.2-2.8-2.8z"></path>
                                                </svg>

                                            @elseif($roomStatus === 'occupied')

                                                {{-- Door --}}
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    class="h-4 w-4"
                                                    aria-hidden="true"
                                                >
                                                    <path d="M5 21V4a2 2 0 0 1 2-2h10v19"></path>
                                                    <path d="M5 21h14"></path>
                                                    <path d="M14 12h.01"></path>
                                                </svg>

                                            @else

                                                {{-- Door --}}
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    class="h-4 w-4"
                                                    aria-hidden="true"
                                                >
                                                    <path d="M5 21V4a2 2 0 0 1 2-2h10v19"></path>
                                                    <path d="M5 21h14"></path>
                                                    <path d="M14 12h.01"></path>
                                                </svg>

                                            @endif

                                        </div>


                                        <div class="min-w-0">

                                            <p class="truncate text-xs font-extrabold text-gray-900 dark:text-white">
                                                {{ $room->name }}
                                            </p>

                                            <p class="mt-0.5 truncate text-[10px] text-gray-400">
                                                {{ $room->category?->name ?? 'General Use' }}
                                            </p>


                                            <div class="mt-1.5">

                                                @if(!$roomIsActive)

                                                    <span class="inline-flex items-center gap-1 text-[8px] font-bold uppercase tracking-wide text-gray-400">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                                        Inactive
                                                    </span>

                                                @elseif($roomStatus === 'maintenance')

                                                    <span class="inline-flex items-center gap-1 text-[8px] font-bold uppercase tracking-wide text-red-500">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                        Maintenance
                                                    </span>

                                                @elseif($roomStatus === 'occupied')

                                                    <span class="inline-flex items-center gap-1 text-[8px] font-bold uppercase tracking-wide text-orange-500">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>
                                                        Occupied
                                                    </span>

                                                @else

                                                    <span class="inline-flex items-center gap-1 text-[8px] font-bold uppercase tracking-wide text-emerald-500">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                        Available
                                                    </span>

                                                @endif

                                            </div>

                                        </div>

                                    </div>

                                </td>


                                {{-- Time cells --}}
                                @foreach($timeColumns as $columnStart)

                                    @php
                                        $columnEnd = $columnStart + $interval;

                                        $appointment = $roomAppointments->first(function ($appt) use (
                                            $columnStart,
                                            $columnEnd,
                                            $timeToMinutes
                                        ) {
                                            $apptStart = $timeToMinutes($appt->start_time);
                                            $apptEnd = $timeToMinutes($appt->end_time);

                                            if ($apptStart === null || $apptEnd === null) {
                                                return false;
                                            }

                                            return $apptStart < $columnEnd
                                                && $apptEnd > $columnStart;
                                        });

                                        $appointmentStart = $appointment
                                            ? $timeToMinutes($appointment->start_time)
                                            : null;

                                        $showAppointmentLabel = $appointment
                                            && $appointmentStart !== null
                                            && $appointmentStart >= $columnStart
                                            && $appointmentStart < $columnEnd;

                                        $appointmentStatus = $appointment
                                            ? strtolower((string) $appointment->status)
                                            : null;
                                    @endphp


                                    <td class="h-[70px] border-r border-gray-100 p-1 dark:border-slate-700">

                                        @if(!$roomIsActive)

                                            <div class="flex h-full items-center justify-center rounded-md bg-gray-50 dark:bg-slate-900/50">
                                                <span class="text-[8px] text-gray-300 dark:text-slate-600">
                                                    —
                                                </span>
                                            </div>


                                        @elseif($roomStatus === 'maintenance')

                                            <div class="flex h-full items-center justify-center rounded-md bg-red-50/70 dark:bg-red-900/10">

                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    class="h-3.5 w-3.5 text-red-400"
                                                    aria-hidden="true"
                                                >
                                                    <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6.2 6.2a2 2 0 0 0 2.8 2.8l6.2-6.2a4 4 0 0 0 5.4-5.4l-2.2 2.2-2.8-2.8z"></path>
                                                </svg>

                                            </div>


                                        @elseif($appointment)

                                            @php
                                                $customerName = $appointment->customer?->full_name
                                                    ?? trim(
                                                        ($appointment->customer?->first_name ?? '')
                                                        . ' '
                                                        . ($appointment->customer?->last_name ?? '')
                                                    )
                                                    ?: 'Walk-in / Guest';

                                                $serviceNames = $appointment->services
                                                    ->pluck('name')
                                                    ->filter()
                                                    ->join(', ');

                                                $staffName = $appointment->staff?->full_name
                                                    ?? trim(
                                                        ($appointment->staff?->first_name ?? '')
                                                        . ' '
                                                        . ($appointment->staff?->last_name ?? '')
                                                    )
                                                    ?: 'Unassigned';

                                                $appointmentData = [
                                                    'customer' => $customerName,
                                                    'service' => $serviceNames ?: 'No service listed',
                                                    'staff' => $staffName,
                                                    'start' => \Carbon\Carbon::parse($appointment->start_time)->format('g:i A'),
                                                    'end' => \Carbon\Carbon::parse($appointment->end_time)->format('g:i A'),
                                                    'status' => ucfirst($appointment->status),
                                                    'room' => $room->name,
                                                    'date' => \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y'),
                                                ];


                                                $statusClasses = match($appointmentStatus) {

                                                    'pending' =>
                                                        'border-amber-200 bg-amber-50 hover:bg-amber-100 dark:border-amber-800/60 dark:bg-amber-900/20 dark:hover:bg-amber-900/30',

                                                    'completed' =>
                                                        'border-purple-200 bg-purple-50 hover:bg-purple-100 dark:border-purple-800/60 dark:bg-purple-900/20 dark:hover:bg-purple-900/30',

                                                    default =>
                                                        'border-brand-200 bg-brand-50 hover:bg-brand-100 dark:border-brand-800/60 dark:bg-brand-900/20 dark:hover:bg-brand-900/30',
                                                };


                                                $statusText = match($appointmentStatus) {

                                                    'pending' =>
                                                        'text-amber-700 dark:text-amber-300',

                                                    'completed' =>
                                                        'text-purple-700 dark:text-purple-300',

                                                    default =>
                                                        'text-brand-700 dark:text-brand-300',
                                                };


                                                $dotClass = match($appointmentStatus) {
                                                    'pending' => 'bg-amber-500',
                                                    'completed' => 'bg-purple-500',
                                                    default => 'bg-brand-500',
                                                };
                                            @endphp


                                            <button
                                                type="button"
                                                @click="openAppointment(@js($appointmentData))"
                                                class="flex h-full w-full items-center overflow-hidden rounded-md border px-1.5 text-left transition hover:-translate-y-px hover:shadow-sm {{ $statusClasses }}"
                                                title="View appointment details"
                                            >

                                                @if($showAppointmentLabel)

                                                    <div class="min-w-0">

                                                        <p class="truncate text-[8px] font-extrabold uppercase tracking-wide {{ $statusText }}">
                                                            {{ ucfirst($appointment->status) }}
                                                        </p>

                                                        <p class="mt-0.5 truncate text-[9px] font-bold text-gray-800 dark:text-gray-100">
                                                            {{ $customerName }}
                                                        </p>

                                                        <p class="mt-0.5 truncate text-[8px] text-gray-500 dark:text-gray-400">
                                                            {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i') }}
                                                            -
                                                            {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
                                                        </p>

                                                    </div>

                                                @else

                                                    <span class="mx-auto h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>

                                                @endif

                                            </button>


                                        @else

                                            <div class="flex h-full items-center justify-center rounded-md bg-emerald-50/40 dark:bg-emerald-900/5">

                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="h-3 w-3 text-emerald-300 dark:text-emerald-700"
                                                    aria-hidden="true"
                                                >
                                                    <path d="m5 12 4 4L19 6"></path>
                                                </svg>

                                            </div>

                                        @endif

                                    </td>

                                @endforeach

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>



            {{-- =================================================
                 MOBILE / TABLET
            ================================================== --}}
            <div class="divide-y divide-gray-100 lg:hidden dark:divide-slate-700">

                @foreach($rooms as $room)

                    @php
                        $roomAppointments = $room->appointments
                            ->filter(function ($appointment) use ($trackingDate) {
                                return \Carbon\Carbon::parse($appointment->appointment_date)->toDateString()
                                    === \Carbon\Carbon::parse($trackingDate)->toDateString();
                            })
                            ->sortBy('start_time')
                            ->values();

                        $roomStatus = strtolower((string) $room->status);
                        $roomIsActive = (bool) $room->is_active;
                    @endphp


                    <div class="p-4">


                        {{-- Room heading --}}
                        <div class="flex items-center justify-between gap-3">

                            <div class="flex min-w-0 items-center gap-2.5">

                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg
                                    {{
                                        !$roomIsActive
                                            ? 'bg-gray-100 text-gray-400 dark:bg-slate-700 dark:text-slate-500'
                                            : ($roomStatus === 'maintenance'
                                                ? 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-300'
                                                : ($roomStatus === 'occupied'
                                                    ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-300'
                                                    : 'bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-300'))
                                    }}"
                                >

                                    @if(!$roomIsActive)

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            class="h-4 w-4"
                                            aria-hidden="true"
                                        >
                                            <path d="m3 3 18 18"></path>
                                            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                                            <path d="M9.4 5.3A10.5 10.5 0 0 1 12 5c7 0 10 7 10 7a16 16 0 0 1-3.1 4.2"></path>
                                            <path d="M6.1 6.1C3.6 7.7 2 12 2 12s3 7 10 7a10 10 0 0 0 3-.5"></path>
                                        </svg>

                                    @elseif($roomStatus === 'maintenance')

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            class="h-4 w-4"
                                            aria-hidden="true"
                                        >
                                            <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6.2 6.2a2 2 0 0 0 2.8 2.8l6.2-6.2a4 4 0 0 0 5.4-5.4l-2.2 2.2-2.8-2.8z"></path>
                                        </svg>

                                    @else

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            class="h-4 w-4"
                                            aria-hidden="true"
                                        >
                                            <path d="M5 21V4a2 2 0 0 1 2-2h10v19"></path>
                                            <path d="M5 21h14"></path>
                                            <path d="M14 12h.01"></path>
                                        </svg>

                                    @endif

                                </div>


                                <div class="min-w-0">

                                    <h3 class="truncate text-xs font-extrabold text-gray-900 dark:text-white">
                                        {{ $room->name }}
                                    </h3>

                                    <p class="mt-0.5 truncate text-[10px] text-gray-400">
                                        {{ $room->category?->name ?? 'General Use' }}
                                    </p>

                                </div>

                            </div>


                            @if(!$roomIsActive)

                                <span class="shrink-0 rounded-full bg-gray-100 px-2 py-1 text-[9px] font-bold text-gray-500 dark:bg-slate-700 dark:text-gray-400">
                                    Inactive
                                </span>

                            @elseif($roomStatus === 'maintenance')

                                <span class="shrink-0 rounded-full bg-red-50 px-2 py-1 text-[9px] font-bold text-red-600 dark:bg-red-900/20 dark:text-red-300">
                                    Maintenance
                                </span>

                            @elseif($roomStatus === 'occupied')

                                <span class="shrink-0 rounded-full bg-orange-50 px-2 py-1 text-[9px] font-bold text-orange-600 dark:bg-orange-900/20 dark:text-orange-300">
                                    Occupied
                                </span>

                            @else

                                <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-1 text-[9px] font-bold text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-300">
                                    Available
                                </span>

                            @endif

                        </div>


                        <div class="mt-3">


                            @if(!$roomIsActive)

                                <div class="rounded-lg bg-gray-50 px-4 py-4 text-center dark:bg-slate-900/40">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="mx-auto h-4 w-4 text-gray-300 dark:text-slate-600"
                                        aria-hidden="true"
                                    >
                                        <path d="m3 3 18 18"></path>
                                        <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                                        <path d="M9.4 5.3A10.5 10.5 0 0 1 12 5c7 0 10 7 10 7a16 16 0 0 1-3.1 4.2"></path>
                                        <path d="M6.1 6.1C3.6 7.7 2 12 2 12s3 7 10 7a10 10 0 0 0 3-.5"></path>
                                    </svg>

                                    <p class="mt-1.5 text-[10px] font-bold text-gray-400">
                                        Room is inactive
                                    </p>

                                </div>


                            @elseif($roomStatus === 'maintenance')

                                <div class="rounded-lg border border-red-200 bg-red-50/60 px-4 py-4 text-center dark:border-red-800/50 dark:bg-red-900/10">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="mx-auto h-4 w-4 text-red-500"
                                        aria-hidden="true"
                                    >
                                        <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6.2 6.2a2 2 0 0 0 2.8 2.8l6.2-6.2a4 4 0 0 0 5.4-5.4l-2.2 2.2-2.8-2.8z"></path>
                                    </svg>

                                    <p class="mt-1.5 text-[10px] font-bold text-red-700 dark:text-red-300">
                                        Room is under maintenance
                                    </p>

                                </div>


                            @elseif($roomAppointments->isEmpty())

                                <div class="rounded-lg border border-dashed border-emerald-200 bg-emerald-50/40 px-4 py-4 text-center dark:border-emerald-800/50 dark:bg-emerald-900/10">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        class="mx-auto h-4 w-4 text-emerald-500"
                                        aria-hidden="true"
                                    >
                                        <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                                        <path d="M16 2v4"></path>
                                        <path d="M8 2v4"></path>
                                        <path d="M3 9h18"></path>
                                        <path d="m8 14 2 2 5-5"></path>
                                    </svg>

                                    <p class="mt-1.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-300">
                                        No appointments
                                    </p>

                                    <p class="mt-0.5 text-[9px] text-emerald-600/70 dark:text-emerald-400/70">
                                        Room is available for this date.
                                    </p>

                                </div>


                            @else

                                <div class="space-y-2">

                                    @foreach($roomAppointments as $appointment)

                                        @php
                                            $customerName = $appointment->customer?->full_name
                                                ?? trim(
                                                    ($appointment->customer?->first_name ?? '')
                                                    . ' '
                                                    . ($appointment->customer?->last_name ?? '')
                                                )
                                                ?: 'Walk-in / Guest';

                                            $serviceNames = $appointment->services
                                                ->pluck('name')
                                                ->filter()
                                                ->join(', ');

                                            $staffName = $appointment->staff?->full_name
                                                ?? trim(
                                                    ($appointment->staff?->first_name ?? '')
                                                    . ' '
                                                    . ($appointment->staff?->last_name ?? '')
                                                )
                                                ?: 'Unassigned';

                                            $appointmentData = [
                                                'customer' => $customerName,
                                                'service' => $serviceNames ?: 'No service listed',
                                                'staff' => $staffName,
                                                'start' => \Carbon\Carbon::parse($appointment->start_time)->format('g:i A'),
                                                'end' => \Carbon\Carbon::parse($appointment->end_time)->format('g:i A'),
                                                'status' => ucfirst($appointment->status),
                                                'room' => $room->name,
                                                'date' => \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y'),
                                            ];

                                            $status = strtolower((string) $appointment->status);

                                            $mobileBorder = match($status) {
                                                'pending' => 'border-amber-200 dark:border-amber-800/60',
                                                'completed' => 'border-purple-200 dark:border-purple-800/60',
                                                default => 'border-brand-200 dark:border-brand-800/60',
                                            };

                                            $mobileBg = match($status) {
                                                'pending' => 'bg-amber-50/70 dark:bg-amber-900/10',
                                                'completed' => 'bg-purple-50/70 dark:bg-purple-900/10',
                                                default => 'bg-brand-50/70 dark:bg-brand-900/10',
                                            };

                                            $mobileBadge = match($status) {
                                                'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                                'completed' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                                default => 'bg-brand-100 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300',
                                            };
                                        @endphp


                                        <button
                                            type="button"
                                            @click="openAppointment(@js($appointmentData))"
                                            class="w-full rounded-lg border p-3 text-left transition hover:-translate-y-px hover:shadow-sm {{ $mobileBorder }} {{ $mobileBg }}"
                                        >

                                            <div class="flex items-start justify-between gap-3">

                                                <div class="min-w-0">

                                                    <div class="flex items-center gap-1.5">

                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 24 24"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            stroke-width="1.8"
                                                            class="h-3.5 w-3.5 shrink-0 text-gray-400"
                                                            aria-hidden="true"
                                                        >
                                                            <circle cx="12" cy="8" r="3"></circle>
                                                            <path d="M5 20a7 7 0 0 1 14 0"></path>
                                                        </svg>

                                                        <p class="truncate text-[11px] font-extrabold text-gray-900 dark:text-white">
                                                            {{ $customerName }}
                                                        </p>

                                                    </div>


                                                    <p class="mt-1 truncate text-[10px] text-gray-500 dark:text-gray-400">
                                                        {{ $serviceNames ?: 'No service listed' }}
                                                    </p>

                                                </div>


                                                <span class="shrink-0 rounded-full px-2 py-1 text-[8px] font-bold uppercase tracking-wide {{ $mobileBadge }}">
                                                    {{ ucfirst($appointment->status) }}
                                                </span>

                                            </div>


                                            <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[9px] font-semibold text-gray-500 dark:text-gray-400">

                                                <span class="inline-flex items-center gap-1">

                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="1.8"
                                                        class="h-3.5 w-3.5"
                                                        aria-hidden="true"
                                                    >
                                                        <circle cx="12" cy="12" r="9"></circle>
                                                        <path d="M12 7v5l3 2"></path>
                                                    </svg>

                                                    {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}
                                                    –
                                                    {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}

                                                </span>


                                                <span class="inline-flex min-w-0 items-center gap-1">

                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="1.8"
                                                        class="h-3.5 w-3.5"
                                                        aria-hidden="true"
                                                    >
                                                        <circle cx="12" cy="8" r="3"></circle>
                                                        <path d="M5 20a7 7 0 0 1 14 0"></path>
                                                    </svg>

                                                    <span class="truncate">
                                                        {{ $staffName }}
                                                    </span>

                                                </span>

                                            </div>

                                        </button>

                                    @endforeach

                                </div>

                            @endif

                        </div>

                    </div>

                @endforeach

            </div>

        @endif

    </section>



    {{-- =========================================================
         APPOINTMENT DETAILS MODAL
    ========================================================== --}}
    <div
        x-cloak
        x-show="showAppointment"
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center px-4 py-5"
        @keydown.escape.window="closeAppointment()"
    >

        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"
            @click="closeAppointment()"
        ></div>


        {{-- Modal --}}
        <div
            x-show="showAppointment"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="scale-95 opacity-0 translate-y-2"
            x-transition:enter-end="scale-100 opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="scale-100 opacity-100 translate-y-0"
            x-transition:leave-end="scale-95 opacity-0 translate-y-2"
            class="relative w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800"
        >


            {{-- Modal header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3.5 dark:border-slate-700">

                <div class="flex items-center gap-2.5">

                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-300">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="h-4 w-4"
                            aria-hidden="true"
                        >
                            <path d="M5 21V4a2 2 0 0 1 2-2h10v19"></path>
                            <path d="M5 21h14"></path>
                            <path d="M14 12h.01"></path>
                        </svg>

                    </div>


                    <div>

                        <p class="text-[9px] font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400">
                            Room booking
                        </p>

                        <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">
                            Appointment Details
                        </h3>

                    </div>

                </div>


                {{-- Close --}}
                <button
                    type="button"
                    @click="closeAppointment()"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-slate-700 dark:hover:text-white"
                    aria-label="Close"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="h-4 w-4"
                        aria-hidden="true"
                    >
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>

                </button>

            </div>


            {{-- Modal body --}}
            <div class="max-h-[70vh] overflow-y-auto p-4">

                <div class="space-y-2.5">


                    {{-- Customer --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 dark:border-slate-700 dark:bg-slate-900/30">

                        <div class="flex items-center gap-2.5">

                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm dark:bg-slate-800 dark:text-gray-400">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-4 w-4"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="8" r="3"></circle>
                                    <path d="M5 20a7 7 0 0 1 14 0"></path>
                                </svg>

                            </div>


                            <div class="min-w-0">

                                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                                    Customer
                                </p>

                                <p
                                    class="mt-0.5 truncate text-xs font-extrabold text-gray-900 dark:text-white"
                                    x-text="appointment.customer || 'Walk-in / Guest'"
                                ></p>

                            </div>

                        </div>

                    </div>


                    {{-- Date / Time --}}
                    <div class="grid grid-cols-2 gap-2.5">


                        <div class="rounded-xl border border-gray-100 p-3 dark:border-slate-700">

                            <div class="flex items-center gap-2">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-3.5 w-3.5 text-gray-400"
                                    aria-hidden="true"
                                >
                                    <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                                    <path d="M16 2v4"></path>
                                    <path d="M8 2v4"></path>
                                    <path d="M3 9h18"></path>
                                </svg>

                                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                                    Date
                                </p>

                            </div>


                            <p
                                class="mt-2 text-[11px] font-bold text-gray-800 dark:text-gray-200"
                                x-text="appointment.date || '—'"
                            ></p>

                        </div>


                        <div class="rounded-xl border border-gray-100 p-3 dark:border-slate-700">

                            <div class="flex items-center gap-2">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-3.5 w-3.5 text-gray-400"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 7v5l3 2"></path>
                                </svg>

                                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                                    Time
                                </p>

                            </div>


                            <p class="mt-2 text-[11px] font-bold text-gray-800 dark:text-gray-200">

                                <span x-text="appointment.start"></span>

                                <span class="px-0.5 text-gray-300">
                                    –
                                </span>

                                <span x-text="appointment.end"></span>

                            </p>

                        </div>

                    </div>


                    {{-- Room / Status --}}
                    <div class="grid grid-cols-2 gap-2.5">


                        <div class="rounded-xl border border-gray-100 p-3 dark:border-slate-700">

                            <div class="flex items-center gap-2">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-3.5 w-3.5 text-gray-400"
                                    aria-hidden="true"
                                >
                                    <path d="M5 21V4a2 2 0 0 1 2-2h10v19"></path>
                                    <path d="M5 21h14"></path>
                                </svg>

                                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                                    Room
                                </p>

                            </div>


                            <p
                                class="mt-2 text-[11px] font-bold text-gray-800 dark:text-gray-200"
                                x-text="appointment.room || '—'"
                            ></p>

                        </div>


                        <div class="rounded-xl border border-gray-100 p-3 dark:border-slate-700">

                            <div class="flex items-center gap-2">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-3.5 w-3.5 text-gray-400"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="m8 12 2.5 2.5L16 9"></path>
                                </svg>

                                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                                    Status
                                </p>

                            </div>


                            <span
                                class="mt-1.5 inline-flex rounded-full bg-gray-100 px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-gray-600 dark:bg-slate-700 dark:text-gray-300"
                                x-text="appointment.status || '—'"
                            ></span>

                        </div>

                    </div>


                    {{-- Services --}}
                    <div class="rounded-xl border border-gray-100 p-3 dark:border-slate-700">

                        <div class="flex items-start gap-2.5">

                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-300">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-4 w-4"
                                    aria-hidden="true"
                                >
                                    <path d="M12 3c1.5 3 4.5 4 4.5 7a4.5 4.5 0 1 1-9 0c0-3 3-4 4.5-7Z"></path>
                                    <path d="M8 16c1 2 2.5 3 4 3s3-1 4-3"></path>
                                </svg>

                            </div>


                            <div class="min-w-0">

                                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                                    Services
                                </p>

                                <p
                                    class="mt-1 text-xs font-bold leading-5 text-gray-800 dark:text-gray-200"
                                    x-text="appointment.service || '—'"
                                ></p>

                            </div>

                        </div>

                    </div>


                    {{-- Staff --}}
                    <div class="rounded-xl border border-gray-100 p-3 dark:border-slate-700">

                        <div class="flex items-start gap-2.5">

                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-gray-300">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="h-4 w-4"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="8" r="3"></circle>
                                    <path d="M5 20a7 7 0 0 1 14 0"></path>
                                    <path d="m16.5 13.5 1 1 2-2"></path>
                                </svg>

                            </div>


                            <div class="min-w-0">

                                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                                    Assigned Staff
                                </p>

                                <p
                                    class="mt-1 text-xs font-bold text-gray-800 dark:text-gray-200"
                                    x-text="appointment.staff || 'Unassigned'"
                                ></p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Modal footer --}}
            <div class="border-t border-gray-100 bg-gray-50/70 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/30">

                <button
                    type="button"
                    @click="closeAppointment()"
                    class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-gray-800 dark:bg-slate-700 dark:hover:bg-slate-600"
                >
                    Close
                </button>

            </div>

        </div>

    </div>

</div>



{{-- =============================================================
     JAVASCRIPT
============================================================== --}}
@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>

<script>
    function roomTrackingPage() {
        return {

            selectedDate: @json($trackingDateCarbon->toDateString()),

            showAppointment: false,

            datePicker: null,

            appointment: {
                customer: '',
                service: '',
                staff: '',
                start: '',
                end: '',
                status: '',
                room: '',
                date: ''
            },


            init() {
                this.$nextTick(() => {
                    this.initDatePicker();
                });
            },


            initDatePicker() {

                const input = document.getElementById(
                    'room-tracking-date'
                );

                if (!input || typeof flatpickr === 'undefined') {
                    return;
                }


                this.datePicker = flatpickr(input, {

                    /*
                     * The input itself displays:
                     *
                     * September 11, 2026
                     *
                     * instead of using altInput.
                     */
                    dateFormat: 'F j, Y',

                    defaultDate: this.selectedDate,

                    allowInput: false,

                    disableMobile: true,

                    monthSelectorType: 'static',


                    onChange: (selectedDates) => {

                        if (!selectedDates.length) {
                            return;
                        }


                        const date = selectedDates[0];

                        const year = date.getFullYear();

                        const month = String(
                            date.getMonth() + 1
                        ).padStart(2, '0');

                        const day = String(
                            date.getDate()
                        ).padStart(2, '0');


                        this.selectedDate =
                            `${year}-${month}-${day}`;


                        this.reloadDate();

                    }

                });

            },


            changeDate(days) {

                const current = new Date(
                    this.selectedDate + 'T00:00:00'
                );


                current.setDate(
                    current.getDate() + Number(days)
                );


                this.selectedDate =
                    current.getFullYear() +
                    '-' +
                    String(
                        current.getMonth() + 1
                    ).padStart(2, '0') +
                    '-' +
                    String(
                        current.getDate()
                    ).padStart(2, '0');


                this.reloadDate();

            },


            goToday() {

                /*
                 * Use local browser date rather than UTC.
                 */
                const today = new Date();

                this.selectedDate =
                    today.getFullYear() +
                    '-' +
                    String(
                        today.getMonth() + 1
                    ).padStart(2, '0') +
                    '-' +
                    String(
                        today.getDate()
                    ).padStart(2, '0');


                this.reloadDate();

            },


            reloadDate() {

                const url = new URL(
                    window.location.href
                );


                url.searchParams.set(
                    'date',
                    this.selectedDate
                );


                window.location.href =
                    url.toString();

            },


            openAppointment(data) {

                this.appointment = {

                    customer:
                        data?.customer
                        || 'Walk-in / Guest',

                    service:
                        data?.service
                        || '—',

                    staff:
                        data?.staff
                        || 'Unassigned',

                    start:
                        data?.start
                        || '—',

                    end:
                        data?.end
                        || '—',

                    status:
                        data?.status
                        || '—',

                    room:
                        data?.room
                        || '—',

                    date:
                        data?.date
                        || '—'

                };


                this.showAppointment = true;

            },


            closeAppointment() {

                this.showAppointment = false;

            }

        };
    }
</script>



{{-- =============================================================
     ROOM TRACKING / FLATPICKR STYLING
============================================================== --}}
<style>

    /*
    |--------------------------------------------------------------------------
    | Date picker input
    |--------------------------------------------------------------------------
    */

    .room-tracking-date-input {

        width: 210px !important;

        min-width: 210px !important;

        max-width: 210px !important;

        box-sizing: border-box !important;

        white-space: nowrap !important;

        overflow: visible !important;

        text-overflow: clip !important;

    }


    /*
    |--------------------------------------------------------------------------
    | Flatpickr calendar
    |--------------------------------------------------------------------------
    */

    .flatpickr-calendar {

        border-radius: 12px !important;

        border: 1px solid #e5e7eb !important;

        box-shadow:
            0 15px 40px rgba(15, 23, 42, 0.16) !important;

        font-family:
            Inter,
            ui-sans-serif,
            system-ui,
            sans-serif !important;

    }


    .flatpickr-months {

        border-radius:
            12px 12px 0 0 !important;

    }


    .flatpickr-month {

        height: 42px !important;

    }


    .flatpickr-current-month {

        padding-top: 8px !important;

    }


    .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-current-month input.cur-year {

        font-size: 13px !important;

        font-weight: 700 !important;

    }


    .flatpickr-weekdays {

        border-bottom:
            1px solid #f1f5f9;

    }


    .flatpickr-weekday {

        font-size: 10px !important;

        font-weight: 700 !important;

    }


    .flatpickr-day {

        border-radius: 8px !important;

        font-size: 11px !important;

    }


    .flatpickr-day.selected,
    .flatpickr-day.selected:hover {

        background: #0f766e !important;

        border-color: #0f766e !important;

    }


    .flatpickr-day.today {

        border-color: #14b8a6 !important;

    }


    /*
    |--------------------------------------------------------------------------
    | Dark mode
    |--------------------------------------------------------------------------
    */

    .dark .flatpickr-calendar {

        background: #1e293b !important;

        border-color: #334155 !important;

        color: #e2e8f0 !important;

    }


    .dark .flatpickr-months,
    .dark .flatpickr-month,
    .dark .flatpickr-weekdays,
    .dark .flatpickr-weekday,
    .dark .flatpickr-current-month {

        background: #1e293b !important;

        color: #e2e8f0 !important;

    }


    .dark .flatpickr-days {

        background: #1e293b !important;

    }


    .dark .flatpickr-day {

        color: #cbd5e1 !important;

    }


    .dark .flatpickr-day:hover {

        background: #334155 !important;

        border-color: #334155 !important;

    }


    .dark .flatpickr-day.today {

        border-color: #2dd4bf !important;

    }


    /*
    |--------------------------------------------------------------------------
    | Small screens
    |--------------------------------------------------------------------------
    */

    @media (max-width: 640px) {

        .room-tracking-date-input {

            width: 180px !important;

            min-width: 180px !important;

            max-width: 180px !important;

        }

    }

</style>

@endpush

@endsection