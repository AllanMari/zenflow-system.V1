@extends(auth()->user()->roles->contains('name', 'admin') ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Daily Attendance')

@push('styles')
<style>
    [x-cloak] {
        display: none !important;
    }

    .report-modal-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .report-modal-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .report-modal-scroll::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.5);
        border-radius: 999px;
    }
</style>
@endpush

@section('content')

@php
    $isAdmin = $isAdmin ?? auth()->user()->roles->contains('name', 'admin');
@endphp

<div
    x-data="attendanceBoard({
        staff: @js($initialState),
        canMark: @js($canMark),
        urls: {
            checkin: @js(route('attendance.quick-checkin', ['staff' => '__ID__'])),
            checkout: @js(route('attendance.quick-checkout', ['staff' => '__ID__'])),
            correct: @js(route('attendance.correct', ['staff' => '__ID__'])),
        },
        reportUrl: @js(route('attendance.report-pdf')),
    })"
    x-cloak
    class="max-w-[85rem] px-4 pb-6 sm:px-6 lg:px-8 mx-auto"
>

    {{-- =========================================================
         HEADER
    ========================================================== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">

        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 7V3m8 4V3M4 9h16M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                </svg>

                {{ $today->format('l, F j, Y') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">

            {{-- Refresh --}}
            <button
                type="button"
                onclick="window.location.reload()"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 active:scale-[.98] dark:border-slate-700 dark:bg-slate-900 dark:text-gray-200 dark:hover:bg-slate-800"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M20 11a8.1 8.1 0 00-14.9-4M4 5v4h4M4 13a8.1 8.1 0 0014.9 4M20 19v-4h-4"/>
                </svg>
                Refresh
            </button>

            {{-- Generate Report --}}
            @if($isAdmin)
                <button
                    type="button"
                    @click="openReportModal()"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 active:scale-[.98] dark:bg-brand-500 dark:hover:bg-brand-600"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6 2h9l5 5v13a2 2 0 01-2 2H6a2 2 0 01-2-2V4a2 2 0 012-2z"/>
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M14 2v6h6M8 13h8M8 17h6"/>
                    </svg>
                    Generate Report
                </button>
            @endif

        </div>
    </div>


    {{-- =========================================================
         NEEDS ATTENTION
    ========================================================== --}}
    <div
        x-show="attention.length > 0"
        x-transition
        class="mb-6 rounded-xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/60 dark:bg-amber-950/20"
    >
        <div class="flex items-start gap-3">

            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v4m0 4h.01M10.3 3.9L2.7 17a2 2 0 001.7 3h15.2a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>
                </svg>
            </div>

            <div class="min-w-0 flex-1">

                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                            Needs Attention
                        </h2>

                        <p class="text-xs text-amber-800/80 dark:text-amber-300/80">
                            Staff who are pending or currently marked late.
                        </p>
                    </div>
                </div>

                <div class="mt-3 space-y-2">
                    <template x-for="s in attention" :key="'att-' + s.id">
                        <div class="flex flex-col gap-3 rounded-lg border border-amber-200 bg-white/80 px-3 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-amber-900/50 dark:bg-slate-900/70">

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p
                                        class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                                        x-text="s.name"
                                    ></p>

                                    <span
                                        class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset"
                                        :class="badge(s).cls"
                                        x-text="badge(s).label"
                                    ></span>
                                </div>

                                <p
                                    class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                                    x-text="s.shift || 'No shift assigned'"
                                ></p>
                            </div>

                            <div class="flex items-center gap-2">

                                <button
                                    x-show="s.display_status === 'pending'"
                                    type="button"
                                    @click="checkIn(s.id)"
                                    class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 dark:bg-brand-500 dark:hover:bg-brand-600"
                                >
                                    Check In
                                </button>

                                <button
                                    x-show="s.display_status === 'late'"
                                    type="button"
                                    @click="jump(s.id)"
                                    class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-200 dark:hover:bg-slate-700"
                                >
                                    View
                                </button>

                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- All accounted for --}}
    <div
        x-show="attention.length === 0 && summary.scheduled > 0"
        x-transition
        class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50/70 px-4 py-3 dark:border-emerald-900/60 dark:bg-emerald-950/20"
    >
        <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <p class="text-sm font-medium text-emerald-800 dark:text-emerald-300">
                All scheduled staff are accounted for.
            </p>
        </div>
    </div>


    {{-- =========================================================
         STAFF ATTENDANCE
    ========================================================== --}}
    <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">

        {{-- Section header --}}
        <div class="border-b border-gray-200 px-4 py-4 sm:px-5 dark:border-slate-800">

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        Staff Attendance
                    </h2>

                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Check staff in and out for today's scheduled shifts.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">

                    {{-- Search --}}
                    <div class="relative sm:w-64">
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <circle cx="11" cy="11" r="7"/>
                            <path stroke-linecap="round" d="m20 20-3.5-3.5"/>
                        </svg>

                        <input
                            type="text"
                            x-model.debounce.150ms="search"
                            placeholder="Search staff..."
                            class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                        >
                    </div>

                </div>
            </div>

            {{-- Quick filters --}}
            <div class="mt-4 flex flex-wrap items-center gap-1.5">
                <template x-for="[f, label] in filters" :key="f">
                    <button
                        type="button"
                        @click="filter = f"
                        :class="pillCls(f)"
                    >
                        <span x-text="label"></span>

                        <span
                            class="rounded-full bg-black/5 px-1.5 py-0.5 text-[10px] dark:bg-white/10"
                            x-text="count(f)"
                        ></span>
                    </button>
                </template>
            </div>
        </div>


        {{-- Empty state --}}
        <div
            x-show="list.length === 0"
            class="px-5 py-12 text-center"
        >
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-gray-500">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </div>

            <p class="mt-3 text-sm font-semibold text-gray-800 dark:text-gray-200">
                No active staff members found.
            </p>
        </div>


        {{-- Desktop table header --}}
        <div
            x-show="list.length > 0"
            class="hidden border-b border-gray-200 bg-gray-50 px-5 py-3 md:grid md:grid-cols-12 md:gap-4 dark:border-slate-800 dark:bg-slate-950/40"
        >
            <div class="col-span-3 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Staff
            </div>

            <div class="col-span-2 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Shift
            </div>

            <div class="col-span-2 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Status
            </div>

            <div class="col-span-3 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Times
            </div>

            <div class="col-span-2 text-right text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Action
            </div>
        </div>


        {{-- Staff rows --}}
        <div
            x-show="list.length > 0"
            class="divide-y divide-gray-200 dark:divide-slate-800"
        >

            <template x-for="s in filtered" :key="s.id">

                <div
                    :id="'att-row-' + s.id"
                    class="grid grid-cols-1 gap-4 px-4 py-4 transition md:grid-cols-12 md:items-center md:gap-4 md:px-5"
                >

                    {{-- Staff --}}
                    <div class="md:col-span-3 min-w-0">
                        <div class="flex items-center gap-3">

                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                <span
                                    x-text="s.name
                                        .split(' ')
                                        .map(n => n[0])
                                        .slice(0, 2)
                                        .join('')
                                        .toUpperCase()"
                                ></span>
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                                    x-text="s.name"
                                ></p>

                                <p
                                    class="truncate text-xs text-gray-500 dark:text-gray-400"
                                    x-text="s.username"
                                ></p>
                            </div>

                        </div>
                    </div>


                    {{-- Shift --}}
                    <div class="md:col-span-2">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 md:hidden">
                            Shift
                        </p>

                        <p
                            class="mt-0.5 text-sm text-gray-700 dark:text-gray-300"
                            x-text="s.shift || 'No schedule'"
                        ></p>
                    </div>


                    {{-- Status --}}
                    <div class="md:col-span-2">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 md:hidden">
                            Status
                        </p>

                        <span
                            class="mt-1 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                            :class="badge(s).cls"
                            x-text="badge(s).label"
                        ></span>
                    </div>


                    {{-- Times --}}
                    <div class="md:col-span-3">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 md:hidden">
                            Attendance Time
                        </p>

                        <div class="mt-1 space-y-0.5 text-xs text-gray-600 dark:text-gray-300">

                            <div class="flex items-center gap-2">
                                <span class="w-14 text-gray-400 dark:text-gray-500">
                                    In
                                </span>

                                <span
                                    class="font-medium"
                                    x-text="s.check_in || '—'"
                                ></span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="w-14 text-gray-400 dark:text-gray-500">
                                    Out
                                </span>

                                <span
                                    class="font-medium"
                                    x-text="s.check_out || '—'"
                                ></span>
                            </div>

                        </div>
                    </div>


                    {{-- Actions --}}
                    <div class="md:col-span-2 md:flex md:justify-end">

                        <div class="flex flex-wrap items-center gap-2">

                            <template x-if="s.display_status === 'pending'">
                                <button
                                    type="button"
                                    @click="checkIn(s.id)"
                                    class="inline-flex flex-1 items-center justify-center rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50 dark:bg-brand-500 dark:hover:bg-brand-600 sm:flex-none"
                                >
                                    Check In
                                </button>
                            </template>


                            <template x-if="s.display_status === 'checked_in' || s.display_status === 'late'">
                                <button
                                    type="button"
                                    @click="checkOut(s.id)"
                                    class="inline-flex flex-1 items-center justify-center rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-gray-800 disabled:opacity-50 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 sm:flex-none"
                                >
                                    Check Out
                                </button>
                            </template>


                            <template x-if="s.display_status === 'completed'">
                                <span class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                                    Done
                                </span>
                            </template>


                            <template x-if="s.off || s.display_status === 'not_scheduled'">
                                <span class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-500 dark:bg-slate-800 dark:text-gray-400">
                                    No action needed
                                </span>
                            </template>


                            <button
                                x-show="canMark && !s.off && s.display_status !== 'not_scheduled'"
                                type="button"
                                @click="openCorrection(s)"
                                class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-2.5 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 dark:hover:bg-slate-800"
                            >
                                More
                            </button>

                        </div>
                    </div>

                </div>

            </template>


            {{-- No filtered results --}}
            <div
                x-show="filtered.length === 0"
                class="px-5 py-12 text-center"
            >
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-gray-500">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="11" cy="11" r="7"/>
                        <path stroke-linecap="round" d="m20 20-3.5-3.5"/>
                    </svg>
                </div>

                <p class="mt-3 text-sm font-semibold text-gray-800 dark:text-gray-200">
                    No staff match your search or filter.
                </p>

                <button
                    type="button"
                    @click="search = ''; filter = 'all'"
                    class="mt-2 text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                >
                    Clear filters
                </button>
            </div>

        </div>
    </section>


    {{-- =========================================================
         TODAY'S SUMMARY
    ========================================================== --}}
    <div class="mt-6" id="audit-section">

        <div class="mb-3">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                Today's Summary
            </h2>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                Current attendance status for today's schedule.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">

            {{-- Checked In --}}
            <button
                type="button"
                @click="setFilter('checked_in')"
                class="rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:border-brand-300 hover:shadow dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-700"
            >
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    Checked In
                </p>

                <p
                    class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                    x-text="summary.checkedIn"
                ></p>
            </button>


            {{-- Late --}}
            <button
                type="button"
                @click="setFilter('late')"
                class="rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:border-red-300 hover:shadow dark:border-slate-800 dark:bg-slate-900 dark:hover:border-red-800"
            >
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    Late
                </p>

                <p
                    class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400"
                    x-text="summary.late"
                ></p>
            </button>


            {{-- Pending --}}
            <button
                type="button"
                @click="setFilter('pending')"
                class="rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:border-amber-300 hover:shadow dark:border-slate-800 dark:bg-slate-900 dark:hover:border-amber-800"
            >
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    Pending
                </p>

                <p
                    class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400"
                    x-text="summary.pending"
                ></p>
            </button>


            {{-- Off / Leave --}}
            <button
                type="button"
                @click="setFilter('off_leave')"
                class="rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:border-gray-300 hover:shadow dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
            >
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    Off / Leave
                </p>

                <p
                    class="mt-1 text-2xl font-bold text-gray-700 dark:text-gray-200"
                    x-text="summary.off"
                ></p>
            </button>

        </div>

        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            <span
                class="font-semibold text-gray-700 dark:text-gray-300"
                x-text="summary.scheduled"
            ></span>
            scheduled staff today.
        </p>

    </div>


    {{-- =========================================================
         ATTENDANCE & LEAVE AUDIT LOGS
    ========================================================== --}}
    <div class="mt-6" id="audit-section">
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (window.location.search.length > 0 || window.location.hash === '#audit-section') {
                const el = document.getElementById('audit-section');
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    </script>


        <div class="mb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                    Attendance & Leave Audit Logs
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Recorded changes to attendance and leave records.
                </p>
            </div>
        </div>

        {{-- Audit filters --}}
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <a href="{{ route('attendance.today') }}#audit-section" class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold {{ empty($auditFilter) ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 border border-gray-200 dark:bg-slate-800 dark:text-gray-300 dark:border-slate-700' }}">
                All
            </a>
            <a href="{{ route('attendance.today', ['audit_filter' => 'attendance']) }}#audit-section" class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold {{ ($auditFilter ?? '') === 'attendance' ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 border border-gray-200 dark:bg-slate-800 dark:text-gray-300 dark:border-slate-700' }}">
                Attendance
            </a>
            <a href="{{ route('attendance.today', ['audit_filter' => 'leave']) }}#audit-section" class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold {{ ($auditFilter ?? '') === 'leave' ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 border border-gray-200 dark:bg-slate-800 dark:text-gray-300 dark:border-slate-700' }}">
                Leave
            </a>
            <a href="{{ route('attendance.today', ['audit_filter' => 'corrections']) }}#audit-section" class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold {{ ($auditFilter ?? '') === 'corrections' ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 border border-gray-200 dark:bg-slate-800 dark:text-gray-300 dark:border-slate-700' }}">
                Corrections
            </a>
        </div>

        @if(isset($attendanceLogs) && $attendanceLogs->count())
            @php
                $statusBadge = function($status) {
                    return match(strtolower($status ?? '')) {
                        'present', 'completed', 'checked_in' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/50',
                        'late' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/50',
                        'on_leave', 'leave', 'sick_leave', 'urgent_leave' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/50',
                        'absent' => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300 border border-red-200/60 dark:border-red-800/50',
                        default => 'bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-gray-400 border border-gray-200 dark:border-slate-700',
                    };
                };
            @endphp
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-700 dark:bg-slate-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Date & Time</th>
                            <th class="px-4 py-3">Change Type</th>
                            <th class="px-4 py-3">Staff</th>
                            <th class="px-4 py-3">Status Change</th>
                            <th class="px-4 py-3">Reason / Notes</th>
                            <th class="px-4 py-3">Changed By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                        @foreach($attendanceLogs as $log)
                            <tr class="text-xs text-gray-700 dark:text-gray-300 transition hover:bg-gray-50/50 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900 dark:text-white" title="{{ $log->changed_at->format('M j, Y g:i A') }}">
                                    {{ $log->changed_at->format('M j, Y g:i A') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/50">
                                        {{ $log->change_type ?? ($log->schedule_exception_id ? 'Leave / Exception' : 'Attendance Log') }}
                                    </span>
                                    @if($log->scheduleException && $log->scheduleException->exception_date)
                                        <span class="block text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
                                            Effective: {{ $log->scheduleException->exception_date->format('M j, Y') }}
                                        </span>
                                    @endif
                                    @if($log->old_check_in || $log->new_check_in || $log->old_check_out || $log->new_check_out)
                                        <span class="block text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 font-medium">
                                            @if($log->old_check_in || $log->new_check_in)
                                                In: {{ $log->old_check_in ? \Carbon\Carbon::parse($log->old_check_in)->format('g:i A') : '—' }} → {{ $log->new_check_in ? \Carbon\Carbon::parse($log->new_check_in)->format('g:i A') : '—' }}
                                            @endif
                                            @if($log->old_check_out || $log->new_check_out)
                                                <br>Out: {{ $log->old_check_out ? \Carbon\Carbon::parse($log->old_check_out)->format('g:i A') : '—' }} → {{ $log->new_check_out ? \Carbon\Carbon::parse($log->new_check_out)->format('g:i A') : '—' }}
                                            @endif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-semibold">
                                    {{ $log->user->full_name ?? 'Unknown' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold {{ $statusBadge($log->old_status) }}">
                                            {{ ucfirst(str_replace('_', ' ', $log->old_status ?? 'None')) }}
                                        </span>
                                        <svg class="h-3 w-3 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold {{ $statusBadge($log->new_status) }}">
                                            {{ ucfirst(str_replace('_', ' ', $log->new_status)) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 max-w-[200px] truncate" title="{{ $log->reason }}">
                                    {{ $log->reason ?? '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    {{ $log->changedBy->full_name ?? 'System' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
                <div class="mt-4">
                    {{ $attendanceLogs->links() }}
                </div>
        @else
            <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-6 text-center text-xs text-gray-500 dark:border-slate-800 dark:bg-slate-900/40 dark:text-gray-400">
                No recent attendance or leave logs found.
            </div>
        @endif

    </div>

    {{-- =========================================================
         GENERATE ATTENDANCE REPORT MODAL
    ========================================================== --}}
    @if($isAdmin)

        <div
            x-show="reportOpen"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-4 py-6 backdrop-blur-sm"
            @keydown.escape.window="reportOpen = false"
        >

            <div
                x-show="reportOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 scale-[.98]"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-[.98]"
                @click.outside="reportOpen = false"
                class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-900"
            >

                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-slate-800">

                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">
                            Generate Attendance Report
                        </h2>

                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            Choose the attendance type and date range.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="reportOpen = false"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800 dark:hover:text-gray-200"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                        </svg>
                    </button>

                </div>


                {{-- Modal Body --}}
                <div class="report-modal-scroll overflow-y-auto px-5 py-5">

                    {{-- Step 1: Attendance Type --}}
                    <div>

                        <label class="block text-sm font-semibold text-gray-900 dark:text-white">
                            Attendance Type
                        </label>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Select which attendance records should appear in the report.
                        </p>

                        <div class="mt-3">

                            <select
                                x-model="report.status"
                                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-100"
                            >
                                <option value="">All Attendance</option>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="late">Late</option>
                                <option value="on_leave">Leave</option>
                                <option value="day_off">Day Off</option>
                                <option value="holiday">Holiday</option>
                            </select>

                        </div>
                    </div>


                    {{-- Step 2: Date --}}
                    <div class="mt-6" id="audit-section">

                        <label class="block text-sm font-semibold text-gray-900 dark:text-white">
                            Date
                        </label>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Select the period covered by the attendance report.
                        </p>


                        {{-- Date options --}}
                        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-5">

                            <button
                                type="button"
                                @click="report.range = 'today'"
                                :class="report.range === 'today'
                                    ? 'border-brand-600 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-900/20 dark:text-brand-300'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700'"
                                class="rounded-lg border px-3 py-2.5 text-xs font-semibold transition"
                            >
                                Today
                            </button>

                            <button
                                type="button"
                                @click="report.range = 'week'"
                                :class="report.range === 'week'
                                    ? 'border-brand-600 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-900/20 dark:text-brand-300'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700'"
                                class="rounded-lg border px-3 py-2.5 text-xs font-semibold transition"
                            >
                                This Week
                            </button>

                            <button
                                type="button"
                                @click="report.range = 'month'"
                                :class="report.range === 'month'
                                    ? 'border-brand-600 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-900/20 dark:text-brand-300'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700'"
                                class="rounded-lg border px-3 py-2.5 text-xs font-semibold transition"
                            >
                                This Month
                            </button>

                            <button
                                type="button"
                                @click="report.range = 'year'"
                                :class="report.range === 'year'
                                    ? 'border-brand-600 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-900/20 dark:text-brand-300'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700'"
                                class="rounded-lg border px-3 py-2.5 text-xs font-semibold transition"
                            >
                                This Year
                            </button>

                            <button
                                type="button"
                                @click="report.range = 'custom'"
                                :class="report.range === 'custom'
                                    ? 'border-brand-600 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-900/20 dark:text-brand-300'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700'"
                                class="col-span-2 rounded-lg border px-3 py-2.5 text-xs font-semibold transition sm:col-span-1"
                            >
                                Custom
                            </button>

                        </div>


                        {{-- Custom date range --}}
                        <div
                            x-show="report.range === 'custom'"
                            x-transition
                            class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:bg-slate-800/60"
                        >

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                                {{-- Start Date --}}
                                <div>

                                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Start Date
                                    </label>

                                    <div class="relative">

                                        <input
                                            id="attendanceReportStart"
                                            type="text"
                                            x-model="report.start_date"
                                            placeholder="Select start date"
                                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 pr-10 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-100"
                                        >

                                        <svg
                                            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <rect x="3" y="5" width="18" height="16" rx="2"/>
                                            <path stroke-linecap="round" d="M8 3v4M16 3v4M3 10h18"/>
                                        </svg>

                                    </div>

                                </div>


                                {{-- End Date --}}
                                <div>

                                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        End Date
                                    </label>

                                    <div class="relative">

                                        <input
                                            id="attendanceReportEnd"
                                            type="text"
                                            x-model="report.end_date"
                                            placeholder="Select end date"
                                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 pr-10 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-100"
                                        >

                                        <svg
                                            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <rect x="3" y="5" width="18" height="16" rx="2"/>
                                            <path stroke-linecap="round" d="M8 3v4M16 3v4M3 10h18"/>
                                        </svg>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- Current selection --}}
                    <div class="mt-5 rounded-lg bg-gray-50 px-3.5 py-3 dark:bg-slate-800/70">

                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Report Selection
                        </p>

                        <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">

                            <span
                                class="font-semibold text-gray-800 dark:text-gray-200"
                                x-text="reportStatusLabel()"
                            ></span>

                            <span class="text-gray-400">•</span>

                            <span
                                class="text-gray-600 dark:text-gray-300"
                                x-text="reportRangeLabel()"
                            ></span>

                        </div>

                    </div>

                </div>


                {{-- Modal Footer --}}
                <div class="border-t border-gray-200 bg-gray-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/40">

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">

                        <button
                            type="button"
                            @click="reportOpen = false"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-200 dark:hover:bg-slate-700"
                        >
                            Cancel
                        </button>


                        {{-- Preview --}}
                        <button
                            type="button"
                            @click="generateReport('stream')"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-brand-600 bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 dark:border-brand-500 dark:bg-slate-900 dark:text-brand-300 dark:hover:bg-brand-900/20"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"/>
                                <circle cx="12" cy="12" r="2.5"/>
                            </svg>

                            Preview PDF
                        </button>


                        {{-- Download --}}
                        <button
                            type="button"
                            @click="generateReport('download')"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 dark:bg-brand-500 dark:hover:bg-brand-600"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 3v12m0 0l4-4m-4 4l-4-4M5 21h14"/>
                            </svg>

                            Download PDF
                        </button>

                    </div>

                </div>

            </div>
        </div>

    @endif


    {{-- =========================================================
         MANUAL CORRECTION MODAL
    ========================================================== --}}
    <div
        x-show="correctionOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 px-4 py-6 backdrop-blur-sm"
        @keydown.escape.window="correctionOpen = false"
    >

        <div
            x-show="correctionOpen"
            x-transition
            @click.outside="correctionOpen = false"
            class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-900"
        >

            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-slate-800">

                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        Manual Attendance Correction
                    </h3>

                    <p
                        class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                        x-text="correcting ? correcting.name : ''"
                    ></p>
                </div>

                <button
                    type="button"
                    @click="correctionOpen = false"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800 dark:hover:text-gray-200"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>

            </div>


            <form
                id="attCorrectionForm"
                @submit.prevent="submitCorrection"
                class="px-5 py-5"
            >

                {{-- Correction Type --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Correction Type
                    </label>

                    <select
                        x-model="corr.type"
                        class="mt-1.5 w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-100"
                    >
                        <option value="check_in">Check In</option>
                        <option value="check_out">Check Out</option>
                    </select>
                </div>


                {{-- Actual Time --}}
                <div class="mt-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Actual Time
                    </label>

                    <input
                        type="time"
                        x-model="corr.time"
                        class="mt-1.5 w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-100"
                    >
                </div>


                {{-- Reason --}}
                <div class="mt-4">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Reason
                    </label>

                    <select
                        x-model="corr.reason"
                        class="mt-1.5 w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-100"
                    >
                        <option value="">Select a reason</option>
                        <option value="Forgot to check in">Forgot to check in</option>
                        <option value="Forgot to check out">Forgot to check out</option>
                        <option value="System error">System error</option>
                        <option value="Incorrect attendance record">Incorrect attendance record</option>
                        <option value="Other">Other</option>
                    </select>
                </div>


                {{-- Other reason --}}
                <div
                    x-show="corr.reason === 'Other'"
                    x-transition
                    class="mt-4"
                >
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Specify Reason
                    </label>

                    <textarea
                        x-model="corr.reasonOther"
                        rows="3"
                        placeholder="Enter the reason..."
                        class="mt-1.5 w-full resize-none rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 outline-none placeholder:text-gray-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-100"
                    ></textarea>
                </div>


                {{-- Error --}}
                <div
                    x-show="corr.error"
                    x-transition
                    class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-xs font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-300"
                    x-text="corr.error"
                ></div>


                {{-- Recorded by --}}
                <div class="mt-5 rounded-lg bg-gray-50 px-3.5 py-3 dark:bg-slate-800/70">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Recorded By
                    </p>

                    <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                    </p>
                </div>


                {{-- Buttons --}}
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">

                    <button
                        type="button"
                        @click="correctionOpen = false"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-200 dark:hover:bg-slate-700"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="submitting"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-brand-500 dark:hover:bg-brand-600"
                    >
                        <svg
                            x-show="submitting"
                            class="h-4 w-4 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            ></path>
                        </svg>

                        <span x-text="submitting ? 'Saving...' : 'Save Correction'"></span>
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>

@endsection


@push('scripts')

<script>
const ATT_CSRF =
    document.querySelector('meta[name="csrf-token"]')?.content || '';

function attToast(message, type = 'success') {
    if (typeof Swal === 'undefined') return;

    Swal.fire({
        icon: type,
        title: message,
        timer: 3200,
        timerProgressBar: true,
        showConfirmButton: false,
        toast: true,
        position: 'top-end',
        background: document.documentElement.classList.contains('dark')
            ? '#1e293b'
            : '#ffffff',
        color: document.documentElement.classList.contains('dark')
            ? '#f8fafc'
            : '#1e293b',
    });
}


document.addEventListener('alpine:init', () => {

    const BADGES = {
        pending: {
            label: 'Pending',
            cls: 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/20 dark:text-amber-300'
        },

        checked_in: {
            label: 'Checked In',
            cls: 'bg-teal-50 text-teal-700 ring-teal-600/20 dark:bg-teal-900/20 dark:text-teal-300'
        },

        late: {
            label: 'Late',
            cls: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/20 dark:text-red-300'
        },

        completed: {
            label: 'Completed',
            cls: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-300'
        },

        off_leave: {
            label: 'Off / Leave',
            cls: 'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-800 dark:text-slate-300'
        },

        not_scheduled: {
            label: 'No Schedule',
            cls: 'bg-gray-50 text-gray-500 ring-gray-400/20 dark:bg-slate-800/60 dark:text-slate-400'
        },
    };


    const FILTERS = [
        ['all', 'All'],
        ['pending', 'Pending'],
        ['checked_in', 'Checked In'],
        ['late', 'Late'],
        ['completed', 'Completed'],
        ['off_leave', 'Off / Leave']
    ];


    Alpine.data('attendanceBoard', (cfg) => ({

        staff: cfg.staff,
        urls: cfg.urls,
        canMark: cfg.canMark,

        reportUrl: cfg.reportUrl,

        filters: FILTERS,

        search: '',
        filter: 'all',

        busy: {},

        correctionOpen: false,
        correcting: null,

        submitting: false,

        corr: {
            type: 'check_in',
            time: '',
            reason: '',
            reasonOther: '',
            error: ''
        },


        /*
         * ========================================================
         * REPORT STATE
         *
         * This is completely separate from the operational
         * attendance list and today's attendance filters.
         * ========================================================
         */

        reportOpen: false,

        report: {
            status: '',
            range: 'today',
            start_date: '',
            end_date: ''
        },


        get list() {
            return Object.values(this.staff);
        },


        count(f) {
            return f === 'all'
                ? this.list.filter(
                    s => s.display_status !== 'not_scheduled'
                ).length
                : this.list.filter(
                    s => s.display_status === f
                ).length;
        },


        get filtered() {

            const q = this.search.trim().toLowerCase();

            return this.list.filter(s => {

                if (
                    this.filter !== 'all' &&
                    s.display_status !== this.filter
                ) {
                    return false;
                }

                if (
                    q &&
                    !s.name.toLowerCase().includes(q) &&
                    !s.username.toLowerCase().includes(q)
                ) {
                    return false;
                }

                return true;
            });
        },


        get summary() {

            const s = {
                scheduled: 0,
                checkedIn: 0,
                late: 0,
                pending: 0,
                off: 0
            };

            for (const m of this.list) {

                if (m.display_status === 'not_scheduled') {
                    continue;
                }

                s.scheduled++;

                if (m.display_status === 'off_leave') {
                    s.off++;
                }

                else if (m.display_status === 'pending') {
                    s.pending++;
                }

                else {
                    s.checkedIn++;

                    if (m.display_status === 'late') {
                        s.late++;
                    }
                }
            }

            return s;
        },


        get attention() {

            return this.list
                .filter(m =>
                    m.display_status === 'late' ||
                    m.display_status === 'pending'
                )
                .sort((a, b) =>
                    a.display_status === b.display_status
                        ? a.name.localeCompare(b.name)
                        : (
                            a.display_status === 'late'
                                ? -1
                                : 1
                        )
                );
        },


        badge(m) {
            return BADGES[m.display_status] || BADGES.pending;
        },


        pillCls(f) {

            const base =
                'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-inset transition active:scale-95 ';

            return this.filter === f
                ? base +
                    'bg-brand-600 text-white ring-brand-600 dark:bg-brand-500 dark:ring-brand-500'
                : base +
                    'bg-white text-gray-600 ring-gray-200 hover:bg-gray-50 dark:bg-slate-900 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-slate-800';
        },


        setFilter(f) {
            this.filter = this.filter === f ? 'all' : f;
        },


        async post(url, id) {

            if (this.busy[id]) {
                return null;
            }

            this.busy[id] = true;

            try {

                const res = await fetch(
                    url.replace('__ID__', id),
                    {
                        method: 'POST',

                        headers: {
                            'X-CSRF-TOKEN': ATT_CSRF,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    }
                );

                const data =
                    await res.json().catch(() => ({}));

                if (!res.ok || data.success === false) {

                    attToast(
                        data.message ||
                        (
                            'Request failed (' +
                            res.status +
                            '). Please try again.'
                        ),
                        'error'
                    );

                    return null;
                }

                return data;

            } catch (e) {

                attToast(
                    'Network error — check your connection and try again.',
                    'error'
                );

                return null;

            } finally {

                this.busy[id] = false;
            }
        },


        async checkIn(id) {

            const data =
                await this.post(this.urls.checkin, id);

            if (!data) {
                return;
            }

            const m = this.staff[id];

            m.check_in = data.check_in;
            m.check_out = data.check_out;
            m.display_status = data.display_status;

            attToast(data.message, 'success');
        },


        async checkOut(id) {

            const data =
                await this.post(this.urls.checkout, id);

            if (!data) {
                return;
            }

            const m = this.staff[id];

            m.check_in = data.check_in;
            m.check_out = data.check_out;
            m.display_status = data.display_status;

            attToast(data.message, 'success');
        },


        jump(id) {

            this.search = '';
            this.filter = 'all';

            this.$nextTick(() => {

                const el =
                    document.getElementById(
                        'att-row-' + id
                    );

                if (!el) {
                    return;
                }

                el.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                el.classList.add(
                    'ring-2',
                    'ring-amber-400',
                    'dark:ring-amber-500'
                );

                setTimeout(() => {

                    el.classList.remove(
                        'ring-2',
                        'ring-amber-400',
                        'dark:ring-amber-500'
                    );

                }, 2000);
            });
        },


        /*
         * ========================================================
         * REPORT MODAL
         * ========================================================
         */

        openReportModal() {

            this.report = {
                status: '',
                range: 'today',
                start_date: '',
                end_date: ''
            };

            this.reportOpen = true;

            this.$nextTick(() => {

                if (
                    typeof flatpickr !== 'undefined'
                ) {

                    const start =
                        document.getElementById(
                            'attendanceReportStart'
                        );

                    const end =
                        document.getElementById(
                            'attendanceReportEnd'
                        );

                    if (start) {

                        if (start._flatpickr) {
                            start._flatpickr.destroy();
                        }

                        flatpickr(start, {
                            dateFormat: 'Y-m-d',
                            altInput: true,
                            altFormat: 'M j, Y',
                            allowInput: true,
                            onChange: (selectedDates, dateStr) => {
                                this.report.start_date =
                                    dateStr;
                            }
                        });
                    }

                    if (end) {

                        if (end._flatpickr) {
                            end._flatpickr.destroy();
                        }

                        flatpickr(end, {
                            dateFormat: 'Y-m-d',
                            altInput: true,
                            altFormat: 'M j, Y',
                            allowInput: true,
                            onChange: (selectedDates, dateStr) => {
                                this.report.end_date =
                                    dateStr;
                            }
                        });
                    }
                }
            });
        },


        reportStatusLabel() {

            const labels = {
                '': 'All Attendance',
                present: 'Present',
                absent: 'Absent',
                late: 'Late',
                on_leave: 'Leave',
                day_off: 'Day Off',
                holiday: 'Holiday'
            };

            return labels[this.report.status] ||
                'All Attendance';
        },


        reportRangeLabel() {

            const labels = {
                today: 'Today',
                week: 'This Week',
                month: 'This Month',
                year: 'This Year',
                custom: 'Custom Date Range'
            };

            if (this.report.range === 'custom') {

                if (
                    this.report.start_date &&
                    this.report.end_date
                ) {
                    return (
                        this.report.start_date +
                        ' to ' +
                        this.report.end_date
                    );
                }

                return 'Custom Date Range';
            }

            return labels[this.report.range] ||
                'Today';
        },


        generateReport(action) {

            if (this.report.range === 'custom') {

                if (
                    !this.report.start_date ||
                    !this.report.end_date
                ) {

                    attToast(
                        'Please select both a start date and end date.',
                        'warning'
                    );

                    return;
                }
            }


            const params =
                new URLSearchParams();

            params.set('action', action);

            params.set(
                'range',
                this.report.range
            );


            if (this.report.status) {

                params.set(
                    'status',
                    this.report.status
                );
            }


            if (this.report.range === 'custom') {

                params.set(
                    'start_date',
                    this.report.start_date
                );

                params.set(
                    'end_date',
                    this.report.end_date
                );
            }


            const url =
                this.reportUrl +
                '?' +
                params.toString();


            if (action === 'stream') {

                window.open(
                    url,
                    '_blank',
                    'noopener'
                );

                return;
            }


            window.location.href = url;
        },


        /*
         * ========================================================
         * MANUAL CORRECTION
         * ========================================================
         */

        openCorrection(m) {

            if (!this.canMark) {

                attToast(
                    'You are not allowed to record attendance corrections.',
                    'error'
                );

                return;
            }

            this.correcting = m;

            const pad =
                n => String(n).padStart(2, '0');

            const now = new Date();

            const type =
                m.check_in
                    ? 'check_out'
                    : 'check_in';

            let time =
                pad(now.getHours()) +
                ':' +
                pad(now.getMinutes());


            if (type === 'check_in' && m.shift) {

                const start =
                    m.shift
                        .split('–')[0]
                        .trim();

                const parsed =
                    new Date(
                        '1970-01-01 ' + start
                    );

                if (!isNaN(parsed)) {

                    time =
                        pad(parsed.getHours()) +
                        ':' +
                        pad(parsed.getMinutes());
                }
            }


            this.corr = {
                type: type,
                time: time,
                reason: '',
                reasonOther: '',
                error: ''
            };

            this.correctionOpen = true;
        },


        async submitCorrection() {

            const m = this.correcting;

            if (!m || this.submitting) {
                return;
            }


            const reason =
                this.corr.reason === 'Other'
                    ? this.corr.reasonOther.trim()
                    : this.corr.reason;


            if (!this.corr.time) {

                this.corr.error =
                    'Please specify the actual time.';

                return;
            }


            if (!reason) {

                this.corr.error =
                    'Please select a reason for this correction.';

                return;
            }


            if (
                this.corr.reason === 'Other' &&
                !this.corr.reasonOther.trim()
            ) {

                this.corr.error =
                    'Please specify the reason.';

                return;
            }


            this.corr.error = '';


            const confirm =
                await Swal.fire({

                    icon: 'warning',

                    title:
                        'Record manual correction?',

                    html:
                        'This will update <b>' +
                        m.name +
                        '</b>\'s attendance and be stored in the audit log.',

                    showCancelButton: true,

                    confirmButtonText:
                        'Yes, save correction',

                    cancelButtonText:
                        'Cancel',

                    confirmButtonColor:
                        '#0d9488',

                    background:
                        document.documentElement.classList.contains('dark')
                            ? '#1e293b'
                            : '#ffffff',

                    color:
                        document.documentElement.classList.contains('dark')
                            ? '#f8fafc'
                            : '#1e293b',
                });


            if (!confirm.isConfirmed) {
                return;
            }


            this.submitting = true;


            try {

                const res =
                    await fetch(
                        this.urls.correct.replace(
                            '__ID__',
                            m.id
                        ),
                        {
                            method: 'POST',

                            headers: {
                                'X-CSRF-TOKEN': ATT_CSRF,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },

                            body: JSON.stringify({
                                type: this.corr.type,
                                time: this.corr.time,
                                reason: reason
                            })
                        }
                    );


                const data =
                    await res.json().catch(() => ({}));


                if (
                    !res.ok ||
                    data.success === false
                ) {

                    this.corr.error =
                        data.message ||
                        'The correction could not be saved. Please try again.';

                    return;
                }


                m.check_in =
                    data.check_in;

                m.check_out =
                    data.check_out;

                m.display_status =
                    data.display_status;


                this.correctionOpen =
                    false;


                attToast(
                    data.message,
                    'success'
                );


            } catch (e) {

                this.corr.error =
                    'Network error — check your connection and try again.';

            } finally {

                this.submitting = false;
            }
        },

    }));
});
</script>

@endpush