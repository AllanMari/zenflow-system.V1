@extends('layouts.receptionist')

@section('title', 'Front Desk Dashboard')

@section('content')
<div class="space-y-6">

    {{-- ═══════════════════════════════════════════════════════════
         1. HERO SECTION & QUICK ACTIONS BAR
    ════════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-6 shadow-sm">
        <div>
            @php
                $hour = (int) now('Asia/Manila')->format('G');
                $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
            @endphp
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 border border-brand-200/60 dark:border-brand-800/40">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse"></span>
                    Front Desk Operations
                </span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-2 tracking-tight">
                {{ $greeting }}, {{ auth()->user()->first_name }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ now('Asia/Manila')->format('l, F j, Y') }} &mdash;
                <span class="font-medium text-gray-700 dark:text-gray-300">
                    {{ $stats['confirmed_today'] }} {{ Str::plural('appointment', $stats['confirmed_today']) }} scheduled today
                </span>
                @if($stats['completed_today'] > 0)
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">({{ $stats['completed_today'] }} completed)</span>
                @endif
            </p>
        </div>

        {{-- Quick Actions Group --}}
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a href="{{ route('receptionist.quick-book') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-sm transition-all duration-150 hover:shadow-brand-500/20 active:scale-[0.98]">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Quick Book / Walk-In</span>
            </a>

            <a href="{{ route('receptionist.active') }}"
               class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700/60 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold transition-all">
                <i data-lucide="calendar-check" class="w-4 h-4 text-brand-600 dark:text-brand-400"></i>
                <span>Active Sessions</span>
            </a>

            <a href="{{ route('receptionist.pending') }}"
               class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700/60 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold transition-all">
                <i data-lucide="inbox" class="w-4 h-4 text-amber-500"></i>
                <span>Pending Review</span>
            </a>

            @if(auth()->user()->can_view_room_tracking)
                <a href="{{ route('receptionist.room-tracking') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700/60 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold transition-all">
                    <i data-lucide="door-open" class="w-4 h-4 text-indigo-500"></i>
                    <span>Room Board</span>
                </a>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         2. CONDITIONAL THERAPIST-ABSENCE ALERT BANNER
    ════════════════════════════════════════════════════════════════ --}}
    @if(isset($absentStaffConflicts) && $absentStaffConflicts->isNotEmpty())
        <div class="rounded-2xl border border-amber-300 dark:border-amber-700/60 bg-amber-50 dark:bg-amber-950/40 p-4 sm:p-5 shadow-sm transition-all">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center shrink-0 text-amber-600 dark:text-amber-400">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <h2 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                            Therapist Absence Alert &mdash; {{ $absentStaffConflicts->count() }} Confirmed {{ Str::plural('Appointment', $absentStaffConflicts->count()) }} Affected Today
                        </h2>
                        <a href="{{ route('receptionist.active') }}"
                           class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-800 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-100 underline underline-offset-2">
                            <span>Open Active Sessions to Reassign</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                    <p class="text-xs text-amber-800/90 dark:text-amber-300/90 mt-1">
                        One or more therapists scheduled for today are logged as absent or on leave. Please review and reassign these appointments to ensure seamless guest service:
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($absentStaffConflicts as $conflict)
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/80 dark:bg-gray-800/80 border border-amber-200 dark:border-amber-800/50 text-xs shadow-xs">
                                <span class="font-semibold text-gray-800 dark:text-gray-200">
                                    {{ \Carbon\Carbon::parse($conflict->start_time)->format('g:i A') }}
                                </span>
                                <span class="text-gray-400">&bull;</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                    {{ $conflict->client_name }}
                                </span>
                                <span class="text-gray-400">&bull;</span>
                                <span class="text-amber-700 dark:text-amber-400 font-medium">
                                    Assigned: {{ $conflict->staff ? trim($conflict->staff->first_name . ' ' . $conflict->staff->last_name) : 'Unassigned' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         3. FIVE OPERATIONAL KPI TILES
    ════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 sm:gap-4">

        {{-- KPI 1: Pending Review --}}
        <a href="{{ route('receptionist.pending') }}"
           class="group bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm hover:border-amber-300 dark:hover:border-amber-700/60 transition-all hover:shadow-md flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:scale-105 transition-transform">
                    <i data-lucide="inbox" class="w-4 h-4"></i>
                </div>
                @if($stats['pending'] > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 animate-pulse">
                        Action required
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                        All clear
                    </span>
                @endif
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">{{ $stats['pending'] }}</p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Pending Review</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 leading-snug">Valid upcoming bookings</p>
            </div>
        </a>

        {{-- KPI 2: Today's Schedule --}}
        <a href="{{ route('receptionist.active') }}"
           class="group bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm hover:border-brand-300 dark:hover:border-brand-700/60 transition-all hover:shadow-md flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center text-brand-600 dark:text-brand-400 group-hover:scale-105 transition-transform">
                    <i data-lucide="calendar-check" class="w-4 h-4"></i>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300">
                    Confirmed
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">{{ $stats['confirmed_today'] }}</p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5 group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">Today's Schedule</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 leading-snug">
                    @if($stats['completed_today'] > 0)
                        {{ $stats['completed_today'] }} completed today
                    @else
                        Active arrivals today
                    @endif
                </p>
            </div>
        </a>

        {{-- KPI 3: Staff Present --}}
        <a href="{{ route('attendance.today') }}"
           class="group bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm hover:border-emerald-300 dark:hover:border-emerald-700/60 transition-all hover:shadow-md flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition-transform">
                    <i data-lucide="users" class="w-4 h-4"></i>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                    Attendance
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">
                    {{ $stats['present_staff_count'] }}<span class="text-sm font-medium text-gray-400 dark:text-gray-500">/{{ $stats['total_active_staff'] }}</span>
                </p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Staff Present</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 leading-snug">Present & late on duty</p>
            </div>
        </a>

        {{-- KPI 4: Rooms Available --}}
        <div class="group bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <i data-lucide="door-open" class="w-4 h-4"></i>
                </div>
                @if(auth()->user()->can_view_room_tracking)
                    <a href="{{ route('receptionist.room-tracking') }}" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                        View Board &rarr;
                    </a>
                @else
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                        Estimated
                    </span>
                @endif
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">
                    {{ $stats['available_rooms'] }}<span class="text-sm font-medium text-gray-400 dark:text-gray-500">/{{ $stats['total_active_rooms'] }}</span>
                </p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5">Rooms Free Now</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 leading-snug" title="Estimated rooms free right now based on confirmed appointments; does not guarantee future slot availability">
                    Estimated free right now
                </p>
            </div>
        </div>

        {{-- KPI 5: Today's Collections --}}
        <a href="{{ route('receptionist.sales') }}"
           class="group bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm hover:border-blue-300 dark:hover:border-blue-700/60 transition-all hover:shadow-md flex flex-col justify-between col-span-2 sm:col-span-1">
            <div class="flex items-start justify-between">
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-105 transition-transform">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                    Net Cash
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">
                    ₱{{ number_format($stats['collections_today'], 2) }}
                </p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Today's Collections</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 leading-snug">Deposits & completions received</p>
            </div>
        </a>

    </div>

    {{-- ═══════════════════════════════════════════════════════════
         4. TWO-COLUMN OPERATIONAL AREA
    ════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- ─────────────────────────────────────────────────────────
             LEFT: TODAY'S SCHEDULE & ARRIVALS (7 of 12 cols on desktop)
        ────────────────────────────────────────────────────────── --}}
        <div class="lg:col-span-7 bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-5 border-b border-gray-200/80 dark:border-gray-700/50 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center text-brand-600 dark:text-brand-400">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Today's Schedule & Arrivals</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Chronological schedule for {{ now('Asia/Manila')->format('M j') }}</p>
                    </div>
                </div>
                <a href="{{ route('receptionist.active') }}"
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors">
                    <span>Manage in Active</span>
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="p-4 flex-1">
                @if(isset($todaySchedule) && $todaySchedule->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($todaySchedule as $appt)
                            @php
                                $isCompleted = $appt->status === 'completed';
                                $customerPhone = $appt->guest_phone ?? $appt->customer?->phone_number ?? null;
                                $serviceNames = $appt->services->map(fn($s) => $s->pivot->service_name ?: $s->name)->filter()->join(', ');
                                $therapistName = $appt->staff ? trim($appt->staff->first_name . ' ' . $appt->staff->last_name) : null;
                            @endphp
                            <div class="p-3.5 rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50/50 dark:bg-gray-800/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0">
                                    {{-- Time badge --}}
                                    <div class="shrink-0 px-2.5 py-1.5 rounded-lg bg-white dark:bg-gray-700/60 border border-gray-200/80 dark:border-gray-600/50 text-center min-w-[70px]">
                                        <p class="text-xs font-bold text-gray-800 dark:text-gray-200 tabular-nums">
                                            {{ \Carbon\Carbon::parse($appt->start_time)->format('g:i A') }}
                                        </p>
                                        <p class="text-[10px] text-gray-400 dark:text-gray-500 tabular-nums">
                                            {{ \Carbon\Carbon::parse($appt->end_time)->format('g:i A') }}
                                        </p>
                                    </div>

                                    {{-- Client & Service info --}}
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                                {{ $appt->client_name }}
                                            </span>
                                            @if($customerPhone)
                                                <a href="tel:{{ $customerPhone }}"
                                                   class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 dark:text-brand-400 hover:underline">
                                                    <i data-lucide="phone" class="w-3 h-3"></i>
                                                    <span>{{ $customerPhone }}</span>
                                                </a>
                                            @endif
                                        </div>

                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5 truncate" title="{{ $serviceNames }}">
                                            <span class="font-medium text-gray-500 dark:text-gray-400">Services:</span>
                                            {{ $serviceNames ?: '—' }}
                                        </p>

                                        <div class="flex items-center gap-3 mt-1 text-[11px] text-gray-500 dark:text-gray-400 flex-wrap">
                                            <span class="inline-flex items-center gap-1">
                                                <i data-lucide="user" class="w-3 h-3 text-gray-400"></i>
                                                {{ $therapistName ?: 'Unassigned' }}
                                            </span>
                                            <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                                            <span class="inline-flex items-center gap-1">
                                                <i data-lucide="door-closed" class="w-3 h-3 text-gray-400"></i>
                                                {{ $appt->room?->name ?? 'No room' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Status badge & action link --}}
                                <div class="shrink-0 flex items-center sm:flex-col sm:items-end justify-between sm:justify-center gap-1.5 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100 dark:border-gray-700/40">
                                    @if($isCompleted)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Completed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border border-teal-200/60 dark:border-teal-800/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse"></span>
                                            Confirmed
                                        </span>
                                    @endif

                                    <a href="{{ route('receptionist.active') }}"
                                       class="text-xs font-semibold text-gray-500 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
                                        View Details &rarr;
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="py-12 px-4 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-700/60 flex items-center justify-center mx-auto text-gray-400 mb-3">
                            <i data-lucide="calendar" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200">No Appointments Scheduled Today</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                            There are currently no confirmed appointments on today's calendar. Use Quick Book to register walk-in guests.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('receptionist.quick-book') }}"
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs transition-colors">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Create Walk-In Booking</span>
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ─────────────────────────────────────────────────────────
             RIGHT: PENDING CONFIRMATIONS QUEUE (5 of 12 cols on desktop)
        ────────────────────────────────────────────────────────── --}}
        <div class="lg:col-span-5 bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-5 border-b border-gray-200/80 dark:border-gray-700/50 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <i data-lucide="inbox" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Pending Confirmations</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Awaiting front desk verification</p>
                    </div>
                </div>
                <a href="{{ route('receptionist.pending') }}"
                   class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300">
                    <span>View All</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="p-4 flex-1">
                @if(isset($pending) && $pending->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($pending as $appt)
                            @php
                                $apptDate = \Carbon\Carbon::parse($appt->appointment_date);
                                $isToday = $apptDate->isToday();
                                $serviceNames = $appt->services->map(fn($s) => $s->pivot->service_name ?: $s->name)->filter()->join(', ');
                                $customerPhone = $appt->guest_phone ?? $appt->customer?->phone_number ?? null;
                            @endphp
                            <div class="p-3.5 rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50/50 dark:bg-gray-800/50 hover:border-amber-200 dark:hover:border-amber-800/50 transition-all flex flex-col justify-between gap-2.5">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white truncate block">
                                                {{ $appt->client_name }}
                                            </span>
                                            @if($customerPhone)
                                                <a href="tel:{{ $customerPhone }}" class="inline-flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400 hover:text-brand-600">
                                                    <i data-lucide="phone" class="w-3 h-3"></i>
                                                    <span>{{ $customerPhone }}</span>
                                                </a>
                                            @endif
                                        </div>

                                        @if($isToday)
                                            <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 animate-pulse">
                                                Today
                                            </span>
                                        @else
                                            <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                                {{ $apptDate->format('M j') }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                        <span class="font-medium text-gray-500 dark:text-gray-400">Scheduled:</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">
                                            {{ $apptDate->format('D, M j') }} &bull; {{ \Carbon\Carbon::parse($appt->start_time)->format('g:i A') }}
                                        </span>
                                    </div>

                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $serviceNames }}">
                                        <span>Services:</span> {{ $serviceNames ?: '—' }}
                                    </div>
                                </div>

                                {{-- Action row --}}
                                <div class="pt-2 border-t border-gray-100 dark:border-gray-700/40 flex items-center justify-between">
                                    <div class="text-[11px] text-gray-500">
                                        Total: <span class="font-bold text-gray-900 dark:text-white">₱{{ number_format($appt->total_price ?? 0, 2) }}</span>
                                    </div>

                                    <a href="{{ route('receptionist.pending') }}"
                                       class="inline-flex items-center gap-1 text-xs font-bold text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 hover:underline">
                                        <span>Review & Confirm</span>
                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/50 text-center">
                        <a href="{{ route('receptionist.pending') }}"
                           class="text-xs font-semibold text-brand-600 dark:text-brand-400 hover:underline">
                            See all {{ $stats['pending'] }} pending bookings &rarr;
                        </a>
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="py-12 px-4 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-700/60 flex items-center justify-center mx-auto text-gray-400 mb-3">
                            <i data-lucide="check-circle-2" class="w-6 h-6 text-emerald-500"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200">Pending Queue Clear</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-xs mx-auto">
                            All guest booking requests have been reviewed. New submissions will appear here automatically.
                        </p>
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });
</script>
@endpush
@endsection