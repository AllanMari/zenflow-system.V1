@extends('layouts.receptionist')

@section('title','Pending Bookings')

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
                <span class="text-xs font-bold text-amber-700 dark:text-amber-400" id="pendingCount">
                    {{ $pendingCount }} pending
                </span>
            </div>

            @if($urgentCount > 0)
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/30">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span class="text-xs font-bold text-red-700 dark:text-red-400">
                        {{ $urgentCount }} urgent
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    @if($appointments->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 flex flex-col sm:flex-row gap-3 shadow-sm">

            <div class="relative flex-1">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>

                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search name or phone..."
                    class="w-full pl-9 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition"
                >
            </div>

            <div class="flex gap-2">
                <select
                    id="dateFilter"
                    class="pl-3 pr-8 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none bg-white"
                >
                    <option value="all">All Dates</option>
                    <option value="today">Today</option>
                    <option value="tomorrow">Tomorrow</option>
                    <option value="week">Next 7 Days</option>
                </select>

                <div class="flex items-center gap-2">
                    <label for="sortBy" class="text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">Sort by Request Date</label>
                    <div class="relative inline-flex items-center" id="sortTooltipWrapper">
                        <button
                            type="button"
                            id="sortTooltipBtn"
                            aria-label="Sorting information"
                            aria-describedby="sortTooltipContent"
                            class="inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 hover:text-teal-600 focus:text-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-500/30 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </button>
                        <div
                            id="sortTooltipContent"
                            role="tooltip"
                            class="hidden absolute right-0 top-7 z-40 w-72 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 text-xs text-gray-600 dark:text-gray-300 shadow-lg space-y-2"
                        >
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white">Earliest Request First</p>
                                <p class="mt-0.5 leading-relaxed">Customers who submitted their booking requests earlier appear at the top.</p>
                            </div>
                            <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                                <p class="font-bold text-gray-900 dark:text-white">Latest Request First</p>
                                <p class="mt-0.5 leading-relaxed">The newest booking requests appear at the top.</p>
                            </div>
                        </div>
                    </div>
                    <select
                        id="sortBy"
                        name="sort"
                        onchange="const u = new URL(window.location.href); u.searchParams.set('sort', this.value); u.searchParams.delete('page'); window.location.href = u.toString();"
                        class="pl-3 pr-8 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none bg-white"
                    >
                        <option value="oldest" {{ ($sort ?? 'oldest') === 'oldest' ? 'selected' : '' }}>Earliest Request First</option>
                        <option value="newest" {{ ($sort ?? 'oldest') === 'newest' ? 'selected' : '' }}>Latest Request First</option>
                    </select>
                </div>
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

                $phoneNum = $appointment->guest_phone ?: ($appointment->customer?->phone_number ?: 'N/A');
                $hasPhone = !empty($phoneNum) && $phoneNum !== 'N/A';
                $requiresRoom = $appointment->services->contains(fn($s) => $s->requires_room);
                $serviceSummary = $appointment->services->pluck('name')->join(', ');
                $totalDuration = $appointment->services->sum('duration_minutes');
                $requestedAt = $appointment->created_at?->timezone('Asia/Manila');
                $requiredStaffCount = max(1, (int) $appointment->services->max('therapists_required'));
                $heldTherapistsLabel = $appointment->preferredStaff->count() > 1
                    ? 'Temporarily Reserved Therapists'
                    : 'Temporarily Reserved Therapist';
            @endphp

            <article
                class="appointment-card group bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden border-l-4 {{ $urgencyClass }}"
                data-id="{{ $appointment->id }}"
                data-customer="{{ strtolower($appointment->client_name) }}"
                data-phone="{{ $phoneNum }}"
                data-date="{{ $dateStr }}"
                data-timestamp="{{ $apptDateTime->timestamp }}"
                data-expires-at="{{ $appointment->expires_at?->toISOString() }}"
                data-cancel-url="{{ route('receptionist.cancel', $appointment) }}"
                data-current-room-id="{{ $appointment->room_id ?? '' }}"
                data-reassign-room-url="{{ route('receptionist.reassign-room', $appointment) }}"
                data-preferred-staff-ids='@json($appointment->preferredStaff->pluck("id")->values())'
                data-required-staff-count="{{ $requiredStaffCount }}"
            >

                {{-- Compact Two-Line Summary --}}
                <div class="px-4 py-3 cursor-pointer" onclick="toggleDetails({{ $appointment->id }})">

                    {{-- Line 1: Identity, Badges, Request Time, Appointment Date/Time, Price & Expand --}}
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wide {{ $urgencyBadge[0] }}">
                                    {{ $urgencyBadge[1] }}
                                </span>

                                @if($appointment->expires_at)
                                    <span
                                        class="pending-countdown-badge inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold border bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900/50"
                                        data-countdown-id="{{ $appointment->id }}"
                                        data-expires-at="{{ $appointment->expires_at->toISOString() }}"
                                        title="Authoritative expiration deadline: {{ $appointment->expires_at->format('M j, Y g:i A') }}"
                                    >
                                        <svg class="w-3 h-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="countdown-text">Calculating...</span>
                                    </span>
                                @endif

                                <span class="text-[11px] font-mono text-gray-400">#{{ $appointment->id }}</span>

                                @if(!empty($meta['hasDepositRequired']))
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/30 text-[10px] font-bold text-amber-700 dark:text-amber-400">
                                        Deposit {{ $meta['maxDepositPercent'] ?? 0 }}%
                                    </span>
                                @endif

                                @if($requestedAt)
                                    <span class="text-[11px] text-gray-400 dark:text-gray-500">
                                        · Requested {{ $requestedAt->format('M j, Y g:i A') }}
                                    </span>
                                @endif

                                <span class="text-[11px] text-gray-500 dark:text-gray-400" title="Booker's IP address">
                                    · <strong>Booking IP:</strong> <span class="font-mono">{{ $appointment->ip_address ?? 'N/A' }}</span>
                                </span>
                            </div>

                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5">
                                <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white break-words">
                                    {{ $appointment->client_name }}
                                </h3>

                                <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a2 2 0 011.498 1.21l1.498 4.493A2 2 0 0119 21h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    @if($hasPhone)
                                        <a href="tel:{{ $phoneNum }}" onclick="event.stopPropagation()" class="hover:underline hover:text-teal-600 dark:hover:text-teal-400 font-medium">
                                            {{ $phoneNum }}
                                        </a>
                                    @else
                                        <span>{{ $phoneNum }}</span>
                                    @endif
                                </span>
                            </div>

                        </div>

                        <div class="flex items-center justify-between md:justify-end gap-3 shrink-0">
                            <div class="text-left md:text-right">
                                <p class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $apptDateTime->format('M j, Y · g:i A') }}
                                </p>
                                <p class="text-xs font-semibold text-teal-700 dark:text-teal-400">
                                    ₱{{ number_format($appointment->total_price, 2) }}
                                </p>
                            </div>

                            <div
                                class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center transition-transform duration-200 shrink-0"
                                id="chevron-{{ $appointment->id }}"
                                title="Review & Confirm"
                            >
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Line 2: Triage Strip --}}

                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700/60 flex flex-wrap items-center justify-between gap-x-3 gap-y-1.5 text-xs">
                        <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5 min-w-0">
                            <span class="text-gray-700 dark:text-gray-200 font-medium truncate max-w-md" title="{{ $serviceSummary }}">
                                {{ $serviceSummary ?: 'No services' }} <span class="text-gray-400 dark:text-gray-500">({{ $totalDuration }} min)</span>
                            </span>

                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800/40 text-teal-800 dark:text-teal-200" id="reserved-room-display-{{ $appointment->id }}">
                                @if($appointment->room)
                                    <strong>Reserved Room:</strong> {{ $appointment->room->name }}
                                    <span class="text-[11px] font-medium text-teal-600 dark:text-teal-400">(Temporarily reserved while pending)</span>
                                @elseif($appointment->requestedRoom)
                                    <strong>Reserved Room:</strong> {{ $appointment->requestedRoom->name }}
                                @else
                                    <strong>Reserved Room:</strong> No room reserved
                                @endif
                            </span>

                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-indigo-50/80 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700/50 text-indigo-800 dark:text-indigo-200">
                                <strong>{{ $heldTherapistsLabel }}:</strong>
                                @if($appointment->preferredStaff->isNotEmpty())
                                    @foreach($appointment->preferredStaff as $pref)
                                        @php
                                            $prefAvail = ($staffAvailability[$appointment->id][$pref->id]['available'] ?? false);
                                        @endphp
                                        <span>{{ $pref->first_name }}</span>
                                        <span class="text-[11px] font-semibold {{ $prefAvail ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">({{ $prefAvail ? 'Available' : 'Unavailable' }})</span>@if(!$loop->last), @endif
                                    @endforeach
                                    <span class="text-[11px] font-medium text-indigo-600 dark:text-indigo-300">(Temporarily held while pending)</span>
                                @else
                                    <span>No preference</span>
                                @endif
                            </span>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                type="button"
                                onclick="event.stopPropagation(); openCancelModal({{ $appointment->id }})"
                                class="inline-flex items-center gap-1 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 px-2.5 py-1 rounded-lg transition font-semibold"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>

                </div>

                {{-- Expandable Details --}}
                <div id="details-{{ $appointment->id }}" class="hidden border-t border-gray-100 dark:border-gray-700/50">

                    <form
                        id="confirm-form-{{ $appointment->id }}"
                        action="{{ route('receptionist.confirm', $appointment) }}"
                        method="POST"
                        class="p-4 space-y-4"
                    >
                        @csrf
                        <input type="hidden" name="staff_id" id="hidden_staff_id_{{ $appointment->id }}" value="">
                        <div id="hidden_assisting_container_{{ $appointment->id }}"></div>

                        {{-- Services --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                    Services & Pricing
                                </h4>
                                <span class="text-[11px] text-gray-400" title="Booker's IP address">
                                    IP: {{ $appointment->ip_address ?? 'N/A' }}
                                </span>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-900/30 rounded-xl p-4 space-y-3">

                                @foreach($appointment->services as $service)

                                    @php
                                        $depositPercent = $service->deposit_percentage_min ?? $service->category?->deposit_percentage_min ?? 0;
                                    @endphp

                                    <div class="flex items-center justify-between">

                                        <div class="flex items-center gap-3">

                                            <div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center text-gray-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                                </svg>
                                            </div>

                                            <div>
                                                <p class="text-sm font-bold text-gray-900 dark:text-white">
                                                    {{ $service->name }}
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    {{ $service->duration_minutes }} min
                                                </p>
                                            </div>

                                        </div>

                                        <div class="text-right">

                                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                                ₱{{ number_format($service->pivot->price_at_booking, 2) }}
                                            </p>

                                            @if($depositPercent > 0)
                                                <p class="text-[10px] text-amber-600 font-medium">
                                                    {{ $depositPercent }}% deposit
                                                </p>
                                            @endif

                                        </div>

                                    </div>

                                @endforeach

                                @if($appointment->packageSelections && $appointment->packageSelections->isNotEmpty())
                                    <div class="mt-2 space-y-1 rounded-lg border border-purple-200 bg-purple-50/70 p-2.5 text-xs dark:border-purple-900/40 dark:bg-purple-950/30">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300">Package Choices:</p>
                                        @foreach($appointment->packageSelections as $sel)
                                            <div class="flex items-center justify-between text-xs text-gray-700 dark:text-gray-300">
                                                <span class="font-medium">{{ $sel->choiceGroupName }}:</span>
                                                <span class="font-bold text-purple-700 dark:text-purple-300">{{ $sel->selectedName }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="pt-3 border-t border-gray-200 dark:border-gray-700/50 flex items-center justify-between">

                                    <div>
                                        <p class="text-xs text-gray-500">Total Duration</p>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $appointment->services->sum('duration_minutes') }} min
                                        </p>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-xs text-gray-500">Total Price</p>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                                            ₱{{ number_format($appointment->total_price, 2) }}
                                        </p>
                                    </div>

                                </div>

                                @if(!empty($meta['hasDepositRequired']))
                                    <div class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-900/15 rounded-lg border border-amber-200 dark:border-amber-800/30">

                                        <span class="text-xs font-bold text-amber-700 dark:text-amber-400">
                                            Minimum Deposit Required
                                        </span>

                                        <span class="text-sm font-bold text-amber-700 dark:text-amber-400">
                                            ₱{{ number_format($meta['totalDepositRequired'], 2) }}
                                        </span>

                                    </div>
                                @endif

                            </div>
                        </div>

                        {{-- Medical Notes --}}
                        @if($appointment->customer && $appointment->customer->medical_notes)

                            <div class="p-4 bg-rose-50 dark:bg-rose-900/15 border border-rose-200 dark:border-rose-800/30 rounded-xl flex items-start gap-3">

                                <div class="shrink-0 w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                </div>

                                <div>
                                    <p class="text-xs font-bold text-rose-700 dark:text-rose-300 uppercase tracking-wider mb-1">
                                        Medical Notes
                                    </p>

                                    <p class="text-sm text-rose-800 dark:text-rose-200 leading-relaxed">
                                        {{ $appointment->customer->medical_notes }}
                                    </p>
                                </div>

                            </div>

                        @endif

                        {{-- Session Treatment Notes --}}
                        @if($appointment->treatment_notes)

                            <div class="p-4 bg-teal-50 dark:bg-teal-900/15 border border-teal-200 dark:border-teal-800/30 rounded-xl flex items-start gap-3">

                                <div class="shrink-0 w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>

                                <div>
                                    <p class="text-xs font-bold text-teal-700 dark:text-teal-300 uppercase tracking-wider mb-1">
                                        Session Treatment Notes (Therapists Only)
                                    </p>

                                    <p class="text-sm text-teal-800 dark:text-teal-200 leading-relaxed">
                                        {{ $appointment->treatment_notes }}
                                    </p>
                                </div>

                            </div>

                        @endif

                        @php
                            $requiredStaffCount = max(1, (int) $appointment->services->max('therapists_required'));
                        @endphp

                        {{-- Staff Grid --}}
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                        Assign Staff <span class="text-red-500">*</span>
                                    </h4>
                                    @if($requiredStaffCount > 1)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            Requires {{ $requiredStaffCount }} Therapists
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    <span id="staff-counter-{{ $appointment->id }}"
                                          class="text-xs font-bold px-2.5 py-0.5 rounded-full transition-all {{ $requiredStaffCount > 1 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'hidden' }}">
                                        0 of {{ $requiredStaffCount }} selected
                                    </span>

                                    @if(!$hasAvailableStaff)
                                        <span class="text-xs font-bold text-red-600 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            No availability
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if($hasAvailableStaff)
                                <div id="staff-grid-{{ $appointment->id }}" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                                    @foreach($allStaff as $staff)
                                        @php
                                            $staffInfo = $apptStaffMap->get($staff->id);
                                            $isAvailable = $staffInfo && $staffInfo['available'];
                                            $statusLabel = $staffInfo ? $staffInfo['status_label'] : 'Not Scheduled';
                                            $hours = $staffInfo ? ($staffInfo['hours'] ?? '') : '';
                                        @endphp

                                        <div
                                            id="staff_card_{{ $appointment->id }}_{{ $staff->id }}"
                                            onclick="handleStaffSelection({{ $appointment->id }}, {{ $staff->id }}, {{ $requiredStaffCount }}, {{ $isAvailable ? 'true' : 'false' }})"
                                            class="staff-card-{{ $appointment->id }} relative p-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 transition-all text-center h-full flex flex-col items-center justify-center select-none {{ $isAvailable ? 'cursor-pointer hover:border-teal-400' : 'opacity-50 cursor-not-allowed' }}"
                                            data-staff-id="{{ $staff->id }}"
                                            data-available="{{ $isAvailable ? '1' : '0' }}"
                                        >
                                            <span id="staff_role_badge_{{ $appointment->id }}_{{ $staff->id }}"
                                                  class="hidden absolute -top-2 px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider shadow-xs">
                                            </span>

                                            <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 font-bold text-sm mb-2">
                                                {{ collect(explode(' ', $staff->full_name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('') }}
                                            </div>

                                            <p class="text-xs font-bold text-gray-900 dark:text-white break-words w-full">
                                                {{ $staff->full_name }}
                                            </p>

                                            <p class="text-[10px] font-medium mt-1 {{ $isAvailable ? 'text-teal-600 dark:text-teal-400' : 'text-red-500' }}">
                                                {{ $isAvailable ? 'Available' : $statusLabel }}
                                            </p>

                                            @if($hours)
                                                <p class="text-[10px] text-gray-400 mt-0.5">
                                                    {{ $hours }}
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                            @else

                                <div class="p-6 rounded-xl bg-red-50 dark:bg-red-900/15 border border-red-200 dark:border-red-800/30 text-center">

                                    <svg class="w-10 h-10 text-red-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>

                                    <p class="text-sm font-bold text-red-700 dark:text-red-300">
                                        No Staff Available
                                    </p>

                                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                                        Consider rescheduling or checking the schedule.
                                    </p>

                                </div>

                            @endif

                        </div>

                        {{-- Reserved Room Status --}}
                        <div class="p-4 bg-teal-50 dark:bg-teal-900/15 border border-teal-200 dark:border-teal-800/30 rounded-xl flex items-start gap-3">
                            <div class="shrink-0 w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-teal-700 dark:text-teal-300 uppercase tracking-wider mb-1">
                                    Reserved Room
                                </p>
                                <p class="text-sm text-teal-800 dark:text-teal-200 leading-relaxed">
                                    @if($appointment->room)
                                        <strong>Reserved Room:</strong> {{ $appointment->room->name }}
                                        <span class="text-xs font-medium text-teal-600 dark:text-teal-400">(Temporarily reserved while pending)</span>
                                    @elseif($appointment->requestedRoom)
                                        <strong>Reserved Room:</strong> {{ $appointment->requestedRoom->name }}
                                    @else
                                        <strong>Reserved Room:</strong> No room reserved
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- Temporarily Reserved Therapist(s) (held while pending; final assignment happens on confirmation) --}}
                        <div class="p-3.5 rounded-xl bg-indigo-50/70 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700/50">
                            <p class="text-xs font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider mb-1">
                                {{ $heldTherapistsLabel }}
                            </p>
                            @if($appointment->preferredStaff->isNotEmpty())
                                <ul class="text-sm text-indigo-900 dark:text-indigo-200 space-y-1">
                                    @foreach($appointment->preferredStaff as $pref)
                                        @php
                                            $prefAvail = ($staffAvailability[$appointment->id][$pref->id]['available'] ?? false);
                                        @endphp
                                        <li>
                                            <strong>Held:</strong> {{ $pref->first_name }}
                                            @if($prefAvail)
                                                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">(Available — temporarily reserved while pending)</span>
                                            @else
                                                <span class="text-xs font-semibold text-rose-700 dark:text-rose-300">(Unavailable — choose another)</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-indigo-800 dark:text-indigo-200">
                                    <strong>Temporarily Reserved Therapist:</strong> No preference
                                </p>
                            @endif
                        </div>

                        {{-- Room Assignment --}}
                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/30 border border-gray-200 dark:border-gray-600/50">

                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                    Room Assignment
                                </h4>
                                @if($requiresRoom)
                                    <button
                                        type="button"
                                        id="save-room-btn-{{ $appointment->id }}"
                                        onclick="savePendingRoomAssignment({{ $appointment->id }})"
                                        disabled
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    >
                                        Save Room Assignment
                                    </button>
                                @endif
                            </div>

                            <div
                                id="room-loading-{{ $appointment->id }}"
                                class="flex items-center gap-2 text-sm text-gray-500 py-4 justify-center"
                            >
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                Checking room requirements...
                            </div>

                            <div
                                id="room-options-{{ $appointment->id }}"
                                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 hidden"
                            ></div>

                            <div
                                id="room-message-{{ $appointment->id }}"
                                class="text-sm text-amber-600 dark:text-amber-400 text-center py-2 hidden"
                            ></div>

                            <div
                                id="room-reassign-status-{{ $appointment->id }}"
                                class="text-xs font-medium mt-2 hidden"
                            ></div>

                            <input
                                type="hidden"
                                name="room_id"
                                id="room-id-{{ $appointment->id }}"
                                value="{{ $appointment->room_id ?? '' }}"
                            >

                        </div>

                        {{-- Payment --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                            <div>

                                <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">
                                    Payment Type <span class="text-red-500">*</span>
                                </h4>

                                <div class="flex flex-wrap gap-2">

                                    @if(empty($meta['hasDepositRequired']))
                                        <button
                                            type="button"
                                            onclick="setPaymentType({{ $appointment->id }}, 'cash_on_site', {{ $appointment->total_price }}, {{ $meta['totalDepositRequired'] ?? 0 }})"
                                            class="payment-btn-{{ $appointment->id }} px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-600 text-sm font-bold text-gray-700 dark:text-gray-200 hover:border-teal-500 transition-colors"
                                            data-type="cash_on_site"
                                        >
                                            Cash on Site
                                        </button>
                                    @endif

                                    <button
                                        type="button"
                                        onclick="setPaymentType({{ $appointment->id }}, 'deposit', {{ $appointment->total_price }}, {{ $meta['totalDepositRequired'] ?? 0 }})"
                                        class="payment-btn-{{ $appointment->id }} px-4 py-2.5 rounded-xl border-2 text-sm font-bold transition-colors {{ !empty($meta['hasDepositRequired']) ? 'border-teal-500 text-teal-600 bg-teal-50 dark:bg-teal-900/20' : 'border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:border-teal-500' }}"
                                        data-type="deposit"
                                    >
                                        Deposit
                                    </button>

                                    <button
                                        type="button"
                                        onclick="setPaymentType({{ $appointment->id }}, 'full', {{ $appointment->total_price }}, {{ $meta['totalDepositRequired'] ?? 0 }})"
                                        class="payment-btn-{{ $appointment->id }} px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-600 text-sm font-bold text-gray-700 dark:text-gray-200 hover:border-teal-500 transition-colors"
                                        data-type="full"
                                    >
                                        Full Payment
                                    </button>

                                </div>

                                <input
                                    type="hidden"
                                    name="payment_type"
                                    id="paymentType_{{ $appointment->id }}"
                                    value="{{ !empty($meta['hasDepositRequired']) ? 'deposit' : 'cash_on_site' }}"
                                >

                            </div>

                            <div class="space-y-4">

                                <div>

                                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                        Amount (₱) <span class="text-red-500">*</span>
                                    </label>

                                    <div class="relative">

                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">
                                            ₱
                                        </span>

                                        <input
                                            type="number"
                                            name="amount"
                                            id="amount_{{ $appointment->id }}"
                                            step="0.01"
                                            min="0"
                                            required
                                            value="{{ !empty($meta['hasDepositRequired']) ? ($meta['totalDepositRequired'] ?? 0) : '0.00' }}"
                                            class="w-full pl-8 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white text-sm font-bold focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition"
                                        >

                                    </div>

                                    <p
                                        class="text-xs text-gray-400 dark:text-gray-500 mt-1.5"
                                        id="amountHint_{{ $appointment->id }}"
                                    >
                                        {{ !empty($meta['hasDepositRequired']) ? 'Minimum deposit: ₱' . number_format($meta['totalDepositRequired'] ?? 0, 2) : 'Customer will pay at the counter' }}
                                    </p>

                                </div>

                                <div>

                                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                        Method <span class="text-red-500">*</span>
                                    </label>

                                    <select
                                        name="payment_method"
                                        required
                                        class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition bg-white"
                                    >
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
                        <div class="space-y-4">

                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    Receptionist Notes
                                    <span class="text-gray-300 dark:text-gray-600 font-normal">(operational, parking, scheduling)</span>
                                </label>

                                <textarea
                                    name="notes"
                                    rows="2"
                                    placeholder="Special requests, preferences, parking needs..."
                                    class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition resize-none"
                                >{{ $appointment->notes }}</textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-teal-700 dark:text-teal-400 uppercase tracking-wider mb-2">
                                    Treatment Notes (Therapists Only)
                                    <span class="text-gray-400 font-normal">(confidential health / treatment info)</span>
                                </label>

                                <textarea
                                    name="treatment_notes"
                                    rows="2"
                                    placeholder="Allergies, pressure preferences, areas to focus or avoid..."
                                    class="w-full border border-teal-200 dark:border-teal-700/60 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition resize-none"
                                >{{ $appointment->treatment_notes }}</textarea>
                            </div>

                        </div>

                        {{-- Confirmation Footer Actions (inside expandable details) --}}
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-700/50 flex flex-wrap items-center gap-3">
                            <button
                                type="submit"
                                form="confirm-form-{{ $appointment->id }}"
                                id="confirm-btn-{{ $appointment->id }}"
                                onclick="if(this.disabled) return false; this.disabled=true; this.textContent='Confirming...'; this.form.submit();"
                                class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white px-5 py-2 rounded-xl text-xs sm:text-sm font-bold transition shadow-sm hover:shadow-md active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
                                disabled
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                Confirm Booking
                            </button>

                            <button
                                type="button"
                                onclick="openCancelModal({{ $appointment->id }})"
                                class="ml-auto inline-flex items-center gap-1.5 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 px-3.5 py-2 rounded-xl transition text-xs sm:text-sm font-bold"
                            >
                                Cancel
                            </button>
                        </div>

                    </form>
                </div>

            </article>

        @empty

            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-12 text-center">

                <div class="w-16 h-16 bg-emerald-50 dark:bg-emerald-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    All Caught Up
                </h3>

                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    No bookings awaiting confirmation.
                </p>

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

    <div
        class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity opacity-0"
        id="cancelModalBackdrop"
        onclick="closeCancelModal()"
    ></div>

    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">

        <div
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 transform transition-all scale-95 opacity-0 pointer-events-auto"
            id="cancelModalContent"
        >

            <div class="flex items-center gap-3 mb-4">

                <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Cancel Booking
                    </h3>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        This action cannot be undone
                    </p>
                </div>

            </div>

            <form id="cancelForm" method="POST" class="space-y-4">

                @csrf

                <div>

                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        Cancellation Reason <span class="text-red-500">*</span>
                    </label>

                    <select
                        name="reason"
                        required
                        id="cancelReason"
                        onchange="toggleOtherReason(this)"
                        class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none bg-white transition"
                    >
                        <option value="">Select a reason...</option>
                        <option value="customer_cancelled">Customer Cancelled</option>
                        <option value="staff_unavailable">Staff Unavailable</option>
                        <option value="other">Other Reason</option>
                    </select>

                </div>

                <div id="otherReasonContainer" class="hidden">

                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        Please Specify
                    </label>

                    <textarea
                        name="other_reason"
                        rows="2"
                        placeholder="Enter details..."
                        class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition resize-none"
                    ></textarea>

                </div>

                <div class="flex gap-3 pt-2">

                    <button
                        type="button"
                        onclick="closeCancelModal()"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                    >
                        Keep Booking
                    </button>

                    <button
                        type="submit"
                        onclick="if(this.disabled) return false; this.disabled=true; this.textContent='Cancelling...'; this.form.submit();"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-bold transition shadow-sm"
                    >
                        Cancel Booking
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>

<script>

function toggleDetails(id) {

    const details = document.getElementById('details-' + id);
    const chevron = document.getElementById('chevron-' + id);

    if (!details || !chevron) return;

    const isHidden = details.classList.contains('hidden');

    if (isHidden) {

        details.classList.remove('hidden');

        chevron.classList.add(
            'rotate-180',
            'bg-teal-50',
            'dark:bg-teal-900/20',
            'text-teal-600'
        );

        chevron.classList.remove(
            'bg-gray-50',
            'bg-gray-100',
            'dark:bg-gray-700',
            'text-gray-400',
            'text-gray-500'
        );

        const card = document.querySelector(`.appointment-card[data-id="${id}"]`);
        if (card && card.dataset.roomsLoaded !== '1') {
            loadRoomsForAppointment(card);
        }
        if (card && card.dataset.staffPreselected !== '1') {
            preselectHeldTherapists(card);
        }

    } else {

        details.classList.add('hidden');

        chevron.classList.remove(
            'rotate-180',
            'bg-teal-50',
            'dark:bg-teal-900/20',
            'text-teal-600'
        );

        chevron.classList.add(
            'bg-gray-50',
            'dark:bg-gray-700',
            'text-gray-400'
        );
    }
}

window.pendingStaffSelections = window.pendingStaffSelections || {};

function preselectHeldTherapists(card) {
    const appointmentId = card.dataset.id;
    if (!appointmentId) return;

    card.dataset.staffPreselected = '1';

    let preferredIds = [];
    try {
        preferredIds = JSON.parse(card.dataset.preferredStaffIds || '[]').map(Number).filter(Boolean);
    } catch (e) {
        preferredIds = [];
    }

    if (!preferredIds.length) {
        return;
    }

    const requiredCount = Math.max(1, Number(card.dataset.requiredStaffCount || 1));
    const eligibleIds = [];

    preferredIds.forEach(staffId => {
        if (eligibleIds.length >= requiredCount) return;
        const staffCard = document.getElementById(`staff_card_${appointmentId}_${staffId}`);
        if (staffCard && staffCard.dataset.available === '1') {
            eligibleIds.push(staffId);
        }
    });

    if (eligibleIds.length > 0) {
        window.pendingStaffSelections[appointmentId] = eligibleIds;
        syncStaffSelectionUI(appointmentId, requiredCount);
    }
}

function handleStaffSelection(appointmentId, staffId, requiredCount, isAvailable) {
    if (!isAvailable) return;

    let selected = window.pendingStaffSelections[appointmentId] || [];

    const index = selected.indexOf(staffId);
    if (index > -1) {
        selected.splice(index, 1);
    } else {
        if (requiredCount === 1) {
            selected = [staffId];
        } else {
            if (selected.length < requiredCount) {
                selected.push(staffId);
            } else {
                selected.shift();
                selected.push(staffId);
            }
        }
    }

    window.pendingStaffSelections[appointmentId] = selected;
    syncStaffSelectionUI(appointmentId, requiredCount);
}

function syncStaffSelectionUI(appointmentId, requiredCount) {
    const selected = window.pendingStaffSelections[appointmentId] || [];
    const container = document.getElementById('staff-grid-' + appointmentId);
    if (!container) return;

    container.querySelectorAll('.staff-card-' + appointmentId).forEach(card => {
        card.classList.remove(
            'border-teal-500', 'bg-teal-50', 'dark:bg-teal-900/20',
            'ring-2', 'ring-teal-500', 'ring-offset-2', 'dark:ring-offset-gray-800'
        );
        card.classList.add('border-gray-200', 'dark:border-gray-600');
        const badge = card.querySelector('[id^="staff_role_badge_"]');
        if (badge) {
            badge.classList.add('hidden');
            badge.textContent = '';
        }
    });

    selected.forEach((staffId, idx) => {
        const card = document.getElementById(`staff_card_${appointmentId}_${staffId}`);
        if (!card) return;

        card.classList.remove('border-gray-200', 'dark:border-gray-600');
        card.classList.add(
            'border-teal-500', 'bg-teal-50', 'dark:bg-teal-900/20',
            'ring-2', 'ring-teal-500', 'ring-offset-2', 'dark:ring-offset-gray-800'
        );

        const badge = document.getElementById(`staff_role_badge_${appointmentId}_${staffId}`);
        if (badge) {
            badge.classList.remove('hidden');
            if (requiredCount > 1) {
                badge.textContent = `Therapist ${idx + 1}`;
                badge.className = 'absolute -top-2 px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-teal-600 text-white shadow-xs';
            } else {
                badge.textContent = 'Selected';
                badge.className = 'absolute -top-2 px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-teal-600 text-white shadow-xs';
            }
        }
    });

    const primaryInput = document.getElementById('hidden_staff_id_' + appointmentId);
    const assistingContainer = document.getElementById('hidden_assisting_container_' + appointmentId);

    if (primaryInput) {
        primaryInput.value = selected[0] || '';
    }

    if (assistingContainer) {
        assistingContainer.innerHTML = '';
        for (let i = 1; i < selected.length; i++) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'assisting_staff_ids[]';
            input.value = selected[i];
            assistingContainer.appendChild(input);
        }
    }

    const counterBadge = document.getElementById('staff-counter-' + appointmentId);
    if (counterBadge) {
        const count = selected.length;
        if (count === requiredCount) {
            counterBadge.textContent = `${count} of ${requiredCount} selected (Ready)`;
            counterBadge.className = 'text-xs font-bold px-2.5 py-0.5 rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
        } else {
            counterBadge.textContent = `${count} of ${requiredCount} selected`;
            counterBadge.className = 'text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
        }
    }

    const confirmBtn = document.getElementById('confirm-btn-' + appointmentId);
    if (confirmBtn) {
        confirmBtn.disabled = selected.length !== requiredCount;
    }
}

function setPaymentType(id, type, total, deposit) {

    const paymentType = document.getElementById('paymentType_' + id);
    const amountInput = document.getElementById('amount_' + id);
    const hint = document.getElementById('amountHint_' + id);

    if (!paymentType || !amountInput) return;

    paymentType.value = type;

    document.querySelectorAll('.payment-btn-' + id).forEach(btn => {

        if (btn.dataset.type === type) {

            btn.classList.add(
                'border-teal-500',
                'text-teal-600',
                'bg-teal-50',
                'dark:bg-teal-900/20'
            );

            btn.classList.remove(
                'border-gray-200',
                'dark:border-gray-600',
                'text-gray-700',
                'dark:text-gray-200'
            );

        } else {

            btn.classList.remove(
                'border-teal-500',
                'text-teal-600',
                'bg-teal-50',
                'dark:bg-teal-900/20'
            );

            btn.classList.add(
                'border-gray-200',
                'dark:border-gray-600',
                'text-gray-700',
                'dark:text-gray-200'
            );
        }
    });

    if (type === 'deposit') {

        amountInput.value = deposit.toFixed(2);
        amountInput.readOnly = false;

        if (hint) {
            hint.textContent = 'Minimum deposit: ₱' + deposit.toFixed(2);
        }

        amountInput.focus();

    } else if (type === 'full') {

        amountInput.value = total.toFixed(2);
        amountInput.readOnly = false;

        if (hint) {
            hint.textContent = 'Full payment of ₱' + total.toFixed(2) + ' required';
        }

        amountInput.focus();

    } else if (type === 'cash_on_site') {

        amountInput.value = '0.00';
        amountInput.readOnly = true;

        if (hint) {
            hint.textContent = 'Customer will pay at the counter. No payment recorded now.';
        }
    }
}

let currentCancelUrl = '';

function openCancelModal(appointmentId) {

    const card = document.querySelector('[data-id="' + appointmentId + '"]');

    if (!card) return;

    currentCancelUrl = card.dataset.cancelUrl;

    document.getElementById('cancelForm').action = currentCancelUrl;

    const modal = document.getElementById('cancelModal');
    const backdrop = document.getElementById('cancelModalBackdrop');
    const content = document.getElementById('cancelModalContent');

    modal.classList.remove('hidden');

    requestAnimationFrame(() => {

        backdrop.classList.remove('opacity-0');

        content.classList.remove(
            'scale-95',
            'opacity-0'
        );

        content.classList.add(
            'scale-100',
            'opacity-100'
        );
    });
}

function closeCancelModal() {

    const modal = document.getElementById('cancelModal');
    const backdrop = document.getElementById('cancelModalBackdrop');
    const content = document.getElementById('cancelModalContent');

    if (!modal || !backdrop || !content) return;

    backdrop.classList.add('opacity-0');

    content.classList.remove(
        'scale-100',
        'opacity-100'
    );

    content.classList.add(
        'scale-95',
        'opacity-0'
    );

    setTimeout(() => {

        modal.classList.add('hidden');

        document.getElementById('cancelReason').value = '';

        document
            .getElementById('otherReasonContainer')
            .classList.add('hidden');

    }, 200);
}

function toggleOtherReason(select) {

    const container = document.getElementById('otherReasonContainer');

    if (container) {

        container.classList.toggle(
            'hidden',
            select.value !== 'other'
        );
    }
}

const searchInput = document.getElementById('searchInput');
const dateFilter = document.getElementById('dateFilter');
const sortBy = document.getElementById('sortBy');

function filterAppointments() {

    const term = searchInput?.value.toLowerCase() || '';
    const filter = dateFilter?.value || 'all';
    const sort = sortBy?.value || 'date_asc';

    const cards = Array.from(
        document.querySelectorAll('.appointment-card')
    );

    cards.forEach(card => {

        const customer = card.dataset.customer || '';
        const phone = card.dataset.phone || '';
        const date = card.dataset.date || '';

        let show = true;

        if (
            term &&
            !customer.includes(term) &&
            !phone.includes(term)
        ) {
            show = false;
        }

        if (show && filter !== 'all') {

            const cardDate = new Date(date + 'T00:00:00');

            const today = new Date();

            today.setHours(0, 0, 0, 0);

            const tomorrow = new Date(today);

            tomorrow.setDate(tomorrow.getDate() + 1);

            const weekFromNow = new Date(today);

            weekFromNow.setDate(weekFromNow.getDate() + 7);

            if (filter === 'today') {

                show = cardDate.getTime() === today.getTime();

            } else if (filter === 'tomorrow') {

                show = cardDate.getTime() === tomorrow.getTime();

            } else if (filter === 'week') {

                show = cardDate >= today && cardDate <= weekFromNow;
            }
        }

        card.style.display = show ? '' : 'none';
    });
}

searchInput?.addEventListener('input', filterAppointments);
dateFilter?.addEventListener('change', filterAppointments);

(function () {
    const wrapper = document.getElementById('sortTooltipWrapper');
    const btn = document.getElementById('sortTooltipBtn');
    const tip = document.getElementById('sortTooltipContent');
    if (!wrapper || !btn || !tip) return;
    const show = () => tip.classList.remove('hidden');
    const hide = () => tip.classList.add('hidden');
    wrapper.addEventListener('mouseenter', show);
    wrapper.addEventListener('mouseleave', hide);
    btn.addEventListener('focus', show);
    btn.addEventListener('blur', hide);
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        tip.classList.toggle('hidden');
    });
    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) hide();
    });
})();

