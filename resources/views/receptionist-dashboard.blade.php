@extends('layouts.receptionist')

@section('title', 'Dashboard')

@section('content')
<div class="bg-white dark:bg-gray-800 rounded shadow p-6 transition-colors duration-300">
    <h1 class="text-3xl font-bold text-teal-600 dark:text-teal-400">Receptionist Dashboard</h1>
    <p class="mt-2 text-gray-600 dark:text-gray-300">Welcome, {{ auth()->user()->first_name }}!</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded shadow">
            <h2 class="font-semibold text-orange-800 dark:text-orange-300">Pending</h2>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ $stats['pending'] ?? 0 }}</p>
        </div>

        <div class="p-4 bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 rounded shadow">
            <h2 class="font-semibold text-teal-800 dark:text-teal-300">Today's Appointments</h2>
            <p class="text-2xl font-bold text-teal-600 dark:text-teal-400 mt-1">{{ $stats['today'] ?? 0 }}</p>
        </div>

        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded shadow">
            <h2 class="font-semibold text-blue-800 dark:text-blue-300">Today's Sales</h2>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">₱{{ number_format($stats['sales'] ?? 0, 2) }}</p>
        </div>
    </div>

    <div class="mt-8">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Pending Confirmations</h3>
        @if(isset($pending) && $pending->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        <th class="p-3 font-bold">Customer</th>
                        <th class="p-3 font-bold">Date/Time</th>
                        <th class="p-3 font-bold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pending as $appointment)
                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="p-3 dark:text-gray-300 font-medium">
                            {{ $appointment->customer->first_name }} {{ $appointment->customer->last_name }}
                        </td>
                        <td class="p-3 dark:text-gray-300">
                            {{ $appointment->appointment_date }} | {{ $appointment->start_time }}
                        </td>
                        <td class="p-3">
                            <a href="{{ route('receptionist.booking', $appointment->id) }}" class="text-teal-600 dark:text-teal-400 font-bold hover:underline">
                                Process
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-gray-500 italic py-4">No pending bookings at the moment.</p>
        @endif
    </div>
</div>
@endsection