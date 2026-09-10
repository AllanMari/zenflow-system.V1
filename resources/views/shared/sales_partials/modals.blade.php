{{-- ============================================================
     TRANSACTION ANALYTICS MODAL
     ============================================================ --}}
<div
    id="txModal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-neutral-950/60 px-4 py-6 backdrop-blur-sm no-print"
    aria-hidden="true"
>
    <div
        class="relative flex w-full max-w-2xl max-h-[88vh] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-neutral-700 dark:bg-neutral-900"
        role="dialog"
        aria-modal="true"
        aria-labelledby="txModalTitle"
    >

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-5 dark:border-neutral-800">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        />
                    </svg>
                </div>

                <div class="min-w-0">
                    <h3
                        id="txModalTitle"
                        class="truncate text-base font-bold text-gray-900 dark:text-white"
                    >
                        Transaction Analytics
                    </h3>

                    <p class="mt-0.5 text-xs text-gray-500 dark:text-neutral-400">
                        Payment activity for the selected period
                    </p>
                </div>
            </div>

            <button
                type="button"
                onclick="closeTxModal()"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:hover:bg-neutral-800 dark:hover:text-neutral-200"
                aria-label="Close modal"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto">
            <div class="space-y-6 p-6">

                {{-- Average Transaction --}}
                <div class="relative overflow-hidden rounded-2xl border border-brand-100 bg-brand-50 p-5 dark:border-brand-500/20 dark:bg-brand-500/10">
                    <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-brand-200/40 blur-2xl dark:bg-brand-500/10"></div>

                    <div class="relative flex items-center justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-brand-700 dark:text-brand-300">
                                Average Transaction
                            </p>

                            <p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                                {{ $currency }}{{ number_format($avgSale ?? 0, 2) }}
                            </p>

                            <p class="mt-1 text-xs text-gray-500 dark:text-neutral-400">
                                Average value per recorded transaction
                            </p>
                        </div>

                        <div class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm dark:bg-neutral-800 dark:text-brand-400 sm:flex">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10V5m0 14v-2m0-12a7 7 0 107 7"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Payment Type --}}
                <section>
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                Payment Type
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-neutral-400">
                                Breakdown by transaction type
                            </p>
                        </div>

                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-gray-500 dark:bg-neutral-800 dark:text-neutral-400">
                            {{ count($typeBreakdown ?? []) }} types
                        </span>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-neutral-800">
                        @forelse($typeBreakdown ?? [] as $type => $data)
                            <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-4 py-3.5 last:border-b-0 dark:border-neutral-800">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M9 8h6m-6 4h6m-6 4h3m6-12H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2z"
                                            />
                                        </svg>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold capitalize text-gray-800 dark:text-neutral-200">
                                            {{ $type }}
                                        </p>

                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-neutral-400">
                                            {{ $data['count'] }} transaction{{ $data['count'] == 1 ? '' : 's' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $currency }}{{ number_format($data['total'], 2) }}
                                    </p>

                                    <p class="mt-0.5 text-[10px] font-medium text-gray-400 dark:text-neutral-500">
                                        Total
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center px-6 py-10 text-center">
                                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-neutral-800 dark:text-neutral-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                </div>

                                <p class="text-sm font-semibold text-gray-700 dark:text-neutral-300">
                                    No payment type data
                                </p>

                                <p class="mt-1 text-xs text-gray-400 dark:text-neutral-500">
                                    No transactions were recorded for this period.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </section>

                {{-- Payment Method --}}
                <section>
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                Payment Method
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-neutral-400">
                                Breakdown by payment channel
                            </p>
                        </div>

                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-gray-500 dark:bg-neutral-800 dark:text-neutral-400">
                            {{ count($methodBreakdown ?? []) }} methods
                        </span>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-neutral-800">
                        @forelse($methodBreakdown ?? [] as $method => $data)
                            <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-4 py-3.5 last:border-b-0 dark:border-neutral-800">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2zm3 9h3"
                                            />
                                        </svg>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold capitalize text-gray-800 dark:text-neutral-200">
                                            {{ str_replace('_', ' ', $method) }}
                                        </p>

                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-neutral-400">
                                            {{ $data['count'] }} transaction{{ $data['count'] == 1 ? '' : 's' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $currency }}{{ number_format($data['total'], 2) }}
                                    </p>

                                    <p class="mt-0.5 text-[10px] font-medium text-gray-400 dark:text-neutral-500">
                                        Total
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center px-6 py-10 text-center">
                                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-neutral-800 dark:text-neutral-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a2 2 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                </div>

                                <p class="text-sm font-semibold text-gray-700 dark:text-neutral-300">
                                    No payment method data
                                </p>

                                <p class="mt-1 text-xs text-gray-400 dark:text-neutral-500">
                                    No transactions were recorded for this period.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </section>

            </div>
        </div>

        {{-- Footer --}}
        <div class="border-t border-gray-200 bg-gray-50/80 px-6 py-3.5 dark:border-neutral-800 dark:bg-neutral-950/50">
            <button
                type="button"
                onclick="closeTxModal()"
                class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700"
            >
                Close
            </button>
        </div>
    </div>
</div>


{{-- ============================================================
     NO SHOW DETAILS MODAL
     ============================================================ --}}
<div
    id="noShowModal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-neutral-950/60 px-4 py-6 backdrop-blur-sm no-print"
    aria-hidden="true"
