@extends('layouts.staff')

@section('title', 'My Appointments')

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .print-block { display: block !important; }
        body { background: white !important; }
    }
    .print-block { display: none; }
</style>
@endpush

@section('content')
<div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-5 bg-white p-5 rounded-2xl border border-gray-200 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-neutral-200 flex items-center gap-3">
                <span class="inline-flex items-center justify-center size-10 rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                    <svg class="flex-shrink-0 size-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                </span>
                My Appointments
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-neutral-500">
                View and manage your client bookings, services, and customer notes for ZenFlow.
            </p>
        </div>

        <!-- Filter Toolbar -->
        <div class="flex items-center gap-2">
            <form method="GET" class="flex items-center gap-2 bg-gray-50 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg p-1.5 shadow-sm">
                <span class="pl-2 text-gray-400 dark:text-neutral-500">
                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                </span>
                <input type="date" name="date" value="{{ request('date') }}"
                       class="border-0 bg-transparent py-1.5 px-2 text-sm font-medium text-gray-700 dark:text-neutral-200 focus:ring-0 cursor-pointer dark:[color-scheme:dark]"
                       onchange="this.form.submit()">
                @if(request('date'))
                    <div class="w-px h-5 bg-gray-200 dark:bg-neutral-700 mx-1"></div>
                    <a href="{{ route('staff.appointments') }}" class="px-3 py-1.5 text-xs font-semibold text-gray-500 hover:text-teal-600 dark:text-neutral-400 dark:hover:text-teal-400 transition-colors uppercase tracking-wide">Clear</a>
                @endif
            </form>

            <button onclick="window.print()" class="no-print hidden sm:inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800 transition-colors shadow-sm">
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                Print
            </button>
        </div>
    </div>

    <!-- Status Filter Pills -->
    <div class="no-print flex flex-wrap items-center gap-2">
        <a href="{{ route('staff.appointments') }}{{ request('date') ? '?date='.request('date') : '' }}"
           class="py-1.5 px-3 inline-flex items-center gap-1.5 text-xs font-semibold rounded-full transition-colors {{ !request('status') ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 dark:bg-neutral-900 dark:text-neutral-300 dark:border-neutral-700 dark:hover:bg-neutral-800' }}">
            All
            <span class="text-[10px] {{ !request('status') ? 'bg-teal-500/30' : 'bg-gray-100 dark:bg-neutral-800' }} rounded-full px-1.5 py-0.5">{{ $appointments->total() }}</span>
        </a>
        @php
            $statusCounts = [
                'confirmed' => $appointments->getCollection()->where('status', 'confirmed')->count(),
                'pending' => $appointments->getCollection()->where('status', 'pending')->count(),
                'completed' => $appointments->getCollection()->where('status', 'completed')->count(),
                'cancelled' => $appointments->getCollection()->where('status', 'cancelled')->count(),
            ];
        @endphp
        @foreach(['confirmed' => 'Confirmed', 'pending' => 'Pending', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
            <a href="?status={{ $key }}{{ request('date') ? '&date='.request('date') : '' }}"
               class="py-1.5 px-3 inline-flex items-center gap-1.5 text-xs font-semibold rounded-full transition-colors {{ request('status') === $key ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 dark:bg-neutral-900 dark:text-neutral-300 dark:border-neutral-700 dark:hover:bg-neutral-800' }}">
                {{ $label }}
                <span class="text-[10px] {{ request('status') === $key ? 'bg-teal-500/30' : 'bg-gray-100 dark:bg-neutral-800' }} rounded-full px-1.5 py-0.5">{{ $statusCounts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    @if($appointments->isEmpty())
        <!-- Empty State -->
        <div class="bg-white dark:bg-neutral-900 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-16 text-center">
            <div class="w-16 h-16 bg-gray-50 dark:bg-neutral-800/50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100 dark:border-neutral-700">
                <svg class="w-8 h-8 text-gray-400 dark:text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-neutral-200 mb-1">No appointments found</h3>
            <p class="text-sm text-gray-500 dark:text-neutral-400">
                @if(request('date'))
                    You don't have any clients scheduled for {{ \Carbon\Carbon::parse(request('date'))->format('M j, Y') }}.
                @elseif(request('status'))
                    You don't have any {{ request('status') }} appointments right now.
                @else
                    You don't have any appointments scheduled yet.
                @endif
            </p>
        </div>
    @else
        <!-- Appointment Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5">
            @foreach($appointments as $appointment)
                @php
                    $isToday = \Carbon\Carbon::parse($appointment->appointment_date)->isToday();
                    $isPast = \Carbon\Carbon::parse($appointment->appointment_date)->isPast() && !$isToday;
                    $statusColors = [
                        'confirmed' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500', 'darkBg' => 'dark:bg-emerald-900/30', 'darkText' => 'dark:text-emerald-400', 'darkBorder' => 'dark:border-emerald-800'],
                        'pending' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'dot' => 'bg-amber-500', 'darkBg' => 'dark:bg-amber-900/30', 'darkText' => 'dark:text-amber-400', 'darkBorder' => 'dark:border-amber-800'],
                        'completed' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'dot' => 'bg-blue-500', 'darkBg' => 'dark:bg-blue-900/30', 'darkText' => 'dark:text-blue-400', 'darkBorder' => 'dark:border-blue-800'],
                        'cancelled' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-200', 'dot' => 'bg-red-500', 'darkBg' => 'dark:bg-red-900/30', 'darkText' => 'dark:text-red-400', 'darkBorder' => 'dark:border-red-800'],
                    ];
                    $sc = $statusColors[$appointment->status] ?? $statusColors['pending'];
                    $cardBorder = $isToday ? 'border-teal-300 ring-2 ring-teal-100 dark:border-teal-700 dark:ring-teal-900/30' : ($isPast ? 'border-gray-100 dark:border-neutral-800 opacity-75' : 'border-gray-200 dark:border-neutral-700');
                @endphp

                <div class="flex flex-col bg-white border shadow-sm rounded-2xl transition-all hover:shadow-md {{ $cardBorder }} dark:bg-neutral-900">
                    <!-- Card Header: Date + Status -->
                    <div class="p-4 border-b border-gray-100 dark:border-neutral-800 flex items-center justify-between {{ $isToday ? 'bg-teal-50/50 dark:bg-teal-900/20 rounded-t-2xl' : '' }}">
                        <div class="flex items-center gap-3">
                            <div class="flex flex-col items-center justify-center size-12 rounded-xl {{ $isToday ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-neutral-300' }} shrink-0">
                                <span class="text-[10px] font-bold uppercase leading-none">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M') }}</span>
                                <span class="text-lg font-bold leading-none mt-0.5">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('j') }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800 dark:text-neutral-200">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('l') }}</p>
                                <p class="text-xs text-gray-500 dark:text-neutral-400">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M j, Y') }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wide border {{ $sc['bg'] }} {{ $sc['text'] }} {{ $sc['border'] }} {{ $sc['darkBg'] }} {{ $sc['darkText'] }} {{ $sc['darkBorder'] }}">
                            <span class="size-1.5 rounded-full {{ $sc['dot'] }}"></span>
                            {{ ucfirst($appointment->status) }}
                        </span>
                    </div>

                    <!-- Card Body: Time + Customer + Services -->
                    <div class="p-4 space-y-4 flex-1">
                        <!-- Time -->
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center size-9 rounded-lg bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400 shrink-0">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-neutral-100">{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}</p>
                                @php $dur = round(\Carbon\Carbon::parse($appointment->start_time)->diffInMinutes(\Carbon\Carbon::parse($appointment->end_time)) / 60, 1); @endphp
                                <p class="text-[11px] text-gray-500 dark:text-neutral-400">{{ $dur }} hour{{ $dur != 1 ? 's' : '' }} duration</p>
                            </div>
                        </div>

                        <!-- Customer -->
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center size-9 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 shrink-0">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-neutral-100 truncate">{{ $appointment->customer->full_name }}</p>
                                <p class="text-xs text-gray-500 dark:text-neutral-400">{{ $appointment->customer->phone_number }}</p>
                            </div>
                        </div>

                        <!-- Services -->
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-neutral-500 mb-2">Services</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($appointment->services as $service)
                                    <span class="inline-flex items-center text-[11px] font-semibold bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800 px-2 py-0.5 rounded-md">
                                        {{ $service->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer: Notes -->
                    <div class="px-4 pb-4">
                        @if($appointment->customer && $appointment->customer->medical_notes)
                            <button onclick="showNote('{{ addslashes(str_replace(["\r", "\n"], ['\r', '\n'], $appointment->customer->medical_notes)) }}', '{{ addslashes($appointment->customer->full_name) }}')"
                                    class="no-print w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 dark:text-rose-400 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 dark:border-rose-800 transition-colors shadow-sm cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                View Medical Notes
                            </button>
                        @else
                            <div class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium text-gray-400 dark:text-neutral-600 bg-gray-50 dark:bg-neutral-800/50 border border-gray-100 dark:border-neutral-800">
                                No medical notes
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($appointments->hasPages())
            <div class="flex justify-center pt-2">
                <nav class="flex items-center gap-1">
                    {{ $appointments->links() }}
                </nav>
            </div>
        @endif
    @endif
</div>

<!-- Medical Note Modal -->
<div id="noteModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeNoteModal()"></div>

    <div class="relative bg-white dark:bg-neutral-900 rounded-2xl w-full max-w-md shadow-2xl border border-gray-200 dark:border-neutral-700 transform transition-all">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-neutral-800 bg-gray-50/50 dark:bg-neutral-800/50 rounded-t-2xl">
            <h3 class="text-base font-bold text-gray-900 dark:text-neutral-200 flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                Customer Notes
            </h3>
            <button onclick="closeNoteModal()" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-neutral-300 dark:hover:bg-neutral-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-6">
            <div class="mb-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-neutral-400">Customer</span>
                <p id="noteCustomerName" class="text-sm font-semibold text-gray-900 dark:text-neutral-200 mt-0.5"></p>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-500 dark:text-rose-400 mb-1.5 block">Important Medical Notes</span>
                <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800/50 rounded-xl p-4">
                    <p id="noteContent" class="text-rose-800 dark:text-rose-200 text-sm leading-relaxed whitespace-pre-wrap"></p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 dark:bg-neutral-800/80 border-t border-gray-100 dark:border-neutral-800 rounded-b-2xl flex justify-end">
            <button onclick="closeNoteModal()" class="px-4 py-2 text-sm font-bold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-neutral-800 dark:text-neutral-200 dark:border-neutral-700 dark:hover:bg-neutral-700 rounded-lg shadow-sm transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function showNote(note, customerName) {
    document.getElementById('noteContent').textContent = note;
    document.getElementById('noteCustomerName').textContent = customerName;
    document.body.style.overflow = 'hidden';
    document.getElementById('noteModal').classList.remove('hidden');
}
function closeNoteModal() {
    document.body.style.overflow = '';
    document.getElementById('noteModal').classList.add('hidden');
}
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && !document.getElementById('noteModal').classList.contains('hidden')) {
        closeNoteModal();
    }
});
</script>
@endsection
