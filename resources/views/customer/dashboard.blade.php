@extends('layouts.customer')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Welcome -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Welcome back, {{ auth()->user()->first_name }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ now()->format('l, F j, Y') }}</p>
        </div>
        <a href="{{ route('booking.wizard') }}" class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition font-medium shadow-lg shadow-teal-200 dark:shadow-none">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Book Now
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Visits</p>
            <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $appointments->count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Completed</p>
            <p class="text-3xl font-bold text-teal-600 dark:text-teal-400 mt-1">{{ $appointments->where('status', 'completed')->count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Lifetime Spent</p>
            <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">₱{{ number_format($appointments->sum('total_price'), 0) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Upcoming</p>
            <p class="text-3xl font-bold text-orange-500 dark:text-orange-400 mt-1">{{ $appointments->whereIn('status', ['pending', 'confirmed'])->count() }}</p>
        </div>
    </div>

    <!-- Upcoming Appointment -->
    @if($latest && in_array($latest->status, ['pending', 'confirmed']))
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-700/30">
            <h2 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Next Appointment
            </h2>
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $latest->status === 'confirmed' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300' }}">
                {{ strtoupper($latest->status) }}
            </span>
        </div>
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-teal-50 dark:bg-teal-900/30 rounded-xl flex flex-col items-center justify-center border border-teal-100 dark:border-teal-800 shrink-0">
                        <span class="text-xs font-bold text-teal-600 dark:text-teal-400 uppercase">{{ \Carbon\Carbon::parse($latest->appointment_date)->format('M') }}</span>
                        <span class="text-xl font-bold text-teal-700 dark:text-teal-300">{{ \Carbon\Carbon::parse($latest->appointment_date)->format('d') }}</span>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white text-lg">{{ $latest->services->pluck('name')->join(', ') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ \Carbon\Carbon::parse($latest->start_time)->format('g:i A') }}
                        </p>
                    </div>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-2xl font-bold text-teal-600">₱{{ number_format($latest->total_price, 2) }}</p>
                </div>
            </div>
            @if($latest->status === 'pending')
                <div class="mt-4 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-100 dark:border-orange-800">
                    <p class="text-sm text-orange-700 dark:text-orange-300 flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        A receptionist will call you to confirm this booking.
                    </p>
                </div>
            @endif
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-8 text-center">
        <div class="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">No upcoming appointments</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-4">Schedule your next session to see it here.</p>
        <a href="{{ route('booking.wizard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition font-medium">
            Book Appointment
        </a>
    </div>
    @endif

    <!-- Appointment History -->
    <div id="history" class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-700/30">
            <h2 class="font-bold text-gray-800 dark:text-white">Appointment History</h2>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $appointments->count() }} records</span>
        </div>

        @if($appointments->isEmpty())
            <div class="text-center py-10">
                <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400 text-sm">No bookings yet.</p>
                <a href="{{ route('booking.wizard') }}" class="text-teal-600 hover:underline text-sm mt-1 inline-block">Book your first appointment &rarr;</a>
            </div>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($appointments as $appt)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition gap-3">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex flex-col items-center justify-center shrink-0">
                            <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase">{{ \Carbon\Carbon::parse($appt->appointment_date)->format('M') }}</span>
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($appt->appointment_date)->format('d') }}</span>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-200">{{ $appt->services->pluck('name')->join(', ') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($appt->start_time)->format('g:i A') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 sm:justify-end">
                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold
                            {{ $appt->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 
                               ($appt->status === 'cancelled' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' : 
                               ($appt->status === 'confirmed' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 
                               'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300')) }}">
                            {{ ucfirst($appt->status) }}
                        </span>
                        <span class="font-bold text-teal-600 text-sm w-16 text-right">₱{{ number_format($appt->total_price, 0) }}</span>
                        <a href="{{ route('booking.wizard', ['rebook_from' => $appt->id]) }}" 
                           class="px-3 py-1.5 bg-teal-600 text-white text-xs rounded hover:bg-teal-700 transition font-medium">
                            Book Again
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection