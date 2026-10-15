
@extends('layouts.receptionist')

@section('title', 'Active Sessions')

@push('styles')
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
    $calendarEvents = $appointments->map(function ($appointment) {
        $customerName = $appointment->customer?->nickname
            ?: $appointment->customer?->full_name
            ?: 'Walk-in Customer';

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
                'staff' => $appointment->staff?->full_name ?? 'Unassigned',
                'room' => $appointment->room?->name ?? 'No room',
            ],
        ];
    })->values();

    $todayCount = $appointments->filter(
        fn($appointment) => $appointment->appointment_date->isToday()
    )->count();

    $upcomingCount = $appointments->filter(
        fn($appointment) => !$appointment->appointment_date->isToday()
    )->count();
@endphp

<div
    x-data="activeSessionsPage()"
    x-init="init()"
    class="space-y-5"
>
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
                        There are no confirmed appointments scheduled for today or upcoming dates.
                    </p>
                </div>
            @else
                @foreach($appointments as $appointment)
                    @include('receptionist._session_card', ['appointment' => $appointment])
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
                                These confirmed appointments have passed their scheduled end time but require manual completion or no-show resolution.
                            </p>
                        </div>
                    </div>
                </div>

                @foreach($pastUnresolvedAppointments as $appointment)
                    @include('receptionist._session_card', ['appointment' => $appointment, 'isPastUnresolved' => true])
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

            <form :action="extraUrl" method="POST">
                @csrf

                <div class="space-y-4 p-5">

                    <div>

                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Service
                        </label>

                        <select
                            name="custom_service_id"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >
                            <option value="">Select a service</option>

                            @foreach($catalogServices as $service)

                                <option value="{{ $service->id }}">
                                    {{ $service->name }} — ₱{{ number_format((float) $service->price, 2) }}
                                </option>

                            @endforeach

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
                        class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white hover:bg-teal-700"
                    >
                        Add Service
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

            <form :action="completeUrl" method="POST">
                @csrf

                <input
                    type="hidden"
                    name="payment_type"
                    :value="fullyPaid ? 'full' : 'completion'"
                >

                <div class="space-y-4 p-5">

                    <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800">

                        <div class="flex items-center justify-between">

                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                Balance due
                            </span>

                            <span
                                class="text-lg font-bold"
                                :class="fullyPaid
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-amber-600 dark:text-amber-400'"
                                x-text="fullyPaid
                                    ? 'Fully Paid'
                                    : '₱' + Number(balance).toLocaleString('en-PH', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    })"
                            ></span>

                        </div>

                    </div>

                    <div>

                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Payment Method
                        </label>

                        <select
                            name="payment_method"
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
                        class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white hover:bg-teal-700"
                    >
                        <span x-text="fullyPaid ? 'Complete Appointment' : 'Complete & Collect'"></span>
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
                        Select another available staff member.
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

            <form :action="reassignUrl" method="POST">
                @csrf

                <div class="p-5">

                    <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                        Staff
                    </label>

                    <select
                        name="staff_id"
                        x-model="reassignStaffId"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    >
                        <option value="">Select staff</option>

                        @foreach($allStaff as $staff)

                            <option value="{{ $staff->id }}">
                                {{ $staff->full_name }}
                            </option>

                        @endforeach

                    </select>

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
                        class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white hover:bg-teal-700"
                    >
                        Reassign Staff
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

        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl dark:bg-slate-900">

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

            <form :action="rescheduleUrl" method="POST">
                @csrf

                <div class="space-y-4 p-5">

                    <div>

                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Date
                        </label>

                        <input
                            type="date"
                            name="appointment_date"
                            x-model="rescheduleDate"
                            :min="today"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >

                    </div>

                    <div>

                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Start Time
                        </label>

                        <input
                            type="time"
                            name="start_time"
                            x-model="rescheduleTime"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >

                    </div>

                    <div>

                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Room
                        </label>

                        <select
                            name="room_id"
                            x-model="rescheduleRoom"
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
                            Room availability is checked when the appointment is rescheduled.
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
                        class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white hover:bg-teal-700"
                    >
                        Reschedule
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

            <form :action="noShowUrl" method="POST">
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
                                        name="action"
                                        value="forfeit"
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
                                        name="action"
                                        value="refund"
                                        class="peer sr-only"
                                    >

                                    <div class="rounded-lg border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-700 peer-checked:border-teal-500 peer-checked:ring-2 peer-checked:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        Refund payment
                                    </div>

                                </label>

                            </div>

                        </div>

                    </template>

                    <template x-if="!noShowHasPayment">

                        <input
                            type="hidden"
                            name="action"
                            value="forfeit"
                        >

                    </template>

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
                        :disabled="noShowReason === 'custom' && !noShowCustomText.trim()"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Mark No-show
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>
@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.js"></script>

<script src="https://unpkg.com/lucide@latest"></script>

<script>
    function activeSessionsPage() {
        return {
            showCalendar: false,
            activeTab: 'active', // Added

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

            noShowReason: 'Customer did not arrive',
            noShowCustomText: '',

            fullyPaid: false,
            balance: 0,

            reassignStaffId: '',

            rescheduleDate: '',
            rescheduleTime: '',
            rescheduleRoom: '',

            noShowHasPayment: false,
            noShowCustomer: '',

            today: @js(now()->format('Y-m-d')),
            events: @js($calendarEvents),

            init() {
                this.$nextTick(() => {
                    this.refreshIcons();
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

            openExtra(id) {
                this.extraUrl = @js(
                    route('receptionist.add-extra', ['appointment' => '__ID__'])
                ).replace('__ID__', id);

                this.modal = 'extra';

                this.refreshIcons();
            },

            openComplete(id, url, fullyPaid, balance) {
                this.completeUrl = url;
                this.fullyPaid = fullyPaid;
                this.balance = Number(balance) || 0;
                this.modal = 'complete';

                this.refreshIcons();
            },

            openReassign(id, staffId, url) {
                this.reassignUrl = url;
                this.reassignStaffId = staffId ? String(staffId) : '';
                this.modal = 'reassign';

                this.refreshIcons();
            },

            openReschedule(id, date, time, roomId, url) {
                this.rescheduleUrl = url;
                this.rescheduleDate = date;
                this.rescheduleTime = time;
                this.rescheduleRoom = roomId ? String(roomId) : '';
                this.modal = 'reschedule';

                this.refreshIcons();
            },

            openNoShow(id, url, hasPayment, customer) {
                this.noShowUrl = url;
                this.noShowHasPayment = Boolean(hasPayment);
                this.noShowCustomer = customer || 'Customer';
                this.noShowReason = 'Customer did not arrive';
                this.noShowCustomText = '';
                this.modal = 'no-show';

                this.refreshIcons();
            },

            closeModal() {
                this.modal = null;
            }
        };
    }
</script>

@endpush
