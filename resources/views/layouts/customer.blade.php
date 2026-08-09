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
    ['r'=>'booking.wizard','l'=>'Book Appointment','p'=>'booking.wizard','i'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
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
    <a href="{{ route('customer-dashboard') }}#history"
       data-label="My Appointments"
       class="nav-item text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800/50 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold">
        <span class="relative shrink-0 w-[18px] h-[18px] flex items-center justify-center">
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </span>
        <span class="nav-text whitespace-nowrap">My Appointments</span>
    </a>
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
    <a href="{{ route('customer-dashboard') }}#history"
       class="flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold text-gray-600 dark:text-gray-400">
        <span class="relative shrink-0 w-[18px] h-[18px] flex items-center justify-center">
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </span>
        <span>My Appointments</span>
    </a>
@endsection