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
            <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">&#8369;{{ number_format($appointments->sum('total_price'), 0) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Upcoming</p>
            <p class="text-3xl font-bold text-orange-500 dark:text-orange-400 mt-1">{{ $appointments->whereIn('status', ['pending', 'confirmed'])->count() }}</p>
        </div>
    </div>
    <!-- My Booking Navigation Banner -->
    <div class="bg-gradient-to-r from-teal-600 to-teal-800 rounded-2xl shadow-lg p-6 text-white flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full text-xs font-semibold uppercase tracking-wider">
                <svg class="w-4 h-4 text-teal-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Appointment Status & Management
            </div>
            <h2 class="text-xl font-bold">Manage Your Current & Upcoming Bookings</h2>
            <p class="text-teal-100 text-sm max-w-xl">
                View detailed appointment status, timelines, service breakdowns, assigned therapists, payment summaries, and manage cancellations in one dedicated place.
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('customer.bookings') }}" class="px-6 py-3 bg-white text-teal-800 hover:bg-teal-50 transition rounded-xl font-bold shadow text-sm flex items-center gap-2">
                View My Booking
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-100 dark:border-gray-700 space-y-3">
            <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Need to Book a New Session?
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Explore our relaxing spa services, choose your preferred therapist, select a convenient time slot, and book instantly.
            </p>
            <div>
                <a href="{{ route('booking.wizard') }}" class="inline-flex items-center gap-2 text-teal-600 dark:text-teal-400 font-semibold text-sm hover:underline">
                    Book Appointment Now &rarr;
                </a>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-100 dark:border-gray-700 space-y-3">
            <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Update Profile & Nickname
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Manage your profile details, contact number, and booking nickname.
            </p>
            <div>
                <a href="{{ route('customer.profile') }}" class="inline-flex items-center gap-2 text-teal-600 dark:text-teal-400 font-semibold text-sm hover:underline">
                    Edit Profile &rarr;
                </a>
            </div>
        </div>
    </div>

</div>
@endsection