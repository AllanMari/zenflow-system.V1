@extends('layouts.admin')

@section('title', 'Attendance Report')

@push('styles')
{{-- Flatpickr CSS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Rounded Flatpickr calendar to match the UI */
    .flatpickr-calendar {
        border-radius: 1rem;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05);
        border: 1px solid #e5e7eb;
        font-family: 'Inter', system-ui, sans-serif;
        overflow: hidden;
    }
    .flatpickr-day.selected,
    .flatpickr-day.selected:hover {
        background: #0d9488;
        border-color: #0d9488;
    }
    .flatpickr-day:hover { background: #ccfbf1; color: #0d9488; }
    .flatpickr-day.today { border-color: #14b8a6; }
    .flatpickr-months .flatpickr-month { background: #0d9488; color: #fff; }
    .flatpickr-current-month .flatpickr-monthDropdown-months { color: #fff; }
    .flatpickr-current-month input.cur-year { color: #fff; }
    .numInputWrapper span.arrowUp:after  { border-bottom-color: #fff; }
    .numInputWrapper span.arrowDown:after { border-top-color: #fff; }
    .flatpickr-weekdays { background: #0d9488; }
    span.flatpickr-weekday { background: #0d9488; color: #ccfbf1; font-weight: 600; }
    .flatpickr-prev-month svg, .flatpickr-next-month svg { fill: #fff; }
    .flatpickr-prev-month:hover svg, .flatpickr-next-month:hover svg { fill: #ccfbf1; }

    /* Dark mode overrides */
    .dark .flatpickr-calendar {
        background: #1f2937;
        border-color: #374151;
        color: #f9fafb;
    }
    .dark .flatpickr-day { color: #d1d5db; }
    .dark .flatpickr-day:hover { background: #134e4a; color: #99f6e4; }
    .dark .flatpickr-day.flatpickr-disabled { color: #4b5563; }
    .dark .flatpickr-day.today { border-color: #14b8a6; color: #5eead4; }
    .dark .flatpickr-day.selected,
    .dark .flatpickr-day.selected:hover { background: #0d9488; border-color: #0d9488; color: #fff; }
    .dark .flatpickr-days { background: #1f2937; }
    .dark .dayContainer { background: #1f2937; }
    .dark .flatpickr-innerContainer { background: #1f2937; }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ mobileFilterOpen: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Attendance Report
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $startDate->format('M j, Y') }} – {{ $endDate->format('M j, Y') }}
                <span class="text-gray-300 dark:text-gray-600 mx-1">•</span>
                Track staff attendance and authorize receptionists
            </p>
        </div>
        <a href="{{ route('attendance.today') }}"
           class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-medium transition-all duration-200 shadow-lg shadow-teal-200 dark:shadow-none text-sm flex items-center justify-center gap-2 active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Mark Today's Attendance
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4">
        @php
            $cardConfig = [
                'present'  => ['label' => 'Present',  'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'green'],
                'absent'   => ['label' => 'Absent',   'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'red'],
                'late'     => ['label' => 'Late',     'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'yellow'],
                'on_leave' => ['label' => 'On Leave', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'color' => 'blue'],
                'worked_hours' => ['label' => 'Worked Hrs', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'indigo'],
                'overtime' => ['label' => 'Overtime Hrs', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'rose'],
            ];
        @endphp
        @foreach($cardConfig as $key => $config)
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-5 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold truncate">{{ $config['label'] }}</p>
                    <div class="w-8 h-8 rounded-lg bg-{{ $config['color'] }}-100 dark:bg-{{ $config['color'] }}-900/30 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-{{ $config['color'] }}-600 dark:text-{{ $config['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $summary[$key] ?? 0 }}</p>
            </div>
        @endforeach
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        <!-- Mobile Filter Toggle -->
        <button type="button"
                @click="mobileFilterOpen = !mobileFilterOpen"
                class="md:hidden w-full px-4 py-3 flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filters
            </span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="mobileFilterOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <!-- Filter Form -->
        <form method="GET"
              class="p-4 flex flex-wrap gap-3 items-end"
              :class="mobileFilterOpen ? '' : 'hidden md:flex'">
            
            {{-- Date Range --}}
            <div class="w-full sm:w-auto flex-1 sm:flex-none min-w-[240px]">
                <label class="block mb-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    Date range
                </label>
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none z-10"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
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
                        class="w-full h-11 pl-10 pr-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 text-sm text-gray-800 dark:text-white cursor-pointer focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition"
                    >
                    <input type="hidden" id="dateFrom" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                    <input type="hidden" id="dateTo" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                </div>
            </div>

            {{-- Staff --}}
            <div class="w-full sm:w-auto flex-1 sm:flex-none min-w-[160px]">
                <label class="block mb-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Staff</label>
                <select name="staff_id"
                        class="w-full h-11 px-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 text-sm text-gray-800 dark:text-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition">
                    <option value="">All Staff</option>
                    @foreach($allStaff as $s)
                        <option value="{{ $s->id }}" {{ request('staff_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->first_name }} {{ $s->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            {{-- Status --}}
            <div class="w-full sm:w-auto flex-1 sm:flex-none min-w-[140px]">
                <label class="block mb-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Status</label>
                <select name="status"
                        class="w-full h-11 px-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 text-sm text-gray-800 dark:text-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition">
                    <option value="">All Statuses</option>
                    <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Present</option>
                    <option value="absent"  {{ request('status') === 'absent'  ? 'selected' : '' }}>Absent</option>
                    <option value="late"    {{ request('status') === 'late'    ? 'selected' : '' }}>Late</option>
                    <option value="on_leave" {{ request('status') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                </select>
            </div>
            
            {{-- Filter Button --}}
            <button type="submit"
                    class="w-full sm:w-auto h-11 px-5 bg-gray-800 dark:bg-gray-700 text-white rounded-xl text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-600 transition shadow-sm flex items-center justify-center gap-2 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Filter
            </button>
        </form>
    </div>

    @php
        // Single source of truth for status badges, shared by desktop + mobile.
        $statusConfig = [
            'present'  => ['bg' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',  'border' => 'border-green-200 dark:border-green-800',    'icon' => 'M5 13l4 4L19 7'],
            'absent'   => ['bg' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',        'border' => 'border-red-200 dark:border-red-800',      'icon' => 'M6 18L18 6M6 6l12 12'],
            'late'     => ['bg' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', 'border' => 'border-yellow-200 dark:border-yellow-800', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            'on_leave' => ['bg' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',    'border' => 'border-blue-200 dark:border-blue-800',    'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ];

        $maxOT = collect($staffOvertimeSummary ?? [])->max('overtime_hours') ?: 1;
    @endphp

    {{-- Staff Overtime Breakdown --}}
    @if(!empty($staffOvertimeSummary))
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        {{-- Header --}}
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-rose-100 dark:bg-rose-900/30 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-gray-800 dark:text-white">Staff Overtime Breakdown</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $startDate->format('M j') }} – {{ $endDate->format('M j, Y') }}
                        @if(!empty($staffOvertimeSummary))
                            · {{ count($staffOvertimeSummary) }} staff with records
                        @endif
                    </p>
                </div>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1 text-xs font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 px-2.5 py-1 rounded-full">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                </svg>
                Sorted by highest OT
            </span>
        </div>

        {{-- Table Desktop --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Staff</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Days Worked</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Scheduled Hrs</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Worked Hrs</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-[200px]">Overtime</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">View Records</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($staffOvertimeSummary as $uid => $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20 transition">
                        {{-- Staff Name --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 text-xs font-bold ring-2 ring-white dark:ring-gray-800 shrink-0">
                                    {{ strtoupper(substr($row['name'], 0, 1)) }}
                                </div>
                                <span class="text-sm font-semibold text-gray-800 dark:text-white">{{ $row['name'] }}</span>
                            </div>
                        </td>
                        {{-- Days Worked --}}
                        <td class="px-5 py-3.5 text-center text-sm text-gray-700 dark:text-gray-300 font-medium">
                            {{ $row['days_worked'] }}
                        </td>
                        {{-- Scheduled --}}
                        <td class="px-5 py-3.5 text-center text-sm text-gray-700 dark:text-gray-300">
                            {{ $row['scheduled_hours'] }} hrs
                        </td>
                        {{-- Worked --}}
                        <td class="px-5 py-3.5 text-center text-sm font-medium {{ $row['worked_hours'] > $row['scheduled_hours'] ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300' }}">
                            {{ $row['worked_hours'] }} hrs
                        </td>
                        {{-- Overtime bar --}}
                        <td class="px-5 py-3.5">
                            @if($row['overtime_hours'] > 0)
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                                        @php $pct = min(100, round(($row['overtime_hours'] / $maxOT) * 100)); @endphp
                                        <div class="bg-rose-500 dark:bg-rose-400 h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 whitespace-nowrap">
                                        +{{ $row['overtime_hours'] }} hrs
                                    </span>
                                </div>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-600 italic">No overtime</span>
                            @endif
                        </td>
                        {{-- Filter link --}}
                        <td class="px-5 py-3.5 text-center">
                            <a href="{{ request()->fullUrlWithQuery(['staff_id' => $row['user_id']]) }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-900/20 hover:bg-teal-100 dark:hover:bg-teal-900/40 rounded-lg transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile Card View --}}
        <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($staffOvertimeSummary as $uid => $row)
            <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 text-sm font-bold ring-2 ring-white dark:ring-gray-800">
                            {{ strtoupper(substr($row['name'], 0, 1)) }}
                        </div>
                        <span class="text-sm font-bold text-gray-800 dark:text-white">{{ $row['name'] }}</span>
                    </div>
                    @if($row['overtime_hours'] > 0)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                            +{{ $row['overtime_hours'] }} hrs OT
                        </span>
                    @else
                        <span class="text-xs text-gray-400 dark:text-gray-600 italic">No overtime</span>
                    @endif
                </div>
                <div class="grid grid-cols-3 gap-2 text-xs mb-3">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                        <p class="text-gray-400 mb-0.5">Sched</p>
                        <p class="font-bold text-gray-700 dark:text-gray-300">{{ $row['scheduled_hours'] }}h</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                        <p class="text-gray-400 mb-0.5">Worked</p>
                        <p class="font-bold text-gray-700 dark:text-gray-300">{{ $row['worked_hours'] }}h</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                        <p class="text-gray-400 mb-0.5">Days</p>
                        <p class="font-bold text-gray-700 dark:text-gray-300">{{ $row['days_worked'] }}</p>
                    </div>
                </div>
                @if($row['overtime_hours'] > 0)
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 mb-2">
                    @php $pct = min(100, round(($row['overtime_hours'] / $maxOT) * 100)); @endphp
                    <div class="bg-rose-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Attendance Records -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <!-- DESKTOP: Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Staff</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check-in</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check-out</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sched Hrs</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Worked Hrs</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Overtime</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Marked By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($attendances as $record)
                        @php $config = $statusConfig[$record->status] ?? ['bg' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', 'border' => 'border-gray-200 dark:border-gray-600', 'icon' => '']; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-6 py-3.5 text-sm text-gray-900 dark:text-white whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    {{ \Carbon\Carbon::parse($record->date)->format('M j, Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 text-xs font-bold ring-2 ring-white dark:ring-gray-800">
                                        {{ strtoupper(substr($record->user->first_name ?? '?', 0, 1)) }}
                                    </div>
                                    <span class="text-sm text-gray-900 dark:text-white font-medium">
                                        {{ $record->user->first_name ?? 'Unknown' }} {{ $record->user->last_name ?? '' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $config['bg'] }}">
                                    @if($config['icon'])
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $config['icon'] }}"/>
                                        </svg>
                                    @endif
                                    {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('g:i A') : '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('g:i A') : '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-sm text-gray-700 dark:text-gray-300">
                                {{ $record->scheduled_hours ?? '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-sm text-gray-700 dark:text-gray-300">
                                {{ $record->worked_hours ?? '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-sm font-medium {{ ($record->overtime_hours ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $record->overtime_hours ?? '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-sm text-gray-500 dark:text-gray-400">
                                {{ $record->marker?->first_name ?? 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-3">
                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 font-medium">No attendance records found</p>
                                    @if(request('status') === 'absent')
                                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Absences are not stored as records — they appear in the summary card only.</p>
                                    @elseif(request('status') === 'on_leave')
                                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Leave is tracked in schedule exceptions — it appears in the summary card only.</p>
                                    @else
                                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Try adjusting your filters or date range</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- MOBILE: Card View -->
        <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($attendances as $record)
                @php $config = $statusConfig[$record->status] ?? ['bg' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', 'border' => 'border-gray-200 dark:border-gray-600']; @endphp
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/20 transition">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 text-sm font-bold ring-2 ring-white dark:ring-gray-800">
                                {{ strtoupper(substr($record->user->first_name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $record->user->first_name ?? 'Unknown' }} {{ $record->user->last_name ?? '' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($record->date)->format('M j, Y') }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $config['bg'] }} border {{ $config['border'] }}">
                            {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                            <p class="text-gray-400 dark:text-gray-500 mb-0.5">Check-in</p>
                            <p class="font-semibold text-gray-700 dark:text-gray-300">{{ $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('g:i A') : '-' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                            <p class="text-gray-400 dark:text-gray-500 mb-0.5">Check-out</p>
                            <p class="font-semibold text-gray-700 dark:text-gray-300">{{ $record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('g:i A') : '-' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                            <p class="text-gray-400 dark:text-gray-500 mb-0.5">Worked (Hrs)</p>
                            <p class="font-semibold text-gray-700 dark:text-gray-300">{{ $record->worked_hours ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                            <p class="text-gray-400 dark:text-gray-500 mb-0.5">Overtime</p>
                            <p class="font-semibold {{ ($record->overtime_hours ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-700 dark:text-gray-300' }}">{{ $record->overtime_hours ?? '-' }}</p>
                        </div>
                    </div>
                    @if($record->notes)
                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/30 rounded-lg p-2">
                            <span class="font-medium">Note:</span> {{ $record->notes }}
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">No attendance records found</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Try adjusting your filters</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($attendances instanceof \Illuminate\Pagination\LengthAwarePaginator && $attendances->hasPages())
            <div class="px-4 sm:px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>

    <!-- Receptionist Permissions Section -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 sm:p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-800 dark:text-white">Receptionist Permissions</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Grant attendance-marking access to receptionists</p>
            </div>
        </div>

        @if($receptionists->isEmpty())
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-sm">No receptionists found</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($receptionists as $rec)
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-100 dark:border-gray-700 hover:shadow-sm transition-shadow duration-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 font-bold text-sm ring-2 ring-white dark:ring-gray-800">
                                {{ strtoupper(substr($rec->first_name, 0, 1) . substr($rec->last_name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $rec->first_name }} {{ $rec->last_name }}</p>
                                <p class="text-xs text-gray-500">{{ $rec->username }}</p>
                            </div>
                        </div>
                        <form action="{{ route('attendance.toggle-permission', $rec) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 active:scale-95 flex items-center gap-1.5
                                    {{ $rec->can_mark_attendance
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50 ring-1 ring-green-200 dark:ring-green-800'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 ring-1 ring-gray-200 dark:ring-gray-600' }}">
                                @if($rec->can_mark_attendance)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                @endif
                                {{ $rec->can_mark_attendance ? 'Can Mark' : 'Cannot Mark' }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@push('scripts')
{{-- Flatpickr JS --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const datePicker = document.getElementById('dateRangePicker');
    const dateFrom   = document.getElementById('dateFrom');
    const dateTo     = document.getElementById('dateTo');

    if (datePicker && window.flatpickr) {
        const existingFrom = dateFrom ? dateFrom.value : '';
        const existingTo   = dateTo   ? dateTo.value   : '';
        
        let defaultDates = [];
        if (existingFrom) defaultDates.push(existingFrom);
        if (existingTo && existingTo !== existingFrom) defaultDates.push(existingTo);

        flatpickr(datePicker, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'M j, Y',
            defaultDate: defaultDates,
            allowInput: false,
            appendTo: document.body,
            onChange: function (selectedDates, dateStr, instance) {
                if (!dateFrom || !dateTo) return;

                if (selectedDates.length === 0) {
                    dateFrom.value = '';
                    dateTo.value = '';
                } else if (selectedDates.length === 1) {
                    dateFrom.value = instance.formatDate(selectedDates[0], 'Y-m-d');
                    dateTo.value = '';
                } else {
                    dateFrom.value = instance.formatDate(selectedDates[0], 'Y-m-d');
                    dateTo.value   = instance.formatDate(selectedDates[1], 'Y-m-d');
                }
            }
        });
    }
});
</script>
@endpush
@endsection