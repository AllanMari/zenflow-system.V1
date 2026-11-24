@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Staff Utilization')

@push('styles')

    {{-- Flatpickr --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        .flatpickr-calendar {
            font-family: inherit;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 32px -12px rgb(15 23 42 / .25);
        }

        .flatpickr-day {
            border-radius: 0.625rem;
        }

        .flatpickr-day:hover {
            background: #f0fdfa;
            border-color: #f0fdfa;
        }

        .flatpickr-day.today {
            border-color: #0d9488;
        }

        .flatpickr-day.selected,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange {
            background: #0d9488;
            border-color: #0d9488;
            color: #fff;
        }

        .flatpickr-day.inRange {
            background: #ccfbf1;
            border-color: #ccfbf1;
            box-shadow: -5px 0 0 #ccfbf1, 5px 0 0 #ccfbf1;
        }

        .flatpickr-months .flatpickr-prev-month:hover svg,
        .flatpickr-months .flatpickr-next-month:hover svg {
            fill: #0d9488;
        }

        .dark .flatpickr-calendar {
            background: #0f172a;
            border-color: #1e293b;
        }

        .dark .flatpickr-month,
        .dark .flatpickr-weekday {
            color: #94a3b8;
        }

        .dark .flatpickr-day {
            color: #e2e8f0;
        }

        .dark .flatpickr-day:hover {
            background: #1e293b;
        }

        .dark .flatpickr-day.inRange {
            background: rgb(20 184 166 / .2);
        }
    </style>

@endpush

@php
    $isAdmin = Auth::user()->isAdmin();

    $reportRoute = $isAdmin
        ? 'admin.staff-utilization'
        : 'receptionist.staff-utilization';

    $pdfRoute = $isAdmin
        ? 'admin.staff-utilization.pdf'
        : 'receptionist.staff-utilization.pdf';

    $summary = $data['summary'];
    $staff = $data['staff'];

    $formatPercent = fn ($value) => number_format((float) $value, 1) . '%';
    $formatMoney = fn ($value) => '₱' . number_format((float) $value, 2);
    $formatHours = fn ($value) => number_format((float) $value, 2) . ' h';

    $isCustom = $data['period'] === 'custom';

    $periodTitle = $data['period'] === 'daily'
        ? $data['startDate']->format('l, F j, Y')
        : $data['startDate']->format('M j') . ' – ' . $data['endDate']->format('M j, Y');

    $rangeValue = $data['startDate']->format('Y-m-d')
        . ' to ' . $data['endDate']->format('Y-m-d');

    $utilizationPercent = min(100, max(0, (float) $summary['overall_utilization']));
@endphp

@section('content')

    <div class="mx-auto max-w-7xl">

        {{-- =========================================================
             HEADER CARD
        ========================================================== --}}
        <section class="mb-5 overflow-visible rounded-3xl border border-gray-200/80 bg-white dark:border-gray-800/70 dark:bg-[#111827]">

            <div class="flex flex-col gap-4 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">

                <div class="flex min-w-0 items-center gap-3">

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                        <i data-lucide="calendar-days" class="h-5 w-5"></i>
                    </div>

                    <div class="min-w-0">

                        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400">
                            Staff Utilization
                        </p>

                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="truncate text-sm font-extrabold text-gray-900 dark:text-white sm:text-base">
                                {{ $periodTitle }}
                            </h2>

                            <span class="inline-flex rounded-full bg-brand-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-brand-700 dark:bg-brand-900/30 dark:text-brand-400">
                                {{ $data['label'] }}
                            </span>
                        </div>

                        <p class="mt-0.5 text-[10px] text-gray-400">
                            Service time compared with recorded clocked hours.
                        </p>

                    </div>

                </div>

                <div class="flex flex-wrap items-center gap-1.5">
                    <a href="{{ route($pdfRoute, array_merge(request()->query(), ['action' => 'stream'])) }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-10 w-full items-center gap-1.5 rounded-xl bg-brand-600 px-3.5 text-xs font-bold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto">
                        <i data-lucide="file-text" class="h-4 w-4"></i>
                        Preview PDF
                    </a>

                    <a href="{{ route($pdfRoute, array_merge(request()->query(), ['action' => 'download'])) }}" class="inline-flex h-10 w-full items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 text-xs font-bold text-gray-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-brand-700 dark:hover:bg-brand-900/20 dark:hover:text-brand-300 sm:w-auto">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        Download PDF
                    </a>
                </div>

            </div>

            {{-- FILTER ROW --}}
            <form method="GET" action="{{ route($reportRoute) }}" class="border-t border-gray-100 px-5 pb-5 pt-4 dark:border-gray-800">
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">

                    <div class="w-full sm:w-auto">
                        <select id="staff-utilization-period" name="period" class="h-10 w-full cursor-pointer rounded-xl border border-gray-200 bg-gray-50 pl-9 pr-8 text-xs font-semibold text-gray-700 outline-none transition hover:border-gray-300 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900/60 dark:text-gray-200 sm:w-44">
                            <option value="daily" @selected($data['period'] === 'daily')>Daily</option>
                            <option value="weekly" @selected($data['period'] === 'weekly')>This Week</option>
                            <option value="monthly" @selected($data['period'] === 'monthly')>This Month</option>
                            <option value="yearly" @selected($data['period'] === 'yearly')>This Year</option>
                            <option value="custom" @selected($data['period'] === 'custom')>Custom Range</option>
                        </select>
                    </div>

                    <div id="staff-utilization-range-wrap" class="relative w-full sm:w-auto {{ $isCustom ? '' : 'hidden' }}">
                        <i data-lucide="calendar" class="pointer-events-none absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-gray-400"></i>

                        <input
                            id="staff-utilization-date-range"
                            type="text"
                            value="{{ $rangeValue }}"
                            placeholder="Select date range"
                            autocomplete="off"
                            readonly
                            class="staff-utilization-flatpickr h-10 w-full cursor-pointer rounded-xl border border-gray-200 bg-gray-50 pl-9 pr-8 text-xs font-semibold text-gray-700 outline-none transition hover:border-gray-300 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900/60 dark:text-gray-200 sm:w-64"
                        >
                    </div>

                    <input type="hidden" name="start_date" id="staff-utilization-start-date" value="{{ $data['startDate']->format('Y-m-d') }}" {{ $isCustom ? '' : 'disabled' }}>
                    <input type="hidden" name="end_date" id="staff-utilization-end-date" value="{{ $data['endDate']->format('Y-m-d') }}" {{ $isCustom ? '' : 'disabled' }}>

                    <button type="submit" class="inline-flex h-10 w-full items-center gap-1.5 rounded-xl bg-brand-600 px-3.5 text-xs font-bold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:w-auto">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Apply Filter
                    </button>

                    <a href="{{ route($reportRoute) }}" class="inline-flex h-10 w-full items-center justify-center gap-1.5 rounded-xl px-3.5 text-xs font-bold text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 sm:w-auto">
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        Reset
                    </a>

                </div>
            </form>

        </section>

        {{-- =========================================================
             KPI CARDS
        ========================================================== --}}
        <section class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-2xl border border-gray-200/80 bg-white p-4 dark:border-gray-800/70 dark:bg-[#111827]">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                        <i data-lucide="users" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Staff Members</p>
                        <p class="mt-1 text-xl font-extrabold leading-none text-gray-900 dark:text-white tabular-nums whitespace-nowrap">{{ $summary['staff_count'] }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200/80 bg-white p-4 dark:border-gray-800/70 dark:bg-[#111827]">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                        <i data-lucide="calendar-days" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Appointments</p>
                        <p class="mt-1 text-xl font-extrabold leading-none text-gray-900 dark:text-white tabular-nums whitespace-nowrap">{{ $summary['appointments'] }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200/80 bg-white p-4 dark:border-gray-800/70 dark:bg-[#111827]">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                        <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Completed</p>
                        <p class="mt-1 text-xl font-extrabold leading-none text-gray-900 dark:text-white tabular-nums whitespace-nowrap">{{ $summary['completed'] }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200/80 bg-white p-4 dark:border-gray-800/70 dark:bg-[#111827]">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                        <i data-lucide="clock" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Service Hours</p>
                        <p class="mt-1 text-xl font-extrabold leading-none text-gray-900 dark:text-white tabular-nums whitespace-nowrap">{{ $formatHours($summary['service_hours']) }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200/80 bg-white p-4 dark:border-gray-800/70 dark:bg-[#111827]">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                        <i data-lucide="timer" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Clocked Hours</p>
                        <p class="mt-1 text-xl font-extrabold leading-none text-gray-900 dark:text-white tabular-nums whitespace-nowrap">{{ $formatHours($summary['clocked_hours']) }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200/80 bg-white p-4 dark:border-gray-800/70 dark:bg-[#111827]">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                        <i data-lucide="gauge" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Overall Utilization</p>
                        <p class="mt-1 text-xl font-extrabold leading-none text-gray-900 dark:text-white tabular-nums whitespace-nowrap">{{ $formatPercent($summary['overall_utilization']) }}</p>
                        <div class="mt-2 h-1 w-full rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-brand-500" style="width: {{ $utilizationPercent }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200/80 bg-white p-4 dark:border-gray-800/70 dark:bg-[#111827]">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                        <i data-lucide="banknote" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Revenue</p>
                        <p class="mt-1 text-xl font-extrabold leading-none text-gray-900 dark:text-white tabular-nums whitespace-nowrap">{{ $formatMoney($summary['revenue']) }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200/80 bg-white p-4 dark:border-gray-800/70 dark:bg-[#111827]">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                        <i data-lucide="trending-up" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Revenue / Hour</p>
                        <p class="mt-1 text-xl font-extrabold leading-none text-gray-900 dark:text-white tabular-nums whitespace-nowrap">{{ $formatMoney($summary['revenue_per_hour']) }}</p>
                    </div>
                </div>
            </div>

        </section>

        {{-- =========================================================
             DETAIL
        ========================================================== --}}
        <section class="mb-5 overflow-hidden rounded-3xl border border-gray-200/80 bg-white dark:border-gray-800/70 dark:bg-[#111827]">

            <div class="flex flex-col gap-3 border-b border-gray-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                        <i data-lucide="layout-grid" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">
                        Staff Utilization Detail
                    </h2>
                </div>
            </div>

            @if($staff->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/60">
                                <th class="border-b border-gray-200 px-4 py-3 text-left dark:border-gray-800"><span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Rank</span></th>
                                <th class="border-b border-gray-200 px-4 py-3 text-left dark:border-gray-800"><span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Staff</span></th>
                                <th class="border-b border-gray-200 px-4 py-3 text-center dark:border-gray-800"><span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Appointments</span></th>
                                <th class="border-b border-gray-200 px-4 py-3 text-center dark:border-gray-800"><span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Completed</span></th>
                                <th class="border-b border-gray-200 px-4 py-3 text-right dark:border-gray-800"><span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Service Hours</span></th>
                                <th class="border-b border-gray-200 px-4 py-3 text-right dark:border-gray-800"><span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Clocked Hours</span></th>
                                <th class="border-b border-gray-200 px-4 py-3 text-center dark:border-gray-800"><span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Utilization</span></th>
                                <th class="border-b border-gray-200 px-4 py-3 text-right dark:border-gray-800"><span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Revenue</span></th>
                                <th class="border-b border-gray-200 px-4 py-3 text-right dark:border-gray-800"><span class="text-[9px] font-bold uppercase tracking-wider text-gray-400">Revenue / Hour</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($staff as $staffMember)
                                @php
                                    $efficiencyRank = (int) ($staffMember['rank'] ?? $loop->iteration);
                                    $efficiencyName = $staffMember['name'] ?? 'Unknown Staff';
                                    $efficiencyAppointments = (int) ($staffMember['appointments'] ?? 0);
                                    $serviceHours = (float) ($staffMember['service_hours'] ?? 0);
                                    $clockedHours = (float) ($staffMember['clocked_hours'] ?? 0);
                                    $utilization = max(0, (float) ($staffMember['utilization'] ?? 0));
                                    $revenue = (float) ($staffMember['revenue'] ?? 0);
                                    $revenuePerHour = (float) ($staffMember['revenue_per_hour'] ?? 0);
                                    $hasAttendance = !empty($staffMember['has_attendance']);
                                @endphp

                                <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $efficiencyRank }}</td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $efficiencyName }}
                                        @if(!$hasAttendance)
                                            <span class="ml-2 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                No Attendance
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">{{ $efficiencyAppointments }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">{{ $staffMember['completed'] ?? 0 }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ $formatHours($serviceHours) }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ $formatHours($clockedHours) }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">{{ $formatPercent($utilization) }}</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-gray-900 dark:text-white">{{ $formatMoney($revenue) }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ $formatMoney($revenuePerHour) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-8 text-center">
                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                        <i data-lucide="calendar-x" class="h-5 w-5"></i>
                    </div>

                    <h3 class="mt-3 text-sm font-bold text-gray-800 dark:text-gray-200">
                        No staff activity for this period
                    </h3>

                    <p class="mx-auto mt-1 max-w-sm text-sm text-gray-400">
                        Try a different date range.
                    </p>
                </div>
            @endif

        </section>

    </div>
@endsection

@push('scripts')

    {{-- =========================================================
         FLATPICKR RANGE (same setup as the Sales Report)
    ========================================================== --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const periodSelect = document.getElementById('staff-utilization-period');
            const rangeWrap = document.getElementById('staff-utilization-range-wrap');
            const dateRangeInput = document.getElementById('staff-utilization-date-range');
            const startDateInput = document.getElementById('staff-utilization-start-date');
            const endDateInput = document.getElementById('staff-utilization-end-date');

            const syncRangeVisibility = function () {
                const show = periodSelect && periodSelect.value === 'custom';

                if (rangeWrap) {
                    rangeWrap.classList.toggle('hidden', !show);
                }

                if (startDateInput) {
                    startDateInput.disabled = !show;
                }

                if (endDateInput) {
                    endDateInput.disabled = !show;
                }
            };

            if (periodSelect) {
                periodSelect.addEventListener('change', syncRangeVisibility);
            }

            syncRangeVisibility();

            function formatFlatpickrDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');

                return `${year}-${month}-${day}`;
            }

            if (dateRangeInput && window.flatpickr) {
                flatpickr(dateRangeInput, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'F j, Y',
                    allowInput: false,
                    conjunction: ' to ',
                    defaultDate: [
                        startDateInput?.value || null,
                        endDateInput?.value || null
                    ],
                    onChange: function (selectedDates) {
                        if (!startDateInput || !endDateInput) {
                            return;
                        }

                        if (selectedDates.length >= 1) {
                            startDateInput.value = formatFlatpickrDate(selectedDates[0]);
                        }

                        if (selectedDates.length >= 2) {
                            endDateInput.value = formatFlatpickrDate(selectedDates[1]);
                        }
                    }
                });
            }
        });
    </script>
@endpush
