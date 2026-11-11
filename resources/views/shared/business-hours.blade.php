@extends(
    auth()->user()->roles->contains('name', 'admin')
        ? 'layouts.admin'
        : 'layouts.receptionist'
)

@section('title', 'Business Hours & Holidays')

@push('styles')
{{-- Flatpickr assets reused from ZenFlow shared views --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
[x-cloak] { display: none !important; }

/* Flatpickr ZenFlow Theme Styling (Dark Mode & Rounded Corners) */
.flatpickr-calendar {
    border-radius: 1rem !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
    border: 1px solid #e2e8f0 !important;
    overflow: hidden;
    font-family: inherit !important;
}
.dark .flatpickr-calendar {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5) !important;
}
.dark .flatpickr-months,
.dark .flatpickr-weekdays {
    background: #1e293b !important;
}
.dark .flatpickr-current-month,
.dark .flatpickr-weekday,
.dark .flatpickr-day {
    color: #f8fafc !important;
}
.dark .flatpickr-day:hover,
.dark .flatpickr-day:focus {
    background: #334155 !important;
}
.dark .flatpickr-day.prevMonthDay,
.dark .flatpickr-day.nextMonthDay {
    color: #64748b !important;
}
.flatpickr-day.selected,
.flatpickr-day.startRange,
.flatpickr-day.endRange {
    background: #0d9488 !important;
    border-color: #0d9488 !important;
    color: #ffffff !important;
}
.flatpickr-day.inRange {
    background: #ccfbf1 !important;
    border-color: #ccfbf1 !important;
    color: #0f766e !important;
}
.dark .flatpickr-day.inRange {
    background: #134e4a !important;
    border-color: #134e4a !important;
    color: #5eead4 !important;
}

/* Time Picker Dropdown & Triggers */
.time-picker-wrapper { position: relative; }
.time-picker-trigger { width: 100%; padding: 0.5rem 0.75rem; background: white; border: 1px solid #d1d5db; border-radius: 0.75rem; font-size: 0.75rem; color: #111827; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: all 0.15s ease; }
.dark .time-picker-trigger { background: #1e293b; border-color: #334155; color: #f8fafc; }
.time-picker-trigger:hover { border-color: #9ca3af; }
.time-picker-trigger:focus, .time-picker-trigger.open { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1); }
.dark .time-picker-trigger:focus, .dark .time-picker-trigger.open { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.15); }
.time-picker-dropdown { position: fixed; max-height: 280px; overflow: hidden; background: white; border: 1px solid #e2e8f0; border-radius: 0.75rem; z-index: 9999; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); min-width: 160px; display: flex; flex-direction: column; }
.dark .time-picker-dropdown { background: #1e293b; border-color: #334155; }
.time-picker-search { padding: 0.375rem 0.5rem; border-bottom: 1px solid #f1f5f9; background: #f8fafc; border-radius: 0.75rem 0.75rem 0 0; }
.dark .time-picker-search { background: #0f172a; border-color: #334155; }
.time-picker-search input { width: 100%; padding: 0.25rem 0.375rem; font-size: 0.75rem; background: white; border: 1px solid #cbd5e1; border-radius: 0.375rem; color: #111827; outline: none; }
.dark .time-picker-search input { background: #1e293b; border-color: #475569; color: #f8fafc; }
.time-picker-search input:focus { border-color: #0d9488; box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.1); }
.time-picker-scroll { overflow-y: auto; padding: 4px; max-height: 220px; }
.time-picker-scroll::-webkit-scrollbar { width: 4px; }
.time-picker-scroll::-webkit-scrollbar-track { background: transparent; }
.time-picker-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.dark .time-picker-scroll::-webkit-scrollbar-thumb { background: #475569; }
.time-picker-group-label { 
    padding: 0.375rem 0.75rem; 
    font-size: 0.65rem; 
    font-weight: 700; 
    text-transform: uppercase; 
    letter-spacing: 0.05em; 
    color: #9ca3af; 
    position: sticky; 
    top: 0; 
    background: white; 
    z-index: 10; 
    box-shadow: 0 1px 0 #e2e8f0;
}
.dark .time-picker-group-label { 
    color: #64748b; 
    background: #1e293b; 
    box-shadow: 0 1px 0 #334155;
}
.time-picker-option { padding: 0.375rem 0.5rem; cursor: pointer; font-size: 0.75rem; border-radius: 0.375rem; color: #374151; transition: all 0.1s ease; display: flex; align-items: center; justify-content: space-between; }
.dark .time-picker-option { color: #e2e8f0; }
.time-picker-option:hover { background: #f1f5f9; }
.dark .time-picker-option:hover { background: #334155; }
.time-picker-option.selected { background: #ccfbf1; color: #0f766e; font-weight: 600; }
.dark .time-picker-option.selected { background: #134e4a; color: #5eead4; }
.time-picker-option .check { width: 12px; height: 12px; opacity: 0; }
.time-picker-option.selected .check { opacity: 1; }

/* Mode Selection Cards */
.mode-card {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}
.mode-card:hover {
    transform: translateY(-2px);
}
</style>
@endpush

@php
    $validTabs = ['hours', 'exceptions', 'permissions'];
    $initialTab = request('tab');
    if (!in_array($initialTab, $validTabs, true)) {
        $initialTab = 'hours';
    }
@endphp

@section('content')
<script>
    // Set immediate initial tab synchronously before Alpine initializes to avoid tab flash
    (function() {
        var params = new URLSearchParams(window.location.search);
        var tabFromUrl = params.get('tab');
        var hash = window.location.hash;
        var valid = ['hours', 'exceptions', 'permissions'];
        if (tabFromUrl && valid.indexOf(tabFromUrl) !== -1) {
            window.__initialBusinessHoursTab = tabFromUrl;
        } else if (hash === '#scheduled-exceptions') {
            window.__initialBusinessHoursTab = 'exceptions';
        } else {
            window.__initialBusinessHoursTab = '{{ $initialTab }}';
        }
    })();
</script>

<div class="space-y-6 pb-12" x-data="{
    tab: window.__initialBusinessHoursTab || '{{ $initialTab }}',
    selectTab(newTab) {
        this.tab = newTab;
        var url = new URL(window.location.href);
        url.searchParams.set('tab', newTab);
        window.history.replaceState({ tab: newTab }, '', url.toString());
    }
}">
    <!-- Sub-navigation Tabs Bar -->
    <div class="flex items-center justify-between gap-4 border-b border-gray-200/80 dark:border-gray-800 pb-3">
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="selectTab('hours')"
                    :class="tab === 'hours'
                        ? 'bg-brand-600 text-white shadow-sm shadow-brand-500/20'
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                <span>Weekly Schedule</span>
            </button>
            <button type="button"
                    @click="selectTab('exceptions')"
                    :class="tab === 'exceptions'
                        ? 'bg-brand-600 text-white shadow-sm shadow-brand-500/20'
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                <i data-lucide="calendar-x-2" class="w-3.5 h-3.5"></i>
                <span>Holidays & Exceptions</span>
            </button>
            @if(auth()->user()->roles->contains('name', 'admin'))
                <button type="button"
                        @click="selectTab('permissions')"
                        :class="tab === 'permissions'
                            ? 'bg-brand-600 text-white shadow-sm shadow-brand-500/20'
                            : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    <span>Permissions</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-xs font-medium flex items-center gap-2.5 shadow-sm">
            <i data-lucide="check-circle" class="w-4 h-4 text-green-600 dark:text-green-400 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('warning'))
        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-medium flex items-start gap-2.5 shadow-sm">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"></i>
            <div>
                <span class="font-bold">Attention Required:</span> {{ session('warning') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-xs font-medium flex items-start gap-2.5 shadow-sm">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 dark:text-red-400 shrink-0 mt-0.5"></i>
            <div>
                <span class="font-bold">Please check the form:</span>
                <ul class="list-disc list-inside mt-1 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- ==================== TAB 1: WEEKLY BUSINESS HOURS ==================== -->
    <div x-show="tab === 'hours'" class="space-y-6" {{ $initialTab !== 'hours' ? 'x-cloak' : '' }}>
        <form method="POST" action="{{ route('business-hours.update') }}" class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/60 rounded-2xl shadow-sm p-6 space-y-6">
            @csrf
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Regular Weekly Schedule</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Set the standard opening and closing time for each day using 30-minute intervals.</p>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700/60 border-y border-gray-100 dark:border-gray-700/60">
                @php $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; @endphp
                @foreach($dayNames as $dayIndex => $dayName)
                    @php
                        $dayHour = $days[$dayIndex] ?? null;
                        $isClosed = $dayHour ? (bool)$dayHour->is_closed : false;
                        $openTime = $dayHour && $dayHour->open_time ? substr($dayHour->open_time, 0, 5) : '09:00';
                        $closeTime = $dayHour && $dayHour->close_time ? substr($dayHour->close_time, 0, 5) : '20:00';
                    @endphp
                    <div x-data="{ 
                        closed: {{ $isClosed ? 'true' : 'false' }}, 
                        timeModel: { open: '{{ $openTime }}', close: '{{ $closeTime }}' } 
                    }" class="py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="w-36 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" :class="closed ? 'bg-red-400' : 'bg-emerald-500'"></span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $dayName }}</span>
                        </div>

                        <div class="flex-1 flex flex-wrap items-center gap-4">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="hours[{{ $dayIndex }}][is_closed]" value="1" x-model="closed" class="rounded border-gray-300 text-brand-600 w-4 h-4">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Closed</span>
                            </label>

                            <div class="flex items-center gap-3" x-show="!closed">
                                <span class="text-xs text-gray-500">Open:</span>
                                <div x-data="timePicker(timeModel, 'open')" class="time-picker-wrapper w-32" @click.away="open = false">
                                    <button type="button" @click="toggle($el)" :class="open ? 'open' : ''" class="time-picker-trigger">
                                        <span x-text="formatTime(model.open, '9:00 AM')"></span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" x-cloak class="time-picker-dropdown" :style="`top:${dropdownTop}px;left:${dropdownLeft}px;width:${dropdownWidth}px`" @click.stop>
                                        <div class="time-picker-search">
                                            <input type="text" x-model="search" placeholder="Find time…" @keydown.stop>
                                        </div>
                                        <div class="time-picker-scroll">
                                            <template x-if="groupedOptions.am.length">
                                                <div>
                                                    <div class="time-picker-group-label">Morning</div>
                                                    <template x-for="opt in groupedOptions.am" :key="opt.value">
                                                        <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.open ? 'selected' : ''">
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
                                                        <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.open ? 'selected' : ''">
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
                                <input type="hidden" name="hours[{{ $dayIndex }}][open_time]" x-model="timeModel.open">

                                <span class="text-xs text-gray-500 ml-2">Close:</span>
                                <div x-data="timePicker(timeModel, 'close')" class="time-picker-wrapper w-32" @click.away="open = false">
                                    <button type="button" @click="toggle($el)" :class="open ? 'open' : ''" class="time-picker-trigger">
                                        <span x-text="formatTime(model.close, '8:00 PM')"></span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" x-cloak class="time-picker-dropdown" :style="`top:${dropdownTop}px;left:${dropdownLeft}px;width:${dropdownWidth}px`" @click.stop>
                                        <div class="time-picker-search">
                                            <input type="text" x-model="search" placeholder="Find time…" @keydown.stop>
                                        </div>
                                        <div class="time-picker-scroll">
                                            <template x-if="groupedOptions.am.length">
                                                <div>
                                                    <div class="time-picker-group-label">Morning</div>
                                                    <template x-for="opt in groupedOptions.am" :key="opt.value">
                                                        <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.close ? 'selected' : ''">
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
                                                        <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.close ? 'selected' : ''">
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
                                <input type="hidden" name="hours[{{ $dayIndex }}][close_time]" x-model="timeModel.close">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center gap-1.5">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Weekly Hours
                </button>
            </div>
        </form>
    </div>

    <!-- ==================== TAB 2: HOLIDAYS & EXCEPTIONS ==================== -->
    <div x-show="tab === 'exceptions'" class="space-y-6" {{ $initialTab !== 'exceptions' ? 'x-cloak' : '' }}
         x-data="exceptionManager()">

        <!-- 4-Tile Operational Metrics Ribbon (Above Mode Selection) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Metric 1: Upcoming Closures -->
            <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/60 rounded-2xl p-4 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Upcoming Closures</p>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $upcomingClosuresCount ?? 0 }}</h3>
                    <p class="text-[10px] text-rose-600 dark:text-rose-400 mt-0.5 font-medium">Spa closed all day</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center shrink-0">
                    <i data-lucide="calendar-off" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
                </div>
            </div>

            <!-- Metric 2: Adjusted-Hour Days -->
            <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/60 rounded-2xl p-4 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Adjusted Hours</p>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $upcomingCustomHoursCount ?? 0 }}</h3>
                    <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-0.5 font-medium">Temporary operating times</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                    <i data-lucide="clock-3" class="w-5 h-5 text-amber-600 dark:text-amber-400"></i>
                </div>
            </div>

            <!-- Metric 3: Upcoming Events -->
            <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/60 rounded-2xl p-4 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Upcoming Events</p>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $upcomingEventsCount ?? 0 }}</h3>
                    <p class="text-[10px] text-indigo-600 dark:text-indigo-400 mt-0.5 font-medium">Announcements & promos</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                    <i data-lucide="sparkles" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                </div>
            </div>

            <!-- Metric 4: Next Scheduled Change -->
            <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/60 rounded-2xl p-4 shadow-sm flex items-center justify-between">
                <div class="min-w-0 pr-2">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Next Change</p>
                    @if(!empty($nextScheduledChange))
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white mt-1 break-words" title="{{ $nextScheduledChange->title }}">
                            {{ \Carbon\Carbon::parse($nextScheduledChange->date)->format('M d') }} — {{ $nextScheduledChange->title }}
                        </h3>
                        <p class="text-[10px] text-teal-600 dark:text-teal-400 mt-0.5 font-medium capitalize">
                            {{ str_replace('_', ' ', $nextScheduledChange->type) }}
                        </p>
                    @else
                        <h3 class="text-sm font-bold text-gray-400 dark:text-gray-500 mt-1">None Scheduled</h3>
                        <p class="text-[10px] text-gray-400 mt-0.5">Regular hours active</p>
                    @endif
                </div>
                <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center shrink-0">
                    <i data-lucide="calendar" class="w-5 h-5 text-teal-600 dark:text-teal-400"></i>
                </div>
            </div>
        </div>

        <!-- 3-Card Distinct Mode Selector -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Select Exception Mode:
                </span>
                <span class="text-[11px] text-gray-400" x-text="'Active: ' + modeConfig[form.type].label"></span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- Mode A: Full-Day Holiday -->
                <div @click="setMode('holiday')"
                     :class="form.type === 'holiday'
                         ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/40 dark:bg-rose-950/20'
                         : 'border-gray-200/80 dark:border-gray-700/60 bg-white dark:bg-gray-800/80 hover:border-rose-300 dark:hover:border-rose-800'"
                     class="mode-card rounded-2xl border p-4 shadow-sm relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-900/40 flex items-center justify-center shrink-0">
                                <i data-lucide="calendar-off" class="w-4 h-4 text-rose-600 dark:text-rose-400"></i>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                CLOSED ALL DAY
                            </span>
                        </div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white mt-3">Full-Day Holiday</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                            The spa will be closed for the selected date(s). Existing affected appointments will be flagged for review.
                        </p>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-[11px] font-medium text-rose-600 dark:text-rose-400">
                        <span>Blocks online & staff booking</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5" x-show="form.type === 'holiday'"></i>
                    </div>
                </div>

                <!-- Mode B: Custom Operating Hours -->
                <div @click="setMode('custom_hours')"
                     :class="form.type === 'custom_hours'
                         ? 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/40 dark:bg-amber-950/20'
                         : 'border-gray-200/80 dark:border-gray-700/60 bg-white dark:bg-gray-800/80 hover:border-amber-300 dark:hover:border-amber-800'"
                     class="mode-card rounded-2xl border p-4 shadow-sm relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center shrink-0">
                                <i data-lucide="clock-3" class="w-4 h-4 text-amber-600 dark:text-amber-400"></i>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                ADJUSTED HOURS
                            </span>
                        </div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white mt-3">Custom Operating Hours</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                            Keep the spa open with temporary opening and closing times for the selected date(s).
                        </p>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-[11px] font-medium text-amber-600 dark:text-amber-400">
                        <span>Restricts slot availability</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5" x-show="form.type === 'custom_hours'"></i>
                    </div>
                </div>

                <!-- Mode C: Special Event -->
                <div @click="setMode('event')"
                     :class="form.type === 'event'
                         ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-50/40 dark:bg-indigo-950/20'
                         : 'border-gray-200/80 dark:border-gray-700/60 bg-white dark:bg-gray-800/80 hover:border-indigo-300 dark:hover:border-indigo-800'"
                     class="mode-card rounded-2xl border p-4 shadow-sm relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center shrink-0">
                                <i data-lucide="sparkles" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                ANNOUNCEMENT
                            </span>
                        </div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white mt-3">Special Event Notice</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                            Publish an event notice without changing normal operating hours or automatically closing bookings.
                        </p>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-[11px] font-medium text-indigo-600 dark:text-indigo-400">
                        <span>Normal booking hours remain</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5" x-show="form.type === 'event'"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Card (Structured in 4 Sections) -->
        <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/60 rounded-2xl shadow-sm p-6 space-y-6">
            <!-- Section A: Form Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700/60 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                         :class="{
                             'bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400': form.type === 'holiday',
                             'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400': form.type === 'custom_hours',
                             'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400': form.type === 'event'
                         }">
                        <i :data-lucide="modeConfig[form.type].icon" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white tracking-tight"
                            x-text="isEditing ? 'Edit: ' + modeConfig[form.type].header : modeConfig[form.type].header"></h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="modeConfig[form.type].description"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                          :class="modeConfig[form.type].badgeClass"
                          x-text="modeConfig[form.type].purpose"></span>

                    <template x-if="isEditing">
                        <button type="button" @click="cancelEdit()" class="px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition flex items-center gap-1">
                            <i data-lucide="x" class="w-3 h-3"></i>
                            Cancel Edit
                        </button>
                    </template>
                </div>
            </div>

            <form :action="formAction" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="_method" :value="isEditing ? 'PUT' : 'POST'">
                <input type="hidden" name="type" x-model="form.type">
                <input type="hidden" name="is_closed" :value="form.type === 'holiday' ? '1' : (form.is_closed ? '1' : '0')">

                <!-- Section B1: Event / Exception Details -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-400">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i>
                        <span>1. Exception Details</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                <span x-text="form.type === 'holiday' ? 'Holiday Name' : (form.type === 'custom_hours' ? 'Schedule Title' : 'Event Title')"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" x-model="form.title" required
                                   :placeholder="form.type === 'holiday' ? 'e.g. New Year\'s Day, Independence Day' : (form.type === 'custom_hours' ? 'e.g. Half-Day Renovation, Inventory Count' : 'e.g. Spa Anniversary Promotion')"
                                   class="w-full text-xs font-medium px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Customer Banner Notice (Optional)
                            </label>
                            <input type="text" name="notice_message" x-model="form.notice_message"
                                   placeholder="e.g. We will be closed today for the holiday."
                                   class="w-full text-xs font-medium px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">Displayed in website announcements and booking wizard banners.</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Internal Notes (Staff Only)
                            </label>
                            <input type="text" name="description" x-model="form.description"
                                   placeholder="e.g. Approved per management circular #12"
                                   class="w-full text-xs font-medium px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">For staff reference only. Not shown to customers.</p>
                        </div>
                    </div>
                </div>

                <!-- Section B2: Dates Section -->
                <div class="space-y-4 pt-2 border-t border-gray-100 dark:border-gray-700/60">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-400">
                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                        <span>2. Effective Date(s)</span>
                    </div>

                    <div class="max-w-md">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            <span x-text="isEditing ? 'Selected Date' : 'Single Date or Inclusive Date Range'"></span>
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" id="exceptionDateRangePicker" name="date_range" x-ref="dateInput" required
                                   :placeholder="isEditing ? 'YYYY-MM-DD' : 'Click to select date or range...'"
                                   class="w-full text-xs font-medium pl-9 pr-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
                            <i data-lucide="calendar" class="w-4 h-4 text-gray-400 absolute left-3 top-3 pointer-events-none"></i>
                        </div>
                        <input type="hidden" name="date" x-model="form.singleDate">
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1" x-show="!isEditing">
                            Click two dates to schedule a multi-day holiday. Existing appointments on every date in the range will be checked for conflicts.
                        </p>
                    </div>
                </div>

                <!-- Section B3: Operating Hours (Only for Custom Hours mode) -->
                <div x-show="form.type === 'custom_hours'" class="space-y-4 pt-2 border-t border-gray-100 dark:border-gray-700/60" x-transition>
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                        <span>3. Adjusted Operating Window</span>
                    </div>

                    <div class="p-4 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/40 dark:bg-amber-950/20 max-w-xl">
                        <div class="flex flex-wrap items-center gap-4">
                            <div>
                                <span class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Special Open Time:</span>
                                <div x-data="timePicker(form.times, 'open')" class="time-picker-wrapper w-36" @click.away="open = false">
                                    <button type="button" @click="toggle($el)" :class="open ? 'open' : ''" class="time-picker-trigger">
                                        <span x-text="formatTime(model.open, '10:00 AM')"></span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" x-cloak class="time-picker-dropdown" :style="`top:${dropdownTop}px;left:${dropdownLeft}px;width:${dropdownWidth}px`" @click.stop>
                                        <div class="time-picker-search">
                                            <input type="text" x-model="search" placeholder="Find time…" @keydown.stop>
                                        </div>
                                        <div class="time-picker-scroll">
                                            <template x-if="groupedOptions.am.length">
                                                <div>
                                                    <div class="time-picker-group-label">Morning</div>
                                                    <template x-for="opt in groupedOptions.am" :key="opt.value">
                                                        <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.open ? 'selected' : ''">
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
                                                        <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.open ? 'selected' : ''">
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
                                <input type="hidden" name="open_time" x-model="form.times.open">
                            </div>

                            <span class="text-gray-400 mt-5 hidden sm:inline">&rarr;</span>

                            <div>
                                <span class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Special Close Time:</span>
                                <div x-data="timePicker(form.times, 'close')" class="time-picker-wrapper w-36" @click.away="open = false">
                                    <button type="button" @click="toggle($el)" :class="open ? 'open' : ''" class="time-picker-trigger">
                                        <span x-text="formatTime(model.close, '5:00 PM')"></span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" x-cloak class="time-picker-dropdown" :style="`top:${dropdownTop}px;left:${dropdownLeft}px;width:${dropdownWidth}px`" @click.stop>
                                        <div class="time-picker-search">
                                            <input type="text" x-model="search" placeholder="Find time…" @keydown.stop>
                                        </div>
                                        <div class="time-picker-scroll">
                                            <template x-if="groupedOptions.am.length">
                                                <div>
                                                    <div class="time-picker-group-label">Morning</div>
                                                    <template x-for="opt in groupedOptions.am" :key="opt.value">
                                                        <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.close ? 'selected' : ''">
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
                                                        <div @click="select(opt.value)" class="time-picker-option" :class="opt.value === model.close ? 'selected' : ''">
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
                                <input type="hidden" name="close_time" x-model="form.times.close">
                            </div>
                        </div>

                        <div class="mt-3 text-xs font-semibold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                            <span>Schedule window: </span>
                            <span class="font-bold underline" x-text="formatTime(form.times.open) + ' → ' + formatTime(form.times.close)"></span>
                        </div>
                    </div>
                </div>

                <!-- Section C: Live Summary Preview Panel -->
                <div class="pt-2 border-t border-gray-100 dark:border-gray-700/60">
                    <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50/70 dark:bg-gray-800/40">
                        <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            <span>Action Preview</span>
                        </div>
                        <div class="text-xs space-y-1">
                            <div class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span x-text="form.title || 'Untitled Exception'"></span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                      :class="modeConfig[form.type].badgeClass"
                                      x-text="modeConfig[form.type].purpose"></span>
                            </div>
                            <div class="text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                                <i data-lucide="calendar" class="w-3 h-3 text-gray-400"></i>
                                <span x-text="selectedDateText || 'No date selected yet.'"></span>
                            </div>
                            <div class="text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                                <i data-lucide="clock" class="w-3 h-3 text-gray-400"></i>
                                <template x-if="form.type === 'holiday'">
                                    <span class="text-rose-600 dark:text-rose-400 font-semibold">Establishment will be strictly CLOSED all day.</span>
                                </template>
                                <template x-if="form.type === 'custom_hours'">
                                    <span class="text-amber-600 dark:text-amber-400 font-semibold"
                                          x-text="'Operating Window: ' + formatTime(form.times.open) + ' to ' + formatTime(form.times.close)"></span>
                                </template>
                                <template x-if="form.type === 'event'">
                                    <span class="text-indigo-600 dark:text-indigo-400 font-medium">Regular operating hours remain unchanged.</span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section D: Submit Controls -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" x-show="isEditing" @click="cancelEdit()" class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        Cancel Edit
                    </button>
                    <button type="submit"
                            :class="modeConfig[form.type].btnClass"
                            class="px-5 py-2.5 text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center gap-1.5">
                        <i :data-lucide="isEditing ? 'check' : 'plus'" class="w-4 h-4"></i>
                        <span x-text="isEditing ? 'Update Scheduled Exception' : modeConfig[form.type].btnLabel"></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Scheduled Exceptions Management Area -->
        <div id="scheduled-exceptions" class="scroll-mt-6 bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/60 rounded-2xl shadow-sm overflow-hidden" x-ref="exceptionsContainer">
            @include('shared._business_exceptions_table', [
                'exceptions' => $exceptions,
                'filter' => $filter,
                'typeFilter' => $typeFilter,
                'search' => $search
            ])
        </div>
    </div>

    <!-- ==================== TAB 3: RECEPTIONIST PERMISSIONS (Admin only) ==================== -->
    @if(auth()->user()->roles->contains('name', 'admin'))
        <div x-show="tab === 'permissions'" class="space-y-6" {{ $initialTab !== 'permissions' ? 'x-cloak' : '' }}>
            <div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/60 rounded-2xl shadow-sm p-6">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="user-check" class="w-5 h-5 text-brand-600 dark:text-brand-400"></i>
                        Receptionist Business Hours Permissions
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Grant specific receptionists access to manage opening hours, holiday closures, and banner notices.</p>
                </div>

                <div class="mt-6 divide-y divide-gray-100 dark:divide-gray-700/60 border-y border-gray-100 dark:divide-gray-700/60">
                    @forelse($receptionists as $recep)
                        <div class="py-4 flex items-center justify-between gap-4">
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $recep->first_name }} {{ $recep->last_name }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ '@' . $recep->username }}</p>
                            </div>

                            <form method="POST" action="{{ route('admin.receptionist.toggle-business-hours', $recep) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $recep->can_manage_business_hours ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-100' }}">
                                    @if($recep->can_manage_business_hours)
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        Access Granted
                                    @else
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        Grant Access
                                    @endif
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="py-6 text-sm text-center text-gray-400">No receptionist accounts found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
{{-- Flatpickr script reused from ZenFlow shared assets --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
});

function confirmDeleteException(id, dateStr, title) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete this scheduled exception?',
            html: `<div class="text-left text-xs space-y-1">
                      <p><strong>Target Date:</strong> ${dateStr}</p>
                      <p><strong>Title:</strong> ${title}</p>
                      <p class="text-rose-600 dark:text-rose-400 font-semibold mt-2">Note: This action removes the exception for ${dateStr} only and restores standard weekly operating hours for that day.</p>
                      <p class="text-gray-500 dark:text-gray-400">Existing appointments on this date will NOT be automatically cancelled or rescheduled.</p>
                   </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete This Date Exception',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#e11d48',
            customClass: {
                popup: 'rounded-2xl dark:bg-[#1e293b] dark:text-white',
                confirmButton: 'rounded-xl px-4 py-2 text-xs font-bold',
                cancelButton: 'rounded-xl px-4 py-2 text-xs font-semibold'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-exception-' + id).submit();
            }
        });
    } else {
        if (confirm(`Remove exception for ${dateStr} (${title}) only?`)) {
            document.getElementById('delete-exception-' + id).submit();
        }
    }
}

function exceptionManager() {
    return {
        isEditing: false,
        editingId: null,
        formAction: '{{ route("business-exceptions.store") }}',
        fpInstance: null,
        selectedDateText: '',
        // Asynchronous filter and search state
        selectedFilter: '{{ $filter ?? "all" }}',
        selectedType: '{{ $typeFilter ?? "all" }}',
        searchQuery: '{{ $search ?? "" }}',
        currentPage: {{ $exceptions->currentPage() }},
        isLoading: false,
        searchDebounceTimer: null,
        activeFetchController: null,
        modeConfig: {
            holiday: {
                label: 'Full-Day Holiday',
                purpose: 'CLOSED ALL DAY',
                header: 'Schedule a Full-Day Closure',
                description: 'The spa will be closed for the selected date(s). Existing affected appointments will be flagged for review.',
                icon: 'calendar-off',
                badgeClass: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800',
                btnClass: 'bg-rose-600 hover:bg-rose-700 shadow-rose-500/20',
                btnLabel: 'Schedule Closure'
            },
            custom_hours: {
                label: 'Custom Operating Hours',
                purpose: 'ADJUSTED HOURS',
                header: 'Set Temporary Operating Hours',
                description: 'Keep the spa open with temporary opening and closing times for the selected date(s).',
                icon: 'clock-3',
                badgeClass: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
                btnClass: 'bg-amber-600 hover:bg-amber-700 shadow-amber-500/20',
                btnLabel: 'Save Operating Hours'
            },
            event: {
                label: 'Special Event Notice',
                purpose: 'ANNOUNCEMENT',
                header: 'Create a Special Event Notice',
                description: 'Publish an event notice without changing normal operating hours or automatically closing bookings.',
                icon: 'sparkles',
                badgeClass: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800',
                btnClass: 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-500/20',
                btnLabel: 'Publish Event Notice'
            }
        },
        form: {
            title: '{{ old("title", "") }}',
            type: '{{ old("type", "holiday") }}',
            is_closed: {{ old("is_closed", "1") ? "true" : "false" }},
            notice_message: '{{ old("notice_message", "") }}',
            description: '{{ old("description", "") }}',
            singleDate: '{{ old("date", "") }}',
            times: {
                open: '{{ old("open_time", "10:00") }}',
                close: '{{ old("close_time", "17:00") }}'
            }
        },

        init() {
            this.$nextTick(() => {
                this.initFlatpickr();
            });
        },

        initFlatpickr() {
            const input = this.$refs.dateInput;
            if (!input || typeof flatpickr === 'undefined') return;

            if (this.fpInstance) {
                this.fpInstance.destroy();
            }

            const self = this;
            this.fpInstance = flatpickr(input, {
                mode: self.isEditing ? 'single' : 'range',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'M j, Y',
                defaultDate: self.form.singleDate ? [self.form.singleDate] : [],
                allowInput: false,
                onChange(selectedDates, dateStr) {
                    if (selectedDates.length === 0) {
                        self.selectedDateText = '';
                        self.form.singleDate = '';
                    } else if (selectedDates.length === 1) {
                        self.selectedDateText = self.fpInstance.formatDate(selectedDates[0], 'M j, Y');
                        self.form.singleDate = self.fpInstance.formatDate(selectedDates[0], 'Y-m-d');
                    } else if (selectedDates.length === 2) {
                        self.selectedDateText = self.fpInstance.formatDate(selectedDates[0], 'M j, Y') + ' to ' + self.fpInstance.formatDate(selectedDates[1], 'M j, Y');
                        self.form.singleDate = self.fpInstance.formatDate(selectedDates[0], 'Y-m-d');
                    }
                }
            });
        },

        setMode(mode) {
            this.form.type = mode;
            if (mode === 'holiday') {
                this.form.is_closed = true;
            } else if (mode === 'custom_hours') {
                this.form.is_closed = false;
            } else if (mode === 'event') {
                this.form.is_closed = false;
            }
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        },

        startEdit(exc) {
            this.isEditing = true;
            this.editingId = exc.id;
            this.formAction = '/business-exceptions/' + exc.id;
            this.form.title = exc.title;
            this.form.type = exc.type;
            this.form.is_closed = !!exc.is_closed;
            this.form.notice_message = exc.notice_message || '';
            this.form.description = exc.description || '';
            this.form.singleDate = exc.date;
            this.form.times.open = exc.open_time || '10:00';
            this.form.times.close = exc.close_time || '17:00';

            this.initFlatpickr();
            if (this.fpInstance) {
                this.fpInstance.setDate(exc.date, true);
            }
            this.selectedDateText = exc.date;

            // Scroll to the management form
            window.scrollTo({ top: 0, behavior: 'smooth' });
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        },

        cancelEdit() {
            this.isEditing = false;
            this.editingId = null;
            this.formAction = '{{ route("business-exceptions.store") }}';
            this.form.title = '';
            this.form.type = 'holiday';
            this.form.is_closed = true;
            this.form.notice_message = '';
            this.form.description = '';
            this.form.singleDate = '';
            this.form.times.open = '10:00';
            this.form.times.close = '17:00';
            this.selectedDateText = '';

            this.initFlatpickr();
            if (this.fpInstance) {
                this.fpInstance.clear();
            }
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        },

        setFilter(filter) {
            if (this.selectedFilter === filter) return;
            this.selectedFilter = filter;
            this.fetchExceptions(1);
        },

        onSearchInput() {
            clearTimeout(this.searchDebounceTimer);
            this.searchDebounceTimer = setTimeout(() => {
                this.fetchExceptions(1);
            }, 300);
        },

        clearSearch() {
            this.searchQuery = '';
            this.fetchExceptions(1);
        },

        async fetchExceptions(page = 1) {
            this.currentPage = page;

            // Abort previous in-flight request if any to prevent race conditions
            if (this.activeFetchController) {
                this.activeFetchController.abort();
            }
            this.activeFetchController = new AbortController();

            const params = new URLSearchParams({
                tab: 'exceptions',
                filter: this.selectedFilter,
                type_filter: this.selectedType,
                page: page
            });

            if (this.searchQuery.trim() !== '') {
                params.set('search', this.searchQuery.trim());
            }

            // Sync URL with History API without page reload
            const newUrl = `${window.location.pathname}?${params.toString()}`;
            window.history.replaceState({ path: newUrl }, '', newUrl);

            this.isLoading = true;

            try {
                // Request fragment only via header / query parameter
                const fetchUrl = `${window.location.pathname}?${params.toString()}&fragment=exceptions`;
                const response = await fetch(fetchUrl, {
                    signal: this.activeFetchController.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error ${response.status}`);
                }

                const html = await response.text();
                const container = this.$refs.exceptionsContainer;
                if (container) {
                    container.innerHTML = html;
                    if (window.lucide && typeof window.lucide.createIcons === 'function') {
                        window.lucide.createIcons();
                    }
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('Failed to filter exceptions:', err);
                }
            } finally {
                this.isLoading = false;
            }
        }
    };
}

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
                    const val = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                    const ampm = h >= 12 ? 'PM' : 'AM';
                    const h12 = h % 12 || 12;
                    opts.push({ value: val, label: `${h12}:${String(m).padStart(2, '0')} ${ampm}`, amPm: ampm });
                }
            }
            return opts;
        })(),
        get filteredOptions() {
            if (!this.search) { return this.timeOptions; }
            const q = this.search.toLowerCase().replace(/\s/g, '');
            return this.timeOptions.filter(o => o.label.toLowerCase().replace(/\s/g, '').includes(q) || o.value.includes(q));
        },
        get groupedOptions() {
            const opts = this.filteredOptions;
            return {
                am: opts.filter(o => o.amPm === 'AM'),
                pm: opts.filter(o => o.amPm === 'PM')
            };
        },
        toggle($el) {
            this.open = !this.open;
            this.search = '';
            if (this.open) {
                this.$nextTick(() => {
                    const rect = $el.getBoundingClientRect();
                    
                    const tempDropdown = document.createElement('div');
                    tempDropdown.className = 'time-picker-dropdown';
                    tempDropdown.style.visibility = 'hidden';
                    tempDropdown.style.position = 'fixed';
                    tempDropdown.style.top = '-9999px';
                    
                    tempDropdown.innerHTML = `
                        <div class="time-picker-search"><input type="text" placeholder="Find time…"></div>
                        <div class="time-picker-scroll">
                            ${this.groupedOptions.am.length ? '<div><div class="time-picker-group-label">Morning</div>' + this.groupedOptions.am.map(() => '<div class="time-picker-option"><span>12:00 AM</span></div>').join('') + '</div>' : ''}
                            ${this.groupedOptions.pm.length ? '<div><div class="time-picker-group-label">Afternoon / Evening</div>' + this.groupedOptions.pm.map(() => '<div class="time-picker-option"><span>12:00 PM</span></div>').join('') + '</div>' : ''}
                        </div>
                    `;
                    document.body.appendChild(tempDropdown);
                    
                    const dropdownHeight = tempDropdown.scrollHeight;
                    const dropdownWidth = Math.max(tempDropdown.scrollWidth, 160);
                    document.body.removeChild(tempDropdown);
                    
                    let top = rect.bottom + 4;
                    if (top + dropdownHeight > window.innerHeight - 12) {
                        top = rect.top - dropdownHeight - 4;
                    }
                    
                    let left = rect.left;
                    if (left + dropdownWidth > window.innerWidth - 12) {
                        left = window.innerWidth - dropdownWidth - 12;
                    }
                    
                    this.dropdownTop = top;
                    this.dropdownLeft = left;
                    this.dropdownWidth = dropdownWidth;
                });
            }
        },
        select(t) {
            this.model[this.property] = t;
            this.open = false;
        },
        close() {
            this.open = false;
        }
    };
}

function formatTime(t, fallback = '') {
    if (!t) { return fallback; }
    const parts = String(t).split(':');
    const h = parseInt(parts[0], 10);
    const m = parseInt(parts[1], 10);
    if (Number.isNaN(h) || Number.isNaN(m)) { return fallback; }
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${String(m).padStart(2, '0')} ${ampm}`;
}
</script>
@endpush
