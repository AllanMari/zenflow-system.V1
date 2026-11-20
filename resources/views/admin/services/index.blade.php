@extends('layouts.admin')

@section('title', 'Service Catalog')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Service Catalog</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage single treatments, package bundles, and service menu categories</p>
        </div>
        <div class="flex items-center gap-2">

            <a href="{{ route('admin.room-categories.index') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 transition flex items-center gap-1.5 shadow-2xs" title="Manage physical room categories">
                <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Room Categories ({{ $roomCategories->count() }})</span>
            </a>
        </div>
    </div>

    {{-- Tabs Container --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        {{-- Navigation Tabs --}}
        <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-700/50">
            <button onclick="switchTab('services')" id="tab-services" class="tab-btn flex-1 py-3.5 px-4 text-center font-semibold text-sm border-b-2 border-teal-600 text-teal-600 bg-white dark:bg-gray-800 dark:text-teal-400 transition-colors flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879a3 3 0 11-4.242-4.242L10.758 4.758a3 3 0 014.242 0L19 9"/></svg>
                <span>Single Services</span>
                <span class="text-xs bg-teal-50 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300 px-2 py-0.5 rounded-full font-bold border border-teal-200 dark:border-teal-700">{{ $services->where('is_package', false)->count() }}</span>
            </button>
            <button onclick="switchTab('packages')" id="tab-packages" class="tab-btn flex-1 py-3.5 px-4 text-center font-semibold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Package Bundles</span>
                <span class="text-xs bg-purple-50 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 px-2 py-0.5 rounded-full font-bold border border-purple-200 dark:border-purple-700">{{ $services->where('is_package', true)->count() }}</span>
            </button>
            <button onclick="switchTab('categories')" id="tab-categories" class="tab-btn flex-1 py-3.5 px-4 text-center font-semibold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <span>Menu Categories</span>
                <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full font-bold border border-gray-200 dark:border-gray-600">{{ $categories->count() }}</span>
            </button>
        </div>

        {{-- ==================== SERVICES TAB ==================== --}}
        <div id="panel-services" class="tab-panel p-6 space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div class="relative w-full max-w-md">
                    <input type="text" id="searchServices" oninput="filterServices(this.value)" placeholder="Search by name, category, or room..." 
                        class="w-full border rounded-xl p-2.5 pl-10 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="button" onclick="openServiceModal(false)" class="shrink-0 bg-teal-600 text-white px-4 py-2.5 rounded-xl hover:bg-teal-700 transition font-medium shadow-sm hover:shadow flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Add Service</span>
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b bg-gray-50/50 dark:bg-gray-700/50 dark:border-gray-700">
                            <th class="py-3 px-3">Service</th>
                            <th class="py-3 px-3">Menu Category</th>
                            <th class="py-3 px-3">Required Room Category</th>
                            <th class="py-3 px-3">Duration</th>
                            <th class="py-3 px-3">Price</th>
                            <th class="py-3 px-3">Deposit</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700" id="servicesTableBody">
                        @foreach($services->where('is_package', false) as $service)
                        <tr class="service-row group hover:bg-teal-50/30 dark:hover:bg-gray-700/50 transition" 
                            data-name="{{ strtolower($service->name) }}" 
                            data-category="{{ strtolower($service->category->name ?? '') }}"
                            data-room-cat="{{ strtolower($service->roomCategory->name ?? '') }}">
                            <td class="py-3 px-3">
                                <div class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <span>{{ $service->name }}</span>
                                    @if($service->code)
                                        <span class="text-[10px] font-mono text-gray-400">({{ $service->code }})</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    @if($service->participants_required > 1 || $service->therapists_required > 1)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-teal-700 bg-teal-50 dark:bg-teal-950/40 dark:text-teal-300 px-1.5 py-0.5 rounded border border-teal-200 dark:border-teal-800">
                                            {{ $service->participants_required }} clients · {{ $service->therapists_required }} therapists
                                        </span>
                                    @endif
                                    @if($service->description)
                                        <span class="text-xs text-gray-400 truncate max-w-[180px]" title="{{ $service->description }}">{{ Str::limit($service->description, 35) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $service->category->color ?? '#ccc' }}"></span>
                                    <span class="truncate max-w-[120px]">{{ $service->category->name }}</span>
                                </span>
                            </td>
                            <td class="py-3 px-3">
                                @if($service->requires_room && $service->roomCategory)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-teal-50 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300 border border-teal-200 dark:border-teal-700" title="{{ $service->roomCategory->description ?? '' }}">
                                        <svg class="w-3.5 h-3.5 text-teal-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        <span>{{ $service->roomCategory->name }}</span>
                                        <span class="text-[10px] opacity-75 font-mono">({{ $service->roomCategory->code }})</span>
                                        @if(!$service->roomCategory->is_active)
                                            <span class="text-[9px] font-bold text-amber-600 dark:text-amber-400 uppercase">[Inactive]</span>
                                        @endif
                                    </span>
                                @elseif($service->requires_room)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-700">
                                        Any compatible room
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">No room required</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-gray-600 dark:text-gray-400 font-medium">
                                @if($service->duration_minutes)
                                    {{ $service->duration_minutes }} min
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200">Draft (No duration)</span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                @if($service->discount_price)
                                    <span class="text-xs text-gray-400 line-through">₱{{ number_format($service->price, 0) }}</span>
                                    <span class="text-sm font-bold text-teal-600 dark:text-teal-400 block">₱{{ number_format($service->discount_price, 2) }}</span>
                                @else
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">₱{{ number_format($service->price, 2) }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-xs text-gray-500 dark:text-gray-400">
                                @if($service->deposit_percentage_min || $service->deposit_percentage_max)
                                    {{ $service->deposit_percentage_min ?? 0 }}% - {{ $service->deposit_percentage_max ?? 0 }}%
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                @if($service->is_active)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300">Active</span>
                                @elseif(is_null($service->duration_minutes))
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">Draft</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">Inactive</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right">
                                <div class="flex justify-end gap-1 opacity-80 group-hover:opacity-100 transition">
                                    <button onclick="editService({{ $service->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition" title="Edit Service">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    @if(is_null($service->duration_minutes))
                                        <button onclick="alert('Cannot activate this service yet: please edit the service to provide a confirmed duration first.')" class="p-1.5 text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition" title="Requires Duration">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </button>
                                    @else
                                        <button onclick="toggleStatus({{ $service->id }}, '{{ addslashes($service->name) }}', {{ $service->is_active ? 0 : 1 }})" class="p-1.5 {{ $service->is_active ? 'text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20' : 'text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20' }} rounded-lg transition" title="{{ $service->is_active ? 'Deactivate' : 'Activate' }}">
                                            @if($service->is_active)
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @endif
                                        </button>
                                    @endif
                                    <button onclick="confirmDelete({{ $service->id }}, '{{ addslashes($service->name) }}')" class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition" title="Delete Service">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div id="noServicesResults" class="hidden text-center py-12 text-gray-400 dark:text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p>No services found matching your search.</p>
                </div>
                @if($services->where('is_package', false)->isEmpty())
                    <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <p>No single services created yet.</p>
                        <button onclick="openServiceModal(false)" class="text-teal-600 hover:underline text-sm mt-1">Create your first service &rarr;</button>
                    </div>
                @endif
            </div>
        </div>

        {{-- ==================== PACKAGES TAB ==================== --}}
        <div id="panel-packages" class="tab-panel p-6 hidden space-y-5">
            @php $allServices = $services->keyBy('id'); @endphp

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div class="relative w-full max-w-md">
                    <input type="text" id="searchPackages" oninput="filterPackages(this.value)" placeholder="Search packages by name or inclusion..." 
                        class="w-full border rounded-xl p-2.5 pl-10 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-purple-500 outline-none">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="button" onclick="openServiceModal(true)" class="shrink-0 bg-purple-600 text-white px-4 py-2.5 rounded-xl hover:bg-purple-700 transition font-medium shadow-sm hover:shadow flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Add Package</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5" id="packagesGrid">
                @foreach($services->where('is_package', true) as $package)
                @php
                    $includedIds = $package->included_services ?? [];
                    if (is_string($includedIds)) $includedIds = json_decode($includedIds, true) ?? [];
                    $included = $allServices->only($includedIds);
                    $totalDuration = $included->sum('duration_minutes');
                @endphp
                <div class="package-card bg-white dark:bg-gray-700/80 rounded-2xl border-2 border-purple-100 dark:border-purple-900/30 p-5 hover:shadow-lg transition flex flex-col justify-between group" data-name="{{ strtolower($package->name) }}">
                    <div>
                        {{-- Top tags & Actions --}}
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800">Package Bundle</span>
                                @if($package->category)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">
                                        <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $package->category->color ?? '#ccc' }}"></span>
                                        {{ $package->category->name }}
                                    </span>
                                @endif
                                @if(!$package->is_active)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-100 text-red-700 dark:bg-red-900/60 dark:text-red-300">Inactive</span>
                                @endif
                            </div>
                            <div class="flex gap-1 opacity-90 group-hover:opacity-100 transition">
                                <button onclick="editService({{ $package->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition" title="Edit Package">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <button onclick="confirmDelete({{ $package->id }}, '{{ addslashes($package->name) }}')" class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition" title="Delete Package">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Title & Room Category Badge --}}
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg leading-snug mb-2">{{ $package->name }}</h3>

                        {{-- Facility and Room Tag --}}
                        <div class="flex flex-wrap items-center gap-1.5 mb-3 text-xs">
                            @if($package->requires_room && $package->roomCategory)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-teal-50 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300 border border-teal-200 dark:border-teal-700" title="{{ $package->roomCategory->description ?? '' }}">
                                    <svg class="w-3.5 h-3.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <span>{{ $package->roomCategory->name }}</span>
                                    <span class="text-[10px] opacity-75 font-mono">({{ $package->roomCategory->code }})</span>
                                </span>
                            @elseif($package->requires_room)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-700">
                                    Any compatible room
                                </span>
                            @else
                                <span class="text-xs text-gray-400 italic">No room required</span>
                            @endif

                            @if($package->participants_required > 1 || $package->therapists_required > 1)
                                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-purple-700 bg-purple-50 dark:bg-purple-950/40 dark:text-purple-300 px-2 py-0.5 rounded border border-purple-200 dark:border-purple-800">
                                    {{ $package->participants_required }} Guests · {{ $package->therapists_required }} Staff
                                </span>
                            @endif
                        </div>

                        {{-- Inclusions List --}}
                        <div class="space-y-1.5 mb-4">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400 block mb-1">Included Services ({{ $included->count() }}):</span>
                            @forelse($included as $inc)
                            <div class="flex items-center justify-between text-xs py-1.5 px-3 bg-purple-50/60 dark:bg-purple-900/20 rounded-lg border border-purple-100 dark:border-purple-900/30">
                                <span class="text-gray-800 dark:text-gray-200 font-medium truncate max-w-[180px]">{{ $inc->name }}</span>
                                <span class="text-gray-500 dark:text-gray-400 shrink-0">{{ $inc->duration_minutes }}m · ₱{{ number_format($inc->price, 0) }}</span>
                            </div>
                            @empty
                            <p class="text-xs text-gray-400 italic py-2">No fixed services attached to this bundle.</p>
                            @endforelse
                        </div>

                        {{-- Choice Groups --}}
                        <div id="package-choices-display-{{ $package->id }}"
                             data-package-id="{{ $package->id }}"
                             data-package-name="{{ $package->name }}"
                             data-choice-enabled="{{ ($package->choiceGroups && $package->choiceGroups->isNotEmpty()) ? '1' : '0' }}"
                             data-choice-options="{{ json_encode($package->choiceGroups->first()?->options->pluck('service_id')->toArray() ?? []) }}"
                             data-choice-label="{{ $package->choiceGroups->first()?->label ?? 'Choose Your Massage' }}"
                             data-choice-required="{{ ($package->choiceGroups->first()?->is_required ?? true) ? '1' : '0' }}">
                            @if($package->choiceGroups && $package->choiceGroups->isNotEmpty())
                            <div class="space-y-1.5 mb-4">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300 block mb-1">Choice Groups ({{ $package->choiceGroups->count() }}):</span>
                                @foreach($package->choiceGroups as $cg)
                                <div class="rounded-lg border border-purple-200 dark:border-purple-800 bg-purple-50/40 dark:bg-purple-950/30 p-2 text-xs">
                                    <div class="flex items-center justify-between font-semibold text-purple-800 dark:text-purple-200 mb-1">
                                        <span>{{ $cg->label ?? $cg->name }}</span>
                                        <span class="text-[10px] {{ $cg->is_required ? 'text-red-500 font-bold' : 'text-gray-400' }}">{{ $cg->is_required ? 'Required' : 'Optional' }}</span>
                                    </div>
                                    <div class="text-[11px] text-gray-600 dark:text-gray-300">
                                        {{ $cg->options->map(fn($o) => $o->name ?? $o->service?->name)->filter()->join(' OR ') }}
                                    </div>
                                </div>
                                @endforeach
                                <button type="button" onclick="openSimpleChoiceModal({{ $package->id }})" class="mt-2 w-full py-1.5 px-3 rounded-lg border border-purple-200 dark:border-purple-800 bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-purple-700 dark:text-purple-300 text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                                    ⚙️ Configure Choice Groups
                                </button>
                            </div>
                            @else
                            <div class="mb-4">
                                <button type="button" onclick="openSimpleChoiceModal({{ $package->id }})" class="w-full py-1.5 px-3 rounded-lg border border-dashed border-purple-200 dark:border-purple-800 hover:border-purple-400 hover:bg-purple-50/50 text-purple-600 dark:text-purple-400 text-xs font-medium flex items-center justify-center gap-1.5 transition">
                                    + Add "Choose Your Massage" Option
                                </button>
                            </div>
                            @endif
                        </div>

                        @if($package->description)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 italic">
                            {{ $package->description }}
                        </p>
                        @endif
                    </div>

                    {{-- Footer & Pricing --}}
                    <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-600">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $package->duration_minutes }} min total
                            @if($totalDuration > 0 && $totalDuration != $package->duration_minutes)
                                <span class="text-[10px] text-gray-400">({{ $totalDuration }}m combined)</span>
                            @endif
                        </div>
                        <div class="text-right">
                            @if($package->discount_price)
                                <span class="text-xs text-gray-400 line-through mr-1">₱{{ number_format($package->price, 0) }}</span>
                                <span class="text-lg font-bold text-purple-600 dark:text-purple-400">₱{{ number_format($package->discount_price, 2) }}</span>
                            @else
                                <span class="text-lg font-bold text-purple-600 dark:text-purple-400">₱{{ number_format($package->price, 2) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- Add Package Placeholder Card --}}
                <button type="button" onclick="openServiceModal(true)" class="border-2 border-dashed border-purple-200 dark:border-purple-900/40 rounded-2xl p-6 flex flex-col items-center justify-center text-purple-500 hover:text-purple-700 hover:border-purple-400 hover:bg-purple-50/50 dark:hover:bg-purple-900/10 transition min-h-[220px] group">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400 mb-3 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <span class="font-bold text-base">Add New Package</span>
                    <span class="text-xs text-gray-400 mt-1">Combine treatments into a special bundle</span>
                </button>
            </div>
        </div>

        {{-- ==================== CATEGORIES TAB ==================== --}}
        <div id="panel-categories" class="tab-panel p-6 hidden space-y-5">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Spa Menu Categories</h2>
                    <p class="text-xs text-gray-500">Service categories organize your customer-facing menu and sales reports</p>
                </div>
                <button type="button" onclick="openCategoryCreateModal()" class="shrink-0 bg-teal-600 text-white px-4 py-2.5 rounded-xl hover:bg-teal-700 transition font-medium shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Add Menu Category</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($categories as $cat)
                @php
                    $catServices = $services->where('category_id', $cat->id);
                    $catPackages = $catServices->where('is_package', true);
                    $catSingle = $catServices->where('is_package', false);
                @endphp
                <div class="bg-white dark:bg-gray-700/80 rounded-2xl border border-gray-100 dark:border-gray-600 p-5 hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-sm shrink-0" style="background-color: {{ $cat->color ?? '#0d9488' }}">
                                {{ strtoupper(substr($cat->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-900 dark:text-white truncate">{{ $cat->name }}</h3>
                                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <span>{{ $catSingle->count() }} services</span>
                                    @if($catPackages->count() > 0)
                                        <span class="text-purple-600 dark:text-purple-400 font-semibold">· {{ $catPackages->count() }} pkg</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex gap-1 shrink-0">
                                <button onclick="openEditCategoryModal({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->color }}')" class="p-1.5 text-gray-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20 rounded-lg transition" title="Edit Category">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                @if($cat->is_active)
                                    <button onclick="confirmDeactivateCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')" class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition" title="Deactivate">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                    </button>
                                @else
                                    <button onclick="confirmActivateCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')" class="p-1.5 text-gray-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20 rounded-lg transition" title="Activate">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                @endif
                                <button onclick="confirmDeleteCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', {{ $cat->services_count ?? 0 }})" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1">
                            @forelse($catServices->take(5) as $svc)
                            <div class="flex items-center justify-between text-xs py-1 px-2.5 bg-gray-50 dark:bg-gray-600/30 rounded-lg">
                                <span class="truncate {{ !$svc->is_active ? 'line-through opacity-50' : 'text-gray-700 dark:text-gray-300' }}">{{ $svc->name }}</span>
                                <span class="text-gray-400 font-medium shrink-0 ml-2">₱{{ number_format($svc->price, 0) }}</span>
                            </div>
                            @empty
                            <p class="text-xs text-gray-400 italic py-2 text-center">No treatments assigned.</p>
                            @endforelse
                            @if($catServices->count() > 5)
                            <p class="text-[11px] text-teal-600 dark:text-teal-400 text-center pt-1 font-semibold">+{{ $catServices->count() - 5 }} more</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach

                <button onclick="openCategoryCreateModal()" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl p-6 flex flex-col items-center justify-center text-gray-500 hover:text-teal-600 hover:border-teal-500 hover:bg-teal-50/50 dark:hover:bg-teal-900/10 transition min-h-[180px]">
                    <svg class="w-7 h-7 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span class="font-bold text-sm">Create Menu Category</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ==================== REDESIGNED REORGANIZED SERVICE / PACKAGE MODAL ==================== --}}
<div id="serviceModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-4xl mx-auto max-h-[92vh] flex flex-col border border-gray-100 dark:border-gray-700 overflow-hidden">

        {{-- Dynamic Header --}}
        <div id="modalHeaderBg" class="p-5 border-b dark:border-gray-700 flex justify-between items-center shrink-0 bg-gradient-to-r from-teal-50 to-white dark:from-gray-700 dark:to-gray-800 transition-colors">
            <div class="flex items-center gap-3">
                <div id="modalIcon" class="w-11 h-11 rounded-2xl bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 transition-colors shadow-2xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                </div>
                <div>
                    <h2 id="modalTitle" class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Add Service</h2>
                    <p id="modalSubtitle" class="text-xs text-gray-500 dark:text-gray-400">Configure treatment specifications, facility requirements, and pricing</p>
                </div>
            </div>
            <button type="button" onclick="closeServiceModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="overflow-y-auto p-6 flex-1 space-y-6">
            <form id="serviceForm" action="{{ route('admin.services.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div id="methodField"></div>

                {{-- Hidden is_package value: NO user-facing checkbox! Handled automatically by the active tab --}}
                <input type="hidden" name="is_package" id="serviceIsPackage" value="0">

                @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-2xl text-sm mb-4">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                {{-- ================= PACKAGE INCLUSIONS (Only visible in Package mode) ================= --}}
                <div id="packageServicesWrapper" class="hidden bg-purple-50/50 dark:bg-purple-950/20 rounded-2xl p-5 border border-purple-200 dark:border-purple-900/50 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-purple-900 dark:text-purple-300 flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <span>Package Inclusions</span>
                            </h3>
                            <p class="text-xs text-purple-700/70 dark:text-purple-300/70">Select the treatments bundled inside this package</p>
                        </div>
                        <span id="selectedInclusionsCount" class="text-xs font-bold text-purple-700 bg-purple-100 dark:bg-purple-900/60 dark:text-purple-300 px-3 py-1 rounded-full border border-purple-200 dark:border-purple-800">
                            0 selected
                        </span>
                    </div>

                    {{-- Selected items chips --}}
                    <div id="selectedInclusionsChips" class="flex flex-wrap gap-1.5 p-3 rounded-xl border border-purple-200 bg-white dark:border-purple-900 dark:bg-gray-800 min-h-[46px] items-center">
                        <span id="noSelectedInclusionsPlaceholder" class="text-xs text-gray-400 italic">No services selected yet. Search and check services below to include them.</span>
                    </div>

                    {{-- Search Input --}}
                    <div class="relative">
                        <input type="text" id="packageServiceSearch" placeholder="Search services to include..." class="w-full border border-purple-200 dark:border-purple-800 rounded-xl py-2.5 pl-9 pr-9 text-xs dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 outline-none" oninput="filterPackageServices()">
                        <svg class="w-4 h-4 text-purple-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <button type="button" id="clearPackageServiceSearch" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 hidden" onclick="clearPackageSearch()">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Scrollable List --}}
                    <div id="packageServicesList" class="border border-purple-200 dark:border-purple-900 rounded-xl bg-white dark:bg-gray-800 max-h-52 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($singleServices as $s)
                            <label class="package-service-item flex items-center p-3 hover:bg-purple-50/50 dark:hover:bg-purple-900/20 cursor-pointer transition"
                                   data-service-id="{{ $s->id }}"
                                   data-service-name="{{ strtolower($s->name) }}"
                                   data-is-package="{{ $s->is_package ? '1' : '0' }}">
                                <input type="checkbox" name="included_services[]" value="{{ $s->id }}"
                                       class="package-checkbox mr-3 h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                                       data-id="{{ $s->id }}"
                                       data-name="{{ $s->name }}"
                                       data-duration="{{ $s->duration_minutes }}"
                                       data-price="{{ $s->price }}"
                                       data-is-package="{{ $s->is_package ? '1' : '0' }}"
                                       onchange="syncSelectedInclusions()">
                                <div class="flex-1 flex items-center justify-between gap-2 min-w-0">
                                    <div class="min-w-0">
                                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 block truncate">{{ $s->name }}</span>
                                        <span class="text-[11px] text-gray-400">{{ $s->duration_minutes }}m · ₱{{ number_format($s->price, 0) }}</span>
                                    </div>
                                    <span class="shrink-0 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $s->is_package ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300' }}">
                                        {{ $s->is_package ? 'Package' : 'Single' }}
                                    </span>
                                </div>
                            </label>
                        @endforeach
                        <div id="noMatchingPackageServices" class="p-4 text-center text-xs text-gray-400 italic hidden">
                            No services found matching your search.
                        </div>
                    </div>
                </div>

                {{-- ================= PACKAGE CHOICE GROUPS (e.g. Choose Your Massage) ================= --}}
                <div id="packageChoiceGroupsWrapper" class="hidden bg-purple-50/70 dark:bg-purple-950/30 rounded-2xl p-5 border border-purple-200 dark:border-purple-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-purple-900 dark:text-purple-300 flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                <span>Choice Groups (e.g. "Choose Your Massage")</span>
                            </h3>
                            <p class="text-xs text-purple-700/70 dark:text-purple-300/70">Configure treatment choices (e.g. Alexandria vs Swedish) where clients pick an option upon booking.</p>
                        </div>
                        <button type="button" id="btnManageChoicesFromModal" onclick="openSimpleChoiceModal(currentEditingServiceId)" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-1.5 shrink-0">
                            <span>⚙️ Configure Choices</span>
                        </button>
                    </div>
                </div>

                {{-- Two Column Grid --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    {{-- Left Column: Basic Info & Requirements --}}
                    <div class="space-y-5">
                        {{-- Basic Info Card --}}
                        <div class="bg-gray-50/80 dark:bg-gray-700/40 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-2">
                                <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>General Information</span>
                            </h3>

                            <div>
                                <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Name</label>
                                <input type="text" name="name" id="serviceName" class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g. Signature Aromatherapy Massage" required>
                            </div>

                            <div>
                                <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Menu Category (Spa Section)</label>
                                <select name="category_id" id="serviceCategory" class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none" required>
                                    <option value="">Select a menu category...</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-gray-400 mt-1">Organizes where this appears on the public booking menu.</p>
                            </div>
                        </div>

                        {{-- Facility & Staffing Card --}}
                        <div class="bg-teal-50/40 dark:bg-gray-700/40 rounded-2xl p-5 border border-teal-100/70 dark:border-gray-700 space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-teal-800 dark:text-teal-400 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <span>Facility & Room Requirements</span>
                            </h3>

                            <label class="flex items-center gap-2.5 cursor-pointer bg-white dark:bg-gray-800 p-3 rounded-xl border border-teal-200 dark:border-gray-600">
                                <input type="checkbox" name="requires_room" id="serviceRequiresRoom" value="1" checked class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded" onchange="toggleRoomRequirement()">
                                <div>
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block">Requires Physical Room Assignment</span>
                                    <span class="text-[11px] text-gray-400">If unchecked, appointments can be booked without reserving a room.</span>
                                </div>
                            </label>

                            <div id="roomCategorySelectWrapper" class="space-y-1.5 transition-opacity">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Required Room Category</label>
                                <select name="room_category_id" id="serviceRoomCategory" class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none">
                                    <option value="">Any compatible room / None specified</option>
                                    @foreach($roomCategories as $rc)
                                        <option value="{{ $rc->id }}" data-is-active="{{ $rc->is_active ? '1' : '0' }}">
                                            {{ $rc->name }} ({{ $rc->code }}){{ $rc->is_active ? '' : ' — [Inactive]' }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-gray-400">Only rooms configured with this physical category will be offered for this treatment.</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-teal-100 dark:border-gray-600">
                                <div>
                                    <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Clients / Guests</label>
                                    <input type="number" name="participants_required" id="serviceParticipants" value="1" min="1" class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none" required>
                                    <span class="text-[10px] text-gray-400">Participants required</span>
                                </div>
                                <div>
                                    <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Staff Needed</label>
                                    <input type="number" name="therapists_required" id="serviceTherapists" value="1" min="1" class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none" required>
                                    <span class="text-[10px] text-gray-400">Therapists assigned</span>
                                </div>
                            </div>
                        </div>

                        {{-- Image Upload Card --}}
                        <div class="bg-gray-50/80 dark:bg-gray-700/40 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Image</span>
                            </h3>
                            <input type="file" name="image" id="serviceImage" accept="image/*" class="w-full text-xs text-gray-600 dark:text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 dark:file:bg-teal-900 dark:file:text-teal-300 transition cursor-pointer">
                            <div id="currentImagePreview" class="hidden">
                                <div class="flex items-center gap-3 p-3 bg-white dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600">
                                    <img id="currentImageImg" src="" class="h-14 w-14 rounded-lg object-cover border dark:border-gray-600">
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Current image uploaded</p>
                                        <label class="flex items-center gap-1.5 text-xs text-red-500 cursor-pointer hover:text-red-600 transition">
                                            <input type="checkbox" name="remove_image" id="serviceRemoveImage" value="1" class="rounded border-gray-300 text-red-500 focus:ring-red-500">
                                            <span>Remove image</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column: Pricing, Duration, Deposit, Descriptions, Settings --}}
                    <div class="space-y-5">
                        {{-- Pricing & Duration Card --}}
                        <div class="bg-gray-50/80 dark:bg-gray-700/40 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-2">
                                <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Pricing & Duration</span>
                            </h3>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Duration</label>
                                    <div class="relative">
                                        <input type="number" name="duration_minutes" id="serviceDuration" value="60" min="15" max="480" class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none pr-10" required>
                                        <span class="absolute right-3 top-2.5 text-gray-400 text-xs">min</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Base Price</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2.5 text-gray-400 text-xs font-bold">₱</span>
                                        <input type="number" name="price" id="servicePrice" min="0" step="0.01" class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 pl-7 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none" placeholder="0.00" required oninput="updateDiscountPreview()">
                                    </div>
                                </div>
                            </div>

                            {{-- Discount --}}
                            <div class="border-t border-gray-200 dark:border-gray-600 pt-3">
                                <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Discount Promotion</label>
                                <div class="flex gap-3 items-start">
                                    <div class="relative w-28 shrink-0">
                                        <input type="number" name="discount_percent" id="serviceDiscountPercent" min="0" max="100" value="0" class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-2 text-sm dark:bg-gray-700 dark:text-white pr-8 focus:ring-2 focus:ring-teal-500 outline-none" oninput="updateDiscountPreview()">
                                        <span class="absolute right-2.5 top-2 text-gray-400 text-xs">%</span>
                                    </div>
                                    <div id="discountPreview" class="hidden flex-1 bg-teal-50 dark:bg-teal-900/30 rounded-xl px-3 py-2 border border-teal-200 dark:border-teal-800">
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400">Discounted final price:</div>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-base font-bold text-teal-700 dark:text-teal-300">₱<span id="finalPrice">0.00</span></span>
                                            <span class="text-xs text-gray-400">(Save ₱<span id="saveAmount">0.00</span>)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="discount_price" id="discountPriceValue">
                        </div>

                        {{-- Deposit Estimate Card --}}
                        <div class="bg-amber-50/60 dark:bg-amber-950/20 rounded-2xl p-5 border border-amber-200/70 dark:border-amber-900/50 space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <span>Deposit Estimate</span>
                            </h3>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1 block">Min %</label>
                                    <div class="relative">
                                        <input type="number" name="deposit_percentage_min" id="serviceDepositMin" min="0" max="100" value="0" class="w-full border border-amber-200 dark:border-amber-800 rounded-xl p-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-amber-500 outline-none pr-8" oninput="updateDepositPreview()">
                                        <span class="absolute right-2.5 top-2 text-gray-400 text-xs">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1 block">Max %</label>
                                    <div class="relative">
                                        <input type="number" name="deposit_percentage_max" id="serviceDepositMax" min="0" max="100" value="0" class="w-full border border-amber-200 dark:border-amber-800 rounded-xl p-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-amber-500 outline-none pr-8" oninput="updateDepositPreview()">
                                        <span class="absolute right-2.5 top-2 text-gray-400 text-xs">%</span>
                                    </div>
                                </div>
                            </div>
                            <div id="depositPreview" class="hidden bg-white dark:bg-gray-800 rounded-xl p-3 border border-amber-200 dark:border-amber-800 text-xs">
                                <p class="text-gray-500 dark:text-gray-400">Estimated deposit required on booking:</p>
                                <p class="font-bold text-amber-700 dark:text-amber-400 mt-0.5"><span id="depositRangeText">0% - 0%</span> (₱<span id="depositPesoMin">0</span> - ₱<span id="depositPesoMax">0</span>)</p>
                            </div>
                        </div>

                        {{-- Descriptions Card --}}
                        <div class="bg-gray-50/80 dark:bg-gray-700/40 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                                <span>Descriptions</span>
                            </h3>
                            <div>
                                <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Customer Description</label>
                                <textarea name="description" id="serviceDescription" rows="2" class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 text-xs dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none resize-none" placeholder="Detailed benefits and experience for clients..."></textarea>
                            </div>
                            <div>
                                <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Landing Promo Snippet</label>
                                <textarea name="landing_description" id="serviceLandingDescription" rows="1" class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 text-xs dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none resize-none" placeholder="Short tagline for homepage cards (optional)"></textarea>
                            </div>
                        </div>

                        {{-- Publishing & Visibility Card --}}
                        <div class="bg-gray-50/80 dark:bg-gray-700/40 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="show_on_landing" id="serviceShowOnLanding" value="1" checked class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Show on Landing Page</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" id="serviceIsActive" value="1" checked class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Active & Bookable</span>
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Footer Actions --}}
        <div class="p-5 border-t dark:border-gray-700 flex justify-end gap-3 shrink-0 bg-white dark:bg-gray-800">
            <button type="button" onclick="closeServiceModal()" class="px-5 py-2.5 border border-gray-200 rounded-xl transition hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white font-medium text-sm">Cancel</button>
            <button type="submit" form="serviceForm" id="saveServiceBtn" class="px-6 py-2.5 bg-teal-600 text-white rounded-xl hover:bg-teal-700 font-semibold text-sm shadow-md transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg id="saveServiceIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span id="saveServiceText">Save Service</span>
            </button>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div id="deleteModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 w-full max-w-sm text-center shadow-2xl border-t-4 border-red-500">
        <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h2 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Delete Item?</h2>
        <p class="text-gray-600 dark:text-gray-400 text-xs mb-1">Permanently remove <span id="deleteServiceName" class="font-bold text-gray-900 dark:text-white"></span>?</p>
        <p class="text-red-500 text-xs mb-5 font-medium">This action cannot be undone.</p>
        <form id="deleteForm" action="" method="POST" class="flex justify-center gap-2">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeDeleteModal()" class="px-5 py-2.5 border rounded-xl text-xs font-semibold hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white">Cancel</button>
            <button type="submit" class="px-5 py-2.5 bg-red-600 text-white rounded-xl text-xs font-bold hover:bg-red-700 shadow-sm transition">Yes, Delete</button>
        </form>
    </div>
</div>

{{-- Hidden Data for Edit Populating --}}
<div id="servicesData" class="hidden">
    @foreach($services as $service)
    @php
        $incServices = $service->included_services;
        if (is_string($incServices)) $incServices = json_decode($incServices, true) ?? [];
    @endphp
    <div data-id="{{ $service->id }}"
         data-name="{{ $service->name }}"
         data-category="{{ $service->category_id }}"
         data-duration="{{ $service->duration_minutes }}"
         data-price="{{ $service->price }}"
         data-discount="{{ $service->discount_price }}"
         data-description="{{ $service->description }}"
         data-landing-description="{{ $service->landing_description ?? '' }}"
         data-show-on-landing="{{ $service->show_on_landing ? '1' : '0' }}"
         data-is-package="{{ $service->is_package ? '1' : '0' }}"
         data-included="{{ json_encode($incServices) }}"
         data-is-active="{{ $service->is_active ? '1' : '0' }}"
         data-image="{{ $service->image_url ?? '' }}"
         data-deposit-min="{{ $service->deposit_percentage_min ?? 0 }}"
         data-deposit-max="{{ $service->deposit_percentage_max ?? 0 }}"
         data-participants="{{ $service->participants_required ?? 1 }}"
         data-therapists="{{ $service->therapists_required ?? 1 }}"
         data-requires-room="{{ $service->requires_room ? '1' : '0' }}"
         data-room-category="{{ $service->room_category_id ?? '' }}">
    </div>
    @endforeach
</div>

{{-- Category Edit Modal --}}
<div id="categoryEditModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[60] p-4">
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl">
        <h2 class="text-lg font-bold mb-4 text-teal-600 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            <span>Edit Menu Category</span>
        </h2>
        <form id="editCategoryForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Category Name</label>
                <input type="text" name="name" id="editCatName" required class="w-full border rounded-xl p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Theme Color</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="color" id="editCatColor" class="w-14 h-10 rounded-xl cursor-pointer border border-gray-200 dark:border-gray-600 bg-transparent">
                    <span class="text-xs text-gray-400">Used on schedule calendar</span>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeCategoryEditModal()" class="px-4 py-2 text-xs font-semibold text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-xl text-xs font-bold hover:bg-teal-700 transition">Update</button>
            </div>
        </form>
    </div>
</div>

{{-- Category Deactivate Modal --}}
<div id="categoryDeactivateModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[60] p-4">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl max-w-sm w-full text-center border-t-4 border-amber-500 shadow-xl">
        <h2 class="text-lg font-bold mb-2 text-amber-600">Deactivate Category?</h2>
        <p class="mb-5 dark:text-gray-300 text-xs">Hide "<span id="deactivateCatName" class="font-bold"></span>" and its treatments from the public booking page?</p>
        <form id="deactivateCategoryForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_active" value="0">
            <div class="flex gap-2">
                <button type="button" onclick="closeCategoryDeactivateModal()" class="flex-1 border p-2 rounded-xl text-xs font-semibold dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition">Cancel</button>
                <button type="submit" class="flex-1 bg-amber-500 text-white p-2 rounded-xl text-xs font-bold hover:bg-amber-600 transition shadow-sm">Deactivate</button>
            </div>
        </form>
    </div>
</div>

{{-- Category Activate Modal --}}
<div id="categoryActivateModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[60] p-4">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl max-w-sm w-full text-center border-t-4 border-teal-500 shadow-xl">
        <h2 class="text-lg font-bold mb-2 text-teal-600">Activate Category?</h2>
        <p class="mb-5 dark:text-gray-300 text-xs">Make "<span id="activateCatName" class="font-bold"></span>" visible on the booking menu again?</p>
        <form id="activateCategoryForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_active" value="1">
            <div class="flex gap-2">
                <button type="button" onclick="closeCategoryActivateModal()" class="flex-1 border p-2 rounded-xl text-xs font-semibold dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition">Cancel</button>
                <button type="submit" class="flex-1 bg-teal-600 text-white p-2 rounded-xl text-xs font-bold hover:bg-teal-700 transition shadow-sm">Activate</button>
            </div>
        </form>
    </div>
</div>

{{-- Category Delete Modal --}}
<div id="categoryDeleteModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[110] p-4">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl max-w-sm w-full shadow-2xl border border-red-100 dark:border-red-900/30 text-center">
        <h3 class="text-lg font-bold text-red-600 mb-2">Delete Menu Category</h3>
        <p id="delCatWarningText" class="text-gray-500 dark:text-gray-400 mb-5 text-xs leading-relaxed"></p>
        <form id="deleteCategoryForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex flex-col gap-2">
                <button type="submit" id="confirmDeleteBtn" disabled class="w-full py-2.5 bg-gray-400 text-white rounded-xl text-xs font-bold cursor-not-allowed transition">
                    Wait (<span id="countdownTimer">5</span>s)
                </button>
                <button type="button" onclick="closeCategoryDeleteModal()" class="w-full py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl text-xs font-semibold hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Category Create Modal --}}
<div id="categoryCreateModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-4">
    <div class="bg-white dark:bg-gray-800 rounded-3xl w-full max-w-md shadow-2xl border border-teal-100 dark:border-gray-700 overflow-hidden">
        <div class="bg-teal-600 p-5 text-white flex justify-between items-center">
            <h3 class="font-bold text-base flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>New Menu Category</span>
            </h3>
            <button onclick="closeCategoryCreateModal()" class="p-1 hover:bg-white/20 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form action="{{ route('admin.categories.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Category Name</label>
                <input type="text" name="name" required placeholder="e.g. Massages, Skincare, Body Wraps" class="w-full p-2.5 text-sm border dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Theme Color</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="color" value="#0d9488" class="h-10 w-16 rounded-xl cursor-pointer bg-transparent border border-gray-200 dark:border-gray-600">
                    <span class="text-xs text-gray-400">Color used for calendar badges</span>
                </div>
            </div>
            <div class="pt-2 flex gap-2">
                <button type="button" onclick="closeCategoryCreateModal()" class="flex-1 py-2.5 text-gray-500 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition">Cancel</button>
                <button type="submit" class="flex-1 bg-teal-600 text-white text-xs font-bold py-2.5 rounded-xl hover:bg-teal-700 transition shadow-sm">Save Category</button>
            </div>
        </form>
    </div>
</div>

{{-- Status Modal --}}
<div id="statusModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[60] p-4">
    <div id="statusModalBorder" class="bg-white dark:bg-gray-800 p-6 rounded-3xl max-w-sm w-full text-center border-t-4 shadow-xl">
        <div id="statusIcon" class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4"></div>
        <h2 id="statusModalTitle" class="text-lg font-bold mb-2"></h2>
        <p class="mb-5 dark:text-gray-300 text-xs">Update status for "<span id="statusModalName" class="font-bold"></span>"?</p>
        <form id="statusForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_active" id="statusValue">
            <div class="flex gap-2">
                <button type="button" onclick="closeStatusModal()" class="flex-1 border p-2 rounded-xl text-xs font-semibold dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition">Cancel</button>
                <button type="submit" id="statusSubmitBtn" class="flex-1 text-white p-2 rounded-xl text-xs font-bold transition">Confirm</button>
            </div>
        </form>
    </div>
</div>

{{-- ==================== STREAMLINED "CHOOSE YOUR MASSAGE" MODAL ==================== --}}
<div id="simpleChoiceModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[70] p-4">
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-md mx-auto flex flex-col border border-purple-200 dark:border-purple-800 overflow-hidden">
        {{-- Header --}}
        <div class="p-5 border-b border-purple-100 dark:border-gray-700 flex justify-between items-center bg-gradient-to-r from-purple-50 to-white dark:from-purple-950/40 dark:to-gray-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center text-purple-600 dark:text-purple-400 shadow-2xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white leading-tight">Treatment Choice Options</h3>
                    <p id="simpleChoiceModalSubtitle" class="text-xs text-purple-700/80 dark:text-purple-300 font-medium">Package</p>
                </div>
            </div>
            <button type="button" onclick="closeSimpleChoiceModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="p-6 space-y-4">
            <input type="hidden" id="simpleChoicePackageId">

            {{-- Enable Toggle --}}
            <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-purple-200 dark:border-purple-800/80 bg-purple-50/50 dark:bg-purple-950/20 cursor-pointer transition hover:bg-purple-50">
                <input type="checkbox" id="simpleChoiceEnabled" checked onchange="toggleSimpleChoiceFields()" class="mt-0.5 h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                <div>
                    <span class="text-xs font-bold text-purple-900 dark:text-purple-200 block">Offer "Choose Your Massage" for this package</span>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400">Clients will select one of the two treatments below when booking.</span>
                </div>
            </label>

            {{-- Fields Container --}}
            <div id="simpleChoiceFieldsWrapper" class="space-y-3.5 pt-1">
                {{-- Group Label (Standard: Choose Your Massage) --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Standard Choice Prompt</label>
                    <input type="text" id="simpleChoiceLabel" value="Choose Your Massage" class="w-full border border-gray-200 dark:border-gray-700 rounded-xl p-2.5 text-xs dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 outline-none">
                </div>

                {{-- Option 1 --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        Massage Option 1 <span class="text-red-500">*</span>
                    </label>
                    <select id="simpleChoiceOption1" class="w-full border border-purple-200 dark:border-purple-800 rounded-xl p-2.5 text-xs dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 outline-none">
                        <option value="">-- Select First Treatment Option --</option>
                        @foreach($singleServices as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->duration_minutes }}m · ₱{{ number_format($s->price, 0) }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Option 2 --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        Massage Option 2 <span class="text-red-500">*</span>
                    </label>
                    <select id="simpleChoiceOption2" class="w-full border border-purple-200 dark:border-purple-800 rounded-xl p-2.5 text-xs dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 outline-none">
                        <option value="">-- Select Second Treatment Option --</option>
                        @foreach($singleServices as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->duration_minutes }}m · ₱{{ number_format($s->price, 0) }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Required Checkbox --}}
                <div class="pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-xs">
                        <input type="checkbox" id="simpleChoiceRequired" checked class="h-3.5 w-3.5 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Customer selection is required during booking</span>
                    </label>
                </div>
            </div>

            {{-- Status Alert (Hidden by default) --}}
            <div id="simpleChoiceAlert" class="hidden p-3 rounded-xl text-xs"></div>
        </div>

        {{-- Footer --}}
        <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-2.5 bg-gray-50 dark:bg-gray-800">
            <button type="button" onclick="closeSimpleChoiceModal()" class="px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                Cancel
            </button>
            <button type="button" id="btnSaveSimpleChoices" onclick="submitSimpleChoices()" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-2">
                <svg id="simpleChoiceSpinner" class="hidden w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span id="simpleChoiceBtnText">Save Choices</span>
            </button>
        </div>
    </div>
</div>

{{-- Toast Notification --}}
<div id="adminServicesToast" class="fixed bottom-5 right-5 z-[100] transform transition-all duration-300 translate-y-20 opacity-0 pointer-events-none flex items-center gap-2.5 px-4 py-3 rounded-2xl bg-gray-900 text-white shadow-2xl border border-gray-700 text-xs font-semibold">
    <span id="toastIcon">✅</span>
    <span id="toastMsg">Treatment choices updated successfully!</span>
</div>

@endsection

@push('scripts')
<script>
    // ==================== TABS WITH PERSISTENCE ====================
    function switchTab(tab) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
        const panel = document.getElementById('panel-' + tab);
        if (panel) panel.classList.remove('hidden');

        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('border-teal-600', 'text-teal-600', 'bg-white', 'dark:bg-gray-800', 'dark:text-teal-400');
            b.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'dark:text-gray-400', 'dark:hover:text-gray-200');
        });
        const activeBtn = document.getElementById('tab-' + tab);
        if (activeBtn) {
            activeBtn.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'dark:text-gray-400', 'dark:hover:text-gray-200');
            activeBtn.classList.add('border-teal-600', 'text-teal-600', 'bg-white', 'dark:bg-gray-800', 'dark:text-teal-400');
        }

        // Remember active tab across page updates and reloads
        try {
            localStorage.setItem('admin_services_active_tab', tab);
            if (window.location.hash !== '#' + tab) {
                history.replaceState(null, null, '#' + tab);
            }
        } catch (e) {}
    }

    // Auto-restore active tab on load so user is never kicked back to tab 1
    document.addEventListener('DOMContentLoaded', function () {
        const hash = window.location.hash ? window.location.hash.replace('#', '') : '';
        const savedTab = hash || localStorage.getItem('admin_services_active_tab');
        if (savedTab && ['services', 'packages', 'categories'].includes(savedTab)) {
            switchTab(savedTab);
        }
    });

    // ==================== SEARCH ====================
    function filterServices(query) {
        const rows = document.querySelectorAll('#servicesTableBody .service-row');
        const term = query.toLowerCase().trim();
        let visible = 0;
        rows.forEach(row => {
            const name = row.dataset.name || '';
            const category = row.dataset.category || '';
            const roomCat = row.dataset.roomCat || '';
            const match = name.includes(term) || category.includes(term) || roomCat.includes(term);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        const noResults = document.getElementById('noServicesResults');
        if (noResults) noResults.style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
    }

    function filterPackages(query) {
        const cards = document.querySelectorAll('#packagesGrid .package-card');
        const term = query.toLowerCase().trim();
        cards.forEach(card => {
            const name = card.dataset.name || '';
            const match = name.includes(term);
            card.style.display = match ? '' : 'none';
        });
    }

    // ==================== DISCOUNT PREVIEW ====================
    function updateDiscountPreview() {
        const priceInput = document.getElementById('servicePrice');
        const percentInput = document.getElementById('serviceDiscountPercent');
        const preview = document.getElementById('discountPreview');
        const finalPriceEl = document.getElementById('finalPrice');
        const saveAmountEl = document.getElementById('saveAmount');
        const discountPriceInput = document.getElementById('discountPriceValue');
        if (!priceInput || !percentInput || !preview) return;
        const price = parseFloat(priceInput.value) || 0;
        const percent = parseFloat(percentInput.value) || 0;
        if (price > 0 && percent > 0 && percent <= 100) {
            const discountAmount = price * (percent / 100);
            const finalPrice = price - discountAmount;
            finalPriceEl.textContent = finalPrice.toFixed(2);
            saveAmountEl.textContent = discountAmount.toFixed(2);
            discountPriceInput.value = finalPrice.toFixed(2);
            preview.classList.remove('hidden');
        } else {
            preview.classList.add('hidden');
            discountPriceInput.value = '';
        }
    }

    // ==================== DEPOSIT PREVIEW ====================
    function updateDepositPreview() {
        const priceInput = document.getElementById('servicePrice');
        const minInput = document.getElementById('serviceDepositMin');
        const maxInput = document.getElementById('serviceDepositMax');
        const preview = document.getElementById('depositPreview');
        const rangeText = document.getElementById('depositRangeText');
        const pesoMin = document.getElementById('depositPesoMin');
        const pesoMax = document.getElementById('depositPesoMax');
        if (!priceInput || !minInput || !maxInput || !preview) return;
        const price = parseFloat(priceInput.value) || 0;
        const minPercent = parseFloat(minInput.value) || 0;
        const maxPercent = parseFloat(maxInput.value) || 0;
        if (minPercent > 0 || maxPercent > 0) {
            const minAmount = price * (minPercent / 100);
            const maxAmount = price * (maxPercent / 100);
            rangeText.textContent = minPercent + '% - ' + maxPercent + '%';
            pesoMin.textContent = minAmount.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            pesoMax.textContent = maxAmount.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            preview.classList.remove('hidden');
        } else {
            preview.classList.add('hidden');
        }
    }

    // ==================== CONTEXT-AWARE MODAL (SERVICES VS PACKAGES) ====================
    let currentEditingServiceId = null;

    function applyModalTheme(isPackage, isEdit) {
        const modalIcon = document.getElementById('modalIcon');
        const modalTitle = document.getElementById('modalTitle');
        const modalSubtitle = document.getElementById('modalSubtitle');
        const modalHeaderBg = document.getElementById('modalHeaderBg');
        const saveBtn = document.getElementById('saveServiceBtn');
        const saveText = document.getElementById('saveServiceText');
        const packageWrapper = document.getElementById('packageServicesWrapper');

        if (isPackage) {
            // Package Mode
            modalTitle.textContent = isEdit ? 'Edit Package Bundle' : 'Create Package Bundle';
            modalSubtitle.textContent = 'Bundle multiple treatments together into a cohesive package offering';
            modalHeaderBg.className = 'p-5 border-b dark:border-gray-700 flex justify-between items-center shrink-0 bg-gradient-to-r from-purple-50 to-white dark:from-purple-950/30 dark:to-gray-800 transition-colors';
            modalIcon.className = 'w-11 h-11 rounded-2xl bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center text-purple-600 dark:text-purple-400 transition-colors shadow-2xs';
            modalIcon.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>';
            saveBtn.className = 'px-6 py-2.5 bg-purple-600 text-white rounded-xl hover:bg-purple-700 font-semibold text-sm shadow-md transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed';
            saveText.textContent = isEdit ? 'Update Package' : 'Save Package';
            packageWrapper.classList.remove('hidden');
            const choiceWrapper = document.getElementById('packageChoiceGroupsWrapper');
            if (choiceWrapper) choiceWrapper.classList.remove('hidden');
        } else {
            // Service Mode
            modalTitle.textContent = isEdit ? 'Edit Service' : 'Add New Service';
            modalSubtitle.textContent = 'Configure treatment specifications, facility requirements, and pricing';
            modalHeaderBg.className = 'p-5 border-b dark:border-gray-700 flex justify-between items-center shrink-0 bg-gradient-to-r from-teal-50 to-white dark:from-teal-950/30 dark:to-gray-800 transition-colors';
            modalIcon.className = 'w-11 h-11 rounded-2xl bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 transition-colors shadow-2xs';
            modalIcon.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>';
            saveBtn.className = 'px-6 py-2.5 bg-teal-600 text-white rounded-xl hover:bg-teal-700 font-semibold text-sm shadow-md transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed';
            saveText.textContent = isEdit ? 'Update Service' : 'Save Service';
            packageWrapper.classList.add('hidden');
            const choiceWrapper = document.getElementById('packageChoiceGroupsWrapper');
            if (choiceWrapper) choiceWrapper.classList.add('hidden');
        }
    }

    function openServiceModal(isPackage = false) {
        currentEditingServiceId = null;
        document.getElementById('serviceForm').action = '{{ route("admin.services.store") }}';
        document.getElementById('methodField').innerHTML = '';

        // Set hidden is_package value (NO user-facing checkbox!)
        document.getElementById('serviceIsPackage').value = isPackage ? '1' : '0';

        applyModalTheme(isPackage, false);
        resetServiceSubmitButton();

        // Reset form inputs
        document.getElementById('serviceName').value = '';
        document.getElementById('serviceCategory').value = '';
        document.getElementById('serviceDuration').value = '60';
        document.getElementById('servicePrice').value = '';
        document.getElementById('serviceDiscountPercent').value = '0';
        document.getElementById('discountPriceValue').value = '';
        document.getElementById('serviceDescription').value = '';
        document.getElementById('serviceLandingDescription').value = '';
        document.getElementById('serviceShowOnLanding').checked = true;
        document.getElementById('serviceIsActive').checked = true;
        document.getElementById('serviceDepositMin').value = '0';
        document.getElementById('serviceDepositMax').value = '0';
        document.getElementById('serviceParticipants').value = '1';
        document.getElementById('serviceTherapists').value = '1';
        document.getElementById('serviceRequiresRoom').checked = true;
        document.getElementById('serviceRoomCategory').value = '';

        // Filter active room categories
        Array.from(document.getElementById('serviceRoomCategory').options).forEach(opt => {
            if (opt.value === '') return;
            const isActive = opt.dataset.isActive === '1';
            opt.hidden = !isActive;
            opt.disabled = !isActive;
        });
        toggleRoomRequirement();

        document.getElementById('serviceImage').value = '';
        document.getElementById('currentImagePreview').classList.add('hidden');
        document.getElementById('currentImageImg').src = '';
        document.getElementById('serviceRemoveImage').checked = false;

        const dp = document.getElementById('discountPreview');
        if (dp) dp.classList.add('hidden');
        const dep = document.getElementById('depositPreview');
        if (dep) dep.classList.add('hidden');

        // Reset package inclusions
        document.querySelectorAll('.package-checkbox').forEach(cb => cb.checked = false);
        clearPackageSearch();
        resetPackageItemsVisibility();
        syncSelectedInclusions();

        const btnManage = document.getElementById('btnManageChoicesFromModal');
        if (btnManage) {
            btnManage.classList.add('opacity-50', 'cursor-not-allowed');
            btnManage.setAttribute('title', 'Save the package first before configuring choice groups');
        }

        const modal = document.getElementById('serviceModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function editService(id) {
        currentEditingServiceId = parseInt(id);
        const data = document.querySelector(`#servicesData div[data-id="${id}"]`);
        if (!data) return;

        const isPackage = data.dataset.isPackage === '1';
        document.getElementById('serviceForm').action = `{{ url('admin/services') }}/${id}`;
        document.getElementById('methodField').innerHTML = '@method("PUT")';

        // Set hidden is_package value
        document.getElementById('serviceIsPackage').value = isPackage ? '1' : '0';

        applyModalTheme(isPackage, true);
        resetServiceSubmitButton();

        const price = parseFloat(data.dataset.price) || 0;
        const discountPrice = parseFloat(data.dataset.discount) || 0;

        document.getElementById('serviceName').value = data.dataset.name;
        document.getElementById('serviceCategory').value = data.dataset.category;
        document.getElementById('serviceDuration').value = data.dataset.duration;
        document.getElementById('servicePrice').value = price;
        document.getElementById('serviceDescription').value = data.dataset.description || '';
        document.getElementById('serviceLandingDescription').value = data.dataset.landingDescription || '';
        document.getElementById('serviceShowOnLanding').checked = data.dataset.showOnLanding === '1';
        document.getElementById('serviceIsActive').checked = data.dataset.isActive === '1';
        document.getElementById('serviceDepositMin').value = data.dataset.depositMin || 0;
        document.getElementById('serviceDepositMax').value = data.dataset.depositMax || 0;
        document.getElementById('serviceParticipants').value = data.dataset.participants || 1;
        document.getElementById('serviceTherapists').value = data.dataset.therapists || 1;
        document.getElementById('serviceRequiresRoom').checked = data.dataset.requiresRoom !== '0';

        const currentRoomCat = data.dataset.roomCategory || '';
        document.getElementById('serviceRoomCategory').value = currentRoomCat;
        Array.from(document.getElementById('serviceRoomCategory').options).forEach(opt => {
            if (opt.value === '') return;
            const isActive = opt.dataset.isActive === '1';
            const isCurrent = opt.value === currentRoomCat;
            opt.hidden = !isActive && !isCurrent;
            opt.disabled = !isActive && !isCurrent;
        });
        toggleRoomRequirement();

        // Image preview
        const imgPreview = document.getElementById('currentImagePreview');
        const imgEl = document.getElementById('currentImageImg');
        if (data.dataset.image) {
            imgEl.src = data.dataset.image;
            imgPreview.classList.remove('hidden');
        } else {
            imgPreview.classList.add('hidden');
            imgEl.src = '';
        }
        document.getElementById('serviceRemoveImage').checked = false;

        // Discount calculation
        const percentInput = document.getElementById('serviceDiscountPercent');
        if (discountPrice > 0 && price > 0) {
            const percent = Math.round(((price - discountPrice) / price) * 100);
            percentInput.value = percent;
        } else {
            percentInput.value = 0;
        }
        updateDiscountPreview();
        updateDepositPreview();

        // Inclusions parsing
        let included = [];
        try {
            const raw = data.dataset.included;
            if (raw && raw !== 'null' && raw !== '') {
                included = JSON.parse(raw);
            }
        } catch (e) {
            console.error('Failed to parse included services:', e);
            included = [];
        }

        document.querySelectorAll('.package-checkbox').forEach(cb => {
            const val = parseInt(cb.value);
            cb.checked = included.includes(val) || included.includes(cb.value);
        });

        clearPackageSearch();
        resetPackageItemsVisibility();
        syncSelectedInclusions();

        const btnManage = document.getElementById('btnManageChoicesFromModal');
        if (btnManage) {
            btnManage.classList.remove('opacity-50', 'cursor-not-allowed');
            btnManage.removeAttribute('title');
        }

        const modal = document.getElementById('serviceModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeServiceModal() {
        const modal = document.getElementById('serviceModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        currentEditingServiceId = null;
    }

    // ==================== STREAMLINED "CHOOSE YOUR MASSAGE" (NO REFRESH) ====================
    function openSimpleChoiceModal(pkgId) {
        if (!pkgId) {
            alert('Please save the package first before configuring treatment choices.');
            return;
        }

        const modal = document.getElementById('simpleChoiceModal');
        if (!modal) return;

        const card = document.getElementById(`package-choices-display-${pkgId}`);
        const pkgName = card ? card.dataset.packageName : (document.getElementById('serviceName')?.value || 'Package');
        document.getElementById('simpleChoicePackageId').value = pkgId;
        document.getElementById('simpleChoiceModalSubtitle').textContent = pkgName;

        const isEnabled = card ? (card.dataset.choiceEnabled === '1') : true;
        document.getElementById('simpleChoiceEnabled').checked = isEnabled;

        let options = [];
        try {
            options = JSON.parse(card?.dataset.choiceOptions || '[]');
        } catch (e) {
            options = [];
        }

        document.getElementById('simpleChoiceLabel').value = card?.dataset.choiceLabel || 'Choose Your Massage';
        document.getElementById('simpleChoiceRequired').checked = (card?.dataset.choiceRequired ?? '1') === '1';

        document.getElementById('simpleChoiceOption1').value = options[0] ? String(options[0]) : '';
        document.getElementById('simpleChoiceOption2').value = options[1] ? String(options[1]) : '';

        toggleSimpleChoiceFields();
        const alertBox = document.getElementById('simpleChoiceAlert');
        if (alertBox) alertBox.classList.add('hidden');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeSimpleChoiceModal() {
        const modal = document.getElementById('simpleChoiceModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function toggleSimpleChoiceFields() {
        const isEnabled = document.getElementById('simpleChoiceEnabled').checked;
        const wrapper = document.getElementById('simpleChoiceFieldsWrapper');
        if (wrapper) {
            wrapper.style.display = isEnabled ? 'block' : 'none';
        }
    }

    async function submitSimpleChoices() {
        const pkgId = document.getElementById('simpleChoicePackageId').value;
        const enabled = document.getElementById('simpleChoiceEnabled').checked;
        const label = document.getElementById('simpleChoiceLabel').value.trim() || 'Choose Your Massage';
        const opt1 = document.getElementById('simpleChoiceOption1').value;
        const opt2 = document.getElementById('simpleChoiceOption2').value;
        const isRequired = document.getElementById('simpleChoiceRequired').checked;
        const alertBox = document.getElementById('simpleChoiceAlert');
        const btn = document.getElementById('btnSaveSimpleChoices');
        const spinner = document.getElementById('simpleChoiceSpinner');
        const btnText = document.getElementById('simpleChoiceBtnText');

        alertBox.classList.add('hidden');

        if (enabled && (!opt1 || !opt2)) {
            alertBox.className = 'p-3 rounded-xl text-xs bg-red-50 text-red-700 border border-red-200 block';
            alertBox.textContent = 'Please select both Massage Option 1 and Massage Option 2.';
            return;
        }

        if (enabled && opt1 === opt2) {
            alertBox.className = 'p-3 rounded-xl text-xs bg-red-50 text-red-700 border border-red-200 block';
            alertBox.textContent = 'Option 1 and Option 2 must be different treatments.';
            return;
        }

        btn.disabled = true;
        spinner.classList.remove('hidden');
        btnText.textContent = 'Saving...';

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value;

        try {
            const response = await fetch(`{{ url('admin/services') }}/${pkgId}/choices-sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    enabled: enabled,
                    label: label,
                    is_required: isRequired ? 1 : 0,
                    option_ids: enabled ? [opt1, opt2] : [],
                }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to save choices.');
            }

            // Update package card display without page refresh
            const cardDisplay = document.getElementById(`package-choices-display-${pkgId}`);
            if (cardDisplay) {
                cardDisplay.dataset.choiceEnabled = data.has_choices ? '1' : '0';
                cardDisplay.dataset.choiceOptions = JSON.stringify(data.options ? data.options.map(o => o.service_id) : []);
                cardDisplay.dataset.choiceLabel = data.label || 'Choose Your Massage';
                cardDisplay.dataset.choiceRequired = data.is_required ? '1' : '0';

                if (data.has_choices && data.options && data.options.length > 0) {
                    cardDisplay.innerHTML = `
                        <div class="space-y-1.5 mb-4">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300 block mb-1">Choice Groups (1):</span>
                            <div class="rounded-lg border border-purple-200 dark:border-purple-800 bg-purple-50/40 dark:bg-purple-950/30 p-2 text-xs">
                                <div class="flex items-center justify-between font-semibold text-purple-800 dark:text-purple-200 mb-1">
                                    <span>${data.label || 'Choose Your Massage'}</span>
                                    <span class="text-[10px] ${data.is_required ? 'text-red-500 font-bold' : 'text-gray-400'}">${data.is_required ? 'Required' : 'Optional'}</span>
                                </div>
                                <div class="text-[11px] text-gray-600 dark:text-gray-300">
                                    ${data.formatted_text}
                                </div>
                            </div>
                            <button type="button" onclick="openSimpleChoiceModal(${pkgId})" class="mt-2 w-full py-1.5 px-3 rounded-lg border border-purple-200 dark:border-purple-800 bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-purple-700 dark:text-purple-300 text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                                ⚙️ Configure Choice Groups
                            </button>
                        </div>
                    `;
                } else {
                    cardDisplay.innerHTML = `
                        <div class="mb-4">
                            <button type="button" onclick="openSimpleChoiceModal(${pkgId})" class="w-full py-1.5 px-3 rounded-lg border border-dashed border-purple-200 dark:border-purple-800 hover:border-purple-400 hover:bg-purple-50/50 text-purple-600 dark:text-purple-400 text-xs font-medium flex items-center justify-center gap-1.5 transition">
                                + Add "Choose Your Massage" Option
                            </button>
                        </div>
                    `;
                }
            }

            closeSimpleChoiceModal();
            showAdminToast(data.message || 'Treatment choices updated successfully!');

        } catch (err) {
            alertBox.className = 'p-3 rounded-xl text-xs bg-red-50 text-red-700 border border-red-200 block';
            alertBox.textContent = err.message || 'An error occurred while saving choices.';
        } finally {
            btn.disabled = false;
            spinner.classList.add('hidden');
            btnText.textContent = 'Save Choices';
        }
    }

    function showAdminToast(msg, isError = false) {
        const toast = document.getElementById('adminServicesToast');
        const msgEl = document.getElementById('toastMsg');
        const iconEl = document.getElementById('toastIcon');
        if (!toast || !msgEl) return;
        msgEl.textContent = msg;
        if (iconEl) iconEl.textContent = isError ? '⚠️' : '✅';
        toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
        toast.classList.add('translate-y-0', 'opacity-100');
        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
            toast.classList.remove('translate-y-0', 'opacity-100');
        }, 3500);
    }

    // ==================== PACKAGE INCLUSION SEARCH & SELECTION ====================
    function filterPackageServices() {
        const searchInput = document.getElementById('packageServiceSearch');
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const clearBtn = document.getElementById('clearPackageServiceSearch');
        if (clearBtn) clearBtn.classList.toggle('hidden', query === '');

        let visibleCount = 0;
        document.querySelectorAll('.package-service-item').forEach(item => {
            const itemId = parseInt(item.dataset.serviceId);
            const itemName = item.dataset.serviceName || '';

            // Prevent self-inclusion
            if (currentEditingServiceId && itemId === currentEditingServiceId) {
                item.classList.add('hidden');
                return;
            }

            if (query === '' || itemName.includes(query)) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        const noMatch = document.getElementById('noMatchingPackageServices');
        if (noMatch) noMatch.classList.toggle('hidden', visibleCount > 0);
    }

    function clearPackageSearch() {
        const searchInput = document.getElementById('packageServiceSearch');
        if (searchInput) searchInput.value = '';
        const clearBtn = document.getElementById('clearPackageServiceSearch');
        if (clearBtn) clearBtn.classList.add('hidden');
        filterPackageServices();
    }

    function resetPackageItemsVisibility() {
        document.querySelectorAll('.package-service-item').forEach(item => {
            const itemId = parseInt(item.dataset.serviceId);
            if (currentEditingServiceId && itemId === currentEditingServiceId) {
                item.classList.add('hidden');
            } else {
                item.classList.remove('hidden');
            }
        });
        const noMatch = document.getElementById('noMatchingPackageServices');
        if (noMatch) noMatch.classList.add('hidden');
    }

    function syncSelectedInclusions() {
        const chipsContainer = document.getElementById('selectedInclusionsChips');
        const countBadge = document.getElementById('selectedInclusionsCount');
        const checkedBoxes = Array.from(document.querySelectorAll('.package-checkbox:checked'));

        if (countBadge) {
            countBadge.textContent = `${checkedBoxes.length} selected`;
        }

        if (!chipsContainer) return;

        if (checkedBoxes.length === 0) {
            chipsContainer.innerHTML = '<span id="noSelectedInclusionsPlaceholder" class="text-xs text-gray-400 italic">No services selected yet. Search and check services below to include them.</span>';
            return;
        }

        let html = '';
        checkedBoxes.forEach(cb => {
            const id = cb.value;
            const name = cb.dataset.name || cb.closest('label').querySelector('.font-semibold')?.textContent?.trim() || `Service #${id}`;
            const isPkg = cb.dataset.isPackage === '1';

            html += `
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium ${isPkg ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-200 border border-purple-200 dark:border-purple-800' : 'bg-teal-50 text-teal-800 dark:bg-teal-950/40 dark:text-teal-200 border border-teal-200 dark:border-teal-800'} shadow-2xs">
                    <span class="truncate max-w-[140px]">${name}</span>
                    <button type="button" class="text-gray-400 hover:text-red-500 font-bold ml-1 transition" onclick="removeInclusion(${id})" title="Remove ${name}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </span>
            `;
        });

        chipsContainer.innerHTML = html;
    }

    function removeInclusion(id) {
        const cb = document.querySelector(`.package-checkbox[value="${id}"]`);
        if (cb) {
            cb.checked = false;
            syncSelectedInclusions();
        }
    }

    function resetServiceSubmitButton() {
        const btn = document.getElementById('saveServiceBtn');
        const icon = document.getElementById('saveServiceIcon');
        const text = document.getElementById('saveServiceText');
        if (btn) btn.disabled = false;
        if (text) {
            const isPkg = document.getElementById('serviceIsPackage').value === '1';
            const isEdit = currentEditingServiceId !== null;
            text.textContent = isPkg ? (isEdit ? 'Update Package' : 'Save Package') : (isEdit ? 'Update Service' : 'Save Service');
        }
        if (icon) {
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
            icon.classList.remove('animate-spin');
        }
    }

    // Attach duplicate-submission prevention to service form
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('serviceForm');
        if (form) {
            form.addEventListener('submit', function() {
                const btn = document.getElementById('saveServiceBtn');
                const icon = document.getElementById('saveServiceIcon');
                const text = document.getElementById('saveServiceText');
                if (btn) {
                    btn.disabled = true;
                    if (text) text.textContent = 'Saving...';
                    if (icon) {
                        icon.innerHTML = '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>';
                        icon.classList.add('animate-spin');
                    }
                }
            });
        }
    });

    function toggleRoomRequirement() {
        const reqRoom = document.getElementById('serviceRequiresRoom').checked;
        const wrapper = document.getElementById('roomCategorySelectWrapper');
        if (wrapper) {
            wrapper.classList.toggle('opacity-40', !reqRoom);
            wrapper.classList.toggle('pointer-events-none', !reqRoom);
            if (!reqRoom) {
                document.getElementById('serviceRoomCategory').value = '';
            }
        }
    }

    // ==================== DELETE ====================
    function confirmDelete(id, name) {
        document.getElementById('deleteServiceName').textContent = name;
        document.getElementById('deleteForm').action = `/admin/services/${id}`;
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // ==================== STATUS TOGGLE ====================
    function toggleStatus(id, name, targetValue) {
        const modal = document.getElementById('statusModal');
        const title = document.getElementById('statusModalTitle');
        const button = document.getElementById('statusSubmitBtn');
        const border = document.getElementById('statusModalBorder');
        const iconDiv = document.getElementById('statusIcon');

        document.getElementById('statusModalName').textContent = name;
        document.getElementById('statusValue').value = targetValue;
        document.getElementById('statusForm').action = `/admin/services/${id}`;

        if (targetValue == 1) {
            title.textContent = 'Activate Item?';
            title.className = 'text-lg font-bold mb-2 text-teal-600';
            border.style.borderColor = '#0d9488';
            button.className = 'flex-1 bg-teal-600 text-white p-2 rounded-xl text-xs font-bold hover:bg-teal-700 transition shadow-sm';
            iconDiv.className = 'w-14 h-14 bg-teal-100 dark:bg-teal-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4';
            iconDiv.innerHTML = '<svg class="w-7 h-7 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        } else {
            title.textContent = 'Deactivate Item?';
            title.className = 'text-lg font-bold mb-2 text-amber-600';
            border.style.borderColor = '#f59e0b';
            button.className = 'flex-1 bg-amber-500 text-white p-2 rounded-xl text-xs font-bold hover:bg-amber-600 transition shadow-sm';
            iconDiv.className = 'w-14 h-14 bg-amber-100 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4';
            iconDiv.innerHTML = '<svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeStatusModal() {
        const modal = document.getElementById('statusModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // ==================== CATEGORY MODALS ====================
    function openEditCategoryModal(id, name, color) {
        const modal = document.getElementById('categoryEditModal');
        const form = document.getElementById('editCategoryForm');
        form.action = `/admin/categories/${id}`;
        document.getElementById('editCatName').value = name;
        document.getElementById('editCatColor').value = color || '#0d9488';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeCategoryEditModal() {
        const modal = document.getElementById('categoryEditModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function confirmDeactivateCategory(id, name) {
        document.getElementById('deactivateCatName').textContent = name;
        document.getElementById('deactivateCategoryForm').action = `/admin/categories/${id}`;
        const modal = document.getElementById('categoryDeactivateModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeCategoryDeactivateModal() {
        const modal = document.getElementById('categoryDeactivateModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function confirmActivateCategory(id, name) {
        document.getElementById('activateCatName').textContent = name;
        document.getElementById('activateCategoryForm').action = `/admin/categories/${id}`;
        const modal = document.getElementById('categoryActivateModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeCategoryActivateModal() {
        const modal = document.getElementById('categoryActivateModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    let catDeleteTimer;
    function confirmDeleteCategory(id, name, serviceCount) {
        const modal = document.getElementById('categoryDeleteModal');
        const form = document.getElementById('deleteCategoryForm');
        const warningText = document.getElementById('delCatWarningText');
        const btn = document.getElementById('confirmDeleteBtn');
        form.action = `/admin/categories/${id}`;
        clearInterval(catDeleteTimer);
        if (serviceCount > 0) {
            warningText.innerHTML = `⚠️ <strong>BLOCKED:</strong> Cannot delete <span class="text-red-600 font-bold">${name}</span>. It contains <strong>${serviceCount} service(s)</strong>. Move or delete those services first.`;
            btn.disabled = true;
            btn.className = 'w-full py-2.5 bg-gray-400 text-white rounded-xl text-xs font-bold cursor-not-allowed transition';
            btn.innerHTML = 'Delete Blocked';
        } else {
            warningText.innerHTML = `Are you sure you want to delete <span class="font-bold text-teal-600">${name}</span>? This category will be permanently removed.`;
            let timeLeft = 5;
            btn.disabled = true;
            btn.className = 'w-full py-2.5 bg-gray-400 text-white rounded-xl text-xs font-bold cursor-not-allowed transition';
            btn.innerHTML = `Wait (${timeLeft}s)`;
            catDeleteTimer = setInterval(() => {
                timeLeft--;
                const timerSpan = document.getElementById('countdownTimer');
                if (timerSpan) timerSpan.textContent = timeLeft;
                if (timeLeft <= 0) {
                    clearInterval(catDeleteTimer);
                    btn.disabled = false;
                    btn.innerHTML = 'Confirm Delete';
                    btn.className = 'w-full py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition shadow-sm';
                }
            }, 1000);
        }
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeCategoryDeleteModal() {
        clearInterval(catDeleteTimer);
        const modal = document.getElementById('categoryDeleteModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openCategoryCreateModal() {
        const modal = document.getElementById('categoryCreateModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeCategoryCreateModal() {
        const modal = document.getElementById('categoryCreateModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // ==================== OUTSIDE CLICK CLOSE ====================
    window.addEventListener('click', function(event) {
        if (event.target === document.getElementById('serviceModal')) closeServiceModal();
        if (event.target === document.getElementById('deleteModal')) closeDeleteModal();
        if (event.target === document.getElementById('categoryEditModal')) closeCategoryEditModal();
        if (event.target === document.getElementById('categoryDeleteModal')) closeCategoryDeleteModal();
        if (event.target === document.getElementById('categoryDeactivateModal')) closeCategoryDeactivateModal();
        if (event.target === document.getElementById('categoryActivateModal')) closeCategoryActivateModal();
        if (event.target === document.getElementById('statusModal')) closeStatusModal();
        if (event.target === document.getElementById('categoryCreateModal')) closeCategoryCreateModal();
    });

</script>
@endpush
