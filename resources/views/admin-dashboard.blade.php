@extends('layouts.admin')

@section('title', 'Dashboard')

@push('styles')
<style>
    /* ── Dashboard-local styles ────────────────────────────────────── */

    /* KPI ribbon */
    .kpi-card {
        position: relative;
        overflow: hidden;
    }
    .kpi-card::after {
        content: '';
        position: absolute;
        top: -20px; right: -20px;
        width: 80px; height: 80px;
        border-radius: 50%;
        opacity: 0.07;
        background: currentColor;
    }

    /* Status badge pill */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: capitalize;
        white-space: nowrap;
    }

    /* Module card hover lift */
    .module-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .module-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(20, 184, 166, 0.1);
    }

    /* Chart containers */
    .chart-wrap { position: relative; height: 200px; }

    /* Table row hover */
    .feed-row { transition: background 0.1s ease; }
    .feed-row:hover { background: rgba(20, 184, 166, 0.04); }
    .dark .feed-row:hover { background: rgba(20, 184, 166, 0.06); }

    /* Section label */
    .section-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }
</style>
@endpush

@section('content')

@php
    $hour = now()->format('H');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = auth()->user()->first_name ?? 'Admin';
    $todayLabel = now()->format('l, F j, Y');

    $statusConfig = [
        'pending'   => ['label' => 'Pending',   'dot' => '#f59e0b', 'bg' => 'bg-amber-100  dark:bg-amber-900/30',  'text' => 'text-amber-700 dark:text-amber-400'],
        'confirmed' => ['label' => 'Confirmed',  'dot' => '#14b8a6', 'bg' => 'bg-teal-100   dark:bg-teal-900/30',   'text' => 'text-teal-700  dark:text-teal-400'],
        'completed' => ['label' => 'Completed',  'dot' => '#22c55e', 'bg' => 'bg-green-100  dark:bg-green-900/30',  'text' => 'text-green-700 dark:text-green-400'],
        'cancelled' => ['label' => 'Cancelled',  'dot' => '#94a3b8', 'bg' => 'bg-slate-100  dark:bg-slate-800',     'text' => 'text-slate-600 dark:text-slate-400'],
        'no_show'   => ['label' => 'No Show',    'dot' => '#f43f5e', 'bg' => 'bg-rose-100   dark:bg-rose-900/30',   'text' => 'text-rose-700  dark:text-rose-400'],
    ];
@endphp

{{-- ═══════════════════════════════════════════════════════════
     PAGE HERO — greeting + date + summary line
════════════════════════════════════════════════════════════════ --}}
<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2.5 mb-1">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center shadow shadow-brand-500/25 shrink-0">
                <i data-lucide="layout-dashboard" class="w-4 h-4 text-white"></i>
            </div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-600 dark:text-brand-400">
                Administration
            </p>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight leading-tight">
            {{ $greeting }}, {{ $firstName }}
        </h2>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
            {{ $todayLabel }} &mdash;
            @if($todayAppointments > 0)
                {{ $todayAppointments }} active appointment{{ $todayAppointments !== 1 ? 's' : '' }} scheduled today
            @else
                No appointments scheduled for today
            @endif
        </p>
    </div>
    <a href="{{ route('admin.appointments') }}"
       class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow shadow-brand-500/20 transition shrink-0">
        <i data-lucide="calendar-check-2" class="w-4 h-4"></i>
        View All Appointments
    </a>
</div>

