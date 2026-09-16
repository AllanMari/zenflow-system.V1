{{-- =========================================================
     ANALYTICS TAB
========================================================= --}}

@php
    /*
    |--------------------------------------------------------------------------
    | Safe Analytics Defaults
    |--------------------------------------------------------------------------
    */

    $safeRevenueChange = (float) ($safeRevenueChange ?? 0);
    $safeCompletionRate = (float) ($safeCompletionRate ?? 0);
    $safeNoShowRate = (float) ($safeNoShowRate ?? 0);
    $safeCancellationRate = (float) ($safeCancellationRate ?? 0);

    $revenueChangeLabel = $revenueChangeLabel ?? 'No change';
    $currency = $currency ?? '₱';

    $staffPerformance = collect($staffPerformance ?? []);
    $staffEfficiency = collect($staffEfficiency ?? []);
    $topServices = collect($topServices ?? []);

    $revenueLoss = $revenueLoss ?? [];
    $customerRetention = $customerRetention ?? [];
    $peakBusinessHours = $peakBusinessHours ?? [];

    /*
    |--------------------------------------------------------------------------
    | Revenue Loss
    |--------------------------------------------------------------------------
    */

    $estimatedRevenueLoss = (float) ($revenueLoss['total_loss'] ?? 0);
    $cancelledLoss = (float) ($revenueLoss['cancelled_loss'] ?? 0);
    $noShowLoss = (float) ($revenueLoss['no_show_loss'] ?? 0);

    $cancelledLossCount = (int) ($revenueLoss['cancelled_count'] ?? 0);
    $noShowLossCount = (int) ($revenueLoss['no_show_count'] ?? 0);

    $affectedAppointments = (int) (
        $revenueLoss['affected_count']
        ?? ($cancelledLossCount + $noShowLossCount)
    );

    /*
    |--------------------------------------------------------------------------
    | Customer Retention
    |--------------------------------------------------------------------------
    */

    $customersServed = (int) ($customerRetention['customers_served'] ?? 0);
    $returningCustomers = (int) ($customerRetention['returning_customers'] ?? 0);
    $newCustomers = (int) ($customerRetention['new_customers'] ?? 0);

    $retentionRate = min(
        100,
        max(0, (float) ($customerRetention['retention_rate'] ?? 0))
    );

    /*
    |--------------------------------------------------------------------------
    | Peak Business Hours
    |--------------------------------------------------------------------------
    */

    $peakCount = (int) ($peakBusinessHours['peakCount'] ?? 0);
    $peakLabel = $peakBusinessHours['peakLabel'] ?? 'No peak hour available';
    $peakHours = collect($peakBusinessHours['hours'] ?? []);

    /*
    |--------------------------------------------------------------------------
    | Reusable Formatting
    |--------------------------------------------------------------------------
    */

    $formatMoney = function ($value) use ($currency) {
        return $currency . number_format((float) $value, 2);
    };

    $formatPercent = function ($value) {
        return number_format((float) $value, 1) . '%';
    };
@endphp


{{-- =========================================================
     QUICK NAVIGATION
========================================================= --}}

<div class="mb-6 overflow-x-auto">
    <div class="inline-flex min-w-full items-center gap-2 rounded-xl border border-gray-200 bg-white p-2 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

        <a
            href="#analytics-summary"
            class="inline-flex shrink-0 items-center gap-x-2 rounded-lg px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700 dark:hover:text-white"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            Summary
        </a>

        <a
            href="#staff-performance"
            class="inline-flex shrink-0 items-center gap-x-2 rounded-lg px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700 dark:hover:text-white"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Staff Performance
        </a>

        <a
            href="#staff-efficiency"
            class="inline-flex shrink-0 items-center gap-x-2 rounded-lg px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700 dark:hover:text-white"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M12 2v2"/>
                <path d="M12 20v2"/>
                <path d="m4.93 4.93 1.42 1.42"/>
                <path d="m17.65 17.65 1.42 1.42"/>
                <path d="M2 12h2"/>
                <path d="M20 12h2"/>
                <path d="m6.35 17.65-1.42 1.42"/>
                <path d="m19.07 4.93-1.42 1.42"/>
                <circle cx="12" cy="12" r="4"/>
            </svg>
            Staff Utilization
        </a>

        <a
            href="#revenue-loss"
            class="inline-flex shrink-0 items-center gap-x-2 rounded-lg px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700 dark:hover:text-white"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M3 3v18h18"/>
                <path d="m7 15 4-4 3 3 6-7"/>
            </svg>
            Revenue Loss
        </a>

        <a
            href="#customer-retention"
            class="inline-flex shrink-0 items-center gap-x-2 rounded-lg px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700 dark:hover:text-white"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M17 1l4 4-4 4"/>
                <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                <path d="m7 23-4-4 4-4"/>
                <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
            </svg>
            Retention
        </a>

        <a
            href="#peak-hours"
            class="inline-flex shrink-0 items-center gap-x-2 rounded-lg px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700 dark:hover:text-white"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 7v5l3 2"/>
            </svg>
            Peak Hours
        </a>

        <a
            href="#booking-trends"
            class="inline-flex shrink-0 items-center gap-x-2 rounded-lg px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-neutral-300 dark:hover:bg-neutral-700 dark:hover:text-white"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M4 19V5"/>
                <path d="M4 19h17"/>
                <path d="m7 15 3-3 3 2 5-6"/>
            </svg>
            Trends
        </a>

    </div>
