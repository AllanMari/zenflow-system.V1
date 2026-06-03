@extends('layouts.staff')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Today's Appointments --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-teal-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wide">Today's Bookings</p>
            <p class="text-2xl font-bold text-teal-600 dark:text-teal-400 mt-1">{{ $myToday->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $myToday->where('status', 'completed')->count() }} done</p>
        </div>

        {{-- Today's Revenue --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-green-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wide">Today's Revenue</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">₱{{ number_format($todayRevenue, 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">From completed</p>
        </div>

        {{-- Weekly Completed --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wide">This Week</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $weeklyCompleted }}</p>
            <p class="text-xs text-gray-400 mt-1">Completed</p>
        </div>

        {{-- Weekly Revenue --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-purple-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wide">Weekly Revenue</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">₱{{ number_format($weeklyRevenue, 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Mon–Sun</p>
        </div>
    </div>

    {{-- Today's Schedule --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
        <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">Today's Schedule</h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ now()->format('l, F j, Y') }}</span>
        </div>

        @if($myToday->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">No appointments today.</p>
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($myToday as $appointment)
                <div class="p-5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        {{-- Time & Status --}}
                        <div class="flex items-center gap-3">
                            <div class="text-center min-w-[70px]">
                                <p class="text-xl font-bold text-teal-600 dark:text-teal-400">
                                    {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i') }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($appointment->start_time)->format('A') }}
                                </p>
                            </div>
                            <div class="h-10 w-px bg-gray-200 dark:bg-gray-600 hidden sm:block"></div>
                            <div>
                                <div class="flex items-center gap-2 mb-0.5">
                                    @if($appointment->status === 'completed')
                                        <span class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs px-2 py-0.5 rounded-full font-medium">Completed</span>
                                    @elseif($appointment->status === 'confirmed')
                                        <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs px-2 py-0.5 rounded-full font-medium">Confirmed</span>
                                    @elseif($appointment->status === 'pending')
                                        <span class="bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 text-xs px-2 py-0.5 rounded-full font-medium">Pending</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $appointment->services->sum('duration_minutes') }} mins
                                </p>
                            </div>
                        </div>

                        {{-- Customer --}}
                        <div class="flex-1 lg:text-center">
                            <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm">{{ $appointment->customer->full_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $appointment->customer->phone_number }}</p>
                        </div>

                        {{-- Services --}}
                        <div class="flex flex-wrap gap-1.5 lg:justify-end">
                            @foreach($appointment->services as $service)
                                <span class="bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300 text-xs px-2.5 py-1 rounded-full border border-teal-200 dark:border-teal-800">
                                    {{ $service->name }}
                                </span>
                            @endforeach
                        </div>

                        {{-- Medical Notes --}}
                        @if($appointment->customer && $appointment->customer->medical_notes)
                            <button onclick="document.getElementById('notes-{{ $appointment->id }}').classList.toggle('hidden')"
                                    class="text-xs text-rose-600 dark:text-rose-400 hover:underline flex items-center gap-1 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                Notes
                            </button>
                        @endif
                    </div>

                    {{-- Expandable Medical Notes --}}
                    @if($appointment->customer && $appointment->customer->medical_notes)
                    <div id="notes-{{ $appointment->id }}" class="hidden mt-3 p-3 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-lg">
                        <p class="text-sm text-rose-800 dark:text-rose-200">
                            <span class="font-semibold">Medical:</span> {{ $appointment->customer->medical_notes }}
                        </p>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Upcoming Preview --}}
    @if($myUpcoming->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
        <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">Upcoming</h2>
            <a href="{{ route('staff.appointments') }}" class="text-sm text-teal-600 dark:text-teal-400 hover:underline">View all &rarr;</a>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($myUpcoming->take(5) as $appointment)
            <div class="p-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                <div class="text-center min-w-[60px]">
                    <p class="text-lg font-bold text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M j') }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}</p>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800 dark:text-gray-200 text-sm truncate">{{ $appointment->customer->full_name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $appointment->services->pluck('name')->join(', ') }}</p>
                </div>
                @if($appointment->customer && $appointment->customer->medical_notes)
                    <span class="text-xs text-rose-500 dark:text-rose-400 flex items-center gap-1 shrink-0" title="Has medical notes">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection