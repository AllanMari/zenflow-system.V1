@extends(
    auth()->user()->roles->contains('name', 'admin')
        ? 'layouts.admin'
        : 'layouts.receptionist'
)

@section('title', 'Business Hours & Holidays')

@push('styles')
<style>
[x-cloak] { display: none !important; }
.time-picker-wrapper { position: relative; }
.time-picker-trigger { width: 100%; padding: 0.375rem 0.625rem; background: white; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.75rem; color: #111827; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: all 0.15s ease; }
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
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto space-y-6 pb-12" x-data="{ tab: 'hours' }">
    <div class="flex items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-4">
        <div class="flex items-center gap-2">
            <button type="button" @click="tab = 'hours'" :class="tab === 'hours' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'" class="px-4 py-2 rounded-xl text-xs font-bold transition">
                Weekly Hours
            </button>
            <button type="button" @click="tab = 'exceptions'" :class="tab === 'exceptions' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'" class="px-4 py-2 rounded-xl text-xs font-bold transition">
                Holidays & Exceptions
            </button>
            @if(auth()->user()->roles->contains('name', 'admin'))
                <button type="button" @click="tab = 'permissions'" :class="tab === 'permissions' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'" class="px-4 py-2 rounded-xl text-xs font-bold transition">
                    Permissions
                </button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-xs font-medium">
            {{ session('success') }}
        </div>
    @endif

    <!-- TAB 1: WEEKLY BUSINESS HOURS -->
    <div x-show="tab === 'hours'" class="space-y-6">
        <form method="POST" action="{{ route('business-hours.update') }}" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6 space-y-6">
            @csrf
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Regular Weekly Schedule</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Set the standard opening and closing time for each day using 30-minute intervals.</p>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800 border-y border-gray-100 dark:border-gray-800">
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
                <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                    Save Weekly Hours
                </button>
            </div>
        </form>
    </div>

    <!-- TAB 2: HOLIDAYS & EXCEPTIONS -->
    <div x-show="tab === 'exceptions'" class="space-y-6" x-cloak>
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6" x-data="{ excType: 'holiday', isClosed: true, excTimes: { open: '10:00', close: '17:00' } }">
            <div class="mb-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Add Exception / Holiday</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Override business hours for specific dates or holiday closures.</p>
            </div>

            <form method="POST" action="{{ route('business-exceptions.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Date</label>
                    <input type="date" name="date" required value="{{ date('Y-m-d') }}" class="w-full text-xs font-medium px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Event Name</label>
                    <input type="text" name="title" required placeholder="e.g. Independence Day, Renovation" class="w-full text-xs font-medium px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <select name="type" x-model="excType" @change="isClosed = (excType === 'holiday')" class="w-full text-xs font-medium px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="holiday">Holiday (Full Day Closure)</option>
                        <option value="event">Special Event (Closure or Custom Hours)</option>
                        <option value="custom_hours">Custom Operating Hours</option>
                    </select>
                </div>

                <div class="md:col-span-3 flex flex-wrap items-center gap-6 py-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_closed" value="1" x-model="isClosed" class="rounded border-gray-300 text-brand-600 w-4 h-4">
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">Entire Day Closed</span>
                    </label>

                    <div class="flex items-center gap-3" x-show="!isClosed">
                        <span class="text-xs text-gray-500">Special Open:</span>
                        <div x-data="timePicker(excTimes, 'open')" class="time-picker-wrapper w-32" @click.away="open = false">
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
                        <input type="hidden" name="open_time" x-model="excTimes.open">

                        <span class="text-xs text-gray-500">Special Close:</span>
                        <div x-data="timePicker(excTimes, 'close')" class="time-picker-wrapper w-32" @click.away="open = false">
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
                        <input type="hidden" name="close_time" x-model="excTimes.close">
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Customer Notice / Away Banner Message (Optional)</label>
                    <input type="text" name="notice_message" placeholder="e.g. We will be closed today for the holiday." class="w-full text-xs font-medium px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>

                <div class="md:col-span-3 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        Add / Update Exception
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Scheduled Exceptions & Holidays</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 font-semibold border-b border-gray-100 dark:border-gray-800">
                            <th class="p-3.5">Date</th>
                            <th class="p-3.5">Title</th>
                            <th class="p-3.5">Type</th>
                            <th class="p-3.5">Hours</th>
                            <th class="p-3.5">Notice Message</th>
                            <th class="p-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($exceptions as $exc)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                <td class="p-3.5 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($exc->date)->format('M d, Y') }}
                                </td>
                                <td class="p-3.5 font-medium text-gray-900 dark:text-white">
                                    {{ $exc->title }}
                                </td>
                                <td class="p-3.5">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $exc->type === 'holiday' ? 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800' : ($exc->type === 'custom_hours' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800') }}">
                                        {{ str_replace('_', ' ', $exc->type) }}
                                    </span>
                                </td>
                                <td class="p-3.5 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                    @if($exc->is_closed)
                                        <span class="text-red-500 font-semibold">Closed All Day</span>
                                    @else
                                        <span>
                                            {{ \Carbon\Carbon::parse($exc->open_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($exc->close_time)->format('g:i A') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3.5 max-w-xs truncate text-gray-500 dark:text-gray-400">
                                    {{ $exc->notice_message ?: '—' }}
                                </td>
                                <td class="p-3.5 text-right">
                                    <form id="delete-exception-{{ $exc->id }}" method="POST" action="{{ route('business-exceptions.destroy', $exc) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 text-xs font-semibold" onclick="swalConfirmAsync('Remove this exception?').then(confirmed => { if (confirmed) document.getElementById('delete-exception-{{ $exc->id }}').submit() })">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-400">No exceptions or holidays added yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($exceptions->hasPages())
                <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                    {{ $exceptions->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- TAB 3: RECEPTIONIST PERMISSIONS (Admin only) -->
    @if(auth()->user()->roles->contains('name', 'admin'))
        <div x-show="tab === 'permissions'" class="space-y-6" x-cloak>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Receptionist Business Hours Permissions</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Grant specific receptionists access to manage opening hours, holiday closures, and banner notices.</p>
                </div>

                <div class="mt-6 divide-y divide-gray-100 dark:divide-gray-800 border-y border-gray-100 dark:divide-gray-800">
                    @forelse($receptionists as $recep)
                        <div class="py-4 flex items-center justify-between gap-4">
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $recep->first_name }} {{ $recep->last_name }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ '@' . $recep->username }}</p>
                            </div>

                            <form method="POST" action="{{ route('admin.receptionist.toggle-business-hours', $recep) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $recep->can_manage_business_hours ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-100' }}">
                                    {{ $recep->can_manage_business_hours ? '✓ Access Granted' : '+ Grant Access' }}
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
<script>
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
                    
                    // Create temporary dropdown to measure actual dimensions
                    const tempDropdown = document.createElement('div');
                    tempDropdown.className = 'time-picker-dropdown';
                    tempDropdown.style.visibility = 'hidden';
                    tempDropdown.style.position = 'fixed';
                    tempDropdown.style.top = '-9999px';
                    
                    // Add search and scroll content to measure
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
                    
                    // Vertical placement
                    let top = rect.bottom + 4;
                    if (top + dropdownHeight > window.innerHeight - 12) {
                        top = rect.top - dropdownHeight - 4;
                    }
                    
                    // Horizontal placement
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