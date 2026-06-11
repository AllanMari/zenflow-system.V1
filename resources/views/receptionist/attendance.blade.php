@extends('layouts.receptionist')

@section('title', 'Staff Attendance')

@section('content')
<div class="max-w-6xl mx-auto" x-data="{ openStaff: null }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Staff Attendance
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                {{ $today->format('l, F j, Y') }}
            </p>
        </div>
        <div class="bg-teal-100 dark:bg-teal-900/30 text-teal-800 dark:text-teal-300 px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            Mark who is present today
        </div>
    </div>

    @if($scheduledStaff->isEmpty())
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Staff Scheduled Today</h3>
            <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto">All staff have today off or no schedules are set for {{ $today->format('l') }}.</p>
        </div>
    @else
        <form action="{{ route('attendance.bulk-mark') }}" method="POST" class="space-y-4">
            @csrf

            <!-- DESKTOP: Table View -->
            <div class="hidden md:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Staff</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Scheduled</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check-in</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($scheduledStaff as $staff)
                                @php
                                    $attendance = $attendances->get($staff->id);
                                    $exception = $exceptions->get($staff->id);
                                    $schedule = $staff->workSchedules->first();
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 font-bold text-sm ring-2 ring-white dark:ring-gray-800 shadow-sm">
                                                {{ strtoupper(substr($staff->first_name, 0, 1) . substr($staff->last_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-white">{{ $staff->first_name }} {{ $staff->last_name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $staff->username }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($exception)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                </svg>
                                                {{ ucfirst(str_replace('_', ' ', $exception->type)) }}
                                            </span>
                                            <p class="text-xs text-gray-500 mt-1">{{ $exception->reason }}</p>
                                        @elseif($schedule)
                                            <span class="inline-flex items-center gap-1 text-sm text-gray-700 dark:text-gray-300">
                                                <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">No schedule</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="hidden" name="attendances[{{ $loop->index }}][user_id]" value="{{ $staff->id }}">
                                        <select name="attendances[{{ $loop->index }}][status]"
                                                class="block w-36 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-teal-500 focus:border-teal-500 py-2 px-3 shadow-sm">
                                            <option value="absent" {{ ($attendance?->status ?? 'absent') === 'absent' ? 'selected' : '' }}>❌ Absent</option>
                                            <option value="present" {{ ($attendance?->status ?? '') === 'present' ? 'selected' : '' }}>✅ Present</option>
                                            <option value="late" {{ ($attendance?->status ?? '') === 'late' ? 'selected' : '' }}>⏰ Late</option>
                                            <option value="on_leave" {{ ($attendance?->status ?? '') === 'on_leave' ? 'selected' : '' }}>🏖️ On Leave</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="time"
                                               name="attendances[{{ $loop->index }}][check_in]"
                                               value="{{ $attendance?->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '' }}"
                                               class="block w-28 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-teal-500 focus:border-teal-500 py-2 px-3 shadow-sm">
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="text"
                                               name="attendances[{{ $loop->index }}][notes]"
                                               value="{{ $attendance?->notes ?? '' }}"
                                               placeholder="Optional note..."
                                               class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-teal-500 focus:border-teal-500 py-2 px-3 shadow-sm placeholder-gray-400 dark:placeholder-gray-500">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MOBILE: Card View -->
            <div class="md:hidden space-y-3">
                @foreach($scheduledStaff as $staff)
                    @php
                        $attendance = $attendances->get($staff->id);
                        $exception = $exceptions->get($staff->id);
                        $schedule = $staff->workSchedules->first();
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden transition-all duration-200"
                         :class="openStaff === {{ $staff->id }} ? 'ring-2 ring-teal-500/30' : ''">

                        <!-- Card Header (Always Visible) -->
                        <div class="p-4 flex items-center justify-between cursor-pointer"
                             @click="openStaff = openStaff === {{ $staff->id }} ? null : {{ $staff->id }}">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 font-bold text-sm ring-2 ring-white dark:ring-gray-800 shadow-sm">
                                    {{ strtoupper(substr($staff->first_name, 0, 1) . substr($staff->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $staff->first_name }} {{ $staff->last_name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $staff->username }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($exception)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                        {{ ucfirst(str_replace('_', ' ', $exception->type)) }}
                                    </span>
                                @elseif($schedule)
                                    <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                        <svg class="w-3 h-3 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}
                                    </span>
                                @endif
                                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                     :class="openStaff === {{ $staff->id }} ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Expandable Form Section -->
                        <div x-show="openStaff === {{ $staff->id }}"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 max-h-0"
                             x-transition:enter-end="opacity-100 max-h-96"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 max-h-96"
                             x-transition:leave-end="opacity-0 max-h-0"
                             class="overflow-hidden">
                            <div class="px-4 pb-4 pt-1 border-t border-gray-100 dark:border-gray-700 space-y-3">
                                @if($exception)
                                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 text-xs text-yellow-800 dark:text-yellow-300">
                                        <span class="font-semibold">Reason:</span> {{ $exception->reason }}
                                    </div>
                                @endif

                                <input type="hidden" name="attendances[{{ $loop->index }}][user_id]" value="{{ $staff->id }}">

                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Status</label>
                                    <select name="attendances[{{ $loop->index }}][status]"
                                            class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-teal-500 focus:border-teal-500 py-2.5 px-3 shadow-sm">
                                        <option value="absent" {{ ($attendance?->status ?? 'absent') === 'absent' ? 'selected' : '' }}>❌ Absent</option>
                                        <option value="present" {{ ($attendance?->status ?? '') === 'present' ? 'selected' : '' }}>✅ Present</option>
                                        <option value="late" {{ ($attendance?->status ?? '') === 'late' ? 'selected' : '' }}>⏰ Late</option>
                                        <option value="on_leave" {{ ($attendance?->status ?? '') === 'on_leave' ? 'selected' : '' }}>🏖️ On Leave</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Check-in Time</label>
                                    <input type="time"
                                           name="attendances[{{ $loop->index }}][check_in]"
                                           value="{{ $attendance?->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '' }}"
                                           class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-teal-500 focus:border-teal-500 py-2.5 px-3 shadow-sm">
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Notes</label>
                                    <input type="text"
                                           name="attendances[{{ $loop->index }}][notes]"
                                           value="{{ $attendance?->notes ?? '' }}"
                                           placeholder="Optional note..."
                                           class="block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-teal-500 focus:border-teal-500 py-2.5 px-3 shadow-sm placeholder-gray-400 dark:placeholder-gray-500">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Save Button -->
            <div class="flex justify-end sticky bottom-4 z-10">
                <button type="submit"
                        class="w-full md:w-auto px-8 py-3.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-semibold shadow-lg shadow-teal-200 dark:shadow-none transition-all duration-200 active:scale-95 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Attendance
                </button>
            </div>
        </form>
    @endif
</div>
@endsection