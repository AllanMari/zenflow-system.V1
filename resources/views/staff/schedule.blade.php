@extends('layouts.staff')

@section('title', 'My Schedule')

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-12">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                My Schedule
            </h1>
            <p class="mt-2 text-base text-gray-500 dark:text-gray-400">
                Weekly shifts & upcoming exceptions
            </p>
        </div>
        @if($templateHint)
        <span class="text-xs font-semibold px-3 py-1 rounded-full bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300">
            Based on template: {{ $templateHint }}
        </span>
        @endif
    </div>

    {{-- Weekly Grid --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                This Week
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
                    {{ $weekStart->format('M j') }} – {{ $weekStart->copy()->endOfWeek()->format('M j, Y') }}
                </span>
            </h2>
        </div>

        <form action="{{ route('staff.schedule.update') }}" method="POST">
            @csrf
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 p-5">
                @foreach($days as $num => $names)
                    @php
                        $sch = $schedules[$num] ?? null;
                        $isOff = $sch ? $sch->is_day_off : true;
                        $start = $sch ? substr($sch->start_time, 0, 5) : '09:00';
                        $end   = $sch ? substr($sch->end_time, 0, 5) : '18:00';
                    @endphp
                    <div class="rounded-xl border-2 p-4 text-center transition-all
                        {{ $isOff ? 'border-red-200 bg-red-50 dark:border-red-900/40 dark:bg-red-900/10' : 'border-teal-200 bg-teal-50 dark:border-teal-900/40 dark:bg-teal-900/10' }}"
                        id="day-card-{{ $num }}">

                        <div class="text-xs font-bold uppercase tracking-wider mb-3
                            {{ $isOff ? 'text-red-600 dark:text-red-400' : 'text-teal-700 dark:text-teal-300' }}">
                            {{ $names[1] }}
                        </div>

                        {{-- Toggle day off --}}
                        <label class="flex items-center justify-center gap-2 mb-3 cursor-pointer">
                            <input type="hidden" name="schedules[{{ $num }}][is_day_off]" value="0">
                            <input type="checkbox" name="schedules[{{ $num }}][is_day_off]" value="1"
                                {{ $isOff ? 'checked' : '' }}
                                onchange="toggleStaffDay({{ $num }})"
                                class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Day Off</span>
                        </label>

                        {{-- Time inputs (hidden when off) --}}
                        <div id="times-{{ $num }}" class="{{ $isOff ? 'hidden' : '' }} space-y-2">
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">Start</label>
                                <input type="time" name="schedules[{{ $num }}][start_time]" value="{{ $start }}"
                                    step="1800"
                                    class="w-full text-center text-sm font-bold bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-teal-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">End</label>
                                <input type="time" name="schedules[{{ $num }}][end_time]" value="{{ $end }}"
                                    step="1800"
                                    class="w-full text-center text-sm font-bold bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>

                        @if($isOff)
                            <div id="off-label-{{ $num }}" class="py-4">
                                <svg class="w-8 h-8 mx-auto text-red-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span class="text-sm font-bold text-red-500 dark:text-red-400">OFF</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="px-5 pb-5 flex justify-end">
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save My Schedule
                </button>
            </div>
        </form>
    </div>

    {{-- Upcoming Exceptions --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Upcoming Exceptions
        </h2>

        @if($exceptions->isEmpty())
            <div class="text-center py-8 text-gray-400 dark:text-gray-500">
                <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm">No upcoming exceptions</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($exceptions as $ex)
                <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700">
                    <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-300 flex items-center justify-center font-bold text-sm">
                        {{ $ex->exception_date->format('d') }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ $ex->exception_date->format('M d, Y') }}</span>
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold
                                {{ $ex->type === 'day_off' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 
                                   ($ex->type === 'holiday' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300') }}">
                                {{ ucfirst(str_replace('_', ' ', $ex->type)) }}
                            </span>
                        </div>
                        @if($ex->start_time && $ex->end_time)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ substr($ex->start_time, 0, 5) }} – {{ substr($ex->end_time, 0, 5) }}
                            </div>
                        @endif
                        @if($ex->reason)
                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 italic">{{ $ex->reason }}</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endif
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-4">
            Exceptions are set by admin or receptionist. Contact them to request changes.
        </p>
    </div>

</div>
@endsection

@push('scripts')
<script>
function toggleStaffDay(num) {
    const card = document.getElementById('day-card-' + num);
    const times = document.getElementById('times-' + num);
    const checkbox = card.querySelector('input[type="checkbox"]');
    const isOff = checkbox.checked;

    if (isOff) {
        card.classList.remove('border-teal-200', 'bg-teal-50', 'dark:border-teal-900/40', 'dark:bg-teal-900/10');
        card.classList.add('border-red-200', 'bg-red-50', 'dark:border-red-900/40', 'dark:bg-red-900/10');
        times.classList.add('hidden');
        // Add OFF label if not present
        if (!document.getElementById('off-label-' + num)) {
            const div = document.createElement('div');
            div.id = 'off-label-' + num;
            div.className = 'py-4';
            div.innerHTML = `<svg class="w-8 h-8 mx-auto text-red-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg><span class="text-sm font-bold text-red-500 dark:text-red-400">OFF</span>`;
            card.appendChild(div);
        }
    } else {
        card.classList.remove('border-red-200', 'bg-red-50', 'dark:border-red-900/40', 'dark:bg-red-900/10');
        card.classList.add('border-teal-200', 'bg-teal-50', 'dark:border-teal-900/40', 'dark:bg-teal-900/10');
        times.classList.remove('hidden');
        const offLabel = document.getElementById('off-label-' + num);
        if (offLabel) offLabel.remove();
    }
}
</script>
@endpush