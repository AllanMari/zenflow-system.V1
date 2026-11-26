@extends('layouts.master', [
    'roleLabel' => 'Reception',
    'userRole' => 'Receptionist',
    'settingsRoute' => 'profile.update',
    'extraHead' => '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>',
])

@section('logo-icon')
    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
    </svg>
@endsection

@php
$isActive = fn($p) => request()->routeIs($p);
/*
 * NOTE: Move these queries to a View Composer in a Service Provider
 * to keep views free of database logic and enable caching.
 * Example: View::composer('layouts.receptionist', ReceptionistNavComposer::class);
 */
$pendingCount = \App\Models\Appointment::pendingValid()->count();
$activeCount  = \App\Models\Appointment::where('status', 'confirmed')
                    ->whereDate('appointment_date', '>=', \Carbon\Carbon::today())
                    ->count();

$navItems = [
    ['r'=>'receptionist.dashboard','l'=>'Dashboard','p'=>'receptionist.dashboard','i'=>'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
    ['r'=>'receptionist.active','l'=>'Confirmed Bookings','p'=>'receptionist.active','i'=>'M13 10V3L4 14h7v7l9-11h-7z','badge'=>$activeCount],
    ['r'=>'receptionist.pending','l'=>'Pending Bookings','p'=>'receptionist.pending','i'=>'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z','badge'=>$pendingCount],
    [
        'r' => 'receptionist.appointment-invoice',
        'l' => 'Appointment Invoice',
        'p' => 'receptionist.appointment-invoice*',
        'i' => 'M9 12h6m-6 4h6m-6-8h6M5.25 3.75h13.5A2.25 2.25 0 0121 6v12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18V6a2.25 2.25 0 012.25-2.25z',
    ],
    [
        'r' => 'receptionist.customers.index',
        'l' => 'Clients',
        'p' => 'receptionist.customers.*',
        'i' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
    ],
    [
        'r' => 'receptionist.sales',
        'l' => 'Sales Report',
        'p' => 'receptionist.sales*',
        'i' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
    ],
    [
        'r' => 'receptionist.staff-utilization',
        'l' => 'Staff Utilization',
        'p' => 'receptionist.staff-utilization',
        'i' => 'M12 2a10 10 0 1 0 10 10',
    ],
    ['r'=>'receptionist.schedules','l'=>'Staff Schedules','p'=>'receptionist.schedules','i'=>'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
    ['r'=>'receptionist.quick-book','l'=>'Quick Book','p'=>'receptionist.quick-book','i'=>'M12 4.5v15m7.5-7.5h-15'],
];

if (auth()->user()->can_manage_schedules ?? false) {
    $navItems[] = [
        'r' => 'receptionist.shift-templates.index',
        'l' => 'Shift Templates',
        'p' => 'receptionist.shift-templates.*',
        'i' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
    ];
}

if(auth()->user()->can_edit_landing ?? false) {
    $navItems[] = ['r'=>'admin.landing.editor','l'=>'Landing Page','p'=>'admin.landing.*','i'=>'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'];
}
if (auth()->user()->can_manage_business_hours ?? false) {
    $navItems[] = [
        'r' => 'business-hours.index',
        'l' => 'Business Hours & Holidays',
        'p' => 'business-hours.*',
        'i' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
    ];
}
if (auth()->user()->can_view_room_tracking) {
    $navItems[] = [
        'r' => 'receptionist.room-tracking',
        'l' => 'Room Tracking',
        'p' => 'receptionist.room-tracking',
        'i' => 'M3 7.5A2.5 2.5 0 015.5 5h13A2.5 2.5 0 0121 7.5v9a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 013 16.5v-9zM8 5v14m8-14v14',
    ];
}
$navItems[] = ['r'=>'attendance.today','l'=>'Staff Attendance','p'=>'attendance.today','i'=>'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'];
$navItems[] = [
    'r' => 'admin.data-import.index',
    'l' => 'Data Import',
    'p' => 'admin.data-import.*',
    'i' => 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5',
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
                @if(($item['badge'] ?? 0) > 0)
                    <span class="nav-badge-icon absolute -top-1 -right-1 bg-red-500 text-white text-[9px] font-bold px-1 min-w-[14px] h-[14px] rounded-full flex items-center justify-center leading-none border-2 border-white dark:border-[#0f172a]">{{ $item['badge'] }}</span>
                @endif
            </span>
            <span class="nav-text whitespace-nowrap">{{ $item['l'] }}</span>
            @if(($item['badge'] ?? 0) > 0)
                <span class="nav-text nav-badge-standalone ml-auto bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center">{{ $item['badge'] }}</span>
            @endif
        </a>
    @endforeach
@endsection

@push('scripts')
<script>
(function () {
    const FEED_URL = @js(route('api.notifications.attendance-feed'));
    const POLL_MS = 10000;
    const STORAGE_KEY = 'zenflow.attendance.state';
    const LEGACY_KEY = 'zenflow.attendance.seen';
    const panelList = document.getElementById('attendanceFollowupList');
    const panelCount = document.getElementById('attendanceFollowupCount');

    function cleanIds(value) {
        if (!Array.isArray(value)) return [];
        return Array.from(new Set(value.filter(function (id) { return typeof id === 'string' && id.length > 0; })));
    }

    function legacyShownIds() {
        try {
            const raw = sessionStorage.getItem(LEGACY_KEY);
            if (!raw) return [];
            const parsed = JSON.parse(raw);
            // The legacy key stored a plain JSON array of notification ids.
            return Array.isArray(parsed) ? cleanIds(parsed) : [];
        } catch (error) {
            return [];
        }
    }

    function readState() {
        let shown = [];
        let queued = [];

        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            if (raw) {
                const data = JSON.parse(raw) || {};
                shown = cleanIds(data.shown);
                queued = cleanIds(data.queued);
            }
        } catch (error) {
            shown = [];
            queued = [];
        }

        // Import ids that were already displayed by the previous implementation
        // so they are not popped again after this change.
        const legacy = legacyShownIds();
        if (legacy.length) {
            shown = Array.from(new Set(shown.concat(legacy)));
        }

        return { shown: shown, queued: queued };
    }

    const initial = readState();
    const shown = new Set(initial.shown);
    const queuedIds = new Set(initial.queued);
    let queue = [];
    let active = false;

    function persist() {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
                shown: Array.from(shown).slice(-300),
                queued: Array.from(queuedIds).slice(-100),
            }));
        } catch (error) { /* in-memory dedupe still works */ }
    }

    function escapeHtml(value) {
        const node = document.createElement('div');
        node.textContent = value == null ? '' : String(value);
        return node.innerHTML;
    }

    function stagePriority(item) {
        const stage = item && item.stage;
        if (stage === 15 || stage === '15' || stage === '15 minutes') return 3;
        if (stage === 30 || stage === '30' || stage === '30 minutes') return 2;
        if (stage === 60 || stage === '60' || stage === '60 minutes') return 1;
        return 0;
    }

    function sortQueue() {
        queue.sort(function (a, b) {
            return stagePriority(b) - stagePriority(a)
                || String(a.appointment_time || '99:99').localeCompare(String(b.appointment_time || '99:99'))
                || String(a.id).localeCompare(String(b.id));
        });
    }

    function showNext() {
        if (active || !queue.length || typeof Swal === 'undefined') return;
        if (typeof Swal.isVisible === 'function' && Swal.isVisible()) {
            setTimeout(showNext, 1500);
            return;
        }

        sortQueue();
        const item = queue.shift();
        queuedIds.delete(item.id);
        persist();

        active = true;
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'warning',
            title: escapeHtml(item.title || 'Attendance reminder'),
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
            active = false;
            if (result.isConfirmed && item.action_url) {
                window.location.href = item.action_url;
                return;
            }
            setTimeout(showNext, 250);
        });

        // Mark as displayed only once the popup has actually been presented.
        shown.add(item.id);
        persist();
    }

    function renderFollowupList(followups) {
        if (!panelList) return;
        panelList.innerHTML = '';
        if (panelCount) panelCount.textContent = String(followups.length);

        if (!followups.length) {
            const empty = document.createElement('p');
            empty.className = 'text-xs text-slate-400 dark:text-slate-500';
            empty.textContent = 'No outstanding attendance follow-ups.';
            panelList.appendChild(empty);
            return;
        }

        followups.forEach(function (item) {
            const card = document.createElement('a');
            card.href = item.action_url || '#';
            card.className = 'flex flex-col gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-800/60';

            const heading = document.createElement('p');
            heading.className = 'text-xs font-bold text-slate-800 dark:text-slate-100';
            heading.textContent = item.customer + ' · ' + (item.date_label || item.appointment_date || '') + ' · ' + item.time;

            const meta = document.createElement('p');
            meta.className = 'text-[11px] text-slate-500 dark:text-slate-400';
            meta.textContent = (item.stage || 'No reminder sent')
                + ' · ' + (item.attendance_label || 'Awaiting confirmation')
                + (item.needs_call ? ' · Call customer' : '');

            const ref = document.createElement('p');
            ref.className = 'text-[10px] font-mono text-slate-400';
            ref.textContent = 'Appointment #' + item.appointment_id + ' · Open';

            card.appendChild(heading);
            card.appendChild(meta);
            card.appendChild(ref);
            panelList.appendChild(card);
        });
    }

    async function pollAttendance() {
        try {
            const response = await fetch(FEED_URL, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();

            if (typeof updateBadge === 'function') updateBadge(data.unread_count || 0);
            renderFollowupList(Array.isArray(data.followups) ? data.followups : []);

            const incoming = (Array.isArray(data.notifications) ? data.notifications : [])
                .filter(function (n) { return n && n.id; });
            const eligibleIds = new Set(incoming.map(function (n) { return String(n.id); }));

            // The unread feed is authoritative: drop queued items that were read,
            // resolved, or otherwise became ineligible since the last poll.
            queue = queue.filter(function (n) { return eligibleIds.has(String(n.id)) && !shown.has(String(n.id)); });
            Array.from(queuedIds).forEach(function (id) {
                if (!eligibleIds.has(id) || shown.has(id)) queuedIds.delete(id);
            });

            incoming.forEach(function (item) {
                const id = String(item.id);
                if (shown.has(id)) return;
                if (queue.some(function (queued) { return String(queued.id) === id; })) return;
                queue.push(item);
                queuedIds.add(id);
            });

            persist();
            sortQueue();
            showNext();
        } catch (error) {
            console.warn('Attendance feed unavailable:', error);
        }
    }

    setInterval(function () { if (!document.hidden) pollAttendance(); }, POLL_MS);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) pollAttendance(); });
    window.addEventListener('load', pollAttendance);
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
                @if(($item['badge'] ?? 0) > 0)
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] font-bold px-1 min-w-[14px] h-[14px] rounded-full flex items-center justify-center leading-none border-2 border-white dark:border-[#0f172a]">{{ $item['badge'] }}</span>
                @endif
            </span>
            <span>{{ $item['l'] }}</span>
            @if(($item['badge'] ?? 0) > 0)
                <span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center">{{ $item['badge'] }}</span>
            @endif
        </a>
    @endforeach
@endsection