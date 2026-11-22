@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Client Directory')

@push('styles')
<style>
    .client-card {
        transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
    }
    .client-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 25px -10px rgba(15, 23, 42, 0.12);
    }
    .dark .client-card:hover {
        box-shadow: 0 10px 25px -10px rgba(0, 0, 0, 0.4);
    }
</style>
@endpush

@section('content')
<div
    x-data="{
        historyModalOpen: false,
        editModalOpen: false,
        loadingHistory: false,
        activeClient: null,
        clientAppointments: [],
        editForm: {
            id: null,
            first_name: '',
            last_name: '',
            nickname: '',
            phone_number: '',
            medical_notes: ''
        },
        openHistory(clientId) {
            this.historyModalOpen = true;
            this.loadingHistory = true;
            this.activeClient = null;
            this.clientAppointments = [];

            const url = '{{ route($routeNamePrefix . ".show", ":id") }}'.replace(':id', clientId);
            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.activeClient = data.customer;
                    this.clientAppointments = data.appointments;
                }
            })
            .catch(err => {
                console.error('Failed to load client details', err);
            })
            .finally(() => {
                this.loadingHistory = false;
            });
        },
        openEdit(client) {
            this.editForm = {
                id: client.id,
                first_name: client.first_name || '',
                last_name: client.last_name || '',
                nickname: client.nickname || '',
                phone_number: client.phone_number || '',
                medical_notes: client.medical_notes || ''
            };
            this.editModalOpen = true;
        }
    }"
    class="space-y-6"