</div>


{{-- =========================================================
     ANALYTICS SUMMARY
========================================================= --}}

<section id="analytics-summary" class="scroll-mt-24">

    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Analytics Summary
        </h2>

        <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
            Key performance indicators for the selected reporting period.
        </p>
    </div>


    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">


        {{-- Revenue Growth --}}

        <section class="sales-card rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

            <div class="flex items-start justify-between gap-4">

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-neutral-400">
                        Revenue Growth
                    </p>

                    <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ $formatPercent(abs($safeRevenueChange)) }}
                    </p>
                </div>

                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $safeRevenueChange >= 0 ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' }}">

                    @if ($safeRevenueChange >= 0)

                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M3 17l6-6 4 4 8-8"/>
                            <path d="M15 7h6v6"/>
                        </svg>

                    @else

                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M3 7l6 6 4-4 8 8"/>
                            <path d="M15 17h6v-6"/>
                        </svg>

                    @endif

                </div>

            </div>

            <div class="mt-4 flex items-center gap-2">

                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $safeRevenueChange >= 0 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400' }}">
                    {{ $revenueChangeLabel }}
                </span>

                <span class="text-xs text-gray-500 dark:text-neutral-500">
                    compared with the previous period
                </span>

            </div>

        </section>


        {{-- Appointment Health --}}

        <section class="sales-card rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

            <div class="flex items-start justify-between gap-4">

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-neutral-400">
                        Appointment Health
                    </p>

                    <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ $formatPercent($safeCompletionRate) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500 dark:text-neutral-500">
                        Completion rate
                    </p>
                </div>

                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-500/10 dark:text-teal-400">

                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 12a9 9 0 1 0 18 0"/>
                        <path d="M12 3v9l6 3"/>
                    </svg>

                </div>

            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">

                <div class="rounded-xl bg-gray-50 p-3 dark:bg-neutral-700/50">

                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                        No-show
                    </p>

                    <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">
                        {{ $formatPercent($safeNoShowRate) }}
                    </p>

                </div>

                <div class="rounded-xl bg-gray-50 p-3 dark:bg-neutral-700/50">

                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                        Cancelled
                    </p>

                    <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">
                        {{ $formatPercent($safeCancellationRate) }}
                    </p>

                </div>

            </div>

        </section>


        {{-- Top Services --}}

        <section class="sales-card rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

            <div class="flex items-start justify-between gap-4">

                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-neutral-400">
                        Top Services
                    </p>

                    <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ $topServices->count() }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500 dark:text-neutral-500">
                        Services with recorded revenue
                    </p>
                </div>

                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">

                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="m12 3 1.9 5.8H20l-4.9 3.6 1.9 5.8-5-3.6-5 3.6 1.9-5.8L4 8.8h6.1L12 3Z"/>
                    </svg>

                </div>

            </div>

            @if ($topServices->isNotEmpty())

                @php
                    $topService = $topServices->first();
                    $topServiceName = $topService['name'] ?? 'Unknown Service';
                    $topServiceRevenue = (float) ($topService['revenue'] ?? 0);
                @endphp

                <div class="mt-4 rounded-xl bg-gray-50 p-3 dark:bg-neutral-700/50">

                    <div class="flex items-center justify-between gap-3">

                        <div class="min-w-0">

                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $topServiceName }}
                            </p>

                            <p class="mt-0.5 text-xs text-gray-500 dark:text-neutral-400">
                                Highest recorded service revenue
                            </p>

                        </div>

                        <span class="shrink-0 text-sm font-bold text-teal-600 dark:text-teal-400">
                            {{ $formatMoney($topServiceRevenue) }}
                        </span>

                    </div>

                </div>

            @else

                <div class="mt-4 rounded-xl bg-gray-50 p-3 dark:bg-neutral-700/50">
                    <p class="text-sm text-gray-500 dark:text-neutral-400">
                        No service revenue data available.
                    </p>
                </div>

            @endif

        </section>

    </div>