function loadRoomsForAppointment(card) {

    const appointmentId = card.dataset.id;

    const loadingEl = document.getElementById(
        'room-loading-' + appointmentId
    );

    const optionsEl = document.getElementById(
        'room-options-' + appointmentId
    );

    const messageEl = document.getElementById(
        'room-message-' + appointmentId
    );

    const hiddenInput = document.getElementById(
        'room-id-' + appointmentId
    );

    if (
        !loadingEl ||
        !optionsEl ||
        !messageEl ||
        !hiddenInput
    ) {
        return;
    }

    fetch(`/receptionist/booking/${appointmentId}/rooms?compatible_only=1`)
        .then(response => {

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            return response.json();
        })
        .then(data => {

            loadingEl.classList.add('hidden');

            if (!data.requires_room) {

                loadingEl.textContent =
                    '✓ No room required for this service.';

                loadingEl.classList.remove(
                    'hidden',
                    'justify-center'
                );

                loadingEl.classList.add(
                    'text-emerald-600',
                    'dark:text-emerald-400',
                    'font-medium'
                );

                return;
            }

            const compatibleRooms = Array.isArray(data.rooms)
                ? data.rooms.filter(r => r.is_compatible)
                : [];

            if (compatibleRooms.length === 0) {

                messageEl.textContent =
                    data.message ||
                    'No compatible rooms available for this time slot.';

                messageEl.classList.remove('hidden');

                return;
            }

            data.rooms = compatibleRooms;

            optionsEl.innerHTML = '';

            optionsEl.classList.remove('hidden');

            if (data.required_category) {

                const badge = document.createElement('div');

                badge.className =
                    'col-span-full mb-2 px-3 py-1.5 bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-400 text-xs font-bold rounded-lg border border-teal-200 dark:border-teal-800/30 flex items-center gap-1.5';

                badge.innerHTML = `
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 11-18 0z"/>
                    </svg>
                    Requires: ${data.required_category} or General
                `;

                optionsEl.appendChild(badge);
            }

            data.rooms.forEach((room, index) => {

                const label = document.createElement('label');

                label.className =
                    'relative cursor-pointer group block';

                const radio = document.createElement('input');

                radio.type = 'radio';
                radio.name = `room_select_${appointmentId}`;
                radio.value = room.id;
                radio.className = 'sr-only';

                const cardElement = document.createElement('div');

                cardElement.dataset.roomCard = '1';

                cardElement.className =
                    'text-center p-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 hover:border-teal-400 dark:hover:border-teal-500 transition-all h-full flex flex-col items-center justify-center cursor-pointer';

                cardElement.innerHTML = `
                    <div class="w-8 h-8 mb-1 rounded-lg bg-gray-100 dark:bg-gray-600 flex items-center justify-center group-hover:bg-teal-50 dark:group-hover:bg-teal-900/20 transition-colors">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>

                    <p class="font-bold text-sm text-gray-900 dark:text-white">
                        ${room.name}
                    </p>

                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        ${room.category || 'General'}
                    </p>
                `;

                function selectRoom() {

                    optionsEl
                        .querySelectorAll('[data-room-card="1"]')
                        .forEach(roomCard => {

                            roomCard.classList.remove(
                                'ring-2',
                                'ring-teal-500',
                                'ring-offset-2',
                                'dark:ring-offset-gray-800',
                                'border-teal-500',
                                'bg-teal-50',
                                'dark:bg-teal-900/20'
                            );
                        });

                    radio.checked = true;

                    cardElement.classList.add(
                        'ring-2',
                        'ring-teal-500',
                        'ring-offset-2',
                        'dark:ring-offset-gray-800',
                        'border-teal-500',
                        'bg-teal-50',
                        'dark:bg-teal-900/20'
                    );

                    hiddenInput.value = room.id;

                    const saveRoomBtn = document.getElementById('save-room-btn-' + appointmentId);
                    if (saveRoomBtn) {
                        saveRoomBtn.disabled = false;
                    }
                }

                radio.addEventListener('change', selectRoom);

                cardElement.addEventListener('click', function (event) {

                    event.preventDefault();

                    selectRoom();
                });

                label.appendChild(radio);
                label.appendChild(cardElement);

                optionsEl.appendChild(label);

                const currentRoomId = String(card.dataset.currentRoomId || '');
                const hasCurrentMatch = currentRoomId && data.rooms.some(r => String(r.id) === currentRoomId);
                if (hasCurrentMatch ? String(room.id) === currentRoomId : index === 0) {
                    selectRoom();
                }
            });

            card.dataset.roomsLoaded = '1';

        })
        .catch(error => {

            console.error(
                'Failed to load rooms:',
                error
            );

            loadingEl.classList.remove('hidden');

            loadingEl.innerHTML = `
                <span class="text-red-500 flex items-center gap-1.5 justify-center font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Failed to load rooms. Please refresh.
                </span>
            `;
        });
}

function savePendingRoomAssignment(appointmentId) {
    const card = document.querySelector(`.appointment-card[data-id="${appointmentId}"]`);
    const hiddenInput = document.getElementById('room-id-' + appointmentId);
    const statusEl = document.getElementById('room-reassign-status-' + appointmentId);
    const saveBtn = document.getElementById('save-room-btn-' + appointmentId);

    if (!card || !hiddenInput || !hiddenInput.value || !statusEl || !saveBtn) return;

    const csrfToken = document.querySelector(`#confirm-form-${appointmentId} input[name="_token"]`)?.value
        || document.querySelector('meta[name="csrf-token"]')?.content
        || '';

    statusEl.classList.add('hidden');
    statusEl.classList.remove('text-emerald-700', 'dark:text-emerald-300', 'text-red-600', 'dark:text-red-400');
    statusEl.textContent = '';
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    fetch(card.dataset.reassignRoomUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ room_id: Number(hiddenInput.value) })
    })
        .then(async response => {
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Unable to save room assignment.');
            }
            return payload;
        })
        .then(payload => {
            if (payload.room) {
                card.dataset.currentRoomId = String(payload.room.id);
                hiddenInput.value = String(payload.room.id);

                const displayEl = document.getElementById('reserved-room-display-' + appointmentId);
                if (displayEl) {
                    displayEl.innerHTML = `<strong>Reserved Room:</strong> ${payload.room.name} <span class="text-[11px] font-medium text-teal-600 dark:text-teal-400">(Temporarily reserved while pending)</span>`;
                }
            }

            statusEl.textContent = (payload.message || 'Reserved room updated.') + ' (Appointment remains pending.)';
            statusEl.classList.add('text-emerald-700', 'dark:text-emerald-300');
            statusEl.classList.remove('hidden');
        })
        .catch(err => {
            statusEl.textContent = err.message || 'Unable to save room assignment.';
            statusEl.classList.add('text-red-600', 'dark:text-red-400');
            statusEl.classList.remove('hidden');
        })
        .finally(() => {
            saveBtn.textContent = 'Save Room Assignment';
            saveBtn.disabled = !hiddenInput.value;
        });
}

document.addEventListener('DOMContentLoaded', function () {

    // Initialize live pending expiration countdowns (room options load on demand)
    initPendingCountdowns();

});

/* ─── Live Pending Expiration Countdown Manager ─── */
function initPendingCountdowns() {
    const badges = document.querySelectorAll('.pending-countdown-badge');
    if (!badges.length) return;

    let refreshTimeout = null;

    function formatRemaining(totalSeconds) {
        if (totalSeconds <= 0) {
            return 'Expired — refreshing status...';
        }
        if (totalSeconds < 60) {
            return `Expires in ${totalSeconds}s`;
        }

        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        if (hours > 0) {
            return `Expires in ${hours}h ${minutes}m`;
        }
        if (minutes > 0) {
            return `Expires in ${minutes}m ${seconds}s`;
        }
        return 'Expires in less than 1 minute';
    }

    function updateAllCountdowns() {
        const now = Date.now();
        let hasExpiredItem = false;

        badges.forEach(badge => {
            const iso = badge.getAttribute('data-expires-at');
            if (!iso) return;

            const textEl = badge.querySelector('.countdown-text');
            if (!textEl) return;

            const deadline = new Date(iso).getTime();
            const diffSeconds = Math.floor((deadline - now) / 1000);

            if (diffSeconds <= 0) {
                textEl.textContent = 'Expired — refreshing status...';
                badge.classList.remove('bg-rose-50', 'text-rose-700', 'border-rose-200');
                badge.classList.add('bg-gray-100', 'text-gray-700', 'border-gray-300', 'dark:bg-gray-800', 'dark:text-gray-300', 'animate-pulse');
                hasExpiredItem = true;
            } else {
                textEl.textContent = formatRemaining(diffSeconds);
                if (diffSeconds < 3600) {
                    badge.classList.add('animate-pulse');
                }
            }
        });

        // If an appointment reached zero, debounce a subtle page reload to reconcile with backend
        if (hasExpiredItem && !refreshTimeout) {
            refreshTimeout = setTimeout(() => {
                window.location.reload();
            }, 3000);
        }
    }

    // Run immediately
    updateAllCountdowns();

    // Recalculate every second against absolute epoch timestamps (handles tab backgrounding & sleep)
    const countdownInterval = setInterval(updateAllCountdowns, 1000);

    // Refresh immediately when tab regains focus or visibility
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            updateAllCountdowns();
        }
    });

    window.addEventListener('beforeunload', () => {
        clearInterval(countdownInterval);
        if (refreshTimeout) clearTimeout(refreshTimeout);
    });
}

</script>

@endsection
