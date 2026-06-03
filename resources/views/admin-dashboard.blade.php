@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="bg-white dark:bg-gray-800 rounded shadow p-6 transition-colors duration-300">
    <h1 class="text-3xl font-bold text-teal-600 dark:text-teal-400">Admin Dashboard</h1>
    <p class="mt-2 text-gray-600 dark:text-gray-300">Welcome, {{ auth()->user()->first_name }}! You have full access.</p>

    {{-- KPI STRIP --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6">
        <div class="p-4 bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 rounded shadow">
            <h2 class="font-semibold text-teal-800 dark:text-teal-300 text-sm">Today's Revenue</h2>
            <p class="text-2xl font-bold text-teal-600 dark:text-teal-400 mt-1">₱{{ number_format($todayRevenue, 2) }}</p>
        </div>

        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded shadow">
            <h2 class="font-semibold text-blue-800 dark:text-blue-300 text-sm">Appointments</h2>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $todayAppointments }}</p>
            <p class="text-xs text-blue-500 dark:text-blue-400 mt-0.5">{{ $completedToday }} completed</p>
        </div>

        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded shadow">
            <h2 class="font-semibold text-indigo-800 dark:text-indigo-300 text-sm">Staff on Duty</h2>
            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $staffOnDuty }}</p>
        </div>

        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded shadow">
            <h2 class="font-semibold text-amber-800 dark:text-amber-300 text-sm">Deposits Held</h2>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">₱{{ number_format($depositsHeld, 2) }}</p>
        </div>

        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded shadow">
            <h2 class="font-semibold text-red-800 dark:text-red-300 text-sm">No Shows</h2>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $todayNoShows }}</p>
        </div>
    </div>

    {{-- MODULE CARDS --}}
    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mt-8 mb-4">Business Operations</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

        <a href="{{ route('admin.sales') }}" class="p-4 bg-teal-50 dark:bg-gray-700/50 border border-transparent dark:border-gray-600 rounded shadow hover:bg-teal-100 dark:hover:bg-gray-600 transition cursor-pointer block">
            <h2 class="font-semibold text-teal-800 dark:text-teal-300">Sales & Reports</h2>
            <p class="text-sm text-gray-700 dark:text-gray-400 mt-1">Revenue analytics, transaction logs, print reports.</p>
        </a>

        <a href="{{ route('admin.schedules') }}" class="p-4 bg-blue-50 dark:bg-gray-700/50 border border-transparent dark:border-gray-600 rounded shadow hover:bg-blue-100 dark:hover:bg-gray-600 transition cursor-pointer block">
            <h2 class="font-semibold text-blue-800 dark:text-blue-300">Appointments</h2>
            <p class="text-sm text-gray-700 dark:text-gray-400 mt-1">Booking calendar and schedule management.</p>
        </a>

        <a href="{{ route('admin.services.index') }}" class="p-4 bg-purple-50 dark:bg-gray-700/50 border border-transparent dark:border-gray-600 rounded shadow hover:bg-purple-100 dark:hover:bg-gray-600 transition cursor-pointer block">
            <h2 class="font-semibold text-purple-800 dark:text-purple-300">Services</h2>
            <p class="text-sm text-gray-700 dark:text-gray-400 mt-1">Catalog, categories, packages, pricing.</p>
        </a>

        <a href="{{ route('admin.schedules') }}" class="p-4 bg-indigo-50 dark:bg-gray-700/50 border border-transparent dark:border-gray-600 rounded shadow hover:bg-indigo-100 dark:hover:bg-gray-600 transition cursor-pointer block">
            <h2 class="font-semibold text-indigo-800 dark:text-indigo-300">Staff & Schedules</h2>
            <p class="text-sm text-gray-700 dark:text-gray-400 mt-1">Shifts, exceptions, permissions.</p>
        </a>

        <a href="{{ route('admin.rooms.index') }}" class="p-4 bg-amber-50 dark:bg-gray-700/50 border border-transparent dark:border-gray-600 rounded shadow hover:bg-amber-100 dark:hover:bg-gray-600 transition cursor-pointer block">
            <h2 class="font-semibold text-amber-800 dark:text-amber-300">Rooms</h2>
            <p class="text-sm text-gray-700 dark:text-gray-400 mt-1">Treatment rooms and availability.</p>
        </a>

        <a href="{{ route('admin.users.index') }}" class="p-4 bg-emerald-50 dark:bg-gray-700/50 border border-transparent dark:border-gray-600 rounded shadow hover:bg-emerald-100 dark:hover:bg-gray-600 transition cursor-pointer block">
            <h2 class="font-semibold text-emerald-800 dark:text-emerald-300">Users</h2>
            <p class="text-sm text-gray-700 dark:text-gray-400 mt-1">Accounts, roles, permissions.</p>
        </a>

        <a href="{{ route('admin.landing.editor') }}" class="p-4 bg-pink-50 dark:bg-gray-700/50 border border-transparent dark:border-gray-600 rounded shadow hover:bg-pink-100 dark:hover:bg-gray-600 transition cursor-pointer block">
            <h2 class="font-semibold text-pink-800 dark:text-pink-300">Landing Page</h2>
            <p class="text-sm text-gray-700 dark:text-gray-400 mt-1">Website editor and showcase.</p>
        </a>

        <div onclick="openSettingsModal()" class="p-4 bg-gray-50 dark:bg-gray-700/50 border border-transparent dark:border-gray-600 rounded shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition cursor-pointer">
            <h2 class="font-semibold text-gray-800 dark:text-gray-300">System Settings</h2>
            <p class="text-sm text-gray-700 dark:text-gray-400 mt-1">Night mode and profile settings.</p>
        </div>
    </div>
</div>
@endsection