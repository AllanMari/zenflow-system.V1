@extends('layouts.admin')

@section('title', 'Room Categories')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Room Categories</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage physical room classifications, codes, and requirements</p>
        </div>
        <button onclick="openCategoryModal()" 
                class="bg-teal-600 hover:bg-teal-700 text-white px-5 py-2.5 rounded-lg font-semibold text-sm shadow-lg shadow-teal-200 dark:shadow-none transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Room Category
        </button>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 p-4 rounded-xl text-sm font-medium flex items-center justify-between">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 p-4 rounded-xl text-sm font-medium flex items-center justify-between">
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 p-4 rounded-xl text-sm font-medium">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($roomCategories as $category)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 transition hover:shadow-md flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded-md text-xs font-mono font-bold bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-700">
                                {{ $category->code }}
                            </span>
                            @if($category->is_active)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">Active</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Inactive</span>
                            @endif
                        </div>
                        <div class="flex gap-1">
                            <button onclick='editCategory(@json($category))' 
                                    class="p-1.5 text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Edit Category">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button onclick="confirmDeleteCategory('{{ route('admin.room-categories.destroy', $category) }}', '{{ addslashes($category->name) }}', {{ $category->rooms_count ?? 0 }}, {{ $category->services_count ?? 0 }})" 
                                    class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Delete Category">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1.5">{{ $category->name }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-2">{{ $category->description ?? 'No description provided.' }}</p>
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-4">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <strong>{{ $category->rooms_count ?? 0 }}</strong> {{ Str::plural('Room', $category->rooms_count ?? 0) }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <strong>{{ $category->services_count ?? 0 }}</strong> {{ Str::plural('Service', $category->services_count ?? 0) }}
                        </span>
                    </div>
                    <form method="POST" action="{{ route('admin.room-categories.update', $category) }}" class="inline">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="name" value="{{ $category->name }}">
                        <input type="hidden" name="code" value="{{ $category->code }}">
                        <input type="hidden" name="description" value="{{ $category->description }}">
                        <input type="hidden" name="is_active" value="{{ $category->is_active ? '0' : '1' }}">
                        <button type="submit" class="text-xs font-semibold {{ $category->is_active ? 'text-amber-600 hover:text-amber-700' : 'text-teal-600 hover:text-teal-700' }} transition underline">
                            {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-16 bg-white dark:bg-gray-800 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <p class="text-gray-500 dark:text-gray-400 font-semibold">No room categories created yet</p>
                <p class="text-xs text-gray-400 mt-1">Create categories to classify rooms for single, couple, or VIP treatments</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Category Modal -->
<div id="categoryModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div id="categoryBackdrop" class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity duration-300 opacity-0" onclick="closeCategoryModal()"></div>
    <div id="categoryPanel" class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-2xl border border-gray-100 dark:border-gray-700 transform transition-all duration-300 scale-95 opacity-0 mx-4">
        
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold dark:text-white" id="modalTitle">Add Room Category</h2>
            <button type="button" onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="categoryForm" method="POST" class="space-y-4">
            @csrf
            <div id="methodField"></div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Category Name</label>
                <input type="text" name="name" id="catName" required
                       class="w-full border border-gray-200 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent outline-none transition"
                       placeholder="e.g. Regular Single, Couple Suite, VIP Lounge">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Unique Code</label>
                <input type="text" name="code" id="catCode" required
                       class="w-full border border-gray-200 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent outline-none transition uppercase font-mono"
                       placeholder="e.g. REGULAR, COUPLE, VIP">
                <p class="text-xs text-gray-400 mt-1">Unique slug/identifier for system matching</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <textarea name="description" id="catDescription" rows="2"
                          class="w-full border border-gray-200 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent outline-none transition resize-none"
                          placeholder="Brief description of room type features..."></textarea>
            </div>

            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                <input type="checkbox" name="is_active" id="catActive" value="1" checked
                       class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600">
                <label for="catActive" class="text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">Category is active</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeCategoryModal()" class="flex-1 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-gray-700 dark:text-gray-300 font-semibold text-sm">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition font-semibold text-sm shadow-lg shadow-teal-200 dark:shadow-none">Save Category</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteCatForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
<script>
    const categoryModal = document.getElementById('categoryModal');
    const categoryBackdrop = document.getElementById('categoryBackdrop');
    const categoryPanel = document.getElementById('categoryPanel');
    const categoryForm = document.getElementById('categoryForm');
    const methodField = document.getElementById('methodField');

    function openCategoryModal() {
        categoryModal.classList.remove('hidden');
        void categoryModal.offsetWidth;
        categoryBackdrop.classList.remove('opacity-0');
        categoryPanel.classList.remove('scale-95', 'opacity-0');
        categoryPanel.classList.add('scale-100', 'opacity-100');
    }

    function closeCategoryModal() {
        categoryBackdrop.classList.add('opacity-0');
        categoryPanel.classList.remove('scale-100', 'opacity-100');
        categoryPanel.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            categoryModal.classList.add('hidden');
            resetCategoryForm();
        }, 300);
    }

    function resetCategoryForm() {
        document.getElementById('modalTitle').textContent = 'Add Room Category';
        categoryForm.action = '{{ route('admin.room-categories.store') }}';
        methodField.innerHTML = '';
        document.getElementById('catName').value = '';
        document.getElementById('catCode').value = '';
        document.getElementById('catDescription').value = '';
        document.getElementById('catActive').checked = true;
    }

    function editCategory(category) {
        document.getElementById('modalTitle').textContent = 'Edit Room Category';
        categoryForm.action = `/admin/room-categories/${category.id}`;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        
        document.getElementById('catName').value = category.name;
        document.getElementById('catCode').value = category.code;
        document.getElementById('catDescription').value = category.description ?? '';
        document.getElementById('catActive').checked = category.is_active;
        
        openCategoryModal();
    }

    function confirmDeleteCategory(actionUrl, name, roomCount, serviceCount) {
        if (roomCount > 0 || serviceCount > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Delete Category',
                text: `"${name}" cannot be deleted because it is still assigned to ${roomCount} room(s) and referenced by ${serviceCount} service(s). Reassign them first or deactivate the category instead.`,
                confirmButtonColor: '#0d9488'
            });
            return;
        }

        swalConfirmStandard({
            title: `Delete "${name}"?`,
            text: 'This action cannot be undone.',
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#ef4444'
        }).then((confirmed) => {
            if (confirmed) {
                const form = document.getElementById('deleteCatForm');
                form.action = actionUrl;
                form.submit();
            }
        });
    }
</script>
@endpush
@endsection
