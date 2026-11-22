@php
    $totalPaid = max(0, (float) $appointment->payments->sum('amount'));
    $balance = max(0, (float) $appointment->total_price - $totalPaid);
    $fullyPaid = $balance <= 0;

    $customerName = $appointment->client_name;

    $phone = $appointment->customer?->phone_number;
    $isPastUnresolved = isset($isPastUnresolved) ? $isPastUnresolved : false;
    $isToday = !$isPastUnresolved && $appointment->appointment_date->isToday();
    $services = $appointment->services;
    $therapistsReq = (int) ($appointment->therapists_required ?? 1);
    $primaryStaff = $appointment->primary_staff ?? $appointment->staff;
    $assistingStaff = $appointment->assisting_staff ?? collect();
    $hasMultipleTherapists = $therapistsReq > 1 || $assistingStaff->isNotEmpty();
@endphp

<article
    id="appointment-{{ $appointment->id }}"
    class="session-card overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
>
    <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $isToday ? 'bg-teal-50 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400' : ($isPastUnresolved ? 'bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300') }}">
                    <i data-lucide="calendar-clock" class="h-5 w-5"></i>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white break-words">
                            {{ $customerName }}
                        </h3>
                        @if($isPastUnresolved)
                            <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-purple-50 px-2.5 py-1 text-[11px] font-bold text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-purple-500"></span>
                                Confirmed · Past
                            </span>
                        @elseif($isToday)
                            <span class="rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-teal-700 dark:bg-teal-950/40 dark:text-teal-300">
                                Today
                            </span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                Upcoming
                            </span>
                        @endif
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                        <span class="inline-flex items-center gap-1">
                            <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                            {{ $appointment->appointment_date->format('M j, Y') }}
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <i data-lucide="clock-3" class="h-3.5 w-3.5"></i>
                            {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}
                            –
                            {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
                        </span>
                        @if($phone)
                            <span class="inline-flex items-center gap-1">
                                <i data-lucide="phone" class="h-3.5 w-3.5"></i>
                                {{ $phone }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                Confirmed
            </span>
        </div>
    </div>
    <div class="grid gap-4 p-4 lg:grid-cols-[1.4fr_1fr_1fr]">
        <div>
            <div class="mb-2 flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                <i data-lucide="sparkles" class="h-3.5 w-3.5"></i>
                Services
            </div>
            <div class="space-y-2">
                @forelse($services as $service)
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/70">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-200 break-words">
                                {{ $service->pivot->service_name ?? $service->name }}
                            </p>
                            @if($service->pivot->is_extra)
                                <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400">
                                    Extra service
                                </span>
                            @endif
                        </div>
                        <span class="shrink-0 text-xs font-semibold text-slate-600 dark:text-slate-300">
                            ₱{{ number_format((float) ($service->pivot->price_at_booking ?? $service->price ?? 0), 2) }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        No services recorded.
                    </p>
                @endforelse

                @if($appointment->packageSelections && $appointment->packageSelections->isNotEmpty())
                    <div class="mt-2 space-y-1 rounded-lg border border-purple-200 bg-purple-50/70 p-2 text-xs dark:border-purple-900/40 dark:bg-purple-950/30">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300">Package Choices:</p>
                        @foreach($appointment->packageSelections as $sel)
                            <div class="flex items-center justify-between text-[11px] text-slate-700 dark:text-slate-300">
                                <span class="font-medium">{{ $sel->choiceGroupName }}:</span>
                                <span class="font-semibold text-purple-800 dark:text-purple-200">{{ $sel->selectedName }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div>
            <div class="mb-2 flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                <i data-lucide="user-round" class="h-3.5 w-3.5"></i>
                Assignment
            </div>
            <div class="space-y-2">
                <div class="rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                    <div class="flex items-center justify-between gap-1">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                            {{ $hasMultipleTherapists ? 'Therapists' : 'Staff' }}
                        </p>
                        @if($therapistsReq > 1)
                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider bg-teal-50 text-teal-700 border border-teal-200 dark:bg-teal-900/40 dark:text-teal-300 dark:border-teal-800">
                                {{ $therapistsReq }} Required
                            </span>
                        @endif
                    </div>

                    @if($hasMultipleTherapists)
                        <div class="mt-1.5 space-y-1.5">
                            {{-- Primary Therapist --}}
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold text-slate-800 dark:text-slate-200" title="{{ $primaryStaff?->full_name ?? 'Unassigned' }}">
                                        {{ $primaryStaff?->full_name ?? 'Unassigned' }}
                                    </p>
                                </div>
                                <span class="inline-flex shrink-0 items-center rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 border border-teal-200 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-800">
                                    Primary
                                </span>
                            </div>

                            {{-- Assisting Therapist(s) --}}
                            @if($assistingStaff->isNotEmpty())
                                @foreach($assistingStaff as $asst)
                                    <div class="flex items-center justify-between gap-2 border-t border-slate-100 pt-1.5 dark:border-slate-800">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-xs font-semibold text-slate-800 dark:text-slate-200" title="{{ $asst->full_name }}">
                                                {{ $asst->full_name }}
                                            </p>
                                        </div>
                                        <span class="inline-flex shrink-0 items-center rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800">
                                            Assisting
                                        </span>
                                    </div>
                                @endforeach
                            @elseif($therapistsReq > 1)
                                <div class="flex items-center justify-between gap-2 border-t border-slate-100 pt-1.5 dark:border-slate-800">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs italic text-amber-600 dark:text-amber-400">
                                            Assisting therapist pending
                                        </p>
                                    </div>
                                    <span class="inline-flex shrink-0 items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800">
                                        Needed
                                    </span>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">
                            {{ $appointment->staff?->full_name ?? 'Unassigned' }}
                        </p>
                    @endif
                </div>
                <div class="rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                        Room
                    </p>
                    <p class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">
                        {{ $appointment->room?->name ?? 'No room assigned' }}
                    </p>
                </div>
            </div>
        </div>
        <div>
            <div class="mb-2 flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                <i data-lucide="wallet" class="h-3.5 w-3.5"></i>
                Payment
            </div>
            <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Total
                    </span>
                    <span class="text-sm font-bold text-slate-900 dark:text-white">
                        ₱{{ number_format((float) $appointment->total_price, 2) }}
                    </span>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Paid
                    </span>
                    <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                        ₱{{ number_format($totalPaid, 2) }}
                    </span>
                </div>
                <div class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2 dark:border-slate-700">
                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                        Balance
                    </span>
                    @if($fullyPaid)
                        <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                            Fully Paid
                        </span>
                    @else
                        <span class="text-sm font-bold text-amber-600 dark:text-amber-400">
                            ₱{{ number_format($balance, 2) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="flex flex-wrap gap-2 border-t border-slate-100 bg-slate-50/70 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/30">
        <button
            type="button"
            @click="openExtra({{ $appointment->id }})"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        >
            <i data-lucide="plus-circle" class="h-3.5 w-3.5"></i>
            Add Service
        </button>
        <button
            type="button"
            @click="openReassign(
                {{ $appointment->id }},
                @js($appointment->staff?->id),
                @js(route('receptionist.reassign', $appointment->id)),
                {{ $therapistsReq }},
                @js($assistingStaff->pluck('id')->values()->toArray())
            )"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        >
            <i data-lucide="user-round-cog" class="h-3.5 w-3.5"></i>
            Reassign
        </button>
        <button
            type="button"
            @click="openTreatmentNotes(
                {{ $appointment->id }},
                @js($appointment->treatment_notes ?? ''),
                @js(route('receptionist.update-treatment-notes', $appointment->id)),
                @js($customerName)
            )"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        >
            <i data-lucide="file-text" class="h-3.5 w-3.5 text-teal-600 dark:text-teal-400"></i>
            Notes
        </button>
        <button
            type="button"
            @click="openReschedule(
                {{ $appointment->id }},
                @js($appointment->appointment_date->format('Y-m-d')),
                @js(\Carbon\Carbon::parse($appointment->start_time)->format('H:i')),
                @js($appointment->room_id),
                @js(route('receptionist.reschedule', $appointment->id))
            )"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        >
            <i data-lucide="calendar-clock" class="h-3.5 w-3.5"></i>
            Reschedule
        </button>
        <button
            type="button"
            @click="openNoShow(
                {{ $appointment->id }},
                @js(route('receptionist.no-show', $appointment->id)),
                {{ $totalPaid > 0 ? 'true' : 'false' }},
                @js($customerName)
            )"
            class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 dark:border-rose-900/60 dark:bg-slate-900 dark:text-rose-400 dark:hover:bg-rose-950/30"
        >
            <i data-lucide="user-x" class="h-3.5 w-3.5"></i>
            No-show
        </button>
        <div class="ml-auto">
            <button
                type="button"
                @click="openComplete(
                    {{ $appointment->id }},
                    @js(route('receptionist.complete', $appointment)),
                    {{ $fullyPaid ? 'true' : 'false' }},
                    @js($balance)
                )"
                class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-teal-700"
            >
                <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>
                @if($fullyPaid)
                    Complete
                @else
                    Complete & Collect
                @endif
            </button>
        </div>
    </div>
</article>


