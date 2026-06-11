@extends($layout)

@section('title', 'Staff Attendance')

@section('content')
<div class="max-w-6xl mx-auto" x-data="{ openStaff: null }">
    <!-- Header with Stats -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
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
        @if(auth()->user()->isAdmin())
        <a href="{{ route('attendance.report') }}"
           class="px-5 py-2.5 bg-gray-800 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-xl font-medium transition-all duration-200 shadow-lg text-sm flex items-center justify-center gap-2 active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            View Report
        </a>
        @endif
    </div>

    <!-- Quick Stats Bar -->
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-3 border border-gray-100 dark:border-gray-700 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Total</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-3 border border-green-100 dark:border-green-800 text-center">
            <p class="text-xs text-green-600 dark:text-green-400 uppercase">Present</p>
            <p class="text-xl font-bold text-green-700 dark:text-green-300">{{ $stats['present'] }}</p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-3 border border-yellow-100 dark:border-yellow-800 text-center">
            <p class="text-xs text-yellow-600 dark:text-yellow-400 uppercase">Late</p>
            <p class="text-xl font-bold text-yellow-700 dark:text-yellow-300">{{ $stats['late'] }}</p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-3 border border-red-100 dark:border-red-800 text-center">
            <p class="text-xs text-red-600 dark:text-red-400 uppercase">Absent</p>
            <p class="text-xl font-bold text-red-700 dark:text-red-300">{{ $stats['absent'] }}</p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 border border-blue-100 dark:border-blue-800 text-center">
            <p class="text-xs text-blue-600 dark:text-blue-400 uppercase">On Leave</p>
            <p class="text-xl font-bold text-blue-700 dark:text-blue-300">{{ $stats['on_leave'] }}</p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-3 border border-gray-100 dark:border-gray-700 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Pending</p>
            <p class="text-xl font-bold text-gray-700 dark:text-gray-300">{{ $stats['pending'] }}</p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-3 mb-4">
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search staff name..."
                           class="w-full pl-9 pr-4 py-2 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
            </div>
            <select name="filter_status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-teal-500 py-2 px-3">
                <option value="">All Statuses</option>
                <option value="present" {{ request('filter_status') === 'present' ? 'selected' : '' }}>✅ Present</option>
                <option value="late" {{ request('filter_status') === 'late' ? 'selected' : '' }}>⏰ Late</option>
                <option value="absent" {{ request('filter_status') === 'absent' ? 'selected' : '' }}>❌ Absent</option>
                <option value="on_leave" {{ request('filter_status') === 'on_leave' ? 'selected' : '' }}>🏖️ On Leave</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium transition">
                Search
            </button>
            @if(request('search') || request('filter_status'))
                <a href="{{ route('attendance.today') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Clear
                </a>
            @endif
        </form>
    </div>

    @if($scheduledStaff->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                {{ request('search') ? 'No staff found matching "' . request('search') . '"' : 'No Staff Scheduled Today' }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                {{ request('search') ? 'Try a different search term.' : 'All staff have today off or no schedules are set for ' . $today->format('l') . '.' }}
            </p>
        </div>
    @else
        <form action="{{ route('attendance.bulk-mark') }}" method="POST" class="space-y-4" id="attendanceForm">
            @csrf
            <!-- Preserve search/filter on save -->
            @if(request('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif
            @if(request('filter_status'))
                <input type="hidden" name="filter_status" value="{{ request('filter_status') }}">
            @endif
            @if(request('page'))
                <input type="hidden" name="page" value="{{ request('page') }}">
            @endif

            <!-- DESKTOP: Table View -->
            <div class="hidden md:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Staff</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Scheduled</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Check-in</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Check-out</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Quick</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($scheduledStaff as $staff)
                                @php
                                    $attendance = $attendances->get($staff->id);
                                    $exception = $exceptions->get($staff->id);
                                    $schedule = $staff->workSchedules->first();
                                    $isOff = $exception && in_array($exception->type, ['day_off', 'holiday', 'sick_leave', 'urgent_leave']);
                                    $hasCheckIn = $attendance?->check_in !== null;
                                    $hasCheckOut = $attendance?->check_out !== null;
                                    $currentTime = $now->format('H:i');
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition {{ $isOff ? 'opacity-60' : '' }}" id="row_{{ $staff->id }}">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-9 h-9 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 font-bold text-xs ring-2 ring-white dark:ring-gray-800">
                                                {{ strtoupper(substr($staff->first_name, 0, 1) . substr($staff->last_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $staff->first_name }} {{ $staff->last_name }}</p>
                                                <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ $staff->username }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($exception)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                                {{ ucfirst(str_replace('_', ' ', $exception->type)) }}
                                            </span>
                                        @elseif($schedule)
                                            <span class="text-xs text-gray-600 dark:text-gray-300">
                                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="hidden" name="attendances[{{ $staff->id }}][user_id]" value="{{ $staff->id }}">
                                        <select name="attendances[{{ $staff->id }}][status]" id="status_{{ $staff->id }}"
                                                class="block w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:ring-teal-500 py-1.5 px-2"
                                                {{ $isOff ? 'disabled' : '' }}>
                                            <option value="absent" {{ ($attendance?->status ?? 'absent') === 'absent' ? 'selected' : '' }}>❌ Absent</option>
                                            <option value="present" {{ ($attendance?->status ?? '') === 'present' ? 'selected' : '' }}>✅ Present</option>
                                            <option value="late" {{ ($attendance?->status ?? '') === 'late' ? 'selected' : '' }}>⏰ Late</option>
                                            <option value="on_leave" {{ ($attendance?->status ?? '') === 'on_leave' ? 'selected' : '' }}>🏖️ Leave</option>
                                        </select>
                                        @if($isOff)
                                            <input type="hidden" name="attendances[{{ $staff->id }}][status]" value="on_leave">
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="time" name="attendances[{{ $staff->id }}][check_in]" id="checkin_{{ $staff->id }}"
                                               value="{{ $attendance?->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '' }}"
                                               class="block w-24 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:ring-teal-500 py-1.5 px-2"
                                               {{ $isOff ? 'disabled' : '' }}>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="time" name="attendances[{{ $staff->id }}][check_out]" id="checkout_{{ $staff->id }}"
                                               value="{{ $attendance?->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '' }}"
                                               class="block w-24 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:ring-teal-500 py-1.5 px-2"
                                               {{ $isOff ? 'disabled' : '' }}>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1" id="quick_actions_{{ $staff->id }}">
                                            @if(!$hasCheckIn && !$isOff)
                                                <button type="button" onclick="quickCheckIn({{ $staff->id }})"
                                                        class="px-2 py-1 bg-teal-600 hover:bg-teal-700 text-white rounded text-[10px] font-medium transition active:scale-95">
                                                    Check In
                                                </button>
                                            @elseif($hasCheckIn && !$hasCheckOut && !$isOff)
                                                <button type="button" onclick="quickCheckOut({{ $staff->id }})"
                                                        class="px-2 py-1 bg-orange-500 hover:bg-orange-600 text-white rounded text-[10px] font-medium transition active:scale-95">
                                                    Check Out
                                                </button>
                                            @elseif($hasCheckIn && $hasCheckOut)
                                                <span class="text-[10px] text-green-600 dark:text-green-400 font-medium">✓ Done</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" name="attendances[{{ $staff->id }}][notes]"
                                               value="{{ $attendance?->notes ?? '' }}"
                                               placeholder="Note..."
                                               class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:ring-teal-500 py-1.5 px-2 placeholder-gray-400">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                    {{ $scheduledStaff->appends(request()->only(['search', 'filter_status']))->links() }}
                </div>
            </div>

            <!-- MOBILE: Card View -->
            <div class="md:hidden space-y-3">
                @foreach($scheduledStaff as $staff)
                    @php
                        $attendance = $attendances->get($staff->id);
                        $exception = $exceptions->get($staff->id);
                        $schedule = $staff->workSchedules->first();
                        $isOff = $exception && in_array($exception->type, ['day_off', 'holiday', 'sick_leave', 'urgent_leave']);
                        $hasCheckIn = $attendance?->check_in !== null;
                        $hasCheckOut = $attendance?->check_out !== null;
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
                         id="m_row_{{ $staff->id }}">
                        <div class="p-4 flex items-center justify-between cursor-pointer {{ $isOff ? 'opacity-60' : '' }}"
                             @click="openStaff = openStaff === {{ $staff->id }} ? null : {{ $staff->id }}">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-full bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 font-bold text-sm ring-2 ring-white dark:ring-gray-800">
                                    {{ strtoupper(substr($staff->first_name, 0, 1) . substr($staff->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $staff->first_name }} {{ $staff->last_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $staff->username }}</p>
                                    @if($hasCheckIn && $hasCheckOut)
                                        <span class="text-[10px] text-green-600 font-medium">✓ Completed</span>
                                    @elseif($hasCheckIn)
                                        <span class="text-[10px] text-orange-500 font-medium">Checked In</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($exception)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                        {{ ucfirst(str_replace('_', ' ', $exception->type)) }}
                                    </span>
                                @elseif($schedule)
                                    <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}</span>
                                @endif
                                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openStaff === {{ $staff->id }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        <div x-show="openStaff === {{ $staff->id }}" x-collapse class="overflow-hidden">
                            <div class="px-4 pb-4 pt-1 border-t border-gray-100 dark:border-gray-700 space-y-3">
                                @if($exception)
                                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-2 text-xs text-yellow-800 dark:text-yellow-300">
                                        <strong>Reason:</strong> {{ $exception->reason }}
                                    </div>
                                @endif
                                @if(!$isOff)
                                    <div class="flex gap-2" id="m_quick_actions_{{ $staff->id }}">
                                        @if(!$hasCheckIn)
                                            <button type="button" onclick="quickCheckIn({{ $staff->id }})"
                                                    class="flex-1 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-medium transition active:scale-95">
                                                ⚡ Check In Now
                                            </button>
                                        @elseif($hasCheckIn && !$hasCheckOut)
                                            <button type="button" onclick="quickCheckOut({{ $staff->id }})"
                                                    class="flex-1 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-xs font-medium transition active:scale-95">
                                                ⚡ Check Out Now
                                            </button>
                                        @endif
                                    </div>
                                @endif
                                <input type="hidden" name="attendances[{{ $staff->id }}][user_id]" value="{{ $staff->id }}">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                                    <select name="attendances[{{ $staff->id }}][status]" id="m_status_{{ $staff->id }}"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2 px-3"
                                            {{ $isOff ? 'disabled' : '' }}>
                                        <option value="absent" {{ ($attendance?->status ?? 'absent') === 'absent' ? 'selected' : '' }}>❌ Absent</option>
                                        <option value="present" {{ ($attendance?->status ?? '') === 'present' ? 'selected' : '' }}>✅ Present</option>
                                        <option value="late" {{ ($attendance?->status ?? '') === 'late' ? 'selected' : '' }}>⏰ Late</option>
                                        <option value="on_leave" {{ ($attendance?->status ?? '') === 'on_leave' ? 'selected' : '' }}>🏖️ On Leave</option>
                                    </select>
                                    @if($isOff)
                                        <input type="hidden" name="attendances[{{ $staff->id }}][status]" value="on_leave">
                                    @endif
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Check-in</label>
                                        <input type="time" name="attendances[{{ $staff->id }}][check_in]" id="m_checkin_{{ $staff->id }}"
                                               value="{{ $attendance?->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '' }}"
                                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2 px-3"
                                               {{ $isOff ? 'disabled' : '' }}>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Check-out</label>
                                        <input type="time" name="attendances[{{ $staff->id }}][check_out]" id="m_checkout_{{ $staff->id }}"
                                               value="{{ $attendance?->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '' }}"
                                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2 px-3"
                                               {{ $isOff ? 'disabled' : '' }}>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Notes</label>
                                    <input type="text" name="attendances[{{ $staff->id }}][notes]"
                                           value="{{ $attendance?->notes ?? '' }}"
                                           placeholder="Optional..."
                                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2 px-3 placeholder-gray-400">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <!-- Mobile Pagination -->
                <div class="mt-4">
                    {{ $scheduledStaff->appends(request()->only(['search', 'filter_status']))->links() }}
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end sticky bottom-4 z-10">
                <button type="submit"
                        class="w-full md:w-auto px-8 py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-semibold shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Page
                </button>
            </div>
        </form>
    @endif
</div>

@push('scripts')
<script>
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

    /**
     * AJAX Quick Check-In — no page reload
     */
    function quickCheckIn(staffId) {
        fetch(`/api/attendance/quick-checkin/${staffId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update desktop inputs
                const checkInInput = document.getElementById('checkin_' + staffId);
                const statusSelect = document.getElementById('status_' + staffId);
                const quickActions = document.getElementById('quick_actions_' + staffId);

                // Update mobile inputs
                const mCheckIn = document.getElementById('m_checkin_' + staffId);
                const mStatus = document.getElementById('m_status_' + staffId);
                const mQuickActions = document.getElementById('m_quick_actions_' + staffId);

                const now = new Date();
                const timeStr = now.toTimeString().slice(0, 5);

                if (checkInInput) checkInInput.value = timeStr;
                if (statusSelect) statusSelect.value = data.status;
                if (mCheckIn) mCheckIn.value = timeStr;
                if (mStatus) mStatus.value = data.status;

                // Replace button with Check Out
                const newBtn = `<button type="button" onclick="quickCheckOut(${staffId})" class="px-2 py-1 bg-orange-500 hover:bg-orange-600 text-white rounded text-[10px] font-medium transition active:scale-95">Check Out</button>`;
                const mNewBtn = `<button type="button" onclick="quickCheckOut(${staffId})" class="flex-1 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-xs font-medium transition active:scale-95">⚡ Check Out Now</button>`;

                if (quickActions) quickActions.innerHTML = newBtn;
                if (mQuickActions) mQuickActions.innerHTML = mNewBtn;

                showToast(data.message, 'success');
            } else {
                showToast(data.error || 'Failed', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Network error. Form will save on submit.', 'warning');
        });
    }

    /**
     * AJAX Quick Check-Out — no page reload
     */
    function quickCheckOut(staffId) {
        fetch(`/api/attendance/quick-checkout/${staffId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const checkOutInput = document.getElementById('checkout_' + staffId);
                const quickActions = document.getElementById('quick_actions_' + staffId);
                const mCheckOut = document.getElementById('m_checkout_' + staffId);
                const mQuickActions = document.getElementById('m_quick_actions_' + staffId);

                const now = new Date();
                const timeStr = now.toTimeString().slice(0, 5);

                if (checkOutInput) checkOutInput.value = timeStr;
                if (mCheckOut) mCheckOut.value = timeStr;

                // Replace button with Done
                const doneBadge = `<span class="text-[10px] text-green-600 dark:text-green-400 font-medium">✓ Done</span>`;
                if (quickActions) quickActions.innerHTML = doneBadge;
                if (mQuickActions) mQuickActions.innerHTML = doneBadge;

                showToast(data.message, 'success');
            } else {
                showToast(data.error || 'Failed', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Network error. Form will save on submit.', 'warning');
        });
    }

    function showToast(message, type) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: message,
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#374151'
            });
        }
    }
</script>
@endpush
@endsection