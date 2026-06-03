@extends(auth()->user()->roles->contains('name', 'admin') ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Staff Schedules')

@push('styles')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' />
<style>
    /* ===== DARK MODE VARIABLES ===== */
    :root {
        --bg-primary: #ffffff;
        --bg-secondary: #f8fafc;
        --bg-card: #ffffff;
        --bg-hover: #f1f5f9;
        --border-color: #e2e8f0;
        --text-primary: #0f172a;
        --text-secondary: #64748b;
        --text-muted: #94a3b8;
        --accent-teal: #0d9488;
        --accent-teal-light: #ccfbf1;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
    }

    .dark {
        --bg-primary: #0f172a;
        --bg-secondary: #1e293b;
        --bg-card: #1e293b;
        --bg-hover: #334155;
        --border-color: #334155;
        --text-primary: #f1f5f9;
        --text-secondary: #cbd5e1;
        --text-muted: #64748b;
        --accent-teal: #2dd4bf;
        --accent-teal-light: #134e4a;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.3);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.4);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.5);
    }

    /* ===== FULLCALENDAR MODERN STYLING ===== */
    .fc {
        --fc-border-color: var(--border-color);
        --fc-page-bg-color: var(--bg-primary);
        --fc-neutral-bg-color: var(--bg-secondary);
        --fc-neutral-text-color: var(--text-secondary);
        --fc-button-text-color: var(--text-primary);
        --fc-button-bg-color: var(--bg-secondary);
        --fc-button-border-color: var(--border-color);
        --fc-button-hover-bg-color: var(--bg-hover);
        --fc-button-hover-border-color: var(--border-color);
        --fc-button-active-bg-color: var(--accent-teal-light);
        --fc-button-active-border-color: var(--accent-teal);
        --fc-today-bg-color: rgba(13, 148, 136, 0.08);
        --fc-event-bg-color: var(--accent-teal);
        --fc-event-border-color: var(--accent-teal);
        --fc-event-text-color: #ffffff;
        --fc-list-event-hover-bg-color: var(--bg-hover);
    }

    .fc .fc-toolbar-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .fc .fc-button {
        padding: 6px 14px;
        font-size: 13px;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        background-color: var(--accent-teal);
        border-color: var(--accent-teal);
        color: white;
    }

    .fc .fc-col-header-cell-cushion {
        font-size: 13px;
        font-weight: 600;
        padding: 10px 4px;
        color: var(--text-secondary);
    }

    .fc-timegrid-slot-label-cushion {
        font-size: 12px;
        color: var(--text-muted);
    }

    .fc-timegrid-axis-cushion {
        font-size: 12px;
        color: var(--text-muted);
    }

    .fc-event {
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        border: none;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        padding: 2px 6px;
        transition: transform 0.15s, box-shadow 0.15s;
    }

    .fc-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        z-index: 100 !important;
    }

    .fc-event.off-event {
        background: repeating-linear-gradient(
            45deg,
            #ef4444,
            #ef4444 10px,
            #dc2626 10px,
            #dc2626 20px
        ) !important;
        opacity: 0.85;
    }

    .fc-event.custom-event {
        background: linear-gradient(135deg, #eab308, #ca8a04) !important;
    }

    /* ===== STAFF LANES (GANTT-STYLE) ===== */
    .staff-lane {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 10px;
        background: var(--bg-secondary);
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
        user-select: none;
    }

    .staff-lane:hover {
        background: var(--bg-hover);
        transform: translateX(4px);
    }

    .staff-lane.active {
        border-color: currentColor;
        background: var(--bg-primary);
        box-shadow: var(--shadow-md);
    }

    .staff-lane.inactive {
        opacity: 0.4;
        filter: grayscale(0.6);
    }

    .staff-lane-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        color: white;
        flex-shrink: 0;
    }

    .staff-lane-info {
        flex: 1;
        min-width: 0;
    }

    .staff-lane-name {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .staff-lane-hours {
        font-size: 12px;
        color: var(--text-muted);
    }

    /* ===== SCHEDULE CARDS ===== */
    .schedule-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
    }

    .schedule-card:hover {
        box-shadow: var(--shadow-md);
    }

    .staff-header {
        background: linear-gradient(135deg, var(--accent-teal-light), rgba(13, 148, 136, 0.05));
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .dark .staff-header {
        background: linear-gradient(135deg, rgba(45, 212, 191, 0.1), rgba(15, 23, 42, 0.5));
    }

    /* ===== DAY CELLS ===== */
    .day-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
        padding: 16px;
    }

    .day-cell {
        border-radius: 12px;
        padding: 12px 8px;
        text-align: center;
        transition: all 0.2s;
        position: relative;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .day-cell.on {
        background: #d1fae5;
        border: 2px solid #6ee7b7;
    }

    .dark .day-cell.on {
        background: rgba(16, 185, 129, 0.15);
        border-color: #10b981;
    }

    .day-cell.off {
        background: #fee2e2;
        border: 2px solid #fca5a5;
    }

    .dark .day-cell.off {
        background: rgba(239, 68, 68, 0.1);
        border-color: #ef4444;
    }

    .day-cell:hover {
        transform: scale(1.03);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .day-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }

    .day-cell.on .day-label {
        color: #047857;
    }

    .dark .day-cell.on .day-label {
        color: #34d399;
    }

    .day-cell.off .day-label {
        color: #dc2626;
    }

    .dark .day-cell.off .day-label {
        color: #f87171;
    }

    /* ===== TIME INPUTS ===== */
    .time-input-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .time-input-label {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
    }

    .time-input {
        width: 85px;
        text-align: center;
        font-size: 14px;
        font-weight: 600;
        padding: 6px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: var(--bg-primary);
        color: var(--text-primary);
        transition: all 0.2s;
    }

    .time-input:focus {
        outline: none;
        border-color: var(--accent-teal);
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    }

    .time-controls {
        display: flex;
        gap: 4px;
        justify-content: center;
        margin-top: 4px;
    }

    .time-btn {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: var(--bg-primary);
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.15s;
    }

    .time-btn:hover {
        background: var(--accent-teal);
        color: white;
        border-color: var(--accent-teal);
    }

    /* ===== OFF STATE ===== */
    .off-icon {
        width: 32px;
        height: 32px;
        margin: 0 auto 6px;
        color: #ef4444;
    }

    .off-text {
        font-size: 13px;
        font-weight: 700;
        color: #ef4444;
    }

    .off-hint {
        font-size: 10px;
        color: var(--text-muted);
        margin-top: 4px;
    }

    /* ===== READ-ONLY TIME DISPLAY ===== */
    .time-display {
        font-size: 20px;
        font-weight: 800;
        color: var(--accent-teal);
        line-height: 1;
    }

    .time-separator {
        font-size: 11px;
        color: var(--text-muted);
        margin: 4px 0;
        font-weight: 500;
    }

    /* ===== GLOBAL PRESETS ===== */
    .preset-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid var(--border-color);
        background: var(--bg-card);
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.2s;
    }

    .preset-btn:hover {
        background: var(--bg-hover);
        border-color: var(--accent-teal);
        color: var(--accent-teal);
    }

    .preset-btn.danger:hover {
        border-color: #ef4444;
        color: #ef4444;
    }

    /* ===== SAVE BAR ===== */
    .save-bar {
        position: sticky;
        bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 24px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        box-shadow: var(--shadow-lg);
        margin-top: 24px;
    }

    /* ===== EXCEPTIONS ===== */
    .exception-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        transition: all 0.2s;
    }

    .exception-card:hover {
        background: var(--bg-hover);
    }

    .exception-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        color: white;
        flex-shrink: 0;
    }

    /* ===== ANIMATIONS ===== */
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }

    .animate-pulse-dot {
        animation: pulse-dot 1.5s ease-in-out infinite;
    }

    /* ===== SCROLLBAR ===== */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: var(--bg-secondary);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--text-muted);
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto space-y-8 pb-12">

    {{-- HEADER --}}
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight" style="color: var(--text-primary);">
                Staff Schedules
            </h1>
            <p class="mt-2 text-base" style="color: var(--text-secondary);">
                @if($isAdmin)
                    Manage weekly shifts and availability
                @else
                    View who's working and when
                @endif
            </p>
        </div>
        
        @if($canEdit)
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="applyGlobalPreset('standard')" class="preset-btn">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Standard Week
            </button>
            <button type="button" onclick="applyGlobalPreset('weekdays')" class="preset-btn">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-8a2 2 0 012-2h14a2 2 0 012 2v8M9 10V7a3 3 0 013-3h0a3 3 0 013 3v3"/></svg>
                Weekdays Only
            </button>
            <button type="button" onclick="applyGlobalPreset('alloff')" class="preset-btn danger">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                All Off
            </button>
        </div>
        @endif
    </div>

    {{-- WEEKLY CALENDAR OVERVIEW --}}
    <div class="schedule-card">
        <div class="p-5 border-b" style="border-color: var(--border-color);">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold flex items-center gap-2" style="color: var(--text-primary);">
                        <svg class="w-5 h-5" style="color: var(--accent-teal);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Weekly Overview
                    </h2>
                    <p class="text-xs mt-1" style="color: var(--text-muted);">Click staff to filter • 30-min intervals</p>
                </div>
                
                {{-- Staff Lanes --}}
                <div class="flex flex-wrap gap-2" id="staffFilter">
                    @foreach($staff as $idx => $s)
                        @php 
                            $colors = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4', '#84cc16', '#f97316'];
                            $c = $colors[$idx % count($colors)];
                        @endphp
                        <div class="staff-lane active" 
                             data-staff="{{ $s->id }}"
                             onclick="toggleStaff({{ $s->id }})"
                             style="color: {{ $c }};">
                            <div class="staff-lane-avatar" style="background-color: {{ $c }};">
                                {{ substr($s->first_name, 0, 1) }}
                            </div>
                            <div class="staff-lane-info">
                                <div class="staff-lane-name">{{ $s->first_name }}</div>
                                <div class="staff-lane-hours">{{ $s->workSchedules->where('is_day_off', false)->count() }} days</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <div id="scheduleCalendar" class="p-4 min-h-[480px]"></div>
        
        <div class="px-5 pb-4 flex gap-6 text-xs justify-center flex-wrap" style="color: var(--text-muted);">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span> Working</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> Off / Holiday</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span> Custom Hours</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-gray-500"></span> No Schedule</span>
        </div>
    </div>

    {{-- ADMIN: RECEPTIONIST PERMISSIONS --}}
    @if($isAdmin)
    <div class="schedule-card p-6">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-2" style="color: var(--text-primary);">
            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Receptionist Permissions
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse($receptionists as $rec)
            <div class="flex items-center justify-between p-4 rounded-xl border" style="background: var(--bg-secondary); border-color: var(--border-color);">
                <div>
                    <div class="font-semibold text-sm" style="color: var(--text-primary);">{{ $rec->first_name }} {{ $rec->last_name }}</div>
                    <div class="text-xs mt-0.5" style="color: var(--text-muted);">
                        {{ $rec->can_manage_schedules ? 'Can edit schedules' : 'View only access' }}
                    </div>
                </div>
                <form action="{{ route('admin.receptionist.toggle', $rec) }}" method="POST" class="inline">
                    @csrf
                    @method('PUT')
                    <button type="submit" 
                        class="text-xs px-4 py-2 rounded-lg transition font-semibold
                        {{ $rec->can_manage_schedules 
                            ? 'bg-orange-100 text-orange-700 hover:bg-orange-200 dark:bg-orange-900/30 dark:text-orange-300' 
                            : 'bg-teal-100 text-teal-700 hover:bg-teal-200 dark:bg-teal-900/30 dark:text-teal-300' 
                        }}">
                        {{ $rec->can_manage_schedules ? 'Revoke' : 'Grant' }}
                    </button>
                </form>
            </div>
            @empty
                <p class="text-sm col-span-full" style="color: var(--text-muted);">No receptionists found.</p>
            @endforelse
        </div>
    </div>
    @endif

    {{-- VIEW-ONLY WARNING --}}
    @if(!$isAdmin && !$canEdit)
    <div class="p-4 rounded-xl border flex items-center gap-3" style="background: rgba(234, 179, 8, 0.08); border-color: rgba(234, 179, 8, 0.3);">
        <svg class="w-6 h-6 text-yellow-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <p class="text-yellow-700 dark:text-yellow-300 text-sm font-medium">View only mode. Contact admin to modify schedules.</p>
    </div>
    @endif

    {{-- EDIT SCHEDULES --}}
    <div class="schedule-card">
        <div class="staff-header">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-teal-600 text-white flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold" style="color: var(--text-primary);">Weekly Shift Editor</h2>
                    <p class="text-xs" style="color: var(--text-muted);">Click days to toggle • +/- buttons adjust by 30 min</p>
                </div>
            </div>
            @if($canEdit)
            <span class="text-xs font-semibold px-3 py-1 rounded-full" style="background: var(--accent-teal-light); color: var(--accent-teal);">30-min intervals</span>
            @endif
        </div>

        @if($canEdit)
        <form id="scheduleForm" action="{{ $isAdmin ? route('admin.staff.schedule.bulk-update') : route('receptionist.schedules.bulk-update') }}" method="POST">
            @csrf
        @endif
        
        <div class="divide-y" style="border-color: var(--border-color);">
            @foreach($staff as $s)
            @php
                $userSchedules = $s->workSchedules->keyBy('day_of_week');
                $days = [
                    1 => ['Mon', 'Monday'],
                    2 => ['Tue', 'Tuesday'], 
                    3 => ['Wed', 'Wednesday'],
                    4 => ['Thu', 'Thursday'],
                    5 => ['Fri', 'Friday'],
                    6 => ['Sat', 'Saturday'],
                    0 => ['Sun', 'Sunday']
                ];
                $staffColor = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4', '#84cc16', '#f97316'][$loop->index % 8];
            @endphp
            
            <div class="p-5" data-staff-id="{{ $s->id }}">
                {{-- Staff Row Header --}}
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full text-white flex items-center justify-center font-bold text-sm" style="background-color: {{ $staffColor }};">
                            {{ substr($s->first_name, 0, 1) }}{{ substr($s->last_name, 0, 1) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-base" style="color: var(--text-primary);">{{ $s->first_name }} {{ $s->last_name }}</h3>
                            <p class="text-xs" style="color: var(--text-muted);">{{ $s->workSchedules->where('is_day_off', false)->count() }} working days</p>
                        </div>
                    </div>
                    @if($canEdit)
                    <div class="flex gap-2">
                        <button type="button" onclick="applyStaffPreset({{ $s->id }}, 'standard')" class="preset-btn text-xs">
                            Standard
                        </button>
                        <button type="button" onclick="applyStaffPreset({{ $s->id }}, 'weekdays')" class="preset-btn text-xs">
                            Mon-Fri
                        </button>
                        <button type="button" onclick="applyStaffPreset({{ $s->id }}, 'alloff')" class="preset-btn danger text-xs">
                            All Off
                        </button>
                    </div>
                    @endif
                </div>
                
                {{-- 7-Day Grid --}}
                <div class="day-grid">
                    @foreach($days as $num => $dayNames)
                        @php
                            $sch = $userSchedules[$num] ?? null;
                            $isOff = $sch ? $sch->is_day_off : true;
                            $startVal = $sch ? substr($sch->start_time, 11, 5) : '09:00';
                            $endVal = $sch ? substr($sch->end_time, 11, 5) : '18:00';
                        @endphp
                        <div class="day-cell {{ $isOff ? 'off' : 'on' }}"
                             id="day-cell-{{ $s->id }}-{{ $num }}"
                             @if($canEdit) onclick="toggleDay({{ $s->id }}, {{ $num }})" style="cursor: pointer;" @endif>
                            
                            <input type="hidden" name="schedules[{{ $s->id }}][{{ $num }}][day_of_week]" value="{{ $num }}">
                            <input type="hidden" name="schedules[{{ $s->id }}][{{ $num }}][is_day_off]" value="{{ $isOff ? '1' : '0' }}" id="input-off-{{ $s->id }}-{{ $num }}">
                            
                            <div class="day-label">{{ $dayNames[0] }}</div>
                            
                            @if($canEdit)
                                <div class="time-edit {{ $isOff ? 'hidden' : '' }}" id="times-{{ $s->id }}-{{ $num }}">
                                    <div class="time-input-group">
                                        <span class="time-input-label">Start</span>
                                        <input type="time" 
                                               name="schedules[{{ $s->id }}][{{ $num }}][start_time]" 
                                               value="{{ $startVal }}"
                                               step="1800"
                                               class="time-input"
                                               onclick="event.stopPropagation()">
                                        <div class="time-controls">
                                            <button type="button" class="time-btn" onclick="event.stopPropagation(); adjustTime(this, -30)">-30</button>
                                            <button type="button" class="time-btn" onclick="event.stopPropagation(); adjustTime(this, 30)">+30</button>
                                        </div>
                                    </div>
                                    <div class="time-input-group mt-2">
                                        <span class="time-input-label">End</span>
                                        <input type="time" 
                                               name="schedules[{{ $s->id }}][{{ $num }}][end_time]" 
                                               value="{{ $endVal }}"
                                               step="1800"
                                               class="time-input"
                                               onclick="event.stopPropagation()">
                                        <div class="time-controls">
                                            <button type="button" class="time-btn" onclick="event.stopPropagation(); adjustTime(this, -30)">-30</button>
                                            <button type="button" class="time-btn" onclick="event.stopPropagation(); adjustTime(this, 30)">+30</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="day-off-label {{ $isOff ? '' : 'hidden' }}" id="off-label-{{ $s->id }}-{{ $num }}">
                                    <svg class="off-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <div class="off-text">OFF</div>
                                    <div class="off-hint">Click to work</div>
                                </div>
                            @else
                                @if($isOff)
                                    <svg class="off-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <div class="off-text">OFF</div>
                                @else
                                    <div class="time-display">{{ $startVal }}</div>
                                    <div class="time-separator">to</div>
                                    <div class="time-display">{{ $endVal }}</div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        
        @if($canEdit)
        <div class="save-bar">
            <div class="flex items-center gap-2 text-sm font-medium" style="color: var(--text-secondary);">
                <span id="unsaved-indicator" class="hidden flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-orange-500 animate-pulse-dot"></span>
                    Unsaved changes
                </span>
            </div>
            <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-xl text-sm font-bold transition shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Save All Changes
            </button>
        </div>
        </form>
        @endif
    </div>

    {{-- ADMIN: DATE EXCEPTIONS --}}
    @if($isAdmin)
    <div class="schedule-card p-6">
        <h2 class="text-lg font-bold mb-1 flex items-center gap-2" style="color: var(--text-primary);">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Date Exceptions
        </h2>
        <p class="text-xs mb-6" style="color: var(--text-muted);">Holidays, day-offs, and custom hours for specific dates</p>
        
        <form action="{{ route('admin.schedule.exception') }}" method="POST" class="flex flex-wrap gap-3 items-end mb-6 p-4 rounded-xl border" style="background: var(--bg-secondary); border-color: var(--border-color);">
            @csrf
            <div class="flex-1 min-w-[140px]">
                <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);">Staff</label>
                <select name="user_id" class="w-full border rounded-lg p-2.5 text-sm font-medium transition focus:ring-2 focus:ring-teal-500 focus:border-teal-500" style="background: var(--bg-card); border-color: var(--border-color); color: var(--text-primary);">
                    @foreach($staff as $s)
                        <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);">Date</label>
                <input type="date" name="exception_date" required 
                       class="w-full border rounded-lg p-2.5 text-sm transition focus:ring-2 focus:ring-teal-500 focus:border-teal-500" style="background: var(--bg-card); border-color: var(--border-color); color: var(--text-primary);">
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);">Type</label>
                <select name="type" id="exceptionType" onchange="toggleExceptionTimes()" 
                        class="w-full border rounded-lg p-2.5 text-sm transition focus:ring-2 focus:ring-teal-500 focus:border-teal-500" style="background: var(--bg-card); border-color: var(--border-color); color: var(--text-primary);">
                    <option value="day_off">Day Off</option>
                    <option value="holiday">Holiday</option>
                    <option value="custom_hours">Custom Hours</option>
                </select>
            </div>
            <div id="exceptionTimes" class="hidden flex gap-2">
                <div>
                    <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);">Start</label>
                    <input type="time" name="start_time" value="09:00" step="1800"
                           class="border rounded-lg p-2.5 text-sm transition focus:ring-2 focus:ring-teal-500" style="background: var(--bg-card); border-color: var(--border-color); color: var(--text-primary);">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);">End</label>
                    <input type="time" name="end_time" value="18:00" step="1800"
                           class="border rounded-lg p-2.5 text-sm transition focus:ring-2 focus:ring-teal-500" style="background: var(--bg-card); border-color: var(--border-color); color: var(--text-primary);">
                </div>
            </div>
            <div class="flex-[2] min-w-[200px]">
                <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider" style="color: var(--text-muted);">Reason</label>
                <input type="text" name="reason" placeholder="Optional note..." 
                       class="w-full border rounded-lg p-2.5 text-sm transition focus:ring-2 focus:ring-teal-500 focus:border-teal-500" style="background: var(--bg-card); border-color: var(--border-color); color: var(--text-primary);">
            </div>
            <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2.5 rounded-lg transition text-sm font-bold shadow-md">
                + Add
            </button>
        </form>

        <div class="space-y-2 max-h-80 overflow-y-auto pr-2">
            @php
                $exceptions = \App\Models\ScheduleException::with('user')
                    ->whereDate('exception_date', '>=', today())
                    ->orderBy('exception_date')
                    ->get();
            @endphp
            
            @forelse($exceptions as $ex)
            <div class="exception-card">
                <div class="exception-avatar" style="background-color: {{ ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4', '#84cc16', '#f97316'][$loop->index % 8] }};">
                    {{ substr($ex->user->first_name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-sm" style="color: var(--text-primary);">{{ $ex->user->first_name }} {{ $ex->user->last_name }}</span>
                        <span class="text-xs" style="color: var(--text-muted);">{{ $ex->exception_date->format('M d, Y') }}</span>
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold
                            {{ $ex->type === 'day_off' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 
                               ($ex->type === 'holiday' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300') }}">
                            {{ ucfirst(str_replace('_', ' ', $ex->type)) }}
                        </span>
                        @if($ex->start_time && $ex->end_time)
                            <span class="text-xs font-mono" style="color: var(--text-muted);">{{ substr($ex->start_time, 11, 5) }} - {{ substr($ex->end_time, 11, 5) }}</span>
                        @endif
                    </div>
                    @if($ex->reason)
                        <div class="text-xs mt-1 italic" style="color: var(--text-muted);">{{ $ex->reason }}</div>
                    @endif
                </div>
                <form action="{{ route('admin.schedule.exception.delete', $ex) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">Remove</button>
                </form>
            </div>
            @empty
                <div class="text-center py-10" style="color: var(--text-muted);">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm">No upcoming exceptions</p>
                </div>
            @endforelse
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script>
    let calendar;
    const allEvents = @json($calendarEvents ?? []);
    let hiddenStaff = new Set();

    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('scheduleCalendar');
        if (!calendarEl) return;
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            firstDay: 1,
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            allDaySlot: true,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'timeGridWeek,dayGridMonth'
            },
            buttonText: {
                today: 'Today',
                week: 'Week',
                month: 'Month'
            },
            events: allEvents,
            eventClick: function(info) {
                info.jsEvent.preventDefault();
            },
            eventDidMount: function(info) {
                if (info.event.extendedProps.staffId) {
                    info.el.dataset.staffId = info.event.extendedProps.staffId;
                }
                
                const type = info.event.extendedProps.type;
                if (type === 'off') info.el.classList.add('off-event');
                if (type === 'custom') info.el.classList.add('custom-event');
                
                // Add tooltip
                const staffName = info.event.extendedProps.staffName || '';
                info.el.title = staffName + ' | ' + info.event.title;
            },
            height: 'auto',
            dayHeaderFormat: { weekday: 'short', month: 'numeric', day: 'numeric' },
            slotDuration: '00:30:00',
            snapDuration: '00:30:00',
            slotLabelInterval: '01:00',
            eventMinHeight: 24,
            eventShortHeight: 24,
        });
        
        calendar.render();
    });

    function toggleStaff(staffId) {
        const btn = document.querySelector(`.staff-lane[data-staff="${staffId}"]`);
        if (!btn) return;
        
        if (hiddenStaff.has(staffId)) {
            hiddenStaff.delete(staffId);
            btn.classList.remove('inactive');
            btn.classList.add('active');
        } else {
            hiddenStaff.add(staffId);
            btn.classList.remove('active');
            btn.classList.add('inactive');
        }
        
        const visibleEvents = allEvents.filter(e => !hiddenStaff.has(e.staffId));
        calendar.removeAllEvents();
        calendar.addEventSource(visibleEvents);
    }

    function toggleDay(staffId, dayNum) {
        const cell = document.getElementById(`day-cell-${staffId}-${dayNum}`);
        const offInput = document.getElementById(`input-off-${staffId}-${dayNum}`);
        const timesDiv = document.getElementById(`times-${staffId}-${dayNum}`);
        const offLabel = document.getElementById(`off-label-${staffId}-${dayNum}`);
        const dayLabel = cell.querySelector('.day-label');
        
        const isOff = offInput.value === '1';
        
        if (isOff) {
            // Turn ON
            offInput.value = '0';
            cell.classList.remove('off');
            cell.classList.add('on');
            timesDiv.classList.remove('hidden');
            offLabel.classList.add('hidden');
            dayLabel.style.color = '';
        } else {
            // Turn OFF
            offInput.value = '1';
            cell.classList.remove('on');
            cell.classList.add('off');
            timesDiv.classList.add('hidden');
            offLabel.classList.remove('hidden');
        }
        
        showUnsaved();
    }

    function adjustTime(btn, minutes) {
        const input = btn.closest('.time-controls').previousElementSibling;
        if (!input || input.type !== 'time') return;
        
        const [h, m] = input.value.split(':').map(Number);
        const date = new Date();
        date.setHours(h, m + minutes, 0);
        
        const newH = String(date.getHours()).padStart(2, '0');
        const newM = String(date.getMinutes()).padStart(2, '0');
        input.value = `${newH}:${newM}`;
        
        showUnsaved();
    }

    function applyStaffPreset(staffId, preset) {
        const days = [1,2,3,4,5,6,0];
        const configs = {
            standard: { off: [], start: '09:00', end: '18:00' },
            weekdays: { off: [6,0], start: '09:00', end: '18:00' },
            alloff: { off: [1,2,3,4,5,6,0], start: '09:00', end: '18:00' }
        };
        
        const config = configs[preset];
        
        days.forEach(dayNum => {
            const cell = document.getElementById(`day-cell-${staffId}-${dayNum}`);
            if (!cell) return;
            
            const offInput = document.getElementById(`input-off-${staffId}-${dayNum}`);
            const timesDiv = document.getElementById(`times-${staffId}-${dayNum}`);
            const offLabel = document.getElementById(`off-label-${staffId}-${dayNum}`);
            const startInput = cell.querySelector('input[name*="[start_time]"]');
            const endInput = cell.querySelector('input[name*="[end_time]"]');
            
            const isOff = config.off.includes(dayNum);
            
            offInput.value = isOff ? '1' : '0';
            
            if (isOff) {
                cell.classList.remove('on');
                cell.classList.add('off');
                timesDiv?.classList.add('hidden');
                offLabel?.classList.remove('hidden');
            } else {
                cell.classList.remove('off');
                cell.classList.add('on');
                timesDiv?.classList.remove('hidden');
                offLabel?.classList.add('hidden');
                if (startInput) startInput.value = config.start;
                if (endInput) endInput.value = config.end;
            }
        });
        
        showUnsaved();
    }

    function applyGlobalPreset(preset) {
        document.querySelectorAll('[data-staff-id]').forEach(card => {
            const staffId = card.dataset.staffId;
            applyStaffPreset(staffId, preset);
        });
    }

    function showUnsaved() {
        const indicator = document.getElementById('unsaved-indicator');
        if (indicator) indicator.classList.remove('hidden');
    }

    function toggleExceptionTimes() {
        const type = document.getElementById('exceptionType').value;
        const times = document.getElementById('exceptionTimes');
        if (times) times.classList.toggle('hidden', type !== 'custom_hours');
    }
</script>
@endpush