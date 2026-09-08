@php
$colors = ['#0d9488','#0ea5e9','#8b5cf6','#f59e0b','#ec4899','#06b6d4','#84cc16','#f97316'];
$bulkRoute = $isAdmin ? route('admin.schedules.bulk-update') : route('receptionist.schedules.bulk-update');
$blockRoute = $isAdmin ? route('admin.schedules.block') : route('receptionist.schedules.block');
$exceptionRoute = $isAdmin ? url('/admin/schedule-exception') : url('/receptionist/schedule-exception');
$templateApplyBulkRoute = $isAdmin ? route('admin.schedules.template.bulk') : route('receptionist.schedules.template.bulk');
$exceptionStoreRoute = $isAdmin ? route('admin.schedule-exception.store') : route('receptionist.schedule-exception.store');
$exceptionBulkRoute = $isAdmin ? route('admin.schedule-exception.bulk-store') : route('receptionist.schedule-exception.bulk-store');

$fmt = fn($t) => $t ? \Carbon\Carbon::createFromFormat('H:i', substr($t, 0, 5))->format('g:i A') : '—';

$todayStr = now()->toDateString();
@endphp

{{-- FIX: Use $isAdmin variable instead of undefined auth method --}}
@extends($isAdmin ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Staff Schedules')

@push('styles')
<style>
[x-cloak] { display: none !important; }

@keyframes selectPulse {
    0% { box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.4); }
    70% { box-shadow: 0 0 0 6px rgba(20, 184, 166, 0); }
    100% { box-shadow: 0 0 0 0 rgba(20, 184, 166, 0); }
}
.cell-selected { animation: selectPulse 1s ease-out; }

.cell-past {
    background-color: #f3f4f6 !important;
    opacity: 0.6;
    cursor: not-allowed !important;
}
.dark .cell-past {
    background-color: #1e293b !important;
    opacity: 0.5;
}
.cell-past .schedule-content { filter: grayscale(0.8); }