</section>


{{-- =========================================================
     STAFF PERFORMANCE
========================================================= --}}

<section id="staff-performance" class="mt-8 scroll-mt-24">

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

        <div class="border-b border-gray-200 px-5 py-5 dark:border-neutral-700">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <div class="flex items-center gap-2">

                        <div class="flex size-9 items-center justify-center rounded-lg bg-teal-50 text-teal-600 dark:bg-teal-500/10 dark:text-teal-400">

                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                                <path d="M16 3.5a4 4 0 0 1 0 7.5"/>
                                <path d="M19 15a4 4 0 0 1 3 4v2"/>
                            </svg>

                        </div>

                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                            Staff Performance
                        </h2>

                    </div>

                    <p class="mt-2 text-sm text-gray-500 dark:text-neutral-400">
                        Completed appointments and recorded revenue by staff member.
                    </p>

                </div>

                <span class="inline-flex w-fit items-center rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 dark:bg-neutral-700 dark:text-neutral-300">
                    {{ $staffPerformance->count() }}
                    {{ $staffPerformance->count() === 1 ? 'staff member' : 'staff members' }}
                </span>

            </div>

        </div>


        @if ($staffPerformance->isNotEmpty())

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">

                    <thead class="bg-gray-50 dark:bg-neutral-800/80">

                        <tr>

                            <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Rank
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Staff
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Appointments
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Completed
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                No-shows
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Cancelled
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Completion
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Revenue
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Commission
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-neutral-700">

                        @foreach ($staffPerformance as $staff)

                            @php
                                $staffRank = (int) ($staff['rank'] ?? $loop->iteration);
                                $staffName = $staff['name'] ?? 'Unknown Staff';

                                $staffAppointments = (int) ($staff['appointments'] ?? 0);
                                $staffCompleted = (int) ($staff['completed'] ?? 0);
                                $staffNoShows = (int) ($staff['no_shows'] ?? 0);
                                $staffCancelled = (int) ($staff['cancelled'] ?? 0);

                                $staffCompletionRate = (float) ($staff['completion_rate'] ?? 0);
                                $staffRevenue = (float) ($staff['revenue'] ?? 0);
                                $staffCommission = (float) ($staff['commission'] ?? 0);
                                $staffServices = (int) ($staff['services'] ?? 0);
                            @endphp

                            <tr class="transition hover:bg-gray-50 dark:hover:bg-neutral-700/40">

                                <td class="whitespace-nowrap px-5 py-4">

                                    <span class="inline-flex size-7 items-center justify-center rounded-full text-xs font-bold {{ $staffRank === 1 ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-gray-100 text-gray-600 dark:bg-neutral-700 dark:text-neutral-300' }}">
                                        {{ $staffRank }}
                                    </span>

                                </td>

                                <td class="whitespace-nowrap px-5 py-4">

                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $staffName }}
                                    </div>

                                    <div class="mt-0.5 text-xs text-gray-500 dark:text-neutral-500">
                                        {{ $staffServices }}
                                        completed service{{ $staffServices === 1 ? '' : 's' }}
                                    </div>

                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-center text-sm text-gray-700 dark:text-neutral-300">
                                    {{ $staffAppointments }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-center font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ $staffCompleted }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-center text-amber-600 dark:text-amber-400">
                                    {{ $staffNoShows }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-center text-red-600 dark:text-red-400">
                                    {{ $staffCancelled }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-center">

                                    <span class="inline-flex rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 dark:bg-teal-500/10 dark:text-teal-400">
                                        {{ $formatPercent($staffCompletionRate) }}
                                    </span>

                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $formatMoney($staffRevenue) }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-medium text-gray-600 dark:text-neutral-300">
                                    {{ $formatMoney($staffCommission) }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="px-5 py-12 text-center">

                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-neutral-700 dark:text-neutral-400">

                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                    </svg>

                </div>

                <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
                    No staff performance data
                </h3>

                <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500 dark:text-neutral-400">
                    There are no staff records available for the selected reporting period.
                </p>

            </div>

        @endif

    </div>

</section>


{{-- =========================================================
     STAFF UTILIZATION
========================================================= --}}

<section id="staff-efficiency" class="mt-8 scroll-mt-24">

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

        <div class="border-b border-gray-200 px-5 py-5 dark:border-neutral-700">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <div class="flex items-center gap-2">

                        <div class="flex size-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">

                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M12 2a10 10 0 1 0 10 10"/>
                                <path d="M12 6v6l4 2"/>
                            </svg>

                        </div>

                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                            Staff Utilization
                        </h2>

                    </div>

                    <p class="mt-2 text-sm text-gray-500 dark:text-neutral-400">
                        Service time compared with recorded clocked hours.
                    </p>

                </div>

                <span class="inline-flex w-fit items-center rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 dark:bg-neutral-700 dark:text-neutral-300">
                    {{ $staffEfficiency->count() }}
                    {{ $staffEfficiency->count() === 1 ? 'staff member' : 'staff members' }}
                </span>

            </div>

        </div>


        @if ($staffEfficiency->isNotEmpty())

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">

                    <thead class="bg-gray-50 dark:bg-neutral-800/80">

                        <tr>

                            <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Rank
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Staff
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Appointments
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Service Hours
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Clocked Hours
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Utilization
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Revenue
                            </th>

                            <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                                Revenue / Hour
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-neutral-700">

                        @foreach ($staffEfficiency as $staff)

                            @php
                                $efficiencyRank = (int) ($staff['rank'] ?? $loop->iteration);
                                $efficiencyName = $staff['name'] ?? 'Unknown Staff';

                                $efficiencyAppointments = (int) ($staff['appointments'] ?? 0);
                                $serviceHours = (float) ($staff['service_hours'] ?? 0);
                                $clockedHours = (float) ($staff['clocked_hours'] ?? 0);
                                $utilization = min(100, max(0, (float) ($staff['utilization'] ?? 0)));
                                $efficiencyRevenue = (float) ($staff['revenue'] ?? 0);
                                $revenuePerHour = (float) ($staff['revenue_per_hour'] ?? 0);
                            @endphp

                            <tr class="transition hover:bg-gray-50 dark:hover:bg-neutral-700/40">

                                <td class="whitespace-nowrap px-5 py-4">

                                    <span class="inline-flex size-7 items-center justify-center rounded-full text-xs font-bold {{ $efficiencyRank === 1 ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-gray-100 text-gray-600 dark:bg-neutral-700 dark:text-neutral-300' }}">
                                        {{ $efficiencyRank }}
                                    </span>

                                </td>

                                <td class="whitespace-nowrap px-5 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $efficiencyName }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-center text-sm text-gray-700 dark:text-neutral-300">
                                    {{ $efficiencyAppointments }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-gray-700 dark:text-neutral-300">
                                    {{ number_format($serviceHours, 1) }} h
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-gray-700 dark:text-neutral-300">
                                    {{ number_format($clockedHours, 1) }} h
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">

                                    <div class="mx-auto w-28">

                                        <div class="mb-1 flex items-center justify-between">

                                            <span class="text-xs font-semibold text-gray-700 dark:text-neutral-300">
                                                {{ $formatPercent($utilization) }}
                                            </span>

                                        </div>

                                        <div class="h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-neutral-700">

                                            <div
                                                class="h-full rounded-full bg-teal-500"
                                                style="width: {{ $utilization }}%"
                                            ></div>

                                        </div>

                                    </div>

                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $formatMoney($efficiencyRevenue) }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-medium text-gray-600 dark:text-neutral-300">
                                    {{ $formatMoney($revenuePerHour) }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="px-5 py-12 text-center">

                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-neutral-700 dark:text-neutral-400">

                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v4l3 2"/>
                    </svg>

                </div>

                <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">
                    No utilization data
                </h3>

                <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500 dark:text-neutral-400">
                    Staff utilization requires recorded service time and attendance hours.
                </p>

            </div>

        @endif

    </div>

</section>


{{-- =========================================================
     ESTIMATED REVENUE LOSS
========================================================= --}}

<section id="revenue-loss" class="mt-8 scroll-mt-24">

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

        <div class="border-b border-gray-200 px-5 py-5 dark:border-neutral-700">

            <div class="flex items-start gap-3">

                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">

                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 3v18h18"/>
                        <path d="m7 15 4-4 3 3 6-7"/>
                    </svg>

                </div>

                <div>

                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        Estimated Revenue Loss
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
                        Estimated service value affected by cancellations and customer no-shows.
                    </p>

                </div>

            </div>

        </div>


        <div class="p-5">

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                <div class="rounded-xl border border-gray-200 p-4 dark:border-neutral-700">

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                        Estimated Total Loss
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $formatMoney($estimatedRevenueLoss) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500 dark:text-neutral-500">
                        {{ $affectedAppointments }}
                        {{ $affectedAppointments === 1 ? 'affected appointment' : 'affected appointments' }}
                    </p>

                </div>


                <div class="rounded-xl border border-gray-200 p-4 dark:border-neutral-700">

                    <div class="flex items-center justify-between gap-3">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                            Cancelled
                        </p>

                        <span class="inline-flex size-7 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">

                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="17" rx="2"/>
                                <path d="M16 2v4M8 2v4M3 10h18"/>
                                <path d="m9 14 6 6M15 14l-6 6"/>
                            </svg>

                        </span>

                    </div>

                    <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">
                        {{ $formatMoney($cancelledLoss) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500 dark:text-neutral-500">
                        {{ $cancelledLossCount }}
                        {{ $cancelledLossCount === 1 ? 'appointment' : 'appointments' }}
                    </p>

                </div>


                <div class="rounded-xl border border-gray-200 p-4 dark:border-neutral-700">

                    <div class="flex items-center justify-between gap-3">

                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                            Customer No-shows
                        </p>

                        <span class="inline-flex size-7 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">

                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                                <path d="m17 16 5 5M22 16l-5 5"/>
                            </svg>

                        </span>

                    </div>

                    <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">
                        {{ $formatMoney($noShowLoss) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500 dark:text-neutral-500">
                        {{ $noShowLossCount }}
                        {{ $noShowLossCount === 1 ? 'appointment' : 'appointments' }}
                    </p>

                </div>

            </div>


            <div class="mt-4 rounded-xl bg-amber-50 p-4 dark:bg-amber-500/5">

                <div class="flex gap-3">

                    <svg class="mt-0.5 size-4 shrink-0 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v4"/>
                        <path d="M12 16h.01"/>
                    </svg>

                    <p class="text-xs leading-5 text-amber-800 dark:text-amber-300">
                        This figure represents an estimated service value associated with
                        cancelled and no-show appointments. It should not be interpreted as
                        confirmed cash that was actually lost.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>


{{-- =========================================================
     CUSTOMER RETENTION
========================================================= --}}

<section id="customer-retention" class="mt-8 scroll-mt-24">

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

        <div class="border-b border-gray-200 px-5 py-5 dark:border-neutral-700">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-start gap-3">

                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">

                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M17 1l4 4-4 4"/>
                            <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                            <path d="m7 23-4-4 4-4"/>
                            <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                        </svg>

                    </div>

                    <div>

                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                            Customer Retention
                        </h2>

                        <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
                            Returning and new customers during the selected period.
                        </p>

                    </div>

                </div>


                <div class="text-left sm:text-right">

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                        Retention Rate
                    </p>

                    <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                        {{ $formatPercent($retentionRate) }}
                    </p>

                </div>

            </div>

        </div>


        <div class="p-5">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                <div class="rounded-xl bg-gray-50 p-4 dark:bg-neutral-700/50">

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                        Customers Served
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $customersServed }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500 dark:text-neutral-500">
                        Completed appointment customers
                    </p>

                </div>


                <div class="rounded-xl bg-gray-50 p-4 dark:bg-neutral-700/50">

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                        Returning Customers
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $returningCustomers }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500 dark:text-neutral-500">
                        Customers with prior completed visits
                    </p>

                </div>


                <div class="rounded-xl bg-gray-50 p-4 dark:bg-neutral-700/50">

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                        New Customers
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $newCustomers }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500 dark:text-neutral-500">
                        Customers with no prior completed visit
                    </p>

                </div>

            </div>


            <div class="mt-5">

                <div class="mb-2 flex items-center justify-between gap-3">

                    <span class="text-xs font-medium text-gray-600 dark:text-neutral-300">
                        Returning customer share
                    </span>

                    <span class="text-xs font-semibold text-gray-900 dark:text-white">
                        {{ $formatPercent($retentionRate) }}
                    </span>

                </div>

                <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-neutral-700">

                    <div
                        class="h-full rounded-full bg-teal-500 transition-all"
                        style="width: {{ $retentionRate }}%"
                    ></div>

                </div>

            </div>

        </div>

    </div>

</section>


{{-- =========================================================
     PEAK BUSINESS HOURS
========================================================= --}}

<section id="peak-hours" class="mt-8 scroll-mt-24">

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

        <div class="border-b border-gray-200 px-5 py-5 dark:border-neutral-700">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-start gap-3">

                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">

                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 7v5l3 2"/>
                        </svg>

                    </div>

                    <div>

                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                            Peak Business Hours
                        </h2>

                        <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
                            Appointment activity by hour for the selected period.
                        </p>

                    </div>

                </div>


                <div class="rounded-xl bg-gray-50 px-4 py-3 dark:bg-neutral-700/50">

                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500 dark:text-neutral-400">
                        Peak Hour
                    </p>

                    <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">
                        {{ $peakLabel }}
                    </p>

                    @if ($peakCount > 0)

                        <p class="mt-0.5 text-xs text-gray-500 dark:text-neutral-400">
                            {{ $peakCount }}
                            {{ $peakCount === 1 ? 'appointment' : 'appointments' }}
                        </p>

                    @endif

                </div>

            </div>

        </div>


        <div class="p-5">

            @if ($peakHours->isNotEmpty())

                @php
                    $maxPeakCount = max(
                        1,
                        (int) $peakHours->max(function ($hour) {
                            return (int) ($hour['count'] ?? 0);
                        })
                    );
                @endphp

                <div class="space-y-3">

                    @foreach ($peakHours as $hour)

                        @php
                            $hourLabel = $hour['label'] ?? 'Unknown';
                            $hourCount = (int) ($hour['count'] ?? 0);
                            $isPeak = (bool) ($hour['is_peak'] ?? false);

                            $barWidth = min(
                                100,
                                max(0, ($hourCount / $maxPeakCount) * 100)
                            );
                        @endphp

                        <div class="grid grid-cols-[72px_minmax(0,1fr)_40px] items-center gap-3">

                            <span class="text-xs font-medium text-gray-600 dark:text-neutral-300">
                                {{ $hourLabel }}
                            </span>

                            <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-neutral-700">

                                <div
                                    class="h-full rounded-full {{ $isPeak ? 'bg-purple-500' : 'bg-teal-500' }}"
                                    style="width: {{ $barWidth }}%"
                                ></div>

                            </div>

                            <span class="text-right text-xs font-semibold text-gray-700 dark:text-neutral-300">
                                {{ $hourCount }}
                            </span>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="py-8 text-center">

                    <div class="mx-auto flex size-11 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-neutral-700 dark:text-neutral-400">

                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 7v5l3 2"/>
                        </svg>

                    </div>

                    <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">
                        No hourly data
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
                        No appointment activity was recorded for this period.
                    </p>

                </div>

            @endif

        </div>

    </div>

</section>


{{-- =========================================================
     12-MONTH BOOKING TRENDS
========================================================= --}}

<section id="booking-trends" class="mt-8 scroll-mt-24">

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800">

        <div class="border-b border-gray-200 px-5 py-5 dark:border-neutral-700">

            <div class="flex items-start gap-3">

                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">

                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4 19V5"/>
                        <path d="M4 19h17"/>
                        <path d="m7 15 3-3 3 2 5-6"/>
                    </svg>

                </div>

                <div>

                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        12-Month Booking Trends
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-neutral-400">
                        Monthly appointment activity over the available historical period.
                    </p>

                </div>

            </div>

        </div>


        <div class="p-5">

            <div class="h-[320px] w-full">

                <canvas id="monthlySpikeChart"></canvas>

            </div>

        </div>

    </div>

</section>


{{-- =========================================================
     ANALYTICS FOOTNOTE
========================================================= --}}

<div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-neutral-700 dark:bg-neutral-800/50">

    <div class="flex gap-3">

        <svg class="mt-0.5 size-4 shrink-0 text-gray-500 dark:text-neutral-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <path d="M12 8v4"/>
            <path d="M12 16h.01"/>
        </svg>

        <p class="text-xs leading-5 text-gray-500 dark:text-neutral-400">
            Analytics are based on recorded appointments, payments, services, staff
            assignments, and attendance within the selected reporting period.
            Estimated revenue loss represents service value associated with
            cancellations and customer no-shows, not confirmed cash loss.
        </p>

    </div>

</div>