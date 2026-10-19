@extends('layouts.staff')

@section('title', 'Staff Dashboard')

@section('content')
<div class="space-y-6">

    {{-- ═══════════════════════════════════════════════════════════
         1. HERO HEADER: Greeting, Shift Hours & Attendance Status
    ════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            @php
                $hour = (int) now('Asia/Manila')->format('G');
                $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
            @endphp
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 border border-brand-200/60 dark:border-brand-800/40">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse"></span>
                    Therapist Daily Treatment Sheet
                </span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-2 tracking-tight">
                {{ $greeting }}, {{ auth()->user()->first_name }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ now('Asia/Manila')->format('l, F j, Y') }} &mdash;
                <span class="font-medium text-gray-700 dark:text-gray-300">
                    {{ $todayAssignedCount }} {{ Str::plural('session', $todayAssignedCount) }} assigned today
                </span>
                @if($todayCompletedCount > 0)
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">({{ $todayCompletedCount }} completed)</span>
                @endif
            </p>
        </div>

        {{-- Shift & Attendance Indicators --}}
        <div class="flex flex-wrap items-center gap-3 shrink-0">
            {{-- Shift Hours Badge --}}
            <div class="px-3.5 py-2 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-200/80 dark:border-gray-600/50 flex items-center gap-2 text-xs">
                <i data-lucide="clock" class="w-4 h-4 text-brand-600 dark:text-brand-400"></i>
                <div>
                    <p class="text-[10px] text-gray-400 dark:text-gray-400 uppercase font-semibold">Today's Shift</p>
                    <p class="font-bold text-gray-800 dark:text-gray-200">{{ $shiftInfo['label'] }}</p>
                </div>
            </div>

            {{-- Attendance Status Badge --}}
            @php
                $attStatus = $todayAttendance?->status;
                $checkInTime = $todayAttendance?->check_in ? \Carbon\Carbon::parse($todayAttendance->check_in)->format('g:i A') : null;
            @endphp
            <div class="px-3.5 py-2 rounded-xl border flex items-center gap-2 text-xs
                @if($attStatus === 'present')
                    bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-300
                @elseif($attStatus === 'late')
                    bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800/50 text-amber-800 dark:text-amber-300
                @elseif($attStatus === 'on_leave' || $attStatus === 'holiday')
                    bg-blue-50 dark:bg-blue-950/40 border-blue-200 dark:border-blue-800/50 text-blue-800 dark:text-blue-300
                @else
                    bg-gray-50 dark:bg-gray-700/50 border-gray-200/80 dark:border-gray-600/50 text-gray-700 dark:text-gray-300
                @endif">
                <i data-lucide="user-check" class="w-4 h-4
                    @if($attStatus === 'present') text-emerald-600 dark:text-emerald-400
                    @elseif($attStatus === 'late') text-amber-600 dark:text-amber-400
                    @else text-gray-400 @endif"></i>
                <div>
                    <p class="text-[10px] uppercase font-semibold text-gray-400 dark:text-gray-400">Attendance</p>
                    <p class="font-bold">
                        @if($attStatus === 'present')
                            Present {{ $checkInTime ? "($checkInTime)" : '' }}
                        @elseif($attStatus === 'late')
                            Late {{ $checkInTime ? "($checkInTime)" : '' }}
                        @elseif($attStatus === 'on_leave')
                            On Leave
                        @elseif($attStatus === 'holiday')
                            Holiday
                        @else
                            Not Yet Logged
                        @endif
                    </p>
                </div>
            </div>

            {{-- Quick Schedule Link --}}
            <a href="{{ route('staff.schedule') }}"
               class="px-3 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700/60 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold flex items-center gap-1.5 transition-colors">
                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                <span>Weekly Schedule</span>
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         2. FOUR USEFUL OPERATIONAL KPI TILES
    ════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">

        {{-- KPI 1: Today's Assigned Bookings --}}
        <a href="{{ route('staff.appointments', ['date' => now('Asia/Manila')->toDateString()]) }}"
           class="group bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm hover:border-brand-300 dark:hover:border-brand-700/60 transition-all hover:shadow-md flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center text-brand-600 dark:text-brand-400 group-hover:scale-105 transition-transform">
                    <i data-lucide="calendar-check" class="w-4 h-4"></i>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300">
                    Today
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">{{ $todayAssignedCount }}</p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5 group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">Today's Bookings</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 leading-snug">Total treatments assigned</p>
            </div>
        </a>

        {{-- KPI 2: Completed Today --}}
        <a href="{{ route('staff.appointments', ['date' => now('Asia/Manila')->toDateString(), 'status' => 'completed']) }}"
           class="group bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm hover:border-emerald-300 dark:hover:border-emerald-700/60 transition-all hover:shadow-md flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition-transform">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                    Finished
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">{{ $todayCompletedCount }}</p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Completed Today</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 leading-snug">Services rendered</p>
            </div>
        </a>

        {{-- KPI 3: Remaining Confirmed Sessions --}}
        <div class="group bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <i data-lucide="hourglass" class="w-4 h-4"></i>
                </div>
                @if($remainingConfirmedCount > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                        In queue
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                        Clear
                    </span>
                @endif
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">{{ $remainingConfirmedCount }}</p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5">Remaining Sessions</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 leading-snug">Confirmed clients awaiting service</p>
            </div>
        </div>

        {{-- KPI 4: Weekly Completed Sessions --}}
        <a href="{{ route('staff.appointments', ['status' => 'completed', 'date_from' => $weekStart->toDateString(), 'date_to' => $weekEnd->toDateString()]) }}"
           class="group bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm hover:border-purple-300 dark:hover:border-purple-700/60 transition-all hover:shadow-md flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:scale-105 transition-transform">
                    <i data-lucide="award" class="w-4 h-4"></i>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">
                    This Week
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">{{ $weeklyCompleted }}</p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">Weekly Completed</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 leading-snug">Monday to Sunday total</p>
            </div>
        </a>

    </div>

    {{-- ═══════════════════════════════════════════════════════════
         3. NEXT / CURRENT SESSION HIGHLIGHT
    ════════════════════════════════════════════════════════════════ --}}
    @if($nextSession)
        @php
            $nextPhone = $nextSession->guest_phone ?? $nextSession->customer?->phone_number ?? null;
            $nextServices = $nextSession->services->map(fn($s) => $s->pivot->service_name ?: $s->name)->filter()->join(', ');
            $nextDuration = $nextSession->services->sum(fn($s) => $s->pivot->service_duration ?: $s->duration_minutes);
            $hasNotes = $nextSession->customer && !empty($nextSession->customer->medical_notes);
            $nowTime = now('Asia/Manila')->format('H:i:s');
            $isInProgress = $nextSession->start_time <= $nowTime && $nextSession->end_time > $nowTime;
        @endphp
        <div class="rounded-2xl border {{ $isInProgress ? 'border-brand-500 dark:border-brand-500 bg-brand-50/50 dark:bg-brand-950/30' : 'border-gray-200/80 dark:border-gray-700/50 bg-white dark:bg-gray-800/80' }} p-5 sm:p-6 shadow-sm transition-all">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl {{ $isInProgress ? 'bg-brand-500 text-white animate-pulse' : 'bg-teal-50 dark:bg-teal-900/40 text-brand-600 dark:text-brand-400' }} flex items-center justify-center shrink-0">
                        <i data-lucide="{{ $isInProgress ? 'sparkles' : 'calendar-clock' }}" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $isInProgress ? 'bg-brand-600 text-white' : 'bg-teal-100 dark:bg-teal-900/50 text-teal-800 dark:text-teal-300' }}">
                                {{ $isInProgress ? '● In Progress' : 'Next Up' }}
                            </span>
                            <span class="text-sm font-bold text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($nextSession->start_time)->format('g:i A') }} &ndash; {{ \Carbon\Carbon::parse($nextSession->end_time)->format('g:i A') }}
                                ({{ $nextDuration }} mins)
                            </span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $nextSession->client_name }}
                        </h2>
                        <div class="flex items-center gap-3 mt-1 text-xs text-gray-600 dark:text-gray-300 flex-wrap">
                            <span class="inline-flex items-center gap-1 font-medium">
                                <i data-lucide="tag" class="w-3.5 h-3.5 text-gray-400"></i>
                                {{ $nextServices ?: 'Spa Service' }}
                            </span>
                            <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                            <span class="inline-flex items-center gap-1">
                                <i data-lucide="door-closed" class="w-3.5 h-3.5 text-gray-400"></i>
                                <span class="font-semibold">{{ $nextSession->room?->name ?? 'No room assigned' }}</span>
                            </span>
                            @if($nextPhone)
                                <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                                <a href="tel:{{ $nextPhone }}" class="inline-flex items-center gap-1 text-brand-600 dark:text-brand-400 hover:underline">
                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                    <span>{{ $nextPhone }}</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Action / Notes Indicator --}}
                <div class="flex items-center gap-3 shrink-0">
                    @if($hasNotes)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 text-xs font-bold text-rose-700 dark:text-rose-300">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-500"></i>
                            <span>Medical Notes Attached</span>
                        </span>
                    @endif
                </div>
            </div>

            {{-- Medical notes alert box if present --}}
            @if($hasNotes)
                <div class="mt-4 p-3.5 rounded-xl bg-rose-50/80 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-xs text-rose-900 dark:text-rose-200">
                    <p class="font-bold flex items-center gap-1 mb-0.5">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400"></i>
                        Client Medical Advisory:
                    </p>
                    <p class="text-rose-800 dark:text-rose-300 pl-4">{{ $nextSession->customer->medical_notes }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         4. TWO-COLUMN WORKSPACE: TODAY'S SCHEDULE & UPCOMING
    ════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- ─────────────────────────────────────────────────────────
             LEFT: TODAY'S TREATMENT SCHEDULE (7 of 12 cols)
        ────────────────────────────────────────────────────────── --}}
        <div class="lg:col-span-7 bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-5 border-b border-gray-200/80 dark:border-gray-700/50 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center text-brand-600 dark:text-brand-400">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Today's Treatment Schedule</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Chronological treatment feed for {{ now('Asia/Manila')->format('M j') }}</p>
                    </div>
                </div>
                <a href="{{ route('staff.appointments', ['date' => now('Asia/Manila')->toDateString()]) }}"
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300">
                    <span>Full Schedule</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="p-4 flex-1">
                @if(isset($myToday) && $myToday->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($myToday as $appointment)
                            @php
                                $isCompleted = $appointment->status === 'completed';
                                $isConfirmed = $appointment->status === 'confirmed';
                                $isPending = $appointment->status === 'pending';
                                $isCancelled = $appointment->status === 'cancelled';
                                $customerPhone = $appointment->guest_phone ?? $appointment->customer?->phone_number ?? null;
                                $serviceNames = $appointment->services->map(fn($s) => $s->pivot->service_name ?: $s->name)->filter()->join(', ');
                                $durationMins = $appointment->services->sum(fn($s) => $s->pivot->service_duration ?: $s->duration_minutes);
                                $hasNotes = $appointment->customer && !empty($appointment->customer->medical_notes);
                            @endphp
                            <div class="p-3.5 rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50/50 dark:bg-gray-800/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors flex flex-col justify-between gap-3">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-start gap-3 min-w-0">
                                        {{-- Time Badge --}}
                                        <div class="shrink-0 px-2.5 py-1.5 rounded-lg bg-white dark:bg-gray-700/60 border border-gray-200/80 dark:border-gray-600/50 text-center min-w-[70px]">
                                            <p class="text-xs font-bold text-gray-800 dark:text-gray-200 tabular-nums">
                                                {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}
                                            </p>
                                            <p class="text-[10px] text-gray-400 dark:text-gray-500 tabular-nums">
                                                {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
                                            </p>
                                        </div>

                                        {{-- Client & Services --}}
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                                    {{ $appointment->client_name }}
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
                                                {{ $serviceNames ?: 'Spa Service' }}
                                                @if($durationMins > 0)
                                                    <span class="text-gray-400">({{ $durationMins }}m)</span>
                                                @endif
                                            </p>

                                            <div class="flex items-center gap-3 mt-1 text-[11px] text-gray-500 dark:text-gray-400 flex-wrap">
                                                <span class="inline-flex items-center gap-1">
                                                    <i data-lucide="door-closed" class="w-3 h-3 text-gray-400"></i>
                                                    <span class="font-medium">{{ $appointment->room?->name ?? 'No room assigned' }}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Status Pill & Notes Action --}}
                                    <div class="shrink-0 flex items-center sm:flex-col sm:items-end justify-between sm:justify-center gap-1.5 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100 dark:border-gray-700/40">
                                        @if($isCompleted)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/40">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Completed
                                            </span>
                                        @elseif($isConfirmed)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border border-teal-200/60 dark:border-teal-800/40">
                                                <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse"></span>
                                                Confirmed
                                            </span>
                                        @elseif($isPending)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/40">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Pending Review
                                            </span>
                                        @elseif($isCancelled)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                                Cancelled
                                            </span>
                                        @endif

                                        {{-- Medical Notes Button --}}
                                        @if($hasNotes)
                                            <button type="button"
                                                    onclick="document.getElementById('notes-{{ $appointment->id }}').classList.toggle('hidden')"
                                                    class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 hover:underline">
                                                <i data-lucide="file-text" class="w-3 h-3"></i>
                                                <span>Medical Notes</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- Expandable Medical Notes Section --}}
                                @if($hasNotes)
                                    <div id="notes-{{ $appointment->id }}" class="hidden mt-1 p-3 rounded-xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-200/70 dark:border-rose-800/50 text-xs text-rose-900 dark:text-rose-200">
                                        <p class="font-bold flex items-center gap-1 mb-0.5">
                                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-rose-500"></i>
                                            Client Medical Note:
                                        </p>
                                        <p class="text-rose-800 dark:text-rose-300 pl-4">{{ $appointment->customer->medical_notes }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="py-12 px-4 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-700/60 flex items-center justify-center mx-auto text-gray-400 mb-3">
                            <i data-lucide="calendar" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200">No Appointments Assigned Today</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                            You currently have no client treatments scheduled on today's calendar. Check your weekly schedule for upcoming shifts.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('staff.schedule') }}"
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-xs transition-colors">
                                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                <span>View Weekly Shift Calendar</span>
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ─────────────────────────────────────────────────────────
             RIGHT: UPCOMING APPOINTMENTS PREVIEW (5 of 12 cols)
        ────────────────────────────────────────────────────────── --}}
        <div class="lg:col-span-5 bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-5 border-b border-gray-200/80 dark:border-gray-700/50 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center text-brand-600 dark:text-brand-400">
                        <i data-lucide="calendar-days" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Upcoming Bookings</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Next 5 confirmed treatments</p>
                    </div>
                </div>
                <a href="{{ route('staff.appointments') }}"
                   class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300">
                    <span>View All</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="p-4 flex-1">
                @if(isset($myUpcoming) && $myUpcoming->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($myUpcoming as $appointment)
                            @php
                                $apptDate = \Carbon\Carbon::parse($appointment->appointment_date);
                                $serviceNames = $appointment->services->map(fn($s) => $s->pivot->service_name ?: $s->name)->filter()->join(', ');
                                $hasNotes = $appointment->customer && !empty($appointment->customer->medical_notes);
                            @endphp
                            <div class="p-3.5 rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50/50 dark:bg-gray-800/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-all flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    {{-- Date & Time Badge --}}
                                    <div class="shrink-0 px-2.5 py-1.5 rounded-lg bg-white dark:bg-gray-700/60 border border-gray-200/80 dark:border-gray-600/50 text-center min-w-[65px]">
                                        <p class="text-xs font-bold text-gray-800 dark:text-gray-200 tabular-nums">
                                            {{ $apptDate->format('M j') }}
                                        </p>
                                        <p class="text-[10px] text-gray-400 dark:text-gray-500 tabular-nums">
                                            {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}
                                        </p>
                                    </div>

                                    {{-- Client & Service info --}}
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                                {{ $appointment->client_name }}
                                            </span>
                                            @if($hasNotes)
                                                <span class="text-rose-500 shrink-0" title="Client has medical notes">
                                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5" title="{{ $serviceNames }}">
                                            {{ $serviceNames ?: 'Spa Service' }}
                                        </p>
                                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                            Room: {{ $appointment->room?->name ?? 'TBD' }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Status badge --}}
                                <div class="shrink-0">
                                    @if($appointment->status === 'confirmed')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300">
                                            Confirmed
                                        </span>
                                    @elseif($appointment->status === 'pending')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                                            Pending
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/50 text-center">
                        <a href="{{ route('staff.appointments') }}"
                           class="text-xs font-semibold text-brand-600 dark:text-brand-400 hover:underline">
                            See full appointment history &rarr;
                        </a>
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="py-12 px-4 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-700/60 flex items-center justify-center mx-auto text-gray-400 mb-3">
                            <i data-lucide="calendar-check" class="w-6 h-6 text-emerald-500"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200">No Upcoming Bookings</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-xs mx-auto">
                            You have no future bookings scheduled after today. New client assignments will be displayed here automatically.
                        </p>
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection