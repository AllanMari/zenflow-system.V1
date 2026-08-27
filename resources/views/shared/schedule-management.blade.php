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

@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.receptionist')

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

.sheet-backdrop {
    background: rgba(0, 0, 0, 0);
    transition: background 0.3s ease;
    pointer-events: none;
}
.sheet-backdrop.open {
    background: rgba(0, 0, 0, 0.2);
    pointer-events: auto;
}
.dark .sheet-backdrop.open { background: rgba(0, 0, 0, 0.5); }

.time-picker-wrapper { position: relative; }
.time-picker-dropdown {
    position: absolute; top: 100%; left: 0; right: 0;
    max-height: 200px; overflow-y: auto;
    background: white; border: 1px solid #e5e7eb;
    border-radius: 0.5rem; z-index: 50;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
}
.dark .time-picker-dropdown { background: #1e293b; border-color: #475569; }
.time-picker-option { padding: 0.5rem 0.75rem; cursor: pointer; font-size: 0.875rem; }
.time-picker-option:hover { background: #f3f4f6; }
.dark .time-picker-option:hover { background: #334155; }
.time-picker-option.selected { background: #ccfbf1; color: #0f766e; font-weight: 600; }
.dark .time-picker-option.selected { background: #134e4a; color: #5eead4; }
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
          @endphp
          <button
            type="button"
            @click="toggleStaffSelection({{ $s->id }})"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-all duration-200"
            :class="selectedStaffIds.includes({{ $s->id }}) ? 'bg-brand-50 dark:bg-brand-900/20 ring-1 ring-brand-200 dark:ring-brand-800' : 'hover:bg-gray-50 dark:hover:bg-slate-700/50'"
            x-show="staffMatchesFilter('{{ addslashes($s->first_name.' '.$s->last_name) }}')"
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
            <div x-show="selectedStaffIds.includes({{ $s->id }})" class="w-4 h-4 rounded-full bg-brand-500 flex items-center justify-center">
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
                  @endphp
                  <tr
                    class="border-b border-gray-100 dark:border-slate-700 last:border-b-0"
                    x-show="staffMatchesFilter('{{ addslashes($s->first_name.' '.$s->last_name) }}') && (selectedStaffIds.length === 0 || selectedStaffIds.includes({{ $s->id }}))"
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
              @endphp
              <div class="flex items-stretch border-b border-gray-100 dark:border-slate-700 last:border-b-0 {{ $isPast ? 'cell-past' : '' }}" x-show="staffMatchesFilter('{{ addslashes($s->first_name.' '.$s->last_name) }}') && (selectedStaffIds.length === 0 || selectedStaffIds.includes({{ $s->id }}))">
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
                      @if(!empty($row['attendance']))
                        <span class="inline-flex items-center mt-1.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide
                          {{ $row['attendance']->status === 'present' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                          {{ $row['attendance']->status === 'late' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : '' }}
                          {{ $row['attendance']->status === 'absent' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}
                        ">{{ ucfirst($row['attendance']->status) }}</span>
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

  {{-- BACKDROP for bottom sheet --}}
  <div
    class="sheet-backdrop fixed inset-0 z-40 md:hidden"
    :class="popover.open ? 'open' : ''"
    @click="closePopover()"
    x-show="popover.open"
    x-transition:enter="transition-opacity ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
  ></div>

  {{-- CONTEXT PANEL (Bottom Sheet) --}}
  <div
    class="fixed bottom-0 right-0 md:left-64 left-0 bg-white dark:bg-slate-800 border-t border-gray-200 dark:border-slate-700 shadow-[0_-4px_24px_rgba(0,0,0,0.08)] dark:shadow-[0_-4px_24px_rgba(0,0,0,0.3)] z-50 transition-transform duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]"
    :class="popover.open ? 'translate-y-0' : 'translate-y-full'"
    x-show="true"
  >
    <div class="max-w-5xl mx-auto px-6 py-5">
      {{-- Single Edit --}}
      <div x-show="popover.mode === 'edit'" x-cloak>
        <div class="flex items-center justify-between mb-4">
          <div class="text-sm font-bold text-gray-800 dark:text-white">
            <span x-text="staffNameById(popover.data.userId)"></span>
            <span class="text-gray-400 font-normal ml-2" x-text="popover.data.date ? new Date(popover.data.date + 'T00:00:00').toLocaleDateString('en-US', {weekday:'long', month:'long', day:'numeric'}) : ''"></span>
          </div>
          <button @click="closePopover()" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="flex flex-wrap items-end gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Status</label>
            <select x-model="popover.data.status" class="py-2 px-3 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 min-w-[140px] transition-shadow">
              <option value="work">Working</option>
              <option value="off">Day Off</option>
              <option value="exception">Exception / Block</option>
            </select>
          </div>

          <template x-if="popover.data.status === 'work'">
            <div class="flex items-end gap-3">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Start</label>
                <div class="time-picker-wrapper" @click.away="showStartPicker = false">
                  <input type="text" readonly @click="showStartPicker = true" :value="popover.data.start" class="py-2 px-3 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 w-[100px] cursor-pointer">
                  <div x-show="showStartPicker" x-cloak class="time-picker-dropdown">
                    <template x-for="t in timeOptions" :key="t">
                      <div @click="popover.data.start = t; showStartPicker = false" class="time-picker-option" :class="t === popover.data.start ? 'selected' : ''" x-text="t"></div>
                    </template>
                  </div>
                </div>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400">End</label>
                <div class="time-picker-wrapper" @click.away="showEndPicker = false">
                  <input type="text" readonly @click="showEndPicker = true" :value="popover.data.end" class="py-2 px-3 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 w-[100px] cursor-pointer">
                  <div x-show="showEndPicker" x-cloak class="time-picker-dropdown">
                    <template x-for="t in timeOptions" :key="t">
                      <div @click="popover.data.end = t; showEndPicker = false" class="time-picker-option" :class="t === popover.data.end ? 'selected' : ''" x-text="t"></div>
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <template x-if="popover.data.status === 'exception'">
            <div class="flex flex-wrap items-end gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Type</label>
                <select x-model="popover.data.exceptionType" class="py-2 px-3 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 min-w-[160px] transition-shadow">
                  <option value="day_off">Day Off</option>
                  <option value="holiday">Holiday</option>
                  <option value="sick_leave">Sick Leave</option>
                  <option value="urgent_leave">Urgent Leave</option>
                  <option value="custom_hours">Custom Hours</option>
                </select>
              </div>
              <template x-if="popover.data.exceptionType === 'custom_hours'">
                <div class="flex items-end gap-3">
                  <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Start</label>
                    <div class="time-picker-wrapper" @click.away="showExStartPicker = false">
                      <input type="text" readonly @click="showExStartPicker = true" :value="popover.data.start" class="py-2 px-3 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 w-[100px] cursor-pointer">
                      <div x-show="showExStartPicker" x-cloak class="time-picker-dropdown">
                        <template x-for="t in timeOptions" :key="t">
                          <div @click="popover.data.start = t; showExStartPicker = false" class="time-picker-option" :class="t === popover.data.start ? 'selected' : ''" x-text="t"></div>
                        </template>
                      </div>
                    </div>
                  </div>
                  <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400">End</label>
                    <div class="time-picker-wrapper" @click.away="showExEndPicker = false">
                      <input type="text" readonly @click="showExEndPicker = true" :value="popover.data.end" class="py-2 px-3 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 w-[100px] cursor-pointer">
                      <div x-show="showExEndPicker" x-cloak class="time-picker-dropdown">
                        <template x-for="t in timeOptions" :key="t">
                          <div @click="popover.data.end = t; showExEndPicker = false" class="time-picker-option" :class="t === popover.data.end ? 'selected' : ''" x-text="t"></div>
                        </template>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
              <div class="flex flex-col gap-1.5 flex-1 min-w-[200px]">
                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Note</label>
                <input type="text" x-model="popover.data.reason" placeholder="Optional reason…" class="py-2 px-3 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
              </div>
            </div>
          </template>

          <div class="flex items-center gap-2 ml-auto">
            <button @click="closePopover()" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">Cancel</button>
            <button @click="saveCell()" :disabled="saving" class="px-4 py-2 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg disabled:opacity-50 transition-colors shadow-sm shadow-brand-500/20">
              <span x-show="!saving">Save</span>
              <span x-show="saving">Saving…</span>
            </button>
            <button x-show="popover.data.exceptionId" @click="removeException(popover.data.exceptionId)" class="px-4 py-2 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors">Remove</button>
          </div>
        </div>
      </div>

      {{-- Bulk Edit --}}
      <div x-show="popover.mode === 'bulk'" x-cloak>
        <div class="flex items-center justify-between mb-4">
          <div class="text-sm font-bold text-gray-800 dark:text-white"><span x-text="Object.keys(selectedCells).length"></span> schedules selected</div>
          <button @click="closePopover()" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-gray-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="flex flex-wrap items-end gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Apply to selected</label>
            <select x-model="popover.data.status" class="py-2 px-3 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 min-w-[160px] transition-shadow">
              <option value="work">Set Working Hours</option>
              <option value="off">Set Day Off</option>
              <option value="exception">Add Exception</option>
            </select>
          </div>

          <template x-if="popover.data.status === 'work'">
            <div class="flex items-end gap-3">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Start</label>
                <div class="time-picker-wrapper" @click.away="showBulkStartPicker = false">
                  <input type="text" readonly @click="showBulkStartPicker = true" :value="popover.data.start" class="py-2 px-3 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 w-[100px] cursor-pointer">
                  <div x-show="showBulkStartPicker" x-cloak class="time-picker-dropdown">
                    <template x-for="t in timeOptions" :key="t">
                      <div @click="popover.data.start = t; showBulkStartPicker = false" class="time-picker-option" :class="t === popover.data.start ? 'selected' : ''" x-text="t"></div>
                    </template>
                  </div>
                </div>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400">End</label>
                <div class="time-picker-wrapper" @click.away="showBulkEndPicker = false">
                  <input type="text" readonly @click="showBulkEndPicker = true" :value="popover.data.end" class="py-2 px-3 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 w-[100px] cursor-pointer">
                  <div x-show="showBulkEndPicker" x-cloak class="time-picker-dropdown">
                    <template x-for="t in timeOptions" :key="t">
                      <div @click="popover.data.end = t; showBulkEndPicker = false" class="time-picker-option" :class="t === popover.data.end ? 'selected' : ''" x-text="t"></div>
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <template x-if="popover.data.status === 'exception'">
            <div class="flex flex-wrap items-end gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Type</label>
                <select x-model="popover.data.exceptionType" class="py-2 px-3 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 min-w-[160px] transition-shadow">
                  <option value="day_off">Day Off</option>
                  <option value="holiday">Holiday</option>
                  <option value="custom_hours">Custom Hours</option>
                </select>
              </div>
              <template x-if="popover.data.exceptionType === 'custom_hours'">
                <div class="flex items-end gap-3">
                  <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Start</label>
                    <div class="time-picker-wrapper" @click.away="showBulkExStartPicker = false">
                      <input type="text" readonly @click="showBulkExStartPicker = true" :value="popover.data.start" class="py-2 px-3 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 w-[100px] cursor-pointer">
                      <div x-show="showBulkExStartPicker" x-cloak class="time-picker-dropdown">
                        <template x-for="t in timeOptions" :key="t">
                          <div @click="popover.data.start = t; showBulkExStartPicker = false" class="time-picker-option" :class="t === popover.data.start ? 'selected' : ''" x-text="t"></div>
                        </template>
                      </div>
                    </div>
                  </div>
                  <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400">End</label>
                    <div class="time-picker-wrapper" @click.away="showBulkExEndPicker = false">
                      <input type="text" readonly @click="showBulkEndPicker = true" :value="popover.data.end" class="py-2 px-3 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 w-[100px] cursor-pointer">
                      <div x-show="showBulkExEndPicker" x-cloak class="time-picker-dropdown">
                        <template x-for="t in timeOptions" :key="t">
                          <div @click="popover.data.end = t; showBulkExEndPicker = false" class="time-picker-option" :class="t === popover.data.end ? 'selected' : ''" x-text="t"></div>
                        </template>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </template>

          <div class="flex items-center gap-2 ml-auto">
            <button @click="selectedCells = {}; closePopover();" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">Clear Selection</button>
            <button @click="saveBulk()" :disabled="saving" class="px-4 py-2 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg disabled:opacity-50 transition-colors shadow-sm shadow-brand-500/20">
              <span x-show="!saving">Apply to <span x-text="Object.keys(selectedCells).length"></span></span>
              <span x-show="saving">Applying…</span>
            </button>
          </div>
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
function schedApp() {
  return {
    staffFilter: '',
    selectedStaffIds: [],
    activeTemplate: '',
    selectedCells: {},
    lastSelected: null,
    saving: false,
    showStartPicker: false,
    showEndPicker: false,
    showExStartPicker: false,
    showExEndPicker: false,
    showBulkStartPicker: false,
    showBulkEndPicker: false,
    showBulkExStartPicker: false,
    showBulkExEndPicker: false,
    timeOptions: (() => {
      const opts = [];
      for (let h = 0; h < 24; h++) {
        for (let m = 0; m < 60; m += 30) {
          opts.push(`${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`);
        }
      }
      return opts;
    })(),
    popover: {
      open: false,
      mode: 'edit',
      data: { userId: '', date: '', dow: '', start: '09:00', end: '18:00', status: 'work', exceptionType: 'day_off', reason: '', exceptionId: null }
    },

    staffNames: @json($timeline->mapWithKeys(function($row) {
      $u = $row['user'];
      return [$u->id => $u->first_name . ' ' . $u->last_name];
    })),

    staffNameById(id) {
      return this.staffNames[id] || 'Staff Member';
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
        if ((e.key === 't' || e.key === 'T') && this.activeTemplate && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'SELECT' && document.activeElement.tagName !== 'TEXTAREA') {
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
      const today = new Date().toISOString().split('T')[0];
      if (date < today) {
        return; // Silently ignore past clicks — master layout handles flash messages server-side
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
              if (cellDate >= today) next[keys[i]] = true;
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
      this.showStartPicker = false;
      this.showEndPicker = false;
      this.showExStartPicker = false;
      this.showExEndPicker = false;
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
      this.showBulkStartPicker = false;
      this.showBulkEndPicker = false;
      this.showBulkExStartPicker = false;
      this.showBulkExEndPicker = false;
    },

    closePopover() { 
      this.popover.open = false; 
      this.showStartPicker = false;
      this.showEndPicker = false;
      this.showExStartPicker = false;
      this.showExEndPicker = false;
      this.showBulkStartPicker = false;
      this.showBulkEndPicker = false;
      this.showBulkExStartPicker = false;
      this.showBulkExEndPicker = false;
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
          setTimeout(() => location.reload(), 300);
        } else {
          alert(d.message || 'Save failed');
        }
      })
      .catch(e => alert('Error: ' + e.message))
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
          this.selectedCells = {};
          this.closePopover();
          setTimeout(() => location.reload(), 300);
        } else {
          alert(d.message || 'Bulk save failed');
        }
      })
      .catch(e => alert('Error: ' + e.message))
      .finally(() => this.saving = false);
    },

    applyTemplateToAll() {
      if (!this.activeTemplate) return alert('Select a template first');
      const userIds = @json($staff->pluck('id'));
      this.applyTemplateToUsers(userIds);
    },

    applyTemplateToSelected() {
      if (!this.activeTemplate) return alert('Select a template first');
      if (this.selectedStaffIds.length === 0) return alert('Select at least one staff member from the sidebar');
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
          week_start: '{{ $weekStart }}' 
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
          setTimeout(() => location.reload(), 300);
        } else if (applied && failedCount > 0) {
          console.error('Template apply failures:', failed);
          alert(`Applied to ${applied}, failed for ${failedCount}`);
        } else {
          const failMsg = failedCount > 0 
            ? 'Failed: ' + Object.entries(failed).map(([k,v]) => `${k}: ${v}`).join(', ')
            : (d.message || 'Unknown error');
          alert(failMsg);
        }
      })
      .catch(e => {
        console.error('Template apply error:', e);
        alert('Error: ' + e.message);
      })
      .finally(() => this.saving = false);
    },

    removeException(id) {
      if (!confirm('Remove this block?')) return;
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '{{ $exceptionRoute }}/' + id;
      form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">`;
      document.body.appendChild(form);
      form.submit();
    }
  }
}
</script>
@endpush