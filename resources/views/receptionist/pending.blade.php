@extends('layouts.receptionist')

@section('title', 'Pending Bookings')

@section('content')
@if(session('success'))
<div class="mb-5 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl text-emerald-700 dark:text-emerald-300 text-sm font-medium flex items-center gap-2.5">
    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-5 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded-xl text-red-700 dark:text-red-300 text-sm font-medium flex items-center gap-2.5">
    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ session('error') }}
</div>
@endif

@if($errors->any())
<div class="mb-5 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded-xl text-red-700 dark:text-red-300 text-sm">
    <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Pending Bookings</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Review and confirm incoming appointments</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 shadow-sm">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
            {{ $appointments->count() }} pending
        </span>
    </div>
</div>

@if($appointments->isEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-16 text-center">
        <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700/50 rounded-full flex items-center justify-center mx-auto mb-5">
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">All Caught Up</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">No pending bookings awaiting confirmation.</p>
    </div>
@else
    <div class="space-y-5">
        @foreach($appointments as $appointment)
        @php
            $maxDepositPercent = $appointment->services->max('deposit_percentage') ?? 0;
            $depositAmount = $appointment->total_price * ($maxDepositPercent / 100);
            $hasDeposit = $maxDepositPercent > 0;
            $requiresDeposit = $appointment->services->contains(function($s) {
                $min = $s->deposit_percentage_min ?? $s->category->deposit_percentage_min ?? 0;
                return $min > 0;
            });
            $systemDepositRequired = $appointment->services->sum(function($s) {
                $min = $s->deposit_percentage_min ?? $s->category->deposit_percentage_min ?? 0;
                return ($s->pivot->price_at_booking ?? $s->price) * ($min / 100);
            });
        @endphp

        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden" data-appointment-id="{{ $appointment->id }}">

            <!-- Card Header -->
            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700/50">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">

                    <!-- Left: Customer Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2.5 mb-3 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/30 text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Pending
                            </span>
                            <span class="text-xs font-mono text-gray-400 dark:text-gray-500">#{{ $appointment->id }}</span>
                            @if($hasDeposit)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/30 text-xs font-bold text-amber-700 dark:text-amber-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Deposit {{ $maxDepositPercent }}%
                            </span>
                            @endif
                        </div>

                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                            {{ $appointment->customer->full_name }}
                        </h3>

                        <a href="tel:{{ $appointment->customer->phone_number }}" 
                           class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition group">
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-teal-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ $appointment->customer->phone_number }}
                        </a>

                        @if($appointment->customer->medical_notes)
                        <div class="mt-4 p-3.5 bg-rose-50 dark:bg-rose-900/15 border border-rose-200 dark:border-rose-800/30 rounded-xl">
                            <div class="flex items-center gap-2 mb-1.5">
                                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span class="text-xs font-bold text-rose-700 dark:text-rose-300 uppercase tracking-wider">Medical Notes</span>
                            </div>
                            <p class="text-sm text-rose-800 dark:text-rose-200 italic leading-relaxed">{{ $appointment->customer->medical_notes }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Right: Date & Time -->
                    <div class="lg:text-right shrink-0">
                        <div class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600/50">
                            <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('F j, Y') }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
                                </p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 lg:text-right">
                            {{ $appointment->services->sum('duration_minutes') }} minutes total
                        </p>
                    </div>
                </div>
            </div>

            <!-- Services -->
            <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/20 border-b border-gray-100 dark:border-gray-700/50">
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Services</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($appointment->services as $service)
                    <div class="inline-flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-sm shadow-sm">
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $service->name }}</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">₱{{ number_format($service->pivot->price_at_booking, 2) }}</span>
                        @php
                            $depositPercent = $service->deposit_percentage ?? $service->category->deposit_percentage ?? 0;
                        @endphp
                        @if($depositPercent > 0)
                            <span class="text-[10px] font-bold px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded">{{ $depositPercent }}%</span>
                        @endif
                    </div>
                    @endforeach
                </div>
                <div class="flex items-center gap-6 mt-4 pt-3 border-t border-gray-200 dark:border-gray-700/50">
                    <div>
                        <span class="text-xs text-gray-400 dark:text-gray-500">Total</span>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">₱{{ number_format($appointment->total_price, 2) }}</p>
                    </div>
                    @if($hasDeposit)
                    <div>
                        <span class="text-xs text-amber-600 dark:text-amber-400">Min. Deposit</span>
                        <p class="text-lg font-bold text-amber-700 dark:text-amber-400">₱{{ number_format($depositAmount, 2) }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Confirm Form -->
            <div class="px-6 py-5">
                <form id="confirm-form-{{ $appointment->id }}" action="{{ route('receptionist.confirm', $appointment) }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                        <!-- Staff -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                Assign Staff <span class="text-red-500">*</span>
                            </label>
                            <select name="staff_id" required 
                                    class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition">
                                <option value="">Select staff...</option>
                                @foreach(\App\Models\User::whereHas('roles', function($q) { $q->where('name', 'staff'); })->orderBy('last_name')->get() as $staff)
                                    <option value="{{ $staff->id }}" {{ $appointment->user_id == $staff->id ? 'selected' : '' }}>
                                        {{ $staff->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                Payment Method <span class="text-red-500">*</span>
                            </label>
                            <select name="payment_method" required
                                    class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="gcash">GCash</option>
                                <option value="paymaya">PayMaya</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <!-- Payment Type -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                Payment Type <span class="text-red-500">*</span>
                            </label>
                            <select name="payment_type" id="paymentType_{{ $appointment->id }}" required
                                    onchange="updateAmount({{ $appointment->id }}, {{ $appointment->total_price }}, {{ $systemDepositRequired }})"
                                    class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition">
                                <option value="full">Full Payment</option>
                                @if($requiresDeposit)
                                <option value="deposit">Deposit Only</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <!-- Room Selection -->
                    <div class="mb-5 p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-600/50">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                            Room Assignment
                        </label>
                        <div id="room-loading-{{ $appointment->id }}" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 py-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            Checking room requirements...
                        </div>
                        <div id="room-options-{{ $appointment->id }}" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 hidden"></div>
                        <div id="room-message-{{ $appointment->id }}" class="text-sm text-amber-600 dark:text-amber-400 mt-2 hidden"></div>
                        <input type="hidden" name="room_id" id="room-id-{{ $appointment->id }}" value="">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                        <!-- Amount -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                Amount Received (₱) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">₱</span>
                                <input type="number" name="amount" id="amount_{{ $appointment->id }}" 
                                    step="0.01" min="0" required
                                    value="{{ $requiresDeposit ? $systemDepositRequired : $appointment->total_price }}"
                                    class="w-full pl-8 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white text-sm font-bold focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition">
                            </div>
                            @if($requiresDeposit)
                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1.5" id="amountHint_{{ $appointment->id }}">
                                System minimum: ₱{{ number_format($systemDepositRequired, 2) }}
                            </p>
                            @endif
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                Notes <span class="text-gray-300 dark:text-gray-600 font-normal">(optional)</span>
                            </label>
                            <input type="text" name="notes" placeholder="Special requests, preferences..."
                                class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition">
                        </div>
                    </div>
                </form>

                <!-- Actions -->
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" form="confirm-form-{{ $appointment->id }}"
                            class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-xl text-sm font-bold transition shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        Confirm Booking
                    </button>

                    <a href="tel:{{ $appointment->customer->phone_number }}" 
                       class="inline-flex items-center gap-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-5 py-3 rounded-xl text-sm font-bold transition">
                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Call
                    </a>

                    <!-- Cancel -->
                    <form action="{{ route('receptionist.cancel', $appointment) }}" method="POST" class="inline ml-auto" onsubmit="return confirm('Cancel this booking?')">
                        @csrf
                        <div class="flex items-center gap-2">
                            <select name="reason" required class="border border-gray-200 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none">
                                <option value="">Reason...</option>
                                <option value="customer_cancelled">Customer Cancelled</option>
                                <option value="staff_unavailable">Staff Unavailable</option>
                                <option value="other">Other</option>
                            </select>
                            <button type="submit" 
                                    class="inline-flex items-center gap-1.5 text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 px-4 py-2.5 rounded-lg transition text-sm font-bold border border-transparent hover:border-red-200 dark:hover:border-red-800/30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@endif

<script>
function updateAmount(id, total, deposit) {
    const type = document.getElementById('paymentType_' + id).value;
    const amountInput = document.getElementById('amount_' + id);
    const hint = document.getElementById('amountHint_' + id);

    if (type === 'deposit') {
        amountInput.value = deposit.toFixed(2);
        if (hint) hint.textContent = 'Minimum deposit: ₱' + deposit.toFixed(2);
    } else {
        amountInput.value = total.toFixed(2);
        if (hint) hint.textContent = 'Full payment required';
    }
}

// Load available rooms for each appointment
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-appointment-id]').forEach(function(card) {
        const appointmentId = card.dataset.appointmentId;
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
                    loadingEl.textContent = 'No room required for this service.';
                    loadingEl.classList.remove('hidden');
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
                    badge.className = 'col-span-full mb-1 px-3 py-1.5 bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-400 text-xs font-bold rounded-lg border border-teal-200 dark:border-teal-800/30';
                    badge.textContent = 'Requires: ' + data.required_category + ' or General';
                    optionsEl.appendChild(badge);
                }

                data.rooms.forEach(function(room) {
                    const label = document.createElement('label');
                    label.className = 'cursor-pointer';
                    label.innerHTML = `
                        <input type="radio" name="room_select_${appointmentId}" value="${room.id}" class="peer hidden" onchange="document.getElementById('room-id-${appointmentId}').value = this.value">
                        <div class="text-center p-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-teal-500 peer-checked:bg-teal-50 dark:peer-checked:bg-teal-900/20 transition hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <p class="font-bold text-sm text-gray-900 dark:text-white">${room.name}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">${room.category || 'General'}</p>
                        </div>
                    `;
                    optionsEl.appendChild(label);
                });
            })
            .catch(err => {
                console.error('Failed to load rooms:', err);
                loadingEl.innerHTML = '<span class="text-red-500 flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Failed to load rooms</span>';
            });
    });
});
</script>
@endsection