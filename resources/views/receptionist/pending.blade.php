@extends('layouts.receptionist')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" id="pendingApp">

    {{-- Stats Row --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/30">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                </span>
                <span class="text-xs font-bold text-amber-700 dark:text-amber-400" id="pendingCount">{{ $pendingCount }} pending</span>
            </div>
            @if($urgentCount > 0)
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/30">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <span class="text-xs font-bold text-red-700 dark:text-red-400">{{ $urgentCount }} urgent</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    @if($appointments->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 flex flex-col sm:flex-row gap-3 shadow-sm">
        <div class="relative flex-1">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="searchInput" placeholder="Search name or phone..."
                class="w-full pl-9 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition">
        </div>
        <div class="flex gap-2">
            <select id="dateFilter" class="pl-3 pr-8 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none bg-white">
                <option value="all">All Dates</option>
                <option value="today">Today</option>
                <option value="tomorrow">Tomorrow</option>
                <option value="week">Next 7 Days</option>
            </select>
            <select id="sortBy" class="pl-3 pr-8 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none bg-white">
                <option value="date_asc">Soonest First</option>
                <option value="date_desc">Latest First</option>
            </select>
        </div>
    </div>
    @endif

    {{-- Appointments --}}
    <div id="appointmentsList" class="space-y-4">
        @forelse($appointments as $appointment)
            @php
                $meta = $appointmentMeta[$appointment->id];
                $apptStaffMap = $staffAvailability[$appointment->id] ?? collect();
                $hasAvailableStaff = $apptStaffMap->where('available', true)->isNotEmpty();

                $u = $appointment->computed_urgency;
                $isOverdue = $u['is_overdue'];
                $isSoon = $u['is_soon'];
                $isToday = $u['is_today'];
                $apptDateTime = $u['datetime'];
                $dateStr = $u['date_string'];

                $urgencyClass = match(true) {
                    $isOverdue => 'border-l-red-500 bg-red-50/30 dark:bg-red-900/5',
                    $isSoon => 'border-l-amber-500 bg-amber-50/30 dark:bg-amber-900/5',
                    $isToday => 'border-l-teal-500',
                    default => 'border-l-gray-300 dark:border-l-gray-600'
                };

                $urgencyBadge = match(true) {
                    $isOverdue => ['bg-red-100 text-red-700 border-red-200', 'Overdue ' . $apptDateTime->diffForHumans()],
                    $isSoon => ['bg-amber-100 text-amber-700 border-amber-200', 'Starting ' . $apptDateTime->diffForHumans()],
                    $isToday => ['bg-teal-100 text-teal-700 border-teal-200', 'Today'],
                    default => ['bg-gray-100 text-gray-600 border-gray-200', $apptDateTime->diffForHumans()]
                };

                $initials = collect(explode(' ', $appointment->customer->full_name))
                    ->map(fn($n) => strtoupper(substr($n, 0, 1)))->filter()->take(2)->join('');
            @endphp

            <article class="appointment-card group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border-l-4 {{ $urgencyClass }}"
                     data-id="{{ $appointment->id }}"
                     data-customer="{{ strtolower($appointment->customer->full_name) }}"
                     data-phone="{{ $appointment->customer->phone_number }}"
                     data-date="{{ $dateStr }}"
                     data-timestamp="{{ $apptDateTime->timestamp }}"
                     data-cancel-url="{{ route('receptionist.cancel', $appointment) }}">

                {{-- Header --}}
                <div class="p-5 cursor-pointer" onclick="toggleDetails({{ $appointment->id }})">
                    <div class="flex items-center gap-4">
                        <div class="shrink-0 w-12 h-12 rounded-full bg-gradient-to-br from-teal-400 to-blue-500 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                            {{ $initials ?: '?' }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold border uppercase tracking-wide {{ $urgencyBadge[0] }}">
                                    {{ $urgencyBadge[1] }}
                                </span>
                                <span class="text-[11px] font-mono text-gray-400">#{{ $appointment->id }}</span>
                                @if(!empty($meta['hasDepositRequired']))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/30 text-[11px] font-bold text-amber-700 dark:text-amber-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Deposit {{ $meta['maxDepositPercent'] ?? 0 }}%
                                </span>
                                @endif
                            </div>

                            <h3 class="text-base font-bold text-gray-900 dark:text-white truncate">
                                {{ $appointment->customer->full_name }}
                            </h3>

                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-sm text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ $apptDateTime->format('M j, Y') }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $apptDateTime->format('g:i A') }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    {{ $appointment->services->count() }} service{{ $appointment->services->count() !== 1 ? 's' : '' }}
                                </span>
                            </div>
                        </div>

                        <div class="shrink-0 flex items-center gap-2">
                            <a href="tel:{{ $appointment->customer->phone_number }}"
                               onclick="event.stopPropagation()"
                               class="p-2 rounded-lg bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 hover:bg-teal-100 transition-colors"
                               title="Call {{ $appointment->customer->phone_number }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </a>
                            <div class="w-8 h-8 rounded-full bg-gray-50 dark:bg-gray-700 flex items-center justify-center transition-transform duration-200" id="chevron-{{ $appointment->id }}">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Expandable Details --}}
                <div id="details-{{ $appointment->id }}" class="hidden border-t border-gray-100 dark:border-gray-700/50">
                    <form id="confirm-form-{{ $appointment->id }}" action="{{ route('receptionist.confirm', $appointment) }}" method="POST" class="p-5 space-y-6">
                        @csrf

                        {{-- Services --}}
                        <div>
                            <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Services & Pricing</h4>
                            <div class="bg-gray-50 dark:bg-gray-900/30 rounded-xl p-4 space-y-3">
                                @foreach($appointment->services as $service)
                                    @php
                                        $depositPercent = $service->deposit_percentage_min ?? $service->category?->deposit_percentage_min ?? 0;
                                    @endphp
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center text-gray-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $service->name }}</p>
                                                <p class="text-xs text-gray-500">{{ $service->duration_minutes }} min</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-gray-900 dark:text-white">₱{{ number_format($service->pivot->price_at_booking, 2) }}</p>
                                            @if($depositPercent > 0)
                                                <p class="text-[10px] text-amber-600 font-medium">{{ $depositPercent }}% deposit</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                                <div class="pt-3 border-t border-gray-200 dark:border-gray-700/50 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-gray-500">Total Duration</p>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $appointment->services->sum('duration_minutes') }} min</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500">Total Price</p>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">₱{{ number_format($appointment->total_price, 2) }}</p>
                                    </div>
                                </div>
                                @if(!empty($meta['hasDepositRequired']))
                                <div class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-900/15 rounded-lg border border-amber-200 dark:border-amber-800/30">
                                    <span class="text-xs font-bold text-amber-700 dark:text-amber-400">Minimum Deposit Required</span>
                                    <span class="text-sm font-bold text-amber-700 dark:text-amber-400">₱{{ number_format($meta['totalDepositRequired'], 2) }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        @if($appointment->customer->medical_notes)
                        <div class="p-4 bg-rose-50 dark:bg-rose-900/15 border border-rose-200 dark:border-rose-800/30 rounded-xl flex items-start gap-3">
                            <div class="shrink-0 w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-rose-700 dark:text-rose-300 uppercase tracking-wider mb-1">Medical Notes</p>
                                <p class="text-sm text-rose-800 dark:text-rose-200 leading-relaxed">{{ $appointment->customer->medical_notes }}</p>
                            </div>
                        </div>
                        @endif

                        {{-- Staff Grid --}}
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Assign Staff <span class="text-red-500">*</span></h4>
                                @if(!$hasAvailableStaff)
                                    <span class="text-xs font-bold text-red-600 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        No availability
                                    </span>
                                @endif
                            </div>

                            @if($hasAvailableStaff)
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                                    @foreach($allStaff as $staff)
                                        @php
                                            $staffInfo = $apptStaffMap->get($staff->id);
                                            $isAvailable = $staffInfo && $staffInfo['available'];
                                            $statusLabel = $staffInfo ? $staffInfo['status_label'] : 'Not Scheduled';
                                            $hours = $staffInfo ? ($staffInfo['hours'] ?? '') : '';
                                            $isOnlyAvailable = $isAvailable && $apptStaffMap->where('available', true)->count() === 1;
                                        @endphp
                                        <label class="relative cursor-pointer {{ $isAvailable ? '' : 'opacity-50 cursor-not-allowed' }}">
                                            <input type="radio" name="staff_id" value="{{ $staff->id }}"
                                                {{ $isAvailable ? '' : 'disabled' }}
                                                class="peer sr-only"
                                                onchange="updateStaffSelection(this, {{ $appointment->id }})"
                                                {{ $isOnlyAvailable ? 'checked' : '' }}>
                                            <div class="p-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-teal-500 peer-checked:bg-teal-50 dark:peer-checked:bg-teal-900/20 hover:border-gray-300 dark:hover:border-gray-500 transition-all text-center h-full flex flex-col items-center justify-center {{ $isOnlyAvailable ? 'ring-2 ring-teal-500 ring-offset-2 dark:ring-offset-gray-800' : '' }}">
                                                <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 font-bold text-sm mb-2">
                                                    {{ collect(explode(' ', $staff->full_name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('') }}
                                                </div>
                                                <p class="text-xs font-bold text-gray-900 dark:text-white truncate w-full">{{ $staff->full_name }}</p>
                                                <p class="text-[10px] font-medium mt-1 {{ $isAvailable ? 'text-teal-600 dark:text-teal-400' : 'text-red-500' }}">
                                                    {{ $isAvailable ? 'Available' : $statusLabel }}
                                                </p>
                                                @if($hours)
                                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $hours }}</p>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-6 rounded-xl bg-red-50 dark:bg-red-900/15 border border-red-200 dark:border-red-800/30 text-center">
                                    <svg class="w-10 h-10 text-red-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="text-sm font-bold text-red-700 dark:text-red-300">No Staff Available</p>
                                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">Consider rescheduling or checking the schedule.</p>
                                </div>
                            @endif
                        </div>

                        {{-- Room Assignment --}}
                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/30 border border-gray-200 dark:border-gray-600/50">
                            <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Room Assignment</h4>
                            <div id="room-loading-{{ $appointment->id }}" class="flex items-center gap-2 text-sm text-gray-500 py-4 justify-center">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                Checking room requirements...
                            </div>
                            <div id="room-options-{{ $appointment->id }}" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 hidden"></div>
                            <div id="room-message-{{ $appointment->id }}" class="text-sm text-amber-600 dark:text-amber-400 text-center py-2 hidden"></div>
                            <input type="hidden" name="room_id" id="room-id-{{ $appointment->id }}" value="">
                        </div>

                        {{-- Payment --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Payment Type <span class="text-red-500">*</span></h4>
                                <div class="flex flex-wrap gap-2">
                                    @if(empty($meta['hasDepositRequired']))
                                        <button type="button" onclick="setPaymentType({{ $appointment->id }}, 'cash_on_site', {{ $appointment->total_price }}, {{ $meta['totalDepositRequired'] ?? 0 }})"
                                            class="payment-btn-{{ $appointment->id }} px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-600 text-sm font-bold text-gray-700 dark:text-gray-200 hover:border-teal-500 transition-colors"
                                            data-type="cash_on_site">
                                            Cash on Site
                                        </button>
                                    @endif
                                    <button type="button" onclick="setPaymentType({{ $appointment->id }}, 'deposit', {{ $appointment->total_price }}, {{ $meta['totalDepositRequired'] ?? 0 }})"
                                        class="payment-btn-{{ $appointment->id }} px-4 py-2.5 rounded-xl border-2 text-sm font-bold transition-colors {{ !empty($meta['hasDepositRequired']) ? 'border-teal-500 text-teal-600 bg-teal-50 dark:bg-teal-900/20' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:border-teal-500' }}"
                                        data-type="deposit">
                                        Deposit
                                    </button>
                                    <button type="button" onclick="setPaymentType({{ $appointment->id }}, 'full', {{ $appointment->total_price }}, {{ $meta['totalDepositRequired'] ?? 0 }})"
                                        class="payment-btn-{{ $appointment->id }} px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-600 text-sm font-bold text-gray-700 dark:text-gray-200 hover:border-teal-500 transition-colors"
                                        data-type="full">
                                        Full Payment
                                    </button>
                                </div>
                                <input type="hidden" name="payment_type" id="paymentType_{{ $appointment->id }}" value="{{ !empty($meta['hasDepositRequired']) ? 'deposit' : 'cash_on_site' }}">
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Amount (₱) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">₱</span>
                                        <input type="number" name="amount" id="amount_{{ $appointment->id }}"
                                            step="0.01" min="0" required
                                            value="{{ !empty($meta['hasDepositRequired']) ? ($meta['totalDepositRequired'] ?? 0) : '0.00' }}"
                                            class="w-full pl-8 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white text-sm font-bold focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition">
                                    </div>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5" id="amountHint_{{ $appointment->id }}">
                                        {{ !empty($meta['hasDepositRequired']) ? 'Minimum deposit: ₱' . number_format($meta['totalDepositRequired'] ?? 0, 2) : 'Customer will pay at the counter' }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Method <span class="text-red-500">*</span></label>
                                    <select name="payment_method" required
                                        class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition bg-white">
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="gcash">GCash</option>
                                        <option value="paymaya">PayMaya</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Receptionist Notes <span class="text-gray-300 dark:text-gray-600 font-normal">(optional)</span></label>
                            <textarea name="notes" rows="2" placeholder="Special requests, preferences, parking needs..."
                                class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition resize-none"></textarea>
                        </div>
                    </form>
                </div>

                {{-- Footer Actions --}}
                <div class="px-5 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/50 flex flex-wrap items-center gap-3">
                    <button type="submit" form="confirm-form-{{ $appointment->id }}"
                        class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition shadow-sm hover:shadow-md active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
                        {{ $hasAvailableStaff ? '' : 'disabled' }}>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        Confirm Booking
                    </button>

                    <button type="button" onclick="openCancelModal({{ $appointment->id }})"
                        class="ml-auto inline-flex items-center gap-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 px-4 py-2.5 rounded-xl transition text-sm font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Cancel
                    </button>
                </div>
            </article>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-12 text-center">
                <div class="w-16 h-16 bg-emerald-50 dark:bg-emerald-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">All Caught Up</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">No bookings awaiting confirmation.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($appointments->hasPages())
    <div class="pt-2">
        {{ $appointments->links() }}
    </div>
    @endif
</div>

{{-- Cancel Modal --}}
<div id="cancelModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity opacity-0" id="cancelModalBackdrop" onclick="closeCancelModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 transform transition-all scale-95 opacity-0 pointer-events-auto" id="cancelModalContent">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Cancel Booking</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">This action cannot be undone</p>
                </div>
            </div>

            <form id="cancelForm" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Cancellation Reason <span class="text-red-500">*</span></label>
                    <select name="reason" required id="cancelReason" onchange="toggleOtherReason(this)"
                        class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none bg-white transition">
                        <option value="">Select a reason...</option>
                        <option value="customer_cancelled">Customer Cancelled</option>
                        <option value="staff_unavailable">Staff Unavailable</option>
                        <option value="other">Other Reason</option>
                    </select>
                </div>
                <div id="otherReasonContainer" class="hidden">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Please Specify</label>
                    <textarea name="other_reason" rows="2" placeholder="Enter details..."
                        class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition resize-none"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeCancelModal()" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Keep Booking</button>
                    <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-bold transition shadow-sm">Cancel Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDetails(id) {
    const details = document.getElementById('details-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const isHidden = details.classList.contains('hidden');

    if (isHidden) {
        details.classList.remove('hidden');
        chevron.classList.add('rotate-180', 'bg-teal-50', 'dark:bg-teal-900/20', 'text-teal-600');
        chevron.classList.remove('bg-gray-50', 'dark:bg-gray-700', 'text-gray-400');
    } else {
        details.classList.add('hidden');
        chevron.classList.remove('rotate-180', 'bg-teal-50', 'dark:bg-teal-900/20', 'text-teal-600');
        chevron.classList.add('bg-gray-50', 'dark:bg-gray-700', 'text-gray-400');
    }
}

function updateStaffSelection(radio, appointmentId) {
    const container = radio.closest('.grid');
    container.querySelectorAll('input[name="staff_id"]').forEach(input => {
        input.closest('label').querySelector('div').classList.remove('ring-2', 'ring-teal-500', 'ring-offset-2', 'dark:ring-offset-gray-800');
    });
    radio.closest('label').querySelector('div').classList.add('ring-2', 'ring-teal-500', 'ring-offset-2', 'dark:ring-offset-gray-800');
}

function setPaymentType(id, type, total, deposit) {
    document.getElementById('paymentType_' + id).value = type;

    document.querySelectorAll('.payment-btn-' + id).forEach(btn => {
        if (btn.dataset.type === type) {
            btn.classList.add('border-teal-500', 'text-teal-600', 'bg-teal-50', 'dark:bg-teal-900/20');
            btn.classList.remove('border-gray-200', 'dark:border-gray-600', 'text-gray-700', 'dark:text-gray-200');
        } else {
            btn.classList.remove('border-teal-500', 'text-teal-600', 'bg-teal-50', 'dark:bg-teal-900/20');
            btn.classList.add('border-gray-200', 'dark:border-gray-600', 'text-gray-700', 'dark:text-gray-200');
        }
    });

    const amountInput = document.getElementById('amount_' + id);
    const hint = document.getElementById('amountHint_' + id);

    if (type === 'deposit') {
        amountInput.value = deposit.toFixed(2);
        amountInput.readOnly = false;
        amountInput.focus();
        if (hint) hint.textContent = 'Minimum deposit: ₱' + deposit.toFixed(2);
    } else if (type === 'full') {
        amountInput.value = total.toFixed(2);
        amountInput.readOnly = false;
        amountInput.focus();
        if (hint) hint.textContent = 'Full payment of ₱' + total.toFixed(2) + ' required';
    } else if (type === 'cash_on_site') {
        amountInput.value = '0.00';
        amountInput.readOnly = true;
        if (hint) hint.textContent = 'Customer will pay at the counter. No payment recorded now.';
    }
}

let currentCancelUrl = '';

function openCancelModal(appointmentId) {
    const card = document.querySelector(`[data-id="${appointmentId}"]`);
    currentCancelUrl = card.dataset.cancelUrl;
    document.getElementById('cancelForm').action = currentCancelUrl;

    const modal = document.getElementById('cancelModal');
    const backdrop = document.getElementById('cancelModalBackdrop');
    const content = document.getElementById('cancelModalContent');

    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        backdrop.classList.remove('opacity-0');
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    });
}

function closeCancelModal() {
    const modal = document.getElementById('cancelModal');
    const backdrop = document.getElementById('cancelModalBackdrop');
    const content = document.getElementById('cancelModalContent');

    backdrop.classList.add('opacity-0');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('cancelReason').value = '';
        document.getElementById('otherReasonContainer').classList.add('hidden');
    }, 200);
}

function toggleOtherReason(select) {
    document.getElementById('otherReasonContainer').classList.toggle('hidden', select.value !== 'other');
}

// Search, Filter, Sort
const searchInput = document.getElementById('searchInput');
const dateFilter = document.getElementById('dateFilter');
const sortBy = document.getElementById('sortBy');

function filterAppointments() {
    const term = searchInput?.value.toLowerCase() || '';
    const filter = dateFilter?.value || 'all';
    const sort = sortBy?.value || 'date_asc';

    const cards = Array.from(document.querySelectorAll('.appointment-card'));

    cards.forEach(card => {
        const customer = card.dataset.customer;
        const phone = card.dataset.phone;
        const date = card.dataset.date;
        let show = true;

        if (term && !customer.includes(term) && !phone.includes(term)) show = false;

        if (show && filter !== 'all') {
            const cardDate = new Date(date + 'T00:00:00');
            const today = new Date(); today.setHours(0,0,0,0);
            const tomorrow = new Date(today); tomorrow.setDate(tomorrow.getDate() + 1);
            const weekFromNow = new Date(today); weekFromNow.setDate(weekFromNow.getDate() + 7);

            if (filter === 'today') show = cardDate.getTime() === today.getTime();
            else if (filter === 'tomorrow') show = cardDate.getTime() === tomorrow.getTime();
            else if (filter === 'week') show = cardDate >= today && cardDate <= weekFromNow;
        }

        card.style.display = show ? '' : 'none';
    });

    const list = document.getElementById('appointmentsList');
    const visibleCards = cards.filter(c => c.style.display !== 'none');
    visibleCards.sort((a, b) => {
        const ta = parseInt(a.dataset.timestamp);
        const tb = parseInt(b.dataset.timestamp);
        return sort === 'date_asc' ? ta - tb : tb - ta;
    });
    visibleCards.forEach(card => list.appendChild(card));
}

