@php
$colors = ['#10b981','#3b82f6','#8b5cf6','#f59e0b','#ec4899','#06b6d4','#84cc16','#f97316'];
$colorDarks = ['#047857','#1d4ed8','#6d28d9','#b45309','#be185d','#0891b2','#65a30d','#c2410c'];
$bulkRoute = $isAdmin ? route('admin.staff.schedule.bulk-update') : route('receptionist.schedules.bulk-update');
$blockRoute = $isAdmin ? route('admin.schedules.block') : route('receptionist.schedules.block');
$exceptionRoute = $isAdmin ? url('/admin/schedule-exception') : url('/receptionist/schedule-exception');
$templateApplyRoute = $isAdmin ? url('/admin/schedules/template') : url('/receptionist/schedules/template');
@endphp

@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Staff Schedules')

@push('styles')
<style>
:root {
  --sch-bg: #ffffff; --sch-bg2: #f8fafc; --sch-card: #ffffff; --sch-hover: #f1f5f9;
  --sch-border: #e2e8f0; --sch-text: #0f172a; --sch-text2: #64748b; --sch-muted: #94a3b8;
  --sch-accent: #0d9488; --sch-accent2: #ccfbf1; --sch-danger: #ef4444; --sch-warn: #f59e0b;
}
.dark { --sch-bg: #0f172a; --sch-bg2: #1e293b; --sch-card: #1e293b; --sch-hover: #334155;
  --sch-border: #334155; --sch-text: #f1f5f9; --sch-text2: #cbd5e1; --sch-muted: #64748b;
  --sch-accent: #2dd4bf; --sch-accent2: #134e4a; }

.sch-wrap { background: var(--sch-card); border: 1px solid var(--sch-border); border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgb(0 0 0 / 0.07); }
.sch-grid { display: grid; grid-template-columns: 220px repeat(7, minmax(130px, 1fr)); }
.sch-head { padding: 12px 6px; text-align: center; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--sch-text2); background: var(--sch-bg2); border-bottom: 1px solid var(--sch-border); border-right: 1px solid var(--sch-border); position: sticky; top: 0; z-index: 20; }
.sch-head:last-child { border-right: none; }
.sch-head.today { background: var(--sch-accent2); color: var(--sch-accent); }
.sch-head .d-num { font-size: 20px; font-weight: 800; color: var(--sch-text); display: block; margin-top: 2px; }
.sch-head.today .d-num { color: var(--sch-accent); }

