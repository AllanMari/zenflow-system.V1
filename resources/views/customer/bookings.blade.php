@extends('layouts.customer')

@section('title', 'My Bookings')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">My Bookings &amp; Appointments</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track active appointments, status timelines, and history.</p>
        </div>
        <a href="{{ route('booking.wizard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition font-medium shadow text-sm">
            Book New Session
        </a>
    </div>

    <!-- UPCOMING BOOKINGS SECTION -->
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                Upcoming Appointments
            </h2>
            <span class="text-xs font-semibold px-2.5 py-1 bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300 rounded-full">
                {{ $upcomingBookings->count() }} Active
            </span>
        </div>

        @if($upcomingBookings->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-2xl border p-12 text-center space-y-4 shadow-sm">
                <h3 class="text-base font-bold text-gray-800 dark:text-white">No Upcoming Appointments</h3>
                <p class="text-sm text-gray-500">You don't have any pending or confirmed bookings scheduled right now.</p>
                <a href="{{ route('booking.wizard') }}" class="inline-flex px-5 py-2.5 bg-teal-600 text-white rounded-xl text-sm">Book Now</a>
            </div>
        @else
            <div class="space-y-6">
                @foreach($upcomingBookings as $booking)
                @php
                    $isPending = $booking->status === 'pending';
                    $isConfirmed = $booking->status === 'confirmed';
                    $appointmentDateTime = \Carbon\Carbon::parse($booking->appointment_date->format('Y-m-d') . ' ' . $booking->start_time);
                    $isCancellable = in_array($booking->status, ['pending', 'confirmed']) && $appointmentDateTime->isFuture();
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden p-6 space-y-4">
                    <div class="flex justify-between items-center border-b pb-3">
                        <span class="font-mono text-xs font-bold text-gray-500">#SPA-{{ $booking->id }}</span>
                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $isConfirmed ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                            {{ ucfirst($booking->status) }}
                        </span>
                    </div>                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase">Date & Time</p>
                            <p class="text-base font-bold text-gray-800 dark:text-white">{{ $booking->appointment_date->format('l, M j, Y') }}</p>
                            <p class="text-sm font-semibold text-teal-600">{{ $booking->time_range }}</p>
                            @if($booking->room)
                            <p class="text-xs font-semibold text-gray-400 uppercase mt-3">Room</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-white">{{ $booking->room->name }}</p>
                            @endif
                            @if($booking->staff)
                            <p class="text-xs font-semibold text-gray-400 uppercase mt-3">Therapist</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-white">{{ $booking->staff->first_name }} {{ $booking->staff->last_name }}</p>
                            @endif
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase mb-2">Services</p>
                            @foreach($booking->services as $service)
                            <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-3 mb-2 flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-bold text-gray-800 dark:text-white">{{ $service->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $service->pivot->service_duration ?? $service->duration_minutes }} mins</p>
                                </div>
                                <span class="text-sm font-bold text-teal-600">₱{{ number_format($service->pivot->price_at_booking ?? $service->price, 0) }}</span>
                            </div>
                            @endforeach
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 flex flex-col justify-between">
                            <div class="space-y-1 text-sm">
                                <p class="text-xs font-semibold text-gray-400 uppercase mb-2">Payment</p>
                                <div class="flex justify-between"><span class="text-gray-600">Total</span><span class="font-bold">₱{{ number_format($booking->total_price, 0) }}</span></div>
                                <div class="flex justify-between"><span class="text-gray-600">Paid</span><span class="font-bold text-teal-600">₱{{ number_format($booking->total_paid, 0) }}</span></div>
                                <div class="flex justify-between border-t pt-2"><span class="text-gray-600">Balance</span><span class="font-bold text-orange-600">₱{{ number_format($booking->remaining_balance, 0) }}</span></div>
                            </div>
                            <div class="mt-4">
                                @if($booking->isFullyPaid())
                                    <span class="block py-1 px-3 bg-green-100 text-green-700 rounded text-center text-xs font-bold">Fully Paid</span>
                                @elseif($booking->isDepositPaid())
                                    <span class="block py-1 px-3 bg-teal-100 text-teal-700 rounded text-center text-xs font-bold">Deposit Paid</span>
                                @else
                                    <span class="block py-1 px-3 bg-orange-100 text-orange-700 rounded text-center text-xs font-bold">Payment Pending</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($isCancellable)
                    <div class="border-t pt-3 flex justify-end">
                        <button type="button" 
                                @click="$dispatch('open-cancel-modal', { url: '{{ route('customer.appointments.cancel', $booking->id) }}', service: '{{ $booking->services->pluck('name')->join(', ') }}' })"
                                class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 text-xs rounded-xl font-bold flex items-center gap-1.5">
                            Cancel Appointment
                        </button>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        @endif
    </div>    <!-- PAST BOOKINGS SECTION -->
    <div x-data="{ openPast: false }" class="bg-white dark:bg-gray-800 rounded-2xl border shadow-sm overflow-hidden">
        <div @click="openPast = !openPast" class="px-6 py-4 bg-gray-50 dark:bg-gray-700/30 flex items-center justify-between cursor-pointer hover:bg-gray-100/50 transition">
            <div class="flex items-center gap-2">
                <h3 class="font-bold text-gray-800 dark:text-white text-sm">Past &amp; Cancelled Bookings History</h3>
                <span class="text-xs font-semibold px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full">
                    {{ $pastBookings->count() }}
                </span>
            </div>
            <button type="button" class="text-xs font-semibold text-teal-600">
                <span x-text="openPast ? 'Hide History' : 'View History'"></span>
            </button>
        </div>

        <div x-show="openPast" x-cloak class="divide-y divide-gray-100 dark:divide-gray-700">
            @if($pastBookings->isEmpty())
                <div class="p-8 text-center text-sm text-gray-500">No past booking history found.</div>
            @else
                @foreach($pastBookings as $past)
                <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full {{ $past->status === 'completed' ? 'bg-teal-100 text-teal-700' : 'bg-red-100 text-red-700' }}">
                                {{ ucfirst($past->status) }}
                            </span>
                            <span class="text-xs font-mono text-gray-400">#SPA-{{ $past->id }}</span>
                        </div>
                        <p class="text-base font-bold text-gray-800 dark:text-white">
                            {{ $past->services->pluck('name')->join(', ') ?: 'Spa Session' }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $past->appointment_date->format('M j, Y') }} at {{ $past->time_range }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="font-bold text-gray-700 text-sm">₱{{ number_format($past->total_price, 0) }}</span>
                        <a href="{{ route('booking.wizard', ['rebook_from' => $past->id]) }}" class="px-3 py-1.5 bg-teal-600 text-white text-xs rounded-lg hover:bg-teal-700 font-medium">
                            Book Again
                        </a>
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    </div>

</div>

<!-- Cancel Modal -->
<div x-data="{ open: false, actionUrl: '', serviceName: '' }"
     @open-cancel-modal.window="open = true; actionUrl = $event.detail.url; serviceName = $event.detail.service"
     x-show="open"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
     style="display: none;"
     x-cloak>
    <div @click.outside="open = false" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 border space-y-4">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Cancel Appointment</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Are you sure you want to cancel <span class="font-semibold text-gray-800" x-text="serviceName"></span>?
        </p>
        <form :action="actionUrl" method="POST">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Cancellation Reason (Optional)</label>
                <textarea name="cancellation_note" rows="3" class="w-full rounded-xl border p-3 text-sm focus:ring-2 focus:ring-teal-500"></textarea>
            </div>
            <div class="flex items-center justify-end gap-3 mt-5">
                <button type="button" @click="open = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-sm font-medium">Keep Appointment</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-xl text-sm font-medium">Yes, Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection