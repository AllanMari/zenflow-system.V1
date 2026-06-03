@extends(auth()->user()->roles->contains('name', 'admin') ? 'layouts.admin' : 'layouts.receptionist')

@section('title', 'Landing Page Editor')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-teal-100 dark:bg-teal-900/50 rounded-lg">
            <svg class="w-6 h-6 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Landing Page Editor</h1>
    </div>

    <form action="{{ route('admin.landing.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Hero Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 transition-colors">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Hero Section</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Title</label>
                    <input type="text" name="hero_title" value="{{ $heroSettings['hero_title'] }}" 
                        class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Subtitle</label>
                    <input type="text" name="hero_subtitle" value="{{ $heroSettings['hero_subtitle'] }}" 
                        class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>

            <!-- Hero Image -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Background Image</label>
                
                @if($heroSettings['hero_image'])
                <div class="relative rounded-xl overflow-hidden h-48 w-full max-w-lg group mb-3" id="heroPreview">
                    <img src="{{ $heroSettings['hero_image'] }}" class="w-full h-full object-cover transition duration-500" id="heroImg" alt="Hero">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition duration-300"></div>
                    
                    <button type="button" onclick="removeHero()" 
                        class="absolute top-3 right-3 bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium shadow-lg opacity-0 group-hover:opacity-100 transition duration-300 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Remove
                    </button>
                    <input type="hidden" name="remove_hero_image" id="removeHeroInput" value="0">
                </div>
                @endif

                <div class="flex items-center gap-3">
                    <label class="cursor-pointer px-4 py-2.5 bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 rounded-lg hover:bg-teal-100 dark:hover:bg-teal-900/50 transition font-medium text-sm border border-teal-200 dark:border-teal-800">
                        <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Choose New Image
                        <input type="file" name="hero_image" accept="image/*" class="hidden" onchange="updateFileName(this, 'heroFileName')">
                    </label>
                    <span id="heroFileName" class="text-sm text-gray-500 dark:text-gray-400">No image selected</span>
                </div>
            </div>
        </div>

        <!-- Services Showcase -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 transition-colors">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1 flex items-center gap-2">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Services Showcase
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Choose which categories and services appear on the landing page.</p>

            <div class="space-y-4">
                @foreach($categories as $category)
                <div class="border dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $category->color ?? '#0d9488' }}"></div>
                            <h3 class="font-bold text-gray-900 dark:text-white">{{ $category->name }}</h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400">({{ $category->services->count() }})</span>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="categories[{{ $category->id }}][show]" value="1" {{ $category->show_on_landing ? 'checked' : '' }}
                                class="w-4 h-4 text-teal-600 rounded focus:ring-teal-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Show on landing</span>
                        </label>
                    </div>

                    <div class="divide-y dark:divide-gray-700">
                        @foreach($category->services as $service)
                        <div class="p-4 flex flex-col md:flex-row md:items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-2">
                                    <input type="checkbox" name="services[{{ $service->id }}][show]" value="1" {{ $service->show_on_landing ? 'checked' : '' }}
                                        class="w-4 h-4 text-teal-600 rounded focus:ring-teal-500 shrink-0">
                                    <span class="font-semibold text-gray-800 dark:text-gray-200 truncate">{{ $service->name }}</span>
                                    <span class="text-xs text-teal-600 font-bold shrink-0">₱{{ number_format($service->price, 2) }}</span>
                                </div>
                                <input type="text" name="services[{{ $service->id }}][landing_description]" 
                                    value="{{ $service->landing_description }}"
                                    placeholder="Short promo description..."
                                    class="w-full text-sm border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-1 focus:ring-teal-500 outline-none">
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                @if($service->image)
                                    <div class="relative w-14 h-14 rounded-lg overflow-hidden">
                                        <img src="{{ $service->image }}" class="w-full h-full object-cover" alt="">
                                    </div>
                                    <label class="flex items-center gap-1 text-xs text-red-500 cursor-pointer hover:text-red-600 transition">
                                        <input type="checkbox" name="services[{{ $service->id }}][remove_image]" value="1" class="rounded text-red-600 focus:ring-red-500">
                                        Remove
                                    </label>
                                @else
                                    <div class="w-14 h-14 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                <label class="cursor-pointer px-3 py-1.5 bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 rounded text-xs font-medium hover:bg-teal-100 dark:hover:bg-teal-900/50 transition border border-teal-200 dark:border-teal-800">
                                    Upload
                                    <input type="file" name="service_images[{{ $service->id }}]" accept="image/*" class="hidden" onchange="updateFileName(this, 'svc-{{ $service->id }}')">
                                </label>
                                <span id="svc-{{ $service->id }}" class="text-xs text-gray-500 dark:text-gray-400 w-20 truncate"></span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="px-8 py-3 bg-teal-600 text-white font-bold rounded-lg hover:bg-teal-700 transition shadow-lg shadow-teal-200 dark:shadow-none">
                Save Landing Page
            </button>
        </div>
    </form>

    <!-- Receptionist Permissions (Admin Only) -->
    @if(auth()->user()->roles->contains('name', 'admin'))
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 transition-colors">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Receptionist Access Control
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 rounded-l-lg text-left">Name</th>
                        <th class="px-4 py-3 text-left">Access</th>
                        <th class="px-4 py-3 rounded-r-lg text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($receptionists as $rec)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $rec->first_name }} {{ $rec->last_name }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $rec->can_edit_landing ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $rec->can_edit_landing ? 'CAN EDIT' : 'NO ACCESS' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form action="{{ route('admin.receptionist.toggle-landing', $rec) }}" method="POST" class="inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded border transition {{ $rec->can_edit_landing ? 'border-red-200 text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400' : 'border-teal-200 text-teal-600 hover:bg-teal-50 dark:border-teal-800 dark:text-teal-400' }}">
                                    {{ $rec->can_edit_landing ? 'Revoke' : 'Grant' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    function updateFileName(input, labelId) {
        const label = document.getElementById(labelId);
        if (input.files && input.files[0]) {
            label.textContent = input.files[0].name;
            label.classList.add('text-teal-600', 'dark:text-teal-400');
        } else {
            label.textContent = '';
        }
    }

    function removeHero() {
        if (!confirm('Remove the hero background image?')) return;
        document.getElementById('removeHeroInput').value = '1';
        const preview = document.getElementById('heroPreview');
        const img = document.getElementById('heroImg');
        img.style.opacity = '0.3';
        img.style.filter = 'grayscale(100%)';
        preview.classList.add('ring-2', 'ring-red-500');
        const btn = preview.querySelector('button');
        btn.innerHTML = '<span class="text-xs font-bold">MARKED FOR REMOVAL</span>';
        btn.classList.remove('opacity-0', 'group-hover:opacity-100');
        btn.classList.add('opacity-100', 'bg-gray-800');
    }
</script>
@endpush