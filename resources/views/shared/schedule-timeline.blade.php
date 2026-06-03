@extends(auth()->user()->roles->contains('name', 'admin') ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Staff Schedules')

@push('styles')
<style>
    :root {
        --bg-primary: #ffffff; --bg-secondary: #f8fafc; --bg-card: #ffffff; --bg-hover: #f1f5f9;
        --border-color: #e2e8f0; --text-primary: #0f172a; --text-secondary: #64748b; --text-muted: #94a3b8;
        --accent-teal: #0d9488; --accent-teal-light: #ccfbf1;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05); --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1); --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
    }
    .dark {
        --bg-primary: #0f172a; --bg-secondary: #1e293b; --bg-card: #1e293b; --bg-hover: #334155;
        --border-color: #334155; --text-primary: #f1f5f9; --text-secondary: #cbd5e1; --text-muted: #64748b;
        --accent-teal: #2dd4bf; --accent-teal-light: #134e4a;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.3); --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.4); --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.5);
    }

    .timeline-wrap {
        background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px;
        overflow: hidden; box-shadow: var(--shadow-sm);
    }
    .timeline-grid {
        display: grid; grid-template-columns: 220px repeat(7, 1fr);
    }
    .timeline-header-cell {
        padding: 14px 8px; text-align: center; font-weight: 700; font-size: 13px;
        color: var(--text-secondary); background: var(--bg-secondary);
        border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color);
    }
    .timeline-header-cell:last-child { border-right: none; }
    .timeline-header-cell.today { background: var(--accent-teal-light); color: var(--accent-teal); }
    .timeline-header-cell .day-num { font-size: 18px; font-weight: 800; color: var(--text-primary); display: block; margin-top: 2px; }
    .timeline-header-cell.today .day-num { color: var(--accent-teal); }

    .timeline-staff {
        padding: 16px; display: flex; align-items: center; gap: 12px;
        border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); background: var(--bg-card);
    }
    .timeline-staff-avatar {
        width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 14px; color: white; flex-shrink: 0;
    }
    .timeline-staff-name { font-weight: 700; font-size: 14px; color: var(--text-primary); }
    .timeline-staff-role { font-size: 12px; color: var(--text-muted); }

    .timeline-cell {
        padding: 10px; min-height: 110px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color);
        position: relative; cursor: pointer; transition: all 0.15s;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
    }
    .timeline-cell:last-child { border-right: none; }
    .timeline-cell:hover { background: var(--bg-hover); }

    .cell-actions {
        position: absolute; top: 4px; right: 4px; display: none; gap: 2px;
    }
    .timeline-cell:hover .cell-actions { display: flex; }
    .cell-btn {
        width: 24px; height: 24px; border-radius: 6px; border: none;
        display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px; transition: all 0.15s;
    }
    .cell-btn:hover { transform: scale(1.1); }
    .cell-btn-add { background: var(--accent-teal); color: white; }
    .cell-btn-block { background: #ef4444; color: white; }
    .cell-btn-edit { background: #3b82f6; color: white; }
    .cell-btn-delete { background: #6b7280; color: white; }

    .cell-working {
        background: linear-gradient(135deg, var(--staff-color, #0d9488), var(--staff-color-dark, #0f766e));
        color: white; margin: 6px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .cell-working:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
    .cell-time { font-size: 13px; font-weight: 700; text-align: center; line-height: 1.3; }
    .cell-time-range { font-size: 11px; opacity: 0.9; font-weight: 500; }

    .cell-off {
        background: repeating-linear-gradient(45deg, var(--bg-secondary), var(--bg-secondary) 8px, var(--border-color) 8px, var(--border-color) 16px);
        opacity: 0.5;
    }
    .cell-off-text { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }

    .cell-exception {
        background: #fee2e2; margin: 6px; border-radius: 10px; border: 2px solid #ef4444;
    }
    .dark .cell-exception { background: rgba(239, 68, 68, 0.12); border-color: #ef4444; }
    .cell-exception-text { font-size: 12px; font-weight: 700; color: #dc2626; text-align: center; }
    .dark .cell-exception-text { color: #f87171; }
    .cell-exception-reason { font-size: 10px; color: #ef4444; text-align: center; margin-top: 2px; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .cell-custom {
        background: linear-gradient(135deg, #fef3c7, #fde68a); margin: 6px; border-radius: 10px; border: 2px solid #f59e0b;
    }
    .dark .cell-custom { background: rgba(245, 158, 11, 0.15); border-color: #f59e0b; }
    .cell-custom-text { font-size: 12px; font-weight: 700; color: #92400e; text-align: center; }

    .staff-selector { display: flex; gap: 8px; flex-wrap: wrap; }
    .staff-chip {
        display: flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 999px;
        border: 2px solid var(--border-color); background: var(--bg-card); cursor: pointer; transition: all 0.2s; user-select: none;
    }
    .staff-chip:hover { border-color: var(--accent-teal); }
    .staff-chip.selected { border-color: var(--accent-teal); background: var(--accent-teal-light); box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
    .staff-chip-avatar { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; color: white; }

    .modal-backdrop {
        position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center;
        z-index: 50; padding: 20px;
    }
    .modal-backdrop.active { display: flex; }
    .modal-box {
        background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px;
        width: 100%; max-width: 480px; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-lg);
    }
    .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; }
    .modal-body { padding: 24px; }
    .modal-footer { padding: 16px 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 10px; }

    .form-label { display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 6px; }
    .form-input, .form-select {
        width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border-color);
        background: var(--bg-primary); color: var(--text-primary); font-size: 14px; transition: all 0.2s;
    }
    .form-input:focus, .form-select:focus { outline: none; border-color: var(--accent-teal); box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }

    .btn {
        padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; border: none;
        cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-primary { background: var(--accent-teal); color: white; }
    .btn-primary:hover { background: #0f766e; }
    .btn-secondary { background: var(--bg-secondary); color: var(--text-secondary); border: 1px solid var(--border-color); }
    .btn-secondary:hover { background: var(--bg-hover); }
    .btn-danger { background: #ef4444; color: white; }
    .btn-danger:hover { background: #dc2626; }

    @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
    .animate-pulse-dot { animation: pulse-dot 1.5s ease-in-out infinite; }

    @media (max-width: 1024px) {
        .timeline-grid { grid-template-columns: 160px repeat(7, 1fr); }
        .timeline-staff { padding: 10px; }
        .timeline-cell { min-height: 80px; padding: 6px; }
        .cell-time { font-size: 11px; }
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-12">

    {{-- HEADER --}}
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight" style="color: var(--text-primary);">Staff Schedules</h1>
            <p class="mt-2 text-base" style="color: var(--text-secondary);">
                @if($isAdmin) Manage weekly shifts, templates & blocks @else View and manage staff availability @endif
            </p>
        </div>
        <div class="flex items-center gap-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-1.5 shadow-sm">
            <a href="?week_start={{ \Carbon\Carbon::parse($weekStart)->subWeek()->toDateString() }}" class="btn btn-secondary !p-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <span class="px-4 font-bold text-sm" style="color: var(--text-primary);">{{ $weekLabel }}</span>
            <a href="?week_start={{ \Carbon\Carbon::parse($weekStart)->addWeek()->toDateString() }}" class="btn btn-secondary !p-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <a href="?week_start={{ now()->startOfWeek()->toDateString() }}" class="btn btn-secondary text-xs ml-1">Today</a>
        </div>
    </div>

    @if($canEdit)
    {{-- TEMPLATE BAR --}}
    <div class="p-5" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px;">
        <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <div>
                    <label class="form-label !mb-1">Shift Template</label>
                    <select id="templateSelect" class="form-select !w-56">
                        <option value="">Select a template...</option>
                        @foreach($templates as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 mt-5">
                    <button type="button" onclick="applyTemplateToAll()" class="btn btn-primary text-xs">Apply to All</button>
                    <button type="button" onclick="applyTemplateToSelected()" class="btn btn-secondary text-xs">Apply to Selected</button>
                </div>
            </div>
            @if($isAdmin)
            <a href="{{ route('admin.shift-templates.index') }}" class="btn btn-secondary text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Manage Templates
            </a>
            @endif
        </div>
        <div class="mt-4">
            <label class="form-label !mb-2">Select Staff to Apply</label>
            <div class="staff-selector" id="staffSelector">
                @foreach($staff as $idx => $s)
                    @php $c = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4', '#84cc16', '#f97316'][$idx % 8]; @endphp
                    <div class="staff-chip" data-id="{{ $s->id }}" onclick="toggleSelectStaff(this)">
                        <div class="staff-chip-avatar" style="background-color: {{ $c }};">{{ substr($s->first_name, 0, 1) }}</div>
                        <span style="font-size: 13px; font-weight: 600; color: var(--text-primary);">{{ $s->first_name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- TIMELINE GRID --}}
    <div class="timeline-wrap">
        <div class="timeline-grid">
            <div class="timeline-header-cell" style="border-right: 1px solid var(--border-color);">Staff</div>
            @foreach($days as $day)
                <div class="timeline-header-cell {{ $day['is_today'] ? 'today' : '' }}">
                    {{ $day['label'] }}
                    <span class="day-num">{{ $day['day'] }}</span>
                </div>
            @endforeach

            @foreach($timeline as $row)
                @php
                    $s = $row['user']; $staffIdx = $loop->index;
                    $staffColor = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4', '#84cc16', '#f97316'][$staffIdx % 8];
                    $staffColorDark = ['#047857', '#1d4ed8', '#6d28d9', '#b45309', '#be185d', '#0891b2', '#65a30d', '#c2410c'][$staffIdx % 8];
                @endphp
                <div class="timeline-staff">
                    <div class="timeline-staff-avatar" style="background-color: {{ $staffColor }};">
                        {{ substr($s->first_name, 0, 1) }}{{ substr($s->last_name, 0, 1) }}
                    </div>
                    <div>
                        <div class="timeline-staff-name">{{ $s->first_name }} {{ $s->last_name }}</div>
                        <div class="timeline-staff-role">{{ $s->workSchedules->where('is_day_off', false)->count() }} working days</div>
                    </div>
                </div>

                @foreach($row['days'] as $cell)
                    @php $cellId = "cell-{$s->id}-{$cell['dow']}"; @endphp
                    <div class="timeline-cell
                        {{ $cell['type'] === 'work' ? 'cell-working' : '' }}
                        {{ $cell['type'] === 'off' ? 'cell-off' : '' }}
                        {{ $cell['type'] === 'exception' && ($cell['exception_type'] ?? '') !== 'custom' ? 'cell-exception' : '' }}
                        {{ $cell['type'] === 'exception' && ($cell['exception_type'] ?? '') === 'custom' ? 'cell-custom' : '' }}"
                        style="{{ $cell['type'] === 'work' ? '--staff-color: ' . $staffColor . '; --staff-color-dark: ' . $staffColorDark . ';' : '' }}"
                        id="{{ $cellId }}"
                        data-staff-id="{{ $s->id }}"
                        data-date="{{ $cell['date'] }}"
                        data-dow="{{ $cell['dow'] }}"
                        data-type="{{ $cell['type'] }}"
                        data-start="{{ $cell['start_time'] }}"
                        data-end="{{ $cell['end_time'] }}"
                        data-exception-id="{{ $cell['exception']['id'] ?? '' }}"
                        data-exception-type="{{ $cell['exception_type'] ?? '' }}"
                        data-reason="{{ $cell['exception']['reason'] ?? '' }}"
                    >
                        @if($cell['type'] === 'work')
                            <div class="cell-time">{{ $cell['start_time'] }}</div>
                            <div class="cell-time-range">to</div>
                            <div class="cell-time">{{ $cell['end_time'] }}</div>
                            @if($canEdit)
                            <div class="cell-actions">
                                <button type="button" class="cell-btn cell-btn-edit" onclick="event.stopPropagation(); openEditModal({{ $s->id }}, '{{ $cell['date'] }}', '{{ $cell['start_time'] }}', '{{ $cell['end_time'] }}')" title="Edit">&#9999;</button>
                                <button type="button" class="cell-btn cell-btn-block" onclick="event.stopPropagation(); openBlockModal({{ $s->id }}, '{{ $cell['date'] }}')" title="Block">&#128683;</button>
                            </div>
                            @endif
                        @elseif($cell['type'] === 'exception')
                            @if(($cell['exception_type'] ?? '') === 'custom')
                                <div class="cell-custom-text">{{ $cell['start_time'] }} &ndash; {{ $cell['end_time'] }}</div>
                                <div class="cell-exception-reason">{{ $cell['exception']['reason'] ?? 'Custom Hours' }}</div>
                            @else
                                <div class="cell-exception-text">{{ ucfirst(str_replace('_', ' ', $cell['exception_type'] ?? 'Blocked')) }}</div>
                                @if($cell['exception']['reason'] ?? false)
                                    <div class="cell-exception-reason">{{ $cell['exception']['reason'] }}</div>
                                @endif
                            @endif
                            @if($canEdit)
                            <div class="cell-actions">
                                <button type="button" class="cell-btn cell-btn-delete" onclick="event.stopPropagation(); removeException({{ $cell['exception']['id'] ?? 0 }})" title="Remove Block">&#128465;</button>
                            </div>
                            @endif
                        @else
                            <div class="cell-off-text">OFF</div>
                            @if($canEdit)
                            <div class="cell-actions">
                                <button type="button" class="cell-btn cell-btn-add" onclick="event.stopPropagation(); openAddModal({{ $s->id }}, '{{ $cell['date'] }}', {{ $cell['dow'] }})" title="Add Shift">+</button>
                                <button type="button" class="cell-btn cell-btn-block" onclick="event.stopPropagation(); openBlockModal({{ $s->id }}, '{{ $cell['date'] }}')" title="Block">&#128683;</button>
                            </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- LEGEND --}}
    <div class="flex gap-6 text-xs justify-center flex-wrap" style="color: var(--text-muted);">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full" style="background: #10b981;"></span> Working</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-500"></span> Blocked (Leave/Holiday)</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-yellow-500"></span> Custom Hours</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background: repeating-linear-gradient(45deg, #e2e8f0, #e2e8f0 4px, #cbd5e1 4px, #cbd5e1 8px);"></span> Weekly Off</span>
    </div>

    @if($isAdmin)
    <div class="p-6" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px;">
        <h2 class="text-lg font-bold mb-4 flex items-center gap-2" style="color: var(--text-primary);">
            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Receptionist Permissions
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse($receptionists as $rec)
            <div class="flex items-center justify-between p-4 rounded-xl border" style="background: var(--bg-secondary); border-color: var(--border-color);">
                <div>
                    <div class="font-semibold text-sm" style="color: var(--text-primary);">{{ $rec->first_name }} {{ $rec->last_name }}</div>
                    <div class="text-xs mt-0.5" style="color: var(--text-muted);">{{ $rec->can_manage_schedules ? 'Can edit schedules' : 'View only' }}</div>
                </div>
                <form action="{{ route('admin.receptionist.toggle', $rec) }}" method="POST" class="inline">
                    @csrf @method('PUT')
                    <button type="submit" class="text-xs px-4 py-2 rounded-lg transition font-semibold {{ $rec->can_manage_schedules ? 'bg-orange-100 text-orange-700 hover:bg-orange-200 dark:bg-orange-900/30 dark:text-orange-300' : 'bg-teal-100 text-teal-700 hover:bg-teal-200 dark:bg-teal-900/30 dark:text-teal-300' }}">
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

    @if(!$isAdmin && !$canEdit)
    <div class="p-4 rounded-xl border flex items-center gap-3" style="background: rgba(234, 179, 8, 0.08); border-color: rgba(234, 179, 8, 0.3);">
        <svg class="w-6 h-6 text-yellow-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <p class="text-yellow-700 dark:text-yellow-300 text-sm font-medium">View only mode. Contact admin to modify schedules.</p>
    </div>
    @endif

</div>

{{-- MODAL: Add Shift --}}
<div class="modal-backdrop" id="addModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="font-bold text-lg" style="color: var(--text-primary);">Add Shift</h3>
            <button type="button" onclick="closeModal('addModal')" class="text-2xl" style="color: var(--text-muted);">x</button>
        </div>
        <form action="{{ $isAdmin ? route('admin.staff.schedule.bulk-update') : route('receptionist.schedules.bulk-update') }}" method="POST">
            @csrf
            <input type="hidden" name="schedules[0][user_id]" id="addUserId">
            <input type="hidden" name="schedules[0][day_of_week]" id="addDow">
            <input type="hidden" name="schedules[0][is_day_off]" value="0">
            <div class="modal-body space-y-4">
                <div>
                    <label class="form-label">Use Template</label>
                    <select id="addTemplatePicker" class="form-select" onchange="fillAddFromTemplate(this)">
                        <option value="">Custom time...</option>
                        @foreach($templates as $t)
                            <option value="{{ json_encode($t->pattern) }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Start Time</label>
                        <input type="time" name="schedules[0][start_time]" id="addStart" value="09:00" step="1800" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">End Time</label>
                        <input type="time" name="schedules[0][end_time]" id="addEnd" value="18:00" step="1800" class="form-input">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Shift</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: Edit Shift --}}
<div class="modal-backdrop" id="editModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="font-bold text-lg" style="color: var(--text-primary);">Edit Shift</h3>
            <button type="button" onclick="closeModal('editModal')" class="text-2xl" style="color: var(--text-muted);">x</button>
        </div>
        <form action="{{ $isAdmin ? route('admin.staff.schedule.bulk-update') : route('receptionist.schedules.bulk-update') }}" method="POST">
            @csrf
            <input type="hidden" name="schedules[0][user_id]" id="editUserId">
            <input type="hidden" name="schedules[0][day_of_week]" id="editDow">
            <input type="hidden" name="schedules[0][is_day_off]" value="0">
            <div class="modal-body space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Start Time</label>
                        <input type="time" name="schedules[0][start_time]" id="editStart" step="1800" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">End Time</label>
                        <input type="time" name="schedules[0][end_time]" id="editEnd" step="1800" class="form-input">
                    </div>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="schedules[0][is_day_off]" value="1" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                    <span class="text-sm font-medium" style="color: var(--text-secondary);">Mark as day off instead</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: Block / Leave --}}
<div class="modal-backdrop" id="blockModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="font-bold text-lg" style="color: var(--text-primary);">Block Date</h3>
            <button type="button" onclick="closeModal('blockModal')" class="text-2xl" style="color: var(--text-muted);">x</button>
        </div>
        <form id="blockForm" action="{{ $isAdmin ? route('admin.schedules.block') : route('receptionist.schedules.block') }}" method="POST">
            @csrf
            <input type="hidden" name="user_id" id="blockUserId">
            <div class="modal-body space-y-4">
                <div>
                    <label class="form-label">Block Type</label>
                    <div class="flex gap-3">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="block_type" value="single" checked class="peer sr-only" onchange="toggleBlockRange()">
                            <div class="text-center p-3 rounded-lg border border-slate-200 dark:border-slate-700 peer-checked:border-teal-500 peer-checked:bg-teal-50 dark:peer-checked:bg-teal-900/20 text-sm font-medium transition" style="color: var(--text-secondary);">Single Day</div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="block_type" value="range" class="peer sr-only" onchange="toggleBlockRange()">
                            <div class="text-center p-3 rounded-lg border border-slate-200 dark:border-slate-700 peer-checked:border-teal-500 peer-checked:bg-teal-50 dark:peer-checked:bg-teal-900/20 text-sm font-medium transition" style="color: var(--text-secondary);">Date Range</div>
                        </label>
                    </div>
                </div>
                <div id="singleDateWrap">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" id="blockDate" required class="form-input">
                </div>
                <div id="rangeDateWrap" class="hidden grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">From</label>
                        <input type="date" name="date" id="blockDateStart" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">To</label>
                        <input type="date" name="end_date" id="blockDateEnd" class="form-input">
                    </div>
                </div>
                <div>
                    <label class="form-label">Reason</label>
                    <select name="exception_type" class="form-select" onchange="toggleBlockCustomHours()">
                        <option value="sick_leave">Sick Leave</option>
                        <option value="urgent_leave">Urgent Leave</option>
                        <option value="holiday">Holiday</option>
                        <option value="day_off">Day Off</option>
                        <option value="custom_hours">Custom Hours</option>
                    </select>
                </div>
                <div id="blockCustomHours" class="hidden grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" value="09:00" step="1800" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" value="18:00" step="1800" class="form-input">
                    </div>
                </div>
                <div>
                    <label class="form-label">Note (Optional)</label>
                    <input type="text" name="reason" placeholder="e.g., Family emergency" class="form-input">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('blockModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-danger">Block Date(s)</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let selectedStaff = new Set();

    function toggleSelectStaff(el) {
        const id = el.dataset.id;
        if (selectedStaff.has(id)) { selectedStaff.delete(id); el.classList.remove('selected'); }
        else { selectedStaff.add(id); el.classList.add('selected'); }
    }

    function applyTemplateToAll() {
        const templateId = document.getElementById('templateSelect').value;
        if (!templateId) { alert('Please select a template first.'); return; }
        let userIds = [];
        document.querySelectorAll('.staff-chip').forEach(function(c) { userIds.push(c.dataset.id); });
        applyTemplateToUsers(templateId, userIds);
    }

    function applyTemplateToSelected() {
        const templateId = document.getElementById('templateSelect').value;
        if (!templateId) { alert('Please select a template first.'); return; }
        if (selectedStaff.size === 0) { alert('Please select at least one staff member.'); return; }
        applyTemplateToUsers(templateId, Array.from(selectedStaff));
    }

    function applyTemplateToUsers(templateId, userIds) {
        if (!confirm('Apply template to ' + userIds.length + ' staff member(s)?')) return;
        let done = 0;
        const baseUrl = '{{ $isAdmin ? url("/admin/schedules/template") : url("/receptionist/schedules/template") }}';
        userIds.forEach(function(uid, idx) {
            setTimeout(function() {
                fetch(baseUrl + '/' + uid, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ template_id: templateId, week_start: '{{ $weekStart }}' })
                }).then(function() {
                    done++;
                    if (done === userIds.length) window.location.reload();
                }).catch(function() {
                    done++;
                    if (done === userIds.length) window.location.reload();
                });
            }, idx * 200);
        });
    }

    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }

    document.querySelectorAll('.modal-backdrop').forEach(function(m) {
        m.addEventListener('click', function(e) { if (e.target === m) closeModal(m.id); });
    });

    function openAddModal(userId, date, dow) {
        document.getElementById('addUserId').value = userId;
        document.getElementById('addDow').value = dow;
        openModal('addModal');
    }

    function fillAddFromTemplate(select) {
        if (!select.value) return;
        try {
            const pattern = JSON.parse(select.value);
            const dow = document.getElementById('addDow').value;
            const day = pattern[dow];
            if (day && !day.is_day_off) {
                document.getElementById('addStart').value = day.start_time;
                document.getElementById('addEnd').value = day.end_time;
            }
        } catch(e) { console.error('Invalid template pattern', e); }
    }

    function openEditModal(userId, date, start, end) {
        document.getElementById('editUserId').value = userId;
        var cell = document.querySelector('[data-staff-id="' + userId + '"][data-date="' + date + '"]');
        if (cell) document.getElementById('editDow').value = cell.dataset.dow;
        document.getElementById('editStart').value = start || '09:00';
        document.getElementById('editEnd').value = end || '18:00';
        openModal('editModal');
    }

    function openBlockModal(userId, date) {
        document.getElementById('blockUserId').value = userId;
        document.getElementById('blockDate').value = date;
        document.getElementById('blockDateStart').value = date;
        document.getElementById('blockDateEnd').value = date;
        openModal('blockModal');
    }

    function toggleBlockRange() {
        var isRange = document.querySelector('input[name="block_type"]:checked').value === 'range';
        document.getElementById('singleDateWrap').classList.toggle('hidden', isRange);
        document.getElementById('rangeDateWrap').classList.toggle('hidden', !isRange);
        var startInput = document.getElementById('blockDateStart');
        var singleInput = document.getElementById('blockDate');
        if (isRange) {
            singleInput.removeAttribute('name');
            singleInput.removeAttribute('required');
            startInput.setAttribute('name', 'date');
            startInput.setAttribute('required', 'required');
        } else {
            singleInput.setAttribute('name', 'date');
            singleInput.setAttribute('required', 'required');
            startInput.removeAttribute('name');
            startInput.removeAttribute('required');
        }
    }

    function toggleBlockCustomHours() {
        var type = document.querySelector('#blockModal select[name="exception_type"]').value;
        document.getElementById('blockCustomHours').classList.toggle('hidden', type !== 'custom_hours');
    }

    function removeException(id) {
        if (!confirm('Remove this block?')) return;
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ url("/admin/schedule-exception") }}/' + id;
        form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
        document.body.appendChild(form);
        form.submit();
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.active').forEach(function(m) { closeModal(m.id); });
        }
    });
</script>
@endpush