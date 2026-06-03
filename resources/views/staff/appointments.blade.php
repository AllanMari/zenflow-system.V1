@extends('layouts.staff')

@section('title', 'My Appointments')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">My Appointments</h1>
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="date" value="{{ request('date') }}" 
                   class="border rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                   onchange="this.form.submit()">
            @if(request('date'))
                <a href="{{ route('staff.appointments') }}" class="text-sm text-teal-600 dark:text-teal-400 hover:underline">Clear</a>
            @endif
        </form>
    </div>

    @if($appointments->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400">No appointments found.</p>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Services</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($appointments as $appointment)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                            {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M j, Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200">
                            {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}
                            <span class="text-gray-400 dark:text-gray-500">- {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $appointment->customer->full_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $appointment->customer->phone_number }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($appointment->services as $service)
                                    <span class="text-xs bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300 px-2 py-0.5 rounded">{{ $service->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($appointment->status === 'confirmed')
                                <span class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs px-2 py-1 rounded-full">Confirmed</span>
                            @elseif($appointment->status === 'pending')
                                <span class="bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 text-xs px-2 py-1 rounded-full">Pending</span>
                            @elseif($appointment->status === 'completed')
                                <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs px-2 py-1 rounded-full">Completed</span>
                            @elseif($appointment->status === 'cancelled')
                                <span class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs px-2 py-1 rounded-full">Cancelled</span>
                            @endif
                            <td class="px-6 py-4">
                                @if($appointment->customer && $appointment->customer->medical_notes)
                                    <button onclick="showNote('{{ addslashes($appointment->customer->medical_notes) }}', '{{ $appointment->customer->full_name }}')" 
                                            class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1 hover:underline cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                        Has notes
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $appointments->links() }}
            </div>
        </div>
    @endif
</div>
<!-- Medical Note Modal -->
<div id="noteModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeNoteModal()"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-lg shadow-2xl border border-gray-100 dark:border-gray-700 mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                Medical Notes - <span id="noteCustomerName"></span>
            </h3>
            <button onclick="closeNoteModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-4">
            <p id="noteContent" class="text-rose-800 dark:text-rose-200 text-sm whitespace-pre-wrap"></p>
        </div>
    </div>
</div>

<script>
function showNote(note, customerName) {
    document.getElementById('noteContent').textContent = note;
    document.getElementById('noteCustomerName').textContent = customerName;
    document.getElementById('noteModal').classList.remove('hidden');
}
function closeNoteModal() {
    document.getElementById('noteModal').classList.add('hidden');
}
</script>
@endsection