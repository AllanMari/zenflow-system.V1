{{-- =========================================================
     ANALYTICS SUMMARY
========================================================= --}}

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

    {{-- Revenue Growth --}}
    <section class="sales-card rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

        <div class="flex items-start justify-between gap-3">

            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400 dark:text-neutral-500">
                    Revenue Growth
                </p>

                <p class="mt-2 text-3xl font-extrabold tracking-tight
                    {{ $safeRevenueChange >= 0
                        ? 'text-green-600 dark:text-green-400'
                        : 'text-red-600 dark:text-red-400'
                    }}"
                >
                    {{ $revenueChangeLabel }}%
                </p>
            </div>

            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl
                {{ $safeRevenueChange >= 0
                    ? 'bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400'
                    : 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400'
                }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($safeRevenueChange >= 0)
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M3 17l6-6 4 4 8-8"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M3 7l6 6 4-4 8 8"/>
                    @endif
                </svg>
            </span>

        </div>

        <div class="mt-5">

            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] font-semibold text-gray-400 dark:text-neutral-500">
                    Change from previous period
                </span>

                <span class="text-[10px] font-bold
                    {{ $safeRevenueChange >= 0
                        ? 'text-green-600 dark:text-green-400'
                        : 'text-red-600 dark:text-red-400'
                    }}"
                >
                    {{ $safeRevenueChange >= 0 ? 'Increase' : 'Decrease' }}
                </span>
            </div>

            <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-neutral-700">

                <div
                    class="metric-bar h-full rounded-full
                        {{ $safeRevenueChange >= 0 ? 'bg-green-500' : 'bg-red-500' }}"
                    style="width: {{ min(abs($safeRevenueChange), 100) }}%"
                ></div>

            </div>

        </div>

    </section>


    {{-- Appointment Health --}}
    <section class="sales-card rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

        <div class="flex items-center justify-between mb-5">

            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400 dark:text-neutral-500">
                    Appointment Health
                </p>

                <p class="mt-1 text-[11px] text-gray-400 dark:text-neutral-500">
                    Current period activity
                </p>
            </div>

            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622C17.176 19.29 21 14.591 21 9c0-1.035-.13-2.04-.382-3.016z"/>
                </svg>
            </span>

        </div>


        @php
            $healthMetrics = [
                [
                    'label' => 'Completion Rate',
                    'value' => $safeCompletionRate,
                    'bar' => 'bg-green-500',
                    'text' => 'text-green-600 dark:text-green-400'
                ],
                [
                    'label' => 'No-Show Rate',
                    'value' => $safeNoShowRate,
                    'bar' => 'bg-red-500',
                    'text' => 'text-red-600 dark:text-red-400'
                ],
                [
                    'label' => 'Cancellation Rate',
                    'value' => $safeCancellationRate,
                    'bar' => 'bg-rose-500',
                    'text' => 'text-rose-600 dark:text-rose-400'
                ]
            ];
        @endphp


        <div class="space-y-4">

            @foreach($healthMetrics as $metric)

                <div>

                    <div class="flex items-center justify-between mb-1.5">

                        <span class="text-xs font-semibold text-gray-600 dark:text-neutral-300">
                            {{ $metric['label'] }}
                        </span>

                        <span class="text-xs font-extrabold {{ $metric['text'] }}">
                            {{ $metric['value'] }}%
                        </span>

                    </div>

                    <div class="h-2 rounded-full bg-gray-100 overflow-hidden dark:bg-neutral-700">

                        <div
                            class="metric-bar h-full rounded-full {{ $metric['bar'] }}"
                            style="width: {{ min($metric['value'], 100) }}%"
                        ></div>

                    </div>

                </div>

            @endforeach

        </div>

    </section>


    {{-- Top Services --}}
    <section class="sales-card rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

        <div class="flex items-center justify-between mb-5">

            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400 dark:text-neutral-500">
                    Top Services
                </p>

                <p class="mt-1 text-[11px] text-gray-400 dark:text-neutral-500">
                    Revenue contribution
                </p>
            </div>

            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/20 dark:text-brand-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M13 7h8m0 0v8m0-8-8 8-4-4-6 6"/>
                </svg>
            </span>

        </div>


        @if(!empty($topServices))

            <div class="space-y-4">

                @foreach($topServices as $name => $amount)

                    @php
                        $servicePercentage =
                            $maxSvc > 0
                                ? min(100, ($amount / $maxSvc) * 100)
                                : 0;
                    @endphp

                    <div>

                        <div class="flex items-center justify-between gap-3 mb-1.5">

                            <span class="min-w-0 truncate text-xs font-semibold text-gray-700 dark:text-neutral-200">
                                {{ $name }}
                            </span>

                            <span class="shrink-0 text-xs font-extrabold text-brand-600 dark:text-brand-400">
                                {{ $currency }}{{ number_format($amount, 2) }}
                            </span>

                        </div>

                        <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden dark:bg-neutral-700">

                            <div
                                class="metric-bar h-full rounded-full bg-brand-500"
                                style="width: {{ $servicePercentage }}%"
                            ></div>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div class="py-8 text-center">

                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-neutral-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5"/>
                    </svg>
                </span>

                <p class="mt-2 text-xs font-semibold text-gray-500 dark:text-neutral-400">
                    No service data
                </p>

            </div>

        @endif

    </section>

</div>


{{-- =========================================================
     STAFF PERFORMANCE
========================================================= --}}
<section class="sales-card mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

    <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-700">

        <div>

            <div class="flex items-center gap-2">

                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M17 20h5v-2a4 4 0 00-4-4h-1m-2 6H7a4 4 0 01-4-4v-1a4 4 0 014-4h8a4 4 0 014 4v1a4 4 0 01-4 4zM12 10a4 4 0 100-8 4 4 0 000 8z"/>
                    </svg>
                </span>

                <div>

                    <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">
                        Staff Performance
                    </h3>

                    <p class="mt-0.5 text-[11px] text-gray-400 dark:text-neutral-500">
                        Assigned appointments and revenue during the selected period
                    </p>

                </div>

            </div>

        </div>


        @if(!empty($staffPerformance))

            <span class="inline-flex w-fit items-center rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-bold text-gray-600 dark:bg-neutral-700 dark:text-neutral-300">
                {{ count($staffPerformance) }}
                staff member{{ count($staffPerformance) === 1 ? '' : 's' }}
            </span>

        @endif

    </div>


    @if(!empty($staffPerformance))

        <div class="overflow-x-auto">

            <table class="min-w-[1050px] w-full">

                <thead>
                    <tr class="bg-gray-50/80 dark:bg-neutral-900/30">

                        <th class="px-4 py-3 text-left text-[9px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-neutral-500">
                            Rank
                        </th>

                        <th class="px-4 py-3 text-left text-[9px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-neutral-500">
                            Staff
                        </th>

                        <th class="px-4 py-3 text-center text-[9px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-neutral-500">
                            Appointments
                        </th>

                        <th class="px-4 py-3 text-center text-[9px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-neutral-500">
                            Completed
                        </th>

                        <th class="px-4 py-3 text-center text-[9px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-neutral-500">
                            No-Shows
                        </th>

                        <th class="px-4 py-3 text-center text-[9px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-neutral-500">
                            Cancelled
                        </th>

                        <th class="px-4 py-3 text-left text-[9px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-neutral-500">
                            Completion
                        </th>

                        <th class="px-4 py-3 text-right text-[9px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-neutral-500">
                            Revenue
                        </th>

                        <th class="px-4 py-3 text-right text-[9px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-neutral-500">
                            Commission
                        </th>

                    </tr>
                </thead>


                <tbody class="divide-y divide-gray-100 dark:divide-neutral-700">

                    @foreach($staffPerformance as $staff)

                        @php

                            $rankClass = match ((int) ($staff['rank'] ?? 0)) {
                                1 => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                2 => 'bg-gray-100 text-gray-600 dark:bg-neutral-700 dark:text-neutral-300',
                                3 => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                                default => 'bg-gray-50 text-gray-500 dark:bg-neutral-700 dark:text-neutral-400'
                            };

                            $completionRate =
                                min(
                                    100,
                                    max(
                                        0,
                                        (float) ($staff['completion_rate'] ?? 0)
                                    )
                                );

                            $completionColor =
                                $completionRate >= 80
                                    ? 'bg-green-500'
                                    : ($completionRate >= 60
                                        ? 'bg-amber-500'
                                        : 'bg-red-500');

                            $completionText =
                                $completionRate >= 80
                                    ? 'text-green-600 dark:text-green-400'
                                    : ($completionRate >= 60
                                        ? 'text-amber-600 dark:text-amber-400'
                                        : 'text-red-600 dark:text-red-400');

                        @endphp


                        <tr class="group transition hover:bg-gray-50/80 dark:hover:bg-neutral-700/20">

                            {{-- Rank --}}
                            <td class="px-4 py-4">

                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-extrabold {{ $rankClass }}">
                                    {{ $staff['rank'] }}
                                </span>

                            </td>


                            {{-- Staff --}}
                            <td class="px-4 py-4">

                                <div class="flex items-center gap-3">

                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-xs font-extrabold text-gray-600 dark:bg-neutral-700 dark:text-neutral-300">
                                        {{ strtoupper(substr($staff['name'], 0, 1)) }}
                                    </span>

                                    <div class="min-w-0">

                                        <p class="truncate text-sm font-bold text-gray-800 dark:text-neutral-100">
                                            {{ $staff['name'] }}
                                        </p>

                                        <p class="mt-0.5 text-[10px] text-gray-400 dark:text-neutral-500">
                                            {{ $staff['services'] }}
                                            service{{ $staff['services'] == 1 ? '' : 's' }}
                                            handled
                                        </p>

                                    </div>

                                </div>

                            </td>


                            {{-- Appointments --}}
                            <td class="px-4 py-4 text-center">

                                <span class="text-sm font-bold text-gray-700 dark:text-neutral-200">
                                    {{ $staff['appointments'] }}
                                </span>

                            </td>


                            {{-- Completed --}}
                            <td class="px-4 py-4 text-center">

                                <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-green-50 px-2 py-1 text-xs font-extrabold text-green-700 dark:bg-green-900/20 dark:text-green-400">
                                    {{ $staff['completed'] }}
                                </span>

                            </td>


                            {{-- No Shows --}}
                            <td class="px-4 py-4 text-center">

                                @if(($staff['no_shows'] ?? 0) > 0)

                                    <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-red-50 px-2 py-1 text-xs font-extrabold text-red-700 dark:bg-red-900/20 dark:text-red-400">
                                        {{ $staff['no_shows'] }}
                                    </span>

                                @else

                                    <span class="text-xs font-semibold text-gray-300 dark:text-neutral-600">
                                        0
                                    </span>

                                @endif

                            </td>


                            {{-- Cancelled --}}
                            <td class="px-4 py-4 text-center">

                                @if(($staff['cancelled'] ?? 0) > 0)

                                    <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-rose-50 px-2 py-1 text-xs font-extrabold text-rose-700 dark:bg-rose-900/20 dark:text-rose-400">
                                        {{ $staff['cancelled'] }}
                                    </span>

                                @else

                                    <span class="text-xs font-semibold text-gray-300 dark:text-neutral-600">
                                        0
                                    </span>

                                @endif

                            </td>


                            {{-- Completion --}}
                            <td class="px-4 py-4">

                                <div class="w-28">

                                    <div class="flex items-center justify-between mb-1">

                                        <span class="text-[10px] font-bold text-gray-400 dark:text-neutral-500">
                                            Completion
                                        </span>

                                        <span class="text-[10px] font-extrabold {{ $completionText }}">
                                            {{ $completionRate }}%
                                        </span>

                                    </div>

                                    <div class="h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-neutral-700">

                                        <div
                                            class="metric-bar h-full rounded-full {{ $completionColor }}"
                                            style="width: {{ $completionRate }}%"
                                        ></div>

                                    </div>

                                </div>

                            </td>


                            {{-- Revenue --}}
                            <td class="px-4 py-4 text-right">

                                <p class="text-sm font-extrabold text-brand-600 dark:text-brand-400">
                                    {{ $currency }}{{ number_format($staff['revenue'], 2) }}
                                </p>

                                <p class="mt-0.5 text-[9px] text-gray-400 dark:text-neutral-500">
                                    {{ $currency }}{{ number_format($staff['avg_revenue'], 2) }}
                                    / completed
                                </p>

                            </td>


                            {{-- Commission --}}
                            <td class="px-4 py-4 text-right">

                                <span class="text-sm font-bold text-gray-700 dark:text-neutral-200">
                                    {{ $currency }}{{ number_format($staff['commission'], 2) }}
                                </span>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @else

        <div class="px-5 py-14 text-center">

            <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-neutral-700 dark:text-neutral-500">

                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m8-8a4 4 0 100-8 4 4 0 000 8zm8-4v6m3-3h-6"/>
                </svg>

            </span>

            <p class="mt-3 text-sm font-bold text-gray-600 dark:text-neutral-300">
                No staff performance data
            </p>

            <p class="mt-1 text-xs text-gray-400 dark:text-neutral-500">
                No assigned staff appointments were found for this period.
            </p>

        </div>

    @endif

