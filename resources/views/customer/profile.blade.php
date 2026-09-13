
@extends('layouts.customer')

@section('title', 'My Profile')

@section('content')

@php
    $nickname = $customer->nickname ?? '';
    $phone = $customer->phone_number ?? '';
    $medicalNotes = $customer->medical_notes ?? '';
@endphp

<div
    x-data="customerProfilePage()"
    x-init="init()"
    class="mx-auto w-full max-w-4xl px-4 py-6 sm:px-6 lg:px-8"
>

    {{-- ============================================================
         PERSONAL INFORMATION
    ============================================================ --}}

    <section
        id="personal-information"
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >

        {{-- Header --}}
        <div class="border-b border-gray-200 px-5 py-5 sm:px-7 dark:border-gray-700">
            <div class="flex items-start gap-3">

                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400"
                >
                    <svg
                        class="h-5 w-5"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0"
                        />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        Personal Information
                    </h2>

                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        Manage your nickname and contact information.
                    </p>
                </div>

                {{-- Unsaved indicator --}}
                <div
                    x-show="profileDirty"
                    x-cloak
                    class="shrink-0"
                >
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/20 dark:text-amber-400"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Unsaved
                    </span>
                </div>

            </div>
        </div>

        <form
            id="profile-form"
            method="POST"
            action="{{ route('customer.profile.update') }}"
            @submit.prevent="confirmProfileUpdate"
        >
            @csrf
            @method('PUT')

            <div class="space-y-6 p-5 sm:p-7">

                {{-- Nickname --}}
                <div>

                    <label
                        for="nickname"
                        class="mb-2 block text-sm font-medium text-gray-800 dark:text-gray-200"
                    >
                        Nickname / Alias
                    </label>

                    <input
                        id="nickname"
                        name="nickname"
                        type="text"
                        value="{{ old('nickname', $nickname) }}"
                        maxlength="100"
                        required
                        autocomplete="nickname"
                        placeholder="Enter your preferred name"
                        class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-teal-500"
                        x-model="nickname"
                        @input="profileSaved = false"
                    >

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Use the name or alias you prefer for your customer profile.
                    </p>

                    {{-- Preferred name preview --}}
                    <div
                        class="mt-3 rounded-xl border border-teal-100 bg-teal-50/70 p-3.5 dark:border-teal-900/40 dark:bg-teal-900/10"
                    >
                        <div class="flex items-start gap-3">

                            <div
                                class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-teal-600 shadow-sm dark:bg-gray-800 dark:text-teal-400"
                            >
                                <svg
                                    class="h-4 w-4"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1-7.5 0 7.5 7.5 0 0 1 15 0Z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4.5 20.25a7.5 7.5 0 0 1 15 0"
                                    />
                                </svg>
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-medium uppercase tracking-wide text-teal-700 dark:text-teal-400">
                                    Preferred booking name
                                </p>

                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                    Spa staff will see you as
                                    <span
                                        class="font-semibold text-teal-700 dark:text-teal-400"
                                        x-text="nickname.trim() || 'your preferred name'"
                                    ></span>.
                                </p>
                            </div>

                        </div>
                    </div>

                </div>


                {{-- Phone Number --}}
                <div>

                    <label
                        for="phone_number"
                        class="mb-2 block text-sm font-medium text-gray-800 dark:text-gray-200"
                    >
                        Phone Number
                    </label>

                    <div class="relative">

                        <input
                            id="phone_number"
                            name="phone_number"
                            type="tel"
                            inputmode="numeric"
                            maxlength="11"
                            value="{{ old('phone_number', $phone) }}"
                            required
                            autocomplete="tel"
                            placeholder="09XXXXXXXXX"
                            class="block w-full rounded-xl border bg-white px-4 py-3 pr-11 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:ring-2 dark:bg-gray-700 dark:text-gray-200 dark:placeholder:text-gray-500"
                            :class="phoneStateClass"
                            x-model="phone"
                            @input="formatPhone"
                        >

                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4"
                        >

                            {{-- Valid --}}
                            <svg
                                x-show="phone.length > 0 && phoneValid"
                                x-cloak
                                class="h-5 w-5 text-emerald-500"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m4.5 12.75 4.5 4.5 10.5-10.5"
                                />
                            </svg>

                            {{-- Invalid --}}
                            <svg
                                x-show="phone.length > 0 && !phoneValid"
                                x-cloak
                                class="h-5 w-5 text-amber-500"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM10.29 3.86l-8.1 14A2 2 0 0 0 3.92 21h16.16a2 2 0 0 0 1.73-3.14l-8.1-14a2 2 0 0 0-3.42 0Z"
                                />
                            </svg>

                        </div>

                    </div>

                    <div class="mt-2 min-h-[18px]">

                        <p
                            x-show="phone.length > 0 && phoneValid"
                            x-cloak
                            class="flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m4.5 12.75 4.5 4.5 10.5-10.5"
                                />
                            </svg>

                            Valid Philippine mobile number.
                        </p>

                        <p
                            x-show="phone.length > 0 && !phoneValid"
                            x-cloak
                            class="flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM10.29 3.86l-8.1 14A2 2 0 0 0 3.92 21h16.16a2 2 0 0 0 1.73-3.14l-8.1-14a2 2 0 0 0-3.42 0Z"
                                />
                            </svg>

                            Enter 11 digits starting with 09.
                        </p>

                    </div>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Example: 09171234567
                    </p>

                </div>


                {{-- Registered Name --}}
                <div
                    class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40"
                >
                    <div class="flex items-start gap-3">

                        <div
                            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm dark:bg-gray-800 dark:text-gray-400"
                        >
                            <svg
                                class="h-4 w-4"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1-7.5 0 7.5 7.5 0 0 1 15 0Z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4.5 20.25a7.5 7.5 0 0 1 15 0"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">

                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                Personal name
                            </p>

                            <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                                Your registered personal name is managed separately and cannot be changed from this form.
                            </p>

                        </div>

                    </div>
                </div>

            </div>


            {{-- Personal Footer --}}
            <div
                class="flex flex-col gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4 dark:border-gray-700 dark:bg-gray-900/40 sm:flex-row sm:items-center sm:justify-between sm:px-7"
            >

                <div class="min-h-[20px]">

                    <p
                        x-show="profileDirty"
                        x-cloak
                        class="text-xs text-amber-600 dark:text-amber-400"
                    >
                        You have unsaved changes.
                    </p>

                    <p
                        x-show="!profileDirty && profileSaved"
                        x-cloak
                        class="flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m4.5 12.75 4.5 4.5 10.5-10.5"
                            />
                        </svg>

                        Changes saved.
                    </p>

                </div>

                <button
                    type="submit"
                    :disabled="submitting"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-60 dark:focus:ring-offset-gray-800"
                >

                    <svg
                        x-show="!submitting"
                        class="h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 4.5h12A1.5 1.5 0 0 1 19.5 6v12A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18V6A1.5 1.5 0 0 1 6 4.5Z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8 4.5v4h8v-4M8 19.5v-6h8v6"
                        />
                    </svg>

                    <svg
                        x-show="submitting"
                        x-cloak
                        class="h-4 w-4 animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>

                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"
                        ></path>
                    </svg>

                    <span x-text="submitting ? 'Saving...' : 'Save Changes'"></span>

                </button>

            </div>

        </form>
    </section>


    {{-- ============================================================
         MEDICAL / TREATMENT NOTES
    ============================================================ --}}

    <section
        id="medical-notes"
        class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >

        {{-- Header --}}
        <div class="border-b border-gray-200 px-5 py-5 sm:px-7 dark:border-gray-700">
            <div class="flex items-start gap-3">

                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400"
                >
                    <svg
                        class="h-5 w-5"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 3v2.25M15 3v2.25M4.5 9h15M6 6h12a1.5 1.5 0 0 1 1.5 1.5v10A3.5 3.5 0 0 1 16 21H8a3.5 3.5 0 0 1-3.5-3.5v-10A1.5 1.5 0 0 1 6 6Z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 13h6M12 10v6"
                        />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">

                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        Medical / Treatment Notes
                    </h2>

                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        Add information that may be relevant to your treatments.
                    </p>

                </div>

                <div
                    x-show="medicalDirty"
                    x-cloak
                    class="shrink-0"
                >
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/20 dark:text-amber-400"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Unsaved
                    </span>
                </div>

            </div>
        </div>


        <form
            id="medical-notes-form"
            method="POST"
            action="{{ route('customer.medical-notes.update') }}"
            @submit.prevent="confirmMedicalNotesUpdate"
        >
            @csrf
            @method('PUT')

            <div class="p-5 sm:p-7">

                <textarea
                    id="medical_notes"
                    name="medical_notes"
                    rows="6"
                    maxlength="2000"
                    placeholder="Enter any information you would like the spa to know before your treatment..."
                    class="block w-full resize-y rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm leading-6 text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder:text-gray-500"
                    x-model="medicalNotes"
                    @input="updateMedicalState"
                >{{ old('medical_notes', $medicalNotes) }}</textarea>

                <div class="mt-2 flex items-center justify-between gap-4">

                    <p
                        class="text-xs"
                        :class="medicalCharacterClass"
                        x-text="medicalCharacterMessage"
                    ></p>

                    <span
                        class="shrink-0 text-xs"
                        :class="medicalCharacterClass"
                    >
                        <span x-text="medicalNotes.length"></span>/2000
                    </span>

                </div>

            </div>


            {{-- Medical Footer --}}
            <div
                class="flex flex-col gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4 dark:border-gray-700 dark:bg-gray-900/40 sm:flex-row sm:items-center sm:justify-between sm:px-7"
            >

                <div class="min-h-[20px]">

                    <p
                        x-show="medicalDirty"
                        x-cloak
                        class="text-xs text-amber-600 dark:text-amber-400"
                    >
                        You have unsaved changes.
                    </p>

                    <p
                        x-show="!medicalDirty && medicalSaved"
                        x-cloak
                        class="flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m4.5 12.75 4.5 4.5 10.5-10.5"
                            />
                        </svg>

                        Notes saved.
                    </p>

                </div>


                <div class="flex flex-col-reverse gap-2 sm:flex-row">

                    {{-- Clear --}}
                    <button
                        type="button"
                        x-show="medicalNotes.length > 0"
                        x-cloak
                        @click="clearMedicalNotes"
                        :disabled="medicalSubmitting"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-offset-gray-800"
                    >

                        <svg
                            class="h-4 w-4"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 3.75h6M4.5 6.75h15M10 10.5v6M14 10.5v6M6.75 6.75l.75 12a1.5 1.5 0 0 0 1.5 1.5h6a1.5 1.5 0 0 0 1.5-1.5l.75-12"
                            />
                        </svg>

                        Clear

                    </button>


                    {{-- Save --}}
                    <button
                        type="submit"
                        :disabled="medicalSubmitting"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-60 dark:focus:ring-offset-gray-800"
                    >

                        <svg
                            x-show="!medicalSubmitting"
                            class="h-4 w-4"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 4.5h12A1.5 1.5 0 0 1 19.5 6v12A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18V6A1.5 1.5 0 0 1 6 4.5Z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8 4.5v4h8v-4M8 19.5v-6h8v6"
                            />
                        </svg>

                        <svg
                            x-show="medicalSubmitting"
                            x-cloak
                            class="h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"
                            ></path>
                        </svg>

                        <span
                            x-text="medicalSubmitting ? 'Saving...' : 'Save Notes'"
                        ></span>

                    </button>

                </div>

            </div>

        </form>

    </section>

