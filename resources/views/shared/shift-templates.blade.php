@php
$apiBase = $isAdmin ? url('/admin/api/staff') : url('/receptionist/api/staff');
$updateRoute = $isAdmin ? route('admin.schedule-template.update') : route('receptionist.schedule-template.update');
$exceptionRoute = $isAdmin ? route('admin.schedule-exception.store') : route('receptionist.schedule-exception.store');
$templateStoreRoute = $isAdmin ? route('admin.shift-templates.store') : route('receptionist.shift-templates.store');
$templateUpdateRoute = $isAdmin ? url('/admin/shift-templates') : url('/receptionist/shift-templates');
$templateDeleteRoute = $isAdmin ? url('/admin/shift-templates') : url('/receptionist/shift-templates');
$days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
@endphp

@extends($isAdmin ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Shift Templates')

@push('styles')
<style>
[x-cloak] { display: none !important; }

/* ─── Shared Time Picker (DRY) ─── */
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

/* ─── Card hover ─── */
.day-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.day-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.dark .day-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.2); }

/* ─── Scrollbar ─── */
.sidebar-scroll::-webkit-scrollbar { width: 4px; }
.sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.dark .sidebar-scroll::-webkit-scrollbar-thumb { background: #475569; }

/* ─── Mobile: left panel becomes horizontal scrollable tabs ─── */
@media (max-width: 768px) {
    .mobile-panel-scroll {
        display: flex;
        overflow-x: auto;
        gap: 0.5rem;
        padding: 0.5rem;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .mobile-panel-scroll::-webkit-scrollbar { display: none; }
    .mobile-panel-scroll > * { flex-shrink: 0; }
}
</style>
@endpush

@section('content')
<div x-data="templateApp()" x-init="init()" class="flex flex-col h-[calc(100dvh-72px-2rem)] md:h-[calc(100dvh-72px-4rem)] overflow-hidden bg-gray-50 dark:bg-slate-900">

  {{-- TOOLBAR (functional, not a page header) --}}
  <div class="flex items-center justify-between gap-3 px-4 py-3 bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 shrink-0 z-30">
    <div class="flex items-center gap-2 min-w-0">
      <div class="w-8 h-8 rounded-lg bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center shrink-0">
        <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
      </div>
      <p class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block truncate">Manage reusable patterns and staff weekly schedules</p>
    </div>
    <a href="{{ $isAdmin ? route('admin.schedules') : route('receptionist.schedules') }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-lg transition-colors shrink-0">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 19l-7-7 7-7"/></svg>
      <span class="hidden sm:inline">Back to Schedules</span>
      <span class="sm:hidden">Back</span>
    </a>
  </div>

  {{-- TWO PANEL WORKSPACE --}}
  <div class="flex flex-1 overflow-hidden relative">

    {{-- LEFT SIDEBAR: Desktop = fixed panel, Mobile = collapsible horizontal strip --}}
    <aside class="hidden md:flex w-72 shrink-0 bg-white dark:bg-slate-800 border-r border-gray-200 dark:border-slate-700 flex-col overflow-hidden h-full">

      {{-- Templates Section --}}
      <div class="border-b border-gray-100 dark:border-slate-700">
        <div class="flex items-center justify-between px-4 py-3">
          <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Shift Templates</h3>
          <button type="button" @click="templateManager.startCreate()" class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-md transition-colors shadow-sm shadow-brand-500/20">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New
          </button>
        </div>
        <div class="px-4 pb-3">
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/></svg>
            <input type="text" x-model="templateManager.filter" placeholder="Search templates…" class="w-full pl-8 pr-3 py-2 text-xs bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
          </div>
        </div>
        <div class="overflow-y-auto sidebar-scroll px-2 pb-2 max-h-[200px]">
          <template x-for="tpl in templateManager.filteredTemplates" :key="tpl.id">
            <div class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-all duration-200 group"
              :class="templateManager.editing === tpl.id ? 'bg-brand-50 dark:bg-brand-900/20 ring-1 ring-brand-200 dark:ring-brand-800' : 'hover:bg-gray-50 dark:hover:bg-slate-700/50'">
              <button type="button" @click="templateManager.startEdit(tpl); selectedStaffId = '';" class="flex items-center gap-3 flex-1 min-w-0 text-left">
                <div class="w-7 h-7 rounded-md flex items-center justify-center text-[10px] font-bold text-white shrink-0" :style="'background: ' + (tpl.name.length > 0 ? 'hsl(' + (tpl.name.charCodeAt(0) * 15 % 360) + ', 60%, 45%)' : '#78716c')" x-text="tpl.name.charAt(0).toUpperCase()"></div>
                <div class="min-w-0 flex-1">
                  <div class="text-xs font-semibold text-gray-800 dark:text-gray-100 truncate" x-text="tpl.name"></div>
                  <div class="flex gap-1 mt-1">
                    <template x-for="(p, idx) in (tpl.pattern || [])" :key="idx">
                      <div class="w-1.5 h-1.5 rounded-full" :class="p && p.is_day_off ? 'bg-gray-300 dark:bg-gray-600' : 'bg-brand-500'"></div>
                    </template>
                  </div>
                </div>
              </button>
              <button type="button" @click.stop="templateManager.deleteTemplate(tpl.id)" class="p-1.5 rounded-md text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all shrink-0" title="Delete template">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
              </button>
            </div>
          </template>
          <div x-show="templateManager.filteredTemplates.length === 0" x-cloak class="px-4 py-6 text-center text-xs text-gray-400">
            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
            No templates found
          </div>
        </div>
      </div>

      {{-- Staff Section --}}
      <div class="flex-1 flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-slate-700">
          <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Staff Members</h3>
        </div>
        <div class="px-4 pb-3 pt-2">
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/></svg>
            <input type="text" x-model="staffFilter" placeholder="Search staff…" class="w-full pl-8 pr-3 py-2 text-xs bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
          </div>
        </div>
        <div class="flex-1 overflow-y-auto sidebar-scroll px-2 pb-2">
          @foreach($staff as $s)
            <button type="button" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-all duration-200"
              :class="selectedStaffId == {{ $s->id }} && templateManager.editing === null ? 'bg-brand-50 dark:bg-brand-900/20 ring-1 ring-brand-200 dark:ring-brand-800' : 'hover:bg-gray-50 dark:hover:bg-slate-700/50'"
              @click="selectedStaffId = {{ $s->id }}; templateManager.editing = null; loadStaffData()"
              x-show="staffMatchesFilter({{ $s->id }}, '{{ strtolower($s->first_name.' '.$s->last_name) }}')">
              <div class="w-7 h-7 rounded-md flex items-center justify-center text-[10px] font-bold text-white shrink-0" style="background: #78716c;">{{ substr($s->first_name,0,1) }}{{ substr($s->last_name,0,1) }}</div>
              <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $s->first_name }} {{ $s->last_name }}</div>
                <div class="text-[10px] text-gray-400 dark:text-gray-500">Weekly schedule & exceptions</div>
              </div>
            </button>
          @endforeach
        </div>
      </div>
    </aside>

    {{-- MOBILE: Horizontal tab strip replaces sidebar --}}
    <div class="md:hidden shrink-0 bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
      {{-- Mobile templates strip --}}
      <div class="border-b border-gray-100 dark:border-slate-700">
        <div class="flex items-center justify-between px-3 py-2">
          <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Templates</h3>
          <button type="button" @click="templateManager.startCreate()" class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold text-white bg-brand-600 hover:bg-brand-700 rounded transition-colors">
            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New
          </button>
        </div>
        <div class="mobile-panel-scroll px-2 pb-2">
          <template x-for="tpl in templateManager.filteredTemplates" :key="tpl.id">
            <button type="button" @click="templateManager.startEdit(tpl); selectedStaffId = '';"
              class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-left transition-all border"
              :class="templateManager.editing === tpl.id ? 'bg-brand-50 dark:bg-brand-900/20 border-brand-200 dark:border-brand-800' : 'bg-gray-50 dark:bg-slate-700 border-gray-200 dark:border-slate-600'">
              <div class="w-6 h-6 rounded flex items-center justify-center text-[9px] font-bold text-white shrink-0" :style="'background: ' + (tpl.name.length > 0 ? 'hsl(' + (tpl.name.charCodeAt(0) * 15 % 360) + ', 60%, 45%)' : '#78716c')" x-text="tpl.name.charAt(0).toUpperCase()"></div>
              <span class="text-xs font-semibold text-gray-800 dark:text-gray-100 whitespace-nowrap" x-text="tpl.name"></span>
            </button>
          </template>
        </div>
      </div>
      {{-- Mobile staff strip --}}
      <div class="px-3 py-2">
        <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Staff</h3>
        <div class="mobile-panel-scroll">
          @foreach($staff as $s)
            <button type="button"
              class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-left transition-all border"
              :class="selectedStaffId == {{ $s->id }} && templateManager.editing === null ? 'bg-brand-50 dark:bg-brand-900/20 border-brand-200 dark:border-brand-800' : 'bg-gray-50 dark:bg-slate-700 border-gray-200 dark:border-slate-600'"
              @click="selectedStaffId = {{ $s->id }}; templateManager.editing = null; loadStaffData()">
              <div class="w-6 h-6 rounded flex items-center justify-center text-[9px] font-bold text-white shrink-0" style="background: #78716c;">{{ substr($s->first_name,0,1) }}{{ substr($s->last_name,0,1) }}</div>
              <span class="text-xs font-semibold text-gray-800 dark:text-gray-100 whitespace-nowrap">{{ $s->first_name }} {{ $s->last_name }}</span>
            </button>
          @endforeach
        </div>
      </div>
    </div>

    {{-- RIGHT WORKSPACE --}}
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 w-full min-w-0">

      {{-- Loading --}}
      <div x-show="loading" x-cloak class="flex flex-col items-center justify-center py-20 gap-3">
        <div class="w-8 h-8 border-2 border-gray-200 dark:border-gray-600 border-t-brand-500 rounded-full animate-spin"></div>
        <p class="text-sm text-gray-400 dark:text-gray-500">Loading schedule data…</p>
      </div>

      {{-- Empty State --}}
      <div x-show="!loading && templateManager.editing === null && !selectedStaffId" x-cloak>
        <div class="flex flex-col items-center justify-center py-20 text-center px-4">
          <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-slate-800 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
          </div>
          <p class="text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">Select a template or staff member</p>
          <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs">Choose from the sidebar to view or edit schedules, or create a new reusable shift template.</p>
        </div>
      </div>

      {{-- TEMPLATE WORKSPACE --}}
      <div x-show="!loading && templateManager.editing !== null" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="max-w-4xl mx-auto">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
            <div>
              <h2 class="text-lg font-bold text-gray-900 dark:text-white" x-text="templateManager.editing === 'new' ? 'Create Template' : 'Edit Template'"></h2>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="templateManager.editing === 'new' ? 'Define a new reusable shift pattern' : 'Modify the existing shift pattern'"></p>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" @click="templateManager.cancelEdit()" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">Cancel</button>
              <button type="button" @click="templateManager.saveTemplate()" :disabled="templateManager.saving" class="px-4 py-2 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg disabled:opacity-50 transition-colors shadow-sm shadow-brand-500/20">
                <span x-show="!templateManager.saving">Save Template</span>
                <span x-show="templateManager.saving">Saving…</span>
              </button>
            </div>
          </div>

          {{-- Template Name --}}
          <div class="mb-6 max-w-sm">
            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">Template Name</label>
            <input type="text" x-model="templateManager.form.name" placeholder="e.g. Morning Shift" class="w-full px-3 py-2 text-sm bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
          </div>

          {{-- Weekly Pattern Visual --}}
          <div class="grid grid-cols-7 gap-1.5 md:gap-2 mb-6">
            <template x-for="(day, idx) in days" :key="idx">
              <div class="day-card bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-2 md:p-3 text-center" :class="templateManager.form.pattern[idx].is_day_off ? 'opacity-50' : ''">
                <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1" x-text="day.substring(0,3).toUpperCase()"></div>
                <div class="text-[11px] md:text-xs font-bold text-gray-800 dark:text-gray-100" x-text="templateManager.form.pattern[idx].is_day_off ? 'Off' : (templateManager.form.pattern[idx].start_time + '–' + templateManager.form.pattern[idx].end_time)"></div>
              </div>
            </template>
          </div>

          {{-- Day Editor Cards --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            <template x-for="(day, index) in days" :key="index">
              <div class="day-card bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                  <span class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wide" x-text="day"></span>
                  <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="templateManager.form.pattern[index].is_day_off" :true-value="1" :false-value="0" class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-xs font-medium text-gray-500">Off</span>
                  </label>
                </div>
                <div x-show="!templateManager.form.pattern[index].is_day_off" x-transition class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Start</label>
                    <div class="time-picker-wrapper" @click.away="templateManager.showPickers[index] = false">
                      <input type="text" readonly @click="templateManager.showPickers[index] = true" :value="templateManager.form.pattern[index].start_time" class="w-full px-2 py-1.5 text-xs bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-md text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 cursor-pointer">
                      <div x-show="templateManager.showPickers[index]" x-cloak class="time-picker-dropdown">
                        <template x-for="t in timeOptions" :key="t">
                          <div @click="templateManager.form.pattern[index].start_time = t; templateManager.showPickers[index] = false" class="time-picker-option" :class="t === templateManager.form.pattern[index].start_time ? 'selected' : ''" x-text="t"></div>
                        </template>
                      </div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">End</label>
                    <div class="time-picker-wrapper" @click.away="templateManager.showEndPickers[index] = false">
                      <input type="text" readonly @click="templateManager.showEndPickers[index] = true" :value="templateManager.form.pattern[index].end_time" class="w-full px-2 py-1.5 text-xs bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-md text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 cursor-pointer">
                      <div x-show="templateManager.showEndPickers[index]" x-cloak class="time-picker-dropdown">
                        <template x-for="t in timeOptions" :key="t">
                          <div @click="templateManager.form.pattern[index].end_time = t; templateManager.showEndPickers[index] = false" class="time-picker-option" :class="t === templateManager.form.pattern[index].end_time ? 'selected' : ''" x-text="t"></div>
                        </template>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>

      {{-- STAFF WORKSPACE --}}
      <div x-show="!loading && selectedStaffId && templateManager.editing === null" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="max-w-4xl mx-auto">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
            <div>
              <h2 class="text-lg font-bold text-gray-900 dark:text-white" x-text="staffNameById(selectedStaffId)"></h2>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Weekly schedule template and upcoming exceptions</p>
            </div>
            <button type="button" @click="saveTemplate()" :disabled="saving" class="px-4 py-2 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg disabled:opacity-50 transition-colors shadow-sm shadow-brand-500/20">
              <span x-show="!saving">Save Weekly Template</span>
              <span x-show="saving">Saving…</span>
            </button>
          </div>

          {{-- Weekly Pattern Visual --}}
          <div class="grid grid-cols-7 gap-1.5 md:gap-2 mb-6">
            <template x-for="(day, idx) in days" :key="idx">
              <div class="day-card bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-2 md:p-3 text-center" :class="template[idx].is_day_off ? 'opacity-50' : ''">
                <div class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1" x-text="day.substring(0,3).toUpperCase()"></div>
                <div class="text-[11px] md:text-xs font-bold text-gray-800 dark:text-gray-100" x-text="template[idx].is_day_off ? 'Off' : (template[idx].start_time + '–' + template[idx].end_time)"></div>
              </div>
            </template>
          </div>

          {{-- Day Editor Cards --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 mb-8">
            <template x-for="(day, index) in days" :key="index">
              <div class="day-card bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                  <span class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wide" x-text="day"></span>
                  <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="template[index].is_day_off" :true-value="1" :false-value="0" class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-xs font-medium text-gray-500">Off</span>
                  </label>
                </div>
                <div x-show="!template[index].is_day_off" x-transition class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Start</label>
                    <div class="time-picker-wrapper" @click.away="showStaffPickers[index] = false">
                      <input type="text" readonly @click="showStaffPickers[index] = true" :value="template[index].start_time" class="w-full px-2 py-1.5 text-xs bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-md text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 cursor-pointer">
                      <div x-show="showStaffPickers[index]" x-cloak class="time-picker-dropdown">
                        <template x-for="t in timeOptions" :key="t">
                          <div @click="template[index].start_time = t; showStaffPickers[index] = false" class="time-picker-option" :class="t === template[index].start_time ? 'selected' : ''" x-text="t"></div>
                        </template>
                      </div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">End</label>
                    <div class="time-picker-wrapper" @click.away="showStaffEndPickers[index] = false">
                      <input type="text" readonly @click="showStaffEndPickers[index] = true" :value="template[index].end_time" class="w-full px-2 py-1.5 text-xs bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-md text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 cursor-pointer">
                      <div x-show="showStaffEndPickers[index]" x-cloak class="time-picker-dropdown">
                        <template x-for="t in timeOptions" :key="t">
                          <div @click="template[index].end_time = t; showStaffEndPickers[index] = false" class="time-picker-option" :class="t === template[index].end_time ? 'selected' : ''" x-text="t"></div>
                        </template>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>

          {{-- Upcoming Exceptions --}}
          <div class="border-t border-gray-200 dark:border-slate-700 pt-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-3">
              <h3 class="text-sm font-bold text-gray-900 dark:text-white">Upcoming Exceptions</h3>
              <button type="button" @click="showAddException = true" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg transition-colors shadow-sm shadow-brand-500/20 self-start sm:self-auto">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Exception
              </button>
            </div>

            {{-- Add Exception Form --}}
            <div x-show="showAddException" x-transition class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-4 mb-4">
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                <div>
                  <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Type</label>
                  <select x-model="newException.type" class="w-full px-2 py-1.5 text-xs bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-md text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
                    <option value="day_off">Day Off</option>
                    <option value="holiday">Holiday</option>
                    <option value="sick_leave">Sick Leave</option>
                    <option value="urgent_leave">Urgent Leave</option>
                    <option value="custom_hours">Custom Hours</option>
                  </select>
                </div>
                <div>
                  <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Start Date</label>
                  <input type="date" x-model="newException.date" class="w-full px-2 py-1.5 text-xs bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-md text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
                </div>
                <div>
                  <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">End Date (optional)</label>
                  <input type="date" x-model="newException.end_date" class="w-full px-2 py-1.5 text-xs bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-md text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
                </div>
              </div>
              <div x-show="newException.type === 'custom_hours'" class="grid grid-cols-2 gap-3 mb-3 max-w-xs">
                <div>
                  <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Start Time</label>
                  <div class="time-picker-wrapper" @click.away="showExStartPicker = false">
                    <input type="text" readonly @click="showExStartPicker = true" :value="newException.start_time" class="w-full px-2 py-1.5 text-xs bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-md text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 cursor-pointer">
                    <div x-show="showExStartPicker" x-cloak class="time-picker-dropdown">
                      <template x-for="t in timeOptions" :key="t">
                        <div @click="newException.start_time = t; showExStartPicker = false" class="time-picker-option" :class="t === newException.start_time ? 'selected' : ''" x-text="t"></div>
                      </template>
                    </div>
                  </div>
                </div>
                <div>
                  <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">End Time</label>
                  <div class="time-picker-wrapper" @click.away="showExEndPicker = false">
                    <input type="text" readonly @click="showExEndPicker = true" :value="newException.end_time" class="w-full px-2 py-1.5 text-xs bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-md text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 cursor-pointer">
                    <div x-show="showExEndPicker" x-cloak class="time-picker-dropdown">
                      <template x-for="t in timeOptions" :key="t">
                        <div @click="newException.end_time = t; showExEndPicker = false" class="time-picker-option" :class="t === newException.end_time ? 'selected' : ''" x-text="t"></div>
                      </template>
                    </div>
                  </div>
                </div>
              </div>
              <div class="mb-3 max-w-md">
                <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Reason (optional)</label>
                <input type="text" x-model="newException.reason" placeholder="Reason…" class="w-full px-2 py-1.5 text-xs bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-md text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:outline-none focus:border-brand-500 transition-shadow">
              </div>
              <div class="flex gap-2">
                <button type="button" @click="showAddException = false" class="px-3 py-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">Cancel</button>
                <button type="button" @click="addException()" :disabled="saving" class="px-3 py-1.5 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg disabled:opacity-50 transition-colors shadow-sm shadow-brand-500/20">
                  <span x-show="!saving">Add Exception</span>
                  <span x-show="saving">Saving…</span>
                </button>
              </div>
            </div>

            {{-- Exceptions Table --}}
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-slate-700">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-gray-50 dark:bg-slate-800/80 border-b border-gray-200 dark:border-slate-700">
                    <th class="text-left px-3 py-2 text-[9px] font-bold uppercase tracking-wider text-gray-400">Date</th>
                    <th class="text-left px-3 py-2 text-[9px] font-bold uppercase tracking-wider text-gray-400">Type</th>
                    <th class="text-left px-3 py-2 text-[9px] font-bold uppercase tracking-wider text-gray-400">Hours</th>
                    <th class="text-left px-3 py-2 text-[9px] font-bold uppercase tracking-wider text-gray-400">Reason</th>
                    <th class="text-right px-3 py-2 text-[9px] font-bold uppercase tracking-wider text-gray-400">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                  <template x-for="ex in exceptions" :key="ex.id">
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                      <td class="px-3 py-2.5 font-semibold text-gray-800 dark:text-gray-100" x-text="ex.exception_date"></td>
                      <td class="px-3 py-2.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide border"
                          :class="{
                            'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800': ex.type === 'day_off' || ex.type === 'sick_leave' || ex.type === 'urgent_leave',
                            'bg-brand-50 text-brand-700 border-brand-200 dark:bg-brand-900/20 dark:text-brand-300 dark:border-brand-800': ex.type === 'holiday',
                            'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800': ex.type === 'custom_hours'
                          }"
                          x-text="ex.type.replace('_', ' ')">
                        </span>
                      </td>
                      <td class="px-3 py-2.5 text-gray-500 dark:text-gray-400" x-text="ex.start_time && ex.end_time ? ex.start_time + ' – ' + ex.end_time : '—'"></td>
                      <td class="px-3 py-2.5 text-gray-500 dark:text-gray-400 max-w-[200px] truncate" x-text="ex.reason || '—'"></td>
                      <td class="px-3 py-2.5 text-right">
                        <button type="button" @click="deleteException(ex.id)" class="inline-flex items-center p-1 rounded-md text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                      </td>
                    </tr>
                  </template>
                  <tr x-show="exceptions.length === 0">
                    <td colspan="5" class="px-3 py-8 text-center text-xs text-gray-400 dark:text-gray-500">
                      <svg class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                      No upcoming exceptions
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>

</div>
@endsection

@push('scripts')
<script>
function templateApp() {
  return {
    selectedStaffId: '',
    staffFilter: '',
    loading: false,
    saving: false,
    showAddException: false,
    days: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
    template: Array.from({length:7},()=>({start_time:'09:00',end_time:'18:00',is_day_off:0})),
    exceptions: [],
    newException: {type:'day_off',date:'',end_date:'',start_time:'09:00',end_time:'18:00',reason:''},
    showStaffPickers: Array(7).fill(false),
    showStaffEndPickers: Array(7).fill(false),
    showExStartPicker: false,
    showExEndPicker: false,
    timeOptions: (() => {
      const opts = [];
      for (let h = 0; h < 24; h++) {
        for (let m = 0; m < 60; m += 30) {
          opts.push(`${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`);
        }
      }
      return opts;
    })(),

    staffNames: @json($staff->mapWithKeys(fn($s) => [$s->id => $s->first_name . ' ' . $s->last_name])),

    templateManager: null,

    init() {
      const parent = this;
      this.templateManager = {
        templates: @json($templates ?? []),
        editing: null,
        saving: false,
        filter: '',
        form: { name: '', pattern: Array.from({length:7},()=>({start_time:'09:00',end_time:'18:00',is_day_off:0})) },
        showPickers: Array(7).fill(false),
        showEndPickers: Array(7).fill(false),

        get filteredTemplates() {
          if (!this.filter) return this.templates;
          const q = this.filter.toLowerCase();
          return this.templates.filter(t => t.name.toLowerCase().includes(q));
        },

        startCreate() {
          this.editing = 'new';
          this.form = { name: '', pattern: Array.from({length:7},()=>({start_time:'09:00',end_time:'18:00',is_day_off:0})) };
          this.showPickers = Array(7).fill(false);
          this.showEndPickers = Array(7).fill(false);
        },

        startEdit(tpl) {
          this.editing = tpl.id;
          let pat;
          if (tpl.pattern && Array.isArray(tpl.pattern)) {
            pat = JSON.parse(JSON.stringify(tpl.pattern));
          } else {
            pat = Array.from({length:7},()=>({start_time:'09:00',end_time:'18:00',is_day_off:0}));
          }
          pat.forEach(p => {
            p.is_day_off = p.is_day_off ? 1 : 0;
            if (p.start_time && typeof p.start_time === 'string') p.start_time = p.start_time.substring(0, 5);
            if (p.end_time && typeof p.end_time === 'string') p.end_time = p.end_time.substring(0, 5);
          });
          this.form = { name: tpl.name, pattern: pat };
          this.showPickers = Array(7).fill(false);
          this.showEndPickers = Array(7).fill(false);
        },

        cancelEdit() {
          this.editing = null;
        },

        saveTemplate() {
          if (!this.form.name.trim()) { 
            alert('Template name is required'); 
            return; 
          }
          this.saving = true;
          const url = this.editing === 'new' ? '{{ $templateStoreRoute }}' : '{{ $templateUpdateRoute }}' + '/' + this.editing;
          const method = this.editing === 'new' ? 'POST' : 'PUT';
          const payload = {
            name: this.form.name.trim(),
            pattern: this.form.pattern.map(p => ({
              start_time: p.is_day_off ? null : (p.start_time || '09:00'),
              end_time: p.is_day_off ? null : (p.end_time || '18:00'),
              is_day_off: p.is_day_off ? true : false
            }))
          };
          fetch(url, {
            method: method,
            headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json','Accept':'application/json'},
            body: JSON.stringify(payload)
          })
          .then(async r => {
            const data = await r.json();
            if (!r.ok) {
              throw new Error(data.message || data.error || 'Server error ' + r.status);
            }
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

        deleteTemplate(id) {
          if (!confirm('Delete this template? This cannot be undone.')) return;
          fetch('{{ $templateDeleteRoute }}' + '/' + id, {
            method: 'DELETE',
            headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
          })
          .then(async r => {
            const data = await r.json();
            if (!r.ok) throw new Error(data.message || 'Delete failed');
            return data;
          })
          .then(d => {
            if (d.success) {
              setTimeout(() => location.reload(), 300);
            } else {
              alert(d.message || 'Delete failed');
            }
          })
          .catch(e => alert('Error: ' + e.message));
        }
      };

      @if($staff->count() === 1)
        this.selectedStaffId = '{{ $staff->first()->id }}';
        this.loadStaffData();
      @endif
    },

    staffNameById(id) {
      return this.staffNames[id] || 'Staff Member';
    },

    staffMatchesFilter(id, nameLower) {
      if (!this.staffFilter) return true;
      return nameLower.includes(this.staffFilter.toLowerCase());
    },

    loadStaffData() {
      if(!this.selectedStaffId) return;
      this.loading = true;
      const url = '{{ $apiBase }}' + '/' + this.selectedStaffId + '/schedule';
      fetch(url, {headers:{'Accept':'application/json'}})
        .then(async r => {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.json();
        })
        .then(data => {
          this.days.forEach((day, idx) => {
            const s = data.schedules[idx];
            if(s) {
              this.template[idx] = {
                start_time: s.start_time ? s.start_time.substring(0,5) : '09:00',
                end_time: s.end_time ? s.end_time.substring(0,5) : '18:00',
                is_day_off: s.is_day_off ? 1 : 0
              };
            } else {
              this.template[idx] = {start_time:'09:00',end_time:'18:00',is_day_off:0};
            }
          });
          this.exceptions = data.exceptions || [];
          this.loading = false;
        })
        .catch(e => {
          alert('Failed to load: '+e.message);
          this.loading=false;
        });
    },

    saveTemplate() {
      this.saving = true;
      const payload = {};
      payload[this.selectedStaffId] = {};
      this.template.forEach((day, idx) => {
        payload[this.selectedStaffId][idx] = {
          start_time: day.is_day_off ? null : (day.start_time || '09:00'),
          end_time: day.is_day_off ? null : (day.end_time || '18:00'),
          is_day_off: day.is_day_off ? '1' : '0'
        };
      });
      fetch('{{ $updateRoute }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json','Accept':'application/json'},
        body: JSON.stringify({schedules: payload})
      })
      .then(async r => {
        const data = await r.json();
        if (!r.ok) throw new Error(data.message || 'Save failed');
        return data;
      })
      .then(d => { /* success — master layout flash will show via redirect */ })
      .catch(e => alert('Error: '+e.message))
      .finally(() => this.saving = false);
    },

    addException() {
      if(!this.newException.date){alert('Start date is required');return;}
      this.saving = true;
      fetch('{{ $exceptionRoute }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json','Accept':'application/json'},
        body: JSON.stringify({
          user_id: this.selectedStaffId,
          exception_type: this.newException.type,
          date: this.newException.date,
          end_date: this.newException.end_date || null,
          start_time: this.newException.type === 'custom_hours' ? (this.newException.start_time || '09:00') : null,
          end_time: this.newException.type === 'custom_hours' ? (this.newException.end_time || '18:00') : null,
          reason: this.newException.reason || null
        })
      })
      .then(async r => {
        const data = await r.json();
        if (!r.ok) throw new Error(data.message || 'Failed to add exception');
        return data;
      })
      .then(d => {
        this.showAddException = false;
        this.newException = {type:'day_off',date:'',end_date:'',start_time:'09:00',end_time:'18:00',reason:''};
        this.loadStaffData();
      })
      .catch(e => alert('Error: '+e.message))
      .finally(() => this.saving = false);
    },

    deleteException(id) {
      if (!confirm('Remove this exception?')) return;
      const url = '{{ $isAdmin ? url("/admin/schedule-exception") : url("/receptionist/schedule-exception") }}' + '/' + id;
      fetch(url, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
      })
      .then(async r => {
        const data = await r.json();
        if (!r.ok) throw new Error(data.message || 'Delete failed');
        return data;
      })
      .then(d => {
        this.loadStaffData();
      })
      .catch(e => alert('Error: ' + e.message));
    }
  }
}
</script>
@endpush