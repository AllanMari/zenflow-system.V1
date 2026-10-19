@php
    $fmt = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : '—';
    $totalHours = $totalHours ?? 0;
    $daysWorking = $daysWorking ?? 0;
    $todayDateStr = \Carbon\Carbon::today('Asia/Manila')->toDateString();

    // Check if the viewed week contains today
    $isCurrentWeek = ($weekStart && $weekEnd) ? ($todayDateStr >= $weekStart->toDateString() && $todayDateStr <= $weekEnd->toDateString()) : false;
@endphp

@extends('layouts.staff')

@section('title', 'My Schedule')

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .print-block { display: block !important; }
        body { background: white !important; }
    }
    .print-block { display: none; }
</style>
@endpush

@section('content')
<div class="space-y-6">

    {{-- ═══════════════════════════════════════════════════════════
         1. HERO HEADER: Week Navigation & Controls
    ════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-5 sm:p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 border border-brand-200/60 dark:border-brand-800/40">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-brand-600 dark:text-brand-400"></i>
                        Work Shift & Roster Timeline
                    </span>
                    @if($isCurrentWeek)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300">
                            Current Week
                        </span>
                    @endif
                </div>

                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-2 tracking-tight flex items-center gap-2">
                    My Schedule
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Your scheduled shifts, custom hours, approved leave, and attendance record for
                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $weekLabel }}</span>.
                </p>
            </div>

            {{-- Controls: Prev Week, Today, Next Week, Print --}}
            <div class="flex flex-wrap items-center gap-2.5 shrink-0 no-print">
                <div class="inline-flex items-center bg-gray-50 dark:bg-gray-700/50 border border-gray-200/80 dark:border-gray-600/50 rounded-xl p-1 shadow-sm">
                    <a href="?week_start={{ $prevWeek }}"
                       class="inline-flex items-center justify-center p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-600 hover:text-gray-900 dark:hover:text-white hover:shadow-xs transition-all"
                       title="Previous Week">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </a>

                    <div class="px-3 border-x border-gray-200 dark:border-gray-600/60 text-xs font-bold text-gray-800 dark:text-gray-200 whitespace-nowrap min-w-[130px] text-center tabular-nums">
                        {{ $weekLabel }}
                    </div>

                    <a href="?week_start={{ $nextWeek }}"
                       class="inline-flex items-center justify-center p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-600 hover:text-gray-900 dark:hover:text-white hover:shadow-xs transition-all"
                       title="Next Week">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                </div>

                @if(!$isCurrentWeek)
                    <a href="?week_start={{ \Carbon\Carbon::now('Asia/Manila')->startOfWeek()->toDateString() }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white dark:bg-gray-800 border border-brand-300 dark:border-brand-700 text-brand-700 dark:text-brand-300 rounded-xl hover:bg-brand-50 dark:hover:bg-brand-950/40 transition-colors shadow-xs">
                        <i data-lucide="calendar-check" class="w-3.5 h-3.5"></i>
                        <span>This Week</span>
                    </a>
                @endif

                <button onclick="window.print()"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors shadow-xs">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                    <span class="hidden sm:inline">Print</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         2. WEEKLY SCHEDULE: RESPONSIVE DAY-BY-DAY ROW LIST
    ════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl shadow-sm overflow-hidden">
        {{-- Section Heading & Inline Legend --}}
        <div class="p-4 sm:p-5 border-b border-gray-200/80 dark:border-gray-700/50 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-2">
                <i data-lucide="calendar-range" class="w-4 h-4 text-brand-600 dark:text-brand-400"></i>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Daily Roster Breakdown</h2>
            </div>

            {{-- Legend Chips --}}
            <div class="no-print flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-teal-50 dark:bg-teal-950/40 text-teal-700 dark:text-teal-300 border border-teal-200/70 dark:border-teal-800/40 font-medium">
                    <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                    Regular Shift
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200/70 dark:border-amber-800/40 font-medium">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    Custom Hours
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200/70 dark:border-rose-800/40 font-medium">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    Leave / Sick
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200/70 dark:border-purple-800/40 font-medium">
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    Holiday
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 font-medium">
                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                    Day Off
                </span>
            </div>
        </div>

        {{-- Schedule Rows --}}
        <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
            @if(isset($weeklySchedule) && count($weeklySchedule) > 0)
                @foreach($weeklySchedule as $day)
                    @php
                        $dayDate = \Carbon\Carbon::parse($day['date']);
                        $isToday = $day['date'] === $todayDateStr;
                        $isPast = $day['date'] < $todayDateStr;

                        $isWork = $day['type'] === 'work';
                        $isExc = $day['type'] === 'exception';
                        $isCustom = ($day['exception_type'] ?? '') === 'custom_hours';
                        $isOff = $day['type'] === 'off' || ($isExc && !$isCustom && in_array($day['exception_type'], ['day_off', 'sick_leave', 'urgent_leave', 'holiday']));

                        $hasShift = ($isWork || $isCustom) && $day['start_time'] && $day['end_time'];
                        $shiftDuration = $hasShift ? round(\Carbon\Carbon::parse($day['start_time'])->diffInMinutes(\Carbon\Carbon::parse($day['end_time'])) / 60, 1) : 0;

                        // Static, discoverable container styles
                        $rowBg = 'bg-white hover:bg-gray-50/70 dark:bg-gray-800/80 dark:hover:bg-gray-700/30';
                        $borderAccent = 'border-l-4 border-transparent';

                        if ($isToday) {
                            $rowBg = 'bg-teal-50/40 dark:bg-teal-950/20 hover:bg-teal-50/60 dark:hover:bg-teal-950/30';
                            $borderAccent = 'border-l-4 border-teal-500 dark:border-teal-400';
                        } elseif ($isPast) {
                            $rowBg = 'bg-gray-50/40 dark:bg-gray-800/40 hover:bg-gray-50/80 dark:hover:bg-gray-700/20';
                        }
                    @endphp

                    <div class="p-4 sm:p-5 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4 {{ $rowBg }} {{ $borderAccent }}">
                        {{-- Left Column: Date & Day Label --}}
                        <div class="flex items-center gap-3.5 min-w-[200px]">
                            {{-- Day number calendar chip --}}
                            <div class="w-12 h-12 rounded-xl flex flex-col items-center justify-center shrink-0 border
                                {{ $isToday
                                    ? 'bg-teal-600 text-white border-teal-600 shadow-xs'
                                    : ($isPast
                                        ? 'bg-gray-100 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600/60'
                                        : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border-gray-200/90 dark:border-gray-600/70 shadow-xs') }}">
                                <span class="text-[10px] font-bold uppercase tracking-wider leading-none">
                                    {{ $dayDate->format('D') }}
                                </span>
                                <span class="text-lg font-bold leading-none mt-1 tabular-nums">
                                    {{ $dayDate->format('j') }}
                                </span>
                            </div>

                            {{-- Full Date Title & "Today" Tag --}}
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $dayDate->format('l') }}
                                    </h3>
                                    @if($isToday)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 dark:bg-teal-900/50 text-teal-800 dark:text-teal-300 border border-teal-200 dark:border-teal-700">
                                            TODAY
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $dayDate->format('F j, Y') }}
                                </p>
                            </div>
                        </div>

                        {{-- Middle Column: Schedule Type, Timing & Hours Badge --}}
                        <div class="flex-1 md:px-4">
                            @if($isWork)
                                {{-- Regular Work Shift --}}
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-teal-50 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300 border border-teal-200/80 dark:border-teal-800/50">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400"></i>
                                        Regular Shift
                                    </span>

                                    <div class="flex items-center gap-2 text-sm font-bold text-gray-800 dark:text-gray-200 tabular-nums">
                                        <span>{{ $fmt($day['start_time']) }}</span>
                                        <span class="text-gray-400 dark:text-gray-500 font-normal">&mdash;</span>
                                        <span>{{ $fmt($day['end_time']) }}</span>
                                    </div>

                                    @if($shiftDuration > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                            {{ $shiftDuration }} hrs scheduled
                                        </span>
                                    @endif
                                </div>

                            @elseif($isExc && $isCustom)
                                {{-- Custom Working Hours Exception --}}
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/50">
                                            <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400"></i>
                                            Custom Shift
                                        </span>

                                        <div class="flex items-center gap-2 text-sm font-bold text-gray-800 dark:text-gray-200 tabular-nums">
                                            <span>{{ $fmt($day['start_time']) }}</span>
                                            <span class="text-gray-400 dark:text-gray-500 font-normal">&mdash;</span>
                                            <span>{{ $fmt($day['end_time']) }}</span>
                                        </div>

                                        @if($shiftDuration > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">
                                                {{ $shiftDuration }} hrs scheduled
                                            </span>
                                        @endif
                                    </div>
                                    @if(!empty($day['reason']))
                                        <p class="text-xs text-amber-700 dark:text-amber-400/90 italic pl-1">
                                            &ldquo;{{ $day['reason'] }}&rdquo;
                                        </p>
                                    @endif
                                </div>

                            @elseif($isExc && !$isCustom)
                                {{-- Approved Leave / Holiday / Special Day Off Exception --}}
                                @php
                                    $exType = $day['exception_type'] ?? '';
                                    $isHoliday = $exType === 'holiday';
                                    $isSick = $exType === 'sick_leave';
                                    $isUrgent = $exType === 'urgent_leave';
                                    $typeTitle = ucfirst(str_replace('_', ' ', $exType));
                                @endphp
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($isHoliday)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-50 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 border border-purple-200/80 dark:border-purple-800/50">
                                                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400"></i>
                                                Holiday (No Shift)
                                            </span>
                                        @elseif($isSick)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-50 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 border border-rose-200/80 dark:border-rose-800/50">
                                                <i data-lucide="heart-pulse" class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400"></i>
                                                Sick Leave Approved
                                            </span>
                                        @elseif($isUrgent)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-orange-50 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 border border-orange-200/80 dark:border-orange-800/50">
                                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-orange-600 dark:text-orange-400"></i>
                                                Urgent Leave Approved
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-50 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 border border-rose-200/80 dark:border-rose-800/50">
                                                <i data-lucide="calendar-x" class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400"></i>
                                                {{ $typeTitle }}
                                            </span>
                                        @endif
                                    </div>
                                    @if(!empty($day['reason']))
                                        <p class="text-xs text-gray-500 dark:text-gray-400 italic pl-1">
                                            Reason: &ldquo;{{ $day['reason'] }}&rdquo;
                                        </p>
                                    @endif
                                </div>

                            @else
                                {{-- Standard Scheduled Day Off --}}
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                        <i data-lucide="coffee" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-400"></i>
                                        Scheduled Day Off
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Right Column: Attendance Records, Worked Hours, & Overtime --}}
                        <div class="flex flex-wrap md:flex-col md:items-end justify-between items-center gap-2 min-w-[210px] shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-gray-100 dark:border-gray-700/40">
                            {{-- Attendance Status Tag --}}
                            @php
                                $attVal = $day['attendance'] ?? null;
                            @endphp

                            @if($attVal === 'present')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                    <i data-lucide="check-circle-2" class="w-3 h-3 text-emerald-600 dark:text-emerald-400"></i>
                                    Present
                                </span>
                            @elseif($attVal === 'late')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                                    <i data-lucide="clock" class="w-3 h-3 text-amber-600 dark:text-amber-400"></i>
                                    Late
                                </span>
                            @elseif($attVal === 'absent')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800">
                                    <i data-lucide="x-circle" class="w-3 h-3 text-rose-600 dark:text-rose-400"></i>
                                    Absent
                                </span>
                            @elseif(!empty($day['check_in']))
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-teal-100 dark:bg-teal-950/60 text-teal-800 dark:text-teal-300 border border-teal-300 dark:border-teal-800">
                                    <i data-lucide="log-in" class="w-3 h-3 text-teal-600 dark:text-teal-400"></i>
                                    Clocked In
                                </span>
                            @elseif($hasShift && $isPast)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium text-gray-400 dark:text-gray-500">
                                    No punch record
                                </span>
                            @elseif($hasShift && $isToday)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium text-brand-600 dark:text-brand-400">
                                    Awaiting punch in
                                </span>
                            @endif

                            {{-- Clock Times & Actual Worked Summary --}}
                            @if(!empty($day['check_in']) || ($day['worked_hours'] ?? 0) > 0)
                                <div class="text-xs text-right">
                                    @if(!empty($day['check_in']))
                                        <p class="text-gray-600 dark:text-gray-300 font-medium tabular-nums">
                                            Punch: {{ $fmt($day['check_in']) }} &ndash; {{ $fmt($day['check_out']) }}
                                        </p>
                                    @endif

                                    <div class="flex items-center justify-end gap-2 mt-0.5">
                                        @if(($day['worked_hours'] ?? 0) > 0)
                                            <span class="font-bold text-gray-800 dark:text-gray-200 tabular-nums">
                                                {{ $day['worked_hours'] }} hrs worked
                                            </span>
                                        @endif
                                        @if(($day['overtime_hours'] ?? 0) > 0)
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300">
                                                +{{ $day['overtime_hours'] }} OT
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <div class="py-16 px-4 text-center">
                    <div class="w-14 h-14 bg-gray-100 dark:bg-gray-700/60 rounded-2xl flex items-center justify-center mx-auto mb-3 text-gray-400">
                        <i data-lucide="calendar-x" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 dark:text-gray-200">No Schedule Found</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                        No recurring shift or exceptions could be loaded for this date window. Contact management if your shift roster is missing.
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         3. SECONDARY WEEKLY SUMMARY METRICS (PLACED BELOW ROSTER)
    ════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Scheduled Hours --}}
        <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center text-brand-600 dark:text-brand-400 shrink-0">
                <i data-lucide="clock" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">Scheduled Hours</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white tabular-nums mt-0.5">
                    {{ $totalHours }} <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">hrs</span>
                </p>
            </div>
        </div>

        {{-- Total Worked Hours --}}
        <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                <i data-lucide="check-check" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">Worked Hours</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white tabular-nums mt-0.5">
                    {{ $totalWorkedHours ?? 0 }} <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">hrs</span>
                </p>
            </div>
        </div>

        {{-- Overtime Hours --}}
        <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0">
                <i data-lucide="flame" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">Overtime Recorded</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white tabular-nums mt-0.5">
                    {{ $totalOvertime ?? 0 }} <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">hrs</span>
                </p>
            </div>
        </div>

        {{-- Days Working this Week --}}
        <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                <i data-lucide="calendar-days" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">Working Days</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white tabular-nums mt-0.5">
                    {{ $daysWorking }} <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">days</span>
                </p>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         4. UPCOMING EXCEPTIONS & LEAVE (NEXT 30+ DAYS)
    ════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-5 sm:p-6 shadow-sm">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100 dark:border-gray-700/50">
            <div class="flex items-center gap-2">
                <i data-lucide="calendar-clock" class="w-5 h-5 text-brand-600 dark:text-brand-400"></i>
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Upcoming Leaves, Holidays & Exceptions</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Approved timeline exceptions starting from today onwards</p>
                </div>
            </div>
        </div>

        @if(!isset($exceptions) || $exceptions->isEmpty())
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-gray-50 dark:bg-gray-700/50 rounded-xl flex items-center justify-center mx-auto mb-2 text-gray-400">
                    <i data-lucide="calendar-check-2" class="w-6 h-6 text-emerald-500"></i>
                </div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No Upcoming Exceptions</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">You have no upcoming scheduled leaves, holidays, or custom shift exceptions on file.</p>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($exceptions as $ex)
                    @php
                        $exDate = \Carbon\Carbon::parse($ex->exception_date);
                        $exType = $ex->type;
                        $isHoliday = $exType === 'holiday';
                        $isSick = $exType === 'sick_leave';
                        $isUrgent = $exType === 'urgent_leave';
                        $isCustom = $exType === 'custom_hours';
                        $typeLabel = ucfirst(str_replace('_', ' ', $exType));
                    @endphp

                    <div class="p-4 rounded-xl border border-gray-200/80 dark:border-gray-700/60 bg-gray-50/50 dark:bg-gray-800/40 flex items-start gap-3.5">
                        {{-- Date Badge --}}
                        <div class="w-12 h-12 rounded-xl bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-center flex flex-col items-center justify-center shrink-0 shadow-xs">
                            <span class="text-[10px] font-bold text-gray-400 dark:text-gray-400 uppercase leading-none">
                                {{ $exDate->format('M') }}
                            </span>
                            <span class="text-base font-bold text-gray-900 dark:text-white tabular-nums leading-none mt-1">
                                {{ $exDate->format('j') }}
                            </span>
                        </div>

                        {{-- Details --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-xs font-bold text-gray-900 dark:text-white">
                                    {{ $exDate->format('l, M j, Y') }}
                                </span>
                            </div>

                            <div class="mt-1">
                                @if($isHoliday)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">
                                        Holiday
                                    </span>
                                @elseif($isSick)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300">
                                        Sick Leave
                                    </span>
                                @elseif($isUrgent)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300">
                                        Urgent Leave
                                    </span>
                                @elseif($isCustom)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                                        Custom Hours
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ $typeLabel }}
                                    </span>
                                @endif
                            </div>

                            @if($ex->start_time && $ex->end_time)
                                <p class="text-xs text-gray-600 dark:text-gray-400 font-medium tabular-nums mt-1.5">
                                    Shift: {{ $fmt($ex->start_time) }} &ndash; {{ $fmt($ex->end_time) }}
                                </p>
                            @endif

                            @if($ex->reason)
                                <p class="text-xs text-gray-500 dark:text-gray-400 italic mt-1 truncate" title="{{ $ex->reason }}">
                                    &ldquo;{{ $ex->reason }}&rdquo;
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         5. NOTICE & HELP FOOTER
    ════════════════════════════════════════════════════════════════ --}}
    <div class="bg-gray-50 dark:bg-gray-800/40 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 flex items-center justify-between gap-4 text-xs text-gray-500 dark:text-gray-400 no-print">
        <div class="flex items-center gap-2">
            <i data-lucide="info" class="w-4 h-4 text-brand-600 dark:text-brand-400 shrink-0"></i>
            <span>
                Need to request a shift swap, day off, or file leave? Please submit your schedule adjustments to the Reception Desk or Spa Administrator.
            </span>
        </div>
        <span class="font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">
            Asia/Manila
        </span>
    </div>

</div>
@endsection
