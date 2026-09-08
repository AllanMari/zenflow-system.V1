@extends(auth()->user()->roles->contains('name', 'admin') ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Staff Attendance')

{{-- Flash messages are handled by the master layout (session success/error -> Swal toast).
     This page does NOT create a second flash component. SweetAlert2 here is used only
     for AJAX success/error feedback and the correction confirmation, per spec. --}}

@push('styles')
<style>[x-cloak] { display: none !important; }</style>
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
    })"
    x-cloak
    class="max-w-[85rem] px-4 py-6 sm:px-6 lg:px-8 mx-auto"
>
    <!-- ================= Header ================= -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Daily Attendance</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ $today->format('l, F j, Y') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.location.reload()" title="Sync with server"
                    class="py-2 px-3.5 inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white text-xs font-semibold text-gray-600 hover:bg-gray-50 transition dark:bg-slate-900 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-slate-800 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </button>
            @if($isAdmin)
            <a href="{{ route('attendance.report') }}"
               class="py-2 px-3.5 inline-flex items-center gap-2 rounded-xl bg-gray-900 text-xs font-semibold text-white hover:bg-gray-800 transition dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                View Report
            </a>
            @endif
        </div>
    </div>

    <!-- ================= Compact Summary (replaces the 6-card dashboard) ================= -->
    <div class="mb-6 flex flex-wrap items-center gap-2">
        <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mr-1">Today</span>
        <button type="button" @click="setFilter('checked_in')" :class="pillCls('checked_in')"
                class="transition active:scale-95">
            <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
            <span x-text="summary.checkedIn"></span> Checked In
        </button>
        <button type="button" @click="setFilter('late')" :class="pillCls('late')"
                class="transition active:scale-95">
            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
            <span x-text="summary.late"></span> Late
        </button>
        <button type="button" @click="setFilter('pending')" :class="pillCls('pending')"
                class="transition active:scale-95">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
            <span x-text="summary.pending"></span> Pending
        </button>
        <button type="button" @click="setFilter('off_leave')" :class="pillCls('off_leave')"
                class="transition active:scale-95">
            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
            <span x-text="summary.off"></span> Off / Leave
        </button>
        <span class="ml-auto text-xs font-medium text-gray-400 dark:text-gray-500 hidden sm:inline">
            <span x-text="summary.scheduled"></span> scheduled today
        </span>
    </div>

    <!-- ================= Needs Attention ================= -->
    <div x-show="attention.length > 0" class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 dark:bg-amber-900/10 dark:border-amber-800/60 p-4">
        <div class="flex items-center gap-2 mb-3">
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <h2 class="text-sm font-bold text-amber-800 dark:text-amber-300">Needs Attention</h2>
            <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/40 rounded-full px-2 py-0.5" x-text="attention.length"></span>
        </div>
        <div class="flex flex-wrap gap-2">
            <template x-for="s in attention" :key="'att-' + s.id">
                <div class="flex items-center gap-2.5 bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-800/50 rounded-xl pl-1.5 pr-2 py-1.5 shadow-sm">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-100 text-amber-800 text-[11px] font-bold dark:bg-amber-900/40 dark:text-amber-300" x-text="s.initials"></span>
                    <div class="leading-tight">
                        <p class="text-xs font-bold text-gray-800 dark:text-gray-200" x-text="s.name"></p>
                        <p class="text-[10px] font-semibold" :class="s.display_status === 'late' ? 'text-red-500' : 'text-amber-500'"
                           x-text="s.display_status === 'late' ? 'Late — checked in after schedule' : 'Not checked in yet'"></p>
                    </div>
                    <button type="button" x-show="s.display_status === 'pending'" @click="checkIn(s.id)" :disabled="busy[s.id]"
                            class="ml-1 py-1.5 px-2.5 rounded-lg bg-brand-600 text-white text-[11px] font-bold hover:bg-brand-700 transition active:scale-95 disabled:opacity-60 inline-flex items-center gap-1">
                        <svg x-show="busy[s.id]" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        Check In
                    </button>
                    <button type="button" x-show="s.display_status === 'late'" @click="jump(s.id)"
                            class="ml-1 py-1.5 px-2.5 rounded-lg border border-gray-200 dark:border-gray-700 text-[11px] font-bold text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-800 transition active:scale-95">View</button>
                </div>
            </template>
        </div>
    </div>

    <div x-show="attention.length === 0 && summary.scheduled > 0"
         class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-900/10 dark:border-emerald-800/60 px-4 py-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">All scheduled staff are accounted for.</p>
    </div>

    <!-- ================= Search + Status Filters ================= -->
    <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <div class="relative sm:w-72">
            <div class="absolute inset-y-0 left-0 flex items-center pointer-events-none pl-3.5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text" x-model.debounce.150ms="search" placeholder="Search staff…"
                   class="py-2.5 pl-10 pr-4 block w-full border border-gray-200 rounded-xl text-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-200">
        </div>
        <div class="flex flex-wrap items-center gap-1.5">
            <template x-for="[f, label] in filters" :key="f">
                <button type="button" @click="filter = f"
                        :class="filter === f
                            ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                            : 'bg-white text-gray-600 ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-slate-900 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-slate-800'"
                        class="rounded-full px-3 py-1.5 text-xs font-semibold transition active:scale-95">
                    <span x-text="label"></span>
                    <span class="opacity-60 ml-0.5" x-text="count(f)"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- ================= Staff List (ONE shared structure: cards on mobile, table rows on desktop) ================= -->
    <div x-show="list.length === 0" class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center">
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No active staff members found.</p>
    </div>

    <div x-show="list.length > 0">
        <!-- Desktop column headers -->
        <div class="hidden md:grid grid-cols-12 gap-4 px-6 pb-2 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            <div class="col-span-4">Staff</div>
            <div class="col-span-2">Shift</div>
            <div class="col-span-2">Status</div>
            <div class="col-span-2">Times</div>
            <div class="col-span-2 text-right">Action</div>
        </div>

        <div class="space-y-3 md:space-y-0">
            <template x-for="s in filtered" :key="s.id">
                <div :id="'att-row-' + s.id"
                     class="grid grid-cols-1 md:grid-cols-12 gap-3 md:gap-4 md:items-center px-4 md:px-6 py-4 rounded-2xl md:rounded-none transition-colors
                            bg-white border border-gray-200 shadow-sm md:shadow-none md:border-0 md:border-b md:bg-transparent
                            dark:bg-slate-900 dark:border-gray-800 md:dark:bg-transparent md:dark:border-gray-800
                            hover:bg-gray-50/70 dark:hover:bg-slate-800/40">

                    <!-- Staff -->
                    <div class="md:col-span-4">
                        <span class="md:hidden block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Staff</span>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-brand-100 text-brand-800 text-sm font-bold ring-2 ring-white dark:ring-slate-900 dark:bg-brand-900/40 dark:text-brand-300 shrink-0" x-text="s.initials"></span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate" x-text="s.name"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 truncate" x-text="s.username"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Shift -->
                    <div class="md:col-span-2">
                        <span class="md:hidden block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Shift</span>
                        <template x-if="s.off">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-800 dark:text-slate-300"
                                  x-text="s.off_label"></span>
                        </template>
                        <template x-if="!s.off">
                            <span class="text-sm text-gray-600 dark:text-gray-400 font-medium" x-text="s.shift ?? 'No schedule'"></span>
                        </template>
                        <p x-show="s.reason" class="text-[11px] text-gray-400 dark:text-gray-500 truncate mt-0.5 max-w-[220px]" x-text="s.reason"></p>
                    </div>

                    <!-- Status (derived by the server — never selected by hand) -->
                    <div class="md:col-span-2">
                        <span class="md:hidden block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Status</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                              :class="badge(s).cls">
                            <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
                            <span x-text="badge(s).label"></span>
                        </span>
                    </div>

                    <!-- Times -->
                    <div class="md:col-span-2">
                        <span class="md:hidden block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1">Times</span>
                        <span class="text-sm text-gray-600 dark:text-gray-400 tabular-nums">
                            <span x-text="s.check_in ?? '—'"></span>
                            <span class="text-gray-300 dark:text-gray-600 mx-0.5">→</span>
                            <span x-text="s.check_out ?? '—'"></span>
                        </span>
                    </div>

                    <!-- Action: exactly ONE primary button per row + overflow -->
                    <div class="md:col-span-2 flex flex-wrap md:justify-end items-center gap-2">
                        <template x-if="s.display_status === 'pending'">
                            <button type="button" @click="checkIn(s.id)" :disabled="busy[s.id] || !canMark"
                                    class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
                                <svg x-show="busy[s.id]" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                <span x-text="busy[s.id] ? 'Saving…' : 'Check In'"></span>
                            </button>
                        </template>
                        <template x-if="s.display_status === 'checked_in' || s.display_status === 'late'">
                            <button type="button" @click="checkOut(s.id)" :disabled="busy[s.id] || !canMark"
                                    class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-xs font-bold text-white bg-orange-500 hover:bg-orange-600 shadow-sm transition active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
                                <svg x-show="busy[s.id]" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                <span x-text="busy[s.id] ? 'Saving…' : 'Check Out'"></span>
                            </button>
                        </template>
                        <template x-if="s.display_status === 'completed'">
                            <span class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Done
                            </span>
                        </template>
                        <template x-if="s.off || s.display_status === 'not_scheduled'">
                            <span class="text-xs text-gray-400 dark:text-gray-600 font-medium">No action needed</span>
                        </template>
                        <button type="button" x-show="canMark && !s.off && s.display_status !== 'not_scheduled'"
                                @click="openCorrection(s)" title="Manual attendance correction"
                                class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 hover:text-gray-700 transition dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-slate-800 dark:hover:text-gray-200 active:scale-95">
                            More
                        </button>
                    </div>
                </div>
            </template>

            <!-- Filtered-empty state -->
            <div x-show="filtered.length === 0" class="py-14 text-center">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No staff match your search or filter.</p>
                <button type="button" @click="search = ''; filter = 'all'"
                        class="mt-3 py-2 px-4 rounded-xl text-xs font-bold bg-gray-900 text-white hover:bg-gray-800 transition dark:bg-white dark:text-gray-900 active:scale-95">
                    Clear filters
                </button>
            </div>
        </div>
    </div>

    <!-- ================= Manual Correction Modal (Preline-style overlay, Alpine-driven) ================= -->
    <div x-show="correctionOpen" x-transition.opacity @keydown.escape.window="correctionOpen = false"
         class="fixed inset-0 z-[70] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Manual attendance correction">
        <div class="absolute inset-0 bg-gray-900/50 dark:bg-black/60 backdrop-blur-sm" @click="correctionOpen = false"></div>

        <div x-show="correctionOpen" x-transition.scale.origin-center
             class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
            <!-- Header -->
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Manual Attendance Correction</h3>
                <button type="button" @click="correctionOpen = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 dark:hover:text-gray-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Body -->
            <div class="px-5 py-4 space-y-4" x-show="correcting">
                <!-- Audit warning -->
                <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/15 dark:border-amber-800/60 px-3.5 py-3">
                    <svg class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <p class="text-xs leading-relaxed text-amber-800 dark:text-amber-300">
                        This is a <strong>manual correction</strong>, not a real-time check-in. It will be written to the
                        audit log with your name, the time of correction, and the reason.
                    </p>
                </div>

                <!-- Staff context -->
                <div class="rounded-xl bg-gray-50 dark:bg-slate-800/50 px-3.5 py-3 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate" x-text="correcting ? correcting.name : ''"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="correcting ? (correcting.shift ?? 'No schedule') : ''"></p>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 tabular-nums shrink-0">
                        <span x-text="correcting && correcting.check_in ? correcting.check_in : '—'"></span>
                        <span class="text-gray-300 dark:text-gray-600">→</span>
                        <span x-text="correcting && correcting.check_out ? correcting.check_out : '—'"></span>
                    </p>
                </div>

                <form id="attCorrectionForm" @submit.prevent="submitCorrection" class="space-y-4">
                    <div>
                        <label class="block mb-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Correction Type</label>
                        <select x-model="corr.type" required
                                class="py-2.5 px-3 block w-full border border-gray-200 rounded-xl text-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-slate-800 dark:border-gray-700 dark:text-gray-200">
                            <template x-if="correcting && correcting.check_in">
                                <option value="check_in">Adjust check-in time</option>
                            </template>
                            <template x-if="correcting && !correcting.check_in">
                                <option value="check_in">Record missed check-in</option>
                            </template>
                            <template x-if="correcting && correcting.check_out">
                                <option value="check_out">Adjust check-out time</option>
                            </template>
                            <template x-if="correcting && !correcting.check_out">
                                <option value="check_out">Record missed check-out</option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actual Time</label>
                        <input type="time" x-model="corr.time" required
                               class="py-2.5 px-3 block w-full border border-gray-200 rounded-xl text-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-slate-800 dark:border-gray-700 dark:text-gray-200">
                    </div>

                    <div>
                        <label class="block mb-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reason</label>
                        <select x-model="corr.reason" required
                                class="py-2.5 px-3 block w-full border border-gray-200 rounded-xl text-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-slate-800 dark:border-gray-700 dark:text-gray-200">
                            <option value="" disabled>Select a reason…</option>
                            <option>Forgot to check in</option>
                            <option>Forgot to check out</option>
                            <option>System or device issue</option>
                            <option>Off-site work assignment</option>
                            <option>Approved by manager</option>
                            <option>Other</option>
                        </select>
                    </div>

                    <div x-show="corr.reason === 'Other'">
                        <label class="block mb-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Specify Reason</label>
                        <input type="text" x-model="corr.reasonOther" maxlength="255" placeholder="Briefly describe the reason…"
                               class="py-2.5 px-3 block w-full border border-gray-200 rounded-xl text-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-slate-800 dark:border-gray-700 dark:text-gray-200">
                    </div>

                    <p x-show="corr.error" x-text="corr.error" class="text-xs font-semibold text-red-600 dark:text-red-400"></p>

                    <div class="flex items-center justify-between gap-3 pt-1">
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 leading-snug">
                            Recorded by {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}<br>in the audit trail.
                        </p>
                        <div class="flex gap-2 shrink-0">
                            <button type="button" @click="correctionOpen = false"
                                    class="py-2.5 px-4 rounded-xl text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition">
                                Cancel
                            </button>
                            <button type="submit" :disabled="submitting"
                                    class="py-2.5 px-5 inline-flex items-center gap-2 rounded-xl text-sm font-bold text-white bg-gray-900 hover:bg-gray-800 transition active:scale-95 disabled:opacity-60 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                                <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                <span x-text="submitting ? 'Saving…' : 'Save Correction'"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/* SweetAlert2 is loaded globally by the master layout — no second include needed.
   It is used ONLY for AJAX feedback + the correction confirmation. Normal flash
   messages still flow through the layout's session('success')/session('error') toasts. */
const ATT_CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

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
        background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1e293b',
    });
}

document.addEventListener('alpine:init', () => {
    const BADGES = {
        pending:       { label: 'Pending',     cls: 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/20 dark:text-amber-300' },
        checked_in:    { label: 'Checked In',  cls: 'bg-teal-50 text-teal-700 ring-teal-600/20 dark:bg-teal-900/20 dark:text-teal-300' },
        late:          { label: 'Late',        cls: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/20 dark:text-red-300' },
        completed:     { label: 'Completed',   cls: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-300' },
        off_leave:     { label: 'Off / Leave', cls: 'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-800 dark:text-slate-300' },
        not_scheduled: { label: 'No Schedule', cls: 'bg-gray-50 text-gray-500 ring-gray-400/20 dark:bg-slate-800/60 dark:text-slate-400' },
    };
    const FILTERS = [['all', 'All'], ['pending', 'Pending'], ['checked_in', 'Checked In'], ['late', 'Late'], ['completed', 'Completed'], ['off_leave', 'Off / Leave']];

    Alpine.data('attendanceBoard', (cfg) => ({
        staff: cfg.staff,
        urls: cfg.urls,
        canMark: cfg.canMark,
        filters: FILTERS,
        search: '',
        filter: 'all',
        busy: {},
        correctionOpen: false,
        correcting: null,
        corr: { type: 'check_in', time: '', reason: '', reasonOther: '', error: '' },
        submitting: false,

        get list() { return Object.values(this.staff); },
        count(f) {
            return f === 'all'
                ? this.list.filter(s => s.display_status !== 'not_scheduled').length
                : this.list.filter(s => s.display_status === f).length;
        },
        get filtered() {
            const q = this.search.trim().toLowerCase();
            return this.list.filter(s => {
                if (this.filter !== 'all' && s.display_status !== this.filter) return false;
                if (q && !s.name.toLowerCase().includes(q) && !s.username.toLowerCase().includes(q)) return false;
                return true;
            });
        },
        get summary() {
            const s = { scheduled: 0, checkedIn: 0, late: 0, pending: 0, off: 0 };
            for (const m of this.list) {
                if (m.display_status === 'not_scheduled') continue;
                s.scheduled++;
                if (m.display_status === 'off_leave') s.off++;
                else if (m.display_status === 'pending') s.pending++;
                else { s.checkedIn++; if (m.display_status === 'late') s.late++; }
            }
            return s;
        },
        get attention() {
            return this.list
                .filter(m => m.display_status === 'late' || m.display_status === 'pending')
                .sort((a, b) => a.display_status === b.display_status
                    ? a.name.localeCompare(b.name)
                    : (a.display_status === 'late' ? -1 : 1));
        },
        badge(m) { return BADGES[m.display_status] || BADGES.pending; },
        pillCls(f) {
            const base = 'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-inset transition active:scale-95 ';
            return this.filter === f
                ? base + 'bg-brand-600 text-white ring-brand-600 dark:bg-brand-500 dark:ring-brand-500'
                : base + 'bg-white text-gray-600 ring-gray-200 hover:bg-gray-50 dark:bg-slate-900 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-slate-800';
        },
        setFilter(f) { this.filter = this.filter === f ? 'all' : f; },

        /* Shared POST helper — duplicate-click safe, surfaces the REAL server message. */
        async post(url, id) {
            if (this.busy[id]) return null;
            this.busy[id] = true;
            try {
                const res = await fetch(url.replace('__ID__', id), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': ATT_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.success === false) {
                    attToast(data.message || ('Request failed (' + res.status + '). Please try again.'), 'error');
                    return null;
                }
                return data;
            } catch (e) {
                attToast('Network error — check your connection and try again.', 'error');
                return null;
            } finally {
                this.busy[id] = false;
            }
        },

        async checkIn(id) {
            const data = await this.post(this.urls.checkin, id);
            if (!data) return;
            const m = this.staff[id];
            m.check_in = data.check_in;
            m.check_out = data.check_out;
            m.display_status = data.display_status;
            attToast(data.message, 'success');
        },

        async checkOut(id) {
            const data = await this.post(this.urls.checkout, id);
            if (!data) return;
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
                const el = document.getElementById('att-row-' + id);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('ring-2', 'ring-amber-400', 'dark:ring-amber-500');
                    setTimeout(() => el.classList.remove('ring-2', 'ring-amber-400', 'dark:ring-amber-500'), 2000);
                }
            });
        },

        openCorrection(m) {
            if (!this.canMark) {
                attToast('You are not allowed to record attendance corrections.', 'error');
                return;
            }
            this.correcting = m;
            const pad = (n) => String(n).padStart(2, '0');
            const now = new Date();
            const type = m.check_in ? 'check_out' : 'check_in';
            let time = pad(now.getHours()) + ':' + pad(now.getMinutes());
            if (type === 'check_in' && m.shift) {
                const start = m.shift.split('–')[0].trim();
                const parsed = new Date('1970-01-01 ' + start);
                if (!isNaN(parsed)) time = pad(parsed.getHours()) + ':' + pad(parsed.getMinutes());
            }
            this.corr = { type: type, time: time, reason: '', reasonOther: '', error: '' };
            this.correctionOpen = true;
        },

        async submitCorrection() {
            const m = this.correcting;
            if (!m || this.submitting) return;
            const reason = this.corr.reason === 'Other' ? this.corr.reasonOther.trim() : this.corr.reason;
            if (!this.corr.time) { this.corr.error = 'Please specify the actual time.'; return; }
            if (!reason) { this.corr.error = 'Please select a reason for this correction.'; return; }
            if (this.corr.reason === 'Other' && !this.corr.reasonOther.trim()) { this.corr.error = 'Please specify the reason.'; return; }
            this.corr.error = '';

            const confirm = await Swal.fire({
                icon: 'warning',
                title: 'Record manual correction?',
                html: 'This will update <b>' + m.name + '</b>\'s attendance and be stored in the audit log.',
                showCancelButton: true,
                confirmButtonText: 'Yes, save correction',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#0d9488',
                background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#1e293b',
            });
            if (!confirm.isConfirmed) return;

            this.submitting = true;
            try {
                const res = await fetch(this.urls.correct.replace('__ID__', m.id), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': ATT_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: this.corr.type, time: this.corr.time, reason: reason }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.success === false) {
                    this.corr.error = data.message || 'The correction could not be saved. Please try again.';
                    return;
                }
                m.check_in = data.check_in;
                m.check_out = data.check_out;
                m.display_status = data.display_status;
                this.correctionOpen = false;
                attToast(data.message, 'success');
            } catch (e) {
                this.corr.error = 'Network error — check your connection and try again.';
            } finally {
                this.submitting = false;
            }
        },
    }));
});
</script>
@endpush