.sch-staff { padding: 12px 14px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--sch-border); border-right: 1px solid var(--sch-border); background: var(--sch-card); position: sticky; left: 0; z-index: 21; }
.sch-staff-av { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; color: white; flex-shrink: 0; }
.sch-staff-name { font-weight: 700; font-size: 13px; color: var(--sch-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sch-staff-meta { font-size: 10px; color: var(--sch-muted); }

.sch-cell { padding: 8px; min-height: 90px; border-bottom: 1px solid var(--sch-border); border-right: 1px solid var(--sch-border); position: relative; cursor: pointer; transition: all .12s; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; user-select: none; }
.sch-cell:last-child { border-right: none; }
.sch-cell:hover { background: var(--sch-hover); }
.sch-cell.selected { box-shadow: inset 0 0 0 2px var(--sch-accent); background: var(--sch-accent2); }
.sch-cell.selected:hover { background: var(--sch-accent2); }

/* Work cell */
.cell-work { background: linear-gradient(135deg, var(--c, var(--sch-accent)), var(--c2, #0f766e)); color: white; margin: 4px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); font-size: 12px; font-weight: 700; text-align: center; line-height: 1.35; }
.cell-work:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.18); }
.cell-work .t-range { font-size: 10px; opacity: .9; font-weight: 500; }

/* Off cell */
.cell-off { opacity: .45; }
.cell-off-txt { font-size: 11px; font-weight: 800; color: var(--sch-muted); text-transform: uppercase; letter-spacing: .06em; }

/* Exception */
.cell-exc { background: #fee2e2; margin: 4px; border-radius: 10px; border: 2px solid #ef4444; }
.dark .cell-exc { background: rgba(239,68,68,0.12); }
.cell-exc-txt { font-size: 11px; font-weight: 700; color: #dc2626; text-align: center; }
.dark .cell-exc-txt { color: #f87171; }
.cell-exc-reason { font-size: 10px; color: #ef4444; text-align: center; margin-top: 2px; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Custom hours */
.cell-custom { background: linear-gradient(135deg, #fef3c7, #fde68a); margin: 4px; border-radius: 10px; border: 2px solid #f59e0b; }
.dark .cell-custom { background: rgba(245,158,11,0.15); }
.cell-custom-txt { font-size: 11px; font-weight: 700; color: #92400e; text-align: center; }

/* Attendance badge */
.att-badge { font-size: 9px; font-weight: 800; padding: 1px 5px; border-radius: 4px; text-transform: uppercase; letter-spacing: .03em; }
.att-absent { background: #ef4444; color: white; }
.att-late { background: #f59e0b; color: white; }
.att-present { background: #10b981; color: white; }

/* Inline Popover Editor */
.popover { position: absolute; z-index: 50; background: var(--sch-card); border: 1px solid var(--sch-border); border-radius: 14px; box-shadow: 0 20px 40px rgba(0,0,0,0.18); width: 260px; padding: 16px; display: none; }
.popover.active { display: block; animation: popIn .15s ease; }
@keyframes popIn { from { opacity: 0; transform: scale(.95) translateY(4px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.popover-arrow { position: absolute; width: 12px; height: 12px; background: var(--sch-card); border-left: 1px solid var(--sch-border); border-top: 1px solid var(--sch-border); transform: rotate(45deg); top: -6px; left: 50%; margin-left: -6px; }
.popover label { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--sch-muted); margin-bottom: 4px; }
.popover input, .popover select { width: 100%; border: 1px solid var(--sch-border); border-radius: 8px; padding: 7px 10px; font-size: 13px; background: var(--sch-bg); color: var(--sch-text); margin-bottom: 10px; }
.popover input:focus, .popover select:focus { outline: none; border-color: var(--sch-accent); box-shadow: 0 0 0 3px rgba(13,148,136,0.12); }
.popover .btn-row { display: flex; gap: 6px; margin-top: 4px; }
.popover button { flex: 1; padding: 8px 0; border-radius: 8px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; transition: all .15s; }
.popover .btn-primary { background: var(--sch-accent); color: white; }
.popover .btn-primary:hover { filter: brightness(1.1); }
.popover .btn-ghost { background: var(--sch-bg2); color: var(--sch-text2); border: 1px solid var(--sch-border); }
.popover .btn-danger { background: #ef4444; color: white; }

/* Bulk action bar */
.bulk-bar { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(100px); background: var(--sch-card); border: 1px solid var(--sch-border); border-radius: 14px; padding: 12px 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 12px; z-index: 40; transition: transform .25s cubic-bezier(0.32,0.72,0,1); }
.bulk-bar.active { transform: translateX(-50%) translateY(0); }

/* Template chips */
.t-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; border: 2px solid var(--sch-border); background: var(--sch-card); cursor: pointer; transition: all .15s; font-size: 12px; font-weight: 600; color: var(--sch-text); }
.t-chip:hover, .t-chip.on { border-color: var(--sch-accent); background: var(--sch-accent2); }

/* Staff filter */
.staff-filter { display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px; }
.staff-filter::-webkit-scrollbar { height: 4px; }
.staff-filter::-webkit-scrollbar-thumb { background: var(--sch-border); border-radius: 4px; }

@media (max-width: 1024px) {
  .sch-grid { grid-template-columns: 180px repeat(7, minmax(110px, 1fr)); }
  .sch-cell { min-height: 75px; padding: 5px; }
}
@media (max-width: 768px) {
  .sch-grid { grid-template-columns: 150px repeat(7, minmax(90px, 1fr)); overflow-x: auto; }
  .popover { width: 220px; }
}
</style>
@endpush

@section('content')
<div x-data="schedApp()" x-init="init()" class="max-w-[1400px] mx-auto space-y-5 pb-12" @keydown.window="handleKey($event)">

  {{-- Header --}}
  <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
    <div>
      <h1 class="text-3xl font-extrabold tracking-tight" style="color: var(--sch-text);">Staff Schedules</h1>
      <p class="mt-1 text-sm" style="color: var(--sch-text2);">
        @if($isAdmin) Manage weekly shifts, templates & exceptions @else View and manage staff availability @endif
        <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300 font-semibold">Shift+Click to select multiple</span>
      </p>
    </div>
    <div class="flex items-center gap-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-1.5 shadow-sm">
      <a :href="`?week_start=${prevWeek}`" class="px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-sm font-semibold text-gray-600 dark:text-gray-300">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </a>
      <span class="px-4 font-bold text-sm tabular-nums" style="color: var(--sch-text);">{{ $weekLabel }}</span>
      <a :href="`?week_start=${nextWeek}`" class="px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-sm font-semibold text-gray-600 dark:text-gray-300">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </a>
      <a href="?week_start={{ now()->startOfWeek()->toDateString() }}" class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-xs font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition">Today</a>
    </div>
  </div>

  @if($canEdit)
  {{-- Toolbar --}}
  <div class="sch-wrap p-4">
    <div class="flex flex-col xl:flex-row gap-4 items-start xl:items-center justify-between">
      <div class="flex flex-wrap items-center gap-3">
        <div>
          <label class="block text-[10px] font-bold uppercase tracking-wider mb-1" style="color: var(--sch-muted);">Template</label>
          <select x-model="activeTemplate" class="border rounded-lg p-2 text-sm font-medium" style="background: var(--sch-bg); border-color: var(--sch-border); color: var(--sch-text); min-width: 180px;">
            <option value="">Select template...</option>
            @foreach($templates as $t)
              <option value="{{ $t->id }}">{{ $t->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="flex gap-2 mt-5">
          <button type="button" @click="applyTemplateToAll()" :disabled="!activeTemplate" class="px-4 py-2 rounded-lg text-xs font-bold text-white transition shadow-sm disabled:opacity-40 disabled:cursor-not-allowed" style="background: var(--sch-accent);">Apply to All</button>
          <button type="button" @click="applyTemplateToSelected()" :disabled="!activeTemplate || selectedCells.size === 0" class="px-4 py-2 rounded-lg text-xs font-bold transition border disabled:opacity-40 disabled:cursor-not-allowed" style="background: var(--sch-bg); border-color: var(--sch-border); color: var(--sch-text2);">Apply to Selected</button>
        </div>
      </div>

      <div class="flex-1 min-w-[200px]">
        <label class="block text-[10px] font-bold uppercase tracking-wider mb-1" style="color: var(--sch-muted);">Filter Staff</label>
        <input type="text" x-model="staffFilter" placeholder="Type to filter..." class="w-full border rounded-lg p-2 text-sm" style="background: var(--sch-bg); border-color: var(--sch-border); color: var(--sch-text);">
      </div>

      @if($isAdmin)
      <a href="{{ route('admin.shift-templates.index') }}" class="px-4 py-2 rounded-lg text-xs font-bold transition border hover:bg-gray-50 dark:hover:bg-gray-800" style="background: var(--sch-bg); border-color: var(--sch-border); color: var(--sch-text2);">Manage Templates</a>
      @endif
    </div>
  </div>
  @endif

  {{-- Grid --}}
  <div class="sch-wrap overflow-x-auto" @click.away="closePopover()">
    <div class="sch-grid" style="min-width: 980px;">
      {{-- Header row --}}
      <div class="sch-head" style="position: sticky; left: 0; z-index: 22; background: var(--sch-bg2);">Staff</div>
      @foreach($days as $day)
        <div class="sch-head {{ $day['is_today'] ? 'today' : '' }}">
          {{ $day['label'] }}
          <span class="d-num">{{ $day['day'] }}</span>
        </div>
      @endforeach

      {{-- Data rows --}}
      @foreach($timeline as $row)
        @php
          $s = $row['user']; $si = $loop->index;
          $c = $colors[$si % 8]; $c2 = $colorDarks[$si % 8];
        @endphp
        <div class="sch-staff" x-show="!staffFilter || '{{ strtolower($s->first_name.' '.$s->last_name) }}'.includes(staffFilter.toLowerCase())">
          <div class="sch-staff-av" style="background: {{ $c }};">{{ substr($s->first_name,0,1) }}{{ substr($s->last_name,0,1) }}</div>
          <div class="min-w-0">
            <div class="sch-staff-name">{{ $s->first_name }} {{ $s->last_name }}</div>
            <div class="sch-staff-meta">{{ collect($row['days'])->where('type','work')->count() }} working days</div>
          </div>
        </div>

        @foreach($row['days'] as $cell)
          @php
            $cellKey = "{{ $s->id }}|{{ $cell['date'] }}|{{ $cell['dow'] }}";
            $typeClass = match($cell['type']) {
              'work' => 'cell-work',
              'exception' => ($cell['exception_type'] ?? '') === 'custom' ? 'cell-custom' : 'cell-exc',
              default => 'cell-off'
            };
          @endphp
          <div
            class="sch-cell {{ $typeClass }}"
            style="{{ $cell['type'] === 'work' ? '--c: '.$c.'; --c2: '.$c2.';' : '' }}"
            :class="selectedCells.has('{{ $s->id }}|{{ $cell['date'] }}') ? 'selected' : ''"
            @if($canEdit)
              @click="handleCellClick($event, {{ $s->id }}, '{{ $cell['date'] }}', {{ $cell['dow'] }}, '{{ $cell['start_time'] }}', '{{ $cell['end_time'] }}', '{{ $cell['type'] }}', '{{ $cell['exception_type'] ?? '' }}', {{ $cell['exception']['id'] ?? 'null' }} )"
            @endif
            data-key="{{ $s->id }}|{{ $cell['date'] }}"
            x-show="!staffFilter || '{{ strtolower($s->first_name.' '.$s->last_name) }}'.includes(staffFilter.toLowerCase())"
          >
            @if($cell['type'] === 'work')
              <div>{{ $cell['start_time'] }}</div>
              <div class="t-range">to</div>
              <div>{{ $cell['end_time'] }}</div>
              @if(!empty($cell['attendance']))
                <span class="att-badge att-{{ $cell['attendance'] }}">{{ $cell['attendance'] }}</span>
              @endif
            @elseif($cell['type'] === 'exception')
              @if(($cell['exception_type'] ?? '') === 'custom')
                <div class="cell-custom-txt">{{ $cell['start_time'] }} – {{ $cell['end_time'] }}</div>
              @else
                <div class="cell-exc-txt">{{ ucfirst(str_replace('_', ' ', $cell['exception_type'] ?? 'Blocked')) }}</div>
              @endif
              @if(!empty($cell['exception']['reason']))
                <div class="cell-exc-reason">{{ $cell['exception']['reason'] }}</div>
              @endif
              @if(!empty($cell['attendance']))
                <span class="att-badge att-{{ $cell['attendance'] }}">{{ $cell['attendance'] }}</span>
              @endif
            @else
              <div class="cell-off-txt">OFF</div>
              @if(!empty($cell['attendance']))
                <span class="att-badge att-{{ $cell['attendance'] }}">{{ $cell['attendance'] }}</span>
              @endif
            @endif
          </div>
        @endforeach
      @endforeach
    </div>

    {{-- Inline Popover Editor --}}
    <div class="popover" :class="popover.open ? 'active' : ''" :style="popover.style" x-cloak>
      <div class="popover-arrow"></div>
      <div x-show="popover.mode === 'edit'">
        <label>Status</label>
        <select x-model="popover.data.status">
          <option value="work">Working</option>
          <option value="off">Day Off</option>
          <option value="exception">Exception / Block</option>
        </select>

        <div x-show="popover.data.status === 'work'" class="grid grid-cols-2 gap-2">
          <div>
            <label>Start</label>
            <input type="time" x-model="popover.data.start" step="1800">
          </div>
          <div>
            <label>End</label>
            <input type="time" x-model="popover.data.end" step="1800">
          </div>
        </div>

        <div x-show="popover.data.status === 'exception'">
          <label>Exception Type</label>
          <select x-model="popover.data.exceptionType">
            <option value="day_off">Day Off</option>
            <option value="holiday">Holiday</option>
            <option value="sick_leave">Sick Leave</option>
            <option value="urgent_leave">Urgent Leave</option>
            <option value="custom_hours">Custom Hours</option>
          </select>
          <div x-show="popover.data.exceptionType === 'custom_hours'" class="grid grid-cols-2 gap-2">
            <div><label>Start</label><input type="time" x-model="popover.data.start" step="1800"></div>
            <div><label>End</label><input type="time" x-model="popover.data.end" step="1800"></div>
          </div>
          <label>Note</label>
          <input type="text" x-model="popover.data.reason" placeholder="Optional reason...">
        </div>

        <div class="btn-row">
          <button type="button" @click="closePopover()" class="btn-ghost">Cancel</button>
          <button type="button" @click="saveCell()" class="btn-primary" :disabled="saving">
            <span x-show="!saving">Save</span>
            <span x-show="saving">...</span>
          </button>
        </div>
        <div x-show="popover.data.exceptionId" class="mt-2 pt-2 border-t" style="border-color: var(--sch-border);">
          <button type="button" @click="removeException(popover.data.exceptionId)" class="btn-danger w-full">Remove Exception</button>
        </div>
      </div>

      {{-- Bulk apply mini-form --}}
      <div x-show="popover.mode === 'bulk'">
        <label>Apply to <span x-text="selectedCells.size"></span> cells</label>
        <select x-model="popover.data.status" class="mb-2">
          <option value="work">Set Working Hours</option>
          <option value="off">Set Day Off</option>
          <option value="exception">Add Exception</option>
        </select>
        <div x-show="popover.data.status === 'work'" class="grid grid-cols-2 gap-2">
          <div><label>Start</label><input type="time" x-model="popover.data.start" step="1800"></div>
          <div><label>End</label><input type="time" x-model="popover.data.end" step="1800"></div>
        </div>
        <div x-show="popover.data.status === 'exception'">
          <label>Type</label>
          <select x-model="popover.data.exceptionType">
            <option value="day_off">Day Off</option>
            <option value="holiday">Holiday</option>
            <option value="custom_hours">Custom Hours</option>
          </select>
        </div>
        <div class="btn-row mt-2">
          <button type="button" @click="closePopover()" class="btn-ghost">Cancel</button>
          <button type="button" @click="saveBulk()" class="btn-primary" :disabled="saving">Apply</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Legend --}}
  <div class="flex gap-5 text-xs justify-center flex-wrap" style="color: var(--sch-muted);">
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full" style="background: #10b981;"></span> Working</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-500"></span> Blocked</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-yellow-500"></span> Custom Hours</span>
    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background: repeating-linear-gradient(45deg, #e2e8f0, #e2e8f0 4px, #cbd5e1 4px, #cbd5e1 8px);"></span> Weekly Off</span>
  </div>

  {{-- Bulk Action Bar --}}
  <div class="bulk-bar" :class="selectedCells.size > 0 ? 'active' : ''">
    <span class="text-sm font-bold" style="color: var(--sch-text);"><span x-text="selectedCells.size"></span> selected</span>
    <button @click="openBulkEdit()" class="px-3 py-1.5 rounded-lg text-xs font-bold text-white" style="background: var(--sch-accent);">Edit Selected</button>
    <button @click="selectedCells.clear(); closePopover();" class="px-3 py-1.5 rounded-lg text-xs font-bold border" style="border-color: var(--sch-border); color: var(--sch-text2);">Clear</button>
  </div>

  @if(!$isAdmin && !$canEdit)
  <div class="p-4 rounded-xl border flex items-center gap-3" style="background: rgba(234,179,8,0.08); border-color: rgba(234,179,8,0.3);">
    <svg class="w-6 h-6 text-yellow-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <p class="text-yellow-700 dark:text-yellow-300 text-sm font-medium">View only mode. Contact admin to modify schedules.</p>
  </div>
  @endif

  {{-- Hidden form for traditional POST fallback --}}
  <form x-ref="bulkForm" action="{{ $bulkRoute }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="schedules" :value="JSON.stringify(buildBulkPayload())">
  </form>

  <form x-ref="blockForm" action="{{ $blockRoute }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="user_id" :value="popover.data.userId">
    <input type="hidden" name="date" :value="popover.data.date">
    <input type="hidden" name="exception_type" :value="popover.data.exceptionType">
    <input type="hidden" name="reason" :value="popover.data.reason">
    <input type="hidden" name="start_time" :value="popover.data.start">
    <input type="hidden" name="end_time" :value="popover.data.end">
  </form>
</div>
@endsection

@push('scripts')
<script>
function schedApp() {
  return {
    staffFilter: '',
    activeTemplate: '',
    selectedCells: new Set(),
    lastSelected: null,
    saving: false,
    popover: {
      open: false,
      mode: 'edit', // 'edit' | 'bulk'
      style: {},
      data: { userId: '', date: '', dow: '', start: '09:00', end: '18:00', status: 'work', exceptionType: 'day_off', reason: '', exceptionId: null }
    },
    weekStart: '{{ $weekStart }}',

    get prevWeek() {
      const d = new Date(this.weekStart);
      d.setDate(d.getDate() - 7);
      return d.toISOString().split('T')[0];
    },
    get nextWeek() {
      const d = new Date(this.weekStart);
      d.setDate(d.getDate() + 7);
      return d.toISOString().split('T')[0];
    },

    init() {
      // Close popover on Escape
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') this.closePopover();
      });
    },

    handleKey(e) {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA') return;
      if (e.key === 't' || e.key === 'T') {
        if (this.activeTemplate) this.applyTemplateToSelected();
      }
    },

    handleCellClick(e, userId, date, dow, start, end, type, exType, exId) {
      const key = `${userId}|${date}`;
      if (e.shiftKey && this.lastSelected && this.lastSelected !== key) {
        // Range select (same staff only for simplicity)
        const lastParts = this.lastSelected.split('|');
        if (lastParts[0] == userId) {
          // Find all cells between last and current
          const cells = Array.from(document.querySelectorAll(`[data-key^="${userId}|"]`));
          const keys = cells.map(c => c.dataset.key);
          const startIdx = keys.indexOf(this.lastSelected);
          const endIdx = keys.indexOf(key);
          const [a, b] = startIdx < endIdx ? [startIdx, endIdx] : [endIdx, startIdx];
          for (let i = a; i <= b; i++) this.selectedCells.add(keys[i]);
        }
      } else if (e.ctrlKey || e.metaKey) {
        this.toggleSelection(key);
      } else {
        this.selectedCells.clear();
        this.selectedCells.add(key);
        this.openEdit(e.target, userId, date, dow, start, end, type, exType, exId);
      }
      this.lastSelected = key;
    },

    toggleSelection(key) {
      if (this.selectedCells.has(key)) this.selectedCells.delete(key);
      else this.selectedCells.add(key);
    },

    openEdit(el, userId, date, dow, start, end, type, exType, exId) {
      const rect = el.getBoundingClientRect();
      const container = el.closest('.overflow-x-auto').getBoundingClientRect();
      let left = rect.left - container.left + rect.width / 2 - 130;
      let top = rect.top - container.top + rect.height + 10;
      if (left < 10) left = 10;
      if (left > container.width - 270) left = container.width - 270;

      this.popover = {
        open: true,
        mode: 'edit',
        style: { position: 'absolute', left: left + 'px', top: top + 'px' },
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
      if (this.selectedCells.size === 0) return;
      const first = Array.from(this.selectedCells)[0];
      const el = document.querySelector(`[data-key="${first}"]`);
      if (!el) return;
      const rect = el.getBoundingClientRect();
      const container = el.closest('.overflow-x-auto').getBoundingClientRect();
      this.popover = {
        open: true,
        mode: 'bulk',
        style: { position: 'absolute', left: (rect.left - container.left) + 'px', top: (rect.top - container.top + rect.height + 8) + 'px' },
        data: { status: 'work', start: '09:00', end: '18:00', exceptionType: 'day_off', reason: '' }
      };
    },

    closePopover() { this.popover.open = false; },

    saveCell() {
      this.saving = true;
      const d = this.popover.data;
      const payload = {
        schedules: [{
          user_id: d.userId,
          day_of_week: d.dow,
          start_time: d.status === 'work' || (d.status === 'exception' && d.exceptionType === 'custom_hours') ? d.start : null,
          end_time: d.status === 'work' || (d.status === 'exception' && d.exceptionType === 'custom_hours') ? d.end : null,
          is_day_off: d.status === 'off' ? '1' : '0'
        }]
      };

      // If exception, use block form instead
      if (d.status === 'exception') {
        const form = this.$refs.blockForm;
        form.querySelector('[name="user_id"]').value = d.userId;
        form.querySelector('[name="date"]').value = d.date;
        form.querySelector('[name="exception_type"]').value = d.exceptionType;
        form.querySelector('[name="reason"]').value = d.reason;
        form.querySelector('[name="start_time"]').value = d.exceptionType === 'custom_hours' ? d.start : '';
        form.querySelector('[name="end_time"]').value = d.exceptionType === 'custom_hours' ? d.end : '';
        form.submit();
        return;
      }

      fetch('{{ $bulkRoute }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      }).then(r => {
        if (r.ok) location.reload();
        else alert('Save failed');
      }).finally(() => this.saving = false);
    },

    saveBulk() {
      this.saving = true;
      const d = this.popover.data;
      const schedules = [];
      this.selectedCells.forEach(key => {
        const [userId, date] = key.split('|');
        const dow = new Date(date).getDay();
        schedules.push({
          user_id: parseInt(userId),
          day_of_week: dow,
          start_time: d.status === 'work' ? d.start : null,
          end_time: d.status === 'work' ? d.end : null,
          is_day_off: d.status === 'off' ? '1' : '0'
        });
      });

      fetch('{{ $bulkRoute }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ schedules })
      }).then(r => {
        if (r.ok) location.reload();
        else alert('Bulk save failed');
      }).finally(() => this.saving = false);
    },

    buildBulkPayload() {
      // Used by hidden form if JS fetch blocked
      const out = [];
      this.selectedCells.forEach(key => {
        const [userId, date] = key.split('|');
        out.push({ user_id: userId, day_of_week: new Date(date).getDay(), start_time: '09:00', end_time: '18:00' });
      });
      return out;
    },

    applyTemplateToAll() {
      if (!this.activeTemplate) return alert('Select a template first');
      const userIds = @json($staff->pluck('id'));
      this.applyTemplateToUsers(userIds);
    },

    applyTemplateToSelected() {
      if (!this.activeTemplate) return alert('Select a template first');
      if (this.selectedCells.size === 0) return alert('Select at least one cell (staff)');
      const userIds = Array.from(new Set(Array.from(this.selectedCells).map(k => parseInt(k.split('|')[0]))));
      this.applyTemplateToUsers(userIds);
    },

    applyTemplateToUsers(userIds) {
      if (!confirm(`Apply template to ${userIds.length} staff?`)) return;
      this.saving = true;
      fetch('{{ $templateApplyRoute }}/bulk', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ template_id: this.activeTemplate, user_ids: userIds, week_start: this.weekStart })
      }).then(r => r.json()).then(d => {
        if (d.applied) location.reload();
        else alert('Failed: ' + JSON.stringify(d.failed));
      }).catch(e => alert('Error: ' + e.message))
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