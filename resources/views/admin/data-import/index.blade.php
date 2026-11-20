@extends(auth()->user()->roles->contains('name', 'admin') ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Data Import & Templates')

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb & Title --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1">
                <span>Management</span>
                <span>/</span>
                <span class="text-brand-600 dark:text-brand-400 font-medium">Data Import</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                Data Import &amp; Template Hub
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Streamline operations by importing existing business data using Excel-compatible CSV templates with duplicate detection and preview safety.
            </p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-800/40 dark:bg-emerald-950/20 dark:text-emerald-300 flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800/40 dark:bg-red-950/20 dark:text-red-300 flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    {{-- Dataset Type Selector Tabs --}}
    <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-800 pb-3">
        <a href="{{ route('admin.data-import.index', ['type' => 'attendance']) }}"
           class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ $currentType === 'attendance' ? 'bg-brand-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Attendance Logs
        </a>

        <a href="{{ route('admin.data-import.index', ['type' => 'offline_sales']) }}"
           class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ $currentType === 'offline_sales' ? 'bg-brand-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6H2.25m19.5 0H21a.75.75 0 01-.75-.75V4.5m0 0a48.667 48.667 0 00-7.5-1.125m-3 0a48.667 48.667 0 00-7.5 1.125m18 0v1.5c0 .621-.504 1.125-1.125 1.125H3.375c-.621 0-1.125-.504-1.125-1.125V4.5" />
            </svg>
            Offline Sales &amp; Transactions
        </a>
    </div>

    {{-- Full-Width Stacked Layout: Top: Upload card; Bottom: Template Specifications --}}
    <div class="space-y-6">

        {{-- Upload & Preview Card (Full Width) --}}
        <div class="w-full bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800 mb-6">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        Upload {{ $templateDef['name'] }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Choose your Excel (.xlsx) or CSV spreadsheet to inspect and preview records.
                    </p>
                </div>
                <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-600/20 dark:bg-brand-950/50 dark:text-brand-300">
                    Step 1: Preview
                </span>
            </div>

            <form action="{{ route('admin.data-import.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                <input type="hidden" name="import_type" value="{{ $currentType }}">

                {{-- Drag and drop file picker --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
                        Select Spreadsheet File (.xlsx or .csv) <span class="text-red-500">*</span>
                    </label>

                    <div class="relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/20 px-6 py-8 text-center hover:border-brand-500 transition">
                        <svg class="mx-auto h-12 w-12 text-teal-600 dark:text-teal-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>

                        <div class="flex text-sm text-gray-600 dark:text-gray-400">
                            <input id="csv_file" name="csv_file" type="file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain" required class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-950/60 dark:file:text-brand-300">
                        </div>
                        <p class="text-[11px] text-gray-400 mt-2">
                            Supports Microsoft Excel <strong>.xlsx</strong> (recommended) and standard <strong>.csv</strong> files (max 10MB).
                        </p>
                    </div>

                    @error('csv_file')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Safety notice --}}
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/40 p-3.5 border border-slate-200 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-400 space-y-1">
                    <div class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Safe Import Guarantee
                    </div>
                    <p>
                        Uploading will <strong>not</strong> modify your database immediately. You will be presented with a full record preview highlighting valid entries, detected duplicates, and any validation warnings before final confirmation.
                    </p>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                        Preview &amp; Validate Records
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        {{-- Template Specifications Card (Full Width) --}}
        <div class="w-full bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800 mb-5">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        Template Specifications
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Specifications and guidelines for formatting your spreadsheet records.
                    </p>
                </div>
                <a href="{{ route('admin.data-import.template', ['type' => $currentType, 'format' => 'xlsx']) }}"
                   class="inline-flex items-center gap-1.5 text-xs font-bold text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950/40 px-3 py-1.5 rounded-lg border border-teal-200 dark:border-teal-800 hover:bg-teal-100 transition">
                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download .XLSX
                </a>
            </div>

            {{-- Easy Guide / Simplified instructions for non-native English speakers --}}
            <div class="rounded-xl bg-teal-50/70 dark:bg-teal-950/30 border border-teal-100 dark:border-teal-900/40 p-4 mb-6 text-xs text-teal-950 dark:text-teal-200 space-y-2">
                <div class="font-bold flex items-center gap-1.5 text-teal-800 dark:text-teal-300">
                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a3.75 3.75 0 10-7.5 0 3.75 3.75 0 007.5 0zm0 0h6m-6 0V6" />
                    </svg>
                    <span>Simple Instructions:</span>
                </div>
                <ul class="list-disc list-inside space-y-1.5 text-gray-700 dark:text-gray-300 text-[11px] leading-relaxed">
                    @foreach(($templateDef['simple_instructions'] ?? []) as $instruction)
                        <li>{{ $instruction }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Column Specifications Responsive Grid (1 col mobile, 2 col md, 3 col xl) --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">
                        Expected Column Specifications ({{ count($templateDef['columns'] ?? []) }} Columns)
                    </p>
                    <span class="text-xs text-gray-400">
                        Fill in records starting after the header row
                    </span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5">
                    @foreach(($templateDef['columns'] ?? []) as $col)
                        <div class="p-3.5 rounded-xl border border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-800/20 flex flex-col justify-between hover:border-gray-300 dark:hover:border-gray-700 transition">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <code class="font-mono font-bold text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-xs">{{ $col['label'] }}</code>
                                    @if(!empty($col['required']))
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 px-2.5 py-0.5 rounded-full border border-rose-200 dark:border-rose-800/40 shrink-0">
                                            Required
                                        </span>
                                    @else
                                        <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-800/50 px-2.5 py-0.5 rounded-full border border-gray-200 dark:border-gray-700/50 shrink-0">
                                            Optional
                                        </span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed">{{ $col['hint'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Download Template Actions --}}
            <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-semibold text-teal-700 dark:text-teal-400">Tip:</span> Use the <span class="font-semibold">Excel (.XLSX) template</span> for full styling, proper column widths, and readable text wrapping.
                </p>
                <div class="flex flex-col sm:flex-row items-center gap-2.5 w-full sm:w-auto">
                    <a href="{{ route('admin.data-import.template', ['type' => $currentType, 'format' => 'csv']) }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        Plain CSV (.csv)
                    </a>

                    <a href="{{ route('admin.data-import.template', ['type' => $currentType, 'format' => 'xlsx']) }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 dark:bg-teal-700 px-5 py-2.5 text-xs font-bold text-white hover:bg-teal-700 dark:hover:bg-teal-600 shadow-sm transition">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Download Styled Excel (.XLSX) Template (Recommended)
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
