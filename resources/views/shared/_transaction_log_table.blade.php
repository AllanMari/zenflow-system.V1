@php
    $statusLabel = match($currentStatus) {
        'customer_no_show' => 'No Show',
        default => ucfirst(str_replace('_', ' ', $currentStatus))
    };
    $statusColor = match($currentStatus) {
        'completed' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        'customer_no_show' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
        'cancelled' => 'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300',
        default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
    };

    $txRows = $txLogData['rows'];
    $txGrandGross = $txLogData['grandGross'];
    $txGrandNet = $txLogData['grandNet'];
    $txGrandCom = $txLogData['grandCom'];
    $totalFiltered = $txLogData['totalFiltered'];
    $txPagination = $txLogData['pagination'];
@endphp

<div class="p-4 border-b dark:border-gray-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 no-print">
    <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Transaction Log</h3>
    <div class="flex items-center gap-3">
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $totalFiltered }} unique transactions</span>
        <select id="statusFilter" onchange="updateStatusFilter()" class="border rounded p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-xs">
            <option value="completed" {{ $currentStatus == 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="cancelled" {{ $currentStatus == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            <option value="customer_no_show" {{ $currentStatus == 'customer_no_show' ? 'selected' : '' }}>No Show</option>
        </select>
        <span id="statusBadge" class="text-xs px-2.5 py-1 rounded-full font-bold {{ $statusColor }}">
            {{ $statusLabel }} ({{ $totalFiltered }})
        </span>
    </div>
</div>

@if(count($txRows) > 0)
<div class="overflow-x-auto p-2">
    <table class="tx-spa-table" id="transactionTable">
        <thead>
            <tr>
                <th class="tx-num">#</th>
                <th class="tx-name">CLIENT NAME</th>
                <th class="tx-room">ROOM</th>
                <th class="tx-time">TIME START</th>
                <th class="tx-time">TIME END</th>
                <th class="tx-therapist">THERAPIST</th>
                <th class="tx-hrs">HRS</th>
                <th class="tx-srvc">SRVCS</th>
                <th class="tx-gross">GROSS</th>
                <th class="tx-discount" colspan="2">DISCOUNT</th>
                <th class="tx-net">NET</th>
                <th class="tx-note">NOTE</th>
                <th class="tx-pct">%</th>
                <th class="tx-com">THERAPIST COM</th>
            </tr>
        </thead>
        <tbody>
            @foreach($txRows as $row)
            <tr class="tx-row" data-status="{{ $row['filterKey'] }}">
                <td class="tx-num">{{ $row['rowNum'] }}</td>
                <td class="tx-name">{{ $row['customerName'] }}</td>
                <td class="tx-room">{{ $row['room'] }}</td>
                <td class="tx-time">{{ $row['startTime'] }}</td>
                <td class="tx-time">{{ $row['endTime'] }}</td>
                <td class="tx-therapist">{{ $row['staffName'] }}</td>
                <td class="tx-hrs">{{ $row['durationHrs'] }}</td>
                <td class="tx-srvc">
                    @if(count($row['serviceList']) > 0)
                    <div class="tx-srv-dropdown">
                        <div class="inline-flex items-center gap-0.5 font-bold text-[10px] cursor-pointer">
                            {{ $row['serviceList'][0]['code'] }}
                            @if(count($row['serviceList']) > 1)
                            <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            @endif
                        </div>
                        @if(count($row['serviceList']) > 1)
                        <div class="tx-srv-menu">
                            @foreach($row['serviceList'] as $s)
                            <div class="tx-srv-menu-item">{{ $s['code'] }} — {{ $s['name'] }}</div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @else
                    <span class="text-gray-400">-</span>
                    @endif
                </td>
                <td class="tx-gross">{{ $row['grossAmount'] }}</td>
                <td class="tx-discount" style="text-align:left; font-size:9px;">
                    @if($row['discountAmount'])
                        <span class="tx-discount-badge">DISCOUNT</span>
                    @else
                        —
                    @endif
                </td>
                <td class="tx-discount" style="text-align:right;">
                    @if($row['discountAmount'])
                        {{ $row['discountAmount'] }}
                    @else
                        —
                    @endif
                </td>
                <td class="tx-net">{{ $row['netAmount'] }}</td>
                <td class="tx-note">
                    @if($row['noteText'] && trim($row['noteText']) !== '')
                        <span class="tx-com-badge" title="{{ $row['noteText'] }}">✓</span>
                    @else
                        —
                    @endif
                </td>
                <td class="tx-pct">{{ $row['comPct'] }}%</td>
                <td class="tx-com">{{ $row['therapistCom'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tx-total-row">
                <td colspan="8" style="text-align:right; padding:6px 8px;">TOTAL</td>
                <td class="tx-gross" style="padding:6px 8px;">{{ number_format($txGrandGross, 2) }}</td>
                <td colspan="2" style="padding:6px 8px;"></td>
                <td class="tx-net" style="padding:6px 8px;">{{ number_format($txGrandNet, 2) }}</td>
                <td colspan="2" style="padding:6px 8px;"></td>
                <td class="tx-com" style="padding:6px 8px;">{{ number_format($txGrandCom, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

@if($txPagination->lastPage() > 1)
<div class="p-4 border-t dark:border-gray-700 no-print flex flex-col sm:flex-row justify-between items-center gap-2 text-xs">
    <span class="text-gray-500 dark:text-gray-400">
        Showing <strong class="text-gray-700 dark:text-gray-200">{{ count($txRows) }}</strong> of <strong class="text-gray-700 dark:text-gray-200">{{ $totalFiltered }}</strong> transactions
    </span>
    <div class="flex items-center gap-1">
        @if($txPagination->currentPage() > 1)
            <button onclick="fetchTxPage('{{ $txPagination->previousPageUrl() }}')" class="text-teal-600 hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-300 font-medium px-3 py-1.5 rounded hover:bg-teal-50 dark:hover:bg-teal-900/20 transition">← Prev</button>
        @else
            <span class="text-gray-400 cursor-not-allowed px-3 py-1.5">← Prev</span>
        @endif

        @for($i = 1; $i <= $txPagination->lastPage(); $i++)
            @if($i == $txPagination->currentPage())
                <span class="bg-teal-600 text-white px-3 py-1.5 rounded font-bold">{{ $i }}</span>
            @else
                <button onclick="fetchTxPage('{{ $txPagination->url($i) }}')" class="text-gray-600 hover:text-teal-600 dark:text-gray-400 dark:hover:text-teal-400 font-medium px-3 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition">{{ $i }}</button>
            @endif
        @endfor

        @if($txPagination->currentPage() < $txPagination->lastPage())
            <button onclick="fetchTxPage('{{ $txPagination->nextPageUrl() }}')" class="text-teal-600 hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-300 font-medium px-3 py-1.5 rounded hover:bg-teal-50 dark:hover:bg-teal-900/20 transition">Next →</button>
        @else
            <span class="text-gray-400 cursor-not-allowed px-3 py-1.5">Next →</span>
        @endif
    </div>
</div>
@else
<div class="p-4 border-t dark:border-gray-700 no-print text-xs text-gray-500 dark:text-gray-400 text-center">
    Showing all {{ $totalFiltered }} transactions
</div>
@endif
@else
<div class="text-center py-12">
    <p class="text-gray-500 dark:text-gray-400 mb-2">No matching transactions found.</p>
    @if($txPagination->currentPage() > 1)
    <button onclick="fetchTxPage('{{ $txPagination->url(1) }}')" class="text-teal-600 hover:underline text-sm">← Back to page 1</button>
    @endif
</div>
@endif