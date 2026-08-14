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
    ['r'=>'receptionist.pending','l'=>'Pending Bookings','p'=>'receptionist.pending','i'=>'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z','badge'=>$pendingCount],
    ['r'=>'receptionist.sales','l'=>'Sales Report','p'=>'receptionist.sales','i'=>'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
    ['r'=>'receptionist.schedules','l'=>'Staff Schedules','p'=>'receptionist.schedules','i'=>'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
    ['r'=>'receptionist.quick-book','l'=>'Quick Book','p'=>'receptionist.quick-book','i'=>'M12 4.5v15m7.5-7.5h-15'],
];

if(auth()->user()->can_edit_landing ?? false) {
    $navItems[] = ['r'=>'admin.landing.editor','l'=>'Landing Page','p'=>'admin.landing.*','i'=>'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'];
}

$navItems[] = ['r'=>'receptionist.active','l'=>'Active Sessions','p'=>'receptionist.active','i'=>'M13 10V3L4 14h7v7l9-11h-7z','badge'=>$activeCount];
$navItems[] = ['r'=>'attendance.today','l'=>'Staff Attendance','p'=>'attendance.today','i'=>'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'];
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