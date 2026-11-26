@extends('layouts.master', [
    'roleLabel' => 'Member',
    'userRole' => 'Member',
    'settingsRoute' => 'profile.update',
])

@section('logo-icon')
    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
@endsection

@php
$isActive = fn($p) => request()->routeIs($p);

$navItems = [
    ['r'=>'customer-dashboard','l'=>'Dashboard','p'=>'customer-dashboard','i'=>'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
    ['r'=>'customer.bookings','l'=>'My Booking','p'=>'customer.bookings','i'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
    ['r'=>'booking.wizard','l'=>'Book Appointment','p'=>'booking.wizard','i'=>'M12 4v16m8-8H4'],
    ['r'=>'customer.profile','l'=>'My Profile','p'=>'customer.profile','i'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
];
@endphp

@section('sidebar-nav')
    @foreach($navItems as $item)
        <a href="{{ route($item['r']) }}"
           data-label="{{ $item['l'] }}"
           class="nav-item {{ $isActive($item['p']) ? 'active text-brand-600 dark:text-brand-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800/50' }} flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold">
            <span class="relative shrink-0 w-[18px] h-[18px] flex items-center justify-center">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['i'] }}"/>
                </svg>
            </span>
            <span class="nav-text whitespace-nowrap">{{ $item['l'] }}</span>
        </a>
    @endforeach
@endsection

@push('scripts')
<script>
(function () {
    const FEED_URL = @js(route('api.notifications.customer-attendance-feed'));
    const POLL_MS = 10000;
    const STORAGE_KEY = 'zenflow.customer.reminders.seen';

    let seen = new Set();
    try { seen = new Set(JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]')); }
    catch (e) { seen = new Set(); }
    let primed = false;

    function persistSeen() {
        try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(seen).slice(-200))); }
        catch (e) { /* in-memory dedupe still works */ }
    }

    function escapeHtml(value) {
        const node = document.createElement('div');
        node.textContent = value == null ? '' : String(value);
        return node.innerHTML;
    }

    function showToast(item) {
        if (typeof Swal === 'undefined') return;

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: escapeHtml(item.title || 'Appointment reminder'),
            html: '<div class="text-left text-xs">' + escapeHtml(item.message || '') + '</div>',
            showConfirmButton: true,
            confirmButtonText: 'Open',
            showCancelButton: true,
            cancelButtonText: 'Dismiss',
            timer: 12000,
            timerProgressBar: true,
            background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#fff' : '#374151',
            customClass: { popup: 'rounded-xl', confirmButton: 'rounded-lg px-3 py-1.5 text-xs', cancelButton: 'rounded-lg px-3 py-1.5 text-xs' }
        }).then(function (result) {
            if (result.isConfirmed && item.action_url) window.location.href = item.action_url;
        });
    }

    async function pollReminders() {
        try {
            const response = await fetch(FEED_URL, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();

            if (typeof updateBadge === 'function') updateBadge(data.unread_count || 0);

            const incoming = (Array.isArray(data.notifications) ? data.notifications : []).filter(function (n) { return n && n.id; });
            if (!primed) {
                incoming.forEach(function (n) { seen.add(n.id); });
                primed = true;
                persistSeen();
            } else {
                incoming.forEach(function (n) {
                    if (!seen.has(n.id)) {
                        seen.add(n.id);
                        showToast(n);
                    }
                });
                persistSeen();
            }
        } catch (error) {
            console.warn('Appointment reminder feed unavailable:', error);
        }
    }

    setInterval(function () { if (!document.hidden) pollReminders(); }, POLL_MS);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) pollReminders(); });
    window.addEventListener('load', pollReminders);
})();
</script>
@endpush

@section('sidebar-nav-mobile')
    @foreach($navItems as $item)
        <a href="{{ route($item['r']) }}"
           class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold {{ $isActive($item['p']) ? 'bg-brand-50 dark:bg-brand-900/10 text-brand-600 dark:text-brand-400' : 'text-gray-600 dark:text-gray-400' }}">
            <span class="relative shrink-0 w-[18px] h-[18px] flex items-center justify-center">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['i'] }}"/>
                </svg>
            </span>
            <span>{{ $item['l'] }}</span>
        </a>
    @endforeach
@endsection