searchInput?.addEventListener('input', filterAppointments);
dateFilter?.addEventListener('change', filterAppointments);
sortBy?.addEventListener('change', filterAppointments);

// Room loading
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.appointment-card').forEach(function(card) {
        const appointmentId = card.dataset.id;
        const loadingEl = document.getElementById('room-loading-' + appointmentId);
        const optionsEl = document.getElementById('room-options-' + appointmentId);
        const messageEl = document.getElementById('room-message-' + appointmentId);
        const hiddenInput = document.getElementById('room-id-' + appointmentId);

        fetch(`/receptionist/booking/${appointmentId}/rooms`)
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                loadingEl.classList.add('hidden');

                if (!data.requires_room) {
                    loadingEl.textContent = '✓ No room required for this service.';
                    loadingEl.classList.remove('hidden', 'justify-center');
                    loadingEl.classList.add('text-emerald-600', 'dark:text-emerald-400', 'font-medium');
                    return;
                }

                if (data.rooms.length === 0) {
                    messageEl.textContent = data.message || 'No rooms available for this time slot.';
                    messageEl.classList.remove('hidden');
                    return;
                }

                optionsEl.classList.remove('hidden');

                if (data.required_category) {
                    const badge = document.createElement('div');
                    badge.className = 'col-span-full mb-2 px-3 py-1.5 bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-400 text-xs font-bold rounded-lg border border-teal-200 dark:border-teal-800/30 flex items-center gap-1.5';
                    badge.innerHTML = `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Requires: ${data.required_category} or General`;
                    optionsEl.appendChild(badge);
                }

                data.rooms.forEach(function(room, index) {
                    const label = document.createElement('label');
                    label.className = 'cursor-pointer group';
                    const isFirst = index === 0;
                    label.innerHTML = `
                        <input type="radio" name="room_select_${appointmentId}" value="${room.id}" class="peer hidden"
                            onchange="this.closest('label').querySelector('div').classList.add('ring-2', 'ring-teal-500', 'ring-offset-2', 'dark:ring-offset-gray-800'); document.querySelectorAll('input[name=\'room_select_${appointmentId}\']').forEach(r => { if(r !== this) r.closest('label').querySelector('div').classList.remove('ring-2', 'ring-teal-500', 'ring-offset-2', 'dark:ring-offset-gray-800'); }); document.getElementById('room-id-${appointmentId}').value = this.value;"
                            ${isFirst ? 'checked' : ''}>
                        <div class="text-center p-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-teal-500 peer-checked:bg-teal-50 dark:peer-checked:bg-teal-900/20 hover:border-gray-300 dark:hover:border-gray-500 transition-all h-full flex flex-col items-center justify-center ${isFirst ? 'ring-2 ring-teal-500 ring-offset-2 dark:ring-offset-gray-800' : ''}">
                            <div class="w-8 h-8 mb-1 rounded-lg bg-gray-100 dark:bg-gray-600 flex items-center justify-center group-hover:bg-teal-50 dark:group-hover:bg-teal-900/20 transition-colors">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <p class="font-bold text-sm text-gray-900 dark:text-white">${room.name}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">${room.category || 'General'}</p>
                        </div>
                    `;
                    optionsEl.appendChild(label);
                });

                if (data.rooms.length > 0) {
                    hiddenInput.value = data.rooms[0].id;
                }
            })
            .catch(err => {
                console.error('Failed to load rooms:', err);
                loadingEl.innerHTML = '<span class="text-red-500 flex items-center gap-1.5 justify-center font-medium"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Failed to load rooms. Please refresh.</span>';
            });
    });
});
</script>
@endsection