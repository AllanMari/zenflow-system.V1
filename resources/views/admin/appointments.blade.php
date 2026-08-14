@extends('layouts.admin') {{-- or whatever your master layout is --}}

@section('title', 'Appointments Summary')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
        @php
        $cards = [
            ['Total', $stats['total'], 'bg-gray-50 dark:bg-gray-800/40 text-gray-700 dark:text-gray-200'],
            ['Pending', $stats['pending'], 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300'],
            ['Confirmed', $stats['confirmed'], 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'],
            ['Completed', $stats['completed'], 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300'],
            ['Cancelled', $stats['cancelled'], 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300'],
            ['No-Shows', $stats['no_show'], 'bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-300'],
            ["Today's Revenue", '₱'.number_format($stats['today_revenue'], 2), 'bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300'],
        ];
        @endphp
        @foreach($cards as $card)
        <div class="rounded-xl p-3 border border-gray-100 dark:border-gray-700/50 {{ $card[2] }}">
            <p class="text-[10px] font-bold uppercase tracking-wider opacity-70">{{ $card[0] }}</p>
            <p class="text-lg font-bold mt-1">{{ $card[1] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-[#1e293b] rounded-2xl p-4 border border-gray-100 dark:border-gray-700/50 shadow-sm">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">Status</label>
                <select name="status" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm dark:text-white">
                    <option value="">All</option>
                    <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                    <option value="confirmed" {{ request('status')=='confirmed'?'selected':'' }}>Confirmed</option>
                    <option value="completed" {{ request('status')=='completed'?'selected':'' }}>Completed</option>
                    <option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm dark:text-white">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm dark:text-white">
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">Staff</label>
                <select name="staff_id" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm dark:text-white">
                    <option value="">All Staff</option>
                    @foreach($staffList as $s)
                        <option value="{{ $s->id }}" {{ request('staff_id')==$s->id?'selected':'' }}>{{ $s->first_name }} {{ $s->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">Search Customer</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or phone..." class="w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm dark:text-white">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-xl transition">Filter</button>
                <a href="{{ route('admin.appointments') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-bold rounded-xl transition">Clear</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-[#1e293b] rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 text-xs uppercase font-bold">
                    <tr>
                        <th class="px-4 py-3">Date / Time</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Services</th>
                        <th class="px-4 py-3">Staff</th>
                        <th class="px-4 py-3">Room</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Paid</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    @forelse($appointments as $appt)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <p class="font-bold text-gray-800 dark:text-gray-200">{{ \Carbon\Carbon::parse($appt->appointment_date)->format('M j, Y') }}</p>
                            <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($appt->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($appt->end_time)->format('g:i A') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $appt->customer->full_name ?? 'Walk-in' }}</p>
                            <p class="text-xs text-gray-400">{{ $appt->customer->phone_number ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach($appt->services as $svc)
                                    <span class="px-2 py-0.5 rounded-md bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 text-[11px] font-bold border border-brand-100 dark:border-brand-800">{{ $svc->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $appt->staff->full_name ?? 'Unassigned' }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $appt->room->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-right font-bold text-gray-800 dark:text-gray-200">₱{{ number_format($appt->total_price, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                            $badge = match($appt->status) {
                                'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                'confirmed' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                            };
                            @endphp
                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase {{ $badge }}">{{ $appt->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">
                            ₱{{ number_format($appt->payments->sum('amount'), 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500 text-sm">No appointments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700/50">
            {{ $appointments->links() }}
        </div>
    </div>
</div>
@endsection