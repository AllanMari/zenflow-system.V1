@extends('layouts.admin')

@section('title', 'Attendance Report')

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
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track staff attendance and authorize receptionists</p>
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
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        @php
            $cardConfig = [
                'present' => ['label' => 'Present', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'green'],
                'absent' => ['label' => 'Absent', 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'red'],
                'late' => ['label' => 'Late', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'yellow'],
                'on_leave' => ['label' => 'On Leave', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'color' => 'blue'],
            ];
        @endphp
        @foreach($cardConfig as $key => $config)
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-5 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">{{ $config['label'] }}</p>
                    <div class="w-8 h-8 rounded-lg bg-{{ $config['color'] }}-100 dark:bg-{{ $config['color'] }}-900/30 flex items-center justify-center">
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
            <div class="w-full sm:w-auto flex-1 sm:flex-none min-w-[140px]">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-teal-500 focus:border-teal-500 py-2 px-3 shadow-sm">
            </div>
            <div class="w-full sm:w-auto flex-1 sm:flex-none min-w-[140px]">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-teal-500 focus:border-teal-500 py-2 px-3 shadow-sm">
            </div>
            <div class="w-full sm:w-auto flex-1 sm:flex-none min-w-[160px]">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Staff</label>
                <select name="staff_id"
                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-teal-500 focus:border-teal-500 py-2 px-3 shadow-sm">
                    <option value="">All Staff</option>
                    @foreach($allStaff as $s)
                        <option value="{{ $s->id }}" {{ request('staff_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->first_name }} {{ $s->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-auto flex-1 sm:flex-none min-w-[140px]">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Status</label>
                <select name="status"
                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-teal-500 focus:border-teal-500 py-2 px-3 shadow-sm">
                    <option value="">All Statuses</option>
                    <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>✅ Present</option>
                    <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>❌ Absent</option>
                    <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>⏰ Late</option>
                    <option value="on_leave" {{ request('status') === 'on_leave' ? 'selected' : '' }}>🏖️ On Leave</option>
                </select>
            </div>
            <button type="submit"
                    class="w-full sm:w-auto px-5 py-2 bg-gray-800 dark:bg-gray-700 text-white rounded-xl text-sm font-medium hover:bg-gray-700 dark:hover:bg-gray-600 transition shadow-sm flex items-center justify-center gap-2 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Filter
            </button>
        </form>
    </div>

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
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Marked By</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($attendances as $record)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition group">
                            <td class="px-6 py-3.5 text-sm text-gray-900 dark:text-white whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    {{ $record->date->format('M j, Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 text-xs font-bold ring-2 ring-white dark:ring-gray-800">
                                        {{ strtoupper(substr($record->user->first_name, 0, 1)) }}
                                    </div>
                                    <span class="text-sm text-gray-900 dark:text-white font-medium">
                                        {{ $record->user->first_name }} {{ $record->user->last_name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5">
                                @php
                                    $statusConfig = [
                                        'present' => ['bg' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', 'icon' => 'M5 13l4 4L19 7'],
                                        'absent' => ['bg' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', 'icon' => 'M6 18L18 6M6 6l12 12'],
                                        'late' => ['bg' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                                        'on_leave' => ['bg' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                                    ];
                                    $config = $statusConfig[$record->status] ?? ['bg' => 'bg-gray-100 text-gray-800', 'icon' => ''];
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $config['bg'] }}">
                                    @if($config['icon'])
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $config['icon'] }}"/>
                                        </svg>
                                    @endif
                                    {{ ucfirst($record->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('g:i A') : '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('g:i A') : '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-sm text-gray-500 dark:text-gray-400">
                                {{ $record->marker?->first_name ?? 'System' }}
                            </td>
                            <td class="px-6 py-3.5 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                {{ $record->notes ?? '-' }}
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
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Try adjusting your filters or date range</p>
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
                @php
                    $statusConfig = [
                        'present' => ['bg' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', 'border' => 'border-green-200 dark:border-green-800'],
                        'absent' => ['bg' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', 'border' => 'border-red-200 dark:border-red-800'],
                        'late' => ['bg' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', 'border' => 'border-yellow-200 dark:border-yellow-800'],
                        'on_leave' => ['bg' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300', 'border' => 'border-blue-200 dark:border-blue-800'],
                    ];
                    $config = $statusConfig[$record->status] ?? ['bg' => 'bg-gray-100 text-gray-800', 'border' => 'border-gray-200'];
                @endphp
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/20 transition">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 text-sm font-bold ring-2 ring-white dark:ring-gray-800">
                                {{ strtoupper(substr($record->user->first_name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $record->user->first_name }} {{ $record->user->last_name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->date->format('M j, Y') }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $config['bg'] }} border {{ $config['border'] }}">
                            {{ ucfirst($record->status) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                            <p class="text-gray-400 dark:text-gray-500 mb-0.5">Check-in</p>
                            <p class="font-semibold text-gray-700 dark:text-gray-300">{{ $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('g:i A') : '-' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                            <p class="text-gray-400 dark:text-gray-500 mb-0.5">Check-out</p>
                            <p class="font-semibold text-gray-700 dark:text-gray-300">{{ $record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('g:i A') : '-' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-center">
                            <p class="text-gray-400 dark:text-gray-500 mb-0.5">Marked By</p>
                            <p class="font-semibold text-gray-700 dark:text-gray-300">{{ $record->marker?->first_name ?? 'System' }}</p>
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
        <div class="px-4 sm:px-6 py-4 border-t border-gray-100 dark:border-gray-700">
            {{ $attendances->links() }}
        </div>
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
@endsection