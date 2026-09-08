@php
    // Helper for time formatting
    $fmt = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : '—';

    // Fallbacks in case the controller hasn't been fully updated yet to pass these explicitly
    $totalHours = $totalHours ?? 0;
    $daysWorking = $daysWorking ?? 0;
    $weekLabel = $weekLabel ?? now()->startOfWeek()->format('M j') . ' – ' . now()->endOfWeek()->format('M j, Y');

    // Compute today's shift (if today falls within this week)
    $todayShift = null;
    if (isset($weeklySchedule) && count($weeklySchedule) > 0) {
        foreach ($weeklySchedule as $d) {
            if (($d['date'] ?? null) === now()->toDateString()) {
                $todayShift = $d;
                break;
            }
        }
    }
@endphp

@extends('layouts.staff')

@section('title', 'My Schedule')

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .print-block { display: block !important; }
        body { background: white !important; }
        .today-card { animation: none !important; box-shadow: none !important; }
    }
    .print-block { display: none; }

    @keyframes subtlePulse {
        0% { box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.4); }
        70% { box-shadow: 0 0 0 4px rgba(20, 184, 166, 0); }
        100% { box-shadow: 0 0 0 0 rgba(20, 184, 166, 0); }
    }
    .today-card {
        animation: subtlePulse 2s infinite;
        border-color: #14b8a6 !important;
    }
    .dark .today-card {
        border-color: #0d9488 !important;
    }
    .past-day {
        opacity: 0.7;
        filter: grayscale(0.5);
    }
</style>
@endpush

