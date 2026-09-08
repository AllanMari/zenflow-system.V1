@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Sales Report')

@push('styles')

<style>
    .card-hover { transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
    .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04); }
    .bar-animate { transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); }
    .tab-btn { position: relative; transition: all 0.3s ease; }
    .tab-btn.active { color: #0d9488; font-weight: 700; }
    .dark .tab-btn.active { color: #2dd4bf; }
    .tab-btn::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 100%; height: 2px; background: currentColor; transform: scaleX(0); transition: transform 0.3s ease; }
    .tab-btn.active::after { transform: scaleX(1); }

    /* Transaction Log Table Styles */
    .tx-spa-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.75rem; }
    .tx-spa-table th { background-color: #f9fafb; padding: 0.75rem 0.5rem; font-weight: 700; color: #4b5563; border-bottom: 1px solid #e5e7eb; text-transform: uppercase; letter-spacing: 0.05em; }
    .dark .tx-spa-table th { background-color: #1f2937; color: #d1d5db; border-bottom-color: #374151; }
    
    .tx-row { border-bottom: 1px solid #e5e7eb; transition: background-color 0.15s; }
    .dark .tx-row { border-bottom-color: #374151; }
    .tx-row:hover { background-color: #f3f4f6; }
    .dark .tx-row:hover { background-color: #374151; }
    
    .tx-spa-table td { padding: 0.75rem 0.5rem; color: #374151; vertical-align: middle; }
    .dark .tx-spa-table td { color: #d1d5db; }
    
    .tx-num { width: 3%; text-align: center; font-weight: 600; color: #9ca3af; }
    .tx-name { font-weight: 600; color: #111827; }
    .dark .tx-name { color: #f9fafb; }
    
    .tx-gross, .tx-net, .tx-com, .tx-discount { text-align: right; font-variant-numeric: tabular-nums; }
    .tx-net { font-weight: 700; color: #0d9488; }
    .dark .tx-net { color: #2dd4bf; }
    .tx-com { font-weight: 700; color: #0284c7; }
    .dark .tx-com { color: #38bdf8; }
    
    .tx-discount-badge { display: inline-block; background-color: #fef3c7; color: #d97706; padding: 0.125rem 0.375rem; border-radius: 9999px; font-weight: 700; font-size: 0.65rem; }
    .dark .tx-discount-badge { background-color: rgba(217, 119, 6, 0.2); color: #fcd34d; }
    
    .tx-com-badge { display: inline-flex; align-items: center; justify-content: center; background-color: #d1fae5; color: #059669; width: 1.25rem; height: 1.25rem; border-radius: 9999px; font-weight: bold; }
    .dark .tx-com-badge { background-color: rgba(5, 150, 105, 0.2); color: #34d399; }
    
    .tx-total-row { background-color: #f9fafb; font-weight: 800; border-top: 2px solid #e5e7eb; }
    .dark .tx-total-row { background-color: #1f2937; border-top-color: #4b5563; }
    
    /* Hover Dropdown for Services */
    .tx-srv-dropdown { position: relative; display: inline-block; }
    .tx-srv-menu { display: none; position: absolute; z-index: 50; left: 0; top: 100%; background: #fff; border: 1px solid #e5e7eb; border-radius: 0.375rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 0.25rem 0; min-width: 120px; white-space: nowrap; }
    .dark .tx-srv-menu { background: #1f2937; border-color: #374151; }
    .tx-srv-dropdown:hover .tx-srv-menu { display: block; }
    .tx-srv-menu-item { padding: 0.25rem 0.75rem; font-size: 0.7rem; color: #374151; }
    .dark .tx-srv-menu-item { color: #d1d5db; }
</style>
@endpush

@section('content')
@php
    // FIX: Define $currency globally here so all partials have access to it
    $currency = '₱';

    $safeTotalAppts       = max(intval($totalApptsInPeriod ?? 0), 1);
    $safeCompleted        = intval($completedApptsInPeriod ?? 0);
    $safeCancelled        = intval($cancelledApptsInPeriod ?? 0);
    $safeNoShow           = intval($noShowApptsInPeriod ?? 0);
    $safeTotalCount       = intval($totalCount ?? 0);
    $safeUniqueCustomers  = intval($uniqueCustomers ?? 0);
    $safeDeposits         = floatval($deposits ?? 0);
    $safeTotalRevenue     = floatval($totalRevenue ?? 0);
    $safeAvgSale          = floatval($avgSale ?? 0);
    $safeRevPerComp       = floatval($revPerCompletedAppt ?? 0);
    $safeRevenueChange    = floatval($revenueChange ?? 0);
    $safeConversionRate   = min(100, floatval($conversionRate ?? 0));
    $safeCompletionRate   = min(100, round(($safeCompleted / $safeTotalAppts) * 100, 1));
    $safeCancellationRate = min(100, round(($safeCancelled / $safeTotalAppts) * 100, 1));
    $safeNoShowRate       = min(100, round(($safeNoShow / $safeTotalAppts) * 100, 1));
@endphp

<div x-data="{ activeTab: 'overview' }" class="no-print">
    
<!-- Sleek Toolbar with Dropdown PDF Actions -->
    <div class="flex flex-col md:flex-row justify-between items-center bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 p-4 mb-6">
<form method="GET" class="flex flex-wrap items-center gap-3">
            <select name="period" onchange="this.form.submit()" class="text-sm border-gray-200 rounded-lg focus:ring-brand-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-300 py-2">
                <option value="daily"   {{ $period=='daily'   ? 'selected' : '' }}>Daily (Today)</option>
                <option value="weekly"  {{ $period=='weekly'  ? 'selected' : '' }}>Weekly</option>
                <option value="monthly" {{ $period=='monthly' ? 'selected' : '' }}>Monthly</option>
                <option value="yearly"  {{ $period=='yearly'  ? 'selected' : '' }}>Yearly</option>
                <option value="custom"  {{ $period=='custom'  ? 'selected' : '' }}>Custom Range</option>
            </select>

            @if($period === 'custom')
                <div class="flex items-center gap-2">
                    <input type="date" name="start_date" value="{{ request('start_date', $today->format('Y-m-d')) }}" class="text-sm border-gray-200 rounded-lg py-2 focus:ring-brand-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-300">
                    <span class="text-gray-500 dark:text-neutral-400 text-sm font-medium">to</span>
                    <input type="date" name="end_date" value="{{ request('end_date', $today->format('Y-m-d')) }}" class="text-sm border-gray-200 rounded-lg py-2 focus:ring-brand-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-300">
                    <button type="submit" class="py-2 px-3 text-sm font-semibold rounded-lg bg-brand-600 text-white hover:bg-brand-700 transition shadow-sm">Apply</button>
                </div>
            @else
                <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-bold bg-brand-50 text-brand-700 dark:bg-brand-900/40 dark:text-brand-400">
                    {{ $startDate->format('M d') }} — {{ $endDate->format('M d, Y') }}
                </span>
            @endif

            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            
            @if($period === 'custom')
                <a href="{{ route($routeName) }}" class="text-xs text-gray-500 hover:text-brand-600 dark:text-neutral-400 dark:hover:text-brand-400 font-medium underline underline-offset-2 ml-2 transition">
                    Clear
                </a>
            @endif
        </form>

        <div class="flex flex-wrap items-center gap-3 mt-4 md:mt-0">
            <!-- Period Report Dropdown (Dynamic Title) -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.away="open = false" type="button" class="py-2 px-3.5 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-gray-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 text-gray-700 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-800 transition shadow-sm">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="capitalize">{{ $period }} Report</span>
                    <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" style="display: none;" class="absolute right-0 mt-2 w-44 bg-white dark:bg-neutral-800 rounded-xl shadow-xl border border-gray-200 dark:border-neutral-700 overflow-hidden z-50">
                    <a href="{{ route($routeName . '.daily-report-pdf', array_merge(request()->all(), ['action' => 'download'])) }}" class="flex items-center gap-x-2.5 px-4 py-2.5 text-xs font-medium text-gray-700 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-700 transition">
                        📥 Download PDF
                    </a>
                    <a href="{{ route($routeName . '.daily-report-pdf', array_merge(request()->all(), ['action' => 'stream'])) }}" target="_blank" class="flex items-center gap-x-2.5 px-4 py-2.5 text-xs font-medium text-gray-700 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-700 transition border-t border-gray-100 dark:border-neutral-700">
                        👁️ Print Preview
                    </a>
                </div>
            </div>

            <!-- Business Report Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.away="open = false" type="button" class="py-2 px-3.5 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg bg-brand-600 text-white hover:bg-brand-700 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Business Report
                    <svg class="w-3 h-3 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" style="display: none;" class="absolute right-0 mt-2 w-44 bg-white dark:bg-neutral-800 rounded-xl shadow-xl border border-gray-200 dark:border-neutral-700 overflow-hidden z-50">
                    <a href="{{ route($routeName . '.business-report-pdf', array_merge(request()->all(), ['action' => 'download'])) }}" class="flex items-center gap-x-2.5 px-4 py-2.5 text-xs font-medium text-gray-700 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-700 transition">
                        📥 Download PDF
                    </a>
                    <a href="{{ route($routeName . '.business-report-pdf', array_merge(request()->all(), ['action' => 'stream'])) }}" target="_blank" class="flex items-center gap-x-2.5 px-4 py-2.5 text-xs font-medium text-gray-700 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-700 transition border-t border-gray-100 dark:border-neutral-700">
                        👁️ Print Preview
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex border-b border-gray-200 dark:border-neutral-700 mb-6 overflow-x-auto">
        <button @click="activeTab = 'overview'" :class="{'active': activeTab === 'overview'}" class="tab-btn px-4 py-3 text-sm font-medium text-gray-500 whitespace-nowrap">Overview & Insights</button>
        <button @click="activeTab = 'analytics'; setTimeout(() => window.dispatchEvent(new Event('resize')), 50)" :class="{'active': activeTab === 'analytics'}" class="tab-btn px-4 py-3 text-sm font-medium text-gray-500 whitespace-nowrap">Deep Analytics</button>
        <button @click="activeTab = 'transactions'" :class="{'active': activeTab === 'transactions'}" class="tab-btn px-4 py-3 text-sm font-medium text-gray-500 whitespace-nowrap">Transaction Log</button>
    </div>

    <!-- Tab Contents -->
    <div x-show="activeTab === 'overview'" x-transition.opacity>
        @include('shared.sales_partials.tab-overview')
    </div>

    <div x-show="activeTab === 'analytics'" style="display: none;" x-transition.opacity>
        @include('shared.sales_partials.tab-analytics')
    </div>

    <div x-show="activeTab === 'transactions'" style="display: none;" x-transition.opacity>
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-700 mb-6" id="transactionLog">
            @include('shared._transaction_log_table', ['txLogData' => $txLogData, 'currentStatus' => $currentStatus, 'currency' => $currency])
        </div>
    </div>
</div>

@include('shared.sales_partials.modals')
@include('shared.sales_partials.ai-chat')

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)';
    const textColor = isDark ? '#a3a3a3' : '#6b7280';
    const tooltipBg = isDark ? 'rgba(23,23,23,0.95)' : 'rgba(255,255,255,0.98)';
    const tooltipText = isDark ? '#e5e5e5' : '#171717';
    const tooltipBorder = isDark ? 'rgba(64,64,64,0.5)' : 'rgba(0,0,0,0.08)';

    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;
    Chart.defaults.font.family = "'Inter', 'Segoe UI', system-ui, sans-serif";
    Chart.defaults.font.size = 11;

    // Revenue Trend Chart
    const ctx1 = document.getElementById('salesChart')?.getContext('2d');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Revenue',
                    data: @json($chartValues),
                    backgroundColor: isDark ? '#14b8a6' : '#0d9488',
                    borderRadius: 8,
                    borderSkipped: false,
                    barPercentage: 0.55,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: tooltipBg, titleColor: tooltipText, bodyColor: tooltipText,
                        borderColor: tooltipBorder, borderWidth: 1, padding: 12, cornerRadius: 8, displayColors: false,
                        callbacks: { label: (ctx) => '₱' + ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor, drawBorder: false },
                        ticks: { color: textColor, padding: 8, callback: v => '₱' + (v >= 1000 ? (v/1000).toFixed(1) + 'k' : v.toLocaleString()) },
                        border: { display: false }
                    },
                    x: { grid: { display: false, drawBorder: false }, ticks: { color: textColor, padding: 8 }, border: { display: false } }
                }
            }
        });
    }

    // Payment Methods Doughnut
    const ctx2 = document.getElementById('methodChart')?.getContext('2d');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: @json($methodBreakdown->keys()->values()),
                datasets: [{
                    data: @json($methodBreakdown->pluck('total')->values()),
                    backgroundColor: ['#0d9488', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#10b981', '#06b6d4'],
                    borderWidth: 0, hoverOffset: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '68%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: textColor, boxWidth: 10, padding: 12, usePointStyle: true, pointStyle: 'circle' } },
                    tooltip: {
                        backgroundColor: tooltipBg, titleColor: tooltipText, bodyColor: tooltipText, borderColor: tooltipBorder, borderWidth: 1, padding: 10, cornerRadius: 8,
                        callbacks: {
                            label: (ctx) => {
                                const val = ctx.parsed;
                                const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
                                const pct = total > 0 ? Math.round((val/total)*100) : 0;
                                return ctx.label + ': ₱' + val.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Monthly Spike Line Chart
    const ctx3 = document.getElementById('monthlySpikeChart');
    if (ctx3) {
        new Chart(ctx3.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($monthlySpike['labels']),
                datasets: [
                    { label: 'Bookings', data: @json($monthlySpike['bookings']), borderColor: '#8b5cf6', backgroundColor: 'rgba(139, 92, 246, 0.08)', fill: true, tension: 0.4, borderWidth: 2.5 },
                    { label: 'Completion %', data: @json($monthlySpike['completionRates']), borderColor: '#10b981', borderDash: [6, 4], tension: 0.4, borderWidth: 2, yAxisID: 'y1' },
                    { label: 'No-Show %', data: @json($monthlySpike['noShowRates']), borderColor: '#ef4444', borderDash: [3, 3], tension: 0.4, borderWidth: 2, yAxisID: 'y1' }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', align: 'end', labels: { color: textColor, boxWidth: 10, usePointStyle: true, pointStyle: 'circle' } },
                    tooltip: { backgroundColor: tooltipBg, titleColor: tooltipText, bodyColor: tooltipText, borderColor: tooltipBorder, borderWidth: 1, padding: 10, cornerRadius: 8 }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor, drawBorder: false }, ticks: { color: textColor, padding: 8 }, border: { display: false } },
                    y1: { position: 'right', min: 0, max: 100, grid: { display: false }, ticks: { color: textColor, callback: v => v + '%', padding: 8 }, border: { display: false } },
                    x: { grid: { display: false, drawBorder: false }, ticks: { color: textColor, padding: 8 }, border: { display: false } }
                }
            }
        });
    }

    // Status Filter AJAX
    function updateStatusFilter() {
        const val = document.getElementById('statusFilter').value;
        const url = new URL(window.location.href);
        url.searchParams.set('status', val);
        url.searchParams.delete('page');

        const isAdmin = window.location.pathname.includes('/admin/');
        const endpoint = isAdmin ? '{{ route("admin.sales.tx-log") }}' : '{{ route("receptionist.sales.tx-log") }}';
        const container = document.getElementById('transactionLog');
        container.style.opacity = '0.4';

        fetch(`${endpoint}?${url.searchParams.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;
            container.style.opacity = '1';
            window.history.replaceState({}, '', url.toString());
        }).catch(() => { container.style.opacity = '1'; window.location.href = url.toString(); });
    }

function fetchTxPage(pageUrl) {
        const container = document.getElementById('transactionLog');
        container.style.opacity = '0.4';

        // Ensure we target the fragment endpoint instead of the full page route
        const urlObj = new URL(pageUrl, window.location.origin);
        const isAdmin = window.location.pathname.includes('/admin/');
        
        // Base fragment endpoint
        const endpoint = isAdmin ? '{{ route("admin.sales.tx-log") }}' : '{{ route("receptionist.sales.tx-log") }}';
        
        // Keep the pagination page and filter parameters intact
        const searchParams = urlObj.searchParams;

        fetch(`${endpoint}?${searchParams.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(r => {
            if (!r.ok) throw new Error('Failed to load page');
            return r.text();
        })
        .then(html => {
            container.innerHTML = html;
            container.style.opacity = '1';
            window.history.replaceState({}, '', urlObj.toString());
        })
        .catch(err => {
            container.style.opacity = '1';
            console.error('Pagination fetch failed:', err);
            window.location.href = pageUrl; // Fallback to full reload if network fails
        });
    }

    // Modals Handling
    function openTxModal() { document.getElementById('txModal').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    function closeTxModal() { document.getElementById('txModal').classList.add('hidden'); document.body.style.overflow = ''; }
    function openNoShowModal() { document.getElementById('noShowModal').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    function closeNoShowModal() { document.getElementById('noShowModal').classList.add('hidden'); document.body.style.overflow = ''; }

    window.addEventListener('click', e => {
        if (e.target.id === 'txModal') closeTxModal();
        if (e.target.id === 'noShowModal') closeNoShowModal();
    });

    // AI Chat Assistant Logic
    let aiChatHistory = [];
    let aiChatOpen = false;
    let aiIsTyping = false;

    function toggleAiChat() {
        aiChatOpen = !aiChatOpen;
        document.getElementById('aiChatPanel').classList.toggle('hidden', !aiChatOpen);
        if (aiChatOpen) setTimeout(() => document.getElementById('aiQuestionInput')?.focus(), 100);
    }

    function clearAiChat() {
        aiChatHistory = [];
        aiIsTyping = false;
        document.getElementById('aiChatMessages').innerHTML = `
            <div class="bg-brand-50 dark:bg-brand-900/20 p-4 rounded-xl text-xs text-gray-600 dark:text-neutral-300 border border-brand-100 dark:border-brand-800">
                <p class="font-bold text-brand-700 dark:text-brand-300 mb-1 text-sm">👋 Hey there! I'm Mari, your spa business advisor.</p>
                <p class="leading-relaxed">Ask me anything about your sales, appointments, or business strategy. I'm here to help! ✨</p>
            </div>
        `;
    }

    function sendAiQuestion(e) {
        e.preventDefault();
        if (aiIsTyping) return;
        const input = document.getElementById('aiQuestionInput');
        const question = input.value.trim();
        if (!question) return;

        addAiMessage('user', question);
        input.value = '';
        aiIsTyping = true;
        const typingId = addAiMessage('typing', 'Mari is thinking...');

        const params = new URLSearchParams(window.location.search);
        const endpoint = window.location.pathname.includes('/admin/') ? '{{ route("admin.sales.ai-chat") }}' : '{{ route("receptionist.sales.ai-chat") }}';

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ question, history: aiChatHistory, period: params.get('period') || 'daily', start_date: params.get('start_date') || '', end_date: params.get('end_date') || '' })
        })
        .then(r => r.json())
        .then(data => {
            if (data.type === 'error') {
                addAiMessage('ai', data.text, 'low');
            } else {
                addAiMessage('ai', data.answer, data.confidence, data.confidence === 'high' ? '✅ High confidence' : '⚡ Medium confidence', data.mood === 'celebratory' ? '🎉' : '🤖', data.action);
                aiChatHistory.push({ role: 'user', content: question });
                aiChatHistory.push({ role: 'assistant', content: data.answer });
                if (aiChatHistory.length > 20) aiChatHistory = aiChatHistory.slice(-20);
            }
        })
        .catch(() => addAiMessage('ai', 'Oops! Lost connection to Ollama. 🔌', 'low'))
        .finally(() => { removeAiMessage(typingId); aiIsTyping = false; });
    }

    function addAiMessage(type, text, confidence = null, badge = null, moodEmoji = null, action = null) {
        const container = document.getElementById('aiChatMessages');
        const id = 'msg_' + Date.now();
        const div = document.createElement('div');
        div.id = id;
        div.className = 'text-xs animate-fade-in';

        if (type === 'user') {
            div.innerHTML = `<div class="bg-gray-100 dark:bg-neutral-700 p-3 rounded-xl ml-10 text-gray-800 dark:text-neutral-200 text-sm font-medium shadow-sm">${escapeHtml(text)}</div>`;
        } else if (type === 'typing') {
            div.innerHTML = `<div class="bg-brand-50 dark:bg-brand-900/20 p-3 rounded-xl mr-10 flex items-center gap-2 text-brand-700 dark:text-brand-300 border border-brand-100 dark:border-brand-800"><span class="w-2 h-2 bg-brand-500 rounded-full animate-bounce"></span><span class="ml-1 text-xs font-medium">${escapeHtml(text)}</span></div>`;
        } else {
            div.innerHTML = `<div class="bg-white dark:bg-neutral-700 p-3.5 rounded-xl mr-10 border-l-4 border-green-500 shadow-sm"><div class="flex items-center gap-1.5 mb-1.5"><span class="text-base">${moodEmoji || '🤖'}</span><span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Mari</span></div><div class="text-gray-700 dark:text-neutral-200 whitespace-pre-line leading-relaxed text-sm">${escapeHtml(text)}</div>${action ? `<div class="mt-2 p-2.5 bg-brand-50 dark:bg-brand-900/30 rounded-lg"><span class="text-[10px] font-bold text-brand-700 uppercase tracking-wider">💡 Action</span><p class="text-xs text-brand-800 dark:text-brand-200 mt-0.5">${escapeHtml(action)}</p></div>` : ''}</div>`;
        }
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
        return id;
    }

    function removeAiMessage(id) { document.getElementById(id)?.remove(); }
    function escapeHtml(text) { const d = document.createElement('div'); d.textContent = text; return d.innerHTML; }
</script>
@endpush