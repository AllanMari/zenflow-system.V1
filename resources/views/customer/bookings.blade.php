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

                    $attendance = $attendanceStates[$booking->id] ?? null;
                    $attendanceConfirmed = (bool) ($attendance['confirmed'] ?? false);
                    $canConfirmAttendance = $booking->status === 'confirmed' && !$attendanceConfirmed;
                    $attendanceSourceLabel = match ($attendance['source'] ?? null) {
                        'customer' => 'you',
                        'receptionist' => 'our receptionist (phone)',
                        default => 'ZenFlow',
                    };
                @endphp
                <div id="booking-{{ $booking->id }}" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden p-6 space-y-4">
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
                            @if($booking->requestedRoom)
                            <p class="text-xs font-semibold text-gray-400 uppercase mt-3">Requested Room</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-white">{{ $booking->requestedRoom->name }}</p>
                            @else
                            <p class="text-xs font-semibold text-gray-400 uppercase mt-3">Room Preference</p>
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">No preference</p>
                            @endif

                            @if($booking->room)
                            <p class="text-xs font-semibold text-gray-400 uppercase mt-3">Assigned Room</p>
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
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs font-semibold text-gray-400 uppercase">Payment</p>
                                    @if($booking->hasDiscount())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-300">
                                            {{ $booking->short_discount_badge }}
                                        </span>
                                    @endif
                                </div>
                                @if($booking->hasDiscount())
                                    <div class="flex justify-between text-xs text-gray-500"><span>Original</span><span>₱{{ number_format($booking->original_amount, 0) }}</span></div>
                                    <div class="flex justify-between text-xs text-amber-700 dark:text-amber-300"><span>{{ $booking->discount_label }}</span><span>-₱{{ number_format($booking->discount_amount, 0) }}</span></div>
                                @endif
                                <div class="flex justify-between"><span class="text-gray-600">Total</span><span class="font-bold">₱{{ number_format($booking->total_price, 0) }}</span></div>
                                <div class="flex justify-between"><span class="text-gray-600">Paid</span><span class="font-bold text-teal-600">₱{{ number_format($booking->total_paid, 0) }}</span></div>
                                <div class="flex justify-between border-t pt-2"><span class="text-gray-600">Balance</span><span class="font-bold text-orange-600">₱{{ number_format($booking->active_outstanding_balance, 0) }}</span></div>
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

                    @if($isConfirmed || $attendanceConfirmed)
                    <div class="border-t pt-3">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $attendanceConfirmed ? 'bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700/40 dark:text-gray-300' }}">
                                    <i data-lucide="{{ $attendanceConfirmed ? 'check-circle-2' : 'calendar-check' }}" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Attendance</span>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $attendanceConfirmed ? 'bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700/40 dark:text-gray-300' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $attendanceConfirmed ? 'bg-teal-500' : 'bg-gray-400' }}"></span>
                                            {{ $attendanceConfirmed ? 'Attendance confirmed' : 'Awaiting attendance confirmation' }}
                                        </span>
                                    </div>
                                    @if($attendanceConfirmed)
                                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                            Confirmed by {{ $attendanceSourceLabel }}
                                            @if(!empty($attendance['confirmed_at']))
                                                on {{ \Carbon\Carbon::parse($attendance['confirmed_at'])->format('M j, Y g:i A') }}
                                            @endif
                                        </p>
                                    @else
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Let us know you received this booking. This does not check you in or change your appointment.
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if($canConfirmAttendance)
                            <button type="button"
                                    onclick="confirmAttendance({{ \Illuminate\Support\Js::from(route('customer.attendance.confirm', $booking->id)) }}, {{ \Illuminate\Support\Js::from($booking->services->pluck('name')->join(', ') ?: 'your appointment') }})"
                                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs rounded-xl font-bold shrink-0">
                                <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>
                                Confirm Attendance
                            </button>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($isCancellable)
                    <div class="border-t pt-3 flex justify-end">
                        <button type="button" 
                                onclick="cancelAppointment({{ \Illuminate\Support\Js::from(route('customer.appointments.cancel', $booking->id)) }}, {{ \Illuminate\Support\Js::from($booking->services->pluck('name')->join(', ')) }})"
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

<script>
async function cancelAppointment(url, serviceNames) {
    const { value: note, isConfirmed } = await Swal.fire({
        title: 'Cancel Appointment',
        html: `
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Are you sure you want to cancel <span class="font-semibold text-gray-800 dark:text-white">${serviceNames}</span>?
            </p>
            <div class="text-left">
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Cancellation Reason (Optional)</label>
                <textarea id="swal-input-reason" rows="3" class="w-full rounded-xl border p-3 text-sm focus:ring-2 focus:ring-teal-500" placeholder="Optional reason..."></textarea>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Cancel',
        cancelButtonText: 'Keep Appointment',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#9ca3af',
        background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#fff' : '#374151',
        width: '400px',
        padding: '1.25rem',
        customClass: {
            popup: 'rounded-2xl',
            title: 'text-lg font-bold',
            htmlContainer: 'text-sm',
            confirmButton: 'rounded-lg px-4 py-2 text-sm',
            cancelButton: 'rounded-lg px-4 py-2 text-sm'
        },
        preConfirm: () => {
            const reason = document.getElementById('swal-input-reason').value;
            if (reason.length > 1000) {
                Swal.showValidationMessage('Reason must be 1000 characters or less.');
                return false;
            }
            return reason;
        }
    });

    if (isConfirmed) {
        Swal.showLoading();
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
        
        if (note && note.trim().length > 0) {
            const noteInput = document.createElement('input');
            noteInput.type = 'hidden';
            noteInput.name = 'cancellation_note';
            noteInput.value = note;
            form.appendChild(noteInput);
        }
        
        document.body.appendChild(form);
        form.submit();
    }
}

async function confirmAttendance(url, serviceNames) {
    const { isConfirmed } = await Swal.fire({
        title: 'Confirm Attendance',
        html: `
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                Please confirm that you received the booking for
                <span class="font-semibold text-gray-800 dark:text-white">${serviceNames}</span>.
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                This only acknowledges the booking. It does not check you in or change your appointment status.
            </p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Confirm',
        cancelButtonText: 'Not Now',
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#9ca3af',
        background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#fff' : '#374151',
        width: '400px',
        padding: '1.25rem',
        customClass: {
            popup: 'rounded-2xl',
            title: 'text-lg font-bold',
            htmlContainer: 'text-sm',
            confirmButton: 'rounded-lg px-4 py-2 text-sm',
            cancelButton: 'rounded-lg px-4 py-2 text-sm'
        }
    });

    if (isConfirmed) {
        Swal.showLoading();

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection