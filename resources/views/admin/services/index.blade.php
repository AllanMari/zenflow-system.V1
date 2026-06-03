@extends('layouts.admin')

@section('title', 'Services Management')

@section('content')
<div class="bg-white dark:bg-gray-800 dark:text-white rounded shadow p-6 transition-colors duration-300">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-teal-600">Services</h1>
        <button onclick="openServiceModal()" class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700 transition">+ Add Service</button>
    </div>

    @if(session('success'))
    <div id="success-notification" 
         class="fixed top-5 right-5 z-[100] transform transition-all duration-700 ease-out translate-x-full opacity-0
                bg-white text-gray-800 border-gray-200 
                dark:bg-gray-900 dark:text-white dark:border-teal-900
                p-4 rounded-lg shadow-2xl border-l-4 border-l-teal-500 flex items-center gap-3">
        <div class="bg-teal-100 dark:bg-teal-900/50 p-1 rounded-full">
            <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <span class="font-medium text-sm">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Search -->
    <div class="mb-4 relative">
        <input type="text" id="searchInput" placeholder="Search services..." 
            class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"
            autocomplete="off">
        <div id="searchSuggestions" class="absolute bg-white dark:bg-gray-700 border dark:border-gray-600 rounded shadow-lg w-full hidden z-10 dark:text-white"></div>
    </div>

    <!-- Services Table -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700">
                    <th class="border dark:border-gray-600 p-3 text-left">ID</th>
                    <th class="border dark:border-gray-600 p-3 text-left">Name</th>
                    <th class="border dark:border-gray-600 p-3 text-left">Category</th>
                    <th class="border dark:border-gray-600 p-3 text-left">Duration</th>
                    <th class="border dark:border-gray-600 p-3 text-left">Price</th>
                    <th class="border dark:border-gray-600 p-3 text-left">Status</th>
                    <th class="border dark:border-gray-600 p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($services as $service)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 service-row transition-colors">
                    <td class="border dark:border-gray-700 p-3">{{ $service->id }}</td>
                    
                    <td class="border dark:border-gray-700 p-3 font-medium">
                        {{ $service->name }}
                        @if($service->is_package)
                            <span class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 px-2 py-1 rounded text-xs font-bold uppercase ml-2">Package</span>
                        @endif
                    </td>
                    
                    <td class="border dark:border-gray-700 p-3">
                        <span class="inline-block w-3 h-3 rounded-full mr-2" style="background-color: {{ $service->category->color ?? '#7c9684' }}"></span>
                        {{ $service->category->name }}
                    </td>
                    
                    <td class="border dark:border-gray-700 p-3 text-sm">{{ $service->duration_minutes }} min</td>
                    
                    <td class="border dark:border-gray-700 p-3">
                        @if($service->discount_price)
                            <span class="line-through text-gray-400 text-sm">₱{{ number_format($service->price, 2) }}</span><br>
                            <span class="text-teal-600 dark:text-teal-400 font-medium">₱{{ number_format($service->discount_price, 2) }}</span>
                        @else
                            ₱{{ number_format($service->price, 2) }}
                        @endif
                    </td>
                    
                    <td class="border dark:border-gray-700 p-3">
                        @if($service->is_active)
                            <span class="bg-teal-100 dark:bg-teal-900 text-teal-800 dark:text-teal-200 px-2 py-1 rounded text-xs font-bold uppercase">Active</span>
                        @else
                            <span class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-2 py-1 rounded text-xs font-bold uppercase">Inactive</span>
                        @endif
                    </td>
                    
                    <td class="border dark:border-gray-700 p-3">
                        <div class="flex gap-2">
                                <button onclick="editService({{ $service->id }})" 
                                    class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition text-sm">
                                    Edit
                                </button>

                                @if($service->is_active)
                                    <button onclick="toggleStatus({{ $service->id }}, '{{ addslashes($service->name) }}', 0)" 
                                        class="bg-amber-500 text-white px-3 py-1 rounded hover:bg-amber-600 transition text-sm">
                                        Deactivate
                                    </button>
                                @else
                                    <button onclick="toggleStatus({{ $service->id }}, '{{ addslashes($service->name) }}', 1)" 
                                        class="bg-teal-600 text-white px-3 py-1 rounded hover:bg-teal-700 transition text-sm">
                                        Activate
                                    </button>
                                @endif

                                <button onclick="confirmDelete({{ $service->id }}, '{{ addslashes($service->name) }}')" 
                                    class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition text-sm">
                                    Delete
                                </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit Service Modal -->
<div id="serviceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 dark:text-white rounded-lg shadow-xl transition-colors w-full max-w-md mx-4 max-h-[90vh] flex flex-col">
        <div class="p-6 border-b dark:border-gray-700 flex justify-between items-center shrink-0">
            <h2 id="modalTitle" class="text-2xl font-bold">Add New Service</h2>
            <button type="button" onclick="closeServiceModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        <div class="overflow-y-auto p-6 flex-1">
            <form id="serviceForm" action="{{ route('admin.services.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div id="methodField"></div>

                @if($errors->any())
                <div class="bg-red-100 text-red-700 p-3 mb-4 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="mb-4">
                    <label class="block mb-1 font-semibold dark:text-gray-200">Service Name</label>
                    <input type="text" name="name" id="serviceName" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-semibold dark:text-gray-200">Category</label>
                    <select name="category_id" id="serviceCategory" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block mb-1 font-semibold dark:text-gray-200">Duration (min)</label>
                        <input type="number" name="duration_minutes" id="serviceDuration" value="60" min="15" max="480" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold dark:text-gray-200">Price (₱)</label>
                        <input type="number" name="price" id="servicePrice" min="0" step="0.01" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required oninput="updateDiscountPreview()">
                    </div>
                </div>

                <!-- Discount Percentage with Live Preview -->
                <div class="mb-4">
                    <label class="block mb-1 font-semibold dark:text-gray-200">
                        Discount % <span class="text-xs font-normal text-gray-500">(0-100)</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="discount_percent" id="serviceDiscountPercent" min="0" max="100" value="0"
                               class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pr-16"
                               oninput="updateDiscountPreview()">
                        <span class="absolute right-3 top-2 text-gray-400">%</span>
                    </div>
                    <div id="discountPreview" class="mt-2 text-sm hidden">
                        <span class="text-gray-500">Final price: </span>
                        <span class="font-bold text-teal-600 dark:text-teal-400 text-lg">₱<span id="finalPrice">0.00</span></span>
                        <span class="text-gray-400 text-xs ml-2">(Save ₱<span id="saveAmount">0.00</span>)</span>
                    </div>
                </div>

                <input type="hidden" name="discount_price" id="discountPriceValue">

                <!-- Deposit Estimate Range -->
                <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <label class="block mb-2 font-semibold dark:text-amber-300 text-amber-800">
                        Deposit Estimate Range
                    </label>
                    
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">Min %</label>
                            <div class="relative">
                                <input type="number" name="deposit_percentage_min" id="serviceDepositMin" min="0" max="100" value="0"
                                    class="w-full border border-amber-300 dark:border-amber-700 rounded p-2 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white pr-12"
                                    oninput="updateDepositPreview()">
                                <span class="absolute right-3 top-2 text-gray-400">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">Max %</label>
                            <div class="relative">
                                <input type="number" name="deposit_percentage_max" id="serviceDepositMax" min="0" max="100" value="0"
                                    class="w-full border border-amber-300 dark:border-amber-700 rounded p-2 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white pr-12"
                                    oninput="updateDepositPreview()">
                                <span class="absolute right-3 top-2 text-gray-400">%</span>
                            </div>
                        </div>
                    </div>

                    <div id="depositPreview" class="text-sm hidden">
                        <div class="bg-white dark:bg-gray-700 rounded p-2 border border-amber-200 dark:border-amber-800">
                            <p class="text-gray-500 dark:text-gray-400 text-xs mb-1">Customer sees:</p>
                            <p class="font-bold text-amber-600 dark:text-amber-400">
                                Deposit estimate: <span id="depositRangeText">0% - 0%</span>
                            </p>
                            <p class="text-teal-600 dark:text-teal-400 font-bold mt-1">
                                ₱<span id="depositPesoMin">0</span> - ₱<span id="depositPesoMax">0</span>
                            </p>
                        </div>
                    </div>
                    
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Set both to 0 for no deposit. If empty, falls back to category deposit %.
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-semibold dark:text-gray-200">Description</label>
                    <textarea name="description" id="serviceDescription" rows="2" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-semibold dark:text-gray-200">Landing Page Description</label>
                    <textarea name="landing_description" id="serviceLandingDescription" rows="2" 
                        class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="Short promo text for homepage (optional)"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Leave empty to use full description on landing page.</p>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-semibold dark:text-gray-200">Service Image</label>
                    <input type="file" name="image" id="serviceImage" accept="image/*"
                        class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 dark:file:bg-teal-900 dark:file:text-teal-300">
                    <div id="currentImagePreview" class="mt-2 hidden">
                        <img id="currentImageImg" src="" class="h-20 rounded-lg object-cover border dark:border-gray-600">
                        <label class="flex items-center gap-1 text-xs text-red-500 mt-1 cursor-pointer">
                            <input type="checkbox" name="remove_image" id="serviceRemoveImage" value="1">
                            Remove current image
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="show_on_landing" id="serviceShowOnLanding" value="1" checked 
                            class="mr-2 h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                        <span class="text-sm font-semibold dark:text-gray-200">Show on Landing Page</span>
                    </label>
                </div>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_package" id="serviceIsPackage" value="1" class="mr-2 h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded" onchange="togglePackageServices()">
                        <span class="text-sm font-semibold dark:text-gray-200">This is a package (multiple services)</span>
                    </label>
                </div>

                <div id="packageServices" class="mb-4 hidden">
                    <label class="block mb-1 font-semibold dark:text-gray-200">Include Services</label>
                    <div class="border rounded p-2 dark:bg-gray-700 dark:border-gray-600 max-h-32 overflow-y-auto">
                        @foreach($singleServices as $s)
                            <label class="flex items-center p-2 hover:bg-gray-50 dark:hover:bg-gray-600 rounded">
                                <input type="checkbox" name="included_services[]" value="{{ $s->id }}" class="package-checkbox mr-2 h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                                <span class="text-sm dark:text-gray-200">{{ $s->name }} ({{ $s->duration_minutes }} min)</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="serviceIsActive" value="1" checked class="mr-2 h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                        <span class="text-sm font-semibold dark:text-gray-200">Active</span>
                    </label>
                </div>
            </form>
        </div>
        
        <div class="p-6 border-t dark:border-gray-700 flex justify-end gap-2 shrink-0 bg-white dark:bg-gray-800 rounded-b-lg">
            <button type="button" onclick="closeServiceModal()" class="px-4 py-2 border rounded transition-colors hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white">Cancel</button>
            <button type="submit" form="serviceForm" class="px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-700">Save Service</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 dark:text-white rounded-lg p-6 w-full max-w-sm text-center shadow-xl border-t-4 border-red-500">
        <h2 class="text-xl font-bold mb-2">Delete Service?</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-4">Are you sure you want to permanently remove <span id="deleteServiceName" class="font-semibold"></span>?</p>
        <p class="text-red-500 text-sm mb-4 font-bold">This action cannot be undone.</p>
        
        <form id="deleteForm" action="" method="POST" class="flex justify-center gap-2">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 border rounded transition-colors hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 font-bold">Yes, Delete</button>
        </form>
    </div>
</div>

<!-- Hidden Data for Edit -->
<div id="servicesData" class="hidden">
    @foreach($services as $service)
    <div data-id="{{ $service->id }}"
         data-name="{{ $service->name }}"
         data-category="{{ $service->category_id }}"
         data-duration="{{ $service->duration_minutes }}"
         data-price="{{ $service->price }}"
         data-discount="{{ $service->discount_price }}"
         data-description="{{ $service->description }}"
         data-is-package="{{ $service->is_package ? '1' : '0' }}"
         data-included="{{ json_encode($service->included_services ?? []) }}"
         data-is-active="{{ $service->is_active ? '1' : '0' }}"
         data-image="{{ $service->image ?? '' }}"
         data-landing-description="{{ $service->landing_description ?? '' }}"
         data-show-on-landing="{{ $service->show_on_landing ? '1' : '0' }}"
         data-deposit-min="{{ $service->deposit_percentage_min ?? 0 }}"
         data-deposit-max="{{ $service->deposit_percentage_max ?? 0 }}">
    </div>
    @endforeach
</div>

<!-- CATEGORY EDIT MODAL -->
<div id="categoryEditModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[60]">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-sm shadow-2xl">
        <h2 class="text-xl font-bold mb-4 text-teal-600">Edit Category</h2>
        <form id="editCategoryForm" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium dark:text-gray-300">Category Name</label>
                <input type="text" name="name" id="editCatName" required
                    class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div class="mb-6">
                <label class="block mb-1 text-sm font-medium dark:text-gray-300">Theme Color</label>
                <input type="color" name="color" id="editCatColor" class="w-full h-10 rounded-lg cursor-pointer border-none bg-transparent">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeCategoryEditModal()" class="px-4 py-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- CATEGORY DEACTIVATE MODAL -->
