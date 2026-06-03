@extends('layouts.receptionist')

@section('title', 'Active Sessions')

@section('content')
@if(session('success'))
<div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg border border-green-200 dark:border-green-800 animate-fade-in">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg border border-red-200 dark:border-red-800 animate-fade-in">
    {{ session('error') }}
</div>
@endif

<!-- Page Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
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

@if($appointments->isEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-1">No active sessions</h3>
        <p class="text-gray-500 dark:text-gray-400 text-sm">All appointments are either pending or completed.</p>
    </div>
@else
    <div class="space-y-5">
        @foreach($appointments as $appointment)
        @php
            $totalPaid = $appointment->payments->sum('amount');
            $balanceDue = $appointment->total_price - $totalPaid;
            $hasExtra = $appointment->services->contains('pivot.is_extra', true);
            $depositPaid = $appointment->payments->where('type', 'deposit')->sum('amount');
            $isFullyPaid = $balanceDue <= 0;
            
            // Serialize data for JS
            $existingServicesJson = $appointment->services
                ->where('pivot.is_extra', false)
                ->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'price' => $s->pivot->price_at_booking])
                ->values();
        @endphp
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow duration-300">
            <!-- Card Header -->
            <div class="p-5 pb-0">
                <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-4">
                    <!-- Left: Customer Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-3 flex-wrap">
                            <span class="bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 text-xs px-2.5 py-1 rounded-full font-semibold tracking-wide uppercase">
                                Confirmed
                            </span>
                            @if($hasExtra)
                            <span class="bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-300 text-xs px-2.5 py-1 rounded-full font-bold tracking-wide uppercase">
                                Extra Added
                            </span>
                            @endif
                            @if($isFullyPaid)
                            <span class="bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 text-xs px-2.5 py-1 rounded-full font-semibold tracking-wide uppercase">
                                Fully Paid
                            </span>
                            @endif
                            <span class="text-gray-400 dark:text-gray-500 text-xs font-mono">#{{ $appointment->id }}</span>
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white truncate">
                            {{ $appointment->customer->full_name }}
                        </h3>
                        
                        <div class="flex items-center gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400 flex-wrap">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                {{ $appointment->customer->phone_number ?? 'N/A' }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ $appointment->staff->full_name ?? 'Unassigned' }}
                            </span>
                        </div>
                    </div>

                    <!-- Right: Date & Time -->
                    <div class="text-left lg:text-right shrink-0">
                        <p class="text-lg font-bold text-teal-600 dark:text-teal-400">
                            {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('F d, Y') }}
                        </p>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-0.5 flex items-center lg:justify-end gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $appointment->services->sum('duration_minutes') }} minutes
                        </p>
                    </div>
                </div>
            </div>

            <!-- Services Section -->
            <div class="px-5 py-4">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Services</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($appointment->services as $service)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm border {{ $service->pivot->is_extra ? 'bg-purple-50 border-purple-200 dark:bg-purple-900/20 dark:border-purple-700 text-purple-700 dark:text-purple-300' : 'bg-gray-50 border-gray-200 dark:bg-gray-700/50 dark:border-gray-600 text-gray-700 dark:text-gray-300' }}">
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
                        <p class="text-lg font-bold text-gray-800 dark:text-gray-200">₱{{ number_format($appointment->total_price, 2) }}</p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center">
                        <p class="text-[10px] text-green-600 dark:text-green-400 uppercase tracking-wider font-semibold">Paid</p>
                        <p class="text-lg font-bold text-green-700 dark:text-green-400">₱{{ number_format($totalPaid, 2) }}</p>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3 text-center {{ $isFullyPaid ? 'opacity-50' : '' }}">
                        <p class="text-[10px] text-amber-600 dark:text-amber-400 uppercase tracking-wider font-semibold">Balance</p>
                        <p class="text-lg font-bold text-amber-700 dark:text-amber-400">₱{{ number_format(max($balanceDue, 0), 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="px-5 py-4 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-2.5">
                <!-- Add Extra Service -->
                <button onclick='openExtraModal({{ $appointment->id }}, @json($existingServicesJson))'
                        class="bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white px-4 py-2.5 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium shadow-sm hover:shadow">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Extra
                </button>

                @if($balanceDue > 0)
                <!-- Complete with Payment -->
                <button onclick="openCompleteModal({{ $appointment->id }}, {{ $balanceDue }})"
                        class="bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white px-4 py-2.5 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium shadow-sm hover:shadow">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Complete (₱{{ number_format($balanceDue, 2) }})
                </button>
                @else
                <!-- Mark Complete (no payment needed) -->
                <form action="{{ route('receptionist.complete', $appointment) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="payment_method" value="cash">
                    <input type="hidden" name="amount" value="0">
                    <input type="hidden" name="payment_type" value="full">
                    <button type="submit"
                            class="bg-green-600 hover:bg-green-700 active:bg-green-800 text-white px-4 py-2.5 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium shadow-sm hover:shadow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Mark Complete
                    </button>
                </form>
                @endif

                <!-- No Show -->
                <button onclick="openNoShowModal({{ $appointment->id }}, {{ $depositPaid }})"
                        class="ml-auto bg-red-50 hover:bg-red-100 active:bg-red-200 text-red-700 dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:text-red-300 px-4 py-2.5 rounded-lg transition-all duration-200 text-sm font-medium border border-red-200 dark:border-red-800">
                    No Show
                </button>
            </div>
        </div>
        @endforeach
    </div>
@endif

<!-- ==================== MODALS ==================== -->

<!-- Add Extra Service Modal -->
<div id="extraModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center transition-opacity duration-200">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all duration-200 scale-95 opacity-0" id="extraModalContent">
        <div class="p-6">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Add Extra Service</h3>
                <button onclick="closeExtraModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form id="extraForm" method="POST" action="">
                @csrf
                
                <div class="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1 mb-5">
                    <button type="button" onclick="setExtraTab('existing')" id="tab-existing" class="flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm">
                        Existing Service
                    </button>
                    <button type="button" onclick="setExtraTab('custom')" id="tab-custom" class="flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                        From Catalog
                    </button>
                </div>

                <!-- Existing Service -->
                <div id="existingServiceDiv" class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Click to add instantly:</p>
                    <div id="existingServicesList" class="space-y-2">
                        <!-- Populated by JS -->
                    </div>
                    <input type="hidden" name="service_id" id="existingServiceId">
                </div>

                <!-- Custom Service -->
                <div id="customServiceDiv" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Service</label>
                    <select name="custom_service_id" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                        <option value="">-- Choose a service --</option>
                        @foreach(\App\Models\Service::where('is_active', true)->orderBy('name')->get() as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} — ₱{{ number_format($s->price, 2) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="closeExtraModal()" class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-sm font-medium">
                        Cancel
                    </button>
                    <button type="submit" id="customSubmitBtn" class="hidden px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition text-sm font-medium shadow-sm">
                        Add Service
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complete Payment Modal -->
<div id="completeModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center transition-opacity duration-200">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all duration-200 scale-95 opacity-0" id="completeModalContent">
        <div class="p-6">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Complete & Collect</h3>
                <button onclick="closeCompleteModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form id="completeForm" method="POST" action="">
                @csrf
                
                <div class="mb-5 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                    <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold uppercase tracking-wider mb-1">Balance Due</p>
                    <p class="text-3xl font-bold text-amber-700 dark:text-amber-400" id="balanceDisplay">₱0.00</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Payment Method <span class="text-red-500">*</span>
                        </label>
                        <select name="payment_method" required class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                            <option value="cash">💵 Cash</option>
                            <option value="card">💳 Card</option>
                            <option value="gcash">📱 GCash</option>
                            <option value="paymaya">📱 PayMaya</option>
                            <option value="bank_transfer">🏦 Bank Transfer</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Amount Received (₱) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="amount" id="completeAmount" step="0.01" min="0" required
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white text-lg font-bold focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                    </div>
                </div>

                <input type="hidden" name="payment_type" value="completion">

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="closeCompleteModal()" class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-sm font-medium">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition text-sm font-medium shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Complete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- No Show Modal -->
<div id="noShowModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center transition-opacity duration-200">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all duration-200 scale-95 opacity-0" id="noShowModalContent">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-red-600 dark:text-red-400">Customer No Show</h3>
                <button onclick="closeNoShowModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div id="noShowInfo" class="mb-5">
                <!-- Populated by JS -->
            </div>
            
            <form id="noShowForm" method="POST" action="">
                @csrf
                
                <div id="noShowActions" class="space-y-3 mb-5 hidden">
                    <label class="flex items-start gap-3 p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer hover:border-red-300 dark:hover:border-red-700 hover:bg-red-50 dark:hover:bg-red-900/10 transition group">
                        <input type="radio" name="action" value="forfeit" checked class="mt-1 text-red-600 focus:ring-red-500">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white group-hover:text-red-700 dark:group-hover:text-red-400 transition">Forfeit Deposit</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Keep the deposit as revenue</p>
                        </div>
                    </label>
                    
                    <label class="flex items-start gap-3 p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer hover:border-orange-300 dark:hover:border-orange-700 hover:bg-orange-50 dark:hover:bg-orange-900/10 transition group">
                        <input type="radio" name="action" value="refund" class="mt-1 text-orange-600 focus:ring-orange-500">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white group-hover:text-orange-700 dark:group-hover:text-orange-400 transition">Refund Deposit</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Return deposit to customer</p>
                        </div>
                    </label>
                </div>

                <input type="hidden" name="action" value="forfeit" id="noShowActionDefault">
                
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="closeNoShowModal()" class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-sm font-medium">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition text-sm font-medium shadow-sm">
                        Confirm No Show
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ==================== EXTRA SERVICE MODAL ====================
function openExtraModal(appointmentId, services) {
    document.getElementById('extraForm').action = `/receptionist/appointments/${appointmentId}/add-extra`;
    
    const list = document.getElementById('existingServicesList');
    list.innerHTML = '';
    
    if (services.length === 0) {
        list.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No existing services to duplicate.</p>';
    } else {
        services.forEach(s => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-purple-50 dark:hover:bg-purple-900/30 border border-gray-200 dark:border-gray-600 rounded-lg transition flex justify-between items-center group';
            btn.innerHTML = `
                <span class="font-medium text-gray-800 dark:text-gray-200 group-hover:text-purple-700 dark:group-hover:text-purple-300 transition">${s.name}</span>
                <span class="text-purple-600 dark:text-purple-400 font-bold">+ ₱${parseFloat(s.price).toFixed(2)}</span>
            `;
            btn.onclick = () => submitExisting(s.id);
            list.appendChild(btn);
        });
    }
    
    document.getElementById('existingServiceId').value = '';
    setExtraTab('existing');
    showModal('extraModal', 'extraModalContent');
}

function closeExtraModal() {
    hideModal('extraModal', 'extraModalContent');
}

function setExtraTab(tab) {
    const existingDiv = document.getElementById('existingServiceDiv');
    const customDiv = document.getElementById('customServiceDiv');
    const submitBtn = document.getElementById('customSubmitBtn');
    const tabExisting = document.getElementById('tab-existing');
    const tabCustom = document.getElementById('tab-custom');
    
    if (tab === 'existing') {
        existingDiv.classList.remove('hidden');
        customDiv.classList.add('hidden');
        submitBtn.classList.add('hidden');
        tabExisting.className = 'flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm';
        tabCustom.className = 'flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200';
    } else {
        existingDiv.classList.add('hidden');
        customDiv.classList.remove('hidden');
        submitBtn.classList.remove('hidden');
        tabExisting.className = 'flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200';
        tabCustom.className = 'flex-1 py-2 px-3 rounded-md text-sm font-medium transition-all bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm';
    }
}

function submitExisting(serviceId) {
    document.getElementById('existingServiceId').value = serviceId;
    document.getElementById('extraForm').submit();
}

// ==================== COMPLETE MODAL ====================
function openCompleteModal(appointmentId, balance) {
    document.getElementById('completeForm').action = `/receptionist/appointments/${appointmentId}/complete`;
    document.getElementById('balanceDisplay').textContent = '₱' + balance.toFixed(2);
    document.getElementById('completeAmount').value = balance.toFixed(2);
    showModal('completeModal', 'completeModalContent');
}

function closeCompleteModal() {
    hideModal('completeModal', 'completeModalContent');
}

// ==================== NO SHOW MODAL ====================
function openNoShowModal(appointmentId, depositAmount) {
    document.getElementById('noShowForm').action = `/receptionist/appointments/${appointmentId}/no-show`;
    const hasDeposit = depositAmount > 0;
    
    const infoDiv = document.getElementById('noShowInfo');
    if (hasDeposit) {
        infoDiv.innerHTML = `
            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold uppercase tracking-wider mb-1">Deposit on File</p>
                <p class="text-2xl font-bold text-amber-700 dark:text-amber-400">₱${depositAmount.toFixed(2)}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Choose how to handle this deposit:</p>
            </div>
        `;
    } else {
        infoDiv.innerHTML = `
            <div class="p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-600">
                <p class="text-sm text-gray-600 dark:text-gray-400">No deposit was collected for this appointment. Mark as no-show?</p>
            </div>
        `;
    }
    
    document.getElementById('noShowActions').classList.toggle('hidden', !hasDeposit);
    document.getElementById('noShowActionDefault').value = 'forfeit';
    
    showModal('noShowModal', 'noShowModalContent');
}

function closeNoShowModal() {
    hideModal('noShowModal', 'noShowModalContent');
}

// ==================== MODAL UTILITIES ====================
function showModal(backdropId, contentId) {
    const backdrop = document.getElementById(backdropId);
    const content = document.getElementById(contentId);
    backdrop.classList.remove('hidden');
    // Trigger animation
    requestAnimationFrame(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    });
    document.body.style.overflow = 'hidden';
}

function hideModal(backdropId, contentId) {
    const backdrop = document.getElementById(backdropId);
    const content = document.getElementById(contentId);
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        backdrop.classList.add('hidden');
        document.body.style.overflow = '';
    }, 200);
}

// Close on backdrop click
window.addEventListener('click', function(e) {
    if (e.target.id === 'extraModal') closeExtraModal();
    if (e.target.id === 'completeModal') closeCompleteModal();
    if (e.target.id === 'noShowModal') closeNoShowModal();
});

// Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeExtraModal();
        closeCompleteModal();
        closeNoShowModal();
    }
});
</script>
@endsection