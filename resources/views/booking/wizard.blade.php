<!DOCTYPE html>
<html lang="en" x-data="spaBookingWizard()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Book an Appointment | Spa Alexandria</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Preline UI -->
    <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- FullCalendar -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css"
    >
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <!-- Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        zen: {
                            50: '#effcf9',
                            100: '#d7f8f1',
                            200: '#b0f0e3',
                            300: '#7fe4d3',
                            400: '#48d1bd',
                            500: '#20b8a3',
                            600: '#159582',
                            700: '#14776a',
                            800: '#155f57',
                            900: '#154f49',
                        }
                    },
                    boxShadow: {
                        soft: '0 10px 40px rgba(15, 118, 110, 0.08)',
                        card: '0 4px 24px rgba(15, 23, 42, 0.06)',
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        /* ---------------------------------------------------------
           FullCalendar
        --------------------------------------------------------- */

        .fc {
            --fc-border-color: #e5e7eb;
            --fc-button-bg-color: #0f766e;
            --fc-button-border-color: #0f766e;
            --fc-button-hover-bg-color: #115e59;
            --fc-button-hover-border-color: #115e59;
            --fc-button-active-bg-color: #115e59;
            --fc-button-active-border-color: #115e59;
            --fc-today-bg-color: #f0fdfa;
            font-family: 'Inter', sans-serif;
        }

        .fc .fc-toolbar-title {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .fc .fc-button {
            border-radius: 0.75rem;
            box-shadow: none;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 0.55rem 0.8rem;
        }

        .fc .fc-button:focus {
            box-shadow: none !important;
        }

        .fc .fc-daygrid-day {
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .fc .fc-daygrid-day:hover {
            background: #f0fdfa;
        }

        .fc .fc-daygrid-day-number {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.55rem;
            color: #334155;
        }

        .fc .fc-col-header-cell-cushion {
            font-size: 0.7rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .fc .fc-day-today .fc-daygrid-day-number {
            color: #0f766e;
        }

        .fc .fc-day-disabled {
            background: #f8fafc;
            cursor: not-allowed;
        }

        .fc .fc-day-disabled .fc-daygrid-day-number {
            color: #cbd5e1;
        }

        .fc .fc-day-sun {
            background: #fafafa;
        }

        @media (max-width: 640px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 0.75rem;
                align-items: stretch;
            }

            .fc .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
            }

            .fc .fc-toolbar-title {
                font-size: 0.95rem;
            }

            .fc .fc-daygrid-day-number {
                padding: 0.4rem;
            }
        }

        /* ---------------------------------------------------------
           Scrollbars
        --------------------------------------------------------- */

        .nice-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        .nice-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .nice-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        /* ---------------------------------------------------------
           Animations
        --------------------------------------------------------- */

        .fade-up {
            animation: fadeUp 0.35s ease-out;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .service-card {
            transition:
                transform 0.2s ease,
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background-color 0.2s ease;
        }

        .service-card:hover {
            transform: translateY(-2px);
        }

        .slot-button {
            transition:
                transform 0.15s ease,
                background-color 0.15s ease,
                border-color 0.15s ease,
                box-shadow 0.15s ease;
        }

        .slot-button:hover:not(:disabled) {
            transform: translateY(-1px);
        }

        /* ---------------------------------------------------------
           Mobile bottom summary
        --------------------------------------------------------- */

        .mobile-safe-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">

    <!-- ============================================================
         NAVIGATION
    ============================================================= -->

    <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">

            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-zen-600 text-white shadow-sm">
                    <i data-lucide="sparkles" class="h-5 w-5"></i>
                </div>

                <div class="hidden sm:block">
                    <div class="text-sm font800 font-bold tracking-tight text-slate-900">
                        Spa Alexandria
                    </div>

                    <div class="text-[11px] font-medium text-slate-500">
                        Wellness & Relaxation
                    </div>
                </div>
            </a>

            <div class="flex items-center gap-2">
                <a
                    href="{{ url('/') }}"
                    class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                >
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    <span class="hidden sm:inline">Back to Home</span>
                    <span class="sm:hidden">Home</span>
                </a>
            </div>
        </div>
    </header>


    <!-- ============================================================
         PAGE
    ============================================================= -->

    <main class="mx-auto max-w-7xl px-4 pb-36 pt-6 sm:px-6 sm:pb-20 sm:pt-10 lg:px-8">

        <!-- Header -->

        <section class="mb-8">
            <div class="max-w-3xl">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-zen-50 px-3 py-1.5 text-xs font-bold text-zen-700 ring-1 ring-zen-100">
                    <span class="h-1.5 w-1.5 rounded-full bg-zen-500"></span>
                    ONLINE APPOINTMENT
                </div>

                <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">
                    Book your time to relax.
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500 sm:text-base">
                    Choose your treatments, select a convenient schedule, and send your
                    appointment request. Our receptionist will confirm your booking.
                </p>
            </div>
        </section>


        <!-- ========================================================
             STEP INDICATOR
        ========================================================= -->

        <section class="mb-8">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-card sm:p-4">

                <div class="grid grid-cols-4 gap-1 sm:gap-2">

                    <!-- Step 1 -->

                    <button
                        type="button"
                        @click="goToStep(1)"
                        class="group rounded-xl px-2 py-2.5 text-left transition sm:px-3"
                        :class="step >= 1
                            ? 'bg-zen-50'
                            : 'bg-transparent hover:bg-slate-50'"
                    >
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold transition sm:h-9 sm:w-9"
                                :class="step >= 1
                                    ? 'bg-zen-600 text-white'
                                    : 'bg-slate-100 text-slate-400'"
                            >
                                <template x-if="step > 1">
                                    <i data-lucide="check" class="h-4 w-4"></i>
                                </template>

                                <template x-if="step <= 1">
                                    <span>1</span>
                                </template>
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="truncate text-xs font-bold sm:text-sm"
                                    :class="step >= 1 ? 'text-slate-900' : 'text-slate-400'"
                                >
                                    Services
                                </p>

                                <p class="hidden text-[11px] text-slate-400 sm:block">
                                    Choose treatments
                                </p>
                            </div>
                        </div>
                    </button>


                    <!-- Connector -->

                    <div class="hidden items-center sm:flex">
                        <div
                            class="h-px w-full"
                            :class="step >= 2 ? 'bg-zen-300' : 'bg-slate-200'"
                        ></div>
                    </div>


                    <!-- Step 2 -->

                    <button
                        type="button"
                        @click="goToStep(2)"
                        :disabled="!canEnterStep(2)"
                        class="group rounded-xl px-2 py-2.5 text-left transition sm:px-3"
                        :class="step >= 2
                            ? 'bg-zen-50'
                            : 'bg-transparent hover:bg-slate-50 disabled:hover:bg-transparent'"
                    >
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold transition sm:h-9 sm:w-9"
                                :class="step >= 2
                                    ? 'bg-zen-600 text-white'
                                    : 'bg-slate-100 text-slate-400'"
                            >
                                <template x-if="step > 2">
                                    <i data-lucide="check" class="h-4 w-4"></i>
                                </template>

                                <template x-if="step <= 2">
                                    <span>2</span>
                                </template>
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="truncate text-xs font-bold sm:text-sm"
                                    :class="step >= 2 ? 'text-slate-900' : 'text-slate-400'"
                                >
                                    Date & Time
                                </p>

                                <p class="hidden text-[11px] text-slate-400 sm:block">
                                    Find a schedule
                                </p>
                            </div>
                        </div>
                    </button>


                    <!-- Connector -->

                    <div class="hidden items-center sm:flex">
                        <div
                            class="h-px w-full"
                            :class="step >= 3 ? 'bg-zen-300' : 'bg-slate-200'"
                        ></div>
                    </div>


                    <!-- Step 3 -->

                    <button
                        type="button"
                        @click="goToStep(3)"
                        :disabled="!canEnterStep(3)"
                        class="group rounded-xl px-2 py-2.5 text-left transition sm:px-3"
                        :class="step >= 3
                            ? 'bg-zen-50'
                            : 'bg-transparent hover:bg-slate-50 disabled:hover:bg-transparent'"
                    >
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold transition sm:h-9 sm:w-9"
                                :class="step >= 3
                                    ? 'bg-zen-600 text-white'
                                    : 'bg-slate-100 text-slate-400'"
                            >
                                3
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="truncate text-xs font-bold sm:text-sm"
                                    :class="step >= 3 ? 'text-slate-900' : 'text-slate-400'"
                                >
                                    Your Details
                                </p>

                                <p class="hidden text-[11px] text-slate-400 sm:block">
                                    Tell us about you
                                </p>
                            </div>
                        </div>
                    </button>

                </div>
            </div>
        </section>


        <!-- ========================================================
             MAIN GRID
        ========================================================= -->

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">

            <!-- ====================================================
                 LEFT / MAIN CONTENT
            ===================================================== -->

            <div>

                <!-- =================================================
                     STEP 1 — SERVICES
                ================================================== -->

                <section
                    x-show="step === 1"
                    x-cloak
                    class="fade-up"
                >

                    <div class="rounded-3xl border border-slate-200 bg-white shadow-card">

                        <div class="border-b border-slate-100 p-5 sm:p-7">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wider text-zen-600">
                                        Step 1
                                    </p>

                                    <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950 sm:text-2xl">
                                        What would you like today?
                                    </h2>

                                    <p class="mt-2 text-sm text-slate-500">
                                        Select one or more treatments for your appointment.
                                    </p>
                                </div>

                                <div class="hidden rounded-xl bg-slate-50 px-3 py-2 text-right sm:block">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                        Selected
                                    </p>

                                    <p class="text-sm font-bold text-slate-900">
                                        <span x-text="selectedServices.length"></span>
                                        <span x-text="selectedServices.length === 1 ? 'service' : 'services'"></span>
                                    </p>
                                </div>
                            </div>
                        </div>


                        <!-- Category navigation -->

                        <div class="border-b border-slate-100 px-5 pt-4 sm:px-7">
                            <div class="nice-scrollbar flex gap-2 overflow-x-auto pb-4">

                                <template x-for="category in categories" :key="category.id">
                                    <button
                                        type="button"
                                        @click="activeCategory = category.id"
                                        class="whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-semibold transition"
                                        :class="activeCategory === category.id
                                            ? 'bg-slate-900 text-white shadow-sm'
                                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    >
                                        <span x-text="category.name"></span>
                                    </button>
                                </template>

                            </div>
                        </div>


                        <!-- Services -->

                        <div class="p-5 sm:p-7">

                            <template x-if="activeCategoryObject">
                                <div>

                                    <div class="mb-4 flex items-center justify-between">
                                        <div>
                                            <h3
                                                class="font-bold text-slate-900"
                                                x-text="activeCategoryObject.name"
                                            ></h3>

                                            <p class="mt-1 text-xs text-slate-500">
                                                Select the treatments you want to include.
                                            </p>
                                        </div>

                                        <span
                                            class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-500"
                                            x-text="activeCategoryServices.length + ' services'"
                                        ></span>
                                    </div>


                                    <div class="grid gap-3 sm:grid-cols-2">

                                        <template
                                            x-for="service in activeCategoryServices"
                                            :key="service.id"
                                        >

                                            <button
                                                type="button"
                                                @click="toggleService(service)"
                                                class="service-card group relative overflow-hidden rounded-2xl border p-4 text-left"
                                                :class="isSelected(service.id)
                                                    ? 'border-zen-500 bg-zen-50/70 shadow-md shadow-zen-100'
                                                    : 'border-slate-200 bg-white hover:border-zen-200 hover:bg-slate-50'"
                                            >

                                                <!-- Selected indicator -->

                                                <div
                                                    class="absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-full transition"
                                                    :class="isSelected(service.id)
                                                        ? 'bg-zen-600 text-white'
                                                        : 'bg-slate-100 text-transparent group-hover:bg-slate-200'"
                                                >
                                                    <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                                </div>


                                                <div class="flex gap-4">

                                                    <!-- Image -->

                                                    <div
                                                        class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-slate-100"
                                                    >
                                                        <template x-if="service.image">
                                                            <img
                                                                :src="service.image"
                                                                :alt="service.name"
                                                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                                            >
                                                        </template>

                                                        <template x-if="!service.image">
                                                            <div class="flex h-full w-full items-center justify-center text-slate-300">
                                                                <i data-lucide="sparkles" class="h-7 w-7"></i>
                                                            </div>
                                                        </template>
                                                    </div>


                                                    <!-- Info -->

                                                    <div class="min-w-0 flex-1 pr-6">

                                                        <h4 class="line-clamp-2 text-sm font-bold leading-5 text-slate-900">
                                                            <span x-text="service.name"></span>
                                                        </h4>

                                                        <p
                                                            x-show="service.description"
                                                            class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500"
                                                            x-text="service.description"
                                                        ></p>

                                                        <div class="mt-3 flex flex-wrap items-center gap-2">

                                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500">
                                                                <i data-lucide="clock-3" class="h-3.5 w-3.5"></i>
                                                                <span x-text="service.duration_minutes"></span> min
                                                            </span>

                                                            <span class="h-1 w-1 rounded-full bg-slate-300"></span>

                                                            <span class="text-sm font-extrabold text-zen-700">
                                                                ₱<span x-text="formatMoney(service.discount_price || service.price)"></span>
                                                            </span>

                                                        </div>
                                                    </div>

                                                </div>

                                            </button>

                                        </template>

                                    </div>


                                    <!-- Empty category -->

                                    <template x-if="activeCategoryServices.length === 0">
                                        <div class="rounded-2xl border border-dashed border-slate-200 py-12 text-center">
                                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                                <i data-lucide="sparkles" class="h-6 w-6"></i>
                                            </div>

                                            <p class="mt-3 text-sm font-semibold text-slate-700">
                                                No services available
                                            </p>

                                            <p class="mt-1 text-xs text-slate-400">
                                                Please choose another category.
                                            </p>
                                        </div>
                                    </template>

                                </div>
                            </template>

                        </div>
                    </div>

                </section>


                <!-- =================================================
                     STEP 2 — DATE & TIME
                ================================================== -->

                <section
                    x-show="step === 2"
                    x-cloak
                    class="fade-up"
                >

                    <div class="rounded-3xl border border-slate-200 bg-white shadow-card">

                        <div class="border-b border-slate-100 p-5 sm:p-7">
                            <p class="text-xs font-bold uppercase tracking-wider text-zen-600">
                                Step 2
                            </p>

                            <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950 sm:text-2xl">
                                Find a time that works for you.
                            </h2>

                            <p class="mt-2 text-sm text-slate-500">
                                Select a date first, then choose from the available appointment times.
                            </p>
                        </div>


                        <div class="grid lg:grid-cols-[minmax(0,1fr)_300px]">

                            <!-- Calendar -->

                            <div class="border-b border-slate-100 p-5 sm:p-7 lg:border-b-0 lg:border-r">

                                <div class="mb-4 flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900">
                                            Choose a date
                                        </h3>

                                        <p class="mt-1 text-xs text-slate-500">
                                            Sundays are unavailable.
                                        </p>
                                    </div>

                                    <div class="hidden items-center gap-2 text-[11px] text-slate-500 sm:flex">
                                        <span class="h-2 w-2 rounded-full bg-zen-500"></span>
                                        Available
                                    </div>
                                </div>

                                <div
                                    id="booking-calendar"
                                    class="min-h-[330px]"
                                ></div>

                            </div>


                            <!-- Times -->

                            <div class="p-5 sm:p-7">

                                <div class="mb-5">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                        Available times
                                    </p>

                                    <h3 class="mt-1 text-base font-extrabold text-slate-900">
                                        <template x-if="appointmentDate">
                                            <span x-text="formatDateLong(appointmentDate)"></span>
                                        </template>

                                        <template x-if="!appointmentDate">
                                            <span>Select a date</span>
                                        </template>
                                    </h3>
                                </div>


                                <!-- Loading -->

                                <template x-if="loadingSlots">
                                    <div class="space-y-2">
                                        <template x-for="i in 6" :key="i">
                                            <div class="h-12 animate-pulse rounded-xl bg-slate-100"></div>
                                        </template>
                                    </div>
                                </template>


                                <!-- No date -->

                                <template x-if="!loadingSlots && !appointmentDate">
                                    <div class="rounded-2xl border border-dashed border-slate-200 p-6 text-center">
                                        <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                            <i data-lucide="calendar-days" class="h-5 w-5"></i>
                                        </div>

                                        <p class="mt-3 text-sm font-semibold text-slate-700">
                                            Choose a date
                                        </p>

                                        <p class="mt-1 text-xs leading-5 text-slate-400">
                                            Available times will appear here.
                                        </p>
                                    </div>
                                </template>


                                <!-- No slots -->

                                <template x-if="!loadingSlots && appointmentDate && availableSlots.length === 0">
                                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-center">
                                        <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                                            <i data-lucide="calendar-x-2" class="h-5 w-5"></i>
                                        </div>

                                        <p class="mt-3 text-sm font-bold text-amber-900">
                                            No available times
                                        </p>

                                        <p class="mt-1 text-xs leading-5 text-amber-700">
                                            Please choose another date.
                                        </p>
                                    </div>
                                </template>


                                <!-- Slots -->

                                <template x-if="!loadingSlots && availableSlots.length > 0">

                                    <div class="space-y-5">

                                        <!-- Morning -->

                                        <template x-if="morningSlots.length">
                                            <div>
                                                <div class="mb-2 flex items-center gap-2">
                                                    <i data-lucide="sunrise" class="h-4 w-4 text-amber-500"></i>

                                                    <span class="text-xs font-bold text-slate-600">
                                                        Morning
                                                    </span>
                                                </div>

                                                <div class="grid grid-cols-2 gap-2">
                                                    <template x-for="slot in morningSlots" :key="slot.time">
                                                        <button
                                                            type="button"
                                                            @click="selectSlot(slot)"
                                                            :disabled="slot.room_available === false"
                                                            class="slot-button min-h-12 rounded-xl border px-3 py-3 text-sm font-bold"
                                                            :class="selectedTime === slot.time
                                                                ? 'border-zen-600 bg-zen-600 text-white shadow-md shadow-zen-100'
                                                                : slot.room_available === false
                                                                    ? 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300'
                                                                    : 'border-slate-200 bg-white text-slate-700 hover:border-zen-300 hover:bg-zen-50 hover:text-zen-700'"
                                                        >
                                                            <span x-text="formatTime(slot.time)"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>


                                        <!-- Afternoon -->

                                        <template x-if="afternoonSlots.length">
                                            <div>
                                                <div class="mb-2 flex items-center gap-2">
                                                    <i data-lucide="sun" class="h-4 w-4 text-orange-500"></i>

                                                    <span class="text-xs font-bold text-slate-600">
                                                        Afternoon
                                                    </span>
                                                </div>

                                                <div class="grid grid-cols-2 gap-2">
                                                    <template x-for="slot in afternoonSlots" :key="slot.time">
                                                        <button
                                                            type="button"
                                                            @click="selectSlot(slot)"
                                                            :disabled="slot.room_available === false"
                                                            class="slot-button min-h-12 rounded-xl border px-3 py-3 text-sm font-bold"
                                                            :class="selectedTime === slot.time
                                                                ? 'border-zen-600 bg-zen-600 text-white shadow-md shadow-zen-100'
                                                                : slot.room_available === false
                                                                    ? 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300'
                                                                    : 'border-slate-200 bg-white text-slate-700 hover:border-zen-300 hover:bg-zen-50 hover:text-zen-700'"
                                                        >
                                                            <span x-text="formatTime(slot.time)"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>


                                        <!-- Evening -->

                                        <template x-if="eveningSlots.length">
                                            <div>
                                                <div class="mb-2 flex items-center gap-2">
                                                    <i data-lucide="sunset" class="h-4 w-4 text-indigo-500"></i>

                                                    <span class="text-xs font-bold text-slate-600">
                                                        Evening
                                                    </span>
                                                </div>

                                                <div class="grid grid-cols-2 gap-2">
                                                    <template x-for="slot in eveningSlots" :key="slot.time">
                                                        <button
                                                            type="button"
                                                            @click="selectSlot(slot)"
                                                            :disabled="slot.room_available === false"
                                                            class="slot-button min-h-12 rounded-xl border px-3 py-3 text-sm font-bold"
                                                            :class="selectedTime === slot.time
                                                                ? 'border-zen-600 bg-zen-600 text-white shadow-md shadow-zen-100'
                                                                : slot.room_available === false
                                                                    ? 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300'
                                                                    : 'border-slate-200 bg-white text-slate-700 hover:border-zen-300 hover:bg-zen-50 hover:text-zen-700'"
                                                        >
                                                            <span x-text="formatTime(slot.time)"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                    </div>

                                </template>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                     STEP 3 — CUSTOMER DETAILS
                ================================================== -->

                <section
                    x-show="step === 3"
                    x-cloak
                    class="fade-up"
                >

                    <div class="rounded-3xl border border-slate-200 bg-white shadow-card">

                        <div class="border-b border-slate-100 p-5 sm:p-7">
                            <p class="text-xs font-bold uppercase tracking-wider text-zen-600">
                                Step 3
                            </p>

                            <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950 sm:text-2xl">
                                Tell us about yourself.
                            </h2>

                            <p class="mt-2 text-sm text-slate-500">
                                We only need a few details so our receptionist can confirm your appointment.
                            </p>
                        </div>


                        <div class="p-5 sm:p-7">

                            <div class="space-y-6">

                                <!-- Name -->

                                <div>
                                    <label
                                        for="guest_first_name"
                                        class="mb-2 block text-sm font-bold text-slate-800"
                                    >
                                        Full name
                                    </label>

                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                            <i data-lucide="user" class="h-4 w-4"></i>
                                        </div>

                                        <input
                                            id="guest_first_name"
                                            name="guest_first_name"
                                            type="text"
                                            x-model="guestName"
                                            autocomplete="name"
                                            maxlength="100"
                                            placeholder="Enter your full name"
                                            class="block w-full rounded-xl border border-slate-200 bg-white py-3.5 pl-11 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-zen-500 focus:ring-4 focus:ring-zen-500/10"
                                        >
                                    </div>

                                    <p class="mt-1.5 text-xs text-slate-400">
                                        You may use your preferred name or alias.
                                    </p>
                                </div>


                                <!-- Phone -->

                                <div>
                                    <label
                                        for="phone"
                                        class="mb-2 block text-sm font-bold text-slate-800"
                                    >
                                        Mobile number
                                    </label>

                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                            <i data-lucide="phone" class="h-4 w-4"></i>
                                        </div>

                                        <input
                                            id="phone"
                                            name="phone"
                                            type="tel"
                                            x-model="phone"
                                            @input="formatPhone()"
                                            autocomplete="tel"
                                            maxlength="11"
                                            inputmode="numeric"
                                            placeholder="09XXXXXXXXX"
                                            class="block w-full rounded-xl border py-3.5 pl-11 pr-12 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:ring-4"
                                            :class="phone.length === 11 && phoneValid
                                                ? 'border-emerald-300 bg-emerald-50/30 focus:border-emerald-500 focus:ring-emerald-500/10'
                                                : phone.length > 0
                                                    ? 'border-red-300 bg-red-50/30 focus:border-red-500 focus:ring-red-500/10'
                                                    : 'border-slate-200 bg-white focus:border-zen-500 focus:ring-zen-500/10'"
                                        >

                                        <div
                                            x-show="phone.length > 0"
                                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4"
                                        >
                                            <i
                                                x-show="phoneValid"
                                                data-lucide="circle-check"
                                                class="h-5 w-5 text-emerald-500"
                                            ></i>

                                            <i
                                                x-show="!phoneValid"
                                                data-lucide="circle-x"
                                                class="h-5 w-5 text-red-400"
                                            ></i>
                                        </div>
                                    </div>

                                    <p
                                        x-show="phone.length > 0 && !phoneValid"
                                        class="mt-1.5 text-xs font-medium text-red-500"
                                    >
                                        Please enter a valid Philippine mobile number starting with 09.
                                    </p>
                                </div>


                                <!-- Notes -->

                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <label
                                            for="medical_notes"
                                            class="block text-sm font-bold text-slate-800"
                                        >
                                            Notes or special concerns
                                        </label>

                                        <span class="text-[11px] text-slate-400">
                                            Optional
                                        </span>
                                    </div>

                                    <textarea
                                        id="medical_notes"
                                        name="medical_notes"
                                        x-model="medicalNotes"
                                        rows="5"
                                        maxlength="1000"
                                        placeholder="Let us know about allergies, sensitivities, preferences, or anything our therapists should be aware of."
                                        class="block w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-zen-500 focus:ring-4 focus:ring-zen-500/10"
                                    ></textarea>

                                    <div class="mt-1.5 flex justify-end">
                                        <span class="text-[11px] text-slate-400">
                                            <span x-text="medicalNotes.length"></span>/1000
                                        </span>
                                    </div>
                                </div>


                                <!-- Privacy -->

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-zen-600 shadow-sm">
                                            <i data-lucide="shield-check" class="h-4 w-4"></i>
                                        </div>

                                        <div>
                                            <p class="text-xs font-bold text-slate-800">
                                                Your information is handled privately.
                                            </p>

                                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                                Your contact details are used to process and confirm
                                                your appointment request with Spa Alexandria.
                                            </p>
                                        </div>
                                    </div>
                                </div>


                                <!-- Confirmation notice -->

                                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                    <div class="flex gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 shadow-sm">
                                            <i data-lucide="phone-call" class="h-4 w-4"></i>
                                        </div>

                                        <div>
                                            <p class="text-xs font-bold text-amber-900">
                                                Your request is not automatically confirmed.
                                            </p>

                                            <p class="mt-1 text-xs leading-5 text-amber-800">
                                                Our receptionist will contact you to confirm your
                                                appointment. Please keep your phone available.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                     STEP 4 — REVIEW
                ================================================== -->

                <section
                    x-show="step === 4"
                    x-cloak
                    class="fade-up"
                >

                    <div class="rounded-3xl border border-slate-200 bg-white shadow-card">

                        <div class="border-b border-slate-100 p-5 sm:p-7">
                            <p class="text-xs font-bold uppercase tracking-wider text-zen-600">
                                Final step
                            </p>

                            <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950 sm:text-2xl">
                                Review your appointment.
                            </h2>

                            <p class="mt-2 text-sm text-slate-500">
                                Check everything before sending your booking request.
                            </p>
                        </div>


                        <div class="divide-y divide-slate-100">

                            <!-- Appointment -->

                            <div class="p-5 sm:p-7">
                                <div class="mb-4 flex items-center justify-between">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                            Appointment
                                        </p>

                                        <h3 class="mt-1 text-sm font-bold text-slate-900">
                                            Your selected schedule
                                        </h3>
                                    </div>

                                    <button
                                        type="button"
                                        @click="step = 2; $nextTick(() => refreshIcons())"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold text-zen-700 transition hover:bg-zen-50"
                                    >
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Edit
                                    </button>
                                </div>


                                <div class="grid gap-3 sm:grid-cols-2">

                                    <div class="rounded-2xl bg-slate-50 p-4">
                                        <div class="flex items-center gap-2 text-slate-400">
                                            <i data-lucide="calendar-days" class="h-4 w-4"></i>
                                            <span class="text-[11px] font-bold uppercase tracking-wider">
                                                Date
                                            </span>
                                        </div>

                                        <p class="mt-2 text-sm font-bold text-slate-900">
                                            <span x-text="formatDateLong(appointmentDate)"></span>
                                        </p>
                                    </div>


                                    <div class="rounded-2xl bg-slate-50 p-4">
                                        <div class="flex items-center gap-2 text-slate-400">
                                            <i data-lucide="clock-3" class="h-4 w-4"></i>
                                            <span class="text-[11px] font-bold uppercase tracking-wider">
                                                Time
                                            </span>
                                        </div>

                                        <p class="mt-2 text-sm font-bold text-slate-900">
                                            <span x-text="formatTime(selectedTime)"></span>
                                            <span class="font-medium text-slate-400">
                                                —
                                                <span x-text="formatTime(endTime)"></span>
                                            </span>
                                        </p>
                                    </div>

                                </div>
                            </div>


                            <!-- Services -->

                            <div class="p-5 sm:p-7">

                                <div class="mb-4 flex items-center justify-between">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                            Services
                                        </p>

                                        <h3 class="mt-1 text-sm font-bold text-slate-900">
                                            Selected treatments
                                        </h3>
                                    </div>

                                    <button
                                        type="button"
                                        @click="step = 1; $nextTick(() => refreshIcons())"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold text-zen-700 transition hover:bg-zen-50"
                                    >
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Edit
                                    </button>
                                </div>


                                <div class="space-y-2">

                                    <template x-for="service in selectedServiceObjects" :key="service.id">
                                        <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 bg-white p-3.5">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-bold text-slate-800">
                                                    <span x-text="service.name"></span>
                                                </p>

                                                <p class="mt-1 text-xs text-slate-400">
                                                    <span x-text="service.duration_minutes"></span> minutes
                                                </p>
                                            </div>

                                            <p class="shrink-0 text-sm font-extrabold text-slate-900">
                                                ₱<span x-text="formatMoney(service.discount_price || service.price)"></span>
                                            </p>
                                        </div>
                                    </template>

                                </div>

                            </div>


                            <!-- Customer -->

                            <div class="p-5 sm:p-7">

                                <div class="mb-4 flex items-center justify-between">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                            Customer
                                        </p>

                                        <h3 class="mt-1 text-sm font-bold text-slate-900">
                                            Your information
                                        </h3>
                                    </div>

                                    <button
                                        type="button"
                                        @click="step = 3; $nextTick(() => refreshIcons())"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold text-zen-700 transition hover:bg-zen-50"
                                    >
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Edit
                                    </button>
                                </div>


                                <div class="grid gap-3 sm:grid-cols-2">

                                    <div class="rounded-xl border border-slate-100 p-3.5">
                                        <p class="text-[11px] font-semibold text-slate-400">
                                            Name
                                        </p>

                                        <p class="mt-1 text-sm font-bold text-slate-800">
                                            <span x-text="guestName"></span>
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-slate-100 p-3.5">
                                        <p class="text-[11px] font-semibold text-slate-400">
                                            Mobile number
                                        </p>

                                        <p class="mt-1 text-sm font-bold text-slate-800">
                                            <span x-text="phone"></span>
                                        </p>
                                    </div>

                                </div>


                                <template x-if="medicalNotes">
                                    <div class="mt-3 rounded-xl border border-slate-100 p-3.5">
                                        <p class="text-[11px] font-semibold text-slate-400">
                                            Notes
                                        </p>

                                        <p
                                            class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700"
                                            x-text="medicalNotes"
                                        ></p>
                                    </div>
                                </template>

                            </div>


                            <!-- Total -->

                            <div class="bg-slate-950 p-5 text-white sm:p-7">

                                <div class="flex items-end justify-between gap-5">
                                    <div>
                                        <p class="text-xs font-semibold text-slate-400">
                                            Estimated total
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            Final charges are based on selected services.
                                        </p>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-3xl font-extrabold tracking-tight">
                                            ₱<span x-text="formatMoney(totalPrice)"></span>
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            <span x-text="totalDuration"></span> minutes
                                        </p>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                </section>

            </div>


            <!-- ====================================================
                 RIGHT / SUMMARY
            ===================================================== -->

            <aside class="hidden lg:block">

                <div class="sticky top-24 space-y-4">

                    <!-- Summary -->

                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">

                        <div class="border-b border-slate-100 p-5">
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-zen-50 text-zen-600">
                                    <i data-lucide="receipt-text" class="h-4 w-4"></i>
                                </div>

                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                        Booking summary
                                    </p>

                                    <h3 class="text-sm font-extrabold text-slate-900">
                                        Your appointment
                                    </h3>
                                </div>
                            </div>
                        </div>


                        <div class="p-5">

                            <!-- Empty -->

                            <template x-if="selectedServices.length === 0">
                                <div class="py-5 text-center">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                                    </div>

                                    <p class="mt-3 text-sm font-semibold text-slate-700">
                                        No services selected
                                    </p>

                                    <p class="mt-1 text-xs leading-5 text-slate-400">
                                        Your selected treatments will appear here.
                                    </p>
                                </div>
                            </template>


                            <!-- Selected -->

                            <template x-if="selectedServices.length > 0">
                                <div>

                                    <div class="nice-scrollbar max-h-60 space-y-3 overflow-y-auto pr-1">

                                        <template
                                            x-for="service in selectedServiceObjects"
                                            :key="service.id"
                                        >
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold leading-5 text-slate-800">
                                                        <span x-text="service.name"></span>
                                                    </p>

                                                    <p class="mt-0.5 text-xs text-slate-400">
                                                        <span x-text="service.duration_minutes"></span> min
                                                    </p>
                                                </div>

                                                <p class="shrink-0 text-sm font-bold text-slate-900">
                                                    ₱<span x-text="formatMoney(service.discount_price || service.price)"></span>
                                                </p>
                                            </div>
                                        </template>

                                    </div>


                                    <div class="my-4 border-t border-slate-100"></div>


                                    <div class="space-y-3">

                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-500">
                                                Duration
                                            </span>

                                            <span class="text-xs font-bold text-slate-800">
                                                <span x-text="totalDuration"></span> min
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-500">
                                                Schedule
                                            </span>

                                            <span
                                                class="text-right text-xs font-bold text-slate-800"
                                                x-text="appointmentDate ? formatDateShort(appointmentDate) : 'Not selected'"
                                            ></span>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-500">
                                                Time
                                            </span>

                                            <span
                                                class="text-xs font-bold text-slate-800"
                                                x-text="selectedTime ? formatTime(selectedTime) : 'Not selected'"
                                            ></span>
                                        </div>

                                    </div>


                                    <div class="my-4 border-t border-slate-100"></div>


                                    <div class="flex items-end justify-between">
                                        <span class="text-sm font-bold text-slate-800">
                                            Total
                                        </span>

                                        <span class="text-2xl font-extrabold tracking-tight text-zen-700">
                                            ₱<span x-text="formatMoney(totalPrice)"></span>
                                        </span>
                                    </div>

                                </div>
                            </template>

                        </div>

                    </div>


                    <!-- Booking policy -->

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">

                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                <i data-lucide="info" class="h-4 w-4"></i>
                            </div>

                            <p class="text-sm font-bold text-slate-800">
                                Before you book
                            </p>
                        </div>

                        <ul class="mt-4 space-y-3">

                            <li class="flex gap-2.5">
                                <i data-lucide="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"></i>
                                <span class="text-xs leading-5 text-slate-500">
                                    Online requests remain pending until confirmed.
                                </span>
                            </li>

                            <li class="flex gap-2.5">
                                <i data-lucide="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"></i>
                                <span class="text-xs leading-5 text-slate-500">
                                    A receptionist may contact you using the mobile number provided.
                                </span>
                            </li>

                            <li class="flex gap-2.5">
                                <i data-lucide="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"></i>
                                <span class="text-xs leading-5 text-slate-500">
                                    Please arrive on time for your scheduled appointment.
                                </span>
                            </li>

                        </ul>

                    </div>

                </div>

            </aside>

        </div>

    </main>


    <!-- ============================================================
         MOBILE STICKY ACTION BAR
    ============================================================= -->

    <div class="mobile-safe-bottom fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 shadow-[0_-10px_30px_rgba(15,23,42,0.08)] backdrop-blur-xl lg:hidden">

        <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6">

            <div class="flex items-center gap-3">

                <!-- Summary -->

                <div class="min-w-0 flex-1">
                    <template x-if="selectedServices.length === 0">
                        <div>
                            <p class="text-xs font-bold text-slate-800">
                                No services selected
                            </p>

                            <p class="mt-0.5 text-[11px] text-slate-400">
                                Choose a service to continue.
                            </p>
                        </div>
                    </template>

                    <template x-if="selectedServices.length > 0">
                        <div>
                            <p class="truncate text-xs font-bold text-slate-800">
                                <span x-text="selectedServices.length"></span>
                                <span x-text="selectedServices.length === 1 ? 'service' : 'services'"></span>

                                <template x-if="appointmentDate && selectedTime">
                                    <span>
                                        · <span x-text="formatTime(selectedTime)"></span>
                                    </span>
                                </template>
                            </p>

                            <p class="mt-0.5 text-[11px] text-slate-400">
                                ₱<span x-text="formatMoney(totalPrice)"></span>
                                ·
                                <span x-text="totalDuration"></span> min
                            </p>
                        </div>
                    </template>
                </div>


                <!-- Back -->

                <button
                    type="button"
                    x-show="step > 1"
                    @click="previousStep()"
                    class="inline-flex h-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                >
                    Back
                </button>


                <!-- Continue -->

                <button
                    type="button"
                    x-show="step < 4"
                    @click="nextStep()"
                    :disabled="!canContinue"
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-zen-600 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-zen-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                >
                    <span x-text="step === 3 ? 'Review' : 'Continue'"></span>
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </button>


                <!-- Submit -->

                <button
                    type="button"
                    x-show="step === 4"
                    @click="submitBooking()"
                    :disabled="submitting"
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-zen-600 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-zen-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                >
                    <template x-if="!submitting">
                        <span class="inline-flex items-center gap-2">
                            Request Booking
                            <i data-lucide="send" class="h-4 w-4"></i>
                        </span>
                    </template>

                    <template x-if="submitting">
                        <span class="inline-flex items-center gap-2">
                            Sending...
                            <i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i>
                        </span>
                    </template>
                </button>

            </div>

        </div>

    </div>


    <!-- ============================================================
         DESKTOP ACTION BAR
    ============================================================= -->

    <div class="fixed inset-x-0 bottom-0 z-30 hidden border-t border-slate-200 bg-white/95 backdrop-blur-xl lg:block">

        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-8 py-4">

            <div class="min-w-0">
                <template x-if="selectedServices.length === 0">
                    <p class="text-sm font-semibold text-slate-500">
                        Start by selecting a service.
                    </p>
                </template>

                <template x-if="selectedServices.length > 0">
                    <div class="flex items-center gap-4">
                        <div>
                            <p class="text-xs text-slate-400">
                                Appointment total
                            </p>

                            <p class="text-lg font-extrabold text-slate-900">
                                ₱<span x-text="formatMoney(totalPrice)"></span>
                            </p>
                        </div>

                        <div class="h-8 w-px bg-slate-200"></div>

                        <div>
                            <p class="text-xs text-slate-400">
                                Duration
                            </p>

                            <p class="text-sm font-bold text-slate-700">
                                <span x-text="totalDuration"></span> minutes
                            </p>
                        </div>

                        <template x-if="appointmentDate && selectedTime">
                            <div class="hidden items-center gap-2 md:flex">
                                <div class="h-8 w-px bg-slate-200"></div>

                                <div>
                                    <p class="text-xs text-slate-400">
                                        Schedule
                                    </p>

                                    <p class="text-sm font-bold text-slate-700">
                                        <span x-text="formatDateShort(appointmentDate)"></span>
                                        ·
                                        <span x-text="formatTime(selectedTime)"></span>
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>


            <div class="flex items-center gap-2">

                <button
                    type="button"
                    x-show="step > 1"
                    @click="previousStep()"
                    class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                >
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Back
                </button>


                <button
                    type="button"
                    x-show="step < 4"
                    @click="nextStep()"
                    :disabled="!canContinue"
                    class="inline-flex h-11 items-center gap-2 rounded-xl bg-zen-600 px-6 text-sm font-bold text-white shadow-sm transition hover:bg-zen-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                >
                    <span x-text="step === 3 ? 'Review Appointment' : 'Continue'"></span>
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </button>


                <button
                    type="button"
                    x-show="step === 4"
                    @click="submitBooking()"
                    :disabled="submitting"
                    class="inline-flex h-11 items-center gap-2 rounded-xl bg-zen-600 px-6 text-sm font-bold text-white shadow-sm transition hover:bg-zen-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                >
                    <template x-if="!submitting">
                        <span class="inline-flex items-center gap-2">
                            Request Appointment
                            <i data-lucide="send" class="h-4 w-4"></i>
                        </span>
                    </template>

                    <template x-if="submitting">
                        <span class="inline-flex items-center gap-2">
                            Sending request...
                            <i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i>
                        </span>
                    </template>
                </button>

            </div>

        </div>

    </div>


    <!-- ============================================================
         ACTUAL LARAVEL FORM
    ============================================================= -->

    <form
        id="booking-form"
        action="{{ route('booking.store') }}"
        method="POST"
        class="hidden"
    >
        @csrf

        <input
            type="hidden"
            name="source"
            value="public"
        >

        <input
            type="hidden"
            name="appointment_date"
            :value="appointmentDate"
        >

        <input
            type="hidden"
            name="start_time"
            :value="selectedTime"
        >

        <input
            type="hidden"
            name="end_time"
            :value="endTime"
        >

        <input
            type="hidden"
            name="guest_first_name"
            :value="guestName"
        >

        <input
            type="hidden"
            name="guest_phone"
            :value="phone"
        >

        <input
            type="hidden"
            name="medical_notes"
            :value="medicalNotes"
        >

        <template x-for="serviceId in selectedServices" :key="serviceId">
            <input
                type="hidden"
                name="services[]"
                :value="serviceId"
            >
        </template>
    </form>


    <!-- ============================================================
         REBOOK DATA
    ============================================================= -->

    <script>
        window.bookingCategories = {!! $categoriesJson !!};

        window.bookingDefaults = {
            name: @json($defaultName ?? ''),
            phone: @json($defaultPhone ?? ''),
            medicalNotes: @json($customerMedicalNotes ?? ''),
            rebookServiceIds: @json($preselectedIds ?? []),
        };
    </script>


    <!-- ============================================================
         BOOKING LOGIC
    ============================================================= -->

    <script>
        function spaBookingWizard() {
            return {

                /* -------------------------------------------------
                   State
                ------------------------------------------------- */

                step: 1,

                categories: window.bookingCategories || [],

                activeCategory: null,

                selectedServices: [],

                appointmentDate: '',

                selectedTime: '',

                slots: [],

                loadingSlots: false,

                submitting: false,

                guestName: window.bookingDefaults?.name || '',

                phone: window.bookingDefaults?.phone || '',

                medicalNotes: window.bookingDefaults?.medicalNotes || '',

                calendar: null,

                initialized: false,


                /* -------------------------------------------------
                   Initialization
                ------------------------------------------------- */

                init() {

                    this.categories = Array.isArray(this.categories)
                        ? this.categories
                        : [];

                    if (this.categories.length > 0) {
                        this.activeCategory = this.categories[0].id;
                    }

                    this.initializeRebook();

                    this.$nextTick(() => {
                        this.refreshIcons();
                        this.initializeCalendar();
                    });

                    this.initialized = true;
                },


                initializeRebook() {

                    const ids = window.bookingDefaults?.rebookServiceIds || [];

                    if (!Array.isArray(ids) || ids.length === 0) {
                        return;
                    }

                    const normalized = ids
                        .map(id => Number(id))
                        .filter(id => !Number.isNaN(id));

                    this.selectedServices = normalized;

                    if (this.selectedServices.length > 0) {
                        this.activeCategory = this.findCategoryForService(
                            this.selectedServices[0]
                        );
                    }
                },


                /* -------------------------------------------------
                   Category helpers
                ------------------------------------------------- */

                get activeCategoryObject() {

                    return this.categories.find(
                        category => Number(category.id) === Number(this.activeCategory)
                    ) || null;
                },


                get activeCategoryServices() {

                    if (!this.activeCategoryObject) {
                        return [];
                    }

                    return Array.isArray(this.activeCategoryObject.services)
                        ? this.activeCategoryObject.services.filter(service => {
                            return service.is_active === undefined ||
                                   service.is_active === true ||
                                   Number(service.is_active) === 1;
                        })
                        : [];
                },


                findCategoryForService(serviceId) {

                    for (const category of this.categories) {

                        const services = Array.isArray(category.services)
                            ? category.services
                            : [];

                        const found = services.some(
                            service => Number(service.id) === Number(serviceId)
                        );

                        if (found) {
                            return category.id;
                        }
                    }

                    return this.categories.length
                        ? this.categories[0].id
                        : null;
                },


                /* -------------------------------------------------
                   Service selection
                ------------------------------------------------- */

                isSelected(serviceId) {

                    return this.selectedServices.includes(Number(serviceId));
                },


                toggleService(service) {

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

                    /*
                     * Changing services changes duration.
                     * Therefore the selected time may no longer be valid.
                     */

                    this.selectedTime = '';

                    if (this.appointmentDate) {
                        this.loadTimeSlots();
                    }

                    this.$nextTick(() => this.refreshIcons());
                },


                get selectedServiceObjects() {

                    const result = [];

                    for (const category of this.categories) {

                        const services = Array.isArray(category.services)
                            ? category.services
                            : [];

                        for (const service of services) {

                            if (this.isSelected(service.id)) {
                                result.push(service);
                            }

                        }
                    }

                    return result;
                },


                /* -------------------------------------------------
                   Pricing / duration
                ------------------------------------------------- */

                servicePrice(service) {

                    const discount = Number(service.discount_price);
                    const price = Number(service.price);

                    if (
                        service.discount_price !== null &&
                        service.discount_price !== undefined &&
                        service.discount_price !== '' &&
                        !Number.isNaN(discount)
                    ) {
                        return discount;
                    }

                    return Number.isNaN(price) ? 0 : price;
                },


                get totalPrice() {

                    return this.selectedServiceObjects.reduce(
                        (total, service) => {
                            return total + this.servicePrice(service);
                        },
                        0
                    );
                },


                get totalDuration() {

                    return this.selectedServiceObjects.reduce(
                        (total, service) => {
                            return total + Number(service.duration_minutes || 0);
                        },
                        0
                    );
                },


                get endTime() {

                    if (!this.selectedTime || !this.totalDuration) {
                        return '';
                    }

                    const [hours, minutes] = this.selectedTime
                        .split(':')
                        .map(Number);

                    const totalMinutes =
                        (hours * 60) +
                        minutes +
                        this.totalDuration;

                    const endHours = Math.floor(totalMinutes / 60);
                    const endMinutes = totalMinutes % 60;

                    return String(endHours).padStart(2, '0')
                        + ':'
                        + String(endMinutes).padStart(2, '0');
                },


                /* -------------------------------------------------
                   Phone
                ------------------------------------------------- */

                formatPhone() {

                    this.phone = String(this.phone || '')
                        .replace(/\D/g, '')
                        .slice(0, 11);
                },


                get phoneValid() {

                    return /^09\d{9}$/.test(this.phone);
                },


                /* -------------------------------------------------
                   Calendar
                ------------------------------------------------- */

                initializeCalendar() {

                    // Guard: never render twice into the same element
                    if (this.calendar) {
                        this.calendar.updateSize();
                        return;
                    }

                    const calendarEl = document.getElementById(
                        'booking-calendar'
                    );

                    if (!calendarEl || !window.FullCalendar) {
                        return;
                    }

                    /*
                     * Use browser date only for presentation.
                     * The actual booking validation remains server-side.
                     */

                    const today = this.localDateString(
                        new Date()
                    );

                    const maxDate = this.addDays(
                        today,
                        14
                    );

                    this.calendar = new FullCalendar.Calendar(
                        calendarEl,
                        {

                            initialView: 'dayGridMonth',

                            initialDate: today,

                            height: 'auto',

                            fixedWeekCount: false,

                            showNonCurrentDates: true,

                            firstDay: 1,

                            headerToolbar: {
                                left: 'prev,next',
                                center: 'title',
                                right: ''
                            },

                            validRange: {
                                start: today,
                                end: this.addDays(maxDate, 1)
                            },

                            dateClick: (info) => {

                                const clickedDate = info.dateStr;

                                /*
                                 * Sunday
                                 */

                                if (this.isSunday(clickedDate)) {

                                    this.showMessage(
                                        'Sunday is unavailable',
                                        'Spa Alexandria is closed on Sundays.',
                                        'info'
                                    );

                                    return;
                                }

                                /*
                                 * Prevent dates beyond booking window.
                                 */

                                if (
                                    clickedDate < today ||
                                    clickedDate > maxDate
                                ) {
                                    return;
                                }

                                this.selectDate(clickedDate);
                            },

                            dayCellClassNames: (arg) => {

                                const date = this.localDateString(
                                    arg.date
                                );

                                if (this.isSunday(date)) {
                                    return ['booking-sunday'];
                                }

                                return [];
                            },

                            datesSet: () => {
                                this.$nextTick(() => {
                                    this.refreshIcons();
                                });
                            }
                        }
                    );

                    this.calendar.render();
                },


                selectDate(date) {

                    if (this.isSunday(date)) {
                        return;
                    }

                    this.appointmentDate = date;

                    /*
                     * A date change invalidates the old time.
                     */

                    this.selectedTime = '';

                    this.loadTimeSlots();

                    this.$nextTick(() => {
                        this.refreshCalendarSelection();
                    });
                },


                refreshCalendarSelection() {

                    const calendarEl = document.getElementById(
                        'booking-calendar'
                    );

                    if (!calendarEl) {
                        return;
                    }

                    calendarEl
                        .querySelectorAll('.booking-selected-date')
                        .forEach(el => {
                            el.classList.remove('booking-selected-date');
                        });

                    if (!this.appointmentDate) {
                        return;
                    }

                    const selectedCell = calendarEl.querySelector(
                        `[data-date="${this.appointmentDate}"]`
                    );

                    if (selectedCell) {
                        selectedCell.classList.add(
                            'booking-selected-date'
                        );
                    }
                },


                /* -------------------------------------------------
                   Slots
                ------------------------------------------------- */

                async loadTimeSlots() {

                    if (
                        !this.appointmentDate ||
                        this.selectedServices.length === 0
                    ) {
                        this.slots = [];
                        return;
                    }

                    this.loadingSlots = true;

                    try {

                        const params = new URLSearchParams();

                        params.append(
                            'date',
                            this.appointmentDate
                        );

                        params.append(
                            'duration',
                            this.totalDuration
                        );

                        this.selectedServices.forEach(
                            id => params.append('services[]', id)
                        );

                        const response = await fetch(
                            `{{ route('booking.slots') }}?${params.toString()}`,
                            {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }
                        );

                        if (!response.ok) {
                            // Surface Laravel validation/error messages (e.g. 422)
                            let serverMessage = null;

                            try {
                                const err = await response.json();
                                serverMessage = err.message || null;

                                if (!serverMessage && err.errors) {
                                    serverMessage = Object.values(err.errors)
                                        .flat()
                                        .join(' ');
                                }
                            } catch (parseError) {
                                // non-JSON response — fall through to default
                            }

                            throw new Error(
                                serverMessage || 'Unable to load available times.'
                            );
                        }

                        const data = await response.json();

                        /*
                         * Controller currently returns either:
                         * - an array
                         * - { slots: [...] }
                         */

                        if (Array.isArray(data)) {
                            this.slots = data;
                        } else if (Array.isArray(data.slots)) {
                            this.slots = data.slots;
                        } else {
                            this.slots = [];
                        }

                        /*
                         * Only show slots for the selected date.
                         */

                        this.slots = this.slots.filter(slot => {

                            if (!slot.date) {
                                return true;
                            }

                            return slot.date === this.appointmentDate;
                        });

                        /*
                         * Make sure an old selected time is removed
                         * if the server no longer returns it.
                         */

                        const stillAvailable = this.slots.some(
                            slot => {
                                return slot.time === this.selectedTime &&
                                    slot.room_available !== false;
                            }
                        );

                        if (!stillAvailable) {
                            this.selectedTime = '';
                        }

                    } catch (error) {

                        console.error(
                            'Booking slot error:',
                            error
                        );

                        this.slots = [];
                        this.selectedTime = '';

                        this.showMessage(
                            'Unable to load times',
                            error.message || 'We could not load the available appointment times. Please try again.',
                            'error'
                        );

                    } finally {

                        this.loadingSlots = false;

                        this.$nextTick(() => {
                            this.refreshIcons();
                            this.refreshCalendarSelection();
                        });
                    }
                },


                get availableSlots() {

                    return this.slots.filter(
                        slot => slot.room_available !== false
                    );
                },


                get morningSlots() {

                    return this.availableSlots.filter(
                        slot => this.hourOf(slot.time) < 12
                    );
                },


                get afternoonSlots() {

                    return this.availableSlots.filter(
                        slot => {
                            const hour = this.hourOf(slot.time);

                            return hour >= 12 && hour < 17;
                        }
                    );
                },


                get eveningSlots() {

                    return this.availableSlots.filter(
                        slot => this.hourOf(slot.time) >= 17
                    );
                },


                selectSlot(slot) {

                    if (!slot || slot.room_available === false) {
                        return;
                    }

                    this.selectedTime = slot.time;

                    this.$nextTick(() => {
                        this.refreshIcons();
                    });
                },


                /* -------------------------------------------------
                   Step navigation
                ------------------------------------------------- */

                canEnterStep(targetStep) {

                    if (targetStep <= 1) {
                        return true;
                    }

                    if (targetStep === 2) {
                        return this.selectedServices.length > 0;
                    }

                    if (targetStep === 3) {
                        return (
                            this.selectedServices.length > 0 &&
                            this.appointmentDate &&
                            this.selectedTime
                        );
                    }

                    if (targetStep === 4) {
                        return (
                            this.selectedServices.length > 0 &&
                            this.appointmentDate &&
                            this.selectedTime &&
                            this.guestName.trim().length > 0 &&
                            this.phoneValid
                        );
                    }

                    return false;
                },


                get canContinue() {

                    if (this.step === 1) {
                        return this.selectedServices.length > 0;
                    }

                    if (this.step === 2) {
                        return !!(
                            this.appointmentDate &&
                            this.selectedTime
                        );
                    }

                    if (this.step === 3) {
                        return (
                            this.guestName.trim().length > 0 &&
                            this.phoneValid
                        );
                    }

                    return true;
                },


                nextStep() {

                    if (!this.canContinue) {
                        this.validateCurrentStep();
                        return;
                    }

                    if (this.step < 4) {
                        this.step++;
                    }

                    this.$nextTick(() => {
                        this.refreshIcons();

                        if (
                            this.step === 2 &&
                            this.calendar
                        ) {
                            this.calendar.updateSize();
                        }

                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    });
                },


                previousStep() {

                    if (this.step > 1) {
                        this.step--;
                    }

                    this.$nextTick(() => {
                        this.refreshIcons();

                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    });
                },


                goToStep(targetStep) {

                    if (targetStep > this.step) {

                        if (!this.canEnterStep(targetStep)) {
                            return;
                        }
                    }

                    /*
                     * Don't allow jumping over unfinished steps.
                     */

                    if (targetStep === 2 && !this.selectedServices.length) {
                        return;
                    }

                    if (
                        targetStep >= 3 &&
                        !(
                            this.selectedServices.length &&
                            this.appointmentDate &&
                            this.selectedTime
                        )
                    ) {
                        return;
                    }

                    if (
                        targetStep === 4 &&
                        !(
                            this.guestName.trim() &&
                            this.phoneValid
                        )
                    ) {
                        return;
                    }

                    this.step = targetStep;

                    this.$nextTick(() => {
                        this.refreshIcons();

                        if (
                            targetStep === 2 &&
                            this.calendar
                        ) {
                            this.calendar.updateSize();
                        }

                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    });
                },


                validateCurrentStep() {

                    if (this.step === 1) {

                        this.showMessage(
                            'Choose a service',
                            'Please select at least one service before continuing.',
                            'info'
                        );

                        return;
                    }

                    if (this.step === 2) {

                        if (!this.appointmentDate) {

                            this.showMessage(
                                'Choose a date',
                                'Please select an appointment date.',
                                'info'
                            );

                            return;
                        }

                        if (!this.selectedTime) {

                            this.showMessage(
                                'Choose a time',
                                'Please select an available appointment time.',
                                'info'
                            );

                            return;
                        }

                        return;
                    }

                    if (this.step === 3) {

                        if (!this.guestName.trim()) {

                            this.showMessage(
                                'Name required',
                                'Please enter your full name.',
                                'info'
                            );

                            return;
                        }

                        if (!this.phoneValid) {

                            this.showMessage(
                                'Invalid mobile number',
                                'Please enter a valid Philippine mobile number.',
                                'info'
                            );

                            return;
                        }
                    }
                },


                /* -------------------------------------------------
                   Submit
                ------------------------------------------------- */

                async submitBooking() {

                    if (this.submitting) {
                        return;
                    }

                    if (!this.canEnterStep(4)) {

                        this.showMessage(
                            'Incomplete booking',
                            'Please complete all required booking details.',
                            'warning'
                        );

                        return;
                    }

                    /*
                     * Confirm one final time before POST.
                     */

                    const result = await Swal.fire({
                        title: 'Send appointment request?',
                        html: `
                            <div class="text-left">
                                <div class="rounded-xl bg-slate-50 p-4 mb-3">
                                    <p class="text-xs font-semibold text-slate-400">
                                        Schedule
                                    </p>
                                    <p class="mt-1 text-sm font-bold text-slate-900">
                                        ${this.escapeHtml(this.formatDateLong(this.appointmentDate))}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        ${this.escapeHtml(this.formatTime(this.selectedTime))}
                                        – 
                                        ${this.escapeHtml(this.formatTime(this.endTime))}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-zen-50 p-4">
                                    <p class="text-xs font-semibold text-zen-700">
                                        Estimated total
                                    </p>
                                    <p class="mt-1 text-xl font-extrabold text-zen-800">
                                        ₱${this.formatMoney(this.totalPrice)}
                                    </p>
                                </div>
                            </div>
                        `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Send request',
                        cancelButtonText: 'Review again',
                        reverseButtons: true,
                        customClass: {
                            popup: 'rounded-3xl',
                            confirmButton: 'rounded-xl px-5 py-3 font-bold',
                            cancelButton: 'rounded-xl px-5 py-3 font-bold'
                        },
                        buttonsStyling: true
                    });

                    if (!result.isConfirmed) {
                        return;
                    }

                    this.submitting = true;

                    /*
                     * Submit the real Laravel form.
                     */

                    const form = document.getElementById(
                        'booking-form'
                    );

                    if (!form) {
                        this.submitting = false;

                        this.showMessage(
                            'Booking error',
                            'The booking form could not be found.',
                            'error'
                        );

                        return;
                    }

                    form.submit();
                },


                /* -------------------------------------------------
                   Date / time utilities
                ------------------------------------------------- */

                localDateString(date) {

                    const year = date.getFullYear();

                    const month = String(
                        date.getMonth() + 1
                    ).padStart(2, '0');

                    const day = String(
                        date.getDate()
                    ).padStart(2, '0');

                    return `${year}-${month}-${day}`;
                },


                addDays(dateString, days) {

                    const parts = dateString
                        .split('-')
                        .map(Number);

                    const date = new Date(
                        parts[0],
                        parts[1] - 1,
                        parts[2]
                    );

                    date.setDate(
                        date.getDate() + days
                    );

                    return this.localDateString(date);
                },


                isSunday(dateString) {

                    const parts = dateString
                        .split('-')
                        .map(Number);

                    const date = new Date(
                        parts[0],
                        parts[1] - 1,
                        parts[2]
                    );

                    return date.getDay() === 0;
                },


                hourOf(time) {

                    if (!time) {
                        return 0;
                    }

                    return Number(
                        String(time)
                            .split(':')[0]
                    );
                },


                formatTime(time) {

                    if (!time) {
                        return '';
                    }

                    const parts = String(time)
                        .split(':')
                        .map(Number);

                    const hours = parts[0];
                    const minutes = parts[1] || 0;

                    const suffix = hours >= 12
                        ? 'PM'
                        : 'AM';

                    const displayHour =
                        hours % 12 || 12;

                    return `${displayHour}:${String(minutes).padStart(2, '0')} ${suffix}`;
                },


                formatDateLong(dateString) {

                    if (!dateString) {
                        return '';
                    }

                    const [year, month, day] = dateString
                        .split('-')
                        .map(Number);

                    const date = new Date(
                        year,
                        month - 1,
                        day
                    );

                    return new Intl.DateTimeFormat(
                        'en-PH',
                        {
                            weekday: 'long',
                            month: 'long',
                            day: 'numeric',
                            year: 'numeric'
                        }
                    ).format(date);
                },


                formatDateShort(dateString) {

                    if (!dateString) {
                        return 'Not selected';
                    }

                    const [year, month, day] = dateString
                        .split('-')
                        .map(Number);

                    const date = new Date(
                        year,
                        month - 1,
                        day
                    );

                    return new Intl.DateTimeFormat(
                        'en-PH',
                        {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric'
                        }
                    ).format(date);
                },


                formatMoney(value) {

                    const number = Number(value || 0);

                    return number.toLocaleString(
                        'en-PH',
                        {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }
                    );
                },


                /* -------------------------------------------------
                   UI helpers
                ------------------------------------------------- */

                refreshIcons() {

                    if (
                        window.lucide &&
                        typeof window.lucide.createIcons === 'function'
                    ) {
                        window.lucide.createIcons();
                    }
                },


                showMessage(title, text, icon = 'info') {

                    Swal.fire({
                        title,
                        text,
                        icon,
                        confirmButtonText: 'Okay',
                        customClass: {
                            popup: 'rounded-3xl',
                            confirmButton: 'rounded-xl px-5 py-3 font-bold'
                        }
                    });
                },


                escapeHtml(value) {

                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

            };
        }
    </script>


    <!-- ============================================================
         EXTRA CALENDAR STYLING
    ============================================================= -->

    <style>
        #booking-calendar .booking-selected-date {
            background: rgba(20, 184, 166, 0.10) !important;
        }

        #booking-calendar .booking-selected-date .fc-daygrid-day-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            margin: 0.15rem;
            border-radius: 9999px;
            background: #0f766e;
            color: white;
        }

        #booking-calendar .booking-sunday {
            background: #fafafa !important;
        }

        #booking-calendar .booking-sunday .fc-daygrid-day-number {
            color: #cbd5e1 !important;
        }
    </style>

</body>
</html>