>
    <div
        class="relative flex w-full max-w-2xl max-h-[88vh] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-neutral-700 dark:bg-neutral-900"
        role="dialog"
        aria-modal="true"
        aria-labelledby="noShowModalTitle"
    >

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-5 dark:border-neutral-800">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>

                <div class="min-w-0">
                    <h3
                        id="noShowModalTitle"
                        class="truncate text-base font-bold text-gray-900 dark:text-white"
                    >
                        No Show Details
                    </h3>

                    <p class="mt-0.5 text-xs text-gray-500 dark:text-neutral-400">
                        Review missed appointments and deposit outcomes
                    </p>
                </div>
            </div>

            <button
                type="button"
                onclick="closeNoShowModal()"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500 dark:hover:bg-neutral-800 dark:hover:text-neutral-200"
                aria-label="Close modal"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto">
            <div class="space-y-6 p-6">

                {{-- Deposit Summary --}}
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

                    <div class="rounded-xl border border-red-100 bg-red-50 p-4 dark:border-red-500/20 dark:bg-red-500/10">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-red-600 dark:text-red-400">
                                    Forfeited
                                </p>

                                <p class="mt-2 text-2xl font-extrabold tracking-tight text-red-700 dark:text-red-300">
                                    {{ $currency }}{{ number_format($noShowData['forfeited'] ?? 0, 2) }}
                                </p>

                                <p class="mt-1 text-xs text-red-600/70 dark:text-red-400/70">
                                    Retained deposits
                                </p>
                            </div>

                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/80 text-red-500 dark:bg-red-950/30 dark:text-red-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M12 9v4m0 4h.01M10.29 3.86l-8.1 14A2 2 0 003.92 21h16.16a2 2 0 001.73-3.14l-8.1-14a2 2 0 00-3.42 0z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-orange-100 bg-orange-50 p-4 dark:border-orange-500/20 dark:bg-orange-500/10">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-orange-600 dark:text-orange-400">
                                    Refunded
                                </p>

                                <p class="mt-2 text-2xl font-extrabold tracking-tight text-orange-700 dark:text-orange-300">
                                    {{ $currency }}{{ number_format($noShowData['refunded'] ?? 0, 2) }}
                                </p>

                                <p class="mt-1 text-xs text-orange-600/70 dark:text-orange-400/70">
                                    Returned deposits
                                </p>
                            </div>

                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/80 text-orange-500 dark:bg-orange-950/30 dark:text-orange-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- No-show List --}}
                <section>
                    <div class="mb-3 flex items-end justify-between gap-3">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                Missed Appointments
                            </h4>

                            <p class="mt-0.5 text-xs text-gray-500 dark:text-neutral-400">
                                Customers marked as no-show during this period
                            </p>
                        </div>

                        <span class="shrink-0 rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-bold text-red-600 dark:bg-red-500/10 dark:text-red-400">
                            {{ count($noShowData['list'] ?? []) }} record{{ count($noShowData['list'] ?? []) == 1 ? '' : 's' }}
                        </span>
                    </div>

                    <div class="space-y-2.5">
                        @forelse($noShowData['list'] ?? [] as $ns)

                            <div class="rounded-xl border border-gray-200 bg-white p-4 transition hover:border-gray-300 hover:shadow-sm dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-700">

                                <div class="flex items-start justify-between gap-4">

                                    {{-- Customer --}}
                                    <div class="flex min-w-0 items-start gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-600 dark:bg-neutral-800 dark:text-neutral-300">
                                            {{ strtoupper(substr($ns['customer'] ?? '?', 0, 1)) }}
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-gray-900 dark:text-white">
                                                {{ $ns['customer'] }}
                                            </p>

                                            <p class="mt-0.5 text-xs font-mono text-gray-500 dark:text-neutral-400">
                                                {{ $ns['phone'] }}
                                            </p>

                                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-500 dark:text-neutral-400">
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="1.7"
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                        />
                                                    </svg>
                                                    {{ \Carbon\Carbon::parse($ns['date'])->format('M d, Y') }}
                                                </span>

                                                <span class="hidden text-gray-300 dark:text-neutral-700 sm:inline">
                                                    •
                                                </span>

                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="1.7"
                                                            d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"
                                                        />
                                                    </svg>
                                                    {{ \Carbon\Carbon::parse($ns['marked_at'])->format('M d, g:i A') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Deposit --}}
                                    <div class="shrink-0 text-right">
                                        <p class="text-base font-extrabold text-gray-900 dark:text-white">
                                            {{ $currency }}{{ number_format($ns['deposit'], 2) }}
                                        </p>

                                        <span class="mt-1.5 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold
                                            {{ $ns['status'] === 'Refunded'
                                                ? 'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400'
                                                : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'
                                            }}"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full
                                                {{ $ns['status'] === 'Refunded'
                                                    ? 'bg-orange-500'
                                                    : 'bg-red-500'
                                                }}"
                                            ></span>

                                            {{ $ns['status'] }}
                                        </span>
                                    </div>

                                </div>
                            </div>

                        @empty

                            <div class="rounded-xl border border-dashed border-gray-300 px-6 py-12 text-center dark:border-neutral-700">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </div>

                                <p class="mt-3 text-sm font-bold text-gray-800 dark:text-neutral-200">
                                    No no-shows recorded
                                </p>

                                <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-gray-500 dark:text-neutral-400">
                                    There were no missed appointments during the selected reporting period.
                                </p>
                            </div>

                        @endforelse
                    </div>
                </section>

            </div>
        </div>

        {{-- Footer --}}
        <div class="border-t border-gray-200 bg-gray-50/80 px-6 py-3.5 dark:border-neutral-800 dark:bg-neutral-950/50">
            <button
                type="button"
                onclick="closeNoShowModal()"
                class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-red-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700"
            >
                Close
            </button>
        </div>
    </div>
</div>