{{-- ═══════════════════════════════════════════════════════════
     KPI RIBBON — 5 tiles, 2-col mobile, 5-col desktop
════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-8">

    {{-- Revenue --}}
    <div class="kpi-card bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm">
        <div class="flex items-start justify-between mb-3">
            <div class="w-9 h-9 rounded-xl bg-brand-50 dark:bg-brand-900/30 flex items-center justify-center">
                <i data-lucide="banknote" class="w-4 h-4 text-brand-600 dark:text-brand-400"></i>
            </div>
            <span class="section-label text-gray-400 dark:text-gray-500">Today</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">
            ₱{{ number_format($todayRevenue, 0) }}
        </p>
        <p class="mt-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">Revenue collected</p>
    </div>

    {{-- Active Appointments --}}
    <div class="kpi-card bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm">
        <div class="flex items-start justify-between mb-3">
            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                <i data-lucide="calendar-days" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
            </div>
            <span class="section-label text-gray-400 dark:text-gray-500">Today</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">{{ $todayAppointments }}</p>
        <p class="mt-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">
            {{ $completedToday }} completed
        </p>
    </div>

    {{-- Staff Scheduled --}}
    <div class="kpi-card bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm">
        <div class="flex items-start justify-between mb-3">
            <div class="w-9 h-9 rounded-xl bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center">
                <i data-lucide="users" class="w-4 h-4 text-violet-600 dark:text-violet-400"></i>
            </div>
            <span class="section-label text-gray-400 dark:text-gray-500">Active</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">{{ $staffOnDuty }}</p>
        <p class="mt-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">
            of {{ $totalStaff }} staff scheduled
        </p>
    </div>

    {{-- No Shows --}}
    <div class="kpi-card bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm">
        <div class="flex items-start justify-between mb-3">
            <div class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center">
                <i data-lucide="calendar-x-2" class="w-4 h-4 text-rose-600 dark:text-rose-400"></i>
            </div>
            <span class="section-label text-gray-400 dark:text-gray-500">Today</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">{{ $todayNoShows }}</p>
        <p class="mt-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">No shows recorded</p>
    </div>

    {{-- Pending Confirmations (replaces broken "Deposits Held") --}}
    <div class="kpi-card bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm sm:col-span-1 col-span-2">
        <div class="flex items-start justify-between mb-3">
            <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                <i data-lucide="inbox" class="w-4 h-4 text-amber-600 dark:text-amber-400"></i>
            </div>
            @if($pendingConfirmations > 0)
                <span class="section-label text-amber-600 dark:text-amber-400">Needs action</span>
            @else
                <span class="section-label text-gray-400 dark:text-gray-500">All clear</span>
            @endif
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">{{ $pendingConfirmations }}</p>
        <p class="mt-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">
            <a href="{{ route('admin.appointments', ['status' => 'pending']) }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition underline-offset-2 hover:underline">
                Pending confirmations
            </a>
        </p>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════
     ANALYTICS ROW — Revenue trend + Status breakdown
════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-8">

    {{-- 7-Day Revenue Bar Chart (3/5 width on desktop) --}}
    <div class="lg:col-span-3 bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="section-label text-gray-400 dark:text-gray-500 mb-0.5">Revenue</p>
                <h3 class="text-sm font-bold text-gray-800 dark:text-white">7-Day Trend</h3>
            </div>
            <div class="w-8 h-8 rounded-xl bg-brand-50 dark:bg-brand-900/30 flex items-center justify-center">
                <i data-lucide="chart-column" class="w-4 h-4 text-brand-600 dark:text-brand-400"></i>
            </div>
        </div>
        <div class="chart-wrap">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    {{-- Today's Appointment Status Donut (2/5 width on desktop) --}}
    <div class="lg:col-span-2 bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="section-label text-gray-400 dark:text-gray-500 mb-0.5">Today</p>
                <h3 class="text-sm font-bold text-gray-800 dark:text-white">Appointment Status</h3>
            </div>
            <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                <i data-lucide="activity" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
            </div>
        </div>

        @php
            $totalForChart = $statusBreakdown->sum();
        @endphp

        @if($totalForChart > 0)
            <div class="chart-wrap">
                <canvas id="statusChart"></canvas>
            </div>
            <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1.5">
                @foreach($statusConfig as $key => $cfg)
                    @if(($statusBreakdown[$key] ?? 0) > 0)
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $cfg['dot'] }}"></span>
                            <span class="text-[11px] font-semibold text-gray-600 dark:text-gray-400">
                                {{ $cfg['label'] }} ({{ $statusBreakdown[$key] }})
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center h-40 text-center">
                <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-700/50 flex items-center justify-center mb-3">
                    <i data-lucide="calendar" class="w-5 h-5 text-gray-300 dark:text-gray-600"></i>
                </div>
                <p class="text-sm font-semibold text-gray-400 dark:text-gray-500">No appointments today</p>
            </div>
        @endif
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════
     TODAY'S APPOINTMENT FEED
════════════════════════════════════════════════════════════════ --}}
<div class="bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl shadow-sm mb-8 overflow-hidden">

    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700/50">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-brand-50 dark:bg-brand-900/30 flex items-center justify-center">
                <i data-lucide="list-checks" class="w-4 h-4 text-brand-600 dark:text-brand-400"></i>
            </div>
            <div>
                <p class="section-label text-gray-400 dark:text-gray-500">Schedule</p>
                <h3 class="text-sm font-bold text-gray-800 dark:text-white leading-tight">Today's Appointments</h3>
            </div>
        </div>
        <a href="{{ route('admin.appointments') }}"
           class="text-xs font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition flex items-center gap-1">
            View all
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        </a>
    </div>

    @if($todayFeed->isEmpty())
        <div class="flex flex-col items-center justify-center py-14 text-center">
            <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-700/50 flex items-center justify-center mb-4">
                <i data-lucide="calendar" class="w-6 h-6 text-gray-300 dark:text-gray-600"></i>
            </div>
            <p class="text-sm font-bold text-gray-500 dark:text-gray-400">No appointments scheduled today</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Bookings will appear here once confirmed</p>
        </div>
    @else
        {{-- Desktop table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-700/50">
                        <th class="text-left px-5 py-3 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider hidden md:table-cell">Services</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider hidden lg:table-cell">Therapist</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider hidden lg:table-cell">Room</th>
                        <th class="text-left px-4 py-3 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/40">
                    @foreach($todayFeed as $appt)
                        @php
                            $sc = $statusConfig[$appt->status] ?? $statusConfig['pending'];
                        @endphp
                        <tr class="feed-row">
                            <td class="px-5 py-3.5 text-xs font-bold text-gray-700 dark:text-gray-300 tabular-nums whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($appt->start_time)->format('g:i A') }}
                                <span class="font-normal text-gray-400 dark:text-gray-500">–</span>
                                {{ \Carbon\Carbon::parse($appt->end_time)->format('g:i A') }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-semibold text-gray-800 dark:text-gray-200 text-sm">
                                    {{ $appt->client_name }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 hidden md:table-cell">
                                <span class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $appt->services->map(fn($s) => $s->pivot->service_name ?: $s->name)->filter()->join(', ') ?: '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 hidden lg:table-cell">
                                <span class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $appt->staff ? trim($appt->staff->first_name . ' ' . $appt->staff->last_name) : '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 hidden lg:table-cell">
                                <span class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $appt->room->name ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="status-pill {{ $sc['bg'] }} {{ $sc['text'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background: {{ $sc['dot'] }}"></span>
                                    {{ $sc['label'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile card list --}}
        <div class="sm:hidden divide-y divide-gray-100 dark:divide-gray-700/50">
            @foreach($todayFeed as $appt)
                @php $sc = $statusConfig[$appt->status] ?? $statusConfig['pending']; @endphp
                <div class="px-4 py-3.5 flex items-center gap-3">
                    <div class="shrink-0 text-right">
                        <p class="text-xs font-bold text-gray-700 dark:text-gray-300 tabular-nums">
                            {{ \Carbon\Carbon::parse($appt->start_time)->format('g:i') }}
                        </p>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500">
                            {{ \Carbon\Carbon::parse($appt->start_time)->format('A') }}
                        </p>
                    </div>
                    <div class="w-px h-8 bg-gray-200 dark:bg-gray-700 shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-800 dark:text-gray-200 truncate">
                            {{ $appt->client_name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ $appt->staff ? trim($appt->staff->first_name . ' ' . $appt->staff->last_name) : 'Unassigned' }}
                        </p>
                    </div>
                    <span class="status-pill shrink-0 {{ $sc['bg'] }} {{ $sc['text'] }}">
                        {{ $sc['label'] }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif

</div>

{{-- ═══════════════════════════════════════════════════════════
     QUICK ACCESS GRID — all 12 admin modules, uniform design
════════════════════════════════════════════════════════════════ --}}
<div class="mb-2">
    <div class="flex items-center gap-2 mb-4">
        <div class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-gray-700/60 flex items-center justify-center">
            <i data-lucide="layout-grid" class="w-4 h-4 text-gray-500 dark:text-gray-400"></i>
        </div>
        <div>
            <p class="section-label text-gray-400 dark:text-gray-500">Modules</p>
            <h3 class="text-sm font-bold text-gray-800 dark:text-white leading-tight">Quick Access</h3>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">

        @php
        $modules = [
            ['route' => 'admin.appointments',      'icon' => 'calendar-check-2', 'label' => 'Appointment Report',  'desc' => 'View and manage all bookings'],
            ['route' => 'admin.sales',             'icon' => 'chart-column',     'label' => 'Sales Report',         'desc' => 'Revenue analytics and transactions'],
            ['route' => 'admin.schedules',         'icon' => 'calendar-days',    'label' => 'Therapist Shifts',     'desc' => 'Shift templates and schedules'],
            ['route' => 'attendance.today',        'icon' => 'clipboard-check',  'label' => 'Attendance',           'desc' => 'Daily attendance and clock-in log'],
            ['route' => 'admin.room-tracking',     'icon' => 'door-open',        'label' => 'Room Tracking',        'desc' => 'Real-time room occupancy'],
            ['route' => 'admin.service-popularity','icon' => 'activity',         'label' => 'Service Popularity',   'desc' => 'Most-booked treatments'],
            ['route' => 'admin.appointment-invoice','icon' => 'receipt-text',    'label' => 'Invoices',             'desc' => 'Appointment invoices and billing'],
            ['route' => 'admin.services.index',    'icon' => 'sparkles',         'label' => 'Services',             'desc' => 'Catalog, categories, pricing'],
            ['route' => 'admin.rooms.index',       'icon' => 'layers-3',         'label' => 'Rooms',                'desc' => 'Treatment rooms and settings'],
            ['route' => 'admin.users.index',       'icon' => 'users',            'label' => 'Users',                'desc' => 'Accounts, roles, permissions'],
            ['route' => 'admin.landing.editor',    'icon' => 'house',            'label' => 'Landing Page',         'desc' => 'Website editor and showcase'],
            ['route' => 'business-hours.index',    'icon' => 'clock',            'label' => 'Business Hours',       'desc' => 'Hours, holidays and closures'],
        ];
        @endphp

        @foreach($modules as $mod)
            <a href="{{ route($mod['route']) }}"
               class="module-card group bg-white dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/50 rounded-2xl p-4 shadow-sm hover:border-brand-300 dark:hover:border-brand-700 flex flex-col gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-700/60 flex items-center justify-center group-hover:bg-brand-50 dark:group-hover:bg-brand-900/30 transition-colors">
                    <i data-lucide="{{ $mod['icon'] }}"
                       class="w-4 h-4 text-gray-500 dark:text-gray-400 group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors"></i>
                </div>
                <div>
                    <p class="text-[13px] font-bold text-gray-800 dark:text-gray-200 leading-tight group-hover:text-brand-700 dark:group-hover:text-brand-300 transition-colors">
                        {{ $mod['label'] }}
                    </p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-500 mt-0.5 leading-snug">
                        {{ $mod['desc'] }}
                    </p>
                </div>
            </a>
        @endforeach

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    /* ── Shared chart globals (matches sales.blade.php conventions) ── */
    const isDark      = document.documentElement.classList.contains('dark');
    const gridColor   = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
    const textColor   = isDark ? '#a3a3a3' : '#6b7280';
    const tooltipBg   = isDark ? 'rgba(23,23,23,0.97)' : 'rgba(255,255,255,0.98)';
    const tooltipText = isDark ? '#f5f5f5' : '#171717';
    const tooltipBorder = isDark ? 'rgba(64,64,64,0.8)' : 'rgba(0,0,0,0.08)';
    const barColor    = isDark ? '#14b8a6' : '#0d9488';

    if (window.Chart) {
        Chart.defaults.color      = textColor;
        Chart.defaults.borderColor = gridColor;
        Chart.defaults.font.family = "'Inter', 'Segoe UI', system-ui, sans-serif";
        Chart.defaults.font.size   = 11;
    }

    /* ── Revenue Bar Chart ─────────────────────────────────────────── */
    const revenueCanvas = document.getElementById('revenueChart');
    if (revenueCanvas && window.Chart) {
        const raw = @json($revenueChart);
        new Chart(revenueCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: raw.map(d => d.label),
                datasets: [{
                    label: 'Revenue',
                    data:  raw.map(d => d.value),
                    backgroundColor: barColor,
                    borderRadius: 7,
                    borderSkipped: false,
                    barPercentage: 0.55,
                    categoryPercentage: 0.78
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 600 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: false,
                        callbacks: {
                            label: ctx => '₱' + Number(ctx.parsed.y || 0).toLocaleString(undefined, {
                                minimumFractionDigits: 2, maximumFractionDigits: 2
                            })
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor, drawBorder: false },
                        ticks: {
                            callback: v => '₱' + Number(v).toLocaleString()
                        }
                    }
                }
            }
        });
    }

    /* ── Status Donut Chart ────────────────────────────────────────── */
    const statusCanvas = document.getElementById('statusChart');
    if (statusCanvas && window.Chart) {
        const breakdown = @json($statusBreakdown);
        const config = {
            pending:   { label: 'Pending',   color: '#f59e0b' },
            confirmed: { label: 'Confirmed',  color: '#14b8a6' },
            completed: { label: 'Completed',  color: '#22c55e' },
            cancelled: { label: 'Cancelled',  color: '#94a3b8' },
            no_show:   { label: 'No Show',    color: '#f43f5e' },
        };
        const keys    = Object.keys(config).filter(k => (breakdown[k] || 0) > 0);
        const labels  = keys.map(k => config[k].label);
        const data    = keys.map(k => breakdown[k] || 0);
        const colors  = keys.map(k => config[k].color);
        const total   = data.reduce((a, b) => a + b, 0);

        new Chart(statusCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 6,
                }]
            },
            plugins: [{
                id: 'centerText',
                afterDraw(chart) {
                    const { ctx, chartArea: { top, bottom, left, right } } = chart;
                    const cx = (left + right) / 2, cy = (top + bottom) / 2;
                    ctx.save();
                    ctx.font = 'bold 22px Inter, sans-serif';
                    ctx.fillStyle = isDark ? '#f1f5f9' : '#1e293b';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(total, cx, cy - 8);
                    ctx.font = '500 10px Inter, sans-serif';
                    ctx.fillStyle = isDark ? '#94a3b8' : '#6b7280';
                    ctx.fillText('Total', cx, cy + 11);
                    ctx.restore();
                }
            }],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                animation: { duration: 600 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.raw}`
                        }
                    }
                }
            }
        });
    }

    /* ── Lucide icon init ──────────────────────────────────────────── */
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
})();
</script>
@endpush