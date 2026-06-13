@extends('layouts.receptionist')

@section('title', 'Active Sessions')

@php
$calendarEvents = $appointments->map(function($a) {
    $totalPaid = $a->payments->sum('amount');
    $depositPaid = $a->payments->where('type', 'deposit')->sum('amount');
    
    $staffAbsent = false;
    if ($a->appointment_date->isToday() && $a->user_id) {
        $staffAbsent = \App\Models\Attendance::where('user_id', $a->user_id)
            ->whereDate('date', today())
            ->whereIn('status', ['absent', 'on_leave', 'holiday'])
            ->exists();
    }

    $color = $staffAbsent ? '#ef4444' : (
        $totalPaid >= $a->total_price ? '#22c55e' : (
            $depositPaid > 0 ? '#f59e0b' : (
                $totalPaid == 0 ? '#f97316' : '#3b82f6'
            )
        )
    );

    return [
        'id' => $a->id,
        'title' => $a->customer->full_name,
        'start' => $a->appointment_date->format('Y-m-d') . 'T' . $a->start_time,
        'end' => $a->appointment_date->format('Y-m-d') . 'T' . $a->end_time,
        'color' => $color,
        'extendedProps' => [
            'customer' => $a->customer->full_name,
            'staff' => $a->staff->full_name ?? 'Unassigned',
            'staffId' => $a->user_id,
            'roomId' => $a->room_id,
            'time' => date('g:i A', strtotime($a->start_time)) . ' - ' . date('g:i A', strtotime($a->end_time)),
            'services' => $a->services->pluck('name')->join(', '),
            'total' => $a->total_price,
            'paid' => $totalPaid,
            'cardId' => 'appt-card-' . $a->id,
            'staffAbsent' => $staffAbsent,
        ]
    ];
});

$staffList = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'staff'))
    ->orderBy('last_name')
    ->get(['id', 'first_name', 'last_name']);

$catalogServices = \App\Models\Service::where('is_active', true)
    ->orderBy('name')
    ->get(['id', 'name', 'price']);

$allRooms = \App\Models\Room::where('status', '!=', 'maintenance')
    ->orderBy('name')
    ->get(['id', 'name']);
@endphp

@section('content')
<!-- Toast Container -->
<div id="toastContainer" class="fixed top-5 right-5 z-[60] flex flex-col gap-3 pointer-events-none"></div>

@if(session('success'))
    <div data-toast='{"type":"success","message":"{{ session('success') }}"}' class="hidden"></div>
@endif
@if(session('error'))
    <div data-toast='{"type":"error","message":"{{ session('error') }}"}' class="hidden"></div>
@endif

<!-- Page Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 animate-fade-in">
    <div>
        <h1 class="text-3xl font-bold text-teal-600 dark:text-teal-400 tracking-tight">Active Sessions</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage ongoing appointments and collect payments</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 px-4 py-2 rounded-full text-sm font-semibold shadow-sm">
            {{ $appointments->count() }} active
        </span>
        <a href="{{ route('receptionist.pending') }}" class="bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg text-sm font-medium transition">
            ← Back to Pending
        </a>
    </div>
</div>

<!-- Calendar View -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8 animate-fade-in">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 gap-3">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Appointment Calendar
        </h2>
        <div class="flex items-center gap-3 text-xs flex-wrap">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500"></span> Fully Paid</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-500"></span> Deposit</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-orange-500"></span> Cash on Site</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500"></span> Staff Absent</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500"></span> Partial</span>
        </div>
    </div>
    <div id="calendar" class="min-h-[500px]"></div>
</div>