.staff-sidebar::-webkit-scrollbar { width: 4px; }
.staff-sidebar::-webkit-scrollbar-track { background: transparent; }
.staff-sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.dark .staff-sidebar::-webkit-scrollbar-thumb { background: #475569; }

.schedule-cell {
    transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
}
.schedule-cell:hover:not(.cell-past) { transform: translateY(-1px); }

/* ─── Centered Modal Overlay ─── */
.modal-backdrop {
    position: fixed; inset: 0; z-index: 50;
    background: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    padding: 16px;
    opacity: 0; pointer-events: none;
    transition: opacity 0.2s ease;
}
.modal-backdrop.open { opacity: 1; pointer-events: auto; }
.modal-panel {
    background: white; border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    width: 100%; max-width: 520px;
    max-height: calc(100dvh - 32px);
    overflow: hidden;
    display: flex; flex-direction: column;
    opacity: 0;
    margin-top: 20px;
    transition: opacity 0.25s cubic-bezier(0.32,0.72,0,1), margin-top 0.25s cubic-bezier(0.32,0.72,0,1);
}
.modal-backdrop.open .modal-panel {
    opacity: 1;
    margin-top: 0;
}
.dark .modal-panel { background: #1e293b; }

/* ─── Redesigned Time Picker ─── */
.time-picker-wrapper { position: relative; }
.time-picker-trigger {
    width: 100%; padding: 0.5rem 0.75rem;
    background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem;
    font-size: 0.875rem; color: #374151; cursor: pointer;
    display: flex; align-items: center; justify-content: space-between;
    transition: all 0.15s ease;
}
.dark .time-picker-trigger { background: #1e293b; border-color: #475569; color: #e2e8f0; }
.time-picker-trigger:hover { border-color: #9ca3af; }
.time-picker-trigger:focus, .time-picker-trigger.open { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.1); }
.dark .time-picker-trigger:focus, .dark .time-picker-trigger.open { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20,184,166,0.15); }

.time-picker-dropdown {
    position: fixed;
    max-height: 320px; overflow: hidden;
    background: white; border: 1px solid #e5e7eb;
    border-radius: 0.75rem; z-index: 9999;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
    min-width: 180px;
    display: flex; flex-direction: column;
}
.dark .time-picker-dropdown { background: #1e293b; border-color: #475569; }

.time-picker-search {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #f3f4f6;
    background: #fafafa;
    border-radius: 0.75rem 0.75rem 0 0;
}
.dark .time-picker-search { background: #0f172a; border-color: #334155; }
.time-picker-search input {
    width: 100%; padding: 0.375rem 0.5rem;
    font-size: 0.75rem; background: white; border: 1px solid #e5e7eb;
    border-radius: 0.375rem; color: #374151;
    outline: none;
}
.dark .time-picker-search input { background: #1e293b; border-color: #475569; color: #e2e8f0; }
.time-picker-search input:focus { border-color: #0d9488; box-shadow: 0 0 0 2px rgba(13,148,136,0.1); }

.time-picker-scroll {
    overflow-y: auto; padding: 4px;
    max-height: 260px;
}
.time-picker-scroll::-webkit-scrollbar { width: 4px; }
.time-picker-scroll::-webkit-scrollbar-track { background: transparent; }
.time-picker-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.dark .time-picker-scroll::-webkit-scrollbar-thumb { background: #475569; }

.time-picker-group-label {
    padding: 0.375rem 0.75rem;
    font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: #9ca3af;
    position: sticky; top: 0; background: inherit;
    z-index: 1;
}
.dark .time-picker-group-label { color: #64748b; }

.time-picker-option {
    padding: 0.5rem 0.75rem; cursor: pointer; font-size: 0.875rem;
    border-radius: 0.5rem; color: #374151;
    transition: all 0.1s ease;
    display: flex; align-items: center; justify-content: space-between;
}
.dark .time-picker-option { color: #e2e8f0; }
.time-picker-option:hover { background: #f3f4f6; }
.dark .time-picker-option:hover { background: #334155; }
.time-picker-option.selected {
    background: #ccfbf1; color: #0f766e; font-weight: 600;
}
.dark .time-picker-option.selected { background: #134e4a; color: #5eead4; }
.time-picker-option .check { width: 14px; height: 14px; opacity: 0; }
.time-picker-option.selected .check { opacity: 1; }
</style>
@endpush

@section('content')
<div
  x-data="schedApp()"
  x-init="init()"
  class="flex flex-col h-[calc(100dvh-72px-2rem)] md:h-[calc(100dvh-72px-4rem)] overflow-hidden"
>
  {{-- TOOLBAR --}}
  <header class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 shrink-0 z-30">
    <div class="flex items-center gap-2">
      <div class="inline-flex items-center bg-gray-100 dark:bg-slate-700 rounded-lg p-1 border border-gray-200 dark:border-slate-600">
        @if($view === 'week')
          <a href="?week_start={{ $prevWeek }}&view=week" class="p-1.5 rounded-md text-gray-500 hover:bg-white dark:hover:bg-slate-600 hover:text-gray-800 dark:hover:text-white transition-colors" title="Previous week">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
          </a>
          <a href="?week_start={{ now()->startOfWeek()->toDateString() }}&view=week" class="px-3 py-1 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-slate-600 rounded-md transition-colors">Today</a>
          <span class="px-3 text-sm font-bold text-gray-800 dark:text-white min-w-[140px] text-center">{{ $weekLabel }}</span>
          <a href="?week_start={{ $nextWeek }}&view=week" class="p-1.5 rounded-md text-gray-500 hover:bg-white dark:hover:bg-slate-600 hover:text-gray-800 dark:hover:text-white transition-colors" title="Next week">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
          </a>
        @else
          <a href="?date={{ $prevDate }}&view=day" class="p-1.5 rounded-md text-gray-500 hover:bg-white dark:hover:bg-slate-600 hover:text-gray-800 dark:hover:text-white transition-colors" title="Previous day">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
          </a>
          <a href="?date={{ now()->toDateString() }}&view=day" class="px-3 py-1 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-slate-600 rounded-md transition-colors">Today</a>
          <span class="px-3 text-sm font-bold text-gray-800 dark:text-white min-w-[180px] text-center">{{ $dateLabel }}</span>
          <a href="?date={{ $nextDate }}&view=day" class="p-1.5 rounded-md text-gray-500 hover:bg-white dark:hover:bg-slate-600 hover:text-gray-800 dark:hover:text-white transition-colors" title="Next day">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
          </a>
        @endif

        <div class="flex items-center gap-1 pl-2 ml-2 border-l border-gray-300 dark:border-slate-500">
          <a href="?week_start={{ $view === 'week' ? $weekStart : \Carbon\Carbon::parse($date)->startOfWeek()->toDateString() }}&view=week" class="px-3 py-1 text-xs font-semibold rounded-md {{ $view === 'week' ? 'bg-white dark:bg-slate-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">Week</a>
          <a href="?date={{ $view === 'week' ? $weekStart : $date }}&view=day" class="px-3 py-1 text-xs font-semibold rounded-md {{ $view === 'day' ? 'bg-white dark:bg-slate-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">Day</a>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-3">
      @if($canEdit)
      <div class="flex items-center gap-2">
        <select x-model="activeTemplate" class="py-1.5 px-3 text-xs font-medium bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
          <option value="">Select template…</option>
          @foreach($templates as $t)
            <option value="{{ $t->id }}">{{ $t->name }}</option>
          @endforeach
        </select>
        <button type="button" @click="applyTemplateToAll()" :disabled="!activeTemplate" class="px-3 py-1.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm shadow-brand-500/20">Apply to All</button>
        <button type="button" @click="applyTemplateToSelected()" :disabled="!activeTemplate || selectedStaffIds.length === 0" class="px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition-colors">Apply to Selected</button>
      </div>
      @endif

      @if(!$isAdmin && !$canEdit)
      <div class="flex items-center gap-2 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-amber-700 dark:text-amber-400 text-xs font-semibold">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span>View only mode</span>
      </div>
      @endif

      @if($isAdmin)
      <a href="{{ route('admin.shift-templates.index') }}" class="px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-600 rounded-lg transition-colors">Manage Templates</a>
      @endif
    </div>
  </header>

  {{-- WORKSPACE --}}
  <div class="flex flex-1 overflow-hidden">

    {{-- STAFF SIDEBAR --}}
    <aside class="w-64 shrink-0 bg-white dark:bg-slate-800 border-r border-gray-200 dark:border-slate-700 flex flex-col overflow-hidden z-20">
      <div class="p-4 border-b border-gray-100 dark:border-slate-700">
        <h2 class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">Staff Directory</h2>
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/></svg>
          <input type="text" x-model="staffFilter" placeholder="Search staff…" class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
        </div>
      </div>
      <div class="flex-1 overflow-y-auto p-2 space-y-1 staff-sidebar">
        @foreach($timeline as $row)
          @php
            $s = $row['user']; $si = $loop->index;
            $stats = $staffStats[$s->id] ?? ['days' => [], 'hours' => 0, 'count' => 0];
            $initials = substr($s->first_name,0,1).substr($s->last_name,0,1);
            $staffNameJson = json_encode($s->first_name.' '.$s->last_name, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
          @endphp
          <button
            type="button"
            @click="toggleStaffSelection({{ $s->id }})"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-all duration-200"
            :class="selectedStaffIds.includes({{ $s->id }}) ? 'bg-brand-50 dark:bg-brand-900/20 ring-1 ring-brand-200 dark:ring-brand-800' : 'hover:bg-gray-50 dark:hover:bg-slate-700/50'"
            x-show="staffMatchesFilter({{ $staffNameJson }})"
          >
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0 shadow-sm" style="background: {{ $colors[$si % 8] }};">{{ $initials }}</div>
            <div class="min-w-0 flex-1">
              <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $s->first_name }} {{ $s->last_name }}</div>
              <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                @if(count($stats['days']))
                  <span class="font-medium text-brand-600 dark:text-brand-400">{{ implode(', ', $stats['days']) }}</span>
                  <span class="mx-1">·</span>
                  <span>{{ $stats['hours'] }}h</span>
                @else
                  <span class="text-gray-400">No scheduled hours</span>
                @endif
              </div>
            </div>
            <div x-show="selectedStaffIds.includes({{ $s->id }})" class="w-4 h-4 rounded-full bg-brand-500 flex items-center justify-center" x-cloak>
              <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
          </button>
        @endforeach
      </div>
    </aside>

    {{-- SCHEDULE CANVAS --}}
    <main class="flex-1 overflow-auto bg-gray-50 dark:bg-slate-900 p-4 md:p-6 min-w-0">
      @if($view === 'week')
        {{-- WEEK GRID --}}
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full border-collapse" style="min-width: 1036px;">
              <thead>
                <tr class="bg-gray-50 dark:bg-slate-800/80">
                  <th class="w-14 p-3 border-b border-r border-gray-200 dark:border-slate-700 sticky left-0 bg-gray-50 dark:bg-slate-800 z-20"></th>
                  @foreach($days as $day)
                    <th class="w-[140px] p-3 text-center border-b border-r border-gray-100 dark:border-slate-700 last:border-r-0 {{ $day['is_today'] ? 'bg-brand-50/50 dark:bg-brand-900/10' : '' }}">
                      <div class="text-[10px] font-bold uppercase tracking-wider {{ $day['is_today'] ? 'text-brand-600 dark:text-brand-400' : 'text-gray-400' }}">{{ $day['label'] }}</div>
                      <div class="text-lg font-bold {{ $day['is_today'] ? 'text-brand-600 dark:text-brand-400' : 'text-gray-800 dark:text-gray-100' }}">{{ $day['day'] }}</div>
                    </th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                @foreach($timeline as $row)
                  @php
                    $s = $row['user']; $si = $loop->index;
                    $initials = substr($s->first_name,0,1).substr($s->last_name,0,1);
                    $staffNameJson = json_encode($s->first_name.' '.$s->last_name, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);
                  @endphp
                  <tr
                    class="border-b border-gray-100 dark:border-slate-700 last:border-b-0"
                    x-show="staffMatchesFilter({{ $staffNameJson }}) && (selectedStaffIds.length === 0 || selectedStaffIds.includes({{ $s->id }}))"
                  >
                    <td class="w-14 p-2 border-r border-gray-200 dark:border-slate-700 sticky left-0 bg-white dark:bg-slate-800 z-10 text-center align-middle">
                      <div class="w-7 h-7 rounded-full flex items-center justify-center text-[9px] font-bold text-white mx-auto shadow-sm" style="background: {{ $colors[$si % 8] }};" title="{{ $s->first_name }} {{ $s->last_name }}">{{ $initials }}</div>
                    </td>
                    @foreach($row['days'] as $cell)
                      @php
                        $isWork = $cell['type'] === 'work';
                        $isExc  = $cell['type'] === 'exception';
                        $isCustom = ($cell['exception_type'] ?? '') === 'custom';
                        $exId = $cell['exception']['id'] ?? null;
                        $cellKey = $s->id . '|' . $cell['date'];
                        $isPast = $cell['date'] < $todayStr;
                      @endphp
                      <td
                        class="schedule-cell w-[140px] p-2 border-r border-gray-100 dark:border-slate-700 last:border-r-0 align-middle transition-all {{ $isPast ? 'cell-past' : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700/30' }}"
                        :class="selectedCells['{{ $cellKey }}'] ? 'ring-2 ring-inset ring-brand-500 bg-brand-50/50 dark:bg-brand-900/10 cell-selected' : ''"
                        @if($canEdit && !$isPast)
                          @click="handleCellClick($event, {{ $s->id }}, '{{ $cell['date'] }}', {{ $cell['dow'] }}, '{{ $cell['start_time'] }}', '{{ $cell['end_time'] }}', '{{ $cell['type'] }}', '{{ $cell['exception_type'] ?? '' }}', {{ $exId ?? 'null' }} )"
                        @endif
                        data-key="{{ $cellKey }}"
                      >
                        <div class="schedule-content min-h-[64px] flex flex-col items-center justify-center gap-1">
                          @if($isWork)
                            <div class="text-center">
                              <div class="text-xs font-bold text-brand-700 dark:text-brand-300">{{ $fmt($cell['start_time']) }}</div>
                              <div class="text-xs font-bold text-brand-700 dark:text-brand-300">{{ $fmt($cell['end_time']) }}</div>
                              @php $hrs = $cell['start_time'] && $cell['end_time'] ? round(\Carbon\Carbon::parse($cell['start_time'])->diffInMinutes(\Carbon\Carbon::parse($cell['end_time']))/60, 1) : 0; @endphp
                              @if($hrs)
                                <div class="text-[10px] font-semibold text-brand-600/70 dark:text-brand-400/70 mt-0.5">{{ $hrs }}h</div>
                              @endif
                            </div>
                            @if(!empty($cell['attendance']))
                              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide
                                {{ $cell['attendance'] === 'present' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                                {{ $cell['attendance'] === 'late' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : '' }}
                                {{ $cell['attendance'] === 'absent' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}
                              ">{{ ucfirst($cell['attendance']) }}</span>
                            @endif
                          @elseif($isExc)
                            <div class="text-center w-full px-1.5 py-1.5 rounded-md border
                              {{ $isCustom ? 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800' : 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800' }}">
                              @if($isCustom)
                                <div class="text-[11px] font-bold text-amber-800 dark:text-amber-300">Custom</div>
                                <div class="text-[10px] font-semibold text-amber-700 dark:text-amber-400 opacity-80">{{ $fmt($cell['start_time']) }} – {{ $fmt($cell['end_time']) }}</div>
                              @else
                                <div class="text-[11px] font-bold text-red-800 dark:text-red-300 capitalize">{{ str_replace('_', ' ', $cell['exception_type'] ?? 'Blocked') }}</div>
                              @endif
                              @if(!empty($cell['exception']['reason']))
                                <div class="text-[9px] text-gray-500 dark:text-gray-400 truncate max-w-full mt-0.5" title="{{ $cell['exception']['reason'] }}">{{ $cell['exception']['reason'] }}</div>
                              @endif
                            </div>
                            @if(!empty($cell['attendance']))
                              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide
                                {{ $cell['attendance'] === 'present' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                                {{ $cell['attendance'] === 'late' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : '' }}
                                {{ $cell['attendance'] === 'absent' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}
                              ">{{ ucfirst($cell['attendance']) }}</span>
                            @endif
                          @else
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-300 dark:text-gray-600">Off</span>
                            @if(!empty($cell['attendance']))
                              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide
                                {{ $cell['attendance'] === 'present' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                                {{ $cell['attendance'] === 'late' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : '' }}
                                {{ $cell['attendance'] === 'absent' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}
                              ">{{ ucfirst($cell['attendance']) }}</span>
                            @endif
                          @endif
                        </div>
                      </td>
                    @endforeach
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        {{-- Legend --}}
        <div class="flex flex-wrap items-center justify-center gap-6 mt-4">
          <span class="flex items-center gap-1.5 text-xs font-medium text-gray-500"><span class="w-2.5 h-2.5 rounded-full bg-brand-500"></span> Working</span>
          <span class="flex items-center gap-1.5 text-xs font-medium text-gray-500"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> Blocked / Day Off</span>
          <span class="flex items-center gap-1.5 text-xs font-medium text-gray-500"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Custom Hours</span>
          <span class="flex items-center gap-1.5 text-xs font-medium text-gray-500"><span class="w-2.5 h-2.5 rounded-full bg-gray-300 dark:bg-gray-600"></span> Unscheduled</span>
          <span class="flex items-center gap-1.5 text-xs font-medium text-gray-500"><span class="w-2.5 h-2.5 rounded-full bg-gray-200 dark:bg-gray-700 border border-gray-400"></span> Past</span>
        </div>

      @else
        {{-- DAY VIEW --}}
        <div class="max-w-3xl mx-auto">
          <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">{{ $dateLabel }}</h2>
          </div>
          <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
            @foreach($timeline as $row)
              @php
                $s = $row['user']; $block = $row['block'];
                $initials = substr($s->first_name,0,1).substr($s->last_name,0,1);
                $isPast = $date < $todayStr;
                $attendanceStatus = $row['attendance']?->status ?? null;
              @endphp
              <div class="flex items-stretch border-b border-gray-100 dark:border-slate-700 last:border-b-0 {{ $isPast ? 'cell-past' : '' }}" x-show="staffMatchesFilter(@json($s->first_name.' '.$s->last_name)) && (selectedStaffIds.length === 0 || selectedStaffIds.includes({{ $s->id }}))">
                <div class="w-52 shrink-0 flex items-center gap-3 px-4 py-4 bg-gray-50 dark:bg-slate-800/50 border-r border-gray-200 dark:border-slate-700">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0 shadow-sm" style="background: {{ $colors[$loop->index % 8] }};">{{ $initials }}</div>
                  <div class="min-w-0">
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $s->first_name }} {{ $s->last_name }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['statusLabel'] }}</div>
                  </div>
                </div>
                <div class="flex-1 p-4 flex items-center">
                  @if($block && $block['type'] !== 'off')
                    <div class="px-4 py-2.5 rounded-lg border text-sm
                      {{ $block['type'] === 'custom' ? 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300' : 'bg-brand-50 dark:bg-brand-900/10 border-brand-200 dark:border-brand-800 text-brand-800 dark:text-brand-300' }}">
                      <div class="font-bold">{{ $block['label'] }}</div>
                      @if(!empty($block['reason']))
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $block['reason'] }}</div>
                      @endif
                      @if($attendanceStatus)
                        <span class="inline-flex items-center mt-1.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide
                          {{ $attendanceStatus === 'present' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                          {{ $attendanceStatus === 'late' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : '' }}
                          {{ $attendanceStatus === 'absent' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}
                        ">{{ ucfirst($attendanceStatus) }}</span>
                      @endif
                    </div>
                  @else
                    <div class="w-full text-center text-xs font-bold uppercase tracking-wider text-gray-300 dark:text-gray-600 py-3">— Day Off —</div>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </main>
  </div>

  {{-- ═══════════════════════════════════════════════════════════════ --}}
  {{-- CENTERED MODAL OVERLAY                                        --}}
  {{-- ═══════════════════════════════════════════════════════════════ --}}
  <div
    class="modal-backdrop"
    :class="popover.open ? 'open' : ''"
    @click="closePopover()"
    x-show="popover.open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
  >
    <div class="modal-panel" @click.stop>

      {{-- Single Edit --}}
      <div x-show="popover.mode === 'edit'" x-cloak class="flex flex-col h-full">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-700 shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold text-white shrink-0" :style="'background: ' + staffColor(popover.data.userId)" x-text="staffInitials(popover.data.userId)"></div>
            <div>
              <h3 class="text-sm font-bold text-gray-900 dark:text-white" x-text="staffNameById(popover.data.userId)"></h3>
              <p class="text-xs text-gray-400 dark:text-gray-500" x-text="popover.data.date ? new Date(popover.data.date + 'T00:00:00').toLocaleDateString('en-US', {weekday:'long', month:'long', day:'numeric'}) : ''"></p>
            </div>
          </div>
          <button @click="closePopover()" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

          {{-- Status Segmented Control --}}
          <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-2">Status</label>
            <div class="grid grid-cols-3 gap-2 p-1 bg-gray-100 dark:bg-slate-700 rounded-xl">
              <button @click="popover.data.status = 'work'" :class="popover.data.status === 'work' ? 'bg-white dark:bg-slate-600 text-brand-600 dark:text-brand-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'" class="py-2.5 text-xs font-semibold rounded-lg transition-all flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Working
              </button>
              <button @click="popover.data.status = 'off'" :class="popover.data.status === 'off' ? 'bg-white dark:bg-slate-600 text-gray-800 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'" class="py-2.5 text-xs font-semibold rounded-lg transition-all flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                Day off
              </button>
              <button @click="popover.data.status = 'exception'" :class="popover.data.status === 'exception' ? 'bg-white dark:bg-slate-600 text-amber-600 dark:text-amber-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'" class="py-2.5 text-xs font-semibold rounded-lg transition-all flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Exception
              </button>
            </div>
          </div>

          {{-- Working Hours --}}
          <div x-show="popover.data.status === 'work'" x-transition class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Start time</label>
              <div x-data="timePicker(popover.data, 'start')" class="time-picker-wrapper" @click.away="open = false">
                <button type="button" @click="toggle($el)" :class="open ? 'open' : ''" class="time-picker-trigger">
                  <span x-text="formatTime(model.start, '9:00 AM')"></span>
                  <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="time-picker-dropdown" :style="`top:${dropdownTop}px;left:${dropdownLeft}px;width:${dropdownWidth}px`" @click.stop>
                  <div class="time-picker-search"><input type="text" x-model="search" placeholder="Find time…" @keydown.stop></div>
                  <div class="time-picker-scroll">
                    <template x-if="groupedOptions.am.length">
                      <div>
                        <div class="time-picker-group-label">Morning</div>
                        <template x-for="opt in groupedOptions.am" :key="opt.value">
                          <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.start ? 'selected' : ''">
                            <span x-text="opt.label"></span>
                            <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                          </div>
                        </template>
                      </div>
                    </template>
                    <template x-if="groupedOptions.pm.length">
                      <div>
                        <div class="time-picker-group-label">Afternoon / Evening</div>
                        <template x-for="opt in groupedOptions.pm" :key="opt.value">
                          <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.start ? 'selected' : ''">
                            <span x-text="opt.label"></span>
                            <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                          </div>
                        </template>
                      </div>
                    </template>
                    <div x-show="!filteredOptions.length" class="px-3 py-4 text-xs text-gray-400 text-center">No times found</div>
                  </div>
                </div>
              </div>
            </div>
            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">End time</label>
              <div x-data="timePicker(popover.data, 'end')" class="time-picker-wrapper" @click.away="open = false">
                <button type="button" @click="toggle($el)" :class="open ? 'open' : ''" class="time-picker-trigger">
                  <span x-text="formatTime(model.end, '6:00 PM')"></span>
                  <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="time-picker-dropdown" :style="`top:${dropdownTop}px;left:${dropdownLeft}px;width:${dropdownWidth}px`" @click.stop>
                  <div class="time-picker-search"><input type="text" x-model="search" placeholder="Find time…" @keydown.stop></div>
                  <div class="time-picker-scroll">
                    <template x-if="groupedOptions.am.length">
                      <div>
                        <div class="time-picker-group-label">Morning</div>
                        <template x-for="opt in groupedOptions.am" :key="opt.value">
                          <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.end ? 'selected' : ''">
                            <span x-text="opt.label"></span>
                            <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                          </div>
                        </template>
                      </div>
                    </template>
                    <template x-if="groupedOptions.pm.length">
                      <div>
                        <div class="time-picker-group-label">Afternoon / Evening</div>
                        <template x-for="opt in groupedOptions.pm" :key="opt.value">
                          <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.end ? 'selected' : ''">
                            <span x-text="opt.label"></span>
                            <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                          </div>
                        </template>
                      </div>
                    </template>
                    <div x-show="!filteredOptions.length" class="px-3 py-4 text-xs text-gray-400 text-center">No times found</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- Exception Fields --}}
          <div x-show="popover.data.status === 'exception'" x-transition class="space-y-3">
            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Exception type</label>
              <select x-model="popover.data.exceptionType" class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
                <option value="day_off">Day off</option>
                <option value="holiday">Holiday</option>
                <option value="sick_leave">Sick leave</option>
                <option value="urgent_leave">Urgent leave</option>
                <option value="custom_hours">Custom hours</option>
              </select>
            </div>

            <div x-show="popover.data.exceptionType === 'custom_hours'" class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Start time</label>
                <div x-data="timePicker(popover.data, 'start')" class="time-picker-wrapper" @click.away="open = false">
                  <button type="button" @click="toggle($el)" :class="open ? 'open' : ''" class="time-picker-trigger">
                    <span x-text="formatTime(model.start, '9:00 AM')"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                  </button>
                  <div x-show="open" x-cloak class="time-picker-dropdown" :style="`top:${dropdownTop}px;left:${dropdownLeft}px;width:${dropdownWidth}px`" @click.stop>
                    <div class="time-picker-search"><input type="text" x-model="search" placeholder="Find time…" @keydown.stop></div>
                    <div class="time-picker-scroll">
                      <template x-if="groupedOptions.am.length">
                        <div>
                          <div class="time-picker-group-label">Morning</div>
                          <template x-for="opt in groupedOptions.am" :key="opt.value">
                            <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.start ? 'selected' : ''">
                              <span x-text="opt.label"></span>
                              <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                          </template>
                        </div>
                      </template>
                      <template x-if="groupedOptions.pm.length">
                        <div>
                          <div class="time-picker-group-label">Afternoon / Evening</div>
                          <template x-for="opt in groupedOptions.pm" :key="opt.value">
                            <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.start ? 'selected' : ''">
                              <span x-text="opt.label"></span>
                              <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                          </template>
                        </div>
                      </template>
                      <div x-show="!filteredOptions.length" class="px-3 py-4 text-xs text-gray-400 text-center">No times found</div>
                    </div>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">End time</label>
                <div x-data="timePicker(popover.data, 'end')" class="time-picker-wrapper" @click.away="open = false">
                  <button type="button" @click="toggle($el)" :class="open ? 'open' : ''" class="time-picker-trigger">
                    <span x-text="formatTime(model.end, '6:00 PM')"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                  </button>
                  <div x-show="open" x-cloak class="time-picker-dropdown" :style="`top:${dropdownTop}px;left:${dropdownLeft}px;width:${dropdownWidth}px`" @click.stop>
                    <div class="time-picker-search"><input type="text" x-model="search" placeholder="Find time…" @keydown.stop></div>
                    <div class="time-picker-scroll">
                      <template x-if="groupedOptions.am.length">
                        <div>
                          <div class="time-picker-group-label">Morning</div>
                          <template x-for="opt in groupedOptions.am" :key="opt.value">
                            <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.end ? 'selected' : ''">
                              <span x-text="opt.label"></span>
                              <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                          </template>
                        </div>
                      </template>
                      <template x-if="groupedOptions.pm.length">
                        <div>
                          <div class="time-picker-group-label">Afternoon / Evening</div>
                          <template x-for="opt in groupedOptions.pm" :key="opt.value">
                            <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.end ? 'selected' : ''">
                              <span x-text="opt.label"></span>
                              <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                          </template>
                        </div>
                      </template>
                      <div x-show="!filteredOptions.length" class="px-3 py-4 text-xs text-gray-400 text-center">No times found</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Note <span class="font-normal text-gray-300">(optional)</span></label>
              <input type="text" x-model="popover.data.reason" placeholder="Add a reason or note…" class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
            </div>
          </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 dark:border-slate-700 shrink-0 bg-gray-50/50 dark:bg-slate-800/50">
          <button x-show="popover.data.exceptionId" @click="removeException(popover.data.exceptionId)" class="px-3 py-2 text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
            Remove
          </button>
          <div class="flex items-center gap-2 ml-auto">
            <button @click="closePopover()" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">Cancel</button>
            <button @click="saveCell()" :disabled="saving" class="px-4 py-2 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg disabled:opacity-50 transition-colors shadow-sm shadow-brand-500/20 flex items-center gap-1.5">
              <span x-show="!saving">Save</span>
              <span x-show="saving">Saving…</span>
            </button>
          </div>
        </div>
      </div>

      {{-- Bulk Edit --}}
      <div x-show="popover.mode === 'bulk'" x-cloak class="flex flex-col h-full">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-700 shrink-0">
          <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white"><span x-text="Object.keys(selectedCells).length"></span> schedules selected</h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Apply changes to all selected cells</p>
          </div>
          <button @click="closePopover()" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

          {{-- Status --}}
          <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-2">Apply to selected</label>
            <div class="grid grid-cols-3 gap-2 p-1 bg-gray-100 dark:bg-slate-700 rounded-xl">
              <button @click="popover.data.status = 'work'" :class="popover.data.status === 'work' ? 'bg-white dark:bg-slate-600 text-brand-600 dark:text-brand-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'" class="py-2.5 text-xs font-semibold rounded-lg transition-all">Set working</button>
              <button @click="popover.data.status = 'off'" :class="popover.data.status === 'off' ? 'bg-white dark:bg-slate-600 text-gray-800 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'" class="py-2.5 text-xs font-semibold rounded-lg transition-all">Set day off</button>
              <button @click="popover.data.status = 'exception'" :class="popover.data.status === 'exception' ? 'bg-white dark:bg-slate-600 text-amber-600 dark:text-amber-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'" class="py-2.5 text-xs font-semibold rounded-lg transition-all">Add exception</button>
            </div>
          </div>

          {{-- Working Hours --}}
          <div x-show="popover.data.status === 'work'" x-transition class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Start time</label>
              <div x-data="timePicker(popover.data, 'start')" class="time-picker-wrapper" @click.away="open = false">
                <button type="button" @click="toggle($el)" :class="open ? 'open' : ''" class="time-picker-trigger">
                  <span x-text="formatTime(model.start, '9:00 AM')"></span>
                  <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="time-picker-dropdown" :style="`top:${dropdownTop}px;left:${dropdownLeft}px;width:${dropdownWidth}px`" @click.stop>
                  <div class="time-picker-search"><input type="text" x-model="search" placeholder="Find time…" @keydown.stop></div>
                  <div class="time-picker-scroll">
                    <template x-if="groupedOptions.am.length">
                      <div>
                        <div class="time-picker-group-label">Morning</div>
                        <template x-for="opt in groupedOptions.am" :key="opt.value">
                          <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.start ? 'selected' : ''">
                            <span x-text="opt.label"></span>
                            <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                          </div>
                        </template>
                      </div>
                    </template>
                    <template x-if="groupedOptions.pm.length">
                      <div>
                        <div class="time-picker-group-label">Afternoon / Evening</div>
                        <template x-for="opt in groupedOptions.pm" :key="opt.value">
                          <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.start ? 'selected' : ''">
                            <span x-text="opt.label"></span>
                            <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                          </div>
                        </template>
                      </div>
                    </template>
                    <div x-show="!filteredOptions.length" class="px-3 py-4 text-xs text-gray-400 text-center">No times found</div>
                  </div>
                </div>
              </div>
            </div>
            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">End time</label>
              <div x-data="timePicker(popover.data, 'end')" class="time-picker-wrapper" @click.away="open = false">
                <button type="button" @click="toggle($el)" :class="open ? 'open' : ''" class="time-picker-trigger">
                  <span x-text="formatTime(model.end, '6:00 PM')"></span>
                  <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="time-picker-dropdown" :style="`top:${dropdownTop}px;left:${dropdownLeft}px;width:${dropdownWidth}px`" @click.stop>
                  <div class="time-picker-search"><input type="text" x-model="search" placeholder="Find time…" @keydown.stop></div>
                  <div class="time-picker-scroll">
                    <template x-if="groupedOptions.am.length">
                      <div>
                        <div class="time-picker-group-label">Morning</div>
                        <template x-for="opt in groupedOptions.am" :key="opt.value">
                          <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.end ? 'selected' : ''">
                            <span x-text="opt.label"></span>
                            <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                          </div>
                        </template>
                      </div>
                    </template>
                    <template x-if="groupedOptions.pm.length">
                      <div>
                        <div class="time-picker-group-label">Afternoon / Evening</div>
                        <template x-for="opt in groupedOptions.pm" :key="opt.value">
                          <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.end ? 'selected' : ''">
                            <span x-text="opt.label"></span>
                            <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                          </div>
                        </template>
                      </div>
                    </template>
                    <div x-show="!filteredOptions.length" class="px-3 py-4 text-xs text-gray-400 text-center">No times found</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- Exception Fields --}}
          <div x-show="popover.data.status === 'exception'" x-transition class="space-y-3">
            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Exception type</label>
              <select x-model="popover.data.exceptionType" class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
                <option value="day_off">Day off</option>
                <option value="holiday">Holiday</option>
                <option value="sick_leave">Sick leave</option>
                <option value="urgent_leave">Urgent leave</option>
                <option value="custom_hours">Custom hours</option>
              </select>
            </div>

            <div x-show="popover.data.exceptionType === 'custom_hours'" class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Start time</label>
                <div x-data="timePicker(popover.data, 'start')" class="time-picker-wrapper" @click.away="open = false">
                  <button type="button" @click="toggle($el)" :class="open ? 'open' : ''" class="time-picker-trigger">
                    <span x-text="formatTime(model.start, '9:00 AM')"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                  </button>
                  <div x-show="open" x-cloak class="time-picker-dropdown" :style="`top:${dropdownTop}px;left:${dropdownLeft}px;width:${dropdownWidth}px`" @click.stop>
                    <div class="time-picker-search"><input type="text" x-model="search" placeholder="Find time…" @keydown.stop></div>
                    <div class="time-picker-scroll">
                      <template x-if="groupedOptions.am.length">
                        <div>
                          <div class="time-picker-group-label">Morning</div>
                          <template x-for="opt in groupedOptions.am" :key="opt.value">
                            <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.start ? 'selected' : ''">
                              <span x-text="opt.label"></span>
                              <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                          </template>
                        </div>
                      </template>
                      <template x-if="groupedOptions.pm.length">
                        <div>
                          <div class="time-picker-group-label">Afternoon / Evening</div>
                          <template x-for="opt in groupedOptions.pm" :key="opt.value">
                            <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.start ? 'selected' : ''">
                              <span x-text="opt.label"></span>
                              <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                          </template>
                        </div>
                      </template>
                      <div x-show="!filteredOptions.length" class="px-3 py-4 text-xs text-gray-400 text-center">No times found</div>
                    </div>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">End time</label>
                <div x-data="timePicker(popover.data, 'end')" class="time-picker-wrapper" @click.away="open = false">
                  <button type="button" @click="toggle($el)" :class="open ? 'open' : ''" class="time-picker-trigger">
                    <span x-text="formatTime(model.end, '6:00 PM')"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                  </button>
                  <div x-show="open" x-cloak class="time-picker-dropdown" :style="`top:${dropdownTop}px;left:${dropdownLeft}px;width:${dropdownWidth}px`" @click.stop>
                    <div class="time-picker-search"><input type="text" x-model="search" placeholder="Find time…" @keydown.stop></div>
                    <div class="time-picker-scroll">
                      <template x-if="groupedOptions.am.length">
                        <div>
                          <div class="time-picker-group-label">Morning</div>
                          <template x-for="opt in groupedOptions.am" :key="opt.value">
                            <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.end ? 'selected' : ''">
                              <span x-text="opt.label"></span>
                              <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                          </template>
                        </div>
                      </template>
                      <template x-if="groupedOptions.pm.length">
                        <div>
                          <div class="time-picker-group-label">Afternoon / Evening</div>
                          <template x-for="opt in groupedOptions.pm" :key="opt.value">
                            <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.end ? 'selected' : ''">
                              <span x-text="opt.label"></span>
                              <svg class="check" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                          </template>
                        </div>
                      </template>
                      <div x-show="!filteredOptions.length" class="px-3 py-4 text-xs text-gray-400 text-center">No times found</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Note <span class="font-normal text-gray-300">(optional)</span></label>
              <input type="text" x-model="popover.data.reason" placeholder="Add a reason or note…" class="w-full px-3 py-2.5 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
            </div>
          </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 dark:border-slate-700 shrink-0 bg-gray-50/50 dark:bg-slate-800/50">
          <button @click="selectedCells = {}; closePopover();" class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">Clear selection</button>
          <button @click="saveBulk()" :disabled="saving" class="px-4 py-2 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg disabled:opacity-50 transition-colors shadow-sm shadow-brand-500/20 flex items-center gap-1.5">
            <span x-show="!saving">Apply to <span x-text="Object.keys(selectedCells).length"></span></span>
            <span x-show="saving">Applying…</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- BULK ACTION BAR --}}
  <div
    class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-xl shadow-lg px-5 py-3 flex items-center gap-4 z-40 transition-all duration-250"
    :class="Object.keys(selectedCells).length > 0 && !popover.open ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8 pointer-events-none'"
    @click.stop
  >
    <span class="text-sm font-bold text-gray-800 dark:text-white"><span x-text="Object.keys(selectedCells).length"></span> selected</span>
    <button @click="openBulkEdit()" class="px-3 py-1.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg transition-colors shadow-sm shadow-brand-500/20">Edit Selected</button>
    <button @click="selectedCells = {}" class="px-3 py-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">Clear</button>
  </div>

</div>
@endsection

@push('scripts')
<script>
/* ═══════════════════════════════════════════════════════════════
   SHARED HELPERS — Extract these to a global app.js in production
   to keep things DRY across views.
   ═══════════════════════════════════════════════════════════════ */

// ─── Redesigned 30-min interval time picker component ───
function timePicker(model, property) {
    return {
        model: model,
        property: property,
        open: false,
        search: '',
        dropdownTop: 0,
        dropdownLeft: 0,
        dropdownWidth: 0,
        timeOptions: (() => {
            const opts = [];
            for (let h = 0; h < 24; h++) {
                for (let m = 0; m < 60; m += 30) {
                    const val = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
                    const ampm = h >= 12 ? 'PM' : 'AM';
                    const h12 = h % 12 || 12;
                    opts.push({ value: val, label: `${h12}:${String(m).padStart(2,'0')} ${ampm}`, amPm: ampm });
                }
            }
            return opts;
        })(),
        get filteredOptions() {
            if (!this.search) return this.timeOptions;
            const q = this.search.toLowerCase().replace(/\s/g, '');
            return this.timeOptions.filter(o => 
                o.label.toLowerCase().replace(/\s/g, '').includes(q) || 
                o.value.includes(q)
            );
        },
        get groupedOptions() {
            const opts = this.filteredOptions;
            return { am: opts.filter(o => o.amPm === 'AM'), pm: opts.filter(o => o.amPm === 'PM') };
        },
        toggle($el) {
            this.open = !this.open;
            this.search = '';
            if (this.open) {
                this.$nextTick(() => {
                    const rect = $el.getBoundingClientRect();
                    const dropdownHeight = 320;
                    const dropdownWidth = 220; // comfortable fixed width

                    // Vertical placement
                    let top = rect.bottom + 6;
                    if (top + dropdownHeight > window.innerHeight - 12) {
                        top = rect.top - dropdownHeight - 6;
                    }

                    // Horizontal: center under trigger, but keep inside viewport
                    let left = rect.left + (rect.width / 2) - (dropdownWidth / 2);
                    if (left < 12) left = 12;
                    if (left + dropdownWidth > window.innerWidth - 12) {
                        left = window.innerWidth - dropdownWidth - 12;
                    }

                    this.dropdownTop = top;
                    this.dropdownLeft = left;
                    this.dropdownWidth = dropdownWidth;
                });
            }
        },
        select(t) { this.model[this.property] = t; this.open = false; },
        close() { this.open = false; }
    };
}

function formatTime(t, fallback = '') {
    if (!t) return fallback;
    const [h, m] = String(t).split(':').map(Number);
    if (isNaN(h) || isNaN(m)) return fallback;
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${String(m).padStart(2,'0')} ${ampm}`;
}

function toast(message, icon = 'success') {
    if (typeof Swal === 'undefined') { alert(message); return; }
    const isDark = document.documentElement.classList.contains('dark');
    Swal.fire({
        icon: icon,
        title: icon === 'success' ? 'Success' : (icon === 'warning' ? 'Warning' : 'Error'),
        text: message,
        timer: icon === 'success' ? 3000 : 4000,
        timerProgressBar: true,
        showConfirmButton: false,
        toast: true,
        position: 'top-end',
        background: isDark ? '#1e293b' : '#ffffff',
        color: isDark ? '#fff' : '#374151'
    });
}

/* ═══════════════════════════════════════════════════════════════
   SCHEDULES APP
   ═══════════════════════════════════════════════════════════════ */
function schedApp() {
  return {
    staffFilter: '',
    selectedStaffIds: [],
    activeTemplate: '',
    selectedCells: {},
    lastSelected: null,
    saving: false,
    todayStr: '{{ $todayStr }}', 
    popover: {
      open: false,
      mode: 'edit',
      data: { userId: '', date: '', dow: '', start: '09:00', end: '18:00', status: 'work', exceptionType: 'day_off', reason: '', exceptionId: null }
    },

    staffNames: @json($timeline->mapWithKeys(function($row) {
      $u = $row['user'];
      return [$u->id => $u->first_name . ' ' . $u->last_name];
    })),

    staffColors: @json($timeline->mapWithKeys(function($row, $index) use ($colors) {
      $u = $row['user'];
      return [$u->id => $colors[$index % 8]];
    })),

    staffNameById(id) {
      return this.staffNames[id] || 'Staff Member';
    },

    staffColor(id) {
      return this.staffColors[id] || '#78716c';
    },

    staffInitials(id) {
      const name = this.staffNames[id] || '';
      return name.split(' ').map(n => n[0]).join('').substring(0,2).toUpperCase();
    },

    staffMatchesFilter(name) {
      if (!this.staffFilter) return true;
      return name.toLowerCase().includes(this.staffFilter.toLowerCase());
    },

    init() {
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          if (this.popover.open) {
            this.closePopover();
            e.preventDefault();
          }
          return;
        }
        if ((e.key === 't' || e.key === 'T') && this.activeTemplate && 
            !['INPUT','SELECT','TEXTAREA'].includes(document.activeElement.tagName)) {
          e.preventDefault();
          this.applyTemplateToSelected();
        }
      });
    },

    toggleStaffSelection(staffId) {
      const idx = this.selectedStaffIds.indexOf(staffId);
      if (idx === -1) {
        this.selectedStaffIds.push(staffId);
      } else {
        this.selectedStaffIds.splice(idx, 1);
      }
    },

    handleCellClick(e, userId, date, dow, start, end, type, exType, exId) {
      if (date < this.todayStr) {
        return;
      }

      const key = `${userId}|${date}`;

      if (e.shiftKey && this.lastSelected && this.lastSelected !== key) {
        const lastParts = this.lastSelected.split('|');
        if (lastParts[0] == userId) {
          const cells = Array.from(document.querySelectorAll(`[data-key^="${userId}|"]`));
          const keys = cells.map(c => c.dataset.key);
          const startIdx = keys.indexOf(this.lastSelected);
          const endIdx = keys.indexOf(key);
          if (startIdx !== -1 && endIdx !== -1) {
            const [a, b] = startIdx < endIdx ? [startIdx, endIdx] : [endIdx, startIdx];
            const next = { ...this.selectedCells };
            for (let i = a; i <= b; i++) {
              const cellDate = keys[i].split('|')[1];
              if (cellDate >= this.todayStr) next[keys[i]] = true;
            }
            this.selectedCells = next;
          }
        }
      } else if (e.ctrlKey || e.metaKey) {
        this.toggleSelection(key);
      } else {
        this.selectedCells = { [key]: true };
        this.openEdit(userId, date, dow, start, end, type, exType, exId);
      }
      this.lastSelected = key;
    },

    toggleSelection(key) {
      const next = { ...this.selectedCells };
      if (next[key]) delete next[key];
      else next[key] = true;
      this.selectedCells = next;
    },

    openEdit(userId, date, dow, start, end, type, exType, exId) {
      this.popover = {
        open: true,
        mode: 'edit',
        data: {
          userId, date, dow,
          start: start || '09:00',
          end: end || '18:00',
          status: type === 'exception' ? 'exception' : (type === 'work' ? 'work' : 'off'),
          exceptionType: exType || 'day_off',
          reason: '',
          exceptionId: exId
        }
      };
    },

    openBulkEdit() {
      if (Object.keys(this.selectedCells).length === 0) return;
      this.popover = {
        open: true,
        mode: 'bulk',
        data: { 
          status: 'work', 
          start: '09:00', 
          end: '18:00', 
          exceptionType: 'day_off', 
          reason: '',
          userId: '',
          date: '',
          dow: '',
          exceptionId: null
        }
      };
    },

    closePopover() { 
      this.popover.open = false;
    },

    buildPayload(d, userId, date) {
      let exceptionType, startTime, endTime;
      if (d.status === 'work') {
        exceptionType = 'custom_hours';
        startTime = d.start;
        endTime = d.end;
      } else if (d.status === 'off') {
        exceptionType = 'day_off';
        startTime = null;
        endTime = null;
      } else {
        exceptionType = d.exceptionType;
        startTime = d.exceptionType === 'custom_hours' ? d.start : null;
        endTime = d.exceptionType === 'custom_hours' ? d.end : null;
      }
      return {
        user_id: userId,
        exception_type: exceptionType,
        date: date,
        end_date: null,
        start_time: startTime,
        end_time: endTime,
        reason: d.reason || null
      };
    },

    saveCell() {
      this.saving = true;
      const payload = this.buildPayload(this.popover.data, this.popover.data.userId, this.popover.data.date);

      fetch('{{ $exceptionStoreRoute }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(async r => {
        const data = await r.json();
        if (!r.ok) throw new Error(data.message || data.error || 'Save failed');
        return data;
      })
      .then(d => {
        if (d.success) {
          toast(d.message || 'Schedule updated');
          setTimeout(() => location.reload(), 300);
        } else {
          toast(d.message || 'Save failed', 'error');
        }
      })
      .catch(e => toast('Error: ' + e.message, 'error'))
      .finally(() => this.saving = false);
    },

    saveBulk() {
      this.saving = true;
      const d = this.popover.data;
      const exceptions = [];

      Object.keys(this.selectedCells).forEach(key => {
        const [userId, date] = key.split('|');
        exceptions.push(this.buildPayload(d, parseInt(userId), date));
      });

      fetch('{{ $exceptionBulkRoute }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ exceptions })
      })
      .then(async r => {
        const data = await r.json();
        if (!r.ok) throw new Error(data.message || data.error || 'Bulk save failed');
        return data;
      })
      .then(d => {
        if (d.success) {
          toast(d.message || 'Schedules updated');
          this.selectedCells = {};
          this.closePopover();
          setTimeout(() => location.reload(), 300);
        } else {
          toast(d.message || 'Bulk save failed', 'error');
        }
      })
      .catch(e => toast('Error: ' + e.message, 'error'))
      .finally(() => this.saving = false);
    },

    applyTemplateToAll() {
      if (!this.activeTemplate) return toast('Select a template first', 'error');
      const userIds = @json($staff->pluck('id'));
      this.applyTemplateToUsers(userIds);
    },

    applyTemplateToSelected() {
      if (!this.activeTemplate) return toast('Select a template first', 'error');
      if (this.selectedStaffIds.length === 0) return toast('Select at least one staff member from the sidebar', 'error');
      this.applyTemplateToUsers(this.selectedStaffIds);
    },

    applyTemplateToUsers(userIds) {
      if (!confirm(`Apply template to ${userIds.length} staff?`)) return;
      this.saving = true;
      fetch('{{ $templateApplyBulkRoute }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ 
          template_id: parseInt(this.activeTemplate), 
          user_ids: userIds, 
          week_start: '{{ $weekStart ?? now()->startOfWeek()->toDateString() }}'
        })
      })
      .then(async r => {
        const data = await r.json();
        if (!r.ok) {
          if (data.errors) {
            const msgs = Object.values(data.errors).flat().join(', ');
            throw new Error(msgs);
          }
          throw new Error(data.message || data.error || 'Request failed: ' + r.status);
        }
        return data;
      })
      .then(d => {
        const applied = d.applied ?? (d.success ? 1 : 0);
        const failed = d.failed ?? {};
        const failedCount = Object.keys(failed).length;

        if (applied && failedCount === 0) {
          toast(d.message || 'Template applied successfully');
          setTimeout(() => location.reload(), 300);
        } else if (applied && failedCount > 0) {
          console.error('Template apply failures:', failed);
          toast(`Applied to ${applied}, failed for ${failedCount}`, 'warning');
        } else {
          const failMsg = failedCount > 0 
            ? 'Failed: ' + Object.entries(failed).map(([k,v]) => `${k}: ${v}`).join(', ')
            : (d.message || 'Unknown error');
          toast(failMsg, 'error');
        }
      })
      .catch(e => {
        console.error('Template apply error:', e);
        toast('Error: ' + e.message, 'error');
      })
      .finally(() => this.saving = false);
    },

    removeException(id) {
      if (!confirm('Remove this block?')) return;
      fetch('{{ $exceptionRoute }}/' + id, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        }
      })
      .then(async r => {
        const data = await r.json();
        if (!r.ok) throw new Error(data.message || 'Delete failed');
        return data;
      })
      .then(d => {
        if (d.success) {
          toast('Block removed');
          setTimeout(() => location.reload(), 300);
        } else {
          toast(d.message || 'Delete failed', 'error');
        }
      })
      .catch(e => toast('Error: ' + e.message, 'error'));
    }
  }
}
</script>
@endpush