</section>


{{-- =========================================================
     PEAK BUSINESS HOURS
========================================================= --}}
<section class="sales-card mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">

        <div>

            <div class="flex items-center gap-2">

                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>

                <div>

                    <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">
                        Peak Business Hours
                    </h3>

                    <p class="mt-0.5 text-[11px] text-gray-400 dark:text-neutral-500">
                        Appointment demand based on scheduled start times
                    </p>

                </div>

            </div>

        </div>


        @if(($peakBusinessHours['peakCount'] ?? 0) > 0)

            <div class="inline-flex w-fit items-center gap-2 rounded-xl border border-brand-100 bg-brand-50 px-3 py-2 dark:border-brand-800 dark:bg-brand-900/20">

                <span class="text-[9px] font-extrabold uppercase tracking-wider text-brand-500 dark:text-brand-400">
                    Peak
                </span>

                <span class="text-xs font-extrabold text-brand-700 dark:text-brand-300">
                    {{ $peakBusinessHours['peakLabel'] }}
                </span>

                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1.5 text-[9px] font-bold text-white">
                    {{ $peakBusinessHours['peakCount'] }}
                </span>

            </div>

        @endif

    </div>


    @if(($peakBusinessHours['peakCount'] ?? 0) > 0)

        @php
            $peakHours = $peakBusinessHours['hours'] ?? [];

            $maxHourCount =
                collect($peakHours)->max('count') ?: 1;
        @endphp


        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-8 gap-3">

            @foreach($peakHours as $hour)

                @php
                    $count = (int) ($hour['count'] ?? 0);

                    $height =
                        $count > 0
                            ? max(12, ($count / $maxHourCount) * 100)
                            : 5;
                @endphp


                <div class="group rounded-xl border p-3 transition
                    {{ $hour['is_peak']
                        ? 'border-brand-200 bg-brand-50/70 dark:border-brand-800 dark:bg-brand-900/10'
                        : 'border-gray-200 bg-gray-50 dark:border-neutral-700 dark:bg-neutral-800/60'
                    }}"
                >

                    <div class="flex items-center justify-between gap-2">

                        <span class="text-[10px] font-bold text-gray-500 dark:text-neutral-400">
                            {{ $hour['label'] }}
                        </span>

                        <span class="text-sm font-extrabold
                            {{ $hour['is_peak']
                                ? 'text-brand-600 dark:text-brand-400'
                                : 'text-gray-700 dark:text-neutral-200'
                            }}"
                        >
                            {{ $count }}
                        </span>

                    </div>


                    <div class="mt-3 h-20 flex items-end">

                        <div class="flex h-full w-full items-end">

                            <div
                                class="metric-bar w-full rounded-lg
                                    {{ $hour['is_peak']
                                        ? 'bg-brand-500'
                                        : 'bg-gray-300 dark:bg-neutral-600'
                                    }}"
                                style="height: {{ $height }}%"
                            ></div>

                        </div>

                    </div>


                    <p class="mt-2 text-[9px] font-medium text-gray-400 dark:text-neutral-500">
                        {{ $count == 1 ? 'appointment' : 'appointments' }}
                    </p>

                </div>

            @endforeach

        </div>

    @else

        <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-5 py-12 text-center dark:border-neutral-700 dark:bg-neutral-800/50">

            <span class="mx-auto inline-flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-neutral-700 dark:text-neutral-500">

                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>

            </span>

            <p class="mt-3 text-sm font-bold text-gray-600 dark:text-neutral-300">
                No appointment activity
            </p>

            <p class="mt-1 text-xs text-gray-400 dark:text-neutral-500">
                Peak hours will appear when appointments are available.
            </p>

        </div>

    @endif

</section>


{{-- =========================================================
     12-MONTH BOOKING TRENDS
========================================================= --}}
<section class="sales-card rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-5">

        <div>

            <div class="flex items-center gap-2">

                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-900/20 dark:text-violet-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M3 3v18h18M7 16l4-5 3 3 6-7"/>
                    </svg>
                </span>

                <div>

                    <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">
                        12-Month Booking Trends
                    </h3>

                    <p class="mt-0.5 text-[11px] text-gray-400 dark:text-neutral-500">
                        Appointment volume and completion trends
                    </p>

                </div>

            </div>

        </div>

        <span class="text-[10px] font-semibold text-gray-400 dark:text-neutral-500">
            Rolling 12 months
        </span>

    </div>


    <div class="relative h-72">

        <canvas id="monthlySpikeChart"></canvas>

    </div>

</section>