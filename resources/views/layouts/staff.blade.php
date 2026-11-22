@extends('layouts.master', [
    'roleLabel' => 'Staff',
    'userRole' => 'Staff',
    'settingsRoute' => 'profile.update',
])

@section('logo-icon')
    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
    </svg>
@endsection

@php
$isActive = fn($p) => request()->routeIs($p);

$navItems = [
    ['r'=>'staff.dashboard','l'=>'Dashboard','p'=>'staff.dashboard','i'=>'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
    ['r'=>'staff.appointments','l'=>'My Appointments','p'=>'staff.appointments','i'=>'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
    ['r'=>'staff.commissions','l'=>'My Commissions','p'=>'staff.commissions*','i'=>'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 13.5v-3a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 10.5v3z'],
    ['r'=>'staff.schedule','l'=>'My Schedule','p'=>'staff.schedule','i'=>'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
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