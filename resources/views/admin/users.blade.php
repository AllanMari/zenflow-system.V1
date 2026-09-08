@extends('layouts.admin')

@section('title', 'User Management')

@section('content')

@php
$roleStyles = [
'admin' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300',
'receptionist' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300',
'staff' => 'bg-teal-50 text-teal-700 dark:bg-teal-900/20 dark:text-teal-300',
'customer' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
];
@endphp

<div class="w-full">

```
{{-- Search and Filter --}}
<div class="mb-5 rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-[#1e293b]">
    <form
        method="GET"
        action="{{ route('admin.users.index') }}"
        class="flex flex-col gap-3 p-4 sm:p-5 lg:flex-row lg:items-center"
    >
        {{-- Search --}}
        <div class="relative min-w-0 flex-1">
            <svg
                class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M21 21l-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"
                />
            </svg>

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search username or name..."
                autocomplete="off"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none transition focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white dark:placeholder-gray-500 dark:focus:border-brand-500"
            >
        </div>

        {{-- Role --}}
        <select
            name="role"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white lg:w-48"
        >
            <option value="">All Roles</option>

            @foreach($roles as $role)
                <option
                    value="{{ $role->name }}"
                    {{ request('role') == $role->name ? 'selected' : '' }}
                >
                    {{ ucfirst($role->name) }}
                </option>
            @endforeach
        </select>

        {{-- Filter --}}
        <button
            type="submit"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#1e293b] lg:w-auto"
        >
            <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M3 5h18M6 12h12m-9 7h6"
                />
            </svg>
            Filter
        </button>

        {{-- Clear --}}
        @if(request()->hasAny(['search', 'role']))
            <a
                href="{{ route('admin.users.index') }}"
                class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 lg:w-auto"
            >
                Clear
            </a>
        @endif

        {{-- Create --}}
        <button
            type="button"
            onclick="openModal('createModal', 'createUsername')"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 dark:focus:ring-offset-[#1e293b] lg:w-auto"
        >
            <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 5v14M5 12h14"
                />
            </svg>
            New User
        </button>
    </form>
</div>

{{-- =========================================================
     DESKTOP / TABLET VIEW
========================================================== --}}
<div class="hidden overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-[#1e293b] md:block">
    <div class="overflow-x-auto">
        <table class="min-w-[760px] w-full">
            <thead class="bg-gray-50 dark:bg-gray-900/40">
                <tr>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        User
                    </th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Role
                    </th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Status
                    </th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Created
                    </th>
                    <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Actions
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($users as $user)
                    @php
                        $hasAppointments =
                            \App\Models\Appointment::where('user_id', $user->id)->exists()
                            || \App\Models\Appointment::where('created_by', $user->id)->exists()
                            || (
                                $user->customerProfile &&
                                \App\Models\Appointment::where('customer_id', $user->customerProfile->id)->exists()
                            );

                        $fullName = trim(
                            ($user->first_name ?? '') . ' ' . ($user->last_name ?? '')
                        );

                        $initials = strtoupper(
                            substr($user->first_name ?? '', 0, 1) .
                            substr($user->last_name ?? '', 0, 1)
                        );

                        $primaryRole = $user->roles->pluck('name')->first() ?? '';

                        $roleClass = $roleStyles[strtolower($primaryRole)]
                            ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                    @endphp

                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50">

                        {{-- User --}}
                        <td class="px-5 py-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                    {{ $initials ?: '?' }}
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $fullName ?: 'Unnamed User' }}
                                    </p>

                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ $user->username }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        {{-- Role --}}
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($user->roles as $role)
                                    @php
                                        $currentRoleClass = $roleStyles[strtolower($role->name)]
                                            ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                                    @endphp

                                    <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold capitalize {{ $currentRoleClass }}">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400">
                                        No role
                                    </span>
                                @endforelse
                            </div>
                        </td>

                        {{-- Status --}}
                        <td class="px-5 py-4">
                            @if($user->is_active)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/20 dark:text-green-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                    Inactive
                                </span>
                            @endif
                        </td>

                        {{-- Created --}}
                        <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap justify-end gap-2">

                                <button
                                    type="button"
                                    onclick='openEditModal(
                                        @json($user->id),
                                        @json($user->username),
                                        @json($user->first_name),
                                        @json($user->last_name),
                                        @json($primaryRole)
                                    )'
                                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-blue-900/20 dark:hover:text-blue-300"
                                >
                                    Edit
                                </button>

                                @if($user->is_active)

                                    <button
                                        type="button"
                                        onclick='openConfirmModal("deactivate", @json($user->id), @json($user->username))'
                                        class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300"
                                    >
                                        Deactivate
                                    </button>

                                    @if(!$hasAppointments)
                                        <button
                                            type="button"
                                            onclick='openConfirmModal("delete", @json($user->id), @json($user->username))'
                                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300"
                                        >
                                            Delete
                                        </button>
                                    @endif

                                @else

                                    <button
                                        type="button"
                                        onclick='openConfirmModal("reactivate", @json($user->id), @json($user->username))'
                                        class="rounded-lg border border-green-200 bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700 transition hover:bg-green-100 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300"
                                    >
                                        Reactivate
                                    </button>

                                @endif
                            </div>
                        </td>
                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="px-5 py-16 text-center text-sm text-gray-500 dark:text-gray-400">
                            No users found.
                        </td>
                    </tr>

                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-700">
            {{ $users->links() }}
        </div>
    @endif
</div>

{{-- =========================================================
     MOBILE VIEW
========================================================== --}}
<div class="space-y-3 md:hidden">

    @forelse($users as $user)

        @php
            $hasAppointments =
                \App\Models\Appointment::where('user_id', $user->id)->exists()
                || \App\Models\Appointment::where('created_by', $user->id)->exists()
                || (
                    $user->customerProfile &&
                    \App\Models\Appointment::where('customer_id', $user->customerProfile->id)->exists()
                );

            $fullName = trim(
                ($user->first_name ?? '') . ' ' . ($user->last_name ?? '')
            );

            $initials = strtoupper(
                substr($user->first_name ?? '', 0, 1) .
                substr($user->last_name ?? '', 0, 1)
            );

            $primaryRole = $user->roles->pluck('name')->first() ?? '';

            $roleClass = $roleStyles[strtolower($primaryRole)]
                ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
        @endphp

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-[#1e293b]">

            <div class="flex items-start gap-3">

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                    {{ $initials ?: '?' }}
                </div>

                <div class="min-w-0 flex-1">

                    <div class="flex items-start justify-between gap-2">

                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-gray-900 dark:text-white">
                                {{ $fullName ?: 'Unnamed User' }}
                            </p>

                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ $user->username }}
                            </p>
                        </div>

                        @if($user->is_active)
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-green-50 px-2 py-1 text-[10px] font-semibold text-green-700 dark:bg-green-900/20 dark:text-green-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                Active
                            </span>
                        @else
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-gray-100 px-2 py-1 text-[10px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                Inactive
                            </span>
                        @endif

                    </div>

                    <div class="mt-3 flex items-center justify-between gap-3">

                        <div class="flex flex-wrap gap-1.5">
                            @forelse($user->roles as $role)
                                @php
                                    $currentRoleClass = $roleStyles[strtolower($role->name)]
                                        ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                                @endphp

                                <span class="inline-flex rounded-lg px-2 py-1 text-[10px] font-semibold capitalize {{ $currentRoleClass }}">
                                    {{ $role->name }}
                                </span>
                            @empty
                                <span class="text-[10px] text-gray-400">
                                    No role
                                </span>
                            @endforelse
                        </div>

                        <span class="shrink-0 text-[10px] text-gray-400 dark:text-gray-500">
                            {{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}
                        </span>

                    </div>
                </div>
            </div>

            {{-- Mobile Actions --}}
            <div class="mt-4 grid grid-cols-2 gap-2 border-t border-gray-100 pt-3 dark:border-gray-700">

                <button
                    type="button"
                    onclick='openEditModal(
                        @json($user->id),
                        @json($user->username),
                        @json($user->first_name),
                        @json($user->last_name),
                        @json($primaryRole)
                    )'
                    class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Edit
                </button>

                @if($user->is_active)

                    <button
                        type="button"
                        onclick='openConfirmModal("deactivate", @json($user->id), @json($user->username))'
                        class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300"
                    >
                        Deactivate
                    </button>

                    @if(!$hasAppointments)

                        <button
                            type="button"
                            onclick='openConfirmModal("delete", @json($user->id), @json($user->username))'
                            class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300"
                        >
                            Delete
                        </button>

                        <span></span>

                    @endif

                @else

                    <button
                        type="button"
                        onclick='openConfirmModal("reactivate", @json($user->id), @json($user->username))'
                        class="rounded-xl border border-green-200 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 transition hover:bg-green-100 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300"
                    >
                        Reactivate
                    </button>

                @endif
            </div>
        </div>

    @empty

        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-12 text-center shadow-sm dark:border-gray-700 dark:bg-[#1e293b]">

            <svg
                class="mx-auto h-8 w-8 text-gray-300 dark:text-gray-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.7"
                    d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"
                />
                <circle
                    cx="9"
                    cy="7"
                    r="4"
                    fill="none"
                    stroke-width="1.7"
                />
            </svg>

            <p class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-300">
                No users found.
            </p>

        </div>

    @endforelse

    @if($users->hasPages())
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-[#1e293b]">
            {{ $users->links() }}
        </div>
    @endif
</div>
```

</div>

{{-- =============================================================
CREATE MODAL
============================================================== --}}

<div
    id="createModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
>
    <div
        class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-[#1e293b]"
        onclick="event.stopPropagation()"
    >

```
    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">

        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                Create New User
            </h2>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                Create an account and assign a role.
            </p>
        </div>

        <button
            type="button"
            onclick="closeModal('createModal')"
            class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
            aria-label="Close"
        >
            ✕
        </button>

    </div>

    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf

        <input type="hidden" name="form_context" value="create">

        <div class="max-h-[75vh] overflow-y-auto p-5">

            @if($errors->any() && old('form_context') === 'create')
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300">

                    <p class="mb-2 font-semibold">
                        Please correct the following:
                    </p>

                    <ul class="list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                {{-- Username --}}
                <div class="sm:col-span-2">

                    <label
                        for="createUsername"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        id="createUsername"
                        value="{{ old('username') }}"
                        autocomplete="username"
                        required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >

                    @error('username')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- First Name --}}
                <div>

                    <label
                        for="createFirstName"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        First Name
                    </label>

                    <input
                        type="text"
                        name="first_name"
                        id="createFirstName"
                        value="{{ old('first_name') }}"
                        autocomplete="given-name"
                        required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >

                    @error('first_name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- Last Name --}}
                <div>

                    <label
                        for="createLastName"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Last Name
                    </label>

                    <input
                        type="text"
                        name="last_name"
                        id="createLastName"
                        value="{{ old('last_name') }}"
                        autocomplete="family-name"
                        required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >

                    @error('last_name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- Password --}}
                <div>

                    <label
                        for="createPassword"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Password
                    </label>

                    <div class="relative">

                        <input
                            type="password"
                            name="password"
                            id="createPassword"
                            autocomplete="new-password"
                            required
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-3.5 pr-10 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                        <button
                            type="button"
                            onclick="togglePassword('createPassword', this)"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400"
                            aria-label="Show password"
                        >
                            ◉
                        </button>

                    </div>

                    <p class="mt-1.5 text-[11px] leading-4 text-gray-500 dark:text-gray-400">
                        At least 8 characters with uppercase, lowercase, number, and symbol.
                    </p>

                    @error('password')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- Confirm Password --}}
                <div>

                    <label
                        for="createPasswordConfirm"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Confirm Password
                    </label>

                    <div class="relative">

                        <input
                            type="password"
                            name="password_confirmation"
                            id="createPasswordConfirm"
                            autocomplete="new-password"
                            required
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-3.5 pr-10 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                        <button
                            type="button"
                            onclick="togglePassword('createPasswordConfirm', this)"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400"
                            aria-label="Show password"
                        >
                            ◉
                        </button>

                    </div>

                    @error('password_confirmation')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- Role --}}
                <div class="sm:col-span-2">

                    <label
                        for="createRole"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Role
                    </label>

                    <select
                        name="role"
                        id="createRole"
                        required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Select Role</option>

                        @foreach($roles as $role)
                            <option
                                value="{{ $role->name }}"
                                {{ old('role') == $role->name ? 'selected' : '' }}
                            >
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>

                    @error('role')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30 sm:flex-row sm:justify-end">

            <button
                type="button"
                onclick="closeModal('createModal')"
                class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
            >
                Cancel
            </button>

            <button
                type="submit"
                class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
            >
                Create User
            </button>

        </div>
    </form>
</div>
```

</div>

{{-- =============================================================
EDIT MODAL
============================================================== --}}

<div
    id="editModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
>
    <div
        class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-[#1e293b]"
        onclick="event.stopPropagation()"
    >

```
    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">

        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                Edit User
            </h2>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                Update account information.
            </p>
        </div>

        <button
            type="button"
            onclick="closeModal('editModal')"
            class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
            aria-label="Close"
        >
            ✕
        </button>

    </div>

    <form
        id="editForm"
        action="{{ route('admin.users.update', '__USER__') }}"
        method="POST"
    >
        @csrf
        @method('PUT')

        <input type="hidden" name="form_context" value="edit">

        <div class="max-h-[75vh] overflow-y-auto p-5">

            @if($errors->any() && old('form_context') === 'edit')
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300">

                    <p class="mb-2 font-semibold">
                        Please correct the following:
                    </p>

                    <ul class="list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                {{-- Username --}}
                <div class="sm:col-span-2">

                    <label
                        for="editUsername"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Username
                    </label>

                    <input
                        type="text"
                        id="editUsername"
                        name="username"
                        autocomplete="username"
                        required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >

                    @error('username')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- First Name --}}
                <div>

                    <label
                        for="editFirstName"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        First Name
                    </label>

                    <input
                        type="text"
                        id="editFirstName"
                        name="first_name"
                        autocomplete="given-name"
                        required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >

                    @error('first_name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- Last Name --}}
                <div>

                    <label
                        for="editLastName"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Last Name
                    </label>

                    <input
                        type="text"
                        id="editLastName"
                        name="last_name"
                        autocomplete="family-name"
                        required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >

                    @error('last_name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- New Password --}}
                <div>

                    <label
                        for="editPassword"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        New Password
                    </label>

                    <div class="relative">

                        <input
                            type="password"
                            name="password"
                            id="editPassword"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-3.5 pr-10 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                        <button
                            type="button"
                            onclick="togglePassword('editPassword', this)"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400"
                            aria-label="Show password"
                        >
                            ◉
                        </button>

                    </div>

                    <p class="mt-1.5 text-[11px] leading-4 text-gray-500 dark:text-gray-400">
                        Leave blank to keep the current password. New passwords must use 8+ characters, uppercase, lowercase, number, and symbol.
                    </p>

                    @error('password')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- Confirm New Password --}}
                <div>

                    <label
                        for="editPasswordConfirm"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Confirm New Password
                    </label>

                    <div class="relative">

                        <input
                            type="password"
                            name="password_confirmation"
                            id="editPasswordConfirm"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-3.5 pr-10 text-sm outline-none focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                        <button
                            type="button"
                            onclick="togglePassword('editPasswordConfirm', this)"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400"
                            aria-label="Show password"
                        >
                            ◉
                        </button>

                    </div>

                    @error('password_confirmation')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- Role --}}
                <div class="sm:col-span-2">

                    <label
                        for="editRole"
                        class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Role
                    </label>

                    <select
                        id="editRole"
                        name="role"
                        required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>

                    @error('role')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                {{-- Authorization --}}
                <div class="sm:col-span-2 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/40 dark:bg-red-900/10">

                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-300">
                        Authorization
                    </p>

                    <label
                        for="editAdminPassword"
                        class="mb-1.5 block text-sm font-semibold text-red-800 dark:text-red-300"
                    >
                        Confirm Your Admin Password
                    </label>

                    <input
                        type="password"
                        name="admin_password"
                        id="editAdminPassword"
                        autocomplete="current-password"
                        required
                        placeholder="Enter your password"
                        class="w-full rounded-xl border border-red-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-red-900/60 dark:bg-gray-800 dark:text-white"
                    >

                    @error('admin_password')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30 sm:flex-row sm:justify-end">

            <button
                type="button"
                onclick="closeModal('editModal')"
                class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
            >
                Cancel
            </button>

            <button
                type="submit"
                class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
            >
                Update User
            </button>

        </div>
    </form>
</div>
```

</div>

{{-- =============================================================
SHARED CONFIRMATION MODAL
============================================================== --}}

<div
    id="confirmModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
>
    <div
        class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-[#1e293b]"
        onclick="event.stopPropagation()"
    >

```
    <div class="p-5 text-center sm:p-6">

        <div
            id="confirmIcon"
            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full"
        >
            <svg
                class="h-6 w-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M12 9v4m0 4h.01"
                />
            </svg>
        </div>

        <h2
            id="confirmTitle"
            class="mt-4 text-lg font-bold text-gray-900 dark:text-white"
        >
            Confirm Action
        </h2>

        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            <span id="confirmMessage"></span>
            <span
                id="confirmUsername"
                class="font-semibold text-gray-900 dark:text-white"
            ></span>?
        </p>

        <div
            id="confirmDescription"
            class="mt-4 rounded-xl border p-3 text-left text-xs"
        ></div>
    </div>

    <form
        id="confirmForm"
        method="POST"
        action=""
        class="border-t border-gray-100 px-5 pb-5 pt-4 dark:border-gray-700 sm:px-6 sm:pb-6"
    >
        @csrf

        <input
            type="hidden"
            id="confirmAdminPassword"
            name="admin_password"
        >

        <div class="mb-4">

            <label
                for="confirmPasswordInput"
                class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-200"
            >
                Confirm Your Admin Password
            </label>

            <div class="relative">

                <input
                    type="password"
                    id="confirmPasswordInput"
                    autocomplete="current-password"
                    required
                    placeholder="Enter your password"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-3.5 pr-10 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >

                <button
                    type="button"
                    onclick="togglePassword('confirmPasswordInput', this)"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400"
                    aria-label="Show password"
                >
                    ◉
                </button>

            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">

            <button
                type="button"
                onclick="closeModal('confirmModal')"
                class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
            >
                Cancel
            </button>

            <button
                id="confirmSubmit"
                type="submit"
                class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
            >
                Confirm
            </button>

        </div>
    </form>
</div>
```

</div>

@endsection

@push('scripts')

<script>
    const USER_MODAL_IDS = [
        'createModal',
        'editModal',
        'confirmModal'
    ];

    function openModal(modalId, focusId) {
        const modal = document.getElementById(modalId);

        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.body.classList.add('overflow-hidden');

        if (focusId) {
            setTimeout(function () {
                const element = document.getElementById(focusId);

                if (element) {
                    element.focus();
                }
            }, 50);
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);

        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');

        modal.querySelectorAll('input[type="password"]').forEach(function (input) {
            input.value = '';
            input.type = 'password';
        });

        const anyOpen = USER_MODAL_IDS.some(function (id) {
            const element = document.getElementById(id);

            return element && element.classList.contains('flex');
        });

        if (!anyOpen) {
            document.body.classList.remove('overflow-hidden');
        }
    }

    function setUserAction(formId, userId) {
        const form = document.getElementById(formId);

        if (!form) {
            return;
        }

        if (!form.dataset.baseAction) {
            form.dataset.baseAction = form.action;
        }

        form.action = form.dataset.baseAction.replace(
            '__USER__',
            encodeURIComponent(userId)
        );
    }

    function openEditModal(id, username, firstName, lastName, role) {
        setUserAction('editForm', id);

        const usernameInput = document.getElementById('editUsername');
        const firstNameInput = document.getElementById('editFirstName');
        const lastNameInput = document.getElementById('editLastName');
        const roleInput = document.getElementById('editRole');
        const passwordInput = document.getElementById('editPassword');
        const passwordConfirmInput = document.getElementById('editPasswordConfirm');
        const adminPasswordInput = document.getElementById('editAdminPassword');

        if (usernameInput) {
            usernameInput.value = username || '';
        }

        if (firstNameInput) {
            firstNameInput.value = firstName || '';
        }

        if (lastNameInput) {
            lastNameInput.value = lastName || '';
        }

        if (roleInput) {
            roleInput.value = role || '';
        }

        if (passwordInput) {
            passwordInput.value = '';
            passwordInput.type = 'password';
        }

        if (passwordConfirmInput) {
            passwordConfirmInput.value = '';
            passwordConfirmInput.type = 'password';
        }

        if (adminPasswordInput) {
            adminPasswordInput.value = '';
            adminPasswordInput.type = 'password';
        }

        openModal('editModal', 'editUsername');
    }

    function openConfirmModal(type, userId, username) {
        const form = document.getElementById('confirmForm');
        const title = document.getElementById('confirmTitle');
        const message = document.getElementById('confirmMessage');
        const description = document.getElementById('confirmDescription');
        const usernameElement = document.getElementById('confirmUsername');
        const icon = document.getElementById('confirmIcon');
        const submit = document.getElementById('confirmSubmit');
        const passwordInput = document.getElementById('confirmPasswordInput');
        const hiddenPassword = document.getElementById('confirmAdminPassword');

        if (
            !form ||
            !title ||
            !message ||
            !description ||
            !usernameElement ||
            !icon ||
            !submit
        ) {
            return;
        }

        usernameElement.textContent = username || '';

        const descriptions = {
            delete: 'This action cannot be undone. The delete option is only available when the user has no appointment history.',
            deactivate: 'The user will no longer be able to log in. Existing appointment history is preserved.',
            reactivate: 'The user will be able to log in again after reactivation.'
        };

        if (type === 'delete') {

            title.textContent = 'Delete User?';
            message.textContent = 'Permanently delete ';
            description.textContent = descriptions.delete;

            icon.className =
                'mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400';

            description.className =
                'mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-left text-xs font-medium text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300';

            submit.className =
                'rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700';

            submit.textContent = 'Delete User';

            form.action = '{{ route('admin.users.destroy', '__USER__') }}'
                .replace('__USER__', encodeURIComponent(userId));

            form.dataset.method = 'DELETE';

        } else if (type === 'deactivate') {

            title.textContent = 'Deactivate User?';
            message.textContent = 'Deactivate ';
            description.textContent = descriptions.deactivate;

            icon.className =
                'mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400';

            description.className =
                'mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-left text-xs font-medium text-amber-700 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300';

            submit.className =
                'rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600';

            submit.textContent = 'Deactivate';

            form.action = '{{ route('admin.users.deactivate', '__USER__') }}'
                .replace('__USER__', encodeURIComponent(userId));

            form.dataset.method = 'PUT';

        } else {

            title.textContent = 'Reactivate User?';
            message.textContent = 'Reactivate ';
            description.textContent = descriptions.reactivate;

            icon.className =
                'mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400';

            description.className =
                'mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-left text-xs font-medium text-green-700 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300';

            submit.className =
                'rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700';

            submit.textContent = 'Reactivate';

            form.action = '{{ route('admin.users.reactivate', '__USER__') }}'
                .replace('__USER__', encodeURIComponent(userId));

            form.dataset.method = 'PUT';
        }

        if (passwordInput) {
            passwordInput.value = '';
            passwordInput.type = 'password';
        }

        if (hiddenPassword) {
            hiddenPassword.value = '';
        }

        const existingMethod = form.querySelector('input[name="_method"]');

        if (existingMethod) {
            existingMethod.remove();
        }

        const methodInput = document.createElement('input');

        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = form.dataset.method;

        form.appendChild(methodInput);

        openModal('confirmModal', 'confirmPasswordInput');
    }

    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);

        if (!input) {
            return;
        }

        const isPassword = input.type === 'password';

        input.type = isPassword ? 'text' : 'password';

        if (button) {
            button.setAttribute(
                'aria-label',
                isPassword ? 'Hide password' : 'Show password'
            );
        }
    }

    const confirmForm = document.getElementById('confirmForm');

    if (confirmForm) {
        confirmForm.addEventListener('submit', function (event) {

            const passwordInput = document.getElementById('confirmPasswordInput');
            const hiddenPassword = document.getElementById('confirmAdminPassword');

            if (!passwordInput || !hiddenPassword) {
                event.preventDefault();
                return;
            }

            hiddenPassword.value = passwordInput.value;
        });
    }

    document.querySelectorAll(
        '#createModal form, #editForm, #confirmForm'
    ).forEach(function (form) {

        form.addEventListener('submit', function (event) {

            if (this.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            this.dataset.submitting = 'true';

            this.querySelectorAll('button[type="submit"]').forEach(function (button) {
                button.disabled = true;
                button.textContent = 'Processing...';
            });
        });
    });

    document.addEventListener('click', function (event) {

        USER_MODAL_IDS.forEach(function (modalId) {

            const modal = document.getElementById(modalId);

            if (event.target === modal) {
                closeModal(modalId);
            }
        });
    });

    document.addEventListener('keydown', function (event) {

        if (event.key !== 'Escape') {
            return;
        }

        USER_MODAL_IDS.forEach(function (modalId) {
            closeModal(modalId);
        });
    });

    @if(old('form_context') === 'create')
        openModal('createModal', 'createUsername');
    @endif

    @if(old('form_context') === 'edit' && session('edit_user_id'))
        @php
            $editUser = \App\Models\User::with('roles')->find(session('edit_user_id'));
        @endphp

        @if($editUser)
            openEditModal(
                @json($editUser->id),
                @json($editUser->username),
                @json($editUser->first_name),
                @json($editUser->last_name),
                @json($editUser->roles->first()->name ?? '')
            );
        @endif
    @elseif(session('edit_user_id'))
        @php
            $editUser = \App\Models\User::with('roles')->find(session('edit_user_id'));
        @endphp

        @if($editUser)
            openEditModal(
                @json($editUser->id),
                @json($editUser->username),
                @json($editUser->first_name),
                @json($editUser->last_name),
                @json($editUser->roles->first()->name ?? '')
            );
        @endif
    @endif
</script>

@endpush
