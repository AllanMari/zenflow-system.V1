<!-- Transaction Analytics Modal -->
<div id="txModal" class="fixed inset-0 bg-black/60 hidden z-50 flex items-center justify-center backdrop-blur-sm no-print" aria-hidden="true">
    <div class="bg-white dark:bg-neutral-800 rounded-2xl p-6 w-full max-w-lg mx-4 shadow-2xl max-h-[80vh] overflow-y-auto border border-gray-200 dark:border-neutral-700" role="dialog" aria-modal="true" aria-labelledby="txModalTitle">
        <div class="flex justify-between items-center mb-5">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
                <h3 id="txModalTitle" class="text-lg font-bold text-gray-800 dark:text-white">Transaction Analytics</h3>
            </div>
            <button onclick="closeTxModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-neutral-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-neutral-700" aria-label="Close modal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="space-y-5">
            <div>
                <h4 class="text-[11px] font-bold text-gray-600 dark:text-neutral-300 uppercase tracking-wider mb-2">By Payment Type</h4>
                <div class="space-y-2">
                    @forelse($typeBreakdown ?? [] as $type => $data)
                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-neutral-700/40 rounded-lg">
                        <span class="capitalize font-semibold text-sm text-gray-700 dark:text-neutral-200">{{ $type }}</span>
                        <div class="text-right"><p class="font-bold text-blue-600 dark:text-blue-400 text-sm">{{ $data['count'] }}</p><p class="text-xs text-gray-400 dark:text-neutral-500">{{ $currency }}{{ number_format($data['total'], 2) }}</p></div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 dark:text-neutral-500 text-center py-2">No data</p>
                    @endforelse
                </div>
            </div>
            <div>
                <h4 class="text-[11px] font-bold text-gray-600 dark:text-neutral-300 uppercase tracking-wider mb-2">By Payment Method</h4>
                <div class="space-y-2">
                    @forelse($methodBreakdown ?? [] as $method => $data)
                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-neutral-700/40 rounded-lg">
                        <span class="capitalize font-semibold text-sm text-gray-700 dark:text-neutral-200">{{ $method }}</span>
                        <div class="text-right"><p class="font-bold text-brand-600 dark:text-brand-400 text-sm">{{ $data['count'] }}</p><p class="text-xs text-gray-400 dark:text-neutral-500">{{ $currency }}{{ number_format($data['total'], 2) }}</p></div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 dark:text-neutral-500 text-center py-2">No data</p>
                    @endforelse
                </div>
            </div>
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl text-center border border-blue-100 dark:border-blue-800">
                <p class="text-sm text-gray-600 dark:text-neutral-400 font-medium">Average Transaction Value</p>
                <p class="text-2xl font-extrabold text-blue-600 dark:text-blue-400 mt-1">{{ $currency }}{{ number_format($avgSale ?? 0, 2) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- No Show Details Modal -->
<div id="noShowModal" class="fixed inset-0 bg-black/60 hidden z-50 flex items-center justify-center backdrop-blur-sm no-print" aria-hidden="true">
    <div class="bg-white dark:bg-neutral-800 rounded-2xl p-6 w-full max-w-lg mx-4 shadow-2xl max-h-[80vh] overflow-y-auto border border-gray-200 dark:border-neutral-700" role="dialog" aria-modal="true" aria-labelledby="noShowModalTitle">
        <div class="flex justify-between items-center mb-5">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <h3 id="noShowModalTitle" class="text-lg font-bold text-red-600 dark:text-red-400">No Show Details</h3>
            </div>
            <button onclick="closeNoShowModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-neutral-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-neutral-700" aria-label="Close modal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-xl text-center border border-red-100 dark:border-red-800">
                <p class="text-[10px] text-gray-600 dark:text-neutral-400 uppercase font-bold tracking-wider mb-1">Forfeited</p>
                <p class="text-xl font-extrabold text-red-600 dark:text-red-400">{{ $currency }}{{ number_format($noShowData['forfeited'] ?? 0, 2) }}</p>
            </div>
            <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl text-center border border-orange-100 dark:border-orange-800">
                <p class="text-[10px] text-gray-600 dark:text-neutral-400 uppercase font-bold tracking-wider mb-1">Refunded</p>
                <p class="text-xl font-extrabold text-orange-600 dark:text-orange-400">{{ $currency }}{{ number_format($noShowData['refunded'] ?? 0, 2) }}</p>
            </div>
        </div>
        <div class="space-y-2">
            @forelse($noShowData['list'] ?? [] as $ns)
            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-neutral-700/40 rounded-lg border-l-4 {{ $ns['status'] === 'Refunded' ? 'border-orange-400' : 'border-red-400' }}">
                <div class="min-w-0">
                    <p class="font-semibold text-gray-800 dark:text-neutral-200 text-sm truncate">{{ $ns['customer'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-neutral-400 font-mono">{{ $ns['phone'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-neutral-400">{{ \Carbon\Carbon::parse($ns['date'])->format('M d, Y') }}</p>
                    <p class="text-[10px] text-gray-400 dark:text-neutral-500">Marked: {{ \Carbon\Carbon::parse($ns['marked_at'])->format('M d, g:i A') }}</p>
                </div>
                <div class="text-right shrink-0 ml-2">
                    <p class="font-bold {{ $ns['status'] === 'Refunded' ? 'text-orange-600 dark:text-orange-400' : 'text-red-600 dark:text-red-400' }}">{{ $currency }}{{ number_format($ns['deposit'], 2) }}</p>
                    <span class="inline-flex items-center gap-x-1.5 py-0.5 px-2 rounded-full text-[10px] font-bold {{ $ns['status'] === 'Refunded' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400' }}">{{ $ns['status'] }}</span>
                </div>
            </div>
            @empty
            <div class="flex flex-col items-center justify-center py-8 text-gray-400 dark:text-neutral-500">
                <svg class="w-10 h-10 mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-medium">No no-shows in this period.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>