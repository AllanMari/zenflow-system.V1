@extends('layouts.customer')

@section('title', 'Dashboard')

@section('content')
@php
    $timezone = config('app.timezone', 'Asia/Manila');
    $now = \Carbon\Carbon::now($timezone);
    $hour = (int) $now->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $nextAppointment = $upcomingAppointments->first();
    $isFirstVisit = $upcomingCount === 0 && $completedCount === 0;
    $isHappeningNow = false;

    if ($nextAppointment && $nextAppointment->appointment_date->toDateString() === $now->toDateString()) {
        $isHappeningNow = $nextAppointment->start_time <= $now->format('H:i:s')
            && $nextAppointment->end_time > $now->format('H:i:s');
    }

    $nextServices = $nextAppointment
        ? ($nextAppointment->services->pluck('name')->filter()->join(', ') ?: 'Appointment')
        : null;
@endphp

<div class="mx-auto max-w-6xl space-y-6">

    {{-- Welcome hero --}}
    <section class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm dark:border-gray-700/50 dark:bg-gray-800/80">
        <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-brand-200/60 bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 dark:border-brand-800/40 dark:bg-brand-900/30 dark:text-brand-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                    Your wellness calendar
                </span>
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    {{ $greeting }}, {{ auth()->user()->first_name }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $now->format('l, F j, Y') }}
                    <span class="text-gray-300 dark:text-gray-600">&mdash;</span>
                    @if($upcomingCount > 0)
                        <span class="font-medium text-gray-700 dark:text-gray-300">
                            {{ $upcomingCount }} upcoming {{ \Illuminate\Support\Str::plural('visit', $upcomingCount) }}
                        </span>
                    @else
                        <span class="font-medium text-gray-700 dark:text-gray-300">No visits on the calendar yet</span>
                    @endif
                </p>
            </div>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <a href="{{ route('booking.wizard') }}"
                   class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-500/20 transition hover:bg-brand-700 sm:w-auto">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Book Now
                </a>
                <a href="{{ route('customer.bookings') }}"
                   class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700/60 sm:w-auto">
                    View bookings
                </a>
            </div>
        </div>
    </section>

    {{-- Next appointment --}}
    <section aria-labelledby="appointment-summary-heading">
        @if($nextAppointment)
            <div class="overflow-hidden rounded-2xl border {{ $isHappeningNow ? 'border-brand-500 bg-brand-50/50 dark:border-brand-500 dark:bg-brand-950/30' : 'border-gray-200/80 bg-white dark:border-gray-700/50 dark:bg-gray-800/80' }} shadow-sm">
                <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-700/50 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 id="appointment-summary-heading" class="text-base font-bold text-gray-900 dark:text-white">Next appointment</h2>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Your nearest pending or confirmed visit.</p>
                    </div>
                    <a href="{{ route('customer.bookings') }}"
                       class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-300">
                        Manage this booking
                        <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                    </a>
                </div>

                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-stretch sm:gap-5 sm:p-6">
                    <div class="flex shrink-0 items-center gap-4 sm:flex-col sm:items-center sm:justify-center sm:gap-0 {{ $isHappeningNow ? 'rounded-2xl bg-brand-600 px-5 py-4 text-white sm:min-w-[7.5rem]' : 'rounded-2xl border border-brand-200/70 bg-brand-50 px-5 py-4 text-brand-800 dark:border-brand-800/40 dark:bg-brand-900/30 dark:text-brand-200 sm:min-w-[7.5rem]' }}">
                        <p class="text-[11px] font-semibold uppercase tracking-wider {{ $isHappeningNow ? 'text-white/80' : 'text-brand-600 dark:text-brand-400' }}">
                            {{ $nextAppointment->appointment_date->format('D') }}
                        </p>
                        <p class="text-3xl font-bold tabular-nums tracking-tight sm:mt-0.5">
                            {{ $nextAppointment->appointment_date->format('j') }}
                        </p>
                        <p class="text-xs font-medium {{ $isHappeningNow ? 'text-white/80' : 'text-brand-700 dark:text-brand-300' }}">
                            {{ $nextAppointment->appointment_date->format('M Y') }}
                        </p>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            @if($isHappeningNow)
                                <span class="inline-flex items-center rounded-full bg-brand-600 px-2.5 py-0.5 text-xs font-bold text-white">Happening now</span>
                            @elseif($nextAppointment->status === 'pending')
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Awaiting confirmation</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-semibold text-teal-800 dark:bg-teal-900/40 dark:text-teal-200">Confirmed</span>
                            @endif
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $nextAppointment->appointment_date->format('l, F j, Y') }}
                            </span>
                        </div>

                        <h3 class="mt-2 text-lg font-bold tracking-tight text-gray-900 dark:text-white">
                            {{ $nextServices }}
                        </h3>

                        <p class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 dark:text-gray-300">
                            <i data-lucide="clock" class="h-4 w-4 text-brand-600 dark:text-brand-400"></i>
                            {{ $nextAppointment->time_range }}
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-5 py-10 text-center shadow-sm dark:border-gray-700/60 dark:bg-gray-800/80 sm:px-8">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                    <i data-lucide="calendar" class="h-7 w-7"></i>
                </div>
                <h2 id="appointment-summary-heading" class="mt-4 text-base font-bold text-gray-900 dark:text-white">No upcoming appointments</h2>
                @if($isFirstVisit)
                    <p class="mx-auto mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">Your first session starts with a booking.</p>
                @else
                    <p class="mx-auto mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">No visits on the calendar. Book when you are ready.</p>
                @endif
                <a href="{{ route('booking.wizard') }}"
                   class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-500/20 transition hover:bg-brand-700">
                    Book an appointment
                </a>
            </div>
        @endif
    </section>

    {{-- Activity statistics --}}
    <section aria-label="Appointment activity" class="grid grid-cols-3 gap-2.5 sm:gap-4">
        <a href="{{ route('customer.bookings') }}"
           class="group rounded-2xl border border-gray-200/80 bg-white p-3 shadow-sm transition hover:border-brand-300 hover:shadow-md dark:border-gray-700/50 dark:bg-gray-800/80 dark:hover:border-brand-700/60 sm:p-4">
            <div class="flex items-start justify-between gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400 sm:h-9 sm:w-9">
                    <i data-lucide="calendar-clock" class="h-4 w-4"></i>
                </div>
            </div>
            <p class="mt-3 text-xl font-bold tabular-nums tracking-tight text-gray-900 dark:text-white sm:text-2xl">{{ $upcomingCount }}</p>
            <p class="mt-0.5 text-[11px] font-semibold leading-tight text-gray-700 dark:text-gray-300 sm:text-xs">Upcoming</p>
            <p class="mt-0.5 hidden text-[11px] leading-snug text-gray-400 dark:text-gray-500 sm:block">Still on the calendar</p>
        </a>

        <a href="{{ route('customer.bookings') }}"
           class="group rounded-2xl border border-gray-200/80 bg-white p-3 shadow-sm transition hover:border-amber-300 hover:shadow-md dark:border-gray-700/50 dark:bg-gray-800/80 dark:hover:border-amber-700/60 sm:p-4">
            <div class="flex items-start justify-between gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 sm:h-9 sm:w-9">
                    <i data-lucide="hourglass" class="h-4 w-4"></i>
                </div>
            </div>
            <p class="mt-3 text-xl font-bold tabular-nums tracking-tight text-gray-900 dark:text-white sm:text-2xl">{{ $pendingCount }}</p>
            <p class="mt-0.5 text-[11px] font-semibold leading-tight text-gray-700 dark:text-gray-300 sm:text-xs">Pending confirmation</p>
            <p class="mt-0.5 hidden text-[11px] leading-snug text-gray-400 dark:text-gray-500 sm:block">Waiting for the spa</p>
        </a>

        <div class="rounded-2xl border border-gray-200/80 bg-white p-3 shadow-sm dark:border-gray-700/50 dark:bg-gray-800/80 sm:p-4">
            <div class="flex items-start justify-between gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400 sm:h-9 sm:w-9">
                    <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                </div>
            </div>
            <p class="mt-3 text-xl font-bold tabular-nums tracking-tight text-gray-900 dark:text-white sm:text-2xl">{{ $completedCount }}</p>
            <p class="mt-0.5 text-[11px] font-semibold leading-tight text-gray-700 dark:text-gray-300 sm:text-xs">Completed</p>
            <p class="mt-0.5 hidden text-[11px] leading-snug text-gray-400 dark:text-gray-500 sm:block">All time</p>
        </div>
    </section>

    {{-- Shortcuts --}}
    <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <a href="{{ route('booking.wizard') }}"
           class="group rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md dark:border-gray-700/50 dark:bg-gray-800/80 dark:hover:border-brand-700/60">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                <i data-lucide="sparkles" class="h-5 w-5"></i>
            </div>
            <h3 class="mt-3 font-bold text-gray-900 dark:text-white">Book a session</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose a treatment and reserve your next visit.</p>
            <p class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 group-hover:text-brand-800 dark:text-brand-400">
                Open booking
                <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
            </p>
        </a>

        <a href="{{ route('customer.profile') }}"
           class="group rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md dark:border-gray-700/50 dark:bg-gray-800/80 dark:hover:border-brand-700/60">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                <i data-lucide="user" class="h-5 w-5"></i>
            </div>
            <h3 class="mt-3 font-bold text-gray-900 dark:text-white">Profile &amp; booking name</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your nickname, contact number, and medical notes.</p>
            <p class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 group-hover:text-brand-800 dark:text-brand-400">
                Edit Profile
                <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
            </p>
        </a>
    </section>

</div>
@endsection
