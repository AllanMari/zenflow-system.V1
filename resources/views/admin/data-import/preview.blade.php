@extends(auth()->user()->roles->contains('name', 'admin') ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Preview Import - ' . $result['typeName'])

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1">
                <a href="{{ route('admin.data-import.index', ['type' => $type]) }}" class="hover:underline">Data Import</a>
                <span>/</span>
                <span class="text-brand-600 dark:text-brand-400 font-medium">Record Preview</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                Import Preview: {{ $result['typeName'] }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Carefully review the parsed records below. ZenFlow detects duplicates and errors automatically to prevent data corruption.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.data-import.index', ['type' => $type]) }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Cancel &amp; Re-upload
            </a>

            @if($result['validCount'] > 0)
                <form action="{{ route('admin.data-import.confirm') }}" method="POST">
                    @csrf
                    <input type="hidden" name="preview_token" value="{{ $previewToken }}">
                    <input type="hidden" name="import_type" value="{{ $type }}">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Confirm &amp; Import ({{ $result['validCount'] }} Valid {{ $result['validCount'] === 1 ? 'Record' : 'Records' }})
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        {{-- Total --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Rows In File</p>
            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $result['totalRows'] }}</p>
            <p class="mt-1 text-[11px] text-gray-400">Processed from uploaded CSV</p>
        </div>

        {{-- Ready to Import --}}
        <div class="rounded-2xl border border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/50 dark:bg-emerald-950/20 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Ready to Import</p>
                <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </div>
            <p class="mt-2 text-2xl font-bold text-emerald-900 dark:text-emerald-300">{{ $result['validCount'] }}</p>
            <p class="mt-1 text-[11px] text-emerald-700/80 dark:text-emerald-400/80">Valid, non-duplicate records</p>
        </div>

        {{-- Duplicates Skipped --}}
        <div class="rounded-2xl border border-amber-200 dark:border-amber-900/40 bg-amber-50/50 dark:bg-amber-950/20 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">Duplicates (Skipped)</p>
                <span class="inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
            </div>
            <p class="mt-2 text-2xl font-bold text-amber-900 dark:text-amber-300">{{ $result['duplicateCount'] }}</p>
            <p class="mt-1 text-[11px] text-amber-700/80 dark:text-amber-400/80">Already in database / duplicated</p>
        </div>

        {{-- Validation Errors --}}
        <div class="rounded-2xl border border-red-200 dark:border-red-900/40 bg-red-50/50 dark:bg-red-950/20 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-red-700 dark:text-red-400">Validation Errors</p>
                <span class="inline-flex h-2 w-2 rounded-full bg-red-500"></span>
            </div>
            <p class="mt-2 text-2xl font-bold text-red-900 dark:text-red-300">{{ $result['invalidCount'] }}</p>
            <p class="mt-1 text-[11px] text-red-700/80 dark:text-red-400/80">Missing or invalid field values</p>
        </div>
    </div>

    {{-- Validation Errors Section (If Any) --}}
    @if(!empty($result['invalidRecords']))
        <div class="rounded-2xl border border-red-200 dark:border-red-900/40 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="bg-red-50/80 dark:bg-red-950/40 px-6 py-4 border-b border-red-200 dark:border-red-900/40 flex items-center justify-between">
                <div class="flex items-center gap-2 text-red-800 dark:text-red-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-sm font-bold">Rows with Validation Errors (Will Not Be Imported)</h3>
                </div>
                <span class="text-xs font-semibold text-red-700 dark:text-red-400">{{ count($result['invalidRecords']) }} issues</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider w-20">Row #</th>
                            <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider w-44">Column</th>
                            <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Validation Issue &amp; How to Fix</th>
                            <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider w-48">Submitted Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($result['invalidRecords'] as $inv)
                            @if(!empty($inv['errors']))
                                @foreach($inv['errors'] as $err)
                                    <tr class="hover:bg-red-50/30 dark:hover:bg-red-950/20">
                                        <td class="px-6 py-3 font-mono font-bold text-red-600">Row {{ $inv['row'] }}</td>
                                        <td class="px-6 py-3">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                                {{ $err['column'] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3">
                                            <p class="font-semibold text-red-700 dark:text-red-400">{{ $err['message'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $err['suggestion'] }}</p>
                                        </td>
                                        <td class="px-6 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                                            @if($err['value'] !== '')
                                                <span class="bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700">{{ $err['value'] }}</span>
                                            @else
                                                <span class="text-gray-400 italic">(empty)</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="hover:bg-red-50/30 dark:hover:bg-red-950/20">
                                    <td class="px-6 py-3 font-mono font-bold text-red-600">Row {{ $inv['row'] }}</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                            Record
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 font-semibold text-red-700 dark:text-red-400">
                                        {{ $inv['reason'] }}
                                    </td>
                                    <td class="px-6 py-3 font-mono text-[11px] text-gray-500 truncate max-w-xs">
                                        {{ json_encode($inv['data']) }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Duplicate Records Section (If Any) --}}
    @if(!empty($result['duplicateRecords']))
        <div class="rounded-2xl border border-amber-200 dark:border-amber-900/40 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="bg-amber-50/80 dark:bg-amber-950/40 px-6 py-4 border-b border-amber-200 dark:border-amber-900/40 flex items-center justify-between">
                <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-sm font-bold">Detected Duplicates (Will Be Safely Skipped)</h3>
                </div>
                <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">{{ count($result['duplicateRecords']) }} duplicates</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider w-20">Row #</th>
                            <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Duplicate Reason</th>
                            <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Raw Record</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($result['duplicateRecords'] as $dup)
                            <tr class="hover:bg-amber-50/30 dark:hover:bg-amber-950/20">
                                <td class="px-6 py-3 font-mono font-bold text-amber-600">{{ $dup['row'] }}</td>
                                <td class="px-6 py-3 font-semibold text-amber-700 dark:text-amber-400">{{ $dup['reason'] }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400 font-mono text-[11px] truncate max-w-md">
                                    {{ json_encode($dup['data']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Valid Records Ready to Import --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
        <div class="bg-gray-50 dark:bg-gray-800/60 px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                    Valid Records Ready to Commit ({{ count($result['validRecords']) }})
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    These new records will be inserted upon confirmation.
                </p>
            </div>
            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300">
                Ready for Database Commit
            </span>
        </div>

        @if(empty($result['validRecords']))
            <div class="p-8 text-center text-sm text-gray-500">
                No valid new records found in this CSV. Please review the errors or duplicate list above and re-upload.
            </div>
        @else
            <div class="overflow-x-auto max-h-[500px]">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0 z-10">
                        <tr>
                            <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider w-16">#</th>
                            @if($type === 'attendance')
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Staff Member</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Time In</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Time Out</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Notes</th>
                            @elseif($type === 'offline_sales')
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Date &amp; Time</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Service / Package</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Amount (PHP)</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Assigned Staff</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Room</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Treatment Notes</th>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Notes</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($result['validRecords'] as $index => $rec)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                <td class="px-6 py-3 font-mono text-gray-400">{{ $index + 1 }}</td>
                                @if($type === 'attendance')
                                    <td class="px-6 py-3">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $rec['staff_name'] ?? $rec['staff_username'] }}</p>
                                        <p class="text-[11px] text-gray-400 font-mono">{{ '@' . $rec['staff_username'] }}</p>
                                    </td>
                                    <td class="px-6 py-3 font-mono text-gray-700 dark:text-gray-300">{{ $rec['date'] }}</td>
                                    <td class="px-6 py-3">
                                        <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 font-semibold text-slate-700 dark:text-slate-300">
                                            {{ ucfirst($rec['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 font-mono text-gray-600 dark:text-gray-400">{{ !empty($rec['check_in']) ? \Carbon\Carbon::parse($rec['check_in'])->format('g:i A') : '—' }}</td>
                                    <td class="px-6 py-3 font-mono text-gray-600 dark:text-gray-400">{{ !empty($rec['check_out']) ? \Carbon\Carbon::parse($rec['check_out'])->format('g:i A') : '—' }}</td>
                                    <td class="px-6 py-3 text-gray-500 truncate max-w-xs">{{ $rec['notes'] ?? '—' }}</td>
                                @elseif($type === 'offline_sales')
                                    <td class="px-6 py-3 font-mono font-bold text-gray-900 dark:text-white">{{ $rec['offline_reference'] }}</td>
                                    <td class="px-6 py-3 font-mono text-gray-700 dark:text-gray-300">{{ $rec['transaction_date'] }} {{ $rec['transaction_time'] }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ $rec['service_name'] }}</span>
                                            @if(!empty($rec['is_package']))
                                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                                    Package
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-300 border border-teal-200 dark:border-teal-800">
                                                    Service
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 font-bold text-brand-600 dark:text-brand-400">₱{{ number_format($rec['amount'], 2) }}</td>
                                    <td class="px-6 py-3">
                                        <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px]">
                                            {{ $rec['payment_method'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-700 dark:text-gray-300">
                                        {{ $rec['customer_name'] }}
                                        @if(!empty($rec['customer_phone']))
                                            <span class="block text-[11px] text-gray-400 font-mono">{{ $rec['customer_phone'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400">
                                        @if(empty($rec['staff_id']))
                                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-800/40">
                                                Unassigned (No Commission)
                                            </span>
                                        @else
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $rec['staff_names'] ?? $rec['staff_name'] }}</span>
                                            @if(!empty($rec['assisting_staff_ids']))
                                                <span class="block text-[10px] text-teal-600 dark:text-teal-400 font-medium">Equal commission split ({{ count($rec['assisting_staff_ids']) + 1 }} therapists)</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400">
                                        @if(!empty($rec['room_name']))
                                            <span class="inline-flex items-center gap-1 font-medium text-slate-800 dark:text-slate-200">
                                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                {{ $rec['room_name'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 italic">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-xs">
                                        @if(!empty($rec['treatment_notes']))
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/30 text-amber-900 dark:text-amber-200 border border-amber-200 dark:border-amber-800/40 font-medium">
                                                <svg class="w-3 h-3 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                {{ $rec['treatment_notes'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 italic">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-gray-500 truncate max-w-xs">{{ $rec['notes'] ?? '—' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Bottom Action Bar --}}
    @if($result['validCount'] > 0)
        <div class="flex items-center justify-between rounded-2xl bg-white dark:bg-gray-900 p-4 border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Ready to commit <strong>{{ $result['validCount'] }}</strong> records to database. Transactions protect data integrity.
            </div>

            <form action="{{ route('admin.data-import.confirm') }}" method="POST">
                @csrf
                <input type="hidden" name="preview_token" value="{{ $previewToken }}">
                <input type="hidden" name="import_type" value="{{ $type }}">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Confirm &amp; Import {{ $result['validCount'] }} Records
                </button>
            </form>
        </div>
    @endif

</div>
@endsection