<div id="categoryDeactivateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[60]">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg max-w-sm w-full text-center border-t-4 border-amber-500 shadow-xl">
        <h2 class="text-xl font-bold mb-4 text-amber-600">Deactivate Category?</h2>
        <p class="mb-6 dark:text-gray-300 text-sm">Hide "<span id="deactivateCatName" class="font-bold"></span>" and its services from the booking page?</p>
        <form id="deactivateCategoryForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_active" value="0">
            <div class="flex gap-2">
                <button type="button" onclick="closeCategoryDeactivateModal()" class="flex-1 border p-2 rounded dark:text-white">Cancel</button>
                <button type="submit" class="flex-1 bg-amber-500 text-white p-2 rounded">Deactivate</button>
            </div>
        </form>
    </div>
</div>

<div id="categoryActivateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[60]">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg max-w-sm w-full text-center border-t-4 border-teal-500 shadow-xl">
        <h2 class="text-xl font-bold mb-4 text-teal-600">Activate Category?</h2>
        <p class="mb-6 dark:text-gray-300 text-sm">Make "<span id="activateCatName" class="font-bold"></span>" visible again?</p>
        <form id="activateCategoryForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_active" value="1">
            <div class="flex gap-2">
                <button type="button" onclick="closeCategoryActivateModal()" class="flex-1 border p-2 rounded dark:text-white">Cancel</button>
                <button type="submit" class="flex-1 bg-teal-600 text-white p-2 rounded">Activate</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE MODAL -->
<div id="categoryDeleteModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[110] p-4">
    <div class="bg-white dark:bg-gray-800 p-8 rounded-3xl max-w-sm w-full shadow-2xl border border-red-100 dark:border-red-900/30">
        <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        </div>
        
        <h3 class="text-xl font-bold text-center text-gray-900 dark:text-white mb-2 text-red-600">Critical Action</h3>
        
        <p id="delCatWarningText" class="text-center text-gray-500 dark:text-gray-400 mb-6 text-sm leading-relaxed"></p>

        <form id="deleteCategoryForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex flex-col gap-2">
                <button type="submit" id="confirmDeleteBtn" disabled
                        class="w-full py-3 bg-gray-400 text-white rounded-xl font-bold cursor-not-allowed transition-all">
                    Wait (<span id="countdownTimer">5</span>s)
                </button>
                <button type="button" onclick="closeCategoryDeleteModal()" 
                        class="w-full py-3 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div id="statusModalBorder" class="bg-white dark:bg-gray-800 p-6 rounded-lg max-w-sm w-full text-center border-t-4 shadow-xl">
        <h2 id="statusModalTitle" class="text-xl font-bold mb-4"></h2>
        <p class="mb-6 dark:text-gray-300">Update status for "<span id="statusModalName" class="font-bold"></span>"?</p>
        <form id="statusForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="is_active" id="statusValue">
            <div class="flex gap-2">
                <button type="button" onclick="closeStatusModal()" class="flex-1 border p-2 rounded dark:text-white hover:bg-gray-100">Cancel</button>
                <button type="submit" id="statusSubmitBtn" class="flex-1 text-white p-2 rounded">Confirm</button>
            </div>
        </form>
    </div>