@section('content')
<div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto space-y-6">

    <!-- 1. Header & Navigation -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-5 bg-white p-5 rounded-2xl border border-gray-200 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-neutral-200 flex items-center gap-3">
                <span class="inline-flex items-center justify-center size-10 rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                    <svg class="flex-shrink-0 size-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                </span>
                My Schedule
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-neutral-500">
                View your assigned shifts, scheduled hours, and approved exceptions for ZenFlow.
            </p>
        </div>

        <!-- Week Navigation Toolbar -->
        <div class="flex items-center gap-2">
            <div class="flex items-center bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg p-1.5 shadow-sm">
                <a href="?week_start={{ $prevWeek ?? now()->subWeek()->toDateString() }}" class="p-2 rounded-md text-gray-500 hover:bg-white hover:text-gray-800 hover:shadow-sm dark:hover:bg-neutral-700 dark:text-neutral-400 dark:hover:text-white transition-all" title="Previous Week">
                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>

                <div class="flex items-center px-4 border-x border-gray-200 dark:border-neutral-700 mx-1">
                    <span class="text-sm font-bold text-gray-800 dark:text-neutral-200 whitespace-nowrap min-w-[140px] text-center">
                        {{ $weekLabel }}
                    </span>
                </div>

                <a href="?week_start={{ $nextWeek ?? now()->addWeek()->toDateString() }}" class="p-2 rounded-md text-gray-500 hover:bg-white hover:text-gray-800 hover:shadow-sm dark:hover:bg-neutral-700 dark:text-neutral-400 dark:hover:text-white transition-all" title="Next Week">
                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>

                <a href="?week_start={{ now()->startOfWeek()->toDateString() }}" class="ml-2 px-3 py-1.5 text-xs font-semibold bg-white border border-gray-200 text-gray-700 rounded-md hover:bg-gray-50 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800 transition-colors shadow-sm">
                    Today
                </a>
            </div>

            <button onclick="window.print()" class="no-print hidden sm:inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800 transition-colors shadow-sm">
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                Print
            </button>
        </div>
    </div>

    <!-- 2. Weekly Summary Metrics -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Metric: Total Hours -->
        <div class="flex flex-col bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-700">
            <div class="p-4 md:p-5 flex items-center gap-x-4">
                <div class="flex-shrink-0 flex justify-center items-center size-[46px] bg-teal-100 text-teal-600 rounded-lg dark:bg-teal-900/30 dark:text-teal-400">
                    <svg class="flex-shrink-0 size-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-neutral-500 font-semibold">Scheduled Hours</p>
                    <h3 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-neutral-200">
                        {{ $totalHours }} <span class="text-sm font-medium text-gray-500 dark:text-neutral-500">hrs</span>
                    </h3>
                </div>
            </div>
        </div>

        <!-- Metric: Days Working -->
        <div class="flex flex-col bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-700">
            <div class="p-4 md:p-5 flex items-center gap-x-4">
                <div class="flex-shrink-0 flex justify-center items-center size-[46px] bg-blue-100 text-blue-600 rounded-lg dark:bg-blue-900/30 dark:text-blue-400">
                    <svg class="flex-shrink-0 size-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-neutral-500 font-semibold">Days Working</p>
                    <h3 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-neutral-200">
                        {{ $daysWorking }} <span class="text-sm font-medium text-gray-500 dark:text-neutral-500">/ 7</span>
                    </h3>
                </div>
            </div>
        </div>

        <!-- Today's Shift Summary -->
        <div class="flex flex-col bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-700">
            <div class="p-4 md:p-5 flex items-center gap-x-4">
                @if($todayShift && ($todayShift['type'] === 'work' || ($todayShift['type'] === 'exception' && ($todayShift['exception_type'] ?? '') === 'custom_hours')))
                    <div class="flex-shrink-0 flex justify-center items-center size-[46px] bg-emerald-100 text-emerald-600 rounded-lg dark:bg-emerald-900/30 dark:text-emerald-400">
                        <svg class="flex-shrink-0 size-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-neutral-500 font-semibold">Today</p>
                        <h3 class="text-sm sm:text-base font-bold text-gray-800 dark:text-neutral-200">
                            {{ $fmt($todayShift['start_time']) }} – {{ $fmt($todayShift['end_time']) }}
                        </h3>
                    </div>
                @elseif($todayShift && $todayShift['type'] === 'exception')
                    <div class="flex-shrink-0 flex justify-center items-center size-[46px] bg-amber-100 text-amber-600 rounded-lg dark:bg-amber-900/30 dark:text-amber-400">
                        <svg class="flex-shrink-0 size-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M3.5 12h17"/><path d="m17 5 3.5 7-3.5 7"/><path d="m7 19-3.5-7 3.5-7"/></svg>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-neutral-500 font-semibold">Today</p>
                        <h3 class="text-sm sm:text-base font-bold text-amber-600 dark:text-amber-400">
                            {{ ucfirst(str_replace('_', ' ', $todayShift['exception_type'] ?? 'off')) }}
                        </h3>
                    </div>
                @else
                    <div class="flex-shrink-0 flex justify-center items-center size-[46px] bg-gray-100 text-gray-400 rounded-lg dark:bg-neutral-800 dark:text-neutral-600">
                        <svg class="flex-shrink-0 size-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-neutral-500 font-semibold">Today</p>
                        <h3 class="text-sm sm:text-base font-bold text-gray-400 dark:text-neutral-500">
                            Day Off
                        </h3>
                    </div>
                @endif
            </div>
        </div>

        <!-- Info Banner -->
        <div class="flex flex-col bg-amber-50 border border-amber-200 shadow-sm rounded-xl dark:bg-amber-900/20 dark:border-amber-900/50">
            <div class="p-4 md:p-5 flex items-start gap-x-4">
                <svg class="flex-shrink-0 size-6 text-amber-600 dark:text-amber-500 mt-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                <div>
                    <h3 class="text-sm font-bold text-amber-800 dark:text-amber-300">Need to request a change?</h3>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
                        This view is read-only. Contact your Receptionist or Administrator to add an exception to your timeline.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="no-print flex flex-wrap items-center gap-x-5 gap-y-2 bg-white border border-gray-200 shadow-sm rounded-xl px-5 py-3 dark:bg-neutral-900 dark:border-neutral-700">
        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-neutral-500">Legend:</span>
        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-neutral-400">
            <span class="size-2.5 rounded-full bg-teal-500"></span> Work Shift
        </span>
        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-neutral-400">
            <span class="size-2.5 rounded-full bg-amber-500"></span> Custom Hours
        </span>
        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-neutral-400">
            <span class="size-2.5 rounded-full bg-red-500"></span> Leave / Sick
        </span>
        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-neutral-400">
            <span class="size-2.5 rounded-full bg-purple-500"></span> Holiday
        </span>
        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-neutral-400">
            <span class="size-2.5 rounded-full bg-gray-300 dark:bg-neutral-600"></span> Day Off
        </span>
    </div>

    <!-- 3. The Weekly Schedule Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4">
        @if(isset($weeklySchedule) && count($weeklySchedule) > 0)
            @foreach($weeklySchedule as $day)
                @php
                    $isPast = $day['date'] < now()->toDateString();
                    $isToday = $day['date'] === now()->toDateString();
                    $isWork = $day['type'] === 'work';
                    $isExc = $day['type'] === 'exception';
                    $isCustom = ($day['exception_type'] ?? '') === 'custom_hours';
                    $isOff = $day['type'] === 'off' || ($isExc && !$isCustom);

                    $cardClass = 'bg-white border-gray-200 dark:bg-neutral-900 dark:border-neutral-700';
                    if ($isToday) $cardClass = 'bg-teal-50/50 border-teal-500 today-card dark:bg-teal-900/10 dark:border-teal-600';
                    elseif ($isPast) $cardClass = 'bg-gray-50 border-gray-100 past-day dark:bg-neutral-800/50 dark:border-neutral-800';
                @endphp

                <div class="flex flex-col border shadow-sm rounded-xl transition-all h-full {{ $cardClass }}">
                    <!-- Day Header -->
                    <div class="p-3 border-b border-gray-100 dark:border-neutral-800 flex items-center justify-between {{ $isToday ? 'bg-teal-100/50 dark:bg-teal-900/20 rounded-t-xl' : '' }}">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider {{ $isToday ? 'text-teal-600 dark:text-teal-400' : 'text-gray-500 dark:text-neutral-500' }}">
                                {{ $day['day_name'] ?? \Carbon\Carbon::parse($day['date'])->format('l') }}
                            </p>
                            <p class="text-sm font-bold {{ $isToday ? 'text-teal-800 dark:text-teal-300' : 'text-gray-800 dark:text-neutral-200' }}">
                                {{ \Carbon\Carbon::parse($day['date'])->format('M j') }}
                            </p>
                        </div>
                        @if($isToday)
                            <span class="inline-flex items-center gap-x-1.5 py-1 px-2 rounded-md text-[10px] font-bold bg-teal-600 text-white shadow-sm uppercase tracking-wider">
                                Today
                            </span>
                        @endif
                    </div>

                    <!-- Day Content -->
                    <div class="p-4 flex-1 flex flex-col justify-center items-center text-center">
                        @if($isWork)
                            <!-- Standard Working Shift -->
                            <div class="flex flex-col items-center gap-2 w-full">
                                <div class="w-12 h-12 bg-teal-50 text-teal-600 rounded-full flex items-center justify-center dark:bg-teal-900/30 dark:text-teal-400 mb-1">
                                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </div>
                                <div class="font-bold text-gray-800 dark:text-neutral-200 text-lg">
                                    {{ $fmt($day['start_time']) }}
                                </div>
                                <div class="w-px h-4 bg-gray-300 dark:bg-neutral-600"></div>
                                <div class="font-bold text-gray-800 dark:text-neutral-200 text-lg">
                                    {{ $fmt($day['end_time']) }}
                                </div>

                                @php $hrs = $day['start_time'] && $day['end_time'] ? round(\Carbon\Carbon::parse($day['start_time'])->diffInMinutes(\Carbon\Carbon::parse($day['end_time']))/60, 1) : 0; @endphp
                                @if($hrs > 0)
                                    <span class="mt-2 inline-flex items-center gap-x-1.5 py-1 px-2.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-800 dark:bg-neutral-800 dark:text-neutral-200">
                                        {{ $hrs }} hours
                                    </span>
                                @endif

                                @if(!empty($day['attendance']))
                                    <span class="mt-2 inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wide
                                        {{ $day['attendance'] === 'present' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                                        {{ $day['attendance'] === 'late' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : '' }}
                                        {{ $day['attendance'] === 'absent' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                    ">
                                        {{ ucfirst($day['attendance']) }}
                                    </span>
                                @endif
                            </div>

                        @elseif($isExc && $isCustom)
                            <!-- Custom Hours Exception -->
                            <div class="flex flex-col items-center gap-2 w-full">
                                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center dark:bg-amber-900/30 dark:text-amber-400 mb-1 ring-2 ring-amber-100 dark:ring-amber-900/50">
                                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M3.5 12h17"/><path d="m17 5 3.5 7-3.5 7"/><path d="m7 19-3.5-7 3.5-7"/></svg>
                                </div>
                                <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Custom Hours</span>
                                <div class="font-bold text-gray-800 dark:text-neutral-200 text-sm">
                                    {{ $fmt($day['start_time']) }} <br> to <br> {{ $fmt($day['end_time']) }}
                                </div>
                                @if(!empty($day['reason']))
                                    <p class="text-[10px] text-gray-500 dark:text-neutral-400 mt-2 px-2 italic text-center leading-tight">
                                        "{{ $day['reason'] }}"
                                    </p>
                                @endif
                            </div>

                        @elseif($isExc && !$isCustom)
                            <!-- Full Day Exception (Leave/Holiday) -->
                            <div class="flex flex-col items-center gap-2 w-full py-4">
                                @php
                                    $exColor = $day['exception_type'] === 'holiday' ? 'purple' : 'red';
                                @endphp
                                <div class="w-12 h-12 bg-{{ $exColor }}-50 text-{{ $exColor }}-600 rounded-full flex items-center justify-center dark:bg-{{ $exColor }}-900/30 dark:text-{{ $exColor }}-400 mb-2">
                                    @if($day['exception_type'] === 'holiday')
                                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                                    @else
                                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
                                    @endif
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wide bg-{{ $exColor }}-100 text-{{ $exColor }}-800 dark:bg-{{ $exColor }}-900/30 dark:text-{{ $exColor }}-400 border border-{{ $exColor }}-200 dark:border-{{ $exColor }}-800">
                                    {{ ucfirst(str_replace('_', ' ', $day['exception_type'])) }}
                                </span>
                                @if(!empty($day['reason']))
                                    <p class="text-[11px] text-gray-500 dark:text-neutral-400 mt-2 px-2 text-center leading-tight">
                                        {{ $day['reason'] }}
                                    </p>
                                @endif
                            </div>

                        @else
                            <!-- Standard Day Off -->
                            <div class="flex flex-col items-center gap-2 w-full py-6">
                                <div class="w-12 h-12 bg-gray-50 border border-gray-200 text-gray-400 rounded-full flex items-center justify-center dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-600 mb-2">
                                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                </div>
                                <span class="text-sm font-bold text-gray-400 dark:text-neutral-500 uppercase tracking-wider">
                                    Day Off
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <!-- Empty State -->
            <div class="col-span-full py-20 bg-white border border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center text-center dark:bg-neutral-900 dark:border-neutral-700">
                <div class="flex justify-center items-center size-16 bg-gray-100 rounded-full dark:bg-neutral-800 mb-4">
                    <svg class="size-8 text-gray-400 dark:text-neutral-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-neutral-200">Timeline Data Unavailable</h3>
                <p class="text-sm text-gray-500 dark:text-neutral-400 mt-1">Please ensure the controller is passing the <code class="text-xs bg-gray-100 dark:bg-neutral-800 px-1.5 py-0.5 rounded">$weeklySchedule</code> array.</p>
            </div>
        @endif
    </div>

    <!-- 4. Future Exceptions List -->
    <div class="flex flex-col bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-700 p-5 md:p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-800 dark:text-neutral-200 mb-5 flex items-center gap-2">
            <svg class="flex-shrink-0 size-5 text-teal-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
            Upcoming Leaves & Holidays
        </h2>

        @if(!isset($exceptions) || $exceptions->isEmpty())
            <div class="text-center py-8">
                <svg class="size-10 mx-auto text-gray-300 dark:text-neutral-700 mb-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                <p class="text-sm font-medium text-gray-500 dark:text-neutral-400">You have no upcoming scheduled leaves or holidays.</p>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($exceptions as $ex)
                <div class="flex items-start gap-x-4 p-4 bg-gray-50 border border-gray-200 rounded-xl dark:bg-neutral-800/50 dark:border-neutral-700">
                    <div class="inline-flex justify-center items-center size-12 rounded-xl bg-white shadow-sm border border-gray-200 text-gray-800 font-bold text-lg dark:bg-neutral-800 dark:border-neutral-600 dark:text-neutral-200 shrink-0">
                        {{ \Carbon\Carbon::parse($ex->exception_date)->format('d') }}
                    </div>
                    <div class="grow">
                        <div class="flex flex-col gap-1">
                            <span class="font-bold text-sm text-gray-900 dark:text-neutral-100">{{ \Carbon\Carbon::parse($ex->exception_date)->format('l, M Y') }}</span>
                            <div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide
                                    {{ in_array($ex->type, ['day_off', 'sick_leave', 'urgent_leave']) ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                                       ($ex->type === 'holiday' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400') }}">
                                    {{ ucfirst(str_replace('_', ' ', $ex->type)) }}
                                </span>
                            </div>
                        </div>
                        @if($ex->start_time && $ex->end_time)
                            <div class="text-xs font-semibold text-gray-500 dark:text-neutral-400 mt-2">
                                {{ \Carbon\Carbon::parse($ex->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($ex->end_time)->format('g:i A') }}
                            </div>
                        @endif
                        @if($ex->reason)
                            <p class="text-xs text-gray-500 dark:text-neutral-500 mt-1.5 truncate" title="{{ $ex->reason }}">
                                "{{ $ex->reason }}"
                            </p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
