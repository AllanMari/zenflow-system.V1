@extends('layouts.admin')

@section('title', 'Rooms')

@section('content')
<div class="max-w-7xl mx-auto">
    
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Rooms</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1 text-sm">Manage treatment rooms and categories</p>
        </div>
        <button onclick="openRoomModal()" 
                class="bg-teal-600 hover:bg-teal-700 text-white px-5 py-2.5 rounded-lg font-semibold text-sm shadow-lg shadow-teal-200 dark:shadow-none transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Room
        </button>
    </div>

    <!-- Rooms Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        @forelse($rooms as $room)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 transition hover:shadow-md">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-lg bg-teal-100 dark:bg-teal-900/40 flex items-center justify-center text-teal-700 dark:text-teal-400 font-bold">
                        {{ strtoupper(substr($room->name, 0, 2)) }}
                    </div>
                    <div class="flex gap-1">
                        <button onclick='editRoom(@json($room))' 
                                class="p-1.5 text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button onclick="confirmDelete('{{ route('admin.rooms.destroy', $room) }}')" 
                                class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>

                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ $room->name }}</h3>

                <div class="flex flex-wrap gap-2 mb-3">
                    @if($room->category)
                        <span class="px-2 py-1 rounded-md text-xs font-semibold bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400 border border-teal-200 dark:border-teal-800">
                            {{ $room->category->name }}
                        </span>
                    @else
                        <span class="px-2 py-1 rounded-md text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-600">
                            General Use
                        </span>
                    @endif

                    @php
                        $statusBadge = match($room->status) {
                            'available' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800',
                            'occupied' => 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
                            'maintenance' => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
                            default => 'bg-gray-100 text-gray-600 border-gray-200',
                        };
                    @endphp
                    <span class="px-2 py-1 rounded-md text-xs font-semibold border {{ $statusBadge }}">
                        {{ ucfirst($room->status) }}
                    </span>

                    @if(!$room->is_active)
                        <span class="px-2 py-1 rounded-md text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-600">
                            Inactive
                        </span>
                    @endif
                </div>

                @if($room->notes)
                    <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $room->notes }}</p>
                @endif
            </div>
        @empty
            <div class="col-span-full text-center py-16 bg-white dark:bg-gray-800 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <p class="text-gray-500 dark:text-gray-400 font-semibold">No rooms created yet</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Room Modal -->
<div id="roomModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div id="roomBackdrop" class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity duration-300 opacity-0" onclick="closeRoomModal()"></div>
    <div id="roomPanel" class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-2xl border border-gray-100 dark:border-gray-700 transform transition-all duration-300 scale-95 opacity-0 mx-4">
        
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold dark:text-white" id="modalTitle">Add Room</h2>
            <button type="button" onclick="closeRoomModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="roomForm" method="POST" class="space-y-4">
            @csrf
            <div id="methodField"></div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Room Name / Code</label>
                <input type="text" name="name" id="roomName" required
                       class="w-full border border-gray-200 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent outline-none transition"
                       placeholder="e.g. A1, VIP Suite 1, Nail Lounge">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Category</label>
                <select name="category_id" id="roomCategory"
                        class="w-full border border-gray-200 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent outline-none transition">
                    <option value="">General / Multi-purpose</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Leave empty for general-use rooms</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['available', 'occupied', 'maintenance'] as $status)
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="{{ $status }}" class="peer hidden room-status-radio">
                            <div class="text-center p-2.5 rounded-lg border-2 border-gray-200 dark:border-gray-600 peer-checked:border-teal-500 peer-checked:bg-teal-50 dark:peer-checked:bg-teal-900/30 peer-checked:text-teal-700 dark:peer-checked:text-teal-300 transition text-sm font-semibold dark:text-gray-300">
                                {{ ucfirst($status) }}
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                <input type="checkbox" name="is_active" id="roomActive" value="1" checked
                       class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600">
                <label for="roomActive" class="text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">Room is active and bookable</label>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                <textarea name="notes" id="roomNotes" rows="2"
                          class="w-full border border-gray-200 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent outline-none transition resize-none"
                          placeholder="Equipment, special features..."></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeRoomModal()" class="flex-1 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-gray-700 dark:text-gray-300 font-semibold text-sm">Cancel</button>
                <button type="submit" class="flex-1 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition font-semibold text-sm shadow-lg shadow-teal-200 dark:shadow-none">Save Room</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
    const roomModal = document.getElementById('roomModal');
    const roomBackdrop = document.getElementById('roomBackdrop');
    const roomPanel = document.getElementById('roomPanel');
    const roomForm = document.getElementById('roomForm');
    const methodField = document.getElementById('methodField');

    function openRoomModal() {
        roomModal.classList.remove('hidden');
        void roomModal.offsetWidth;
        roomBackdrop.classList.remove('opacity-0');
        roomPanel.classList.remove('scale-95', 'opacity-0');
        roomPanel.classList.add('scale-100', 'opacity-100');
    }

    function closeRoomModal() {
        roomBackdrop.classList.add('opacity-0');
        roomPanel.classList.remove('scale-100', 'opacity-100');
        roomPanel.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            roomModal.classList.add('hidden');
            resetForm();
        }, 300);
    }

    function resetForm() {
        document.getElementById('modalTitle').textContent = 'Add Room';
        roomForm.action = '{{ route('admin.rooms.store') }}';
        methodField.innerHTML = '';
        document.getElementById('roomName').value = '';
        document.getElementById('roomCategory').value = '';
        document.querySelectorAll('.room-status-radio').forEach(r => r.checked = false);
        document.querySelector('.room-status-radio[value="available"]').checked = true;
        document.getElementById('roomActive').checked = true;
        document.getElementById('roomNotes').value = '';
    }

    function editRoom(room) {
        document.getElementById('modalTitle').textContent = 'Edit Room';
        roomForm.action = `/admin/rooms/${room.id}`;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        
        document.getElementById('roomName').value = room.name;
        document.getElementById('roomCategory').value = room.category_id ?? '';
        document.querySelectorAll('.room-status-radio').forEach(r => r.checked = (r.value === room.status));
        document.getElementById('roomActive').checked = room.is_active;
        document.getElementById('roomNotes').value = room.notes ?? '';
        
        openRoomModal();
    }

    function confirmDelete(actionUrl) {
        Swal.fire({
            title: 'Delete Room?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0d9488',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#fff' : '#374151'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteForm');
                form.action = actionUrl;
                form.submit();
            }
        });
    }
</script>
@endsection