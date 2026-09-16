@extends('layouts.receptionist')

@section('title', 'Quick Book')

@php
    $today = now('Asia/Manila')->format('Y-m-d');
@endphp

@section('content')

<div
    x-data="quickBook()"
    x-init="init()"
    class="mx-auto w-full max-w-[1500px] space-y-5 pb-10"
>

    <div class="rounded-2xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-xl dark:border-gray-800/70 dark:bg-[#0f172a]/80">

        <div class="flex flex-col gap-4 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">

            <div class="flex flex-wrap items-center gap-2">

                <button
                    type="button"
                    @click="mode = 'now'; selectedDate = today; selectedSlot = null; selectedRoom = ''; loadNextSlots()"
                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition"
                    :class="mode === 'now'
                        ? 'border-brand-500 bg-brand-500 text-white shadow-lg shadow-brand-500/20'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-800/70 dark:text-gray-300 dark:hover:border-brand-500 dark:hover:text-brand-300'"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2"/>
                    </svg>
                    Right Now
                </button>

                <button
                    type="button"
                    @click="mode = 'future'; selectedSlot = null; selectedRoom = ''; initCalendar();"
                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition"
                    :class="mode === 'future'
                        ? 'border-brand-500 bg-brand-500 text-white shadow-lg shadow-brand-500/20'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-800/70 dark:text-gray-300 dark:hover:border-brand-500 dark:hover:text-brand-300'"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 2v4m8-4v4M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/>
                    </svg>
                    Future
                </button>

                <div class="hidden h-7 w-px bg-gray-200 dark:bg-gray-700 sm:block"></div>

                <div class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 dark:border-gray-700 dark:bg-gray-800/60">
                    <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                    <span
                        class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                        x-text="displayDate"
                    ></span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Booking status
                    </p>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        <span x-show="!submitting">Ready to book</span>
                        <span x-show="submitting">Saving booking...</span>
                    </p>
                </div>

                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl"
                    :class="submitting
                        ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'
                        : 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400'"
                >
                    <svg x-show="!submitting" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>

                    <svg x-show="submitting" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                </div>
            </div>

        </div>
    </div>


    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">

        <div class="min-w-0 space-y-5">

            <div class="rounded-2xl border border-gray-200/80 bg-white/90 shadow-sm dark:border-gray-800/70 dark:bg-[#0f172a]/90">

                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800/70">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-8a4 4 0 100-8 4 4 0 000 8zm9 0a3 3 0 100-6 3 3 0 000 6zm0 2a4 4 0 014 4v2"/>
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                                        Customer
                                    </h2>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        Search an existing customer or continue as guest
                                    </p>
                                </div>
                            </div>
                        </div>

                        <template x-if="customer.id">
                            <button
                                type="button"
                                @click="clearSelectedCustomer()"
                                class="inline-flex items-center gap-1.5 self-start rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600 dark:border-gray-700 dark:text-gray-300 dark:hover:border-red-800 dark:hover:bg-red-900/20 dark:hover:text-red-400 sm:self-auto"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Use guest
                            </button>
                        </template>

                    </div>
                </div>

                <div class="p-5">

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

                        <div class="lg:col-span-2">

                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Mobile number
                            </label>

                            <div class="relative">

                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 6.75c0-1.243 1.007-2.25 2.25-2.25h2.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125H5.625c.621 0-1.125.504-1.125 1.125A11.25 11.25 0 0015.75 21c.621 0 1.125-.504 1.125-1.125v-1.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v2.25A2.25 2.25 0 0119.125 23C9.196 23 1 14.804 1 4.875A2.25 2.25 0 013.25 2.625h2.25A1.125 1.125 0 016.625 3.75V6c0 .621-.504 1.125-1.125 1.125H3.25"/>
                                    </svg>
                                </div>

                                <input
                                    type="tel"
                                    x-model="customer.phone"
                                    @input="handlePhoneInput()"
                                    maxlength="11"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    placeholder="09XXXXXXXXX"
                                    class="w-full rounded-xl border border-gray-200 bg-white py-3.5 pl-11 pr-12 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/70 dark:text-white dark:placeholder:text-gray-500"
                                >

                                <div
                                    x-show="customer.phone.length > 0"
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4"
                                >
                                    <svg
                                        x-show="phoneValid"
                                        class="h-5 w-5 text-emerald-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-width="2" d="M5 12l4 4L19 6"/>
                                    </svg>

                                    <svg
                                        x-show="!phoneValid"
                                        class="h-5 w-5 text-red-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </div>

                            </div>

                            <p
                                x-show="customer.phone.length > 0 && !phoneValid"
                                class="mt-1.5 text-xs text-red-500"
                            >
                                Enter a valid Philippine mobile number.
                            </p>

                            <div
                                x-show="searchingCustomer"
                                class="mt-2 flex items-center gap-2 text-xs text-gray-400"
                            >
                                <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                                </svg>
                                Searching customers...
                            </div>

                            <div
                                x-show="customerSuggestions.length > 0"
                                x-transition
                                class="mt-2 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800"
                            >

                                <template x-for="item in customerSuggestions" :key="item.id">

                                    <button
                                        type="button"
                                        @click="selectCustomer(item)"
                                        class="flex w-full items-center gap-3 border-b border-gray-100 px-4 py-3 text-left transition last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50"
                                    >

                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                                            <span x-text="(item.name || '?').charAt(0).toUpperCase()"></span>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <p
                                                    class="truncate text-sm font-bold text-gray-800 dark:text-gray-100"
                                                    x-text="item.name"
                                                ></p>

                                                <span
                                                    x-show="item.is_registered"
                                                    class="rounded-full bg-brand-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-brand-700 dark:bg-brand-900/30 dark:text-brand-300"
                                                >
                                                    Registered
                                                </span>
                                            </div>

                                            <p
                                                class="mt-0.5 truncate text-xs text-gray-400"
                                                x-text="item.real_name && item.real_name !== item.name ? item.real_name + ' · ' + item.phone : item.phone"
                                            ></p>
                                        </div>

                                        <svg class="h-4 w-4 shrink-0 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>

                                    </button>

                                </template>

                            </div>

                        </div>


                        <div>

                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Preferred name / alias
                            </label>

                            <input
                                type="text"
                                x-model="customer.name"
                                @input="handleNameInput()"
                                maxlength="100"
                                :readonly="customer.id"
                                placeholder="Customer name"
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 read-only:cursor-default read-only:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/70 dark:text-white dark:placeholder:text-gray-500 dark:read-only:bg-gray-800"
                            >

                            <template x-if="customer.id && customer.real_name && customer.real_name !== customer.name">
                                <p class="mt-1.5 text-xs text-gray-400">
                                    Registered name:
                                    <span class="font-medium text-gray-500 dark:text-gray-300" x-text="customer.real_name"></span>
                                </p>
                            </template>

                        </div>


                        <div>

                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Customer type
                            </label>

                            <div class="flex h-[50px] items-center rounded-xl border border-gray-200 bg-gray-50 px-4 dark:border-gray-700 dark:bg-gray-800/50">

                                <template x-if="customer.id">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                            Registered customer
                                        </span>
                                    </div>
                                </template>

                                <template x-if="!customer.id">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">
                                            Guest / walk-in
                                        </span>
                                    </div>
                                </template>

                            </div>

                        </div>


                        <div class="lg:col-span-2">

                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Notes / special concerns
                            </label>

                            <textarea
                                x-model="customer.medical_notes"
                                rows="3"
                                maxlength="1000"
                                placeholder="Allergies, sensitivities, preferences, or other notes..."
                                class="w-full resize-none rounded-xl border border-gray-200 bg-white px-4 py-3.5 text-sm leading-6 text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/70 dark:text-white dark:placeholder:text-gray-500"
                            ></textarea>

                            <div class="mt-1 flex justify-end">
                                <span class="text-[11px] text-gray-400">
                                    <span x-text="customer.medical_notes.length"></span>/1000
                                </span>
                            </div>

                        </div>

                    </div>

                </div>
            </div>


            <div class="rounded-2xl border border-gray-200/80 bg-white/90 shadow-sm dark:border-gray-800/70 dark:bg-[#0f172a]/90">

                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800/70">

                    <div class="flex items-center justify-between gap-3">

                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2h-4M9 5a3 3 0 006 0M9 5a3 3 0 016 0m-3 7h.01M9 16h6"/>
                                </svg>
                            </div>

                            <div>
                                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                                    Services
                                </h2>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    Select one or more services
                                </p>
                            </div>
                        </div>

                        <div class="rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            <span x-text="selectedServices.length"></span>
                            selected
                        </div>

                    </div>

                </div>

                <div class="p-4 sm:p-5">

                    <div
                        x-show="categories.length === 0"
                        class="rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700"
                    >
                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                            No active services available.
                        </p>
                    </div>

                    <div class="space-y-3">

                        <template x-for="category in categories" :key="category.id">

                            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">

                                <button
                                    type="button"
                                    @click="toggleCat(category.id)"
                                    class="flex w-full items-center justify-between gap-3 bg-gray-50 px-4 py-3.5 text-left transition hover:bg-gray-100 dark:bg-gray-800/60 dark:hover:bg-gray-800"
                                >

                                    <div class="flex min-w-0 items-center gap-3">

                                        <div
                                            class="h-2.5 w-2.5 shrink-0 rounded-full"
                                            :style="`background-color:${category.color || '#14b8a6'}`"
                                        ></div>

                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-sm font-bold text-gray-800 dark:text-gray-100"
                                                x-text="category.name"
                                            ></p>

                                            <p class="text-[11px] text-gray-400">
                                                <span x-text="(category.services || []).length"></span>
                                                service<span x-show="(category.services || []).length !== 1">s</span>
                                            </p>
                                        </div>

                                    </div>

                                    <svg
                                        class="h-4 w-4 shrink-0 text-gray-400 transition-transform"
                                        :class="activeCategory === category.id ? 'rotate-180' : ''"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>

                                </button>


                                <div
                                    x-show="activeCategory === category.id"
                                    x-collapse
                                    class="border-t border-gray-200 dark:border-gray-700"
                                >

                                    <div class="grid grid-cols-1 gap-3 p-3 sm:grid-cols-2">

                                        <template x-for="service in category.services" :key="service.id">

                                            <label
                                                class="group relative cursor-pointer rounded-xl border p-4 transition"
                                                :class="isSelected(service.id)
                                                    ? 'border-brand-500 bg-brand-50/70 shadow-sm dark:border-brand-500/70 dark:bg-brand-900/20'
                                                    : 'border-gray-200 bg-white hover:border-brand-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40 dark:hover:border-brand-700 dark:hover:bg-gray-800'"
                                            >

                                                <input
                                                    type="checkbox"
                                                    class="sr-only"
                                                    :checked="isSelected(service.id)"
                                                    @change="toggleServiceFromCheckbox(service)"
                                                >

                                                <div class="flex items-start gap-3">

                                                    <div
                                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition"
                                                        :class="isSelected(service.id)
                                                            ? 'border-brand-500 bg-brand-500 text-white'
                                                            : 'border-gray-300 bg-white text-transparent dark:border-gray-600 dark:bg-gray-800'"
                                                    >
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-width="2" d="M5 12l4 4L19 6"/>
                                                        </svg>
                                                    </div>

                                                    <div class="min-w-0 flex-1">

                                                        <div class="flex items-start justify-between gap-2">

                                                            <p
                                                                class="text-sm font-bold leading-5 text-gray-800 dark:text-gray-100"
                                                                x-text="service.name"
                                                            ></p>

                                                            <span
                                                                x-show="service.is_package"
                                                                class="shrink-0 rounded-full bg-purple-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-purple-700 dark:bg-purple-900/30 dark:text-purple-300"
                                                            >
                                                                Package
                                                            </span>

                                                        </div>

                                                        <p
                                                            x-show="service.description"
                                                            class="mt-1 line-clamp-2 text-xs leading-5 text-gray-400 dark:text-gray-500"
                                                            x-text="service.description"
                                                        ></p>

                                                        <div class="mt-3 flex flex-wrap items-center gap-2">

                                                            <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2 py-1 text-[11px] font-semibold text-gray-500 dark:bg-gray-700/60 dark:text-gray-300">
                                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-width="2" d="M12 6v6l4 2"/>
                                                                </svg>
                                                                <span x-text="service.duration_minutes + ' min'"></span>
                                                            </span>

                                                            <span
                                                                x-show="service.requires_room"
                                                                class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2 py-1 text-[11px] font-semibold text-blue-600 dark:bg-blue-900/20 dark:text-blue-300"
                                                            >
                                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-width="2" d="M4 21V7a2 2 0 012-2h12a2 2 0 012 2v14M8 9h8M8 13h8M8 17h4"/>
                                                                </svg>
                                                                Room
                                                            </span>

                                                        </div>

                                                        <div class="mt-3 flex items-center gap-2">

                                                            <template x-if="service.discount_price && Number(service.discount_price) > 0 && Number(service.discount_price) < Number(service.price)">
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-sm font-extrabold text-brand-600 dark:text-brand-400"
                                                                        x-text="'₱' + Number(service.discount_price).toLocaleString('en-PH', {minimumFractionDigits:2})"
                                                                    ></span>

                                                                    <span
                                                                        class="text-xs text-gray-400 line-through"
                                                                        x-text="'₱' + Number(service.price).toLocaleString('en-PH', {minimumFractionDigits:2})"
                                                                    ></span>
                                                                </div>
                                                            </template>

                                                            <template x-if="!service.discount_price || Number(service.discount_price) <= 0 || Number(service.discount_price) >= Number(service.price)">
                                                                <span
                                                                    class="text-sm font-extrabold text-gray-800 dark:text-white"
                                                                    x-text="'₱' + Number(service.price || 0).toLocaleString('en-PH', {minimumFractionDigits:2})"
                                                                ></span>
                                                            </template>

                                                        </div>

                                                    </div>

                                                </div>

                                            </label>

                                        </template>

                                    </div>

                                </div>

                            </div>

                        </template>

                    </div>

                </div>
            </div>


            <div class="rounded-2xl border border-gray-200/80 bg-white/90 shadow-sm dark:border-gray-800/70 dark:bg-[#0f172a]/90">

                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800/70">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div class="flex items-center gap-2.5">

                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 2v4m8-4v4M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm3 8h2m2 0h2m2 0h2M8 17h2m2 0h2"/>
                                </svg>
                            </div>

                            <div>
                                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                                    Schedule
                                </h2>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    Select a staff member, date and available time
                                </p>
                            </div>

                        </div>

                        <div
                            x-show="mode === 'now'"
                            class="inline-flex items-center gap-2 self-start rounded-lg bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-700 dark:bg-brand-900/20 dark:text-brand-300"
                        >
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-brand-500"></span>
                            Finding next available slots
                        </div>

                    </div>

                </div>


                <div class="p-5">

                    <div
                        x-show="selectedServices.length === 0"
                        class="mb-5 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/10"
                    >
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-width="2" d="M12 9v3m0 4h.01M10.29 3.86l-8.18 14A2 2 0 003.84 21h16.32a2 2 0 001.73-3.14l-8.18-14a2 2 0 00-3.42 0z"/>
                        </svg>

                        <div>
                            <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                                Select a service first
                            </p>
                            <p class="mt-0.5 text-xs text-amber-700/80 dark:text-amber-400/80">
                                Available staff, rooms and time slots will be calculated from the selected services.
                            </p>
                        </div>
                    </div>


                    <div x-show="mode === 'now'" x-cloak>

                        <div
                            x-show="loadingNext"
                            class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <template x-for="i in 6" :key="i">
                                <div class="animate-pulse rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="h-4 w-1/2 rounded bg-gray-200 dark:bg-gray-700"></div>
                                    <div class="mt-3 h-7 w-2/3 rounded bg-gray-200 dark:bg-gray-700"></div>
                                    <div class="mt-3 h-3 w-1/3 rounded bg-gray-200 dark:bg-gray-700"></div>
                                </div>
                            </template>
                        </div>


                        <div
                            x-show="!loadingNext && selectedServices.length > 0 && nextSlots.length === 0"
                            class="rounded-xl border border-dashed border-gray-300 px-5 py-10 text-center dark:border-gray-700"
                        >
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-width="1.7" d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>

                            <p class="mt-3 text-sm font-bold text-gray-700 dark:text-gray-200">
                                No opening found right now
                            </p>

                            <p
                                x-show="nextDayHint"
                                class="mx-auto mt-1 max-w-md text-xs text-gray-400"
                                x-text="'Next opening: ' + nextDayHint"
                            ></p>

                            <button
                                type="button"
                                x-show="nextDayRaw"
                                @click="jumpToNextDay()"
                                class="mt-4 rounded-xl bg-brand-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-brand-500/20 transition hover:bg-brand-600"
                            >
                                View next opening
                            </button>
                        </div>


                        <div
                            x-show="!loadingNext && nextSlots.length > 0"
                            class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                        >

                            <template x-for="slot in nextSlots" :key="slot.staff_id + '-' + slot.time">

                                <button
                                    type="button"
                                    @click="selectQuickSlot(slot)"
                                    class="group rounded-xl border p-4 text-left transition"
                                    :class="selectedSlot && selectedSlot.staff_id == slot.staff_id && selectedSlot.time === slot.time
                                        ? 'border-brand-500 bg-brand-50 shadow-sm dark:border-brand-500/70 dark:bg-brand-900/20'
                                        : 'border-gray-200 bg-white hover:border-brand-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40 dark:hover:border-brand-700 dark:hover:bg-gray-800'"
                                >

                                    <div class="flex items-start justify-between gap-3">

                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-sm font-bold text-gray-800 dark:text-gray-100"
                                                x-text="slot.staff_name"
                                            ></p>

                                            <p
                                                class="mt-1 text-xs text-gray-400"
                                                x-text="slot.display || (slot.time + ' – ' + slot.end_time)"
                                            ></p>
                                        </div>

                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition"
                                            :class="selectedSlot && selectedSlot.staff_id == slot.staff_id && selectedSlot.time === slot.time
                                                ? 'bg-brand-500 text-white'
                                                : 'bg-gray-100 text-gray-400 group-hover:bg-brand-100 group-hover:text-brand-600 dark:bg-gray-700 dark:text-gray-400 dark:group-hover:bg-brand-900/30 dark:group-hover:text-brand-300'"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/>
                                            </svg>
                                        </div>

                                    </div>

                                    <div class="mt-4 flex items-center justify-between">

                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Available
                                        </span>

                                        <span class="text-[11px] text-gray-400">
                                            <span x-text="(slot.free_rooms || []).length"></span>
                                            room<span x-show="(slot.free_rooms || []).length !== 1">s</span>
                                        </span>

                                    </div>

                                </button>

                            </template>

                        </div>

                    </div>


                    <div x-show="mode === 'future'" x-cloak>

                        <div class="mb-5">

                            <div class="mb-2 flex items-center justify-between">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Selected date
                                </label>

                                <button
                                    type="button"
                                    @click="selectedDate = today; initCalendar()"
                                    class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                                >
                                    Today
                                </button>
                            </div>

                            <input
                                type="date"
                                x-model="selectedDate"
                                @change="initCalendar(); loadGaps()"
                                :min="today"
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-800 outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/70 dark:text-white"
                            >

                        </div>


                        <div
                            class="mb-5 rounded-xl border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-800/40"
                        >

                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Staff
                            </label>

                            <select
                                x-model="selectedStaff"
                                @change="loadGaps()"
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-800 outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="">Select staff</option>

                                @foreach($staff as $member)
                                    <option value="{{ $member->id }}">
                                        {{ $member->full_name ?? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}
                                    </option>
                                @endforeach
                            </select>

                        </div>


                        <div
                            x-show="calendarReady"
                            class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800/50"
                        >
                            <div id="quickBookCalendar" class="p-2 sm:p-4"></div>
                        </div>


                        <div
                            x-show="selectedStaff && selectedDate"
                            class="mt-5"
                        >

                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-gray-800 dark:text-gray-100">
                                        Available times
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        <span x-text="selectedStaffName || 'Selected staff'"></span>
                                    </p>
                                </div>

                                <span
                                    x-show="loadingGaps"
                                    class="inline-flex items-center gap-1.5 text-xs text-gray-400"
                                >
                                    <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                                    </svg>
                                    Checking
                                </span>
                            </div>


                            <div
                                x-show="!loadingGaps && gaps.length > 0"
                                class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4"
                            >

                                <template x-for="gap in gaps" :key="gap.time">

                                    <button
                                        type="button"
                                        @click="selectSlot(gap)"
                                        class="rounded-xl border px-3 py-3 text-left transition"
                                        :class="selectedSlot && selectedSlot.time === gap.time
                                            ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-500/70 dark:bg-brand-900/20 dark:text-brand-300'
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-brand-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-brand-700'"
                                    >
                                        <p class="text-sm font-bold" x-text="gap.display || gap.time"></p>
                                        <p class="mt-1 text-[10px] text-gray-400">
                                            <span x-text="(gap.free_rooms || []).length"></span>
                                            available room<span x-show="(gap.free_rooms || []).length !== 1">s</span>
                                        </p>
                                    </button>

                                </template>

                            </div>


                            <div
                                x-show="!loadingGaps && selectedStaff && gaps.length === 0"
                                class="rounded-xl border border-dashed border-gray-300 px-5 py-8 text-center dark:border-gray-700"
                            >
                                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                                    No available times for this staff member.
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    Try another date or staff member.
                                </p>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="rounded-2xl border border-gray-200/80 bg-white/90 shadow-sm dark:border-gray-800/70 dark:bg-[#0f172a]/90">

                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800/70">

                    <div class="flex items-center gap-2.5">

                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300">
                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-width="1.8" d="M3 12h18M12 3v18M5 5l14 14M19 5L5 19"/>
                            </svg>
                        </div>

                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                                Availability
                            </h2>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Quick view of the selected booking slot
                            </p>
                        </div>

                    </div>

                </div>


                <div class="p-5">

                    <div
                        x-show="!selectedSlot"
                        class="rounded-xl border border-dashed border-gray-300 px-5 py-8 text-center dark:border-gray-700"
                    >

                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-width="1.6" d="M12 6v6l4 2"/>
                            </svg>
                        </div>

                        <p class="mt-3 text-sm font-semibold text-gray-600 dark:text-gray-300">
                            Select a time slot
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            Staff and room availability will appear here.
                        </p>

                    </div>


                    <div x-show="selectedSlot" x-cloak class="space-y-4">

                        <div class="flex flex-col gap-3 rounded-xl border border-brand-200 bg-brand-50/70 p-4 dark:border-brand-900/50 dark:bg-brand-900/10 sm:flex-row sm:items-center sm:justify-between">

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-brand-600 dark:text-brand-400">
                                    Selected slot
                                </p>

                                <p
                                    class="mt-1 text-base font-extrabold text-gray-900 dark:text-white"
                                    x-text="selectedSlot?.display || ''"
                                ></p>

                                <p
                                    class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                    x-text="selectedSlot?.staff_name || selectedStaffName || ''"
                                ></p>
                            </div>

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-500 text-white shadow-lg shadow-brand-500/20">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </div>

                        </div>


                        <div>

                            <div class="mb-2 flex items-center justify-between">

                                <div>
                                    <p class="text-sm font-bold text-gray-800 dark:text-gray-100">
                                        Rooms
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Choose a room when required
                                    </p>
                                </div>

                                <span class="rounded-lg bg-gray-100 px-2 py-1 text-[10px] font-bold text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    <span x-text="freeRooms.length"></span> free
                                </span>

                            </div>


                            <div
                                x-show="!requiresRoom"
                                class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/40"
                            >
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-width="1.6" d="M5 12h14"/>
                                    </svg>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                                        Selected services do not require a room.
                                    </span>
                                </div>
                            </div>


                            <div
                                x-show="requiresRoom"
                                class="grid grid-cols-1 gap-2 sm:grid-cols-2"
                            >

                                <template x-for="room in freeRooms" :key="room.id">

                                    <button
                                        type="button"
                                        @click="selectedRoom = String(room.id)"
                                        class="flex items-center justify-between rounded-xl border p-3 text-left transition"
                                        :class="String(selectedRoom) === String(room.id)
                                            ? 'border-brand-500 bg-brand-50 dark:border-brand-500/70 dark:bg-brand-900/20'
                                            : 'border-gray-200 bg-white hover:border-brand-300 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:border-brand-700'"
                                    >

                                        <div class="flex min-w-0 items-center gap-3">

                                            <div
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                                :class="String(selectedRoom) === String(room.id)
                                                    ? 'bg-brand-500 text-white'
                                                    : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-300'"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-width="1.7" d="M4 21V7a2 2 0 012-2h12a2 2 0 012 2v14M8 9h8M8 13h8M8 17h4"/>
                                                </svg>
                                            </div>

                                            <div class="min-w-0">
                                                <p
                                                    class="truncate text-sm font-bold text-gray-800 dark:text-gray-100"
                                                    x-text="room.name"
                                                ></p>

                                                <p class="text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                                                    Available
                                                </p>
                                            </div>

                                        </div>

                                        <div
                                            class="flex h-5 w-5 items-center justify-center rounded-full border"
                                            :class="String(selectedRoom) === String(room.id)
                                                ? 'border-brand-500 bg-brand-500 text-white'
                                                : 'border-gray-300 dark:border-gray-600'"
                                        >
                                            <svg
                                                x-show="String(selectedRoom) === String(room.id)"
                                                class="h-3 w-3"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-width="2" d="M5 12l4 4L19 6"/>
                                            </svg>
                                        </div>

                                    </button>

                                </template>

                            </div>


                            <div
                                x-show="requiresRoom && freeRooms.length === 0"
                                class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900/50 dark:bg-red-900/10"
                            >
                                <p class="text-xs font-semibold text-red-700 dark:text-red-300">
                                    No room is available for this time.
                                </p>
                            </div>

                        </div>

                    </div>

                </div>
            </div>

        </div>


        <aside class="xl:sticky xl:top-24 xl:self-start">

            <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm dark:border-gray-800/70 dark:bg-[#0f172a]">

                <div class="border-b border-gray-100 bg-gradient-to-r from-brand-50 to-white px-5 py-4 dark:border-gray-800/70 dark:from-brand-900/20 dark:to-[#0f172a]">

                    <div class="flex items-center justify-between">

                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-brand-600 dark:text-brand-400">
                                Booking summary
                            </p>

                            <p class="mt-1 text-lg font-extrabold text-gray-900 dark:text-white">
                                Review & book
                            </p>
                        </div>

                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-500 text-white shadow-lg shadow-brand-500/20">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-width="1.7" d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </div>

                    </div>

                </div>


                <div class="p-5">

                    <div
                        x-show="selectedServices.length === 0"
                        class="mb-4 rounded-xl border border-dashed border-gray-300 p-4 text-center dark:border-gray-700"
                    >
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            No services selected
                        </p>
                    </div>


                    <div
                        x-show="selectedServices.length > 0"
                        class="space-y-2"
                    >

                        <template x-for="service in selectedServiceObjects" :key="service.id">

                            <div class="flex items-start justify-between gap-3 rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-gray-800/60">

                                <div class="min-w-0">
                                    <p
                                        class="truncate text-xs font-bold text-gray-700 dark:text-gray-200"
                                        x-text="service.name"
                                    ></p>

                                    <p
                                        class="mt-0.5 text-[10px] text-gray-400"
                                        x-text="service.duration_minutes + ' min'"
                                    ></p>
                                </div>

                                <span
                                    class="shrink-0 text-xs font-bold text-gray-700 dark:text-gray-200"
                                    x-text="'₱' + Number(serviceEffectivePrice(service)).toLocaleString('en-PH', {minimumFractionDigits:2})"
                                ></span>

                            </div>

                        </template>

                    </div>


                    <div class="my-5 border-t border-dashed border-gray-200 dark:border-gray-700"></div>


                    <div class="space-y-3">

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-400">Customer</span>
                            <span
                                class="max-w-[200px] truncate text-right text-xs font-bold text-gray-700 dark:text-gray-200"
                                x-text="customer.name || '—'"
                            ></span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-400">Phone</span>
                            <span
                                class="text-right text-xs font-semibold text-gray-600 dark:text-gray-300"
                                x-text="customer.phone || '—'"
                            ></span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-400">Date</span>
                            <span
                                class="text-right text-xs font-semibold text-gray-600 dark:text-gray-300"
                                x-text="displayDate"
                            ></span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-400">Time</span>
                            <span
                                class="text-right text-xs font-bold text-gray-700 dark:text-gray-200"
                                x-text="selectedSlot?.display || '—'"
                            ></span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-400">Staff</span>
                            <span
                                class="max-w-[200px] truncate text-right text-xs font-bold text-gray-700 dark:text-gray-200"
                                x-text="selectedStaffName || '—'"
                            ></span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-400">Room</span>
                            <span
                                class="max-w-[200px] truncate text-right text-xs font-bold text-gray-700 dark:text-gray-200"
                                x-text="selectedRoomName || (!requiresRoom ? 'Not required' : '—')"
                            ></span>
                        </div>

                    </div>


                    <div class="my-5 border-t border-gray-200 dark:border-gray-700"></div>


                    <div class="grid grid-cols-2 gap-3">

                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-800/60">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
                                Duration
                            </p>

                            <p
                                class="mt-1 text-sm font-extrabold text-gray-800 dark:text-white"
                                x-text="totalDuration + ' min'"
                            ></p>
                        </div>

                        <div class="rounded-xl bg-brand-50 p-3 dark:bg-brand-900/20">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400">
                                Total
                            </p>

                            <p
                                class="mt-1 text-sm font-extrabold text-brand-700 dark:text-brand-300"
                                x-text="'₱' + Number(totalPrice).toLocaleString('en-PH', {minimumFractionDigits:2})"
                            ></p>
                        </div>

                    </div>


                    <div class="mt-5 space-y-4">

                        <div>

                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Payment method
                            </label>

                            <select
                                x-model="payment.method"
                                class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-3 text-sm font-semibold text-gray-700 outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            >
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="gcash">GCash</option>
                                <option value="paymaya">PayMaya</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>

                        </div>


                        <div>

                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Amount received
                            </label>

                            <div class="relative">

                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-gray-400">
                                    ₱
                                </span>

                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    x-model.number="payment.amount"
                                    class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-9 pr-4 text-sm font-bold text-gray-800 outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >

                            </div>

                            <p class="mt-1.5 text-[11px] text-gray-400">
                                Enter 0 if payment has not been collected yet.
                            </p>

                        </div>


                        <div
                            x-show="payment.amount > 0 && payment.amount >= totalPrice"
                            class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 dark:border-emerald-900/50 dark:bg-emerald-900/10"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                    Change
                                </span>

                                <span
                                    class="text-sm font-extrabold text-emerald-700 dark:text-emerald-300"
                                    x-text="'₱' + Number(Math.max(0, payment.amount - totalPrice)).toLocaleString('en-PH', {minimumFractionDigits:2})"
                                ></span>
                            </div>
                        </div>

                    </div>


                    <button
                        type="button"
                        @click="submitBooking()"
                        :disabled="!canSubmit || submitting"
                        class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3.5 text-sm font-bold text-white shadow-lg transition disabled:cursor-not-allowed disabled:opacity-40"
                        :class="canSubmit && !submitting
                            ? 'bg-brand-500 shadow-brand-500/20 hover:bg-brand-600'
                            : 'bg-gray-400 shadow-none dark:bg-gray-700'"
                    >

                        <svg
                            x-show="!submitting"
                            class="h-4.5 w-4.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-width="1.8" d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>

                        <svg
                            x-show="submitting"
                            class="h-4.5 w-4.5 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>

                        <span x-text="submitting ? 'Creating booking...' : 'Create booking'"></span>

                    </button>


                    <div
                        x-show="!canSubmit"
                        class="mt-3 text-center"
                    >
                        <p class="text-[11px] text-gray-400">
                            Complete the customer, service, staff, time and room details before booking.
                        </p>
                    </div>

                </div>

            </div>

        </aside>

    </div>


    <div
        x-show="showSuccess"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
    >

        <div
            x-show="showSuccess"
            x-transition.scale
            class="w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-[#0f172a]"
        >

            <div class="p-7 text-center">

                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-width="2.2" d="M5 12l4 4L19 6"/>
                    </svg>
                </div>

                <p class="mt-5 text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-400">
                    Booking created
                </p>

                <h3 class="mt-2 text-xl font-extrabold text-gray-900 dark:text-white">
                    Appointment saved successfully
                </h3>

                <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-gray-500 dark:text-gray-400">
                    The appointment has been added to the system and is ready for the next receptionist workflow.
                </p>


                <div class="mt-5 rounded-xl bg-gray-50 p-4 text-left dark:bg-gray-800/70">

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs text-gray-400">Customer</span>
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200" x-text="customer.name"></span>
                    </div>

                    <div class="mt-2 flex items-center justify-between gap-3">
                        <span class="text-xs text-gray-400">Schedule</span>
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200" x-text="displayDate + ' · ' + (selectedSlot?.display || '')"></span>
                    </div>

                    <div class="mt-2 flex items-center justify-between gap-3">
                        <span class="text-xs text-gray-400">Total</span>
                        <span class="text-xs font-extrabold text-brand-600 dark:text-brand-400" x-text="'₱' + Number(totalPrice).toLocaleString('en-PH', {minimumFractionDigits:2})"></span>
                    </div>

                </div>


                <button
                    type="button"
                    @click="window.location.href='{{ route('receptionist.active') }}'"
                    class="mt-6 w-full rounded-xl bg-brand-500 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-500/20 transition hover:bg-brand-600"
                >
                    Continue
                </button>

            </div>

        </div>

    </div>


    <div
        x-show="errorMessage"
        x-cloak
        x-transition
        class="fixed bottom-5 right-5 z-[110] w-[calc(100%-2rem)] max-w-md"
    >

        <div class="rounded-2xl border border-red-200 bg-white p-4 shadow-2xl dark:border-red-900/60 dark:bg-[#0f172a]">

            <div class="flex items-start gap-3">

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-width="2" d="M12 9v3m0 4h.01M10.29 3.86l-8.18 14A2 2 0 003.84 21h16.32a2 2 0 001.73-3.14l-8.18-14a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                        Booking could not be created
                    </p>

                    <p
                        class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400"
                        x-text="errorMessage"
                    ></p>
                </div>

                <button
                    type="button"
                    @click="errorMessage = ''"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

            </div>

        </div>

    </div>

</div>


<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css"
/>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<style>
    [x-cloak] {
        display: none !important;
    }

    .fc {
        --fc-border-color: #e5e7eb;
        --fc-button-bg-color: #14b8a6;
        --fc-button-border-color: #14b8a6;
        --fc-button-hover-bg-color: #0d9488;
        --fc-button-hover-border-color: #0d9488;
        --fc-button-active-bg-color: #0f766e;
        --fc-button-active-border-color: #0f766e;
        --fc-today-bg-color: rgba(20,184,166,.08);
        --fc-neutral-bg-color: #f8fafc;
        --fc-page-bg-color: transparent;
        --fc-list-event-hover-bg-color: #f8fafc;
    }

    .fc .fc-toolbar-title {
        font-size: 1rem;
        font-weight: 800;
        color: #1f2937;
    }

    .fc .fc-button {
        border-radius: .7rem;
        box-shadow: none;
        font-size: .75rem;
        font-weight: 700;
        padding: .45rem .7rem;
    }

    .fc .fc-daygrid-day-number {
        font-size: .75rem;
        font-weight: 700;
        color: #64748b;
    }

    .fc .fc-col-header-cell-cushion {
        font-size: .7rem;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .fc .fc-daygrid-day.fc-day-today {
        background: rgba(20,184,166,.07);
    }

    .fc .fc-daygrid-day:hover {
        background: rgba(20,184,166,.05);
        cursor: pointer;
    }

    .dark .fc {
        --fc-border-color: #334155;
        --fc-neutral-bg-color: #1e293b;
        --fc-list-event-hover-bg-color: #1e293b;
        --fc-page-bg-color: transparent;
    }

    .dark .fc .fc-toolbar-title,
    .dark .fc .fc-daygrid-day-number,
    .dark .fc .fc-col-header-cell-cushion {
        color: #e2e8f0;
    }

    .dark .fc .fc-button-primary {
        background: #0f766e;
        border-color: #0f766e;
    }

    .dark .fc .fc-button-primary:hover {
        background: #0d9488;
        border-color: #0d9488;
    }

    .dark .fc .fc-daygrid-day.fc-day-today {
        background: rgba(20,184,166,.1);
    }

    .dark .fc .fc-scrollgrid,
    .dark .fc td,
    .dark .fc th {
        border-color: #334155;
    }

    @media (max-width: 640px) {
        .fc .fc-toolbar {
            flex-direction: column;
            gap: .75rem;
        }

        .fc .fc-toolbar-chunk {
            display: flex;
            justify-content: center;
        }

        .fc .fc-toolbar-title {
            font-size: .9rem;
        }
    }
</style>


<script>
    function quickBook() {
        return {

            today: @json($today),

            mode: 'now',

            categories: @json($categories),

            staff: @json($staff),

            rooms: @json($rooms),

            customer: {
                id: null,
                name: '',
                real_name: '',
                nickname: '',
                phone: '',
                selectedPhone: '',
                medical_notes: '',
                is_registered: false
            },

            customerSuggestions: [],
            searchingCustomer: false,
            searchTimer: null,

            selectedServices: [],

            selectedDate: @json($today),

            selectedStaff: '',
            selectedStaffName: '',

            selectedSlot: null,

            selectedRoom: '',

            freeRooms: [],

            nextSlots: [],
            nextDayRaw: '',
            nextDayHint: '',
            loadingNext: false,

            gaps: [],
            loadingGaps: false,

            calendar: null,
            calendarReady: false,

            activeCategory: null,

            payment: {
                method: 'cash',
                amount: 0
            },

            submitting: false,
            showSuccess: false,
            errorMessage: '',

            suppressStaffWatch: false,

            phoneValid: false,

            get displayDate() {
                if (!this.selectedDate) {
                    return 'No date selected';
                }

                return new Date(this.selectedDate + 'T00:00:00')
                    .toLocaleDateString('en-PH', {
                        weekday: 'short',
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    });
            },

            get selectedServiceObjects() {
                const all = [];

                this.categories.forEach(category => {
                    (category.services || []).forEach(service => {
                        if (this.selectedServices.includes(Number(service.id))) {
                            all.push(service);
                        }
                    });
                });

                return all;
            },

            get totalDuration() {
                return this.selectedServiceObjects.reduce((total, service) => {
                    return total + Number(service.duration_minutes || 0);
                }, 0);
            },

            get totalPrice() {
                return this.selectedServiceObjects.reduce((total, service) => {
                    return total + this.serviceEffectivePrice(service);
                }, 0);
            },

            get requiresRoom() {
                return this.selectedServiceObjects.some(service => {
                    return service.requires_room == true ||
                        service.requires_room === 1 ||
                        service.requires_room === '1';
                });
            },

            get selectedRoomName() {
                if (!this.selectedRoom) {
                    return '';
                }

                const room = this.rooms.find(room => String(room.id) === String(this.selectedRoom));

                if (room) {
                    return room.name;
                }

                const available = this.freeRooms.find(room => String(room.id) === String(this.selectedRoom));

                return available?.name || '';
            },

            get canSubmit() {
                return !!(
                    this.customer.name &&
                    this.customer.phone &&
                    this.selectedServices.length > 0 &&
                    this.selectedStaff &&
                    this.selectedSlot &&
                    (!this.requiresRoom || this.selectedRoom) &&
                    Number(this.payment.amount) >= 0
                );
            },

            init() {

                this.categories = Array.isArray(this.categories)
                    ? this.categories
                    : [];

                this.staff = Array.isArray(this.staff)
                    ? this.staff
                    : [];

                this.rooms = Array.isArray(this.rooms)
                    ? this.rooms
                    : [];

                if (this.categories.length > 0) {
                    this.activeCategory = this.categories[0].id;
                }

                this.phoneValid = this.isValidPhone(this.customer.phone);

                this.$watch('selectedServices', () => {
                    this.selectedSlot = null;
                    this.selectedRoom = '';
                    this.freeRooms = [];

                    if (this.mode === 'now') {
                        this.loadNextSlots();
                    } else if (this.selectedStaff) {
                        this.loadGaps();
                    }
                });

                this.$watch('selectedStaff', () => {

                    if (this.suppressStaffWatch) {
                        this.suppressStaffWatch = false;
                        return;
                    }

                    const member = this.staff.find(
                        item => String(item.id) === String(this.selectedStaff)
                    );

                    this.selectedStaffName = member
                        ? (
                            member.full_name ||
                            `${member.first_name || ''} ${member.last_name || ''}`.trim()
                        )
                        : '';

                    this.selectedSlot = null;
                    this.selectedRoom = '';
                    this.freeRooms = [];

                    if (this.mode === 'future') {
                        this.loadGaps();
                    }
                });

                this.$nextTick(() => {

                    if (this.mode === 'now') {
                        this.loadNextSlots();
                    }

                    this.initCalendar();

                });

            },

            serviceEffectivePrice(service) {

                const price = Number(service.price || 0);
                const discount = Number(service.discount_price || 0);

                if (discount > 0 && discount < price) {
                    return discount;
                }

                return price;
            },

            isSelected(id) {
                return this.selectedServices.includes(Number(id));
            },

            toggleCat(id) {
                this.activeCategory =
                    this.activeCategory === id
                        ? null
                        : id;
            },

            toggleServiceFromCheckbox(service) {

                const id = Number(service.id);

                if (this.isSelected(id)) {

                    this.selectedServices = this.selectedServices.filter(
                        serviceId => serviceId !== id
                    );

                } else {

                    this.selectedServices = [
                        ...this.selectedServices,
                        id
                    ];

                }
            },

            isValidPhone(phone) {

                if (!phone) {
                    return false;
                }

                return /^09\d{9}$/.test(String(phone));
            },

            handlePhoneInput() {

                this.customer.phone = String(this.customer.phone || '')
                    .replace(/\D/g, '')
                    .slice(0, 11);

                this.phoneValid = this.isValidPhone(this.customer.phone);

                if (
                    this.customer.id &&
                    this.customer.selectedPhone &&
                    this.customer.phone !== this.customer.selectedPhone
                ) {
                    this.clearSelectedCustomer(true);
                }

                clearTimeout(this.searchTimer);

                if (this.customer.phone.length < 6) {
                    this.customerSuggestions = [];
                    return;
                }

                this.searchTimer = setTimeout(() => {
                    this.searchCustomer();
                }, 250);

            },

            handleNameInput() {

                if (
                    this.customer.id &&
                    this.customer.name !== this.customer.nickname
                ) {
                    const typedName = this.customer.name;

                    this.clearSelectedCustomer(true);

                    this.customer.name = typedName;
                }
            },

            clearSelectedCustomer(preserveInput = false) {

                const currentName = this.customer.name;
                const currentPhone = this.customer.phone;
                const currentNotes = this.customer.medical_notes;

                this.customer = {
                    id: null,
                    name: preserveInput ? currentName : '',
                    real_name: '',
                    nickname: '',
                    phone: preserveInput ? currentPhone : '',
                    selectedPhone: '',
                    medical_notes: preserveInput ? currentNotes : '',
                    is_registered: false
                };

                this.phoneValid = this.isValidPhone(this.customer.phone);
                this.customerSuggestions = [];

            },

            async searchCustomer() {

                if (this.customer.phone.length < 6) {
                    this.customerSuggestions = [];
                    return;
                }

                this.searchingCustomer = true;

                try {

                    const params = new URLSearchParams({
                        phone: this.customer.phone
                    });

                    const response = await fetch(
                        `/api/customers/lookup?${params.toString()}`,
                        {
                            headers: {
                                'Accept': 'application/json'
                            }
                        }
                    );

                    if (!response.ok) {
                        throw new Error('Customer lookup failed.');
                    }

                    const data = await response.json();

                    if (Array.isArray(data)) {
                        this.customerSuggestions = data;
                    } else {
                        this.customerSuggestions = [];
                    }

                } catch (error) {

                    this.customerSuggestions = [];

                } finally {

                    this.searchingCustomer = false;

                }
            },

            selectCustomer(item) {

                this.customer = {
                    id: item.id || null,
                    name: item.name || item.nickname || item.real_name || '',
                    real_name: item.real_name || '',
                    nickname: item.nickname || '',
                    phone: item.phone || '',
                    selectedPhone: item.phone || '',
                    medical_notes: item.medical_notes || '',
                    is_registered: !!item.is_registered
                };

                this.phoneValid = this.isValidPhone(this.customer.phone);
                this.customerSuggestions = [];

            },

            async loadNextSlots() {

                if (this.mode !== 'now') {
                    return;
                }

                if (this.selectedServices.length === 0) {
                    this.nextSlots = [];
                    this.nextDayRaw = '';
                    this.nextDayHint = '';
                    return;
                }

                this.loadingNext = true;

                try {

                    const duration = this.totalDuration || 60;

                    const serviceIds = this.selectedServices.map(
                        id => Number(id)
                    );

                    const params = new URLSearchParams({
                        date: this.today,
                        duration: duration,
                        buffer_minutes: 5
                    });

                    serviceIds.forEach(id => {
                        params.append('services[]', id);
                    });

                    const response = await fetch(
                        `/api/booking/next-slots?${params.toString()}`,
                        {
                            headers: {
                                'Accept': 'application/json'
                            }
                        }
                    );

                    if (!response.ok) {
                        throw new Error('Unable to load available slots.');
                    }

                    const data = await response.json();

                    this.nextSlots = data.slots || [];

                    this.nextDayRaw = data.next_day_hint || '';

                    this.nextDayHint = data.next_day_hint
                        ? new Date(data.next_day_hint + 'T00:00:00')
                            .toLocaleDateString('en-PH', {
                                weekday: 'short',
                                month: 'short',
                                day: 'numeric'
                            }) +
                            (
                                data.next_open_time
                                    ? ' at ' + data.next_open_time
                                    : ''
                            )
                        : '';

                } catch (error) {

                    this.nextSlots = [];
                    this.nextDayRaw = '';
                    this.nextDayHint = '';

                } finally {

                    this.loadingNext = false;

                }
            },

            selectQuickSlot(slot) {

                this.suppressStaffWatch = true;

                this.selectedStaff = String(slot.staff_id);

                this.selectedStaffName = slot.staff_name || '';

                this.selectedSlot = {
                    ...slot,
                    time: slot.time,
                    end_time: slot.end_time || ''
                };

                this.freeRooms = Array.isArray(slot.free_rooms)
                    ? slot.free_rooms
                    : [];

                this.selectedRoom = '';

                if (this.freeRooms.length === 1) {
                    this.selectedRoom = String(this.freeRooms[0].id);
                }

                this.$nextTick(() => {
                    this.suppressStaffWatch = false;
                });

            },

            jumpToNextDay() {

                if (!this.nextDayRaw) {
                    return;
                }

                this.mode = 'future';
                this.selectedDate = this.nextDayRaw;
                this.selectedSlot = null;
                this.selectedRoom = '';
                this.freeRooms = [];

                this.$nextTick(() => {
                    this.initCalendar();

                    if (this.selectedStaff) {
                        this.loadGaps();
                    }
                });

            },

            initCalendar() {

                if (this.mode !== 'future') {
                    return;
                }

                this.$nextTick(() => {

                    const element = document.getElementById('quickBookCalendar');

                    if (!element) {
                        return;
                    }

                    if (this.calendar) {
                        this.calendar.destroy();
                        this.calendar = null;
                    }

                    this.calendar = new FullCalendar.Calendar(
                        element,
                        {
                            initialView: window.innerWidth < 640
                                ? 'dayGridMonth'
                                : 'dayGridMonth',

                            initialDate: this.selectedDate || this.today,

                            validRange: {
                                start: this.today,
                                end: (() => {
                                    const date = new Date(
                                        this.today + 'T00:00:00'
                                    );

                                    date.setDate(date.getDate() + 60);

                                    return date
                                        .toISOString()
                                        .split('T')[0];
                                })()
                            },

                            height: 'auto',

                            fixedWeekCount: false,

                            showNonCurrentDates: false,

                            headerToolbar: {
                                left: 'prev,next',
                                center: 'title',
                                right: 'today'
                            },

                            dateClick: info => {

                                if (info.dateStr < this.today) {
                                    return;
                                }

                                this.selectedDate = info.dateStr;
                                this.selectedSlot = null;
                                this.selectedRoom = '';
                                this.freeRooms = [];

                                if (this.selectedStaff) {
                                    this.loadGaps();
                                }

                                this.calendar?.gotoDate(info.dateStr);

                            },

                            datesSet: () => {
                                this.calendarReady = true;
                            }

                        }
                    );

                    this.calendar.render();

                    this.calendarReady = true;

                });

            },

            async loadGaps(preserveSelection = false) {

                if (this.mode !== 'future') {
                    return;
                }

                if (!this.selectedStaff || !this.selectedDate) {
                    this.gaps = [];
                    return;
                }

                if (this.selectedServices.length === 0) {
                    this.gaps = [];
                    return;
                }

                this.loadingGaps = true;

                const oldSlot = preserveSelection
                    ? this.selectedSlot
                    : null;

                try {

                    const params = new URLSearchParams({
                        date: this.selectedDate,
                        staff_id: this.selectedStaff,
                        duration: this.totalDuration || 60
                    });

                    this.selectedServices.forEach(id => {
                        params.append('services[]', id);
                    });

                    const response = await fetch(
                        `/api/booking/staff-gaps?${params.toString()}`,
                        {
                            headers: {
                                'Accept': 'application/json'
                            }
                        }
                    );

                    if (!response.ok) {
                        throw new Error('Unable to load staff availability.');
                    }

                    const data = await response.json();

                    this.gaps = data.gaps || [];

                    if (oldSlot) {

                        const stillAvailable = this.gaps.find(
                            gap => gap.time === oldSlot.time
                        );

                        if (stillAvailable) {
                            this.selectSlot(stillAvailable);
                        } else {
                            this.selectedSlot = null;
                            this.selectedRoom = '';
                            this.freeRooms = [];
                        }

                    }

                } catch (error) {

                    this.gaps = [];

                } finally {

                    this.loadingGaps = false;

                }
            },

            selectSlot(slot) {

                this.selectedSlot = {
                    ...slot
                };

                this.freeRooms = Array.isArray(slot.free_rooms)
                    ? slot.free_rooms
                    : [];

                this.selectedRoom = '';

                if (this.freeRooms.length === 1) {
                    this.selectedRoom = String(this.freeRooms[0].id);
                }

            },

            async submitBooking() {

                if (!this.canSubmit || this.submitting) {
                    return;
                }

                this.submitting = true;
                this.errorMessage = '';

                try {

                    const startTime = this.selectedSlot.time;

                    let endTime = this.selectedSlot.end_time;

                    if (!endTime) {

                        const parts = startTime.split(':');

                        const date = new Date(
                            `2000-01-01T${parts[0]}:${parts[1]}:00`
                        );

                        date.setMinutes(
                            date.getMinutes() + this.totalDuration
                        );

                        endTime =
                            String(date.getHours()).padStart(2, '0') +
                            ':' +
                            String(date.getMinutes()).padStart(2, '0');

                    }

                    const formData = {

                        source: 'receptionist',

                        customer_id: this.customer.id || null,

                        services: this.selectedServices.map(
                            id => Number(id)
                        ),

                        appointment_date: this.selectedDate,

                        start_time: startTime,

                        end_time: endTime,

                        guest_first_name: this.customer.name,

                        guest_phone: this.customer.phone,

                        medical_notes: this.customer.medical_notes,

                        staff_id: this.selectedStaff,

                        room_id: this.selectedRoom || null,

                        payment_method: this.payment.method,

                        payment_amount:
                            parseFloat(this.payment.amount) || 0,

                        payment_type: 'full',

                        walk_in_now: this.mode === 'now'

                    };

                    const response = await fetch(
                        "{{ route('booking.store') }}",
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN':
                                    document.querySelector(
                                        'meta[name="csrf-token"]'
                                    )?.getAttribute('content') || ''
                            },

                            body: JSON.stringify(formData)
                        }
                    );

                    const data = await response.json();

                    if (!response.ok || !data.success) {

                        const validation =
                            data.errors
                                ? Object.values(data.errors).flat()[0]
                                : null;

                        throw new Error(
                            validation ||
                            data.message ||
                            'Booking failed.'
                        );

                    }

                    this.showSuccess = true;

                } catch (error) {

                    this.errorMessage =
                        error.message ||
                        'Something went wrong while creating the booking.';

                } finally {

                    this.submitting = false;

                }
            }

        };
    }
</script>

@endsection