</div>

<!-- CREATE MODAL -->
<div id="categoryCreateModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[100] p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-md shadow-2xl border border-teal-100 dark:border-gray-700 overflow-hidden">
        <div class="bg-teal-600 p-4 text-white flex justify-between items-center">
            <h3 class="font-bold">New Category</h3>
            <button onclick="closeCategoryCreateModal()" class="hover:rotate-90 transition-transform"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>
        <form action="{{ route('admin.categories.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Category Name</label>
                <input type="text" name="name" required placeholder="e.g., Massage, Skincare" class="w-full p-3 border dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Color Theme</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="color" value="#0d9488" class="h-10 w-20 rounded-lg cursor-pointer bg-transparent">
                    <span class="text-sm text-gray-400">Choose a color for the appointment labels</span>
                </div>
            </div>
            <div class="pt-4 flex gap-2">
                <button type="button" onclick="closeCategoryCreateModal()" class="flex-1 py-3 text-gray-500 font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition">Cancel</button>
                <button type="submit" class="flex-1 bg-teal-600 text-white font-semibold py-3 rounded-xl hover:shadow-lg transition">Create Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Categories Side Panel -->
<div class="fixed top-1/2 right-0 -translate-y-1/2 z-[999] group">
    <div class="bg-teal-600 dark:bg-teal-500 text-white py-8 px-2 rounded-l-2xl shadow-lg cursor-pointer transition-all duration-300 group-hover:translate-x-full border-l border-t border-b border-white/20">
        <div class="flex flex-col items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <span class="[writing-mode:vertical-lr] font-bold text-[10px] uppercase tracking-[0.2em] opacity-90">Categories</span>
        </div>
    </div>

    <div class="absolute top-1/2 right-0 -translate-y-1/2 w-80 h-[600px] bg-white dark:bg-gray-800 shadow-[-20px_0_40px_rgba(0,0,0,0.3)] border-l border-t border-b dark:border-gray-700 transition-all duration-300 transform translate-x-full group-hover:translate-x-0 rounded-l-[2.5rem] flex flex-col overflow-hidden pointer-events-none group-hover:pointer-events-auto">
        
        <div class="p-6 border-b dark:border-gray-700 bg-teal-600 text-white flex justify-between items-center">
            <div>
                <h3 class="font-bold text-lg leading-tight">Categories</h3>
                <span class="text-[10px] uppercase tracking-widest opacity-70">Management</span>
            </div>
            <button onclick="openCategoryCreateModal()" class="bg-white/20 hover:bg-white/40 p-2 rounded-xl transition-all shadow-inner" title="Add New Category">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-5 space-y-3 bg-gray-50/50 dark:bg-gray-900/40">
            @forelse($categories as $cat)
                <div class="p-4 bg-white dark:bg-gray-700/60 rounded-2xl border border-gray-100 dark:border-gray-600 flex items-center justify-between group/item hover:border-teal-400 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-center gap-4">
                        <div class="w-3 h-3 rounded-full ring-4 ring-gray-100 dark:ring-gray-800" style="background-color: {{ $cat->color }}"></div>
                        <div>
                            <p class="text-sm font-bold dark:text-white">{{ $cat->name }}</p>
                            <span class="text-[10px] uppercase font-bold {{ $cat->is_active ? 'text-teal-500' : 'text-red-400' }}">
                                {{ $cat->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-0.5 opacity-0 group-hover/item:opacity-100 transition-all">
                        <button onclick="openEditCategoryModal({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->color }}')" class="p-2 text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/30 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        </button>

                        @if($cat->is_active)
                            <button onclick="confirmDeactivateCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')" class="p-2 text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition" title="Deactivate">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                            </button>
                        @else
                            <button onclick="confirmActivateCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')" class="p-2 text-teal-500 hover:bg-teal-50 dark:hover:bg-teal-900/30 rounded-lg transition" title="Activate">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            </button>
                        @endif
                        
                        <button onclick="confirmDeleteCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', {{ $cat->services_count ?? 0 }})" 
                                class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-center text-xs text-gray-400 py-10 italic">No categories.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Live discount preview
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

    // Modal Controls
    function openServiceModal() {
        document.getElementById('modalTitle').textContent = 'Add New Service';
        document.getElementById('serviceForm').action = '{{ route("admin.services.store") }}';
        document.getElementById('methodField').innerHTML = '';
        
        document.getElementById('serviceName').value = '';
        document.getElementById('serviceCategory').value = '';
        document.getElementById('serviceDuration').value = '60';
        document.getElementById('servicePrice').value = '';
        document.getElementById('serviceDiscountPercent').value = '0';
        document.getElementById('discountPriceValue').value = '';
        document.getElementById('serviceDescription').value = '';
        document.getElementById('serviceIsPackage').checked = false;
        document.getElementById('serviceIsActive').checked = true;
        document.getElementById('serviceLandingDescription').value = '';
        document.getElementById('serviceShowOnLanding').checked = true;
        document.getElementById('currentImagePreview').classList.add('hidden');
        document.getElementById('currentImageImg').src = '';
        document.getElementById('serviceRemoveImage').checked = false;
        document.getElementById('serviceDepositMin').value = '0';
        document.getElementById('serviceDepositMax').value = '0';
        
        const preview = document.getElementById('discountPreview');
        if (preview) preview.classList.add('hidden');
        
        const depositPreview = document.getElementById('depositPreview');
        if (depositPreview) depositPreview.classList.add('hidden');
        
        document.querySelectorAll('.package-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('packageServices').classList.add('hidden');
        
        const modal = document.getElementById('serviceModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function editService(id) {
        const data = document.querySelector(`#servicesData div[data-id="${id}"]`);
        if (!data) return;

        document.getElementById('modalTitle').textContent = 'Edit Service';
        document.getElementById('serviceForm').action = `{{ url('admin/services') }}/${id}`;
        document.getElementById('methodField').innerHTML = '@method("PUT")';

        const price = parseFloat(data.dataset.price) || 0;
        const discountPrice = parseFloat(data.dataset.discount) || 0;
        
        document.getElementById('serviceName').value = data.dataset.name;
        document.getElementById('serviceCategory').value = data.dataset.category;
        document.getElementById('serviceDuration').value = data.dataset.duration;
        document.getElementById('servicePrice').value = price;
        document.getElementById('serviceDescription').value = data.dataset.description || '';
        document.getElementById('serviceIsPackage').checked = data.dataset.isPackage === '1';
        document.getElementById('serviceIsActive').checked = data.dataset.isActive === '1';

        // Landing page fields
        document.getElementById('serviceLandingDescription').value = data.dataset.landingDescription || '';
        document.getElementById('serviceShowOnLanding').checked = data.dataset.showOnLanding === '1';

        // Deposit - set values first, then update preview
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

        const percentInput = document.getElementById('serviceDiscountPercent');
        if (discountPrice > 0 && price > 0) {
            const percent = Math.round(((price - discountPrice) / price) * 100);
            percentInput.value = percent;
        } else {
            percentInput.value = 0;
        }
        
        // Update previews AFTER price is set
        updateDiscountPreview();
        updateDepositPreview();

        const included = JSON.parse(data.dataset.included || '[]');
        document.querySelectorAll('.package-checkbox').forEach(cb => {
            cb.checked = included.includes(parseInt(cb.value));
        });
        togglePackageServices();

        const modal = document.getElementById('serviceModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeServiceModal() {
        document.getElementById('serviceModal').classList.add('hidden');
        document.getElementById('serviceModal').classList.remove('flex');
    }

    function togglePackageServices() {
        const isPackage = document.getElementById('serviceIsPackage').checked;
        document.getElementById('packageServices').classList.toggle('hidden', !isPackage);
    }

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const suggestions = document.getElementById('searchSuggestions');
    const tableBody = document.querySelector('tbody');
    const allRows = tableBody.querySelectorAll('tr.service-row');

    const noResultsRow = document.createElement('tr');
    noResultsRow.id = 'noResults';
    noResultsRow.innerHTML = '<td colspan="7" class="border p-3 text-center text-gray-500">No services found</td>';
    noResultsRow.style.display = 'none';
    tableBody.appendChild(noResultsRow);

    function getServicesFromTable() {
        const services = [];
        allRows.forEach(row => {
            services.push({
                id: row.cells[0].textContent.trim(),
                name: row.cells[1].textContent.trim(),
                category: row.cells[2].textContent.trim()
            });
        });
        return services;
    }

    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        
        if (query.length < 1) {
            suggestions.classList.add('hidden');
            resetTable();
            return;
        }
        
        const services = getServicesFromTable();
        const matches = services.filter(s => 
            s.name.toLowerCase().includes(query) || 
            s.category.toLowerCase().includes(query)
        );
        
        suggestions.innerHTML = '';
        if (matches.length === 0) {
            const div = document.createElement('div');
            div.className = 'p-2 text-gray-500';
            div.textContent = 'No services found';
            suggestions.appendChild(div);
        } else {
            matches.forEach(service => {
                const div = document.createElement('div');
                div.className = 'p-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer border-b last:border-b-0 dark:border-gray-600';
                div.textContent = `${service.name} - ${service.category}`;
                div.onclick = function() {
                    searchInput.value = service.name;
                    suggestions.classList.add('hidden');
                    filterTable(service.name);
                };
                suggestions.appendChild(div);
            });
        }
        suggestions.classList.remove('hidden');
        filterTable(query);
    });

    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            suggestions.classList.add('hidden');
            filterTable(this.value);
        }
    });

    function filterTable(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        let visibleCount = 0;
        allRows.forEach(row => {
            const name = row.cells[1].textContent.toLowerCase().trim();
            const category = row.cells[2].textContent.toLowerCase().trim();
            const match = name.includes(term) || category.includes(term);
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });
        document.getElementById('noResults').style.display = visibleCount === 0 ? '' : 'none';
    }

    function resetTable() {
        allRows.forEach(row => {
            row.style.display = '';
        });
    }

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestions.contains(e.target)) {
            suggestions.classList.add('hidden');
        }
    });

    // Notification animation
    window.addEventListener('load', function() {
        const notification = document.getElementById('success-notification');
        if (notification) {
            setTimeout(() => {
                notification.classList.remove('translate-x-full', 'opacity-0');
                notification.classList.add('translate-x-0', 'opacity-100');
            }, 100);
            setTimeout(() => {
                notification.classList.remove('translate-x-0', 'opacity-100');
                notification.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => notification.remove(), 700);
            }, 3100);
        }
    });

    // Category Modal Controls
    function openEditCategoryModal(id, name, color) {
        const modal = document.getElementById('categoryEditModal');
        const form = document.getElementById('editCategoryForm');
        form.action = `/admin/categories/${id}`;
        document.getElementById('editCatName').value = name;
        document.getElementById('editCatColor').value = color;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeCategoryEditModal() {
        document.getElementById('categoryEditModal').classList.add('hidden');
        document.getElementById('categoryEditModal').classList.remove('flex');
    }

    let catDeleteTimer; 

    function confirmDeleteCategory(id, name, serviceCount) {
        const modal = document.getElementById('categoryDeleteModal');
        const form = document.getElementById('deleteCategoryForm');
        const warningText = document.getElementById('delCatWarningText');
        const btn = document.getElementById('confirmDeleteBtn');
        
        form.action = `/admin/categories/${id}`;
        
        if (serviceCount > 0) {
            // MATCH BACKEND BEHAVIOR: Backend blocks deletion if services exist
            warningText.innerHTML = `⚠️ <strong>BLOCKED:</strong> Cannot delete <span class="text-red-600 font-bold">${name}</span>. It contains <strong>${serviceCount} service(s)</strong>. You must move or delete those services first.`;
            
            // Disable the delete button entirely
            btn.disabled = true;
            btn.className = "w-full py-3 bg-gray-400 text-white rounded-xl font-bold cursor-not-allowed transition-all";
            btn.innerHTML = 'Delete Disabled';
            
            // Hide countdown timer since action is blocked
            clearInterval(catDeleteTimer);
        } else {
            warningText.innerHTML = `Are you sure you want to delete <span class="font-bold text-teal-600">${name}</span>?`;
            
            // Enable countdown for actual deletion
            let timeLeft = 5;
            btn.disabled = true;
            btn.className = "w-full py-3 bg-gray-400 text-white rounded-xl font-bold cursor-not-allowed transition-all";
            btn.innerHTML = `Wait (<span id="countdownTimer">${timeLeft}</span>s)`;
            
            clearInterval(catDeleteTimer);
            catDeleteTimer = setInterval(() => {
                timeLeft--;
                const timerSpan = document.getElementById('countdownTimer');
                if (timerSpan) timerSpan.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(catDeleteTimer);
                    btn.disabled = false;
                    btn.innerHTML = 'Confirm Delete';
                    btn.className = "w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-red-200";
                }
            }, 1000);
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeCategoryDeleteModal() {
        clearInterval(catDeleteTimer);
        const modal = document.getElementById('categoryDeleteModal');
        modal.classList.replace('flex', 'hidden');
    }

    function confirmDeactivateCategory(id, name) {
        document.getElementById('deactivateCatName').textContent = name;
        document.getElementById('deactivateCategoryForm').action = `/admin/categories/${id}`;
        const modal = document.getElementById('categoryDeactivateModal');
        modal.classList.replace('hidden', 'flex');
    }

    function closeCategoryDeactivateModal() {
        document.getElementById('categoryDeactivateModal').classList.replace('flex', 'hidden');
    }

    function confirmActivateCategory(id, name) {
        document.getElementById('activateCatName').textContent = name;
        document.getElementById('activateCategoryForm').action = `/admin/categories/${id}`;
        const modal = document.getElementById('categoryActivateModal');
        modal.classList.replace('hidden', 'flex');
    }

    function closeCategoryActivateModal() {
        document.getElementById('categoryActivateModal').classList.replace('flex', 'hidden');
    }

    function toggleStatus(id, name, targetValue) {
        const modal = document.getElementById('statusModal');
        const title = document.getElementById('statusModalTitle');
        const button = document.getElementById('statusSubmitBtn');
        const border = document.getElementById('statusModalBorder');
        
        document.getElementById('statusModalName').textContent = name;
        document.getElementById('statusValue').value = targetValue;
        document.getElementById('statusForm').action = `/admin/services/${id}`;

        if (targetValue === 1) {
            title.textContent = "Activate Service?";
            title.className = "text-xl font-bold mb-4 text-teal-600";
            border.style.borderColor = "#0d9488";
            button.className = "flex-1 bg-teal-600 text-white p-2 rounded hover:bg-teal-700";
        } else {
            title.textContent = "Deactivate Service?";
            title.className = "text-xl font-bold mb-4 text-amber-600";
            border.style.borderColor = "#f59e0b";
            button.className = "flex-1 bg-amber-500 text-white p-2 rounded hover:bg-amber-600";
        }

        modal.classList.replace('hidden', 'flex');
    }

    function closeStatusModal() {
        document.getElementById('statusModal').classList.replace('flex', 'hidden');
    }

    // Service Delete
    function confirmDelete(id, name) {
        document.getElementById('deleteServiceName').textContent = name;
        document.getElementById('deleteForm').action = `/admin/services/${id}`;
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
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

    // Close modals on outside click
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
</script>
@endpush