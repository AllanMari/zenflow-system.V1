@extends('layouts.admin')

@section('title', 'User Management')

@section('content')
<div class="bg-white dark:bg-gray-800 dark:text-white rounded shadow p-6 transition-colors duration-300">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-teal-600">User Management</h1>
        <button onclick="openModal()" class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700 transition">+ Create New User</button>
    </div>

    {{-- Server-Side Search & Filter --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4 flex flex-col sm:flex-row gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username or name..." 
            class="w-full sm:w-1/3 border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400">
        
        <select name="role" class="border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Roles</option>
            @foreach($roles as $role)
                <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                    {{ ucfirst($role->name) }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">Filter</button>
        
        @if(request()->hasAny(['search', 'role']))
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border rounded text-center hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white transition">Clear</a>
        @endif
    </form>

    {{-- Users Table --}}
    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700">
                    <th class="border dark:border-gray-600 p-3 text-left">ID</th>
                    <th class="border dark:border-gray-600 p-3 text-left">Username</th>
                    <th class="border dark:border-gray-600 p-3 text-left">Name</th>
                    <th class="border dark:border-gray-600 p-3 text-left">Role</th>
                    <th class="border dark:border-gray-600 p-3 text-left">Status</th>
                    <th class="border dark:border-gray-600 p-3 text-left">Created At</th>
                    <th class="border dark:border-gray-600 p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                @php
                    $hasAppointments = \App\Models\Appointment::where('user_id', $user->id)->exists()
                        || \App\Models\Appointment::where('created_by', $user->id)->exists()
                        || ($user->customerProfile && \App\Models\Appointment::where('customer_id', $user->customerProfile->id)->exists());
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 user-row transition-colors">
                    <td class="border dark:border-gray-700 p-3">{{ $user->id }}</td>
                    <td class="border dark:border-gray-700 p-3 font-medium">{{ $user->username }}</td>
                    <td class="border dark:border-gray-700 p-3">{{ $user->first_name }} {{ $user->last_name }}</td>
                    <td class="border dark:border-gray-700 p-3">
                        @foreach($user->roles as $role)
                            <span class="inline-block bg-teal-100 dark:bg-teal-900 text-teal-800 dark:text-teal-200 px-2 py-1 rounded text-xs font-bold uppercase mb-1">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    </td>
                    <td class="border dark:border-gray-700 p-3">
                        @if($user->is_active)
                            <span class="inline-block bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded text-xs font-bold uppercase">Active</span>
                        @else
                            <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded text-xs font-bold uppercase">Inactive</span>
                        @endif
                    </td>
                    <td class="border dark:border-gray-700 p-3 text-sm">
                        {{ $user->created_at->format('Y-m-d') }}
                    </td>
                    <td class="border dark:border-gray-700 p-3">
                        <div class="flex gap-2 flex-wrap">
                            <button onclick='openEditModal(@json($user->id), @json($user->username), @json($user->first_name), @json($user->last_name), @json($user->roles->pluck("name")->first() ?? ""))' 
                                class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition text-sm">
                                Edit
                            </button>
                            
                            @if($user->is_active)
                                {{-- ALL active users can be deactivated --}}
                                <button onclick='confirmDeactivate(@json($user->id), @json($user->username))' 
                                    class="bg-amber-500 text-white px-3 py-1 rounded hover:bg-amber-600 transition text-sm">
                                    Deactivate
                                </button>
                                
                                {{-- Only users with NO appointment history can be hard deleted --}}
                                @if(!$hasAppointments)
                                    <button onclick='confirmDelete(@json($user->id), @json($user->username))' 
                                        class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition text-sm">
                                        Delete
                                    </button>
                                @endif
                            @else
                                {{-- Inactive users can be reactivated --}}
                                <button onclick='confirmReactivate(@json($user->id), @json($user->username))' 
                                    class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition text-sm">
                                    Reactivate
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="border dark:border-gray-700 p-6 text-center text-gray-500 dark:text-gray-400">
                        No users found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>

<!-- Create User Modal -->
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 dark:text-white rounded-lg p-6 w-full max-w-md shadow-xl transition-colors max-h-[90vh] overflow-y-auto">
        <h2 class="text-2xl font-bold mb-4">Create New User</h2>
        
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <input type="hidden" name="form_context" value="create">

            @if($errors->any() && old('form_context') === 'create')
              <div class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 p-3 mb-4 rounded">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                 </ul>
              </div>
            @endif

            <div class="mb-4">
                <label class="block mb-1 font-semibold dark:text-gray-200">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                @error('username')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-semibold dark:text-gray-200">First Name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-semibold dark:text-gray-200">Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
            </div>

            <div class="mb-4 relative">
                <label class="block mb-1 font-semibold dark:text-gray-200">Password</label>
                <input type="password" name="password" id="createPassword" class="w-full border rounded p-2 pr-10 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                <button type="button" onclick="togglePassword('createPassword', this)" class="absolute right-3 top-[2.35rem] text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4 relative">
                <label class="block mb-1 font-semibold dark:text-gray-200">Confirm Password</label>
                <input type="password" name="password_confirmation" id="createPasswordConfirm" class="w-full border rounded p-2 pr-10 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                <button type="button" onclick="togglePassword('createPasswordConfirm', this)" class="absolute right-3 top-[2.35rem] text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-semibold dark:text-gray-200">Role</label>
                <select name="role" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                    <option value="">Select Role</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="receptionist" {{ old('role') == 'receptionist' ? 'selected' : '' }}>Receptionist</option>
                    <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                    <option value="customer" {{ old('role') == 'customer' ? 'selected' : '' }}>Customer</option>
                </select>
                @error('role')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded transition-colors hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-700">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal (for users with NO appointment history) -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 dark:text-white rounded-lg p-6 w-full max-w-sm text-center shadow-xl">
        <h2 class="text-xl font-bold mb-2">Delete User?</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-4">Permanently delete <span id="deleteUsername" class="font-semibold"></span>?</p>
        <p class="text-red-500 text-sm mb-4">This action cannot be undone. No appointment history exists.</p>
        
        <form id="deleteForm" action="{{ route('admin.users.destroy', 0) }}" method="POST" class="text-left">
            @csrf
            @method('DELETE')
            
            <div class="mb-4">
                <label class="block mb-1 font-semibold text-red-600 dark:text-red-400 text-sm">Admin Password Required</label>
                <div class="relative">
                    <input type="password" name="admin_password" id="deleteAdminPassword" class="w-full border rounded p-2 pr-10 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required placeholder="Enter your password">
                    <button type="button" onclick="togglePassword('deleteAdminPassword', this)" class="absolute right-2 top-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 focus:outline-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex justify-center gap-2">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 border rounded transition-colors hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Deactivate Confirmation Modal (for ALL active users) -->
<div id="deactivateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 dark:text-white rounded-lg p-6 w-full max-w-sm text-center shadow-xl border-t-4 border-amber-500">
        <h2 class="text-xl font-bold mb-2 text-amber-600">Deactivate User?</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-4">Deactivate <span id="deactivateUsername" class="font-semibold"></span>?</p>
        <p class="text-amber-600 text-sm mb-4">They will no longer be able to log in. Appointment history is preserved.</p>
        
        <form id="deactivateForm" action="{{ route('admin.users.deactivate', 0) }}" method="POST" class="text-left">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label class="block mb-1 font-semibold text-amber-600 dark:text-amber-400 text-sm">Admin Password Required</label>
                <div class="relative">
                    <input type="password" name="admin_password" id="deactivateAdminPassword" class="w-full border rounded p-2 pr-10 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required placeholder="Enter your password">
                    <button type="button" onclick="togglePassword('deactivateAdminPassword', this)" class="absolute right-2 top-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 focus:outline-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex justify-center gap-2">
                <button type="button" onclick="closeDeactivateModal()" class="px-4 py-2 border rounded transition-colors hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded hover:bg-amber-600">Deactivate</button>
            </div>
        </form>
    </div>
</div>

<!-- Reactivate Confirmation Modal -->
<div id="reactivateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 dark:text-white rounded-lg p-6 w-full max-w-sm text-center shadow-xl border-t-4 border-green-500">
        <h2 class="text-xl font-bold mb-2 text-green-600">Reactivate User?</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-4">Reactivate <span id="reactivateUsername" class="font-semibold"></span>?</p>
        <p class="text-green-600 text-sm mb-4">They will be able to log in again. You must reassign their role manually.</p>
        
        <form id="reactivateForm" action="{{ route('admin.users.reactivate', 0) }}" method="POST" class="text-left">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label class="block mb-1 font-semibold text-green-600 dark:text-green-400 text-sm">Admin Password Required</label>
                <div class="relative">
                    <input type="password" name="admin_password" id="reactivateAdminPassword" class="w-full border rounded p-2 pr-10 focus:outline-none focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required placeholder="Enter your password">
                    <button type="button" onclick="togglePassword('reactivateAdminPassword', this)" class="absolute right-2 top-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 focus:outline-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex justify-center gap-2">
                <button type="button" onclick="closeReactivateModal()" class="px-4 py-2 border rounded transition-colors hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Reactivate</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 dark:text-white rounded-lg p-6 w-full max-w-md shadow-xl transition-colors max-h-[90vh] overflow-y-auto">
        <h2 class="text-2xl font-bold mb-4">Edit User</h2>
        
        <form id="editForm" action="{{ route('admin.users.update', 0) }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="form_context" value="edit">

            @if($errors->any() && old('form_context') === 'edit')
              <div class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 p-3 mb-4 rounded">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                 </ul>
              </div>
            @endif

            <div class="mb-4">
                <label class="block mb-1 font-semibold dark:text-gray-200">Username</label>
                <input type="text" id="editUsername" name="username" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                @error('username')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-semibold dark:text-gray-200">First Name</label>
                <input type="text" id="editFirstName" name="first_name" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-semibold dark:text-gray-200">Last Name</label>
                <input type="text" id="editLastName" name="last_name" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
            </div>

            <div class="mb-4 relative">
                <label class="block mb-1 font-semibold dark:text-gray-200">New Password <span class="text-xs font-normal text-gray-500">(leave blank to keep current)</span></label>
                <input type="password" name="password" id="editPassword" class="w-full border rounded p-2 pr-10 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <button type="button" onclick="togglePassword('editPassword', this)" class="absolute right-3 top-[2.35rem] text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4 relative">
                <label class="block mb-1 font-semibold dark:text-gray-200">Confirm New Password</label>
                <input type="password" name="password_confirmation" id="editPasswordConfirm" class="w-full border rounded p-2 pr-10 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <button type="button" onclick="togglePassword('editPasswordConfirm', this)" class="absolute right-3 top-[2.35rem] text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-semibold dark:text-gray-200">Role</label>
                <select id="editRole" name="role" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                    <option value="admin">Admin</option>
                    <option value="receptionist">Receptionist</option>
                    <option value="staff">Staff</option>
                    <option value="customer">Customer</option>
                </select>
            </div>

            <div class="mb-4 border-t dark:border-gray-600 pt-4 mt-2">
                <label class="block mb-1 font-semibold text-red-600 dark:text-red-400 text-sm">Confirm Your Admin Password</label>
                <div class="relative">
                    <input type="password" name="admin_password" id="editAdminPassword" class="w-full border rounded p-2 pr-10 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required placeholder="Enter your password to authorize changes">
                    <button type="button" onclick="togglePassword('editAdminPassword', this)" class="absolute right-2 top-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 focus:outline-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 border rounded transition-colors hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update User</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ========== MODAL CONTROLS ==========
    function openModal() {
        document.getElementById('createModal').classList.remove('hidden');
        document.getElementById('createModal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('createModal').classList.add('hidden');
        document.getElementById('createModal').classList.remove('flex');
    }

    function confirmDelete(userId, username) {
        document.getElementById('deleteUsername').textContent = username;
        const form = document.getElementById('deleteForm');
        if (!form.dataset.baseAction) {
            form.dataset.baseAction = form.action;
        }
        form.action = form.dataset.baseAction.replace('/0', '/' + userId);
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
    }

    function confirmDeactivate(userId, username) {
        document.getElementById('deactivateUsername').textContent = username;
        const form = document.getElementById('deactivateForm');
        if (!form.dataset.baseAction) {
            form.dataset.baseAction = form.action;
        }
        form.action = form.dataset.baseAction.replace('/0', '/' + userId);
        document.getElementById('deactivateModal').classList.remove('hidden');
        document.getElementById('deactivateModal').classList.add('flex');
    }

    function closeDeactivateModal() {
        document.getElementById('deactivateModal').classList.add('hidden');
        document.getElementById('deactivateModal').classList.remove('flex');
    }

    function confirmReactivate(userId, username) {
        document.getElementById('reactivateUsername').textContent = username;
        const form = document.getElementById('reactivateForm');
        if (!form.dataset.baseAction) {
            form.dataset.baseAction = form.action;
        }
        form.action = form.dataset.baseAction.replace('/0', '/' + userId);
        document.getElementById('reactivateModal').classList.remove('hidden');
        document.getElementById('reactivateModal').classList.add('flex');
    }

    function closeReactivateModal() {
        document.getElementById('reactivateModal').classList.add('hidden');
        document.getElementById('reactivateModal').classList.remove('flex');
    }

    function openEditModal(id, username, firstName, lastName, role) {
        const form = document.getElementById('editForm');
        if (!form.dataset.baseAction) {
            form.dataset.baseAction = form.action;
        }
        form.action = form.dataset.baseAction.replace('/0', '/' + id);
        document.getElementById('editUsername').value = username;
        document.getElementById('editFirstName').value = firstName;
        document.getElementById('editLastName').value = lastName;
        document.getElementById('editRole').value = role;
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editModal').classList.remove('flex');
    }

    // ========== PASSWORD EYE TOGGLE ==========
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        
        const eyeOpen = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
        const eyeClosed = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-3.925 4.472m-5.858-9.9l-3.29-3.29"/></svg>';
        
        btn.innerHTML = isHidden ? eyeClosed : eyeOpen;
    }

    // ========== CLICK OUTSIDE TO CLOSE ==========
    window.addEventListener('click', function(event) {
        if (event.target === document.getElementById('createModal')) closeModal();
        if (event.target === document.getElementById('deleteModal')) closeDeleteModal();
        if (event.target === document.getElementById('deactivateModal')) closeDeactivateModal();
        if (event.target === document.getElementById('reactivateModal')) closeReactivateModal();
        if (event.target === document.getElementById('editModal')) closeEditModal();
    });

    // ========== BULLETPROOF DOUBLE SUBMIT PREVENTION ==========
    document.querySelectorAll('#createModal form, #editForm, #deleteForm, #deactivateForm, #reactivateForm').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (this.dataset.submitting === 'true') {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
            this.dataset.submitting = 'true';
            const buttons = this.querySelectorAll('button[type="submit"]');
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.innerHTML = '<span class="opacity-75">Processing...</span>';
            });
        });
    });

    // ========== AUTO-OPEN MODALS ON VALIDATION/ERROR ==========
    @if(old('form_context') === 'create')
        openModal();
    @endif

    @if(session('edit_user_id'))
        @php
            $editUser = \App\Models\User::with('roles')->find(session('edit_user_id'));
        @endphp
        @if($editUser)
            openEditModal(
                {{ $editUser->id }}, 
                @json($editUser->username), 
                @json($editUser->first_name), 
                @json($editUser->last_name), 
                @json($editUser->roles->first()->name ?? '')
            );
        @endif
    @endif
</script>
@endpush