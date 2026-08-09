@extends('layouts.admin')

@section('title', 'Service Catalog')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Service Catalog</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage single services, packages, and categories</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <button onclick="switchTab('services')" id="tab-services" class="tab-btn flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-teal-600 text-teal-600 bg-white dark:bg-gray-800 dark:text-teal-400 transition-colors">
                Single Services <span class="ml-1 text-xs bg-gray-200 dark:bg-gray-600 px-1.5 py-0.5 rounded-full text-gray-600 dark:text-gray-300">{{ $services->where('is_package', false)->count() }}</span>
            </button>
            <button onclick="switchTab('packages')" id="tab-packages" class="tab-btn flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                Packages <span class="ml-1 text-xs bg-gray-200 dark:bg-gray-600 px-1.5 py-0.5 rounded-full text-gray-600 dark:text-gray-300">{{ $services->where('is_package', true)->count() }}</span>
            </button>
            <button onclick="switchTab('categories')" id="tab-categories" class="tab-btn flex-1 py-3 px-4 text-center font-semibold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                Categories <span class="ml-1 text-xs bg-gray-200 dark:bg-gray-600 px-1.5 py-0.5 rounded-full text-gray-600 dark:text-gray-300">{{ $categories->count() }}</span>
            </button>
        </div>

        {{-- SERVICES TAB --}}
        <div id="panel-services" class="tab-panel p-6">
            <div class="flex justify-between items-center mb-4">
                <input type="text" id="searchServices" oninput="filterServices(this.value)" placeholder="Search by name or category..." 
                    class="w-full max-w-md border rounded-lg p-2.5 pl-10 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none bg-[url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%229ca3af%22%3E%3Cpath stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z%22/%3E%3C/svg%3E')] bg-no-repeat bg-[length:20px] bg-[position:12px_center]">
                <button onclick="openServiceModal()" class="ml-3 shrink-0 bg-teal-600 text-white px-4 py-2.5 rounded-lg hover:bg-teal-700 transition font-medium shadow flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Service
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-xs font-bold text-gray-500 uppercase tracking-wider border-b dark:border-gray-700">
                            <th class="pb-3 pl-2">Service</th>
                            <th class="pb-3">Category</th>
                            <th class="pb-3">Duration</th>
                            <th class="pb-3">Price</th>
                            <th class="pb-3">Deposit</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3 text-right pr-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700" id="servicesTableBody">
                        @foreach($services->where('is_package', false) as $service)
                        <tr class="service-row group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition" data-name="{{ strtolower($service->name) }}" data-category="{{ strtolower($service->category->name ?? '') }}">
                            <td class="py-3.5 pl-2">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $service->name }}</div>
                                @if($service->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[200px]" title="{{ $service->description }}">{{ Str::limit($service->description, 40) }}</div>
                                @endif
                            </td>
                            <td class="py-3.5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $service->category->color ?? '#ccc' }}"></span>
                                    {{ $service->category->name }}
                                </span>
                            </td>
                            <td class="py-3.5 text-sm text-gray-600 dark:text-gray-400">{{ $service->duration_minutes }} min</td>
                            <td class="py-3.5">
                                @if($service->discount_price)
                                    <span class="text-xs text-gray-400 line-through">₱{{ number_format($service->price, 0) }}</span>
                                    <span class="text-sm font-bold text-teal-600 dark:text-teal-400">₱{{ number_format($service->discount_price, 2) }}</span>
                                @else
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">₱{{ number_format($service->price, 2) }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 text-xs text-gray-500 dark:text-gray-400">
                                @if($service->deposit_percentage_min || $service->deposit_percentage_max)
                                    {{ $service->deposit_percentage_min ?? 0 }}% - {{ $service->deposit_percentage_max ?? 0 }}%
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            <td class="py-3.5">
                                @if($service->is_active)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">Active</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300">Inactive</span>
                                @endif
                            </td>
                            <td class="py-3.5 text-right pr-2">
                                <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition">
                                    <button onclick="editService({{ $service->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <button onclick="toggleStatus({{ $service->id }}, '{{ addslashes($service->name) }}', {{ $service->is_active ? 0 : 1 }})" class="p-1.5 {{ $service->is_active ? 'text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20' : 'text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20' }} rounded" title="{{ $service->is_active ? 'Deactivate' : 'Activate' }}">
                                        @if($service->is_active)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @endif
                                    </button>
                                    <button onclick="confirmDelete({{ $service->id }}, '{{ addslashes($service->name) }}')" class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded" title="Delete">
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
                    <p>No services found.</p>
                </div>
                @if($services->where('is_package', false)->isEmpty())
                    <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <p>No single services yet.</p>
                        <button onclick="openServiceModal()" class="text-teal-600 hover:underline text-sm mt-1">Create one &rarr;</button>
                    </div>
                @endif
            </div>
        </div>

        {{-- PACKAGES TAB --}}
        <div id="panel-packages" class="tab-panel p-6 hidden">
            @php $allServices = $services->keyBy('id'); @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="packagesGrid">
                @foreach($services->where('is_package', true) as $package)
                @php
                    $includedIds = $package->included_services ?? [];
                    if (is_string($includedIds)) $includedIds = json_decode($includedIds, true) ?? [];
                    $included = $allServices->only($includedIds);
                    $totalDuration = $included->sum('duration_minutes');
                @endphp
                <div class="package-card bg-white dark:bg-gray-700 rounded-xl border-2 border-purple-100 dark:border-purple-900/30 p-5 hover:shadow-lg transition group" data-name="{{ strtolower($package->name) }}">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300">Package</span>
                                @if(!$package->is_active)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300">Inactive</span>
                                @endif
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-lg leading-tight">{{ $package->name }}</h3>
                        </div>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition">
                            <button onclick="editService({{ $package->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                            <button onclick="confirmDelete({{ $package->id }}, '{{ addslashes($package->name) }}')" class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5 mb-4">
                        @forelse($included as $inc)
                        <div class="flex items-center justify-between text-sm py-1.5 px-3 bg-purple-50/50 dark:bg-purple-900/10 rounded-lg border border-purple-100 dark:border-purple-900/20">
                            <span class="text-gray-700 dark:text-gray-300">{{ $inc->name }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $inc->duration_minutes }} min</span>
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 italic py-2">No services included</p>
                        @endforelse
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-600">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $included->count() }} service(s) · {{ $totalDuration }} min total
                        </div>
                        <div class="text-lg font-bold text-purple-600 dark:text-purple-400">
                            ₱{{ number_format($package->price, 2) }}
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- Add Package Placeholder --}}
                <button onclick="openServiceModal(true)" class="border-2 border-dashed border-purple-200 dark:border-purple-900/30 rounded-xl p-6 flex flex-col items-center justify-center text-purple-400 hover:text-purple-600 hover:border-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/10 transition min-h-[200px]">
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="font-medium">Create Package</span>
                </button>
            </div>
        </div>

        {{-- CATEGORIES TAB --}}
        <div id="panel-categories" class="tab-panel p-6 hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($categories as $cat)
                @php
                    $catServices = $services->where('category_id', $cat->id);
                    $catPackages = $catServices->where('is_package', true);
                    $catSingle = $catServices->where('is_package', false);
                @endphp
                <div class="bg-white dark:bg-gray-700 rounded-xl border border-gray-100 dark:border-gray-600 p-5 hover:shadow-lg transition">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-sm" style="background-color: {{ $cat->color ?? '#7c9684' }}">
                            {{ strtoupper(substr($cat->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-900 dark:text-white truncate">{{ $cat->name }}</h3>
                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                <span>{{ $catSingle->count() }} services</span>
                                @if($catPackages->count() > 0)
                                    <span class="text-purple-500">· {{ $catPackages->count() }} package(s)</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-0.5">
                            <button onclick="openEditCategoryModal({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->color }}')" class="p-1.5 text-gray-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20 rounded transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                            @if($cat->is_active)
                                <button onclick="confirmDeactivateCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')" class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded transition" title="Deactivate">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                </button>
                            @else
                                <button onclick="confirmActivateCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')" class="p-1.5 text-gray-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20 rounded transition" title="Activate">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            @endif
                            <button onclick="confirmDeleteCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', {{ $cat->services_count ?? 0 }})" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1">
                        @forelse($catServices->take(6) as $svc)
                        <div class="flex items-center justify-between text-sm py-1.5 px-3 bg-gray-50 dark:bg-gray-600/30 rounded-lg">
                            <div class="flex items-center gap-2 min-w-0">
                                @if($svc->is_package)
                                    <span class="shrink-0 text-[10px] font-bold text-purple-600 bg-purple-100 dark:bg-purple-900 px-1.5 py-0.5 rounded">PKG</span>
                                @endif
                                <span class="truncate {{ !$svc->is_active ? 'line-through opacity-50' : 'text-gray-700 dark:text-gray-300' }}">{{ $svc->name }}</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">₱{{ number_format($svc->price, 0) }}</span>
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 italic py-2 text-center">No services</p>
                        @endforelse
                        @if($catServices->count() > 6)
                        <p class="text-xs text-teal-600 dark:text-teal-400 text-center py-1 font-medium">+{{ $catServices->count() - 6 }} more</p>
                        @endif
                    </div>
                </div>
                @endforeach

                <button onclick="openCategoryCreateModal()" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 flex flex-col items-center justify-center text-gray-500 hover:text-teal-600 hover:border-teal-500 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition min-h-[200px]">
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span class="font-medium">Add Category</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ==================== UPGRADED SERVICE MODAL ==================== --}}
<div id="serviceModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-xl mx-auto max-h-[92vh] flex flex-col border border-gray-100 dark:border-gray-700 overflow-hidden">

        {{-- Header --}}
        <div class="p-5 border-b dark:border-gray-700 flex justify-between items-center shrink-0 bg-gradient-to-r from-teal-50 to-white dark:from-gray-700 dark:to-gray-800">
            <div class="flex items-center gap-3">
                <div id="modalIcon" class="w-10 h-10 rounded-xl bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                </div>
                <h2 id="modalTitle" class="text-xl font-bold text-gray-800 dark:text-white">Add Service</h2>
            </div>
            <button type="button" onclick="closeServiceModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="overflow-y-auto p-5 flex-1 space-y-5">
            <form id="serviceForm" action="{{ route('admin.services.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div id="methodField"></div>

                @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-xl text-sm">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                {{-- Section: Basic Info --}}
                <div class="bg-gray-50 dark:bg-gray-700/40 rounded-xl p-4 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Basic Info
                    </h3>
                    <div>
                        <label class="block mb-1.5 text-sm font-semibold dark:text-gray-200">Service Name</label>
                        <input type="text" name="name" id="serviceName" class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none" placeholder="e.g. Swedish Massage" required>
                    </div>
                    <div>
                        <label class="block mb-1.5 text-sm font-semibold dark:text-gray-200">Category</label>
                        <select name="category_id" id="serviceCategory" class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none" required>
                            <option value="">Select a category...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Section: Pricing --}}
                <div class="bg-gray-50 dark:bg-gray-700/40 rounded-xl p-4 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Pricing & Duration
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1.5 text-sm font-semibold dark:text-gray-200">Duration</label>
                            <div class="relative">
                                <input type="number" name="duration_minutes" id="serviceDuration" value="60" min="15" max="480" class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none pr-12" required>
                                <span class="absolute right-3 top-2.5 text-gray-400 text-sm">min</span>
                            </div>
                        </div>
                        <div>
                            <label class="block mb-1.5 text-sm font-semibold dark:text-gray-200">Base Price</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-gray-400 text-sm">₱</span>
                                <input type="number" name="price" id="servicePrice" min="0" step="0.01" class="w-full border rounded-lg p-2.5 pl-8 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none" placeholder="0.00" required oninput="updateDiscountPreview()">
                            </div>
                        </div>
                    </div>

                    {{-- Discount --}}
                    <div class="border-t border-gray-200 dark:border-gray-600 pt-3">
                        <label class="block mb-1.5 text-sm font-semibold dark:text-gray-200">Discount</label>
                        <div class="flex gap-3 items-start">
                            <div class="relative w-32 shrink-0">
                                <input type="number" name="discount_percent" id="serviceDiscountPercent" min="0" max="100" value="0" class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white pr-10 focus:ring-2 focus:ring-teal-500 outline-none" oninput="updateDiscountPreview()">
                                <span class="absolute right-3 top-2.5 text-gray-400">%</span>
                            </div>
                            <div id="discountPreview" class="hidden flex-1 bg-teal-50 dark:bg-teal-900/20 rounded-lg px-3 py-2 border border-teal-100 dark:border-teal-800">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Final price after discount</div>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-lg font-bold text-teal-600 dark:text-teal-400">₱<span id="finalPrice">0.00</span></span>
                                    <span class="text-xs text-gray-400">save ₱<span id="saveAmount">0.00</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="discount_price" id="discountPriceValue">
                </div>

                {{-- Section: Deposit --}}
                <div class="bg-amber-50/60 dark:bg-amber-900/15 rounded-xl p-4 space-y-3 border border-amber-100 dark:border-amber-800/50">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Deposit Estimate
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Min %</label>
                            <div class="relative">
                                <input type="number" name="deposit_percentage_min" id="serviceDepositMin" min="0" max="100" value="0" class="w-full border border-amber-200 dark:border-amber-700 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-amber-500 outline-none pr-10" oninput="updateDepositPreview()">
                                <span class="absolute right-3 top-2.5 text-gray-400">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 block">Max %</label>
                            <div class="relative">
                                <input type="number" name="deposit_percentage_max" id="serviceDepositMax" min="0" max="100" value="0" class="w-full border border-amber-200 dark:border-amber-700 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-amber-500 outline-none pr-10" oninput="updateDepositPreview()">
                                <span class="absolute right-3 top-2.5 text-gray-400">%</span>
                            </div>
                        </div>
                    </div>
                    <div id="depositPreview" class="hidden bg-white dark:bg-gray-700 rounded-lg p-3 border border-amber-200 dark:border-amber-700">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Customer sees on booking:</p>
                        <p class="font-bold text-amber-700 dark:text-amber-400 text-sm">Deposit: <span id="depositRangeText">0% - 0%</span></p>
                        <p class="text-teal-600 dark:text-teal-400 font-bold text-sm">₱<span id="depositPesoMin">0</span> - ₱<span id="depositPesoMax">0</span></p>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Set both to 0 if no deposit is required.</p>
                </div>

                {{-- Section: Descriptions --}}
                <div class="bg-gray-50 dark:bg-gray-700/40 rounded-xl p-4 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                        Descriptions
                    </h3>
                    <div>
                        <label class="block mb-1.5 text-sm font-semibold dark:text-gray-200">Full Description</label>
                        <textarea name="description" id="serviceDescription" rows="2" class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none resize-none" placeholder="Detailed description for admin view..."></textarea>
                    </div>
                    <div>
                        <label class="block mb-1.5 text-sm font-semibold dark:text-gray-200">Landing Page Description</label>
                        <textarea name="landing_description" id="serviceLandingDescription" rows="2" class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none resize-none" placeholder="Short promo text for homepage (optional)"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use the full description on the landing page.</p>
                    </div>
                </div>

                {{-- Section: Image --}}
                <div class="bg-gray-50 dark:bg-gray-700/40 rounded-xl p-4 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Image
                    </h3>
                    <input type="file" name="image" id="serviceImage" accept="image/*" class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 dark:file:bg-teal-900 dark:file:text-teal-300 transition cursor-pointer">
                    <div id="currentImagePreview" class="hidden">
                        <div class="flex items-center gap-3 p-3 bg-white dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600">
                            <img id="currentImageImg" src="" class="h-16 w-16 rounded-lg object-cover border dark:border-gray-600">
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Current image</p>
                                <label class="flex items-center gap-1.5 text-xs text-red-500 cursor-pointer hover:text-red-600 transition">
                                    <input type="checkbox" name="remove_image" id="serviceRemoveImage" value="1" class="rounded border-gray-300 text-red-500 focus:ring-red-500">
                                    Remove this image
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section: Package & Visibility --}}
                <div class="bg-gray-50 dark:bg-gray-700/40 rounded-xl p-4 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Settings
                    </h3>

                    <label class="flex items-center gap-3 p-3 bg-white dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 cursor-pointer hover:border-teal-300 dark:hover:border-teal-700 transition">
                        <input type="checkbox" name="is_package" id="serviceIsPackage" value="1" class="h-5 w-5 text-purple-600 focus:ring-purple-500 border-gray-300 rounded" onchange="togglePackageServices()">
                        <div>
                            <span class="text-sm font-semibold dark:text-gray-200 block">This is a Package</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Combine multiple services into one offering</span>
                        </div>
                    </label>

                    <div id="packageServices" class="hidden">
                        <label class="block mb-2 text-sm font-semibold dark:text-gray-200">Included Services</label>
                        <div class="border rounded-xl dark:bg-gray-700 dark:border-gray-600 max-h-40 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-600">
                            @foreach($singleServices as $s)
                                <label class="flex items-center p-3 hover:bg-purple-50 dark:hover:bg-purple-900/20 cursor-pointer transition">
                                    <input type="checkbox" name="included_services[]" value="{{ $s->id }}" class="package-checkbox mr-3 h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                    <div class="flex-1">
                                        <span class="text-sm dark:text-gray-200 font-medium">{{ $s->name }}</span>
                                        <span class="text-xs text-gray-400 ml-2">{{ $s->duration_minutes }} min · ₱{{ number_format($s->price, 0) }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @if($singleServices->isEmpty())
                            <p class="text-xs text-gray-500 mt-2 italic">No active single services available to include.</p>
                        @endif
                    </div>

                    <div class="flex gap-4 pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="show_on_landing" id="serviceShowOnLanding" value="1" checked class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                            <span class="text-sm dark:text-gray-200">Show on landing page</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" id="serviceIsActive" value="1" checked class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                            <span class="text-sm dark:text-gray-200">Active</span>
                        </label>
                    </div>
                </div>
            </form>
        </div>

        {{-- Footer --}}
        <div class="p-5 border-t dark:border-gray-700 flex justify-end gap-2 shrink-0 bg-white dark:bg-gray-800">
            <button type="button" onclick="closeServiceModal()" class="px-5 py-2.5 border rounded-xl transition-colors hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white font-medium">Cancel</button>
            <button type="submit" form="serviceForm" class="px-5 py-2.5 bg-teal-600 text-white rounded-xl hover:bg-teal-700 font-medium shadow-lg shadow-teal-200 dark:shadow-none transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Save
            </button>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div id="deleteModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-sm text-center shadow-2xl border-t-4 border-red-500">
        <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h2 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Delete Service?</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-1">Permanently remove <span id="deleteServiceName" class="font-semibold text-gray-900 dark:text-white"></span>?</p>
        <p class="text-red-500 text-sm mb-5 font-medium">This action cannot be undone.</p>
        <form id="deleteForm" action="" method="POST" class="flex justify-center gap-2">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeDeleteModal()" class="px-5 py-2.5 border rounded-xl transition-colors hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white font-medium">Cancel</button>
            <button type="submit" class="px-5 py-2.5 bg-red-500 text-white rounded-xl hover:bg-red-600 font-bold shadow-lg shadow-red-200 dark:shadow-none transition">Yes, Delete</button>
        </form>
    </div>
</div>

{{-- Hidden Data for Edit --}}
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
         data-image="{{ $service->image ?? '' }}"
         data-deposit-min="{{ $service->deposit_percentage_min ?? 0 }}"
         data-deposit-max="{{ $service->deposit_percentage_max ?? 0 }}">
    </div>
    @endforeach
</div>

{{-- Category Edit Modal --}}
<div id="categoryEditModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[60] p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-sm shadow-2xl">
        <h2 class="text-xl font-bold mb-5 text-teal-600 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            Edit Category
        </h2>
        <form id="editCategoryForm" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block mb-1.5 text-sm font-medium dark:text-gray-300">Category Name</label>
                <input type="text" name="name" id="editCatName" required class="w-full border rounded-xl p-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div class="mb-6">
                <label class="block mb-1.5 text-sm font-medium dark:text-gray-300">Theme Color</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="color" id="editCatColor" class="w-16 h-12 rounded-xl cursor-pointer border-none bg-transparent">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Pick a color for appointment labels</span>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeCategoryEditModal()" class="px-5 py-2.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition font-medium">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition font-medium shadow-lg shadow-teal-200 dark:shadow-none">Update</button>
            </div>
        </form>
    </div>
</div>

{{-- Category Deactivate Modal --}}
<div id="categoryDeactivateModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[60] p-4">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl max-w-sm w-full text-center border-t-4 border-amber-500 shadow-xl">
        <div class="w-14 h-14 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
        </div>
        <h2 class="text-xl font-bold mb-2 text-amber-600">Deactivate Category?</h2>
        <p class="mb-6 dark:text-gray-300 text-sm">Hide "<span id="deactivateCatName" class="font-bold"></span>" and all its services from the booking page?</p>
        <form id="deactivateCategoryForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_active" value="0">
            <div class="flex gap-2">
                <button type="button" onclick="closeCategoryDeactivateModal()" class="flex-1 border p-2.5 rounded-xl dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancel</button>
                <button type="submit" class="flex-1 bg-amber-500 text-white p-2.5 rounded-xl hover:bg-amber-600 transition font-medium shadow-lg shadow-amber-200 dark:shadow-none">Deactivate</button>
            </div>
        </form>
    </div>
</div>

{{-- Category Activate Modal --}}
<div id="categoryActivateModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[60] p-4">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl max-w-sm w-full text-center border-t-4 border-teal-500 shadow-xl">
        <div class="w-14 h-14 bg-teal-100 dark:bg-teal-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        </div>
        <h2 class="text-xl font-bold mb-2 text-teal-600">Activate Category?</h2>
        <p class="mb-6 dark:text-gray-300 text-sm">Make "<span id="activateCatName" class="font-bold"></span>" visible on the booking page again?</p>
        <form id="activateCategoryForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_active" value="1">
            <div class="flex gap-2">
                <button type="button" onclick="closeCategoryActivateModal()" class="flex-1 border p-2.5 rounded-xl dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancel</button>
                <button type="submit" class="flex-1 bg-teal-600 text-white p-2.5 rounded-xl hover:bg-teal-700 transition font-medium shadow-lg shadow-teal-200 dark:shadow-none">Activate</button>
            </div>
        </form>
    </div>
</div>

{{-- Category Delete Modal --}}
<div id="categoryDeleteModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[110] p-4">
    <div class="bg-white dark:bg-gray-800 p-8 rounded-3xl max-w-sm w-full shadow-2xl border border-red-100 dark:border-red-900/30">
        <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        </div>
        <h3 class="text-xl font-bold text-center text-red-600 mb-2">Critical Action</h3>
        <p id="delCatWarningText" class="text-center text-gray-500 dark:text-gray-400 mb-6 text-sm leading-relaxed"></p>
        <form id="deleteCategoryForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex flex-col gap-2">
                <button type="submit" id="confirmDeleteBtn" disabled class="w-full py-3 bg-gray-400 text-white rounded-xl font-bold cursor-not-allowed transition-all">
                    Wait (<span id="countdownTimer">5</span>s)
                </button>
                <button type="button" onclick="closeCategoryDeleteModal()" class="w-full py-3 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Status Modal --}}
<div id="statusModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div id="statusModalBorder" class="bg-white dark:bg-gray-800 p-6 rounded-2xl max-w-sm w-full text-center border-t-4 shadow-xl">
        <div id="statusIcon" class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"></div>
        <h2 id="statusModalTitle" class="text-xl font-bold mb-2"></h2>
        <p class="mb-6 dark:text-gray-300 text-sm">Update status for "<span id="statusModalName" class="font-bold"></span>"?</p>
        <form id="statusForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_active" id="statusValue">
            <div class="flex gap-2">
                <button type="button" onclick="closeStatusModal()" class="flex-1 border p-2.5 rounded-xl dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">Cancel</button>
                <button type="submit" id="statusSubmitBtn" class="flex-1 text-white p-2.5 rounded-xl transition font-medium shadow-lg dark:shadow-none">Confirm</button>
            </div>
        </form>
    </div>
</div>

{{-- Category Create Modal --}}
<div id="categoryCreateModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-md shadow-2xl border border-teal-100 dark:border-gray-700 overflow-hidden">
        <div class="bg-teal-600 p-5 text-white flex justify-between items-center">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                New Category
            </h3>
            <button onclick="closeCategoryCreateModal()" class="hover:rotate-90 transition-transform p-1 hover:bg-white/20 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form action="{{ route('admin.categories.store') }}" method="POST" class="p-6 space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Category Name</label>
                <input type="text" name="name" required placeholder="e.g., Massage, Skincare" class="w-full p-3 border dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Color Theme</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="color" value="#0d9488" class="h-12 w-20 rounded-xl cursor-pointer bg-transparent border border-gray-200 dark:border-gray-600">
                    <span class="text-sm text-gray-400">Choose a color for appointment labels</span>
                </div>
            </div>
            <div class="pt-2 flex gap-2">
                <button type="button" onclick="closeCategoryCreateModal()" class="flex-1 py-3 text-gray-500 font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition">Cancel</button>
                <button type="submit" class="flex-1 bg-teal-600 text-white font-semibold py-3 rounded-xl hover:shadow-lg transition shadow-teal-200 dark:shadow-none">Create Category</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ==================== TABS ====================
    function switchTab(tab) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
        document.getElementById('panel-' + tab).classList.remove('hidden');
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('border-teal-600', 'text-teal-600', 'bg-white', 'dark:bg-gray-800', 'dark:text-teal-400');
            b.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'dark:text-gray-400', 'dark:hover:text-gray-200');
        });
        const activeBtn = document.getElementById('tab-' + tab);
        activeBtn.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'dark:text-gray-400', 'dark:hover:text-gray-200');
        activeBtn.classList.add('border-teal-600', 'text-teal-600', 'bg-white', 'dark:bg-gray-800', 'dark:text-teal-400');
    }

    // ==================== SEARCH ====================
    function filterServices(query) {
        const rows = document.querySelectorAll('#servicesTableBody .service-row');
        const term = query.toLowerCase().trim();
        let visible = 0;
        rows.forEach(row => {
            const name = row.dataset.name || '';
            const category = row.dataset.category || '';
            const match = name.includes(term) || category.includes(term);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        const noResults = document.getElementById('noServicesResults');
        if (noResults) noResults.style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
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

    // ==================== SERVICE MODAL ====================
    function openServiceModal(isPackage = false) {
        document.getElementById('modalTitle').textContent = isPackage ? 'Add Package' : 'Add Service';
        document.getElementById('serviceForm').action = '{{ route("admin.services.store") }}';
        document.getElementById('methodField').innerHTML = '';

        // Reset all fields
        document.getElementById('serviceName').value = '';
        document.getElementById('serviceCategory').value = '';
        document.getElementById('serviceDuration').value = '60';
        document.getElementById('servicePrice').value = '';
        document.getElementById('serviceDiscountPercent').value = '0';
        document.getElementById('discountPriceValue').value = '';
        document.getElementById('serviceDescription').value = '';
        document.getElementById('serviceLandingDescription').value = '';
        document.getElementById('serviceShowOnLanding').checked = true;
        document.getElementById('serviceIsPackage').checked = isPackage;
        document.getElementById('serviceIsActive').checked = true;
        document.getElementById('serviceDepositMin').value = '0';
        document.getElementById('serviceDepositMax').value = '0';
        document.getElementById('serviceImage').value = '';
        document.getElementById('currentImagePreview').classList.add('hidden');
        document.getElementById('currentImageImg').src = '';
        document.getElementById('serviceRemoveImage').checked = false;

        const dp = document.getElementById('discountPreview');
        if (dp) dp.classList.add('hidden');
        const dep = document.getElementById('depositPreview');
        if (dep) dep.classList.add('hidden');

        document.querySelectorAll('.package-checkbox').forEach(cb => cb.checked = false);
        togglePackageServices();

        const modal = document.getElementById('serviceModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function editService(id) {
        const data = document.querySelector(`#servicesData div[data-id="${id}"]`);
        if (!data) return;

        const isPackage = data.dataset.isPackage === '1';
        document.getElementById('modalTitle').textContent = isPackage ? 'Edit Package' : 'Edit Service';
        document.getElementById('serviceForm').action = `{{ url('admin/services') }}/${id}`;
        document.getElementById('methodField').innerHTML = '@method("PUT")';

        const price = parseFloat(data.dataset.price) || 0;
        const discountPrice = parseFloat(data.dataset.discount) || 0;

        document.getElementById('serviceName').value = data.dataset.name;
        document.getElementById('serviceCategory').value = data.dataset.category;
        document.getElementById('serviceDuration').value = data.dataset.duration;
        document.getElementById('servicePrice').value = price;
        document.getElementById('serviceDescription').value = data.dataset.description || '';
        document.getElementById('serviceLandingDescription').value = data.dataset.landingDescription || '';
        document.getElementById('serviceShowOnLanding').checked = data.dataset.showOnLanding === '1';
        document.getElementById('serviceIsPackage').checked = isPackage;
        document.getElementById('serviceIsActive').checked = data.dataset.isActive === '1';
        document.getElementById('serviceDepositMin').value = data.dataset.depositMin || 0;
        document.getElementById('serviceDepositMax').value = data.dataset.depositMax || 0;

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

        // FIX: Parse included services properly
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
            cb.checked = included.includes(parseInt(cb.value)) || included.includes(cb.value);
        });
        togglePackageServices();

        const modal = document.getElementById('serviceModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeServiceModal() {
        const modal = document.getElementById('serviceModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function togglePackageServices() {
        const isPackage = document.getElementById('serviceIsPackage').checked;
        document.getElementById('packageServices').classList.toggle('hidden', !isPackage);
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
            title.textContent = 'Activate Service?';
            title.className = 'text-xl font-bold mb-2 text-teal-600';
            border.style.borderColor = '#0d9488';
            button.className = 'flex-1 bg-teal-600 text-white p-2.5 rounded-xl hover:bg-teal-700 transition font-medium shadow-lg shadow-teal-200 dark:shadow-none';
            iconDiv.className = 'w-14 h-14 bg-teal-100 dark:bg-teal-900/30 rounded-full flex items-center justify-center mx-auto mb-4';
            iconDiv.innerHTML = '<svg class="w-7 h-7 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        } else {
            title.textContent = 'Deactivate Service?';
            title.className = 'text-xl font-bold mb-2 text-amber-600';
            border.style.borderColor = '#f59e0b';
            button.className = 'flex-1 bg-amber-500 text-white p-2.5 rounded-xl hover:bg-amber-600 transition font-medium shadow-lg shadow-amber-200 dark:shadow-none';
            iconDiv.className = 'w-14 h-14 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mx-auto mb-4';
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
            warningText.innerHTML = `⚠️ <strong>BLOCKED:</strong> Cannot delete <span class="text-red-600 font-bold">${name}</span>. It contains <strong>${serviceCount} service(s)</strong>. You must move or delete those services first.`;
            btn.disabled = true;
            btn.className = 'w-full py-3 bg-gray-400 text-white rounded-xl font-bold cursor-not-allowed transition-all';
            btn.innerHTML = 'Delete Disabled';
        } else {
            warningText.innerHTML = `Are you sure you want to delete <span class="font-bold text-teal-600">${name}</span>? This category will be permanently removed.`;
            let timeLeft = 5;
            btn.disabled = true;
            btn.className = 'w-full py-3 bg-gray-400 text-white rounded-xl font-bold cursor-not-allowed transition-all';
            btn.innerHTML = `Wait (<span id="countdownTimer">${timeLeft}</span>s)`;
            catDeleteTimer = setInterval(() => {
                timeLeft--;
                const timerSpan = document.getElementById('countdownTimer');
                if (timerSpan) timerSpan.textContent = timeLeft;
                if (timeLeft <= 0) {
                    clearInterval(catDeleteTimer);
                    btn.disabled = false;
                    btn.innerHTML = 'Confirm Delete';
                    btn.className = 'w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-red-200 dark:shadow-none';
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