</div>


<script>
function customerProfilePage() {

    return {

        /* ============================================================
           DATA
        ============================================================ */

        nickname: @js(old('nickname', $nickname)),
        phone: @js(old('phone_number', $phone)),
        medicalNotes: @js(old('medical_notes', $medicalNotes)),

        originalNickname: @js(old('nickname', $nickname)),
        originalPhone: @js(old('phone_number', $phone)),
        originalMedicalNotes: @js(old('medical_notes', $medicalNotes)),

        submitting: false,
        medicalSubmitting: false,

        profileSaved: false,
        medicalSaved: false,

        /*
         * IMPORTANT:
         *
         * The layout uses:
         *
         * <main id="mainContent" class="... overflow-y-auto">
         *
         * Therefore this is the actual scrolling container.
         */
        scrollStorageKey: 'customer-profile-scroll',

        /* ============================================================
           INIT
        ============================================================ */

        init() {

            this.normalizeValues();

            this.restoreMainScroll();

        },

        normalizeValues() {

            this.nickname = String(
                this.nickname ?? ''
            );

            this.phone = String(
                this.phone ?? ''
            )
                .replace(/\D/g, '')
                .slice(0, 11);

            this.medicalNotes = String(
                this.medicalNotes ?? ''
            );

            this.originalNickname = String(
                this.originalNickname ?? ''
            );

            this.originalPhone = String(
                this.originalPhone ?? ''
            )
                .replace(/\D/g, '')
                .slice(0, 11);

            this.originalMedicalNotes = String(
                this.originalMedicalNotes ?? ''
            );

        },

        /* ============================================================
           PHONE
        ============================================================ */

        formatPhone() {

            this.phone = String(
                this.phone ?? ''
            )
                .replace(/\D/g, '')
                .slice(0, 11);

            this.profileSaved = false;

        },

        get phoneValid() {

            return /^09\d{9}$/.test(
                this.phone
            );

        },

        get phoneStateClass() {

            if (!this.phone) {

                return `
                    border-gray-300
                    focus:border-teal-500
                    focus:ring-2
                    focus:ring-teal-500/20
                    dark:border-gray-600
                    dark:focus:border-teal-500
                `;

            }

            if (this.phoneValid) {

                return `
                    border-emerald-400
                    focus:border-emerald-500
                    focus:ring-2
                    focus:ring-emerald-500/20
                    dark:border-emerald-600
                    dark:focus:border-emerald-500
                `;

            }

            return `
                border-amber-400
                focus:border-amber-500
                focus:ring-2
                focus:ring-amber-500/20
                dark:border-amber-600
                dark:focus:border-amber-500
            `;

        },

        /* ============================================================
           DIRTY STATE
        ============================================================ */

        get profileDirty() {

            return (
                this.nickname.trim() !==
                    this.originalNickname.trim()
                ||
                this.phone !==
                    this.originalPhone
            );

        },

        get medicalDirty() {

            return (
                this.medicalNotes !==
                this.originalMedicalNotes
            );

        },

        /* ============================================================
           MEDICAL NOTES
        ============================================================ */

        updateMedicalState() {

            this.medicalSaved = false;

        },

        get medicalCharacterClass() {

            const length =
                this.medicalNotes.length;

            if (length >= 1900) {

                return 'text-amber-600 dark:text-amber-400';

            }

            return 'text-gray-500 dark:text-gray-400';

        },

        get medicalCharacterMessage() {

            const length =
                this.medicalNotes.length;

            if (length >= 2000) {

                return 'Character limit reached.';

            }

            if (length >= 1900) {

                return 'Almost at the character limit.';

            }

            return 'Maximum 2,000 characters.';

        },

        /* ============================================================
           CLEAR NOTES
        ============================================================ */

        async clearMedicalNotes() {

            if (!this.medicalNotes.length) {
                return;
            }

            const result = await Swal.fire({

                icon: 'warning',

                title: 'Clear notes?',

                text: 'This will remove the notes currently entered in the form.',

                showCancelButton: true,

                confirmButtonText: 'Clear',

                cancelButtonText: 'Cancel',

                confirmButtonColor: '#dc2626',

                cancelButtonColor: '#6b7280',

                reverseButtons: true,

                focusCancel: true,

                /*
                 * Same size as confirmation dialogs.
                 */
                width: '360px',

                padding: '1.25rem',

                customClass: {

                    popup: 'rounded-2xl',

                    title: 'text-lg',

                    htmlContainer: 'text-sm',

                    confirmButton: 'rounded-lg px-4 py-2 text-sm',

                    cancelButton: 'rounded-lg px-4 py-2 text-sm'

                }

            });

            if (!result.isConfirmed) {
                return;
            }

            this.medicalNotes = '';

            this.medicalSaved = false;

            this.focusMedicalNotes();

        },

        focusMedicalNotes() {

            requestAnimationFrame(() => {

                const textarea =
                    document.getElementById(
                        'medical_notes'
                    );

                if (textarea) {
                    textarea.focus();
                }

            });

        },

        /* ============================================================
           ACTUAL SCROLL CONTAINER
        ============================================================ */

        getMainContent() {

            return document.getElementById(
                'mainContent'
            );

        },

        getMainScrollPosition() {

            const main =
                this.getMainContent();

            if (!main) {

                return {
                    top: 0,
                    left: 0
                };

            }

            return {

                top: main.scrollTop,

                left: main.scrollLeft

            };

        },

        /* ============================================================
           SAVE SCROLL POSITION
        ============================================================ */

        saveMainScroll(target = null) {

            const position =
                this.getMainScrollPosition();

            const state = {

                scrollTop: position.top,

                scrollLeft: position.left,

                target: target,

                timestamp: Date.now()

            };

            try {

                sessionStorage.setItem(
                    this.scrollStorageKey,
                    JSON.stringify(state)
                );

            } catch (error) {

                console.warn(
                    'Unable to save profile scroll position.',
                    error
                );

            }

        },

        /* ============================================================
           GET SAVED SCROLL
        ============================================================ */

        getSavedMainScroll() {

            try {

                const raw =
                    sessionStorage.getItem(
                        this.scrollStorageKey
                    );

                if (!raw) {
                    return null;
                }

                const state =
                    JSON.parse(raw);

                if (
                    !state ||
                    typeof state !== 'object'
                ) {
                    return null;
                }

                /*
                 * Do not restore an old scroll state.
                 */
                if (
                    state.timestamp &&
                    Date.now() - state.timestamp > 30000
                ) {

                    sessionStorage.removeItem(
                        this.scrollStorageKey
                    );

                    return null;

                }

                return state;

            } catch (error) {

                return null;

            }

        },

        /* ============================================================
           RESTORE ACTUAL MAIN SCROLL
        ============================================================ */

        restoreMainScroll() {

            const state =
                this.getSavedMainScroll();

            if (!state) {
                return;
            }

            const restore = () => {

                const main =
                    this.getMainContent();

                if (!main) {
                    return;
                }

                /*
                 * If the user saved Medical Notes,
                 * restore the exact main-container
                 * position that existed before submit.
                 */
                if (
                    state.target ===
                    'medical-notes'
                ) {

                    /*
                     * Use the stored scrollTop first.
                     *
                     * This is important because the user
                     * may have been somewhere in the middle
                     * of the Medical Notes section.
                     */
                    main.scrollTo({

                        top:
                            Number(
                                state.scrollTop
                            ) || 0,

                        left:
                            Number(
                                state.scrollLeft
                            ) || 0,

                        behavior: 'auto'

                    });

                    return;

                }

                main.scrollTo({

                    top:
                        Number(
                            state.scrollTop
                        ) || 0,

                    left:
                        Number(
                            state.scrollLeft
                        ) || 0,

                    behavior: 'auto'

                });

            };

            /*
             * Wait until the browser has completed
             * layout and Alpine has initialized.
             */
            requestAnimationFrame(() => {

                requestAnimationFrame(() => {

                    restore();

                });

            });

            /*
             * Restore again shortly afterward in case
             * the page height changed after rendering.
             */
            setTimeout(() => {

                restore();

            }, 100);

            /*
             * One final restoration after everything has
             * settled.
             */
            setTimeout(() => {

                restore();

                this.clearSavedMainScroll();

            }, 300);

        },

        clearSavedMainScroll() {

            try {

                sessionStorage.removeItem(
                    this.scrollStorageKey
                );

            } catch (error) {

                // Ignore storage errors.

            }

        },

        /* ============================================================
           SUBMIT FORM
        ============================================================ */

        submitForm(formId) {

            const form =
                document.getElementById(
                    formId
                );

            if (!form) {
                return;
            }

            /*
             * Bypass Alpine's submit handler.
             * Otherwise the SweetAlert confirmation
             * would execute again.
             */
            HTMLFormElement.prototype.submit.call(
                form
            );

        },

        /* ============================================================
           PROFILE UPDATE
        ============================================================ */

        async confirmProfileUpdate() {

            this.formatPhone();

            if (!this.nickname.trim()) {

                await Swal.fire({

                    icon: 'warning',

                    title: 'Nickname required',

                    text: 'Please enter a nickname or alias.',

                    confirmButtonText: 'Okay',

                    confirmButtonColor: '#0d9488',

                    width: '360px',

                    padding: '1.25rem',

                    customClass: {

                        popup: 'rounded-2xl',

                        title: 'text-lg',

                        htmlContainer: 'text-sm',

                        confirmButton: 'rounded-lg px-4 py-2 text-sm'

                    }

                });

                return;

            }

            if (!this.phoneValid) {

                await Swal.fire({

                    icon: 'warning',

                    title: 'Invalid phone number',

                    text: 'Please enter a valid Philippine mobile number starting with 09.',

                    confirmButtonText: 'Okay',

                    confirmButtonColor: '#0d9488',

                    width: '360px',

                    padding: '1.25rem',

                    customClass: {

                        popup: 'rounded-2xl',

                        title: 'text-lg',

                        htmlContainer: 'text-sm',

                        confirmButton: 'rounded-lg px-4 py-2 text-sm'

                    }

                });

                return;

            }

            if (!this.profileDirty) {

                await Swal.fire({

                    icon: 'info',

                    title: 'No changes to save',

                    text: 'Your personal information is already up to date.',

                    confirmButtonText: 'Okay',

                    confirmButtonColor: '#0d9488',

                    /*
                     * SAME SIZE AS CONFIRMATION.
                     */
                    width: '360px',

                    padding: '1.25rem',

                    customClass: {

                        popup: 'rounded-2xl',

                        title: 'text-lg',

                        htmlContainer: 'text-sm',

                        confirmButton: 'rounded-lg px-4 py-2 text-sm'

                    }

                });

                return;

            }

            const result = await Swal.fire({

                icon: 'question',

                title: 'Save changes?',

                text: 'Update your nickname and phone number?',

                showCancelButton: true,

                confirmButtonText: 'Save',

                cancelButtonText: 'Cancel',

                confirmButtonColor: '#0d9488',

                cancelButtonColor: '#6b7280',

                reverseButtons: true,

                focusCancel: true,

                width: '360px',

                padding: '1.25rem',

                customClass: {

                    popup: 'rounded-2xl',

                    title: 'text-lg',

                    htmlContainer: 'text-sm',

                    confirmButton: 'rounded-lg px-4 py-2 text-sm',

                    cancelButton: 'rounded-lg px-4 py-2 text-sm'

                }

            });

            if (!result.isConfirmed) {
                return;
            }

            this.submitting = true;

            /*
             * Save the ACTUAL #mainContent scroll position.
             */
            this.saveMainScroll(
                'personal-information'
            );

            this.submitForm(
                'profile-form'
            );

        },

        /* ============================================================
           MEDICAL NOTES UPDATE
        ============================================================ */

        async confirmMedicalNotesUpdate() {

            if (!this.medicalDirty) {

                await Swal.fire({

                    icon: 'info',

                    title: 'No changes to save',

                    text: 'Your medical or treatment notes are already up to date.',

                    confirmButtonText: 'Okay',

                    confirmButtonColor: '#0d9488',

                    /*
                     * SAME SIZE AS CONFIRMATION.
                     */
                    width: '360px',

                    padding: '1.25rem',

                    customClass: {

                        popup: 'rounded-2xl',

                        title: 'text-lg',

                        htmlContainer: 'text-sm',

                        confirmButton: 'rounded-lg px-4 py-2 text-sm'

                    }

                });

                return;

            }

            const result = await Swal.fire({

                icon: 'question',

                title: 'Save notes?',

                text: 'Save these medical or treatment notes?',

                showCancelButton: true,

                confirmButtonText: 'Save',

                cancelButtonText: 'Cancel',

                confirmButtonColor: '#0d9488',

                cancelButtonColor: '#6b7280',

                reverseButtons: true,

                focusCancel: true,

                width: '360px',

                padding: '1.25rem',

                customClass: {

                    popup: 'rounded-2xl',

                    title: 'text-lg',

                    htmlContainer: 'text-sm',

                    confirmButton: 'rounded-lg px-4 py-2 text-sm',

                    cancelButton: 'rounded-lg px-4 py-2 text-sm'

                }

            });

            if (!result.isConfirmed) {
                return;
            }

            this.medicalSubmitting = true;

            /*
             * THIS IS THE IMPORTANT FIX.
             *
             * The actual page scroll is:
             *
             * #mainContent.scrollTop
             *
             * NOT:
             *
             * window.scrollY
             */
            this.saveMainScroll(
                'medical-notes'
            );

            this.submitForm(
                'medical-notes-form'
            );

        }

    };

}
</script>

@endsection