>
    {{-- =========================================================
         PAGE HEADER & KPIS
    ========================================================== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                Client Directory
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Manage customer profiles, visit history, contact information, and health contraindications.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-teal-50 border border-teal-200 text-teal-800 text-xs font-bold dark:bg-teal-950/40 dark:border-teal-800 dark:text-teal-300">
                <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                {{ number_format($totalCustomers) }} Total Clients
            </span>
        </div>
    </div>

    {{-- KPI METRICS CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        {{-- Total Clients --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Total Clients</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-900/20 dark:text-teal-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ number_format($totalCustomers) }}</p>
            <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">Registered client profiles</p>
        </div>

        {{-- Active Clients --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400">Active Clients</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-900/20 dark:text-teal-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-black text-teal-600 dark:text-teal-400">{{ number_format($activeCustomers ?? $totalCustomers) }}</p>
            <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">Clients with booking records</p>
        </div>

        {{-- Completed Visits --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Completed Visits</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($completedVisits) }}</p>
            <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">All-time finished appointments</p>
        </div>
    </div>

    {{-- =========================================================
         SEARCH & FILTER BAR
    ========================================================== --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" action="{{ route($routeNamePrefix . '.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            {{-- Search Bar --}}
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search by name, nickname, or phone number..."
                    class="w-full rounded-xl border border-gray-200 bg-gray-50/50 pl-10 pr-4 py-2 text-xs font-medium text-gray-900 placeholder:text-gray-400 focus:border-teal-500 focus:bg-white focus:outline-hidden dark:border-slate-800 dark:bg-slate-800/40 dark:text-white dark:focus:border-teal-400"
                >
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="px-4 py-2 rounded-xl text-xs font-bold bg-teal-600 text-white hover:bg-teal-700 transition shadow-xs"
                >
                    Search
                </button>
                @if($search)
                    <a
                        href="{{ route($routeNamePrefix . '.index') }}"
                        class="px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30 transition whitespace-nowrap"
                    >
                        Clear Search
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- =========================================================
         CUSTOMERS DIRECTORY TABLE (DESKTOP)
    ========================================================== --}}
    <div class="hidden md:block overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-gray-100 bg-gray-50/80 uppercase tracking-wider text-[11px] font-bold text-gray-500 dark:border-slate-800 dark:bg-slate-800/40 dark:text-gray-400">
                    <tr>
                        <th class="px-5 py-3.5">Client Profile</th>
                        <th class="px-4 py-3.5">Contact Details</th>
                        <th class="px-4 py-3.5 text-center">Visits</th>
                        <th class="px-4 py-3.5 text-right">Lifetime Spend</th>
                        <th class="px-4 py-3.5">Medical Notes</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800 font-medium">
                    @forelse($customers as $client)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-slate-800/40 transition">
                            {{-- Profile --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xs font-black bg-teal-100 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300">
                                        {{ strtoupper(substr($client->first_name ?: ($client->nickname ?: 'C'), 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-gray-900 dark:text-white text-sm">
                                                {{ $client->first_name }} {{ $client->last_name }}
                                            </span>
                                            @if($client->nickname)
                                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                                    ("{{ $client->nickname }}")
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-gray-400 dark:text-gray-500">
                                            Member since {{ $client->created_at ? $client->created_at->format('M Y') : 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- Contact --}}
                            <td class="px-4 py-4">
                                @if($client->phone_number)
                                    <div class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300">
                                        <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <a href="tel:{{ $client->phone_number }}" class="hover:text-teal-600 transition">{{ $client->phone_number }}</a>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-[11px]">No phone recorded</span>
                                @endif
                            </td>

                            {{-- Visits --}}
                            <td class="px-4 py-4 text-center">
                                <span class="font-bold text-gray-900 dark:text-white">{{ $client->completed_appointments_count }}</span>
                                <span class="text-[11px] text-gray-400">/ {{ $client->appointments_count }}</span>
                            </td>

                            {{-- Lifetime Spend --}}
                            <td class="px-4 py-4 text-right">
                                <span class="font-bold text-teal-700 dark:text-teal-400">
                                    ₱{{ number_format((float) ($client->lifetime_spend ?? 0), 2) }}
                                </span>
                            </td>

                            {{-- Medical Notes --}}
                            <td class="px-4 py-4 max-w-xs">
                                @if(!empty($client->medical_notes))
                                    <div class="flex items-start gap-1.5 p-2 rounded-lg bg-rose-50 border border-rose-100 text-rose-800 text-[11px] dark:bg-rose-950/30 dark:border-rose-900/40 dark:text-rose-300">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-rose-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span class="truncate" title="{{ $client->medical_notes }}">
                                            {{ $client->medical_notes }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-[11px] italic">None reported</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="openHistory({{ $client->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-teal-200 bg-teal-50 text-teal-700 hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/40 dark:text-teal-300 text-xs font-bold transition shadow-xs"
                                        title="View past sessions and treatment history"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        History
                                    </button>
                                    <button
                                        type="button"
                                        @click="openEdit({{ json_encode($client) }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-300 text-xs font-bold transition shadow-xs"
                                        title="Edit client profile and medical notes"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-gray-400 text-xs">
                                No clients matching your criteria found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- =========================================================
         CUSTOMERS DIRECTORY CARDS (MOBILE VIEW)
    ========================================================== --}}
    <div class="md:hidden space-y-3">
        @forelse($customers as $client)
            <div class="client-card rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xs font-black bg-teal-100 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300">
                            {{ strtoupper(substr($client->first_name ?: ($client->nickname ?: 'C'), 0, 1)) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm">
                                {{ $client->first_name }} {{ $client->last_name }}
                            </h3>
                            @if($client->nickname)
                                <p class="text-[11px] text-gray-400">"{{ $client->nickname }}"</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-[11px] pt-1 border-t border-gray-50 dark:border-slate-800/60">
                    <div>
                        <span class="text-gray-400 block">Phone</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $client->phone_number ?: 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block">Visits & Spend</span>
                        <span class="font-bold text-teal-700 dark:text-teal-400">
                            {{ $client->completed_appointments_count }} visits • ₱{{ number_format((float) ($client->lifetime_spend ?? 0), 2) }}
                        </span>
                    </div>
                </div>

                @if(!empty($client->medical_notes))
                    <div class="p-2 rounded-lg bg-rose-50 border border-rose-100 text-rose-800 text-[11px] dark:bg-rose-950/30 dark:border-rose-900/40 dark:text-rose-300">
                        <span class="font-bold block">Medical Notes:</span>
                        {{ $client->medical_notes }}
                    </div>
                @endif

                <div class="flex items-center gap-2 pt-2 border-t border-gray-50 dark:border-slate-800">
                    <button
                        type="button"
                        @click="openHistory({{ $client->id }})"
                        class="flex-1 py-2 rounded-xl border border-teal-200 bg-teal-50 text-teal-700 hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/40 dark:text-teal-300 text-xs font-bold transition text-center shadow-xs"
                    >
                        View History
                    </button>
                    <button
                        type="button"
                        @click="openEdit({{ json_encode($client) }})"
                        class="flex-1 py-2 rounded-xl border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-300 text-xs font-bold transition text-center shadow-xs"
                    >
                        Edit Profile
                    </button>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center text-gray-400 text-xs dark:border-slate-800 dark:bg-slate-900">
                No clients matching your criteria found.
            </div>
        @endforelse
    </div>

    {{-- PAGINATION --}}
    <div class="mt-4">
        {{ $customers->links() }}
    </div>

    {{-- =========================================================
         MODAL 1: CLIENT HISTORY & PROFILE (DRAWER / MODAL)
    ========================================================== --}}
    <div
        x-show="historyModalOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-4 py-6 backdrop-blur-sm"
        @keydown.escape.window="historyModalOpen = false"
    >
        <div
            x-show="historyModalOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-3 scale-95"
            @click.outside="historyModalOpen = false"
            class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-900 dark:border dark:border-slate-800"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300 font-bold">
                        <template x-if="activeClient">
                            <span x-text="(activeClient.first_name || 'C').charAt(0).toUpperCase()"></span>
                        </template>
                        <template x-if="!activeClient">
                            <span>...</span>
                        </template>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white" x-text="activeClient ? activeClient.full_name : 'Loading Profile...'"></h3>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            <span x-text="activeClient ? (activeClient.phone_number || 'No phone') : ''"></span>
                        </p>
                    </div>
                </div>
                <button type="button" @click="historyModalOpen = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800 dark:hover:text-gray-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="flex-1 overflow-y-auto p-6 space-y-5">
                <template x-if="loadingHistory">
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400 space-y-2">
                        <svg class="h-6 w-6 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span class="text-xs">Loading complete appointment history...</span>
                    </div>
                </template>

                <template x-if="!loadingHistory && activeClient">
                    <div class="space-y-5">
                        {{-- Quick Stats Banner --}}
                        <div class="grid grid-cols-3 gap-3 p-3.5 rounded-xl bg-gray-50 dark:bg-slate-800/50 border border-gray-100 dark:border-slate-800 text-center">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total Bookings</span>
                                <p class="text-base font-black text-gray-900 dark:text-white" x-text="activeClient.total_appointments"></p>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Completed</span>
                                <p class="text-base font-black text-emerald-600 dark:text-emerald-400" x-text="activeClient.completed_visits"></p>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400">Lifetime Spend</span>
                                <p class="text-base font-black text-teal-700 dark:text-teal-300" x-text="activeClient.lifetime_spend"></p>
                            </div>
                        </div>

                        {{-- Medical Notes Box --}}
                        <template x-if="activeClient.medical_notes">
                            <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 dark:bg-rose-950/30 dark:border-rose-900/40 text-rose-900 dark:text-rose-200 text-xs space-y-1">
                                <div class="flex items-center gap-1.5 font-bold text-rose-700 dark:text-rose-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Medical Contraindications & Health Notes
                                </div>
                                <p class="leading-relaxed" x-text="activeClient.medical_notes"></p>
                            </div>
                        </template>

                        {{-- Chronological Appointment History --}}
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Chronological Visit History
                                </h4>
                                <span class="text-xs text-gray-400" x-text="clientAppointments.length + ' sessions logged'"></span>
                            </div>

                            <template x-if="clientAppointments.length === 0">
                                <p class="py-8 text-center text-xs text-gray-400">No appointments recorded for this client.</p>
                            </template>

                            <div class="space-y-3">
                                <template x-for="appt in clientAppointments" :key="appt.id">
                                    <div class="p-3.5 rounded-xl border border-gray-100 bg-white dark:border-slate-800 dark:bg-slate-800/40 space-y-2 hover:border-teal-200 dark:hover:border-teal-800 transition">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-gray-900 dark:text-white text-xs" x-text="appt.date"></span>
                                                    <span class="text-gray-400 text-[11px]" x-text="appt.time"></span>
                                                </div>
                                                <p class="text-[11px] text-teal-700 dark:text-teal-400 font-semibold mt-0.5" x-text="appt.services.join(', ') || 'Service session'"></p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span class="font-black text-xs text-gray-900 dark:text-white block" x-text="appt.total_price"></span>
                                                <span
                                                    class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold capitalize"
                                                    :class="{
                                                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300': appt.status === 'completed',
                                                        'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300': appt.status === 'confirmed',
                                                        'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300': appt.status === 'pending',
                                                        'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300': appt.status === 'cancelled' || appt.status === 'no_show'
                                                    }"
                                                    x-text="appt.status"
                                                ></span>
                                            </div>
                                        </div>

                                        {{-- Assigned Therapists & Room --}}
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-50 dark:border-slate-800/60">
                                            <div class="flex items-center gap-1">
                                                <span class="font-semibold text-gray-400">Therapists:</span>
                                                <span class="font-medium text-gray-700 dark:text-gray-300" x-text="appt.therapists.length ? appt.therapists.join(', ') : 'Unassigned'"></span>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="font-semibold text-gray-400">Room:</span>
                                                <span class="font-medium text-gray-700 dark:text-gray-300" x-text="appt.room"></span>
                                            </div>
                                        </div>

                                        {{-- Session Treatment Notes if any --}}
                                        <template x-if="appt.treatment_notes">
                                            <div class="text-[11px] bg-gray-50 dark:bg-slate-900/60 p-2 rounded-lg text-gray-600 dark:text-gray-300 italic">
                                                <span class="font-semibold text-gray-500 not-italic">Treatment note:</span>
                                                <span x-text="appt.treatment_notes"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="border-t border-gray-100 bg-gray-50 px-6 py-3 dark:border-slate-800 dark:bg-slate-950/40 flex justify-end">
                <button
                    type="button"
                    @click="historyModalOpen = false"
                    class="rounded-xl bg-gray-200 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-300 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700 transition"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- =========================================================
         MODAL 2: EDIT CLIENT PROFILE & MEDICAL NOTES
    ========================================================== --}}
    <div
        x-show="editModalOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-4 py-6 backdrop-blur-sm"
        @keydown.escape.window="editModalOpen = false"
    >
        <div
            x-show="editModalOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-3 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-3 scale-95"
            @click.outside="editModalOpen = false"
            class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-900 dark:border dark:border-slate-800"
        >
            <form :action="'{{ route($routeNamePrefix . ".update", ":id") }}'.replace(':id', editForm.id)" method="POST" class="flex flex-col h-full">
                @csrf
                @method('PUT')

                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-600 dark:bg-teal-900/20 dark:text-teal-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Edit Client Profile</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Update contact info and health contraindications.</p>
                        </div>
                    </div>
                    <button type="button" @click="editModalOpen = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800 dark:hover:text-gray-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Fields --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-4 text-xs font-medium">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">First Name *</label>
                            <input
                                type="text"
                                name="first_name"
                                x-model="editForm.first_name"
                                required
                                class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 text-xs text-gray-900 focus:border-teal-500 focus:bg-white focus:outline-hidden dark:border-slate-800 dark:bg-slate-800/40 dark:text-white"
                            >
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Last Name *</label>
                            <input
                                type="text"
                                name="last_name"
                                x-model="editForm.last_name"
                                required
                                class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 text-xs text-gray-900 focus:border-teal-500 focus:bg-white focus:outline-hidden dark:border-slate-800 dark:bg-slate-800/40 dark:text-white"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Nickname (Optional)</label>
                            <input
                                type="text"
                                name="nickname"
                                x-model="editForm.nickname"
                                placeholder="Preferred name"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 text-xs text-gray-900 focus:border-teal-500 focus:bg-white focus:outline-hidden dark:border-slate-800 dark:bg-slate-800/40 dark:text-white"
                            >
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Phone Number</label>
                            <input
                                type="text"
                                name="phone_number"
                                x-model="editForm.phone_number"
                                placeholder="e.g. 09171234567"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 text-xs text-gray-900 focus:border-teal-500 focus:bg-white focus:outline-hidden dark:border-slate-800 dark:bg-slate-800/40 dark:text-white"
                            >
                        </div>
                    </div>

                    <div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block font-bold text-gray-700 dark:text-gray-300">
                                Medical & Treatment Notes
                            </label>
                            <span class="text-[10px] text-gray-400">Allergies, injuries, pressure preferences</span>
                        </div>
                        <textarea
                            name="medical_notes"
                            x-model="editForm.medical_notes"
                            rows="3"
                            placeholder="e.g. Sensitive lower back; allergic to eucalyptus oil; prefers light pressure."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50/50 p-3 text-xs text-gray-900 focus:border-teal-500 focus:bg-white focus:outline-hidden dark:border-slate-800 dark:bg-slate-800/40 dark:text-white"
                        ></textarea>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="border-t border-gray-100 bg-gray-50 px-6 py-3.5 dark:border-slate-800 dark:bg-slate-950/40 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="editModalOpen = false"
                        class="rounded-xl bg-gray-200 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-300 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700 transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-teal-600 px-5 py-2 text-xs font-bold text-white shadow-xs hover:bg-teal-700 transition"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