@if($appointments->isEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center animate-fade-in">
        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-1">No active sessions</h3>
        <p class="text-gray-500 dark:text-gray-400 text-sm">All appointments are either pending or completed.</p>
    </div>
@else
    <div class="space-y-5" id="appointmentsList">
        @foreach($appointments as $appointment)
        @php
            $totalPaid = $appointment->payments->sum('amount');
            $balanceDue = max($appointment->total_price - $totalPaid, 0);
            $hasExtra = $appointment->services->contains('pivot.is_extra', true);
            $depositPaid = $appointment->payments->where('type', 'deposit')->sum('amount');
            $isFullyPaid = $balanceDue <= 0;
            $isCashOnSite = $totalPaid == 0 && $balanceDue == $appointment->total_price;
            
            $staffAbsent = false;
            if ($appointment->appointment_date->isToday() && $appointment->user_id) {
                $staffAbsent = \App\Models\Attendance::where('user_id', $appointment->user_id)
                    ->whereDate('date', today())
                    ->whereIn('status', ['absent', 'on_leave', 'holiday'])
                    ->exists();
            }

            $existingServicesJson = $appointment->services
                ->where('pivot.is_extra', false)
                ->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'price' => $s->pivot->price_at_booking])
                ->values();
        @endphp

        <div id="appt-card-{{ $appointment->id }}" 
             data-appointment-id="{{ $appointment->id }}"
             data-staff-id="{{ $appointment->user_id }}"
             data-room-id="{{ $appointment->room_id }}"
             data-duration="{{ $appointment->services->sum('duration_minutes') }}"
             class="appointment-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border {{ $staffAbsent ? 'border-red-300 dark:border-red-700 ring-2 ring-red-100 dark:ring-red-900/30' : 'border-gray-200 dark:border-gray-700' }} overflow-hidden hover:shadow-md transition-all duration-300 animate-fade-in">
            
            <!-- Card Header -->
            <div class="p-5 pb-0">
                <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-3 flex-wrap">
                            <span class="bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 text-xs px-2.5 py-1 rounded-full font-semibold tracking-wide uppercase">Confirmed</span>
                            @if($staffAbsent)
                            <span class="bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300 text-xs px-2.5 py-1 rounded-full font-bold tracking-wide uppercase animate-pulse">⚠ Staff Absent</span>
                            @endif
                            @if($hasExtra)
                            <span class="bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-300 text-xs px-2.5 py-1 rounded-full font-bold tracking-wide uppercase">Extra Added</span>
                            @endif
                            @if($isFullyPaid)
                            <span class="bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 text-xs px-2.5 py-1 rounded-full font-semibold tracking-wide uppercase">Fully Paid</span>
                            @elseif($isCashOnSite)
                            <span class="bg-orange-100 dark:bg-orange-900/40 text-orange-800 dark:text-orange-300 text-xs px-2.5 py-1 rounded-full font-semibold tracking-wide uppercase">Cash on Site</span>
                            @elseif($depositPaid > 0)
                            <span class="bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 text-xs px-2.5 py-1 rounded-full font-semibold tracking-wide uppercase">Deposit Paid</span>
                            @endif
                            <span class="text-gray-400 dark:text-gray-500 text-xs font-mono">#{{ $appointment->id }}</span>
                        </div>

                        <h3 class="text-xl font-bold text-gray-900 dark:text-white truncate">{{ $appointment->customer->full_name }}</h3>
                        
                        <div class="flex items-center gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ $appointment->customer->phone_number ?? 'N/A' }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $appointment->staff->full_name ?? 'Unassigned' }}
                            </span>
                            @if($appointment->room)
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                {{ $appointment->room->name }}
                            </span>
                            @endif
                        </div>
                    </div>

                    <div class="text-left lg:text-right shrink-0">
                        <p class="text-lg font-bold text-teal-600 dark:text-teal-400">{{ $appointment->appointment_date->format('F d, Y') }}</p>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-0.5 flex items-center lg:justify-end gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">{{ $appointment->services->sum('duration_minutes') }} minutes</p>
                    </div>
                </div>
            </div>

            <!-- Services -->
            <div class="px-5 py-4">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Services</p>
                <div class="flex flex-wrap gap-2" id="services-{{ $appointment->id }}">
                    @foreach($appointment->services as $service)
                    <span class="service-tag inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm border {{ $service->pivot->is_extra ? 'bg-purple-50 border-purple-200 dark:bg-purple-900/20 dark:border-purple-700 text-purple-700 dark:text-purple-300' : 'bg-gray-50 border-gray-200 dark:bg-gray-700/50 dark:border-gray-600 text-gray-700 dark:text-gray-300' }}">
                        {{ $service->name }}
                        <span class="font-semibold">₱{{ number_format($service->pivot->price_at_booking, 2) }}</span>
                        @if($service->pivot->is_extra)
                            <span class="text-[10px] bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-purple-200 px-1.5 py-0.5 rounded font-bold uppercase">Extra</span>
                        @endif
                    </span>
                    @endforeach
                </div>

                <!-- Financial Summary -->
                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-3 text-center">
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Total</p>
                        <p class="text-lg font-bold text-gray-800 dark:text-gray-200" id="total-{{ $appointment->id }}">₱{{ number_format($appointment->total_price, 2) }}</p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center">
                        <p class="text-[10px] text-green-600 dark:text-green-400 uppercase tracking-wider font-semibold">Paid</p>
                        <p class="text-lg font-bold text-green-700 dark:text-green-400" id="paid-{{ $appointment->id }}">₱{{ number_format($totalPaid, 2) }}</p>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3 text-center {{ $isFullyPaid ? 'opacity-50' : '' }}">
                        <p class="text-[10px] text-amber-600 dark:text-amber-400 uppercase tracking-wider font-semibold">Balance</p>
                        <p class="text-lg font-bold text-amber-700 dark:text-amber-400" id="balance-{{ $appointment->id }}">₱{{ number_format($balanceDue, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="px-5 py-4 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-2.5">
                <button onclick="App.openExtra({{ $appointment->id }}, {{ $existingServicesJson }})"
                        class="btn-action bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white px-4 py-2.5 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium shadow-sm hover:shadow">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Add Extra
                </button>

                @if($balanceDue > 0)
                <button onclick="App.openComplete({{ $appointment->id }}, {{ $balanceDue }})"
                        class="btn-action bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white px-4 py-2.5 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium shadow-sm hover:shadow">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Complete (₱{{ number_format($balanceDue, 2) }})
                </button>
                @else
                <form action="{{ route('receptionist.complete', $appointment) }}" method="POST" class="inline" onsubmit="App.setLoading(this)">
                    @csrf
                    <input type="hidden" name="payment_method" value="cash">
                    <input type="hidden" name="payment_type" value="full">
                    <button type="submit" class="btn-action bg-green-600 hover:bg-green-700 active:bg-green-800 text-white px-4 py-2.5 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium shadow-sm hover:shadow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="btn-text">Mark Complete</span>
                        <svg class="btn-spinner w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </form>
                @endif

                <button onclick="App.openReassign({{ $appointment->id }}, '{{ $appointment->staff->full_name ?? 'Unassigned' }}')"
                        class="btn-action bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2.5 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Reassign
                </button>

                <button onclick="App.openReschedule({{ $appointment->id }}, '{{ $appointment->appointment_date }}', '{{ $appointment->start_time }}', {{ $appointment->services->sum('duration_minutes') }}, {{ $appointment->user_id ?? 'null' }}, {{ $appointment->room_id ?? 'null' }})"
                        class="btn-action bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2.5 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Reschedule
                </button>

                <button onclick="App.openNoShow({{ $appointment->id }}, {{ $totalPaid }})"
                        class="btn-action ml-auto bg-red-50 hover:bg-red-100 active:bg-red-200 text-red-700 dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:text-red-300 px-4 py-2.5 rounded-lg transition-all duration-200 text-sm font-medium border border-red-200 dark:border-red-800">
                    No Show
                </button>
            </div>
        </div>
        @endforeach
    </div>
@endif

<!-- ════════════════════════════════════════ -->
<!-- ═══════════════ MODALS ═════════════════ -->
<!-- ════════════════════════════════════════ -->

<!-- Extra Service Modal -->
<div id="modal-extra" class="modal-backdrop fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center opacity-0 transition-opacity duration-200">
    <div class="modal-content bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all duration-200 scale-95 opacity-0">
        <div class="p-6">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Add Extra Service</h3>
                <button onclick="App.closeModal('modal-extra')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="" id="form-extra" onsubmit="App.setLoading(this)">
                @csrf
                <div class="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1 mb-5">
                    <button type="button" onclick="App.setExtraTab('existing')" id="tab-existing" class="flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm">Existing Service</button>
                    <button type="button" onclick="App.setExtraTab('custom')" id="tab-custom" class="flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">From Catalog</button>
                </div>

                <div id="extra-existing" class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Click to add instantly:</p>
                    <div id="extra-existing-list" class="space-y-2"></div>
                    <input type="hidden" name="service_id" id="extra-service-id">
                </div>

                <div id="extra-custom" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Service</label>
                    <select name="custom_service_id" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                        <option value="">-- Choose a service --</option>
                        @foreach($catalogServices as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} — ₱{{ number_format($s->price, 2) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="App.closeModal('modal-extra')" class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-sm font-medium">Cancel</button>
                    <button type="submit" id="extra-custom-submit" class="hidden px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition text-sm font-medium shadow-sm flex items-center gap-2">
                        <span class="btn-text">Add Service</span>
                        <svg class="btn-spinner w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complete Payment Modal -->
<div id="modal-complete" class="modal-backdrop fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center opacity-0 transition-opacity duration-200">
    <div class="modal-content bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all duration-200 scale-95 opacity-0">
        <div class="p-6">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Complete & Collect</h3>
                <button onclick="App.closeModal('modal-complete')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="" id="form-complete" onsubmit="App.setLoading(this)">
                @csrf
                <div class="mb-5 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                    <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold uppercase tracking-wider mb-1">Balance Due</p>
                    <p class="text-3xl font-bold text-amber-700 dark:text-amber-400" id="complete-balance">₱0.00</p>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Payment Method <span class="text-red-500">*</span></label>
                        <select name="payment_method" required class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="gcash">GCash</option>
                            <option value="paymaya">PayMaya</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="payment_type" value="completion">
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="App.closeModal('modal-complete')" class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-sm font-medium">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition text-sm font-medium shadow-sm flex items-center gap-2">
                        <span class="btn-text">Complete</span>
                        <svg class="btn-spinner w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- No Show Modal -->
<div id="modal-noshow" class="modal-backdrop fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center opacity-0 transition-opacity duration-200">
    <div class="modal-content bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all duration-200 scale-95 opacity-0">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-red-600 dark:text-red-400">Customer No Show</h3>
                <button onclick="App.closeModal('modal-noshow')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="noshow-info" class="mb-5"></div>
            <form method="POST" action="" id="form-noshow" onsubmit="App.setLoading(this)">
                @csrf
                <div id="noshow-actions" class="space-y-3 mb-5 hidden">
                    <label class="flex items-start gap-3 p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer hover:border-red-300 dark:hover:border-red-700 hover:bg-red-50 dark:hover:bg-red-900/10 transition group">
                        <input type="radio" name="action" value="forfeit" checked class="mt-1 text-red-600 focus:ring-red-500">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white group-hover:text-red-700 dark:group-hover:text-red-400 transition">Forfeit Payment</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Keep the payment as revenue</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer hover:border-orange-300 dark:hover:border-orange-700 hover:bg-orange-50 dark:hover:bg-orange-900/10 transition group">
                        <input type="radio" name="action" value="refund" class="mt-1 text-orange-600 focus:ring-orange-500">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white group-hover:text-orange-700 dark:group-hover:text-orange-400 transition">Refund Payment</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Return payment to customer</p>
                        </div>
                    </label>
                </div>
                <input type="hidden" name="action" value="forfeit" id="noshow-default">
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="App.closeModal('modal-noshow')" class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-sm font-medium">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition text-sm font-medium shadow-sm flex items-center gap-2">
                        <span class="btn-text">Confirm No Show</span>
                        <svg class="btn-spinner w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reassign Staff Modal -->
<div id="modal-reassign" class="modal-backdrop fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center opacity-0 transition-opacity duration-200">
    <div class="modal-content bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all duration-200 scale-95 opacity-0">
        <div class="p-6">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Reassign Staff</h3>
                <button onclick="App.closeModal('modal-reassign')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="" id="form-reassign" onsubmit="App.setLoading(this)">
                @csrf
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select New Staff <span class="text-red-500">*</span></label>
                    <select name="staff_id" required class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                        <option value="">-- Choose staff --</option>
                        @foreach($staffList as $s)
                            <option value="{{ $s->id }}">{{ $s->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="App.closeModal('modal-reassign')" class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-sm font-medium">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition text-sm font-medium shadow-sm flex items-center gap-2">
                        <span class="btn-text">Reassign</span>
                        <svg class="btn-spinner w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reschedule Modal -->
<div id="modal-reschedule" class="modal-backdrop fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center opacity-0 transition-opacity duration-200">
    <div class="modal-content bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl mx-4 transform transition-all duration-200 scale-95 opacity-0">
        <div class="p-6">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Reschedule Appointment</h3>
                <button onclick="App.closeModal('modal-reschedule')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="" id="form-reschedule" onsubmit="App.setLoading(this)">
                @csrf
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        1. Pick New Date <span class="text-red-500">*</span>
                    </label>
                    <div id="reschedule-calendar" class="min-h-[380px] rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600"></div>
                    <input type="hidden" name="appointment_date" id="reschedule-date" required>
                    <p id="reschedule-date-display" class="text-sm text-teal-600 dark:text-teal-400 mt-2 font-semibold"></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            2. Pick New Time <span class="text-red-500">*</span>
                        </label>
                        <div id="reschedule-slots" class="grid grid-cols-3 sm:grid-cols-4 gap-2 max-h-56 overflow-y-auto p-1">
                            <p class="text-sm text-gray-400 col-span-full text-center py-8">Select a date to view slots</p>
                        </div>
                        <input type="hidden" name="start_time" id="reschedule-time" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            3. Choose Room
                        </label>
                        <select name="room_id" id="reschedule-room" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                            <option value="">No room / Remove room</option>
                            @foreach($allRooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-2">If you change the room, the old room will be freed automatically.</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="App.closeModal('modal-reschedule')" class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-sm font-medium">Cancel</button>
                    <button type="submit" id="reschedule-submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition text-sm font-medium shadow-sm flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <span class="btn-text">Reschedule</span>
                        <svg class="btn-spinner w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
const App = {
    mainCalendar: null,
    rescheduleCalendar: null,
    currentReschedule: { id: null, staffId: null, roomId: null, duration: 60 },
    appointments: @json($calendarEvents),

    init() {
        this.initMainCalendar();
        this.convertSessionFlashes();
        this.bindGlobalEvents();
    },

    toast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const el = document.createElement('div');
        const colors = { success: 'bg-green-500', error: 'bg-red-500', warning: 'bg-amber-500', info: 'bg-blue-500' };
        const icons = {
            success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
            error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
            warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>',
            info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        };

        el.className = `${colors[type]} text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-3 transform transition-all duration-300 translate-x-full opacity-0 pointer-events-auto min-w-[300px]`;
        el.innerHTML = `${icons[type]}<<span class="font-medium text-sm">${message}</span>`;
        
        container.appendChild(el);
        requestAnimationFrame(() => el.classList.remove('translate-x-full', 'opacity-0'));

        setTimeout(() => {
            el.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => el.remove(), 300);
        }, 4000);
    },

    convertSessionFlashes() {
        document.querySelectorAll('[data-toast]').forEach(el => {
            const data = JSON.parse(el.dataset.toast);
            this.toast(data.message, data.type);
            el.remove();
        });
    },

    setLoading(form) {
        const btn = form.querySelector('button[type="submit"]');
        if (!btn) return;
        btn.disabled = true;
        const spinner = btn.querySelector('.btn-spinner');
        const text = btn.querySelector('.btn-text');
        if (spinner) spinner.classList.remove('hidden');
        if (text) text.dataset.original = text.textContent;
    },

    openModal(id) {
        const backdrop = document.getElementById(id);
        const content = backdrop.querySelector('.modal-content');
        backdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        });
    },

    closeModal(id) {
        const backdrop = document.getElementById(id);
        const content = backdrop.querySelector('.modal-content');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        backdrop.classList.add('opacity-0');
        setTimeout(() => {
            backdrop.classList.add('hidden');
            document.body.style.overflow = '';
            if (id === 'modal-reschedule' && this.rescheduleCalendar) {
                this.rescheduleCalendar.destroy();
                this.rescheduleCalendar = null;
            }
        }, 200);
    },

    initMainCalendar() {
        const el = document.getElementById('calendar');
        if (!el) return;
        this.mainCalendar = new FullCalendar.Calendar(el, {
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: { left: 'title', center: '', right: 'prev,next' },
            events: this.appointments,
            eventClick: (info) => {
                const card = document.getElementById(info.event.extendedProps.cardId);
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    card.classList.add('ring-2', 'ring-teal-500', 'scale-[1.02]');
                    setTimeout(() => card.classList.remove('ring-2', 'ring-teal-500', 'scale-[1.02]'), 2000);
                }
            },
            eventContent: (arg) => ({
                html: `<div class="px-2 py-1 text-xs font-bold truncate">${arg.event.title}</div><div class="px-2 text-[10px] opacity-90 truncate">${arg.event.extendedProps.time}</div>`
            })
        });
        this.mainCalendar.render();
    },

    openReschedule(id, currentDate, currentTime, duration, staffId, roomId) {
        this.currentReschedule = { id, staffId, roomId, duration };
        document.getElementById('form-reschedule').action = `/receptionist/appointments/${id}/reschedule`;
        document.getElementById('reschedule-submit').disabled = true;
        document.getElementById('reschedule-date').value = '';
        document.getElementById('reschedule-time').value = '';
        document.getElementById('reschedule-date-display').textContent = '';
        document.getElementById('reschedule-slots').innerHTML = '<p class="text-sm text-gray-400 col-span-full text-center py-8">Select a date to view slots</p>';
        
        const roomSelect = document.getElementById('reschedule-room');
        if (roomSelect) roomSelect.value = roomId || '';

        this.openModal('modal-reschedule');
        setTimeout(() => this.initRescheduleCalendar(currentDate), 100);
    },

    initRescheduleCalendar(currentDate) {
        const el = document.getElementById('reschedule-calendar');
        if (this.rescheduleCalendar) this.rescheduleCalendar.destroy();

        const backgroundEvents = this.appointments
            .filter(a => a.id !== this.currentReschedule.id)
            .map(a => ({ start: a.start, end: a.end, display: 'background', color: '#fee2e2' }));

        this.rescheduleCalendar = new FullCalendar.Calendar(el, {
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: { left: 'title', center: '', right: 'prev,next' },
            validRange: { start: new Date().toISOString().split('T')[0] },
            events: backgroundEvents,
            dateClick: (info) => {
                document.getElementById('reschedule-date').value = info.dateStr;
                const dateObj = new Date(info.dateStr + 'T00:00:00');
                document.getElementById('reschedule-date-display').textContent = 'Selected: ' + dateObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                this.loadRescheduleSlots(info.dateStr);
            }
        });
        this.rescheduleCalendar.render();
    },

    async loadRescheduleSlots(dateStr) {
        const container = document.getElementById('reschedule-slots');
        const duration = this.currentReschedule.duration;

        container.innerHTML = Array(8).fill(0).map(() => 
            `<div class="h-12 bg-gray-100 dark:bg-gray-700 rounded-xl animate-pulse"></div>`
        ).join('');

        try {
            const res = await fetch(`/api/booking/slots?date=${dateStr}&duration=${duration}`);
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to load slots');

            const slots = data.slots?.filter(s => s.date === dateStr && !s.occupied) || [];

            if (slots.length === 0) {
                container.innerHTML = '<div class="col-span-full text-center py-8"><p class="text-gray-400 text-sm">No available slots for this date</p><p class="text-xs text-gray-300 mt-1">Try a different date</p></div>';
                return;
            }

            container.innerHTML = '';
            slots.forEach(slot => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'time-slot py-3 px-2 rounded-xl border-2 text-sm font-bold text-center transition-all duration-200 bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:border-teal-400 hover:bg-teal-50 dark:hover:bg-teal-900/20';
                btn.textContent = slot.display;
                btn.onclick = () => {
                    container.querySelectorAll('.time-slot').forEach(b => {
                        b.classList.remove('bg-teal-600', 'text-white', 'border-teal-600', 'selected');
                        b.classList.add('bg-white', 'dark:bg-gray-700', 'border-gray-200', 'dark:border-gray-600', 'text-gray-700', 'dark:text-gray-200');
                    });
                    btn.classList.remove('bg-white', 'dark:bg-gray-700', 'border-gray-200', 'dark:border-gray-600', 'text-gray-700', 'dark:text-gray-200');
                    btn.classList.add('bg-teal-600', 'text-white', 'border-teal-600', 'selected');
                    document.getElementById('reschedule-time').value = slot.time;
                    document.getElementById('reschedule-submit').disabled = false;
                };
                container.appendChild(btn);
            });
        } catch (err) {
            container.innerHTML = `
                <div class="col-span-full text-center py-8">
                    <p class="text-red-500 text-sm mb-2">Unable to load slots</p>
                    <button onclick="App.loadRescheduleSlots('${dateStr}')" class="text-teal-600 text-xs font-semibold hover:underline">Retry</button>
                </div>`;
        }
    },

    openExtra(id, services) {
        document.getElementById('form-extra').action = `/receptionist/appointments/${id}/add-extra`;
        const list = document.getElementById('extra-existing-list');
        list.innerHTML = '';

        if (services.length === 0) {
            list.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No existing services to duplicate.</p>';
        } else {
            services.forEach(s => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-purple-50 dark:hover:bg-purple-900/30 border border-gray-200 dark:border-gray-600 rounded-lg transition flex justify-between items-center group';
                btn.innerHTML = `<span class="font-medium text-gray-800 dark:text-gray-200 group-hover:text-purple-700 dark:group-hover:text-purple-300 transition">${s.name}</span><span class="text-purple-600 dark:text-purple-400 font-bold">+ ₱${parseFloat(s.price).toFixed(2)}</span>`;
                btn.onclick = () => {
                    document.getElementById('extra-service-id').value = s.id;
                    document.getElementById('form-extra').submit();
                };
                list.appendChild(btn);
            });
        }

        document.getElementById('extra-service-id').value = '';
        this.setExtraTab('existing');
        this.openModal('modal-extra');
    },

    setExtraTab(tab) {
        const existing = document.getElementById('extra-existing');
        const custom = document.getElementById('extra-custom');
        const submit = document.getElementById('extra-custom-submit');
        const tabE = document.getElementById('tab-existing');
        const tabC = document.getElementById('tab-custom');

        const active = 'flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm';
        const inactive = 'flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200';

        if (tab === 'existing') {
            existing.classList.remove('hidden'); custom.classList.add('hidden'); submit.classList.add('hidden');
            tabE.className = active; tabC.className = inactive;
        } else {
            existing.classList.add('hidden'); custom.classList.remove('hidden'); submit.classList.remove('hidden');
            tabE.className = inactive; tabC.className = active;
        }
    },

    openComplete(id, balance) {
        document.getElementById('form-complete').action = `/receptionist/appointments/${id}/complete`;
        document.getElementById('complete-balance').textContent = '₱' + balance.toFixed(2);
        this.openModal('modal-complete');
    },

    openNoShow(id, totalPaid) {
        document.getElementById('form-noshow').action = `/receptionist/appointments/${id}/no-show`;
        const hasPayments = totalPaid > 0;
        const info = document.getElementById('noshow-info');

        if (hasPayments) {
            info.innerHTML = `<div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800"><p class="text-xs text-amber-600 dark:text-amber-400 font-semibold uppercase tracking-wider mb-1">Payment on File</p><p class="text-2xl font-bold text-amber-700 dark:text-amber-400">₱${totalPaid.toFixed(2)}</p><p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Choose how to handle this payment:</p></div>`;
        } else {
            info.innerHTML = `<div class="p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-600"><p class="text-sm text-gray-600 dark:text-gray-400">No payment was collected. Mark as no-show?</p></div>`;
        }

        document.getElementById('noshow-actions').classList.toggle('hidden', !hasPayments);
        document.getElementById('noshow-default').value = 'forfeit';
        this.openModal('modal-noshow');
    },

    openReassign(id, currentStaffName) {
        document.getElementById('form-reassign').action = `/receptionist/appointments/${id}/reassign`;
        this.openModal('modal-reassign');
    },

    bindGlobalEvents() {
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) this.closeModal(backdrop.id);
            });
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-backdrop:not(.hidden)').forEach(m => this.closeModal(m.id));
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => App.init());
</script>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }

.time-slot.selected {
    background-color: rgb(13 148 136) !important;
    color: white !important;
    border-color: rgb(13 148 136) !important;
}

.dark ::-webkit-scrollbar { width: 8px; }
.dark ::-webkit-scrollbar-track { background: rgb(55 65 81); }
.dark ::-webkit-scrollbar-thumb { background: rgb(75 85 99); border-radius: 4px; }
.dark ::-webkit-scrollbar-thumb:hover { background: rgb(107 114 128); }

.fc-event { transition: transform 0.1s; cursor: pointer; }
.fc-event:hover { transform: scale(1.02); z-index: 10 !important; }

.btn-action:disabled { opacity: 0.7; cursor: not-allowed; }
</style>
@endsection