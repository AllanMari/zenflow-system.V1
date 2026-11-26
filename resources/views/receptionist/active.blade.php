
@extends('layouts.receptionist')

@section('title', 'Active Sessions')

@push('styles')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    [x-cloak] {
        display: none !important;
    }

    .active-session-scroll::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .active-session-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .active-session-scroll::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, .35);
        border-radius: 999px;
    }

    .modal-backdrop {
        background: rgba(15, 23, 42, .72);
        backdrop-filter: blur(4px);
    }

    .session-card {
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    .session-card:hover {
        transform: translateY(-1px);
    }

    .dark .session-card:hover {
        box-shadow: 0 12px 30px rgba(0, 0, 0, .18);
    }

    .active-calendar {
        min-width: 0;
    }

    .active-calendar .fc {
        --fc-border-color: rgb(226 232 240);
        --fc-page-bg-color: transparent;
        --fc-neutral-bg-color: rgb(248 250 252);
        --fc-list-event-hover-bg-color: rgb(241 245 249);
        --fc-today-bg-color: rgba(20, 184, 166, .07);
        --fc-page-text-color: rgb(15 23 42);
        --fc-neutral-text-color: rgb(100 116 139);
        --fc-small-font-size: .75rem;
        font-family: Inter, system-ui, sans-serif;
    }

    .dark .active-calendar .fc {
        --fc-border-color: rgb(51 65 85);
        --fc-page-bg-color: transparent;
        --fc-neutral-bg-color: rgb(15 23 42);
        --fc-list-event-hover-bg-color: rgb(30 41 59);
        --fc-today-bg-color: rgba(20, 184, 166, .09);
        --fc-page-text-color: rgb(226 232 240);
        --fc-neutral-text-color: rgb(148 163 184);
    }

    .active-calendar .fc-theme-standard td,
    .active-calendar .fc-theme-standard th {
        border-color: var(--fc-border-color);
    }

    .active-calendar .fc-scrollgrid {
        border-radius: 14px;
        overflow: hidden;
        border-color: var(--fc-border-color);
    }

    .active-calendar .fc-col-header-cell {
        background: rgb(248 250 252);
    }

    .dark .active-calendar .fc-col-header-cell {
        background: rgb(15 23 42);
    }

    .active-calendar .fc-col-header-cell-cushion {
        display: block;
        padding: 12px 6px;
        color: rgb(71 85 105);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .dark .active-calendar .fc-col-header-cell-cushion {
        color: rgb(148 163 184);
    }

    .active-calendar .fc-daygrid-day-number {
        padding: 9px 10px;
        color: rgb(71 85 105);
        font-size: 12px;
        font-weight: 700;
    }

    .dark .active-calendar .fc-daygrid-day-number {
        color: rgb(203 213 225);
    }

    .active-calendar .fc-day-today {
        background: rgba(20, 184, 166, .06) !important;
    }

    .active-calendar .fc-day-today .fc-daygrid-day-number {
        color: rgb(13 148 136);
    }

    .dark .active-calendar .fc-day-today .fc-daygrid-day-number {
        color: rgb(45 212 191);
    }

    .active-calendar .fc-daygrid-day-frame {
        min-height: 108px;
    }

    .active-calendar .fc-event {
        border: 0 !important;
        border-radius: 7px !important;
        padding: 3px 5px !important;
        margin: 2px 4px !important;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(15, 118, 110, .14);
    }

    .active-calendar .fc-event:hover {
        filter: brightness(.96);
        transform: translateY(-1px);
    }

    .active-calendar .fc-event-main {
        padding: 0 !important;
    }

    .zen-calendar-event {
        display: flex;
        align-items: center;
        gap: 5px;
        min-width: 0;
        line-height: 1.2;
    }

    .zen-calendar-event-icon {
        width: 13px;
        height: 13px;
        min-width: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .zen-calendar-event-icon svg {
        width: 13px !important;
        height: 13px !important;
        stroke-width: 2;
    }

    .zen-calendar-event-label {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 11px;
        font-weight: 700;
    }

    .active-calendar .fc-timegrid-slot {
        height: 44px;
    }

    .active-calendar .fc-timegrid-slot-label-cushion {
        color: rgb(100 116 139);
        font-size: 10px;
        font-weight: 600;
    }

    .dark .active-calendar .fc-timegrid-slot-label-cushion {
        color: rgb(148 163 184);
    }

    .active-calendar .fc-timegrid-axis-cushion {
        font-size: 10px;
    }

    .active-calendar .fc-timegrid-event {
        border-radius: 8px !important;
        padding: 4px !important;
    }

    .active-calendar .fc-list {
        border-color: var(--fc-border-color);
        border-radius: 14px;
        overflow: hidden;
    }

    .active-calendar .fc-list-day-cushion {
        background: rgb(248 250 252);
    }

    .dark .active-calendar .fc-list-day-cushion {
        background: rgb(15 23 42);
    }

    .active-calendar .fc-list-event:hover td {
        background: var(--fc-list-event-hover-bg-color);
    }

    .active-calendar .fc-list-event-dot {
        border-color: rgb(13 148 136) !important;
    }

    .active-calendar .fc-header-toolbar {
        display: none !important;
    }

    .zen-calendar-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    .zen-calendar-toolbar-left,
    .zen-calendar-toolbar-right {
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .zen-calendar-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        border: 1px solid rgb(226 232 240);
        background: white;
        color: rgb(71 85 105);
        transition: all .18s ease;
    }

    .zen-calendar-icon-btn:hover {
        background: rgb(248 250 252);
        border-color: rgb(203 213 225);
        color: rgb(15 118 110);
    }

    .zen-calendar-icon-btn svg {
        width: 17px;
        height: 17px;
    }

    .dark .zen-calendar-icon-btn {
        background: rgb(15 23 42);
        border-color: rgb(51 65 85);
        color: rgb(203 213 225);
    }

    .dark .zen-calendar-icon-btn:hover {
        background: rgb(30 41 59);
        border-color: rgb(71 85 105);
        color: rgb(45 212 191);
    }

    .zen-calendar-today {
        height: 38px;
        padding: 0 13px;
        border-radius: 10px;
        border: 1px solid rgb(226 232 240);
        background: white;
        color: rgb(51 65 85);
        font-size: 12px;
        font-weight: 700;
        transition: all .18s ease;
    }

    .zen-calendar-today:hover {
        background: rgb(248 250 252);
        border-color: rgb(203 213 225);
    }

    .dark .zen-calendar-today {
        background: rgb(15 23 42);
        border-color: rgb(51 65 85);
        color: rgb(226 232 240);
    }

    .dark .zen-calendar-today:hover {
        background: rgb(30 41 59);
    }

    .zen-calendar-title {
        min-width: 150px;
        padding: 0 8px;
        text-align: center;
        color: rgb(15 23 42);
        font-size: 15px;
        font-weight: 800;
        letter-spacing: -.01em;
    }

    .dark .zen-calendar-title {
        color: rgb(248 250 252);
    }

    .zen-calendar-views {
        display: inline-flex;
        align-items: center;
        padding: 3px;
        border-radius: 11px;
        background: rgb(241 245 249);
        border: 1px solid rgb(226 232 240);
    }

    .dark .zen-calendar-views {
        background: rgb(30 41 59);
        border-color: rgb(51 65 85);
    }

    .zen-calendar-view-btn {
        height: 30px;
        padding: 0 10px;
        border-radius: 8px;
        border: 0;
        background: transparent;
        color: rgb(100 116 139);
        font-size: 11px;
        font-weight: 700;
        transition: all .18s ease;
    }

    .zen-calendar-view-btn:hover {
        color: rgb(15 118 110);
    }

    .zen-calendar-view-btn.active {
        background: white;
        color: rgb(13 148 136);
        box-shadow: 0 1px 4px rgba(15, 23, 42, .10);
    }

    .dark .zen-calendar-view-btn {
        color: rgb(148 163 184);
    }

    .dark .zen-calendar-view-btn.active {
        background: rgb(51 65 85);
        color: rgb(45 212 191);
    }

    @media (max-width: 640px) {
        .zen-calendar-toolbar {
            align-items: stretch;
        }

        .zen-calendar-toolbar-left,
        .zen-calendar-toolbar-right {
            width: 100%;
            justify-content: center;
        }

        .zen-calendar-title {
            min-width: 0;
            flex: 1;
            font-size: 14px;
        }

        .zen-calendar-view-btn {
            flex: 1;
            padding: 0 8px;
        }

        .active-calendar .fc-daygrid-day-frame {
            min-height: 82px;
        }

        .active-calendar .fc-col-header-cell-cushion {
            font-size: 9px;
            padding: 9px 2px;
        }

        .active-calendar .fc-daygrid-day-number {
            font-size: 10px;
            padding: 6px;
        }
    }
</style>
@endpush

@section('content')
@php
    $calendarEvents = $appointments->merge($confirmedUpcoming)->map(function ($appointment) {
        $customerName = $appointment->client_name;

        return [
            'id' => (string) $appointment->id,
            'title' => $customerName,
            'start' => $appointment->appointment_date->format('Y-m-d') . 'T' . $appointment->start_time,
            'end' => $appointment->appointment_date->format('Y-m-d') . 'T' . $appointment->end_time,
            'backgroundColor' => '#0d9488',
            'borderColor' => '#0d9488',
            'textColor' => '#ffffff',
            'extendedProps' => [
                'appointmentId' => $appointment->id,
                'customer' => $customerName,
                'staff' => ($appointment->assignedStaff && $appointment->assignedStaff->isNotEmpty())
                    ? $appointment->assignedStaff->pluck('full_name')->join(', ')
                    : ($appointment->staff?->full_name ?? 'Unassigned'),
                'room' => $appointment->room?->name ?? 'No room',
            ],
        ];
    })->values();

    $todayCount = $appointments->count();
    $upcomingCount = $confirmedUpcoming->count();
@endphp

<div
    x-data="activeSessionsPage()"
    x-init="init()"
    class="space-y-5"
>
    {{-- Persistent attendance follow-ups — refreshed by the shared polling script --}}
    <section id="attendanceFollowupPanel" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <i data-lucide="list-checks" class="h-4 w-4 text-teal-600 dark:text-teal-400"></i>
                <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Attendance Follow-ups</h2>
                <span id="attendanceFollowupCount" class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">0</span>
            </div>
            <span class="text-[11px] text-slate-400 dark:text-slate-500">Outstanding only · updates automatically</span>
        </div>
        <div id="attendanceFollowupList" class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3"></div>
    </section>

    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-2">

            <button
                type="button"
                @click="openCalendar()"
                :class="showCalendar
                    ? 'bg-teal-600 text-white border-teal-600 shadow-sm'
                    : 'bg-white text-slate-700 border-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700'"
                class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition"
            >
                <i data-lucide="calendar-days" class="h-4 w-4 shrink-0"></i>
                <span>Calendar</span>
            </button>

            <button
                type="button"
                @click="activeTab = 'active'; closeCalendar()"
                :class="!showCalendar && activeTab === 'active'
                    ? 'bg-teal-600 text-white border-teal-600 shadow-sm'
                    : 'bg-white text-slate-700 border-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700'"
                class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition"
            >
                <i data-lucide="list" class="h-4 w-4 shrink-0"></i>
                <span>Active Appointments ({{ $appointments->count() }})</span>
            </button>

            <button
                type="button"
                @click="activeTab = 'confirmed'; closeCalendar()"
                :class="!showCalendar && activeTab === 'confirmed'
                    ? 'bg-sky-600 text-white border-sky-600 shadow-sm'
                    : 'bg-white text-slate-700 border-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700'"
                class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition"
            >
                <i data-lucide="calendar-check-2" class="h-4 w-4 shrink-0"></i>
                <span>Confirmed Bookings ({{ $confirmedUpcoming->count() }})</span>
            </button>

            <button
                type="button"
                @click="activeTab = 'unresolved'; closeCalendar()"
                :class="!showCalendar && activeTab === 'unresolved'
                    ? 'bg-amber-600 text-white border-amber-600 shadow-sm'
                    : 'bg-white text-slate-700 border-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700'"
                class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition"
            >
                <i data-lucide="alert-circle" class="h-4 w-4 shrink-0"></i>
                <span>Past Unresolved ({{ $pastUnresolvedAppointments->count() }})</span>
            </button>

        </div>

        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-teal-500"></span>
                {{ $todayCount }} today
            </span>

            <span class="text-slate-300 dark:text-slate-600">•</span>

            <span>
                {{ $upcomingCount }} upcoming
            </span>
        </div>
    </div>

    <section
        x-show="showCalendar"
        x-cloak
        x-transition
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
    >
        <div class="border-b border-slate-200 bg-gradient-to-r from-teal-50/60 to-sky-50/40 px-5 py-4 dark:border-slate-800 dark:from-teal-950/20 dark:to-slate-900">

            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400">
                        <i data-lucide="calendar-check-2" class="h-5 w-5"></i>
                    </div>

                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                            Appointment Calendar
                        </h2>

                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            Confirmed spa sessions
                        </p>
                    </div>

                </div>

                <div class="hidden items-center gap-3 text-xs text-slate-500 dark:text-slate-400 sm:flex">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-sm bg-teal-600"></span>
                        Confirmed
                    </span>
                </div>
            </div>
        </div>

        <div class="p-4 md:p-5">

            <div class="zen-calendar-toolbar">

                <div class="zen-calendar-toolbar-left">

                    <button
                        type="button"
                        class="zen-calendar-icon-btn"
                        title="Previous"
                        @click="calendarPrev()"
                    >
                        <i data-lucide="chevron-left"></i>
                    </button>

                    <button
                        type="button"
                        class="zen-calendar-icon-btn"
                        title="Next"
                        @click="calendarNext()"
                    >
                        <i data-lucide="chevron-right"></i>
                    </button>

                    <button
                        type="button"
                        class="zen-calendar-today"
                        @click="calendarToday()"
                    >
                        Today
                    </button>

                </div>

                <div class="zen-calendar-title" x-text="calendarTitle">
                    Calendar
                </div>

                <div class="zen-calendar-toolbar-right">

                    <div class="zen-calendar-views">

                        <button
                            type="button"
                            class="zen-calendar-view-btn"
                            :class="{ 'active': currentView === 'dayGridMonth' }"
                            @click="changeCalendarView('dayGridMonth')"
                        >
                            Month
                        </button>

                        <button
                            type="button"
                            class="zen-calendar-view-btn"
                            :class="{ 'active': currentView === 'timeGridWeek' }"
                            @click="changeCalendarView('timeGridWeek')"
                        >
                            Week
                        </button>

                        <button
                            type="button"
                            class="zen-calendar-view-btn"
                            :class="{ 'active': currentView === 'listWeek' }"
                            @click="changeCalendarView('listWeek')"
                        >
                            List
                        </button>

                    </div>

                </div>
            </div>

            <div
                x-ref="calendar"
                id="activeSessionsCalendar"
                class="active-calendar"
            ></div>

        </div>
    </section>

    <section
        x-show="!showCalendar"
        x-cloak
        x-transition
        class="space-y-4"
    >
        <div x-show="activeTab === 'active'" x-cloak class="space-y-4">
            @if($appointments->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                        <i data-lucide="calendar-x-2" class="h-6 w-6 text-slate-400"></i>
                    </div>
                    <h3 class="mt-4 text-sm font-bold text-slate-900 dark:text-white">
                        No active sessions
                    </h3>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">
                        There are no confirmed appointments scheduled for today.
                    </p>
                </div>
            @else
                @foreach($appointments as $appointment)
                    @php
                        $startsAt = \Carbon\Carbon::parse(
                            $appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->start_time,
                            'Asia/Manila'
                        );
                        // Contact controls appear only once the session is near.
                        $actionsVisible = $now->gte($startsAt->copy()->subMinutes(15));
                    @endphp
                    @include('receptionist._session_card', [
                        'appointment' => $appointment,
                        'attendance' => $attendanceStates[$appointment->id] ?? null,
                        'attendanceActionsVisible' => $actionsVisible,
                        'sessionContext' => 'active',
                    ])
                @endforeach
            @endif
        </div>

        <div x-show="activeTab === 'confirmed'" x-cloak class="space-y-4">
            @if($confirmedUpcoming->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sky-50 dark:bg-sky-950/40">
                        <i data-lucide="calendar-check-2" class="h-6 w-6 text-sky-500"></i>
                    </div>
                    <h3 class="mt-4 text-sm font-bold text-slate-900 dark:text-white">
                        No future confirmed bookings
                    </h3>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">
                        Approved bookings scheduled after today will appear here. Attendance confirmation is tracked separately.
                    </p>
                </div>
            @else
                <div class="rounded-xl border border-sky-200 bg-sky-50/50 p-4 dark:border-sky-900/50 dark:bg-sky-950/20">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300">
                            <i data-lucide="calendar-check-2" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-sky-900 dark:text-sky-200">
                                Approved, awaiting their scheduled date
                            </h3>
                            <p class="text-xs text-sky-700 dark:text-sky-400">
                                These confirmed bookings are scheduled after today. Attendance confirmation is a separate state.
                            </p>
                        </div>
                    </div>
                </div>

                @foreach($confirmedUpcoming as $appointment)
                    @include('receptionist._session_card', [
                        'appointment' => $appointment,
                        'attendance' => $attendanceStates[$appointment->id] ?? null,
                        'attendanceActionsVisible' => false,
                        'sessionContext' => 'confirmed',
                    ])
                @endforeach
            @endif
        </div>

        <div x-show="activeTab === 'unresolved'" x-cloak class="space-y-4">
            @if($pastUnresolvedAppointments->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-purple-50 dark:bg-purple-950/40">
                        <i data-lucide="check-circle-2" class="h-6 w-6 text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <h3 class="mt-4 text-sm font-bold text-slate-900 dark:text-white">
                        No past unresolved appointments
                    </h3>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">
                        All past confirmed appointments have been successfully resolved.
                    </p>
                </div>
            @else
                <div class="rounded-xl border border-purple-200 bg-purple-50/50 p-4 dark:border-purple-900/50 dark:bg-purple-950/20">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                            <i data-lucide="alert-circle" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-purple-900 dark:text-purple-200">
                                Unresolved Past Bookings
                            </h3>
                            <p class="text-xs text-purple-700 dark:text-purple-400">
                                Confirmed follow-ups that are 15+ minutes past their start or past their scheduled end. Attendance is still unresolved — no automatic No Show or cancellation.
                            </p>
                        </div>
                    </div>
                </div>

                @foreach($pastUnresolvedAppointments as $appointment)
                    @php
                        $startsAt = \Carbon\Carbon::parse(
                            $appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->start_time,
                            'Asia/Manila'
                        );
                        $endsAt = \Carbon\Carbon::parse(
                            $appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->end_time,
                            'Asia/Manila'
                        );
                        $pastUnresolvedReason = $now->gte($endsAt) ? 'time_passed' : 'late';
                        $elapsedLateMinutes = abs((int) $now->diffInMinutes($startsAt));
                    @endphp
                    @include('receptionist._session_card', [
                        'appointment' => $appointment,
                        'isPastUnresolved' => true,
                        'attendance' => $attendanceStates[$appointment->id] ?? null,
                        'attendanceActionsVisible' => true,
                        'pastUnresolvedReason' => $pastUnresolvedReason,
                        'elapsedLateMinutes' => $elapsedLateMinutes,
                        'sessionContext' => 'past_unresolved',
                    ])
                @endforeach
            @endif
        </div>
    </section>

    {{-- Add Extra Service Modal --}}
    <div
        x-show="modal === 'extra'"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        @keydown.escape.window="closeModal()"
    >
        <div class="absolute inset-0 modal-backdrop" @click="closeModal()"></div>

        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl dark:bg-slate-900">

            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">

                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Add Extra Service
                    </h3>

                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        Add another catalog service to this appointment.
                    </p>
                </div>

                <button
                    type="button"
                    @click="closeModal()"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>

            </div>

            <form :action="extraUrl" method="POST" @submit.prevent="if(!submitting){ submitting=true; $el.submit(); }">
                @csrf

                <div class="space-y-4 p-5">

                    <div>

                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Service
                        </label>

                        <select
                            name="custom_service_id"
                            x-model="extraServiceId"
                            @change="onExtraServiceChange()"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >
                            <option value="">Select a service</option>

                            <template x-for="service in extraServices" :key="service.id">
                                <option :value="service.id" x-text="service.name + ' — ₱' + Number(service.price).toFixed(2) + ' • ' + (service.duration_minutes || 0) + ' min'"></option>
                            </template>

                        </select>

                        <p
                            x-show="extraService()"
                            class="mt-2 text-xs text-slate-500 dark:text-slate-400"
                        >
                            <span x-text="extraService() ? extraService().duration_minutes + ' min treatment' : ''"></span>
                            •
                            <span x-text="'Requires ' + extraUpdatedReqCount + ' therapist(s)'"></span>
                            <span x-show="extraService() && extraService().requires_room"> • Room required</span>
                        </p>

                    </div>

                    {{-- Package choice options: choose ONE alternative, never add both. --}}
                    <template x-if="extraService() && extraService().choice_groups && extraService().choice_groups.length">
                        <div class="space-y-3 rounded-lg border border-teal-200 bg-teal-50/60 p-3 dark:border-teal-900/50 dark:bg-teal-900/10">
                            <template x-for="group in extraService().choice_groups" :key="group.id">
                                <div>
                                    <p class="mb-1.5 text-xs font-bold text-teal-800 dark:text-teal-300">
                                        <span x-text="group.label"></span>
                                        <span x-show="group.is_required" class="text-red-500">*</span>
                                    </p>
                                    <p
                                        x-show="extraChoices[group.id]"
                                        class="mb-1.5 text-[11px] text-teal-700 dark:text-teal-400"
                                    >
                                        Currently selected:
                                        <span x-text="(group.options.find(o => String(o.service_id) === String(extraChoices[group.id])) || {}).name || extraChoices[group.id]"></span>
                                    </p>
                                    <div class="space-y-1">
                                        <template x-for="option in group.options" :key="option.service_id">
                                            <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-200">
                                                <input
                                                    type="radio"
                                                    :name="'extra_choice_' + group.id"
                                                    :value="option.service_id"
                                                    x-model="extraChoices[group.id]"
                                                    class="h-3.5 w-3.5 border-slate-300 text-teal-600 focus:ring-teal-500"
                                                >
                                                <span x-text="option.name"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <template x-for="groupId in Object.keys(extraChoices)" :key="groupId">
                                <input type="hidden" :name="'package_choices[' + groupId + ']'" :value="extraChoices[groupId]">
                            </template>
                        </div>
                    </template>

                    {{-- Additional therapists (existing assignments are preserved). --}}
                    <div x-show="extraUpdatedReqCount > extraStaffIds.length" class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">
                                Additional therapists required
                            </p>
                            <span class="rounded bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 dark:bg-teal-900/30 dark:text-teal-300"
                                  x-text="(extraStaffIds.length + extraAdditional.length) + ' of ' + extraUpdatedReqCount + ' selected'"></span>
                        </div>
                        <p class="mb-2 text-[11px] text-slate-500 dark:text-slate-400">
                            Current therapists are kept. Choose the additional therapist(s) for the extended treatment.
                        </p>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="member in allStaffList" :key="member.id">
                                <button
                                    type="button"
                                    x-show="!extraStaffIds.includes(Number(member.id))"
                                    @click="toggleExtraTherapist(member.id)"
                                    :disabled="(extraTherapistStatus(member.id) && extraTherapistStatus(member.id).status !== 'available')
                                        || (extraUpdatedReqCount - extraStaffIds.length <= 0)"
                                    :class="extraAdditional.includes(Number(member.id))
                                        ? 'border-teal-500 bg-teal-50 text-teal-700 dark:bg-teal-900/20 dark:text-teal-300'
                                        : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300'"
                                    class="rounded-lg border px-2 py-2 text-left text-[11px] font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <span x-text="member.name"></span>
                                    <span class="block text-[10px] font-normal text-slate-400" x-text="extraTherapistStatus(member.id) && extraTherapistStatus(member.id).status !== 'available'
                                        ? extraTherapistStatus(member.id).label
                                        : 'Available'"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="extraLoadingStatuses" class="mt-2 text-[10px] text-slate-400">Checking therapist availability...</p>
                    </div>

                    <template x-for="id in extraSelectedAll" :key="id">
                        <input type="hidden" name="therapist_ids[]" :value="id">
                    </template>

                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">

                    <button
                        type="button"
                        @click="closeModal()"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="submitting || !extraServiceId
                            || (extraService() && (extraService().choice_groups || []).some(g => g.is_required && !extraChoices[g.id]))
                            || extraSelectedAll.length < extraUpdatedReqCount"
                        class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="!submitting">Add Service</span>
                        <span x-show="submitting">Adding...</span>
                    </button>

                </div>

            </form>

        </div>
    </div>

    {{-- Complete Modal --}}
    <div
        x-show="modal === 'complete'"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        @keydown.escape.window="closeModal()"
    >
        <div class="absolute inset-0 modal-backdrop" @click="closeModal()"></div>

        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl dark:bg-slate-900">

            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">

                <div class="flex items-center gap-3">

                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-teal-50 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400">
                        <i data-lucide="check-circle-2" class="h-5 w-5"></i>
                    </div>

                    <div>

                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Complete Appointment
                        </h3>

                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            Record the remaining payment if needed.
                        </p>

                    </div>

                </div>

            </div>

            <form :action="completeUrl" method="POST" @submit.prevent="if(!submitting && !isExcessDeposit() && (discountType === '' || discountVerified)){ submitting=true; $el.submit(); }">
                @csrf

                <input type="hidden" name="complete_appointment_id" :value="completeAppointmentId">
                <input
                    type="hidden"
                    name="payment_type"
                    :value="computedBalance() <= 0 ? 'full' : 'completion'"
                >

                <div class="space-y-4 p-5">

                    @if($errors->any() && old('complete_appointment_id'))
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700 dark:border-rose-800/50 dark:bg-rose-950/30 dark:text-rose-300">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if(session('error') && old('complete_appointment_id'))
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700 dark:border-rose-800/50 dark:bg-rose-950/30 dark:text-rose-300">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div>
                        <label for="complete_discount_type" class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Discount (Optional)
                        </label>
                        <select
                            id="complete_discount_type"
                            name="discount_type"
                            x-model="discountType"
                            @change="if(discountType === '') discountVerified = false"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >
                            <option value="">No Discount</option>
                            <option value="senior_citizen">Senior Citizen Discount — 20%</option>
                            <option value="pwd">PWD Discount — 20%</option>
                        </select>
                    </div>

                    <div x-show="discountType !== ''" x-cloak class="rounded-lg border border-amber-200 bg-amber-50/70 p-3 dark:border-amber-800/40 dark:bg-amber-950/20">
                        <label class="flex items-start gap-2 text-xs text-amber-900 dark:text-amber-200 cursor-pointer">
                            <input
                                type="checkbox"
                                name="discount_verified"
                                value="1"
                                x-model="discountVerified"
                                class="mt-0.5 h-4 w-4 rounded border-amber-300 text-teal-600 focus:ring-teal-500"
                            >
                            <span>I have verified the customer's eligibility for this discount.</span>
                        </label>
                    </div>

                    <div class="rounded-lg bg-slate-50 p-3.5 space-y-1.5 text-xs dark:bg-slate-800">
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                            <span>Original Amount</span>
                            <span class="font-semibold" x-text="'₱' + Number(completeOriginalTotal).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                        </div>
                        <div x-show="discountType !== ''" class="flex items-center justify-between text-amber-700 dark:text-amber-300">
                            <span x-text="discountType === 'senior_citizen' ? 'Senior Citizen Discount — 20%' : 'PWD Discount — 20%'"></span>
                            <span class="font-semibold" x-text="'-₱' + discountAmount().toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-200 pt-1.5 font-bold text-slate-900 dark:border-slate-700 dark:text-white">
                            <span>Final Payable Amount</span>
                            <span x-text="'₱' + finalDiscountedTotal().toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                        </div>
                        <div x-show="completeTotalPaid > 0" class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                            <span>Previously Paid</span>
                            <span x-text="'-₱' + Number(completeTotalPaid).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-200 pt-1.5 dark:border-slate-700">
                            <span class="font-semibold text-slate-600 dark:text-slate-300">Balance due</span>
                            <span
                                class="text-base font-bold"
                                :class="isExcessDeposit()
                                    ? 'text-rose-600 dark:text-rose-400'
                                    : (computedBalance() <= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400')"
                                x-text="isExcessDeposit()
                                    ? 'Excess: ₱' + excessDeposit().toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                                    : (computedBalance() <= 0
                                        ? 'Fully Paid'
                                        : '₱' + computedBalance().toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))"
                            ></span>
                        </div>
                    </div>

                    <div x-show="isExcessDeposit()" x-cloak class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700 dark:border-rose-800/50 dark:bg-rose-950/30 dark:text-rose-300">
                        <p class="font-bold">Completion Blocked — Excess Deposit</p>
                        <p class="mt-0.5">
                            Payments already collected exceed the discounted payable total by
                            <strong x-text="'₱' + excessDeposit().toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></strong>.
                            Reconcile the excess deposit before completing this appointment.
                        </p>
                    </div>

                    <div>

                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Payment Method
                        </label>

                        <select
                            name="payment_method"
                            x-model="completePaymentMethod"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="gcash">GCash</option>
                            <option value="paymaya">PayMaya</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>

                    </div>

                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">

                    <button
                        type="button"
                        @click="closeModal()"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="submitting || isExcessDeposit() || (discountType !== '' && !discountVerified)"
                        class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="!submitting" x-text="computedBalance() <= 0 ? 'Complete Appointment' : 'Complete &amp; Collect'"></span>
                        <span x-show="submitting">Processing...</span>
                    </button>

                </div>

            </form>

        </div>
    </div>

    {{-- Reassign Staff Modal --}}
    <div
        x-show="modal === 'reassign'"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        @keydown.escape.window="closeModal()"
    >
        <div class="absolute inset-0 modal-backdrop" @click="closeModal()"></div>

        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl dark:bg-slate-900">

            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">

                <div>

                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Reassign Staff
                    </h3>

                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        Select the full team required by the services (including the five-minute preparation window).
                    </p>

                </div>

                <button
                    type="button"
                    @click="closeModal()"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>

            </div>

            <form :action="reassignUrl" method="POST" @submit.prevent="if(!submitting){ submitting=true; $el.submit(); }">
                @csrf

                {{-- Hidden input for primary therapist if using multi-therapist grid --}}
                <template x-if="reassignReqCount > 1">
                    <div>
                        <input type="hidden" name="staff_id" :value="reassignSelected[0] || ''">
                        <template x-for="aId in reassignSelected.slice(1)" :key="aId">
                            <input type="hidden" name="assisting_staff_ids[]" :value="aId">
                        </template>
                    </div>
                </template>

                <div class="p-5">

                    {{-- Multi-Therapist Selection Grid (Responsive) --}}
                    <div x-show="reassignReqCount > 1" class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Assigned Therapists
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                :class="reassignSelected.length === reassignReqCount
                                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800'
                                    : 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="reassignSelected.length === reassignReqCount ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse'"></span>
                                <span x-text="reassignSelected.length + ' of ' + reassignReqCount + ' Selected'"></span>
                            </span>
                        </div>

                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Select <strong>Therapist 1</strong> and <strong>Therapist 2</strong> for this session (both have equal status). Click to assign.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-56 overflow-y-auto pr-1">
                            @foreach($allStaff as $staff)
                                <button
                                    type="button"
                                    @click="toggleReassignTherapist({{ $staff->id }})"
                                    :disabled="therapistStatusFor({{ $staff->id }}) && therapistStatusFor({{ $staff->id }}).status !== 'available'"
                                    class="flex items-center justify-between p-2.5 rounded-xl border text-left transition text-xs font-medium cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                    :class="reassignSelected.includes({{ $staff->id }})
                                        ? 'border-teal-500 bg-teal-50/70 text-teal-900 dark:border-teal-500/70 dark:bg-teal-900/30 dark:text-teal-200 shadow-sm'
                                        : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-300'"
                                >
                                    <div class="min-w-0 pr-2">
                                        <div class="font-bold truncate text-slate-900 dark:text-white">{{ $staff->full_name }}</div>
                                        <div class="text-[10px] text-slate-400 truncate">{{ '@' . $staff->username }}</div>
                                        <template x-if="therapistStatusFor({{ $staff->id }}) && therapistStatusFor({{ $staff->id }}).status !== 'available'">
                                            <div class="mt-1 text-[10px] font-semibold text-amber-600 dark:text-amber-400" x-text="therapistStatusText({{ $staff->id }})"></div>
                                        </template>
                                    </div>
                                    <template x-if="reassignSelected.includes({{ $staff->id }})">
                                        <span class="shrink-0 px-2 py-0.5 rounded text-[10px] font-bold bg-teal-600 text-white shadow-xs">
                                            <span x-text="'Therapist ' + (reassignSelected.indexOf({{ $staff->id }}) + 1)"></span>
                                        </span>
                                    </template>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Single-Therapist Selection (Fallback for 1-therapist services) --}}
                    <div x-show="reassignReqCount <= 1">
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Staff
                        </label>

                        <select
                            name="staff_id"
                            x-model="reassignStaffId"
                            :required="reassignReqCount <= 1"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >
                            <option value="">Select staff</option>

                            @foreach($allStaff as $staff)
                                <option value="{{ $staff->id }}"
                                    :disabled="therapistStatusFor({{ $staff->id }}) && therapistStatusFor({{ $staff->id }}).status !== 'available'"
                                    x-text="therapistAvailabilityLabel({{ $staff->id }}, @js($staff->full_name))">
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        Staff availability will be validated when submitted.
                    </p>

                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">

                    <button
                        type="button"
                        @click="closeModal()"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="submitting || (reassignReqCount > 1 && reassignSelected.length < reassignReqCount) || (reassignReqCount <= 1 && !reassignStaffId)"
                        class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="!submitting">Reassign Staff</span>
                        <span x-show="submitting">Reassigning...</span>
                    </button>

                </div>

            </form>

        </div>
    </div>

    {{-- Treatment Notes Modal --}}
    <div
        x-show="modal === 'treatmentNotes'"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
        @keydown.escape.window="closeModal()"
    >
        <div class="absolute inset-0 modal-backdrop" @click="closeModal()"></div>

        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400">
                        <i data-lucide="file-text" class="h-4 w-4"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                            Session Treatment Notes
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="treatmentNotesClientName ? 'Client: ' + treatmentNotesClientName : 'Session Notes'"></p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="closeModal()"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <form :action="treatmentNotesUrl" method="POST" @submit.prevent="if(!submitting){ submitting=true; $el.submit(); }">
                @csrf
                <div class="p-5 space-y-3">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                        Treatment & Clinical Observations
                    </label>
                    <textarea
                        name="treatment_notes"
                        x-model="treatmentNotesContent"
                        rows="5"
                        maxlength="2000"
                        placeholder="e.g. Focus on lower back and neck. Client requested medium-firm pressure. Avoided peppermint oil due to sensitive skin."
                        class="w-full rounded-xl border border-slate-300 bg-white p-3.5 text-sm text-slate-800 placeholder-slate-400 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder-slate-500"
                    ></textarea>
                    <div class="flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500">
                        <span>Visible to therapists & receptionist on active sessions</span>
                        <span x-text="(treatmentNotesContent ? treatmentNotesContent.length : 0) + ' / 2000'"></span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-3.5 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                    <button
                        type="button"
                        @click="closeModal()"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-teal-700 disabled:opacity-50 transition"
                    >
                        <i data-lucide="check" class="h-3.5 w-3.5"></i>
                        <span x-show="!submitting">Save Treatment Notes</span>
                        <span x-show="submitting">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reschedule Modal --}}
    <div
        x-show="modal === 'reschedule'"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto p-4"
        @keydown.escape.window="closeModal()"
    >
        <div class="absolute inset-0 modal-backdrop" @click="closeModal()"></div>

        <div class="relative max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl dark:bg-slate-900">

            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">

                <div>

                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Reschedule Appointment
                    </h3>

                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        The current staff must still be available at the new time.
                    </p>

                </div>

                <button
                    type="button"
                    @click="closeModal()"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>

            </div>

            <form :action="rescheduleUrl" method="POST" @submit.prevent="if(!submitting){ submitting=true; $el.submit(); }">
                @csrf

                <div class="space-y-4 p-5">

                    <div>

                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Date
                        </label>

                        <input
                            id="rescheduleDate"
                            type="text"
                            name="appointment_date"
                            x-model="rescheduleDate"
                            placeholder="Select date"
                            autocomplete="off"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >

                    </div>

                    <div>

                        <input type="hidden" name="start_time" :value="rescheduleTime" :required="!rescheduleTime ? true : null">

                        <div class="mb-2 flex items-center justify-between gap-2">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Available times
                            </label>
                            <span x-show="rescheduleLoading" class="inline-flex items-center gap-1 text-[11px] text-slate-400">
                                <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                                </svg>
                                Checking
                            </span>
                        </div>

                        {{-- Selected date/time review --}}
                        <div class="mb-2 flex flex-wrap items-center gap-x-2 gap-y-1 rounded-lg bg-teal-50 px-2.5 py-1.5 text-[11px] font-semibold text-teal-700 dark:bg-teal-950/30 dark:text-teal-300">
                            <i data-lucide="calendar-check-2" class="h-3.5 w-3.5"></i>
                            <span>Selected</span>
                            <span x-text="rescheduleDate || '—'"></span>
                            <span class="text-teal-400">•</span>
                            <span x-text="rescheduleTime || 'Choose a time below'"></span>
                        </div>

                        {{-- Compact, internally scrollable time picker --}}
                        <div
                            x-show="!rescheduleLoading && rescheduleGroups.length > 0"
                            class="active-session-scroll max-h-56 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/60 p-2.5 dark:border-slate-700 dark:bg-slate-800/40"
                        >
                            <template x-for="group in rescheduleGroups" :key="group.key">
                                <div class="mb-2 last:mb-0">
                                    <div class="mb-1.5 flex items-center gap-1.5">
                                        <span x-show="group.key === 'morning'"><i data-lucide="sun" class="h-3.5 w-3.5 text-amber-500"></i></span>
                                        <span x-show="group.key === 'afternoon'"><i data-lucide="sun-dim" class="h-3.5 w-3.5 text-orange-500"></i></span>
                                        <span x-show="group.key === 'evening'"><i data-lucide="moon" class="h-3.5 w-3.5 text-indigo-500"></i></span>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400" x-text="group.label"></span>
                                        <span class="text-[10px] text-slate-400" x-text="'(' + group.times.filter(t => t.available).length + ')'"></span>
                                    </div>

                                    <div class="grid grid-cols-3 gap-1.5 sm:grid-cols-4">
                                        <template x-for="slot in group.times" :key="slot.time">
                                            <button
                                                type="button"
                                                :disabled="!slot.available"
                                                @click="if (slot.available) { rescheduleTime = slot.time; rescheduleError = '' }"
                                                :class="!slot.available
                                                    ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 line-through dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-600'
                                                    : (rescheduleTime === slot.time
                                                        ? 'border-teal-500 bg-teal-600 text-white shadow-sm'
                                                        : 'border-slate-200 bg-white text-slate-700 hover:border-teal-300 hover:bg-teal-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-teal-700')"
                                                class="rounded-md border px-1.5 py-1.5 text-center text-[11px] font-bold leading-tight transition"
                                            >
                                                <span x-text="slot.time"></span>
                                                <span x-show="slot.end_time" class="mt-0.5 block text-[9px] font-normal opacity-70" x-text="slot.end_time"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="rescheduleLoading" class="rounded-lg border border-dashed border-slate-300 px-3 py-4 text-center text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Checking available times...
                        </div>

                        <div
                            x-show="!rescheduleLoading && rescheduleError"
                            class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300"
                            x-text="rescheduleError"
                        ></div>

                        <div
                            x-show="!rescheduleLoading && !rescheduleError && rescheduleTimes.length === 0"
                            class="mt-2 rounded-lg border border-dashed border-slate-300 px-3 py-4 text-center text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400"
                        >
                            No valid time with the current therapist team and room on this date.
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-slate-400">
                            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-teal-600"></span>Selected</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm border border-slate-300 bg-white dark:border-slate-600 dark:bg-slate-800"></span>Available</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-slate-200 dark:bg-slate-700"></span>Unavailable</span>
                        </div>

                        <p class="mt-1.5 text-[11px] text-slate-400">
                            Treatment: <span x-text="rescheduleDuration"></span> min + 5 min preparation. The assigned therapists and room are preserved.
                        </p>

                    </div>

                    <div>

                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Room
                        </label>

                        <select
                            name="room_id"
                            x-model="rescheduleRoom"
                            @change="rescheduleRoomChanged()"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >
                            <option value="">No room</option>

                            @foreach($allRooms as $room)

                                <option value="{{ $room->id }}">
                                    {{ $room->name }}
                                </option>

                            @endforeach

                        </select>

                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            The current room stays assigned unless a different one is chosen here. Compatibility and availability are rechecked on save.
                        </p>

                    </div>

                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">

                    <button
                        type="button"
                        @click="closeModal()"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="submitting || !rescheduleTime || rescheduleLoading"
                        class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="!submitting">Reschedule</span>
                        <span x-show="submitting">Rescheduling...</span>
                    </button>

                </div>

            </form>

        </div>
    </div>

    {{-- No-show Modal --}}
    <div
        x-show="modal === 'no-show'"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        @keydown.escape.window="closeModal()"
    >
        <div class="absolute inset-0 modal-backdrop" @click="closeModal()"></div>

        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl dark:bg-slate-900">

            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">

                <div class="flex items-center gap-3">

                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                        <i data-lucide="user-x" class="h-5 w-5"></i>
                    </div>

                    <div>

                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            Mark as No-show
                        </h3>

                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            <span x-text="noShowCustomer"></span>
                        </p>

                    </div>

                </div>

            </div>

            <form :action="noShowUrl" method="POST" @submit.prevent="if(!submitting){ submitting=true; $el.submit(); }">
                @csrf

                <div class="p-5">

                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        This will cancel the appointment and record it as a customer no-show.
                    </p>

                    <template x-if="noShowHasPayment">

                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/60 dark:bg-amber-950/20">

                            <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">
                                A payment exists for this appointment.
                            </p>

                            <div class="mt-3 grid gap-2 sm:grid-cols-2">

                                <label class="cursor-pointer">

                                    <input
                                        type="radio"
                                        name="no_show_action_radio"
                                        value="forfeit"
                                        x-model="noShowAction"
                                        checked
                                        class="peer sr-only"
                                    >

                                    <div class="rounded-lg border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-700 peer-checked:border-amber-500 peer-checked:ring-2 peer-checked:ring-amber-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        Forfeit payment
                                    </div>

                                </label>

                                <label class="cursor-pointer">

                                    <input
                                        type="radio"
                                        name="no_show_action_radio"
                                        value="refund"
                                        x-model="noShowAction"
                                        class="peer sr-only"
                                    >

                                    <div class="rounded-lg border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-700 peer-checked:border-teal-500 peer-checked:ring-2 peer-checked:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        Refund payment
                                    </div>

                                </label>

                            </div>

                        </div>

                    </template>

                    <input
                        type="hidden"
                        name="action"
                        :value="noShowAction"
                    >

                    <div class="mt-4 space-y-3">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Reason <span class="text-rose-500">*</span>
                        </label>

                        <div class="space-y-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                <input type="radio" name="no_show_reason_radio" value="Customer did not arrive" x-model="noShowReason" class="text-rose-600 focus:ring-rose-500">
                                <span>Customer did not arrive</span>
                            </label>

                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                <input type="radio" name="no_show_reason_radio" value="Customer could not be contacted" x-model="noShowReason" class="text-rose-600 focus:ring-rose-500">
                                <span>Customer could not be contacted</span>
                            </label>

                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                <input type="radio" name="no_show_reason_radio" value="Customer arrived too late to be served" x-model="noShowReason" class="text-rose-600 focus:ring-rose-500">
                                <span>Customer arrived too late to be served</span>
                            </label>

                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                <input type="radio" name="no_show_reason_radio" value="custom" x-model="noShowReason" class="text-rose-600 focus:ring-rose-500">
                                <span>Custom reason</span>
                            </label>
                        </div>

                        <!-- Hidden input submitting actual no_show_reason -->
                        <input type="hidden" name="no_show_reason" :value="noShowReason === 'custom' ? noShowCustomText : noShowReason">

                        <!-- Custom reason textarea -->
                        <div x-show="noShowReason === 'custom'" x-cloak class="mt-2">
                            <textarea
                                x-model="noShowCustomText"
                                rows="2"
                                placeholder="Enter custom reason..."
                                class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-800 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            ></textarea>
                        </div>
                    </div>

                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">

                    <button
                        type="button"
                        @click="closeModal()"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="(noShowReason === 'custom' && !noShowCustomText.trim()) || submitting"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="!submitting">Mark No-show</span>
                        <span x-show="submitting">Processing...</span>
                    </button>

                </div>

            </form>

        </div>
    </div>

    {{-- Attendance Contact Modal --}}
    <div
        x-show="modal === 'attendance'"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        @keydown.escape.window="closeModal()"
    >
        <div class="absolute inset-0 modal-backdrop" @click="closeModal()"></div>

        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl dark:bg-slate-900">

            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full"
                        :class="attendanceOutcome === 'confirm'
                            ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400'
                            : (attendanceOutcome === 'no_answer'
                                ? 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400'
                                : 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400')"
                    >
                        <i data-lucide="phone" class="h-5 w-5"></i>
                    </div>

                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white" x-text="attendanceTitle"></h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            <span x-text="attendanceCustomer"></span>
                            <template x-if="attendancePhone">
                                <span> · <span x-text="attendancePhone"></span></span>
                            </template>
                        </p>
                    </div>
                </div>
            </div>

            <form :action="attendanceUrl" method="POST" @submit.prevent="if(!submitting){ submitting=true; $el.submit(); }">
                @csrf

                <div class="space-y-3 p-5">
                    <p class="text-sm text-slate-600 dark:text-slate-300" x-text="attendanceDescription"></p>

                    <div>
                        <label for="attendance-note" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Note (optional)
                        </label>
                        <textarea
                            id="attendance-note"
                            name="note"
                            rows="3"
                            maxlength="500"
                            x-model="attendanceNote"
                            placeholder="e.g. Customer confirmed by phone."
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-800 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        ></textarea>
                    </div>

                    <p class="text-[11px] text-slate-400">
                        This records attendance only. It does not check the customer in, change the appointment status, or cancel the booking.
                    </p>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                    <button
                        type="button"
                        @click="closeModal()"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="submitting"
                        class="rounded-lg px-4 py-2 text-sm font-bold text-white disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="attendanceOutcome === 'confirm'
                            ? 'bg-emerald-600 hover:bg-emerald-700'
                            : (attendanceOutcome === 'no_answer'
                                ? 'bg-amber-600 hover:bg-amber-700'
                                : 'bg-rose-600 hover:bg-rose-700')"
                    >
                        <span x-show="!submitting" x-text="attendanceSubmitLabel"></span>
                        <span x-show="submitting">Saving...</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    {{-- Manual cancellation modal (confirmed or pending bookings) --}}
    <div
        x-show="modal === 'cancel'"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto p-4"
        @keydown.escape.window="closeModal()"
    >
        <div class="absolute inset-0 modal-backdrop" @click="closeModal()"></div>

        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                        <i data-lucide="circle-x" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Cancel Booking</h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            <span x-text="cancelCustomer"></span> · this records a cancellation, not a no-show.
                        </p>
                    </div>
                </div>
            </div>

            <form :action="cancelUrl" method="POST" @submit.prevent="if(!submitting){ submitting=true; $el.submit(); }">
                @csrf

                <div class="space-y-3 p-5">
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        Cancelling releases the room and therapist for this appointment. The booking remains in history with a cancelled status.
                    </p>

                    <div>
                        <label for="cancel-reason" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Cancellation reason
                        </label>
                        <select id="cancel-reason" name="reason" x-model="cancelReason" required
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                            <option value="staff_unavailable">Staff unavailable</option>
                            <option value="customer_cancelled">Customer cancelled</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div x-show="cancelReason === 'other'" x-cloak>
                        <label for="cancel-other-reason" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Specify reason
                        </label>
                        <textarea id="cancel-other-reason" name="other_reason" rows="3" maxlength="500"
                                  x-model="cancelOtherReason" :required="cancelReason === 'other'"
                                  placeholder="Explain why this booking is being cancelled."
                                  class="mt-2 w-full rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-800 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                    <button type="button" @click="closeModal()"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                        Keep booking
                    </button>
                    <button type="submit" :disabled="submitting"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!submitting">Cancel Booking</span>
                        <span x-show="submitting">Cancelling...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.js"></script>

<script src="https://unpkg.com/lucide@latest"></script>

<script>
    function activeSessionsPage() {
        return {
            showCalendar: false,
            activeTab: 'active', // Added

            submitting: false,

            modal: null,

            calendar: null,
            calendarInitialized: false,

            currentView: 'dayGridMonth',
            calendarTitle: 'Calendar',

            extraUrl: '',
            completeUrl: '',
            reassignUrl: '',
            rescheduleUrl: '',
            noShowUrl: '',

            cancelUrl: '',
            cancelCustomer: '',
            cancelReason: 'staff_unavailable',
            cancelOtherReason: '',

            noShowAction: 'forfeit',
            noShowReason: 'Customer did not arrive',
            noShowCustomText: '',

            fullyPaid: false,
            balance: 0,
            completeAppointmentId: null,
            completeOriginalTotal: 0,
            completeTotalPaid: 0,
            discountType: '',
            discountVerified: false,
            completePaymentMethod: 'cash',
            completableAppointments: @js(
                $appointments->merge($pastUnresolvedAppointments)->unique('id')->mapWithKeys(function ($appt) {
                    $paid = max(0, (float) $appt->payments->sum('amount'));
                    $svcSum = (float) $appt->services->sum(fn($s) => (float) ($s->pivot->custom_price ?? $s->pivot->price_at_booking ?? 0));
                    $orig = $svcSum > 0 ? round($svcSum, 2) : round((float) ($appt->original_total_price ?? $appt->total_price), 2);
                    $bal = max(0, (float) $appt->total_price - $paid);
                    return [
                        $appt->id => [
                            'url' => route('receptionist.complete', $appt),
                            'fullyPaid' => $bal <= 0,
                            'balance' => $bal,
                            'originalTotal' => $orig,
                            'totalPaid' => $paid,
                        ],
                    ];
                })
            ),

            reassignStaffId: '',
            reassignReqCount: 1,
            reassignSelected: [],
            reassignAppointmentId: null,
            reassignStatuses: [],
            reassignStatusSeq: 0,

            extraAppointmentId: null,
            extraServiceId: '',
            extraStaffIds: [],
            extraReqCount: 1,
            extraUpdatedReqCount: 1,
            extraAdditional: [],
            extraChoices: {},
            extraSavedChoices: [],
            extraStatuses: [],
            extraStatusSeq: 0,
            extraLoadingStatuses: false,
            extraDate: '',
            extraStartTime: '',
            extraDuration: 0,
            extraServices: @js($catalogServices->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'price' => (float) $s->price,
                'duration_minutes' => $s->duration_minutes,
                'therapists_required' => (int) $s->therapists_required,
                'requires_room' => (bool) $s->requires_room,
                'choice_groups' => $s->choiceGroups->map(fn($g) => [
                    'id' => $g->id,
                    'label' => $g->label,
                    'is_required' => (bool) $g->is_required,
                    'options' => $g->options->map(fn($o) => [
                        'service_id' => (int) $o->service_id,
                        'name' => $o->service ? $o->service->name : 'Selected Service',
                    ])->values(),
                ])->values(),
            ])->values()),
            allStaffList: @js($allStaff->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->full_name ?: trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
            ])->values()),

            treatmentNotesApptId: null,
            treatmentNotesContent: '',
            treatmentNotesUrl: '',
            treatmentNotesClientName: '',
            async loadReassignStatuses() {
                if (!this.reassignAppointmentId) {
                    this.reassignStatuses = [];
                    return;
                }

                const seq = ++this.reassignStatusSeq;

                try {
                    const response = await fetch(
                        `/receptionist/api/booking/therapist-statuses?appointment_id=${encodeURIComponent(this.reassignAppointmentId)}`,
                        { headers: { 'Accept': 'application/json' } }
                    );

                    if (!response.ok) {
                        if (seq === this.reassignStatusSeq) this.reassignStatuses = [];
                        return;
                    }

                    const data = await response.json();
                    if (seq === this.reassignStatusSeq) {
                        this.reassignStatuses = data.statuses || [];
                    }
                } catch (error) {
                    if (seq === this.reassignStatusSeq) this.reassignStatuses = [];
                }
            },

            therapistStatusFor(id) {
                return (this.reassignStatuses || []).find(
                    item => String(item.staff_id) === String(id)
                ) || null;
            },

            therapistStatusText(id) {
                const status = this.therapistStatusFor(id);
                if (!status || status.status === 'available') {
                    return '';
                }

                return status.label + (status.reason ? ' — ' + status.reason : '');
            },

            therapistAvailabilityLabel(id, name) {
                const status = this.therapistStatusFor(id);

                if (!status || status.status === 'available') {
                    return name;
                }

                let label = name + ' — ' + status.label;
                if (status.reason) {
                    label += ' (' + status.reason + ')';
                }

                return label;
            },

            toggleReassignTherapist(id) {
                id = Number(id);
                const idx = this.reassignSelected.indexOf(id);
                if (idx !== -1) {
                    this.reassignSelected.splice(idx, 1);
                } else {
                    if (this.reassignSelected.length < this.reassignReqCount) {
                        this.reassignSelected.push(id);
                    } else if (this.reassignReqCount === 1) {
                        this.reassignSelected = [id];
                    }
                }
                this.reassignStaffId = this.reassignSelected[0] ? String(this.reassignSelected[0]) : '';
            },

            staffName(id) {
                const found = (this.allStaffList || []).find(s => String(s.id) === String(id));
                return found ? found.name : ('Therapist ' + id);
            },

            extraService() {
                return (this.extraServices || []).find(s => String(s.id) === String(this.extraServiceId)) || null;
            },

            onExtraServiceChange() {
                const service = this.extraService();
                this.extraUpdatedReqCount = Math.max(
                    Number(this.extraReqCount) || 1,
                    service ? Number(service.therapists_required || 1) : 1
                );
                this.extraDuration = service ? (Number(service.duration_minutes) || 0) : 0;

                // Restore only choices already saved for THIS service's choice
                // groups. Never auto-select an alternative when none was saved.
                this.extraChoices = {};
                if (service) {
                    (service.choice_groups || []).forEach(group => {
                        const saved = (this.extraSavedChoices || []).find(
                            item => String(item.choice_group_id) === String(group.id)
                        );
                        if (saved && saved.selected_service_id) {
                            this.extraChoices[group.id] = saved.selected_service_id;
                        }
                    });
                }

                this.extraAdditional = [];
                this.extraStatuses = [];
                this.loadExtraTherapistStatuses();
            },

            toggleExtraTherapist(id) {
                id = Number(id);
                const idx = this.extraAdditional.indexOf(id);
                if (idx !== -1) {
                    this.extraAdditional.splice(idx, 1);
                    return;
                }
                const slots = Math.max(0, this.extraUpdatedReqCount - this.extraStaffIds.length);
                if (slots > 0 && this.extraAdditional.length < slots) {
                    this.extraAdditional.push(id);
                }
            },

            get extraSelectedAll() {
                return [...this.extraStaffIds.map(Number), ...this.extraAdditional.map(Number)];
            },

            async loadExtraTherapistStatuses() {
                const seq = ++this.extraStatusSeq;
                this.extraLoadingStatuses = true;
                this.extraStatuses = [];

                const service = this.extraService();
                if (!service || this.extraUpdatedReqCount <= this.extraStaffIds.length) {
                    this.extraLoadingStatuses = false;
                    return;
                }

                const [h, m] = String(this.extraStartTime || '00:00').split(':').map(Number);
                const total = (this.extraDuration || 0) + (h * 60) + m;
                const endTime = String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');

                const params = new URLSearchParams({
                    date: this.extraDate,
                    start_time: this.extraStartTime,
                    duration: String(this.extraDuration || 0),
                    exclude_appointment_id: String(this.extraAppointmentId),
                });
                params.append('services[]', String(service.id));

                try {
                    const response = await fetch(`/receptionist/api/booking/therapist-statuses?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!response.ok) throw new Error('status request failed');
                    const data = await response.json();
                    if (seq === this.extraStatusSeq) {
                        this.extraStatuses = data.statuses || [];
                    }
                } catch (error) {
                    if (seq === this.extraStatusSeq) this.extraStatuses = [];
                } finally {
                    if (seq === this.extraStatusSeq) this.extraLoadingStatuses = false;
                }
            },

            extraTherapistStatus(id) {
                return (this.extraStatuses || []).find(s => String(s.staff_id) === String(id)) || null;
            },

            rescheduleDateChanged(dateStr) {
                this.rescheduleDate = dateStr;
                this.rescheduleTime = '';
                this.rescheduleError = '';
                this.loadRescheduleAvailability(false, true);
            },

            rescheduleRoomChanged() {
                this.rescheduleTime = '';
                this.loadRescheduleAvailability(false, true);
            },

            applyRescheduleDates(dates) {
                if (!this.rescheduleFlatpickr || !this.rescheduleMonth) return;

                const [year, month] = this.rescheduleMonth.split('-').map(Number);
                const lastDay = new Date(year, month, 0).getDate();
                const enabled = new Set(Array.isArray(dates) ? dates : []);
                const disabled = [];
                for (let day = 1; day <= lastDay; day++) {
                    const date = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    if (!enabled.has(date)) disabled.push(date);
                }
                this.rescheduleFlatpickr.set('disable', disabled);
            },

            /**
             * Group server-provided available times into compact display buckets.
             * This is presentation only: every submitted start_time still comes
             * from a slot returned by the availability endpoint and is validated
             * again by the server on save.
             */
            buildRescheduleGroups() {
                const available = Array.isArray(this.rescheduleTimes) ? this.rescheduleTimes : [];
                const toMinutes = (value) => {
                    const parts = String(value).split(':').map(Number);
                    return (parts[0] || 0) * 60 + (parts[1] || 0);
                };
                const format = (minutes) =>
                    String(Math.floor(minutes / 60)).padStart(2, '0') + ':' + String(minutes % 60).padStart(2, '0');

                const byTime = new Map(available.map((slot) => [slot.time, slot]));
                const minuteValues = available.map((slot) => toMinutes(slot.time));
                const grid = new Set(available.map((slot) => slot.time));

                if (minuteValues.length) {
                    const earliest = Math.min(...minuteValues);
                    const latest = Math.max(...minuteValues);
                    for (let minutes = Math.ceil(earliest / 30) * 30; minutes <= latest; minutes += 30) {
                        grid.add(format(minutes));
                    }
                }

                const groups = [
                    { key: 'morning', label: 'Morning', times: [] },
                    { key: 'afternoon', label: 'Afternoon', times: [] },
                    { key: 'evening', label: 'Evening', times: [] },
                ];

                grid.forEach((time) => {
                    const slot = byTime.get(time);
                    const hour = Number(String(time).slice(0, 2));
                    const group = hour < 12 ? groups[0] : (hour < 17 ? groups[1] : groups[2]);
                    group.times.push({
                        time: time,
                        end_time: slot ? (slot.end_time || '') : '',
                        available: Boolean(slot),
                    });
                });

                groups.forEach((group) => group.times.sort((a, b) => a.time.localeCompare(b.time)));
                this.rescheduleGroups = groups.filter((group) => group.times.length > 0);
            },

            async loadRescheduleAvailability(withMonth = false, withTimes = false) {
                if (!this.rescheduleAppointmentId) return;

                const seq = ++this.rescheduleRequestSeq;
                this.rescheduleLoading = true;
                this.rescheduleError = '';

                const params = new URLSearchParams();
                if (withMonth && this.rescheduleMonth) params.append('month', this.rescheduleMonth);
                if (withTimes && this.rescheduleDate) {
                    params.append('date', this.rescheduleDate);
                    if (this.rescheduleRoom) params.append('room_id', this.rescheduleRoom);
                }
                if (!withMonth && !withTimes) return;

                const url = this.rescheduleAvailabilityUrl.replace('__ID__', this.rescheduleAppointmentId);

                try {
                    const response = await fetch(`${url}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!response.ok) throw new Error('availability request failed');
                    const data = await response.json();
                    if (seq !== this.rescheduleRequestSeq) return;

                    if (withMonth) {
                        this.applyRescheduleDates(data.dates || []);
                    }

                    if (withTimes) {
                        this.rescheduleTimes = data.times || [];
                        this.rescheduleDuration = data.duration || this.rescheduleDuration;
                        const stillValid = this.rescheduleTimes.some(t => t.time === this.rescheduleTime);
                        if (this.rescheduleTime && !stillValid) {
                            this.rescheduleTime = '';
                            this.rescheduleError = data.message
                                ? data.message
                                : 'The previously selected time is no longer available. Please choose another time.';
                        } else if (!this.rescheduleTime && !this.rescheduleTimes.length && data.message) {
                            this.rescheduleError = data.message;
                        }

                        this.buildRescheduleGroups();
                    }
                } catch (error) {
                    if (seq === this.rescheduleRequestSeq) {
                        // Leave existing dates/time state untouched on failure:
                        // a failed request must never enable unavailable dates.
                        this.rescheduleError = 'Unable to load availability. Please try again.';
                    }
                } finally {
                    if (seq === this.rescheduleRequestSeq) this.rescheduleLoading = false;
                }
            },

            rescheduleDate: '',
            rescheduleTime: '',
            rescheduleRoom: '',
            rescheduleFlatpickr: null,
            rescheduleAppointmentId: null,
            rescheduleAvailabilityUrl: @js(route('receptionist.reschedule.availability', ['appointmentId' => '__ID__'])),
            rescheduleMonth: '',
            rescheduleDuration: 0,
            rescheduleStaffIds: [],
            rescheduleReqCount: 1,
            rescheduleTimes: [],
            rescheduleGroups: [],
            rescheduleLoading: false,
            rescheduleError: '',
            rescheduleRequestSeq: 0,

            noShowHasPayment: false,
            noShowCustomer: '',

            attendanceUrl: '',
            attendanceOutcome: 'confirm',
            attendanceCustomer: '',
            attendancePhone: '',
            attendanceNote: '',
            attendanceTitle: 'Confirm Attendance by Phone',
            attendanceDescription: '',
            attendanceSubmitLabel: 'Confirm Attendance',

            today: @js(now()->format('Y-m-d')),
            events: @js($calendarEvents),

            init() {
                this.$nextTick(() => {
                    this.refreshIcons();
                    const reopenId = @js(old('complete_appointment_id'));
                    if (reopenId && this.completableAppointments && this.completableAppointments[reopenId]) {
                        const target = this.completableAppointments[reopenId];
                        this.openComplete(
                            Number(reopenId),
                            target.url,
                            target.fullyPaid,
                            target.balance,
                            target.originalTotal,
                            target.totalPaid,
                            @js(old('discount_type', '')),
                            @js((bool) old('discount_verified')),
                            @js(old('payment_method', 'cash'))
                        );
                    }
                });
            },

            refreshIcons() {
                this.$nextTick(() => {
                    if (window.lucide && typeof window.lucide.createIcons === 'function') {
                        window.lucide.createIcons();
                    }
                });
            },

            openCalendar() {
                this.showCalendar = true;

                this.$nextTick(() => {
                    this.initCalendar();

                    if (this.calendar) {
                        this.calendar.updateSize();
                        this.updateCalendarState();
                    }

                    this.refreshIcons();
                });
            },

            closeCalendar() {
                this.showCalendar = false;
            },

            initCalendar() {
                if (this.calendarInitialized) {
                    return;
                }

                if (typeof FullCalendar === 'undefined') {
                    console.error('FullCalendar failed to load.');
                    return;
                }

                const element = this.$refs.calendar;

                if (!element) {
                    console.error('Calendar element was not found.');
                    return;
                }

                this.calendar = new FullCalendar.Calendar(element, {

                    initialView: 'dayGridMonth',

                    height: 'auto',

                    firstDay: 1,

                    events: this.events,

                    headerToolbar: false,

                    dayMaxEvents: 4,

                    navLinks: true,

                    nowIndicator: true,

                    editable: false,

                    selectable: false,

                    eventDisplay: 'block',

                    eventContent: (arg) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'zen-calendar-event';

                        const icon = document.createElement('span');
                        icon.className = 'zen-calendar-event-icon';

                        const iconElement = document.createElement('i');
                        iconElement.setAttribute('data-lucide', 'sparkles');

                        icon.appendChild(iconElement);

                        const label = document.createElement('span');
                        label.className = 'zen-calendar-event-label';
                        label.textContent = arg.event.title;

                        wrapper.appendChild(icon);
                        wrapper.appendChild(label);

                        return {
                            domNodes: [wrapper]
                        };
                    },

                    eventDidMount: (info) => {
                        const staff = info.event.extendedProps.staff || 'Unassigned';
                        const room = info.event.extendedProps.room || 'No room';

                        info.el.title =
                            info.event.title +
                            ' — ' +
                            staff +
                            ' — ' +
                            room;

                        this.refreshIcons();
                    },

                    datesSet: () => {
                        this.updateCalendarState();
                        this.refreshIcons();
                    },

                    viewDidMount: () => {
                        this.updateCalendarState();
                        this.refreshIcons();
                    },

                    eventClick: (info) => {
                        info.jsEvent.preventDefault();

                        const id = info.event.extendedProps.appointmentId;

                        const card = document.getElementById(
                            'appointment-' + id
                        );

                        if (!card) {
                            return;
                        }

                        this.showCalendar = false;

                        this.$nextTick(() => {

                            card.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });

                            card.classList.add(
                                'ring-2',
                                'ring-teal-500',
                                'ring-offset-2',
                                'dark:ring-offset-slate-950'
                            );

                            window.setTimeout(() => {

                                card.classList.remove(
                                    'ring-2',
                                    'ring-teal-500',
                                    'ring-offset-2',
                                    'dark:ring-offset-slate-950'
                                );

                            }, 1800);

                        });
                    }

                });

                this.calendar.render();

                this.calendarInitialized = true;

                this.updateCalendarState();

                this.refreshIcons();
            },

            updateCalendarState() {
                if (!this.calendar) {
                    return;
                }

                const view = this.calendar.view;

                this.currentView = view.type;

                this.calendarTitle = view.title;
            },

            calendarPrev() {
                if (!this.calendar) {
                    return;
                }

                this.calendar.prev();

                this.updateCalendarState();

                this.refreshIcons();
            },

            calendarNext() {
                if (!this.calendar) {
                    return;
                }

                this.calendar.next();

                this.updateCalendarState();

                this.refreshIcons();
            },

            calendarToday() {
                if (!this.calendar) {
                    return;
                }

                this.calendar.today();

                this.updateCalendarState();

                this.refreshIcons();
            },

            changeCalendarView(view) {
                if (!this.calendar) {
                    return;
                }

                this.calendar.changeView(view);

                this.currentView = view;

                this.updateCalendarState();

                this.$nextTick(() => {
                    this.calendar.updateSize();
                    this.refreshIcons();
                });
            },

            openExtra(id, staffIds = [], reqCount = 1, date = '', startTime = '', savedChoices = []) {
                this.extraUrl = @js(
                    route('receptionist.add-extra', ['appointment' => '__ID__'])
                ).replace('__ID__', id);

                this.extraAppointmentId = id;
                this.extraStaffIds = (Array.isArray(staffIds) ? staffIds : []).map(Number);
                this.extraReqCount = Number(reqCount) || 1;
                this.extraUpdatedReqCount = this.extraReqCount;
                this.extraServiceId = '';
                this.extraAdditional = [];
                this.extraChoices = {};
                this.extraSavedChoices = Array.isArray(savedChoices) ? savedChoices : [];
                this.extraStatuses = [];
                this.extraDate = date;
                this.extraStartTime = startTime;
                this.extraDuration = 0;
                this.modal = 'extra';

                this.refreshIcons();
            },

            discountAmount() {
                if (!this.discountType) return 0;
                return Math.round(Number(this.completeOriginalTotal) * 0.20 * 100) / 100;
            },

            finalDiscountedTotal() {
                return Math.max(0, Math.round((Number(this.completeOriginalTotal) - this.discountAmount()) * 100) / 100);
            },

            computedBalance() {
                return Math.max(0, Math.round((this.finalDiscountedTotal() - Number(this.completeTotalPaid)) * 100) / 100);
            },

            excessDeposit() {
                return Math.max(0, Math.round((Number(this.completeTotalPaid) - this.finalDiscountedTotal()) * 100) / 100);
            },

            isExcessDeposit() {
                return this.excessDeposit() > 0.009;
            },

            openComplete(id, url, fullyPaid, balance, originalTotal = null, totalPaid = 0, oldDiscountType = '', oldDiscountVerified = false, oldPaymentMethod = 'cash') {
                this.completeAppointmentId = id;
                this.completeUrl = url;
                this.fullyPaid = fullyPaid;
                this.balance = Number(balance) || 0;
                this.completeOriginalTotal = originalTotal !== null ? Number(originalTotal) : (Number(balance) + Number(totalPaid));
                this.completeTotalPaid = Number(totalPaid) || 0;
                this.discountType = oldDiscountType || '';
                this.discountVerified = Boolean(oldDiscountVerified);
                this.completePaymentMethod = oldPaymentMethod || 'cash';
                this.modal = 'complete';

                this.refreshIcons();
            },

            openReassign(id, staffId, url, reqCount = 1, assistingIds = []) {
                this.reassignUrl = url;
                this.reassignAppointmentId = id;
                this.reassignStatuses = [];
                this.reassignStaffId = staffId ? String(staffId) : '';
                this.reassignReqCount = Number(reqCount) || 1;
                this.reassignSelected = [];
                if (staffId) {
                    this.reassignSelected.push(Number(staffId));
                }
                if (Array.isArray(assistingIds)) {
                    assistingIds.forEach(aId => {
                        const num = Number(aId);
                        if (num && !this.reassignSelected.includes(num)) {
                            this.reassignSelected.push(num);
                        }
                    });
                }
                this.modal = 'reassign';

                this.loadReassignStatuses();
                this.refreshIcons();
            },

            openTreatmentNotes(id, notes, url, clientName) {
                this.treatmentNotesApptId = id;
                this.treatmentNotesContent = notes || '';
                this.treatmentNotesUrl = url;
                this.treatmentNotesClientName = clientName || 'Client';
                this.modal = 'treatmentNotes';

                this.refreshIcons();
            },

            openReschedule(id, date, time, roomId, url, duration = 60, staffIds = [], reqCount = 1) {
                this.rescheduleUrl = url;
                this.rescheduleAppointmentId = id;
                this.rescheduleDate = date;
                this.rescheduleTime = time;
                this.rescheduleRoom = roomId ? String(roomId) : '';
                this.rescheduleMonth = String(date || this.today).slice(0, 7);
                this.rescheduleDuration = Number(duration) || 60;
                this.rescheduleStaffIds = (Array.isArray(staffIds) ? staffIds : []).map(Number);
                this.rescheduleReqCount = Number(reqCount) || 1;
                this.rescheduleTimes = [];
                this.rescheduleGroups = [];
                this.rescheduleError = '';
                this.modal = 'reschedule';

                this.$nextTick(() => {
                    if (!this.rescheduleFlatpickr && window.flatpickr) {
                        this.rescheduleFlatpickr = flatpickr('#rescheduleDate', {
                            dateFormat: 'Y-m-d',
                            minDate: this.today,
                            defaultDate: this.rescheduleDate,
                            disableMobile: true,
                            onChange: (dates) => {
                                const selected = dates.length
                                    ? `${dates[0].getFullYear()}-${String(dates[0].getMonth() + 1).padStart(2, '0')}-${String(dates[0].getDate()).padStart(2, '0')}`
                                    : '';
                                this.rescheduleDateChanged(selected);
                            },
                            onMonthChange: (dates, monthIndex, instance) => {
                                // Flatpickr passes the *selected* dates first; the
                                // second argument is the newly visible month.
                                const visibleMonth = typeof monthIndex === 'number'
                                    ? monthIndex
                                    : instance.currentMonth;
                                const visibleYear = instance.currentYear;
                                this.rescheduleMonth =
                                    `${visibleYear}-${String(visibleMonth + 1).padStart(2, '0')}`;
                                this.loadRescheduleAvailability(true, false);
                            }
                        });
                    } else if (this.rescheduleFlatpickr) {
                        this.rescheduleFlatpickr.setDate(this.rescheduleDate, false);
                        this.rescheduleFlatpickr.jumpToDate(this.rescheduleDate, false);
                    }

                    // Load the currently visible month and the current date's times.
                    this.loadRescheduleAvailability(true, true);
                });

                this.refreshIcons();
            },

            openNoShow(id, url, hasPayment, customer) {
                this.noShowUrl = url;
                this.noShowAction = 'forfeit';
                this.noShowHasPayment = Boolean(hasPayment);
                this.noShowCustomer = customer || 'Customer';
                this.noShowReason = 'Customer did not arrive';
                this.noShowCustomText = '';
                this.modal = 'no-show';

                this.refreshIcons();
            },

            openCancel(id, url, customer) {
                this.cancelUrl = url;
                this.cancelCustomer = customer || 'Customer';
                this.cancelReason = 'staff_unavailable';
                this.cancelOtherReason = '';
                this.modal = 'cancel';

                this.refreshIcons();
            },

            openAttendance(id, outcome, url, customer, phone) {
                this.attendanceUrl = url;
                this.attendanceOutcome = outcome;
                this.attendanceCustomer = customer || 'Customer';
                this.attendancePhone = phone || '';
                this.attendanceNote = '';

                if (outcome === 'confirm') {
                    this.attendanceTitle = 'Confirm Attendance by Phone';
                    this.attendanceDescription = 'Confirm that the customer has acknowledged this appointment by phone. Attendance reminders and follow-ups will stop. The appointment status is not changed.';
                    this.attendanceSubmitLabel = 'Confirm Attendance';
                } else if (outcome === 'no_answer') {
                    this.attendanceTitle = 'Record No Answer';
                    this.attendanceDescription = 'Record that the customer did not answer the phone. Reminders continue and the appointment is not cancelled or marked no-show.';
                    this.attendanceSubmitLabel = 'Save No Answer';
                } else {
                    this.attendanceTitle = 'Record Unable to Reach';
                    this.attendanceDescription = 'Record that the customer could not be reached. Reminders continue and the appointment is not cancelled or marked no-show.';
                    this.attendanceSubmitLabel = 'Save Unable to Reach';
                }

                this.modal = 'attendance';

                this.refreshIcons();
            },

            closeModal() {
                this.modal = null;
                this.submitting = false;
                // Invalidate in-flight availability lookups so a response from
                // a closed modal cannot overwrite a newer selection.
                this.rescheduleRequestSeq++;
                this.extraStatusSeq++;
                this.reassignStatusSeq++;
                this.rescheduleLoading = false;
            }
        };
    }
</script>

@endpush
