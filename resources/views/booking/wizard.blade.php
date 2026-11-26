<!DOCTYPE html>
<html lang="en" x-data="spaBookingWizard()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Book your spa appointment online at Spa Alexandria in Bacolod City. Choose treatments, preferred date and time easily.">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Favicon --}}
    @include('partials.favicon')

    <title>Book an Appointment | Spa Alexandria</title>

    <!-- Dark mode: apply before render to avoid flash -->
    <script>
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.documentElement.classList.add('dark');
        }
    </script>

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

@php
    $backRoute = url('/');
    $backLabel = 'Back to Home';
    if (auth()->check()) {
        $role = auth()->user()->roles->first()->name ?? 'customer';
        $backRoute = match ($role) {
            'admin' => route('admin-dashboard'),
            'receptionist' => route('receptionist.dashboard'),
            'staff' => route('staff.dashboard'),
            'customer' => route('customer-dashboard'),
            default => url('/'),
        };
        $backLabel = 'Back to Dashboard';
    }
@endphp

<body class="min-h-screen overflow-x-hidden bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased">

    <!-- ============================================================
         NAVIGATION
    ============================================================= -->

    <header class="sticky top-0 z-50 border-b border-slate-200/80 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">

            <a href="{{ $backRoute }}" class="flex items-center gap-3">
                @if(file_exists(public_path('uploads/branding/spa-alexandria-logo.png')))
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white p-1 shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <img src="{{ asset('uploads/branding/spa-alexandria-logo.png') }}" alt="Spa Alexandria" class="h-10 w-10 object-contain">
                    </div>
                @else
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-zen-600 text-white shadow-sm">
                        <i data-lucide="sparkles" class="h-5 w-5"></i>
                    </div>
                @endif

                <div class="block">
                    <div class="text-xs sm:text-sm font-bold tracking-tight text-slate-900 dark:text-slate-100 whitespace-nowrap">
                        Spa Alexandria
                    </div>

                    <div class="text-[10px] sm:text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        Wellness &amp; Relaxation
                    </div>
                </div>
            </a>

            <div class="flex items-center gap-4">
                <!-- Dark Mode Toggle -->
                <button type="button" onclick="toggleDarkMode()" class="w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 dark:text-slate-500 hover:text-teal-600 dark:hover:text-teal-400 hover:border-teal-200 dark:hover:border-teal-700 transition" aria-label="Toggle dark mode">
                    <i data-lucide="sun" class="h-4 w-4 hidden dark:block"></i>
                    <i data-lucide="moon" class="h-4 w-4 block dark:hidden"></i>
                </button>

                <a
                    href="{{ $backRoute }}"
                    class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 transition hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100"
                >
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    <span class="hidden sm:inline">{{ $backLabel }}</span>
                    <span class="sm:hidden">{{ auth()->check() ? 'Dashboard' : 'Home' }}</span>
                </a>
            </div>
        </div>
    </header>

    <div class="sticky top-16 z-40 px-4 pt-2.5 sm:px-6 sm:pt-3 lg:px-8">
        <div class="mx-auto max-w-7xl">
            @include('partials.academic-capstone-notice')
        </div>
    </div>


    <!-- ============================================================
         PAGE
    ============================================================= -->

    <main class="mx-auto max-w-7xl px-4 pb-36 pt-6 sm:px-6 sm:pb-20 sm:pt-10 lg:px-8">

        <!-- Header -->

        <section class="mb-8">
            <div class="max-w-3xl">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-zen-50 dark:bg-teal-900/20 px-3 py-1.5 text-xs font-bold text-zen-700 dark:text-teal-400 ring-1 ring-zen-100 dark:ring-teal-800/50">
                    <span class="h-1.5 w-1.5 rounded-full bg-zen-500"></span>
                    ONLINE APPOINTMENT
                </div>

                <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                    Book your time to relax.
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400 dark:text-slate-500 sm:text-base">
                    Choose your treatments, select a convenient schedule, and send your
                    appointment request. Our receptionist will confirm your booking.
                </p>
            </div>
        </section>

        @if(!empty($activeNotice))
            <div class="mb-8 rounded-2xl border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/40 p-4 text-amber-900 dark:text-amber-200 flex items-start gap-3 shadow-sm">
                <div class="p-2 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400 shrink-0 mt-0.5">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider flex items-center gap-2">
                        <span>{{ $activeNotice['title'] }}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-200 dark:bg-amber-800/60 text-amber-800 dark:text-amber-300 font-semibold uppercase">
                            {{ !empty($activeNotice['is_closed']) ? 'Closed Today' : 'Today\'s Notice' }}
                        </span>
                    </h4>
                    <p class="text-xs text-amber-800/90 dark:text-amber-300/90 mt-1 leading-relaxed">
                        {{ $activeNotice['message'] ?? 'We have special operating hours or closure today.' }}
                    </p>
                </div>
            </div>
        @endif

        @if(!empty($upcomingNotices) && $upcomingNotices->isNotEmpty())
            <div class="mb-8 space-y-3">
                <div class="sr-only">Upcoming Schedule Notices</div>
                @foreach($upcomingNotices as $notice)
                    @include('shared._business_notice_banner', ['notice' => $notice])
                @endforeach
            </div>
        @endif


        <!-- ========================================================
             STEP INDICATOR
        ========================================================= -->

        <section class="mb-8">
            <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 shadow-card sm:p-4">

                <div class="grid grid-cols-3 gap-1 sm:gap-2">

                    <!-- Step 1 -->

                    <button
                        type="button"
                        @click="goToStep(1)"
                        class="group rounded-xl p-1.5 sm:px-3 sm:py-2.5 text-left transition"
                        :class="step >= 1
                            ? 'bg-zen-50 dark:bg-teal-900/20'
                            : 'bg-transparent hover:bg-slate-50 dark:hover:bg-slate-800/50'"
                    >
                        <div class="flex items-center gap-1.5 sm:gap-3">
                            <div
                                class="flex h-7 w-7 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold transition"
                                :class="step >= 1
                                    ? 'bg-zen-600 text-white'
                                    : 'bg-slate-100 dark:bg-slate-800/60 text-slate-400'"
                            >
                                <svg x-show="step > 1" x-cloak class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span x-show="step <= 1">1</span>
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="break-words text-xs font-bold sm:text-sm leading-tight"
                                    :class="step >= 1 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500'"
                                >
                                    Services
                                </p>

                                <p class="hidden text-[11px] text-slate-400 dark:text-slate-500 sm:block">
                                    Choose treatments
                                </p>
                            </div>
                        </div>
                    </button>


                    <!-- Step 2 -->

                    <button
                        type="button"
                        @click="goToStep(2)"
                        :disabled="!canEnterStep(2)"
                        class="group rounded-xl p-1.5 sm:px-3 sm:py-2.5 text-left transition"
                        :class="step >= 2
                            ? 'bg-zen-50 dark:bg-teal-900/20'
                            : 'bg-transparent hover:bg-slate-50 dark:bg-slate-900 disabled:hover:bg-transparent'"
                    >
                        <div class="flex items-center gap-1.5 sm:gap-3">
                            <div
                                class="flex h-7 w-7 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold transition"
                                :class="step >= 2
                                    ? 'bg-zen-600 text-white'
                                    : 'bg-slate-100 dark:bg-slate-800/60 text-slate-400'"
                            >
                                <svg x-show="step > 2" x-cloak class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span x-show="step <= 2">2</span>
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="break-words text-xs font-bold sm:text-sm leading-tight"
                                    :class="step >= 2 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500'"
                                >
                                    Date &amp; Time
                                </p>

                                <p class="hidden text-[11px] text-slate-400 dark:text-slate-500 sm:block">
                                    Find a schedule
                                </p>
                            </div>
                        </div>
                    </button>


                    <!-- Step 3 -->

                    <button
                        type="button"
                        @click="goToStep(3)"
                        :disabled="!canEnterStep(3)"
                        class="group rounded-xl p-1.5 sm:px-3 sm:py-2.5 text-left transition"
                        :class="step >= 3
                            ? 'bg-zen-50 dark:bg-teal-900/20'
                            : 'bg-transparent hover:bg-slate-50 dark:bg-slate-900 disabled:hover:bg-transparent'"
                    >
                        <div class="flex items-center gap-1.5 sm:gap-3">
                            <div
                                class="flex h-7 w-7 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold transition"
                                :class="step >= 3
                                    ? 'bg-zen-600 text-white'
                                    : 'bg-slate-100 dark:bg-slate-800/60 text-slate-400'"
                            >
                                <svg x-show="step > 3" x-cloak class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span x-show="step <= 3">3</span>
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="break-words text-xs font-bold sm:text-sm leading-tight"
                                    :class="step >= 3 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500'"
                                >
                                    Your Details
                                </p>

                                <p class="hidden text-[11px] text-slate-400 dark:text-slate-500 sm:block">
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

            <div class="min-w-0">

                <!-- =================================================
                     STEP 1 — SERVICES
                ================================================== -->

                <section
                    x-show="step === 1"
                    x-cloak
                    class="fade-up"
                >

                    <div class="overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-card">

                        <div class="border-b border-slate-100 dark:border-slate-700/50 p-5 sm:p-7">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wider text-zen-600">
                                        Step 1
                                    </p>

                                    <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                                        What would you like today?
                                    </h2>

                                    <p class="mt-2 break-words text-sm text-slate-500 dark:text-slate-400 dark:text-slate-500">
                                        Select one or more treatments for your appointment.
                                    </p>
                                </div>

                                <div class="hidden rounded-xl bg-slate-50 dark:bg-slate-900 px-3 py-2 text-right sm:block">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                        Selected
                                    </p>

                                    <p class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                        <span x-text="selectedServices.length"></span>
                                        <span x-text="selectedServices.length === 1 ? 'service' : 'services'"></span>
                                    </p>
                                </div>
                            </div>
                        </div>


                        <!-- Category navigation -->

                        <div class="border-b border-slate-100 dark:border-slate-700/50 px-5 pt-4 sm:px-7">
                            <div class="grid grid-cols-2 gap-2 pb-4">

                                <template x-for="category in categories" :key="category.id">
                                    <button
                                        type="button"
                                        @click="activeCategory = category.id; $nextTick(() => refreshIcons())"
                                        class="flex flex-col justify-between h-full rounded-2xl border p-3 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-zen-500"
                                        :class="activeCategory === category.id
                                            ? 'border-zen-500 bg-zen-50 dark:border-teal-600 dark:bg-teal-950/20'
                                            : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-zen-200 dark:hover:border-teal-700 hover:bg-slate-50 dark:hover:bg-slate-800/80'"
                                    >
                                        <p
                                            class="text-sm font-semibold leading-tight"
                                            :class="activeCategory === category.id
                                                ? 'text-zen-700 dark:text-teal-300'
                                                : 'text-slate-800 dark:text-slate-200'"
                                            x-text="category.name"
                                        ></p>
                                        <p
                                            class="mt-0.5 text-xs"
                                            :class="activeCategory === category.id
                                                ? 'text-zen-500 dark:text-teal-400'
                                                : 'text-slate-400 dark:text-slate-500'"
                                            x-text="category.services.length + ' services'"
                                        ></p>
                                    </button>
                                </template>

                            </div>
                        </div>


                        <!-- Services -->

                        <div class="p-5 sm:p-7">

                            <template x-if="activeCategoryObject">
                                <div>

                                    <div class="mb-4">
                                        <h3
                                            class="font-bold text-slate-900 dark:text-slate-100"
                                            x-text="activeCategoryObject.name"
                                        ></h3>

                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Select the treatments you want to include.
                                        </p>
                                    </div>


                                    <div class="grid gap-3 sm:grid-cols-2">

                                        <template
                                            x-for="service in activeCategoryServices"
                                            :key="service.id"
                                        >

                                            <div
                                                class="service-card group relative overflow-hidden rounded-2xl border p-4 text-left transition-all duration-200"
                                                :class="isSelected(service.id)
                                                    ? 'border-zen-500 bg-zen-50/70 shadow-md shadow-zen-100 dark:border-teal-600 dark:bg-teal-950/20'
                                                    : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-zen-200 dark:hover:border-teal-700 hover:bg-slate-50 dark:hover:bg-slate-800/80'"
                                            >

                                                <!-- Selected indicator -->
                                                <button
                                                    type="button"
                                                    @click.stop="toggleService(service)"
                                                    class="absolute right-3 top-3 z-10 flex h-6 w-6 items-center justify-center rounded-full transition focus:outline-none"
                                                    :class="isSelected(service.id)
                                                        ? 'bg-zen-600 text-white'
                                                        : 'bg-slate-100 dark:bg-slate-800/60 text-transparent group-hover:bg-slate-200'"
                                                    :aria-label="isSelected(service.id) ? 'Deselect service' : 'Select service'"
                                                >
                                                    <i data-lucide="check" class="h-3.5 w-3.5" :class="isSelected(service.id) ? 'text-white' : 'text-slate-400 opacity-0 group-hover:opacity-100'"></i>
                                                </button>

                                                <!-- Clickable card top area for selection -->
                                                <div
                                                    class="cursor-pointer select-none"
                                                    @click="toggleService(service)"
                                                >
                                                    <div class="flex gap-4">

                                                        <!-- Image -->
                                                        <div
                                                            class="h-16 w-16 sm:h-20 sm:w-20 shrink-0 overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800/60"
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

                                                        <!-- Info Summary -->
                                                        <div class="min-w-0 flex-1 pr-7">

                                                            <h4 class="break-words text-sm font-bold leading-5 text-slate-900 dark:text-slate-100">
                                                                <span x-text="service.name"></span>
                                                            </h4>

                                                            <!-- Short preview description -->
                                                            <p
                                                                x-show="service.description"
                                                                class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500 dark:text-slate-400"
                                                                x-text="service.description"
                                                            ></p>

                                                            <!-- Summary Badges & Pricing -->
                                                            <div class="mt-2 flex flex-wrap items-center gap-2">

                                                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                                    <i data-lucide="clock-3" class="h-3.5 w-3.5"></i>
                                                                    <span x-text="service.duration_minutes"></span> min
                                                                </span>

                                                                <span class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>

                                                                <span class="text-sm font-extrabold text-zen-700 dark:text-teal-400">
                                                                    ₱<span x-text="formatMoney(service.discount_price || service.price)"></span>
                                                                </span>

                                                                <template x-if="service.discount_price && service.discount_price < service.price">
                                                                    <span class="text-xs text-slate-400 line-through">
                                                                        ₱<span x-text="formatMoney(service.price)"></span>
                                                                    </span>
                                                                </template>

                                                                <template x-if="service.is_package">
                                                                    <span class="rounded bg-purple-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">
                                                                        Package
                                                                    </span>
                                                                </template>

                                                                <template x-if="service.participants_required > 1 || (service.name && service.name.toLowerCase().includes('couple'))">
                                                                    <div class="mt-1 flex flex-wrap items-center gap-1">
                                                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 border border-rose-200 px-2 py-0.5 text-[9px] font-bold text-rose-700 dark:bg-rose-950/40 dark:border-rose-900/40 dark:text-rose-300">
                                                                            <i data-lucide="heart" class="h-2.5 w-2.5 text-rose-500"></i>
                                                                            <span>Duo Experience • 2 Guests (Covers Both)</span>
                                                                        </span>
                                                                    </div>
                                                                </template>

                                                                <template x-if="(!service.participants_required || service.participants_required <= 1) && (!service.name || !service.name.toLowerCase().includes('couple')) && (service.therapists_required > 1)">
                                                                    <div class="mt-1 flex flex-wrap items-center gap-1">
                                                                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 border border-indigo-200 px-2 py-0.5 text-[9px] font-bold text-indigo-700 dark:bg-indigo-950/40 dark:border-indigo-900/40 dark:text-indigo-300">
                                                                            <i data-lucide="sparkles" class="h-2.5 w-2.5 text-indigo-500"></i>
                                                                            <span>Four-Hands • 2 Therapists on 1 Guest</span>
                                                                        </span>
                                                                    </div>
                                                                </template>

                                                            </div>

                                                        </div>

                                                    </div>
                                                </div>

                                                <!-- Card Bottom Action Bar: View Details Modal trigger -->
                                                <div class="mt-2.5 flex items-center justify-between border-t border-slate-100 dark:border-slate-700/50 pt-2">
                                                    <button
                                                        type="button"
                                                        @click.stop="openServiceDetails(service, $event)"
                                                        class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-semibold text-zen-700 hover:bg-zen-50 dark:text-teal-400 dark:hover:bg-slate-700/50 transition-colors focus:outline-none"
                                                    >
                                                        <i data-lucide="info" class="h-3.5 w-3.5"></i>
                                                        <span>View full details</span>
                                                    </button>

                                                    <template x-if="isSelected(service.id)">
                                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-zen-700 dark:text-teal-400">
                                                            <i data-lucide="check-circle" class="h-3 w-3"></i>
                                                            <span>Selected</span>
                                                        </span>
                                                    </template>
                                                </div>

                                                <!-- Package Choice Selection (When Selected) -->
                                                <template x-if="service.is_package && service.choice_groups && service.choice_groups.length > 0 && isSelected(service.id)">
                                                    <div class="mt-2.5 rounded-xl border border-zen-300 bg-white p-2.5 text-left shadow-2xs dark:border-teal-700/80 dark:bg-slate-900" @click.stop>
                                                        <template x-for="group in service.choice_groups" :key="group.id">
                                                            <div class="space-y-1.5">
                                                                <div class="flex items-center justify-between">
                                                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200" x-text="group.name || group.label"></span>
                                                                    <span class="rounded bg-zen-100 px-1.5 py-0.5 text-[10px] font-bold text-zen-700 dark:bg-teal-900/40 dark:text-teal-300" x-show="group.is_required">Required</span>
                                                                </div>
                                                                <div class="grid gap-1">
                                                                    <template x-for="opt in group.options" :key="opt.id">
                                                                        <label
                                                                            class="flex cursor-pointer items-center justify-between rounded-lg border p-2 text-xs transition"
                                                                            :class="packageChoices[group.id] === opt.id || packageChoices[group.id] === opt.service_id
                                                                                ? 'border-zen-500 bg-zen-50/80 font-bold text-zen-900 dark:border-teal-500 dark:bg-teal-950/40 dark:text-teal-100'
                                                                                : 'border-slate-200 bg-slate-50/50 font-normal text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-300 dark:hover:bg-slate-800'"
                                                                            @click.stop="selectChoiceOption(group.id, opt.id)"
                                                                        >
                                                                            <div class="flex items-center gap-2">
                                                                                <input
                                                                                    type="radio"
                                                                                    :name="'pkg_choice_' + service.id + '_' + group.id"
                                                                                    :value="opt.id"
                                                                                    :checked="packageChoices[group.id] === opt.id || packageChoices[group.id] === opt.service_id"
                                                                                    class="h-3.5 w-3.5 text-zen-600 focus:ring-zen-500 border-slate-300 dark:border-slate-600"
                                                                                    @click.stop="selectChoiceOption(group.id, opt.id)"
                                                                                >
                                                                                <span x-text="opt.name || opt.service_name"></span>
                                                                            </div>
                                                                            <span class="text-[10px] text-slate-400 dark:text-slate-500" x-show="opt.service_duration">
                                                                                <span x-text="opt.service_duration"></span>m
                                                                            </span>
                                                                        </label>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>

                                            </div>

                                        </template>

                                    </div>


                                    <!-- Empty category -->

                                    <template x-if="activeCategoryServices.length === 0">
                                        <div class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 py-12 text-center">
                                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800/60 text-slate-400 dark:text-slate-500">
                                                <i data-lucide="sparkles" class="h-6 w-6"></i>
                                            </div>

                                            <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                                No services available
                                            </p>

                                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
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

                    <div class="overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-card">

                        <div class="border-b border-slate-100 dark:border-slate-700/50 p-5 sm:p-7">
                            <p class="text-xs font-bold uppercase tracking-wider text-zen-600">
                                Step 2
                            </p>

                            <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                                Find a time that works for you.
                            </h2>

                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 dark:text-slate-500">
                                Select a date first, then choose from the available appointment times.
                            </p>
                        </div>


                        <div class="grid lg:grid-cols-[minmax(0,1fr)_300px]">

                            <!-- Calendar -->

                            <div class="border-b border-slate-100 dark:border-slate-700/50 p-5 sm:p-7 lg:border-b-0 lg:border-r">

                                <div class="mb-4 flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                            Choose a date
                                        </h3>

                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 dark:text-slate-500">
                                            Sundays are unavailable.
                                        </p>
                                    </div>

                                    <div class="hidden items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 dark:text-slate-500 sm:flex">
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
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                        Available times
                                    </p>

                                    <h3 class="mt-1 text-base font-extrabold text-slate-900 dark:text-slate-100">
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
                                            <div class="h-12 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800/60"></div>
                                        </template>
                                    </div>
                                </template>


                                <!-- No date -->

                                <template x-if="!loadingSlots && !appointmentDate">
                                    <div class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 p-6 text-center">
                                        <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800/60 text-slate-400 dark:text-slate-500">
                                            <i data-lucide="calendar-days" class="h-5 w-5"></i>
                                        </div>

                                        <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                            Choose a date
                                        </p>

                                        <p class="mt-1 text-xs leading-5 text-slate-400 dark:text-slate-500">
                                            Available times will appear here.
                                        </p>
                                    </div>
                                </template>


                                <!-- Contextual No Slots Diagnostic Card -->

                                <template x-if="!loadingSlots && appointmentDate && availableSlots.length === 0">
                                    <div class="rounded-2xl border border-amber-200 dark:border-amber-800/40 bg-gradient-to-br from-amber-50/90 to-orange-50/40 dark:from-amber-950/20 dark:to-neutral-900 p-5 sm:p-6 text-left shadow-sm transition-all">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 shadow-xs">
                                                <i data-lucide="calendar-x-2" class="h-5 w-5"></i>
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                                                          x-text="diagnostics?.badge || 'Booking Notice'">
                                                    </span>
                                                </div>

                                                <h4 class="mt-2 text-base font-bold text-gray-900 dark:text-white"
                                                    x-text="diagnostics?.title || 'No available times for this date'">
                                                </h4>

                                                <p class="mt-1.5 text-xs leading-relaxed text-gray-600 dark:text-neutral-300"
                                                   x-text="diagnostics?.message || 'Every appointment slot for this date is currently occupied or unavailable.'">
                                                </p>

                                                <template x-if="diagnostics?.tip">
                                                    <div class="mt-3.5 flex items-start gap-2 rounded-xl bg-white/80 dark:bg-neutral-800/80 border border-amber-200/60 dark:border-amber-800/40 p-3">
                                                        <i data-lucide="lightbulb" class="h-4 w-4 shrink-0 text-amber-500 mt-0.5"></i>
                                                        <p class="text-xs text-amber-900 dark:text-amber-200 font-medium leading-relaxed"
                                                           x-text="diagnostics.tip">
                                                        </p>
                                                    </div>
                                                </template>

                                                <div class="mt-4 flex flex-wrap items-center gap-2.5">
                                                    <template x-if="diagnostics?.action_step === 1">
                                                        <button type="button"
                                                                @click="goToStep(1)"
                                                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 transition shadow-xs">
                                                            <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                                                            <span x-text="diagnostics.action_label || 'Modify Services'"></span>
                                                        </button>
                                                    </template>

                                                    <button type="button"
                                                            @click="nextDay()"
                                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-gray-700 dark:text-neutral-200 bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-700 transition shadow-xs">
                                                        <i data-lucide="calendar" class="h-3.5 w-3.5 text-amber-500"></i>
                                                        <span>Check Next Day</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>


                                <!-- Subtle stale availability notice -->
                                <template x-if="availabilityStale && availableSlots.length > 0">
                                    <div class="mb-3 flex items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs text-amber-800 dark:border-amber-800/50 dark:bg-amber-950/20 dark:text-amber-300">
                                        <span>Availability may be out of date. Please refresh before continuing.</span>
                                        <button type="button" @click="loadTimeSlots({ silent: true }); noteBookingInteraction();" class="font-bold underline">Refresh times</button>
                                    </div>
                                </template>

                                <!-- Slots -->

                                <template x-if="!loadingSlots && availableSlots.length > 0">

                                    <div class="space-y-5">

                                        @foreach ([
                                            ['key' => 'morning', 'slots' => 'morningSlots', 'label' => 'Morning', 'icon' => 'sunrise', 'iconClass' => 'text-amber-500'],
                                            ['key' => 'afternoon', 'slots' => 'afternoonSlots', 'label' => 'Afternoon', 'icon' => 'sun', 'iconClass' => 'text-orange-500'],
                                            ['key' => 'evening', 'slots' => 'eveningSlots', 'label' => 'Evening', 'icon' => 'sunset', 'iconClass' => 'text-indigo-500'],
                                        ] as $group)
                                            <template x-if="{{ $group['slots'] }}.length">
                                                <div>
                                                    <div class="mb-2 flex items-center gap-2">
                                                        <i data-lucide="{{ $group['icon'] }}" class="h-4 w-4 {{ $group['iconClass'] }}"></i>
                                                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400 dark:text-slate-500">{{ $group['label'] }}</span>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-2">
                                                        <template x-for="slot in {{ $group['slots'] }}" :key="slot.time">
                                                            <div class="contents">
                                                                <div class="relative flex flex-col">
                                                                    <button
                                                                        type="button"
                                                                        @click="selectSlot(slot)"
                                                                        :disabled="slot.room_available === false"
                                                                        class="flex h-16 w-full flex-col items-center justify-center rounded-xl border px-3 py-2 text-sm font-bold transition-colors"
                                                                        :class="selectedTime === slot.time
                                                                            ? 'border-zen-600 bg-zen-600 text-white shadow-md shadow-zen-100'
                                                                            : slot.room_available === false
                                                                                ? 'cursor-not-allowed border-slate-100 dark:border-slate-700/50 bg-slate-50 dark:bg-slate-900 text-slate-300'
                                                                                : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:border-zen-300 hover:bg-zen-50 dark:hover:bg-teal-900/20 hover:text-zen-700'"
                                                                    >
                                                                        <span x-text="formatTime(slot.time)"></span>
                                                                        <span class="text-[10px] font-normal" x-text="((slot.free_rooms || []).length + ' compatible room' + ((slot.free_rooms || []).length !== 1 ? 's' : '') + ' available')"></span>
                                                                    </button>
                                                                </div>
                                                                <div :data-slot-anchor="slot.time" x-show="selectedTime === slot.time" x-cloak class="col-span-2"></div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        @endforeach

                                        <!-- Single Shared Room & Preferred Therapist Card (moved directly below the selected slot) -->
                                        <div
                                            x-ref="preferenceCard"
                                            x-show="selectedTime"
                                            x-cloak
                                            class="my-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 p-4 shadow-inner space-y-3"
                                        >
                                            <!-- Room Preference (optional) -->
                                            <div @click.away="roomPreferenceOpen = false">
                                                <div class="flex items-center justify-between mb-2">
                                                    <p class="font-bold text-xs text-slate-500 uppercase tracking-wider">Room preference (optional)</p>
                                                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400" x-text="selectedSlotRooms.length + ' available'"></span>
                                                </div>

                                                <button
                                                    type="button"
                                                    @click="roomPreferenceOpen = !roomPreferenceOpen"
                                                    class="flex w-full items-center justify-between rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-left text-sm transition-colors hover:border-zen-300"
                                                >
                                                    <span class="truncate font-medium text-slate-700 dark:text-slate-200" x-text="selectedRoomSummary"></span>
                                                    <span class="ml-2 text-xs text-slate-400" x-text="roomPreferenceOpen ? '▲' : '▼'"></span>
                                                </button>

                                                <div x-show="roomPreferenceOpen" x-cloak class="mt-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 space-y-1 shadow-sm">
                                                    <div class="mx-1 mb-1 rounded-md bg-slate-50 dark:bg-slate-900/60 px-2.5 py-2 text-[11px] text-slate-600 dark:text-slate-300">
                                                        <p class="font-bold text-slate-700 dark:text-slate-200">Room Reservation</p>
                                                        <p class="mt-0.5 leading-relaxed">Your selected room is temporarily reserved while your booking is pending. This reservation ends if your booking expires or is cancelled.</p>
                                                    </div>
                                                    <label class="flex items-center gap-2 text-sm cursor-pointer px-2.5 py-1.5 rounded-md hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                                        <input type="radio" :value="null" name="room_pref_selected" x-model="selectedRoomId" class="text-zen-600 focus:ring-zen-500">
                                                        <span class="text-slate-600 dark:text-slate-300 font-medium">No preference</span>
                                                    </label>
                                                    <template x-for="r in selectedSlotRooms" :key="r.id">
                                                        <label class="flex items-center gap-2 text-sm cursor-pointer px-2.5 py-1.5 rounded-md hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                                            <input type="radio" :value="r.id" name="room_pref_selected" x-model="selectedRoomId" class="text-zen-600 focus:ring-zen-500">
                                                            <span x-text="r.name" class="text-slate-700 dark:text-slate-300 font-medium"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- Preferred Therapist (optional) -->
                                            <div class="pt-3 border-t border-slate-200 dark:border-slate-700" @click.away="preferredTherapistsOpen = false">
                                                <div class="flex items-center justify-between mb-2">
                                                    <p class="font-bold text-xs text-slate-500 uppercase tracking-wider">Preferred Therapist (optional)</p>
                                                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400" x-text="'Max ' + requiredTherapistCount"></span>
                                                </div>

                                                <button
                                                    type="button"
                                                    @click="preferredTherapistsOpen = !preferredTherapistsOpen"
                                                    class="flex w-full items-center justify-between rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-left text-sm transition-colors hover:border-zen-300"
                                                >
                                                    <span class="truncate font-medium text-slate-700 dark:text-slate-200" x-text="preferredTherapistSummary"></span>
                                                    <span class="ml-2 text-xs text-slate-400" x-text="preferredTherapistsOpen ? '▲' : '▼'"></span>
                                                </button>

                                                <div x-show="preferredTherapistsOpen" x-cloak class="mt-2 max-h-56 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 space-y-1 shadow-sm">
                                                    <div class="mx-1 mb-1 rounded-md bg-slate-50 dark:bg-slate-900/60 px-2.5 py-2 text-[11px] text-slate-600 dark:text-slate-300">
                                                        <p class="font-bold text-slate-700 dark:text-slate-200">Preferred Therapist Request</p>
                                                        <p class="mt-0.5 leading-relaxed">This therapist is currently shown as available for your selected schedule. Your selection is a preference request, not a confirmed assignment. The receptionist will finalize the therapist assignment when confirming your booking.</p>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        @click="clearPreferredTherapists()"
                                                        class="flex w-full items-center justify-between rounded-md px-2.5 py-1.5 text-xs font-semibold transition-colors"
                                                        :class="selectedPreferredStaffIds.length === 0 ? 'bg-zen-50 text-zen-700 dark:bg-teal-900/30 dark:text-teal-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'"
                                                    >
                                                        <span>No preference</span>
                                                        <span x-show="selectedPreferredStaffIds.length === 0" class="text-[10px]">Selected</span>
                                                    </button>

                                                    <div x-show="loadingPreferredTherapists" class="px-2.5 py-2 text-xs text-slate-400">Checking availability…</div>

                                                    <template x-for="t in preferredTherapists" :key="t.id">
                                                        <label
                                                            class="flex items-center justify-between gap-2 rounded-md px-2.5 py-1.5 text-sm transition-colors"
                                                            :class="t.status === 'available'
                                                                ? 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/50'
                                                                : 'cursor-not-allowed opacity-60'"
                                                        >
                                                            <span class="flex items-center gap-2 min-w-0">
                                                                <input
                                                                    type="checkbox"
                                                                    :value="t.id"
                                                                    :checked="selectedPreferredStaffIds.includes(t.id)"
                                                                    :disabled="t.status !== 'available' || (!selectedPreferredStaffIds.includes(t.id) && selectedPreferredStaffIds.length >= requiredTherapistCount)"
                                                                    @change="togglePreferredTherapist(t)"
                                                                    class="rounded border-slate-300 text-zen-600 focus:ring-zen-500"
                                                                >
                                                                <span class="truncate font-medium text-slate-700 dark:text-slate-200" x-text="t.first_name"></span>
                                                            </span>
                                                            <span
                                                                class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                                                :class="{
                                                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300': t.status === 'available',
                                                                    'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300': t.status === 'not_scheduled' || t.status === 'day_off',
                                                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300': t.status === 'on_leave',
                                                                    'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300': t.status === 'occupied'
                                                                }"
                                                                x-text="t.label"
                                                            ></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

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

                    <div class="overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-card">

                        <div class="border-b border-slate-100 dark:border-slate-700/50 p-5 sm:p-7">
                            <p class="text-xs font-bold uppercase tracking-wider text-zen-600">
                                Step 3
                            </p>

                            <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                                Tell us about yourself.
                            </h2>

                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 dark:text-slate-500">
                                We only need a few details so our receptionist can confirm your appointment.
                            </p>
                        </div>


                        <div class="p-5 sm:p-7">

                            <div class="space-y-6">

                                <template x-if="cartHasDuoService">
                                    <div class="rounded-2xl border border-rose-200 bg-rose-50/70 p-4 text-xs text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-200 space-y-1.5 shadow-2xs">
                                        <div class="flex items-center gap-2 font-bold text-rose-800 dark:text-rose-300">
                                            <i data-lucide="heart" class="h-4 w-4 text-rose-600"></i>
                                            <span>Duo / Couple Session: For 2 Guests</span>
                                        </div>
                                        <p class="text-rose-700 dark:text-rose-300/90 leading-relaxed">
                                            You are booking a couple treatment! This session accommodates <strong>2 guests</strong> together in a private couple room with <strong>2 dedicated therapists</strong>. The price covers both guests.
                                        </p>
                                    </div>
                                </template>

                                <template x-if="cartHasFourHandsService">
                                    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-4 text-xs text-indigo-900 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-200 space-y-1.5 shadow-2xs">
                                        <div class="flex items-center gap-2 font-bold text-indigo-800 dark:text-indigo-300">
                                            <i data-lucide="sparkles" class="h-4 w-4 text-indigo-600"></i>
                                            <span>Four-Hands Luxury Session: 1 Guest • 2 Therapists</span>
                                        </div>
                                        <p class="text-indigo-700 dark:text-indigo-300/90 leading-relaxed">
                                            This specialized treatment is dedicated to <strong>one guest</strong> attended simultaneously by <strong>two therapists</strong> for synchronized massage.
                                        </p>
                                    </div>
                                </template>

                                <!-- Name -->

                                <div>
                                    <label
                                        for="guest_first_name"
                                        class="mb-2 block text-sm font-bold text-slate-800 dark:text-slate-200"
                                    >
                                        Full name
                                    </label>

                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 dark:text-slate-500">
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
                                            class="block w-full rounded-xl border py-3.5 pl-11 pr-12 text-base text-slate-900 dark:text-slate-100 outline-none transition placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-4"
                                            :class="guestName.trim().length >= 2
                                                ? 'border-emerald-300 bg-emerald-50/30 dark:bg-emerald-900/20 focus:border-emerald-500 focus:ring-emerald-500/10'
                                                : guestName.trim().length > 0
                                                    ? 'border-red-300 bg-red-50/30 dark:bg-red-900/20 focus:border-red-500 focus:ring-red-500/10'
                                                    : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 focus:border-zen-500 focus:ring-zen-500/10'"
                                        >
                                        
                                        <div
                                            x-show="guestName.trim().length > 0"
                                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4"
                                        >
                                            <i
                                                x-show="guestName.trim().length >= 2"
                                                data-lucide="circle-check"
                                                class="h-5 w-5 text-emerald-500"
                                            ></i>

                                            <i
                                                x-show="guestName.trim().length > 0 && guestName.trim().length < 2"
                                                data-lucide="circle-x"
                                                class="h-5 w-5 text-red-400"
                                            ></i>
                                        </div>
                                    </div>

                                    <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">
                                        You may use your preferred name or alias.
                                    </p>
                                </div>


                                <!-- Phone -->

                                <div>
                                    <label
                                        for="phone"
                                        class="mb-2 block text-sm font-bold text-slate-800 dark:text-slate-200"
                                    >
                                        Mobile number
                                    </label>

                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 dark:text-slate-500">
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
                                            class="block w-full rounded-xl border py-3.5 pl-11 pr-12 text-base text-slate-900 dark:text-slate-100 outline-none transition placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-4"
                                            :class="phone.length === 11 && phoneValid
                                                ? 'border-emerald-300 bg-emerald-50/30 dark:bg-emerald-900/20 focus:border-emerald-500 focus:ring-emerald-500/10'
                                                : phone.length > 0
                                                    ? 'border-red-300 bg-red-50/30 dark:bg-red-900/20 focus:border-red-500 focus:ring-red-500/10'
                                                    : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 focus:border-zen-500 focus:ring-zen-500/10'"
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
                                            class="block text-sm font-bold text-slate-800 dark:text-slate-200"
                                        >
                                            Notes or special concerns
                                        </label>

                                        <span class="text-[11px] text-slate-400 dark:text-slate-500">
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
                                        class="block w-full resize-none rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3.5 text-base leading-6 text-slate-900 dark:text-slate-100 outline-none transition placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-zen-500 focus:ring-4 focus:ring-zen-500/10"
                                    ></textarea>

                                    <div class="mt-1.5 flex justify-end">
                                        <span class="text-[11px] text-slate-400 dark:text-slate-500">
                                            <span x-text="medicalNotes.length"></span>/1000
                                        </span>
                                    </div>
                                </div>


                                <!-- Privacy -->

                                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 p-4">
                                    <div class="flex gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white dark:bg-slate-800 text-zen-600 shadow-sm">
                                            <i data-lucide="shield-check" class="h-4 w-4"></i>
                                        </div>

                                        <div>
                                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                Your information is handled privately.
                                            </p>

                                            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400 dark:text-slate-500">
                                                Your contact details are used to process and confirm
                                                your appointment request with Spa Alexandria.
                                            </p>
                                        </div>
                                    </div>
                                </div>


                                <!-- Confirmation notice -->

                                <div class="rounded-2xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 p-4">
                                    <div class="flex gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white dark:bg-slate-800 text-amber-600 shadow-sm">
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

                    <div class="overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-card">

                        <div class="border-b border-slate-100 dark:border-slate-700/50 p-5 sm:p-7">
                            <p class="text-xs font-bold uppercase tracking-wider text-zen-600">
                                Final step
                            </p>

                            <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                                Review your appointment.
                            </h2>

                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 dark:text-slate-500">
                                Check everything before sending your booking request.
                            </p>
                        </div>


                        <div class="divide-y divide-slate-100">

                            <!-- Appointment -->

                            <div class="p-5 sm:p-7">
                                <div class="mb-4 flex items-center justify-between">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                            Appointment
                                        </p>

                                        <h3 class="mt-1 text-sm font-bold text-slate-900 dark:text-slate-100">
                                            Your selected schedule
                                        </h3>
                                    </div>

                                    <button
                                        type="button"
                                        @click="step = 2; $nextTick(() => refreshIcons())"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold text-zen-700 dark:text-teal-400 transition hover:bg-zen-50 dark:hover:bg-teal-900/20"
                                    >
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Edit
                                    </button>
                                </div>


                                <div class="grid gap-3 sm:grid-cols-2">

                                    <div class="rounded-2xl bg-slate-50 dark:bg-slate-900 p-4">
                                        <div class="flex items-center gap-2 text-slate-400 dark:text-slate-500">
                                            <i data-lucide="calendar-days" class="h-4 w-4"></i>
                                            <span class="text-[11px] font-bold uppercase tracking-wider">
                                                Date
                                            </span>
                                        </div>

                                        <p class="mt-2 text-sm font-bold text-slate-900 dark:text-slate-100">
                                            <span x-text="formatDateLong(appointmentDate)"></span>
                                        </p>
                                    </div>


                                    <div class="rounded-2xl bg-slate-50 dark:bg-slate-900 p-4">
                                        <div class="flex items-center gap-2 text-slate-400 dark:text-slate-500">
                                            <i data-lucide="clock-3" class="h-4 w-4"></i>
                                            <span class="text-[11px] font-bold uppercase tracking-wider">
                                                Time
                                            </span>
                                        </div>

                                        <p class="mt-2 text-sm font-bold text-slate-900 dark:text-slate-100">
                                            <span x-text="formatTime(selectedTime)"></span>
                                            <span class="font-medium text-slate-400 dark:text-slate-500">
                                                —
                                                <span x-text="formatTime(endTime)"></span>
                                            </span>
                                        </p>
                                    </div>

                                    <div class="rounded-2xl bg-slate-50 dark:bg-slate-900 p-4">
                                        <div class="flex items-center gap-2 text-slate-400 dark:text-slate-500">
                                            <i data-lucide="door-open" class="h-4 w-4"></i>
                                            <span class="text-[11px] font-bold uppercase tracking-wider">Room Preference</span>
                                        </div>
                                        <p class="mt-2 text-sm font-bold text-slate-900 dark:text-slate-100" x-text="selectedRoomSummary"></p>
                                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Your selected room will be temporarily reserved once your booking is submitted, subject to confirmation and expiration.</p>
                                    </div>

                                    <div class="rounded-2xl bg-slate-50 dark:bg-slate-900 p-4">
                                        <div class="flex items-center gap-2 text-slate-400 dark:text-slate-500">
                                            <i data-lucide="user-check" class="h-4 w-4"></i>
                                            <span class="text-[11px] font-bold uppercase tracking-wider">Therapist Preference</span>
                                        </div>
                                        <p class="mt-2 text-sm font-bold text-slate-900 dark:text-slate-100" x-text="preferredTherapistSummary"></p>
                                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Preference only; final assignment is made by the receptionist during confirmation.</p>
                                    </div>

                                </div>
                            </div>


                            <!-- Services -->

                            <div class="p-5 sm:p-7">

                                <div class="mb-4 flex items-center justify-between">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                            Services
                                        </p>

                                        <h3 class="mt-1 text-sm font-bold text-slate-900 dark:text-slate-100">
                                            Selected treatments
                                        </h3>
                                    </div>

                                    <button
                                        type="button"
                                        @click="step = 1; $nextTick(() => refreshIcons())"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold text-zen-700 dark:text-teal-400 transition hover:bg-zen-50 dark:hover:bg-teal-900/20"
                                    >
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Edit
                                    </button>
                                </div>


                                <div class="space-y-2">

                                    <template x-for="service in selectedServiceObjects" :key="service.id">
                                        <div class="rounded-xl border border-slate-100 dark:border-slate-700/50 bg-white dark:bg-slate-800 p-3.5">
                                            <div class="flex items-center justify-between gap-4">
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200 break-words">
                                                            <span x-text="service.name"></span>
                                                        </p>
                                                        <template x-if="service.is_package">
                                                            <span class="rounded bg-purple-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                                                Package
                                                            </span>
                                                        </template>
                                                    </div>

                                                    <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-slate-400 dark:text-slate-500">
                                                        <span x-text="service.duration_minutes + ' minutes'"></span>
                                                        <template x-if="service.participants_required > 1 || (service.name && service.name.toLowerCase().includes('couple'))">
                                                            <span class="inline-flex items-center gap-1 font-semibold text-rose-600 dark:text-rose-400">
                                                                • 👥 2 Guests (Covers Both)
                                                            </span>
                                                        </template>
                                                        <template x-if="(!service.participants_required || service.participants_required <= 1) && (!service.name || !service.name.toLowerCase().includes('couple')) && (service.therapists_required > 1)">
                                                            <span class="inline-flex items-center gap-1 font-semibold text-indigo-600 dark:text-indigo-400">
                                                                • ✨ 1 Guest • 2 Therapists
                                                            </span>
                                                        </template>
                                                        <template x-if="service.room_category_name">
                                                            <span class="inline-flex items-center gap-1 font-medium text-slate-500 dark:text-slate-400">
                                                                • <span x-text="service.room_category_name"></span>
                                                            </span>
                                                        </template>
                                                    </div>
                                                </div>

                                                <p class="shrink-0 text-sm font-extrabold text-slate-900 dark:text-slate-100">
                                                    ₱<span x-text="formatMoney(service.discount_price || service.price)"></span>
                                                </p>
                                            </div>

                                            <template x-if="service.is_package && service.included_services && service.included_services.length > 0">
                                                <div class="mt-2.5 flex flex-wrap items-center gap-1.5 border-t border-slate-100 pt-2 text-xs dark:border-slate-700/60">
                                                    <span class="text-[11px] font-semibold text-purple-700 dark:text-purple-300">Includes:</span>
                                                    <template x-for="(inc, idx) in service.included_services" :key="idx">
                                                        <span class="inline-flex items-center gap-1 rounded bg-slate-50 px-2 py-0.5 text-[11px] text-slate-600 dark:bg-slate-700/50 dark:text-slate-300">
                                                            <span class="h-1 w-1 rounded-full bg-purple-400"></span>
                                                            <span x-text="inc.name"></span>
                                                        </span>
                                                    </template>
                                                </div>
                                            </template>

                                            <template x-if="service.is_package && service.choice_groups && service.choice_groups.length > 0">
                                                <div class="mt-2 space-y-1 border-t border-slate-100 pt-2 text-xs dark:border-slate-700/60">
                                                    <template x-for="grp in service.choice_groups" :key="grp.id">
                                                        <div class="flex items-center gap-1.5 text-[11px]">
                                                            <span class="font-semibold text-zen-700 dark:text-teal-400" x-text="(grp.name || grp.label) + ':'"></span>
                                                            <span class="font-medium text-slate-800 dark:text-slate-200" x-text="getOptionName(grp, packageChoices[grp.id])"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                </div>

                            </div>


                            <!-- Customer -->

                            <div class="p-5 sm:p-7">

                                <div class="mb-4 flex items-center justify-between">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                            Customer
                                        </p>

                                        <h3 class="mt-1 text-sm font-bold text-slate-900 dark:text-slate-100">
                                            Your information
                                        </h3>
                                    </div>

                                    <button
                                        type="button"
                                        @click="step = 3; $nextTick(() => refreshIcons())"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold text-zen-700 dark:text-teal-400 transition hover:bg-zen-50 dark:hover:bg-teal-900/20"
                                    >
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        Edit
                                    </button>
                                </div>


                                <div class="grid gap-3 sm:grid-cols-2">

                                    <div class="rounded-xl border border-slate-100 dark:border-slate-700/50 p-3.5">
                                        <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                                            Name
                                        </p>

                                        <p class="mt-1 text-sm font-bold text-slate-800 dark:text-slate-200">
                                            <span x-text="guestName"></span>
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-slate-100 dark:border-slate-700/50 p-3.5">
                                        <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                                            Mobile number
                                        </p>

                                        <p class="mt-1 text-sm font-bold text-slate-800 dark:text-slate-200">
                                            <span x-text="phone"></span>
                                        </p>
                                    </div>

                                </div>


                                <template x-if="medicalNotes">
                                    <div class="mt-3 rounded-xl border border-slate-100 dark:border-slate-700/50 p-3.5">
                                        <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                                            Notes
                                        </p>

                                        <p
                                            class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700 dark:text-slate-300"
                                            x-text="medicalNotes"
                                        ></p>
                                    </div>
                                </template>

                            </div>


                            <!-- Total -->

                            <div class="bg-slate-950 p-5 text-white sm:p-7">

                                <div class="flex items-end justify-between gap-5">
                                    <div>
                                        <p class="text-xs font-semibold text-slate-400 dark:text-slate-500">
                                            Estimated total
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 dark:text-slate-500">
                                            Final charges are based on selected services.
                                        </p>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-3xl font-extrabold tracking-tight">
                                            ₱<span x-text="formatMoney(totalPrice)"></span>
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
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

                    <div class="overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-card">

                        <div class="border-b border-slate-100 dark:border-slate-700/50 p-5">
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-zen-50 dark:bg-teal-900/20 text-zen-600">
                                    <i data-lucide="receipt-text" class="h-4 w-4"></i>
                                </div>

                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                        Booking summary
                                    </p>

                                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">
                                        Your appointment
                                    </h3>
                                </div>
                            </div>
                        </div>


                        <div class="p-5">

                            <!-- Empty -->

                            <template x-if="selectedServices.length === 0">
                                <div class="py-5 text-center">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800/60 text-slate-400 dark:text-slate-500">
                                        <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                                    </div>

                                    <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                        No services selected
                                    </p>

                                    <p class="mt-1 text-xs leading-5 text-slate-400 dark:text-slate-500">
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
                                                    <p class="text-sm font-semibold leading-5 text-slate-800 dark:text-slate-200">
                                                        <span x-text="service.name"></span>
                                                        <span x-show="service.is_package" class="ml-1 text-[10px] font-bold text-purple-600 dark:text-purple-400">(Package)</span>
                                                    </p>

                                                    <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                                                        <span x-text="service.duration_minutes"></span> min
                                                    </p>

                                                    <template x-if="service.participants_required > 1 || (service.name && service.name.toLowerCase().includes('couple'))">
                                                        <p class="mt-0.5 text-[10px] font-bold text-rose-600 dark:text-rose-400">
                                                            👥 2 Guests (Covers Both)
                                                        </p>
                                                    </template>
                                                    <template x-if="(!service.participants_required || service.participants_required <= 1) && (!service.name || !service.name.toLowerCase().includes('couple')) && (service.therapists_required > 1)">
                                                        <p class="mt-0.5 text-[10px] font-bold text-indigo-600 dark:text-indigo-400">
                                                            ✨ 1 Guest • 2 Therapists
                                                        </p>
                                                    </template>

                                                    <template x-if="service.is_package && service.choice_groups && service.choice_groups.length > 0">
                                                        <div class="mt-1 space-y-0.5">
                                                            <template x-for="grp in service.choice_groups" :key="grp.id">
                                                                <p class="text-[10px] text-zen-700 dark:text-teal-400">
                                                                    <span x-text="(grp.name || grp.label) + ': '"></span>
                                                                    <span class="font-semibold" x-text="getOptionName(grp, packageChoices[grp.id])"></span>
                                                                </p>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>

                                                <p class="shrink-0 text-sm font-bold text-slate-900 dark:text-slate-100">
                                                    ₱<span x-text="formatMoney(service.discount_price || service.price)"></span>
                                                </p>
                                            </div>
                                        </template>

                                    </div>


                                    <div class="my-4 border-t border-slate-100 dark:border-slate-700/50"></div>


                                    <div class="space-y-3">

                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-500 dark:text-slate-400 dark:text-slate-500">
                                                Duration
                                            </span>

                                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                <span x-text="totalDuration"></span> min
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-500 dark:text-slate-400 dark:text-slate-500">
                                                Schedule
                                            </span>

                                            <span
                                                class="text-right text-xs font-bold text-slate-800 dark:text-slate-200"
                                                x-text="appointmentDate ? formatDateShort(appointmentDate) : 'Not selected'"
                                            ></span>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-500 dark:text-slate-400 dark:text-slate-500">
                                                Time
                                            </span>

                                            <span
                                                class="text-xs font-bold text-slate-800 dark:text-slate-200"
                                                x-text="selectedTime ? formatTime(selectedTime) : 'Not selected'"
                                            ></span>
                                        </div>

                                    </div>


                                    <div class="my-4 border-t border-slate-100 dark:border-slate-700/50"></div>


                                    <div class="flex items-end justify-between">
                                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                            Total
                                        </span>

                                        <span class="text-2xl font-extrabold tracking-tight text-zen-700 dark:text-teal-400">
                                            ₱<span x-text="formatMoney(totalPrice)"></span>
                                        </span>
                                    </div>

                                </div>
                            </template>

                        </div>

                    </div>


                    <!-- Booking policy -->

                    <div class="rounded-3xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-card">

                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-600">
                                <i data-lucide="info" class="h-4 w-4"></i>
                            </div>

                            <p class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                Before you book
                            </p>
                        </div>

                        <ul class="mt-4 space-y-3">

                            <li class="flex gap-2.5">
                                <i data-lucide="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"></i>
                                <span class="text-xs leading-5 text-slate-500 dark:text-slate-400 dark:text-slate-500">
                                    Online requests remain pending until confirmed.
                                </span>
                            </li>

                            <li class="flex gap-2.5">
                                <i data-lucide="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"></i>
                                <span class="text-xs leading-5 text-slate-500 dark:text-slate-400 dark:text-slate-500">
                                    A receptionist may contact you using the mobile number provided.
                                </span>
                            </li>

                            <li class="flex gap-2.5">
                                <i data-lucide="clock-alert" class="mt-0.5 h-4 w-4 shrink-0 text-amber-500"></i>
                                <span class="text-xs leading-5 text-slate-600 dark:text-slate-300">
                                    <span class="font-bold text-slate-800 dark:text-slate-100">15-Minute Late-Arrival Policy</span>
                                    <span class="mt-0.5 block">Please arrive on time for your scheduled appointment. Customers who have not checked in within 15 minutes after their scheduled appointment time may have their appointment cancelled in accordance with Spa Alexandria's late-arrival policy.</span>
                                </span>
                            </li>

                        </ul>

                    </div>

                </div>

            </aside>

        </div>


        <!-- ============================================================
             SERVICE DETAILS MODAL (MODULE)
        ============================================================= -->
        <div
            x-show="activeDetailService"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            role="dialog"
            aria-modal="true"
            @keydown.escape.window="closeServiceDetails()"
        >
            <!-- Backdrop -->
            <div
                x-show="activeDetailService"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity"
                @click="closeServiceDetails()"
            ></div>

            <!-- Modal Dialog Panel -->
            <div
                x-show="activeDetailService"
                x-transition:enter="ease-out duration-250"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                class="relative w-full max-w-lg overflow-hidden rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 z-10 max-h-[90vh] flex flex-col"
                @click.stop
            >
                <!-- Modal Header -->
                <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <template x-if="activeDetailService?.is_package">
                                <span class="rounded bg-purple-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">
                                    Package
                                </span>
                            </template>
                            <template x-if="activeDetailService?.participants_required > 1">
                                <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                    <i data-lucide="users" class="h-3 w-3"></i>
                                    <span x-text="activeDetailService?.participants_required + ' Guests'"></span>
                                </span>
                            </template>
                        </div>
                        <h3 class="mt-1.5 text-lg font-extrabold text-slate-900 dark:text-white" x-text="activeDetailService?.name"></h3>
                    </div>
                    <button
                        type="button"
                        @click="closeServiceDetails()"
                        class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 dark:text-slate-500 transition-colors focus:outline-none"
                        aria-label="Close modal"
                    >
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <!-- Modal Scrollable Body -->
                <div class="flex-1 overflow-y-auto py-4 space-y-4 pr-1">
                    <!-- Full Description / Experience -->
                    <template x-if="activeDetailService?.description">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Service Description & Amenities</h4>
                            <div class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-slate-700 dark:text-slate-300 font-normal" x-text="activeDetailService?.description"></div>
                        </div>
                    </template>

                    <!-- Package Inclusions Breakdown (when package) -->
                    <template x-if="activeDetailService?.is_package && activeDetailService?.included_services && activeDetailService?.included_services.length > 0">
                        <div class="rounded-2xl border border-purple-100 bg-purple-50/50 p-4 dark:border-purple-900/40 dark:bg-purple-950/20">
                            <p class="text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300">
                                Included Treatments (<span x-text="activeDetailService?.included_services.length"></span>):
                            </p>
                            <div class="mt-2.5 grid gap-2 sm:grid-cols-2">
                                <template x-for="(inc, idx) in activeDetailService?.included_services" :key="idx">
                                    <div class="flex items-center justify-between rounded-xl border border-purple-200/60 bg-white px-3 py-2 text-xs text-slate-800 shadow-2xs dark:border-purple-900/40 dark:bg-slate-800 dark:text-slate-200">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                                            <span class="font-semibold" x-text="inc.name"></span>
                                        </div>
                                        <span class="text-[11px] text-slate-400 dark:text-slate-500" x-show="inc.duration_minutes">
                                            <span x-text="inc.duration_minutes"></span>m
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Customizable Massage Choices Preview -->
                    <template x-if="activeDetailService?.is_package && activeDetailService?.choice_groups && activeDetailService?.choice_groups.length > 0">
                        <div class="rounded-2xl border border-zen-200/80 bg-zen-50/40 p-4 dark:border-teal-900/40 dark:bg-teal-950/20">
                            <p class="text-xs font-bold uppercase tracking-wider text-zen-800 dark:text-teal-300">
                                Customizable Choices:
                            </p>
                            <div class="mt-2 space-y-2">
                                <template x-for="grp in activeDetailService?.choice_groups" :key="grp.id">
                                    <div class="rounded-xl border border-zen-200/60 bg-white p-2.5 text-xs dark:border-teal-800/40 dark:bg-slate-800">
                                        <span class="font-bold text-slate-800 dark:text-slate-200" x-text="(grp.name || grp.label) + ':'"></span>
                                        <span class="ml-1 text-slate-600 dark:text-slate-300 font-medium" x-text="grp.options.map(o => o.name || o.service_name).join(' OR ')"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Specifications Card -->
                    <div class="grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-3.5 dark:bg-slate-800/50 text-xs sm:grid-cols-3">
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <i data-lucide="clock" class="h-4 w-4 text-zen-600 dark:text-teal-400"></i>
                            <div>
                                <div class="text-[10px] text-slate-400 uppercase font-bold">Duration</div>
                                <div class="font-bold text-slate-900 dark:text-white" x-text="activeDetailService?.duration_minutes + ' mins'"></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <i data-lucide="users" class="h-4 w-4 text-zen-600 dark:text-teal-400"></i>
                            <div>
                                <div class="text-[10px] text-slate-400 uppercase font-bold">Guests</div>
                                <div class="font-bold text-slate-900 dark:text-white" x-text="activeDetailService?.participants_required + ' Guest' + (activeDetailService?.participants_required > 1 ? 's' : '')"></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <i data-lucide="user-check" class="h-4 w-4 text-zen-600 dark:text-teal-400"></i>
                            <div>
                                <div class="text-[10px] text-slate-400 uppercase font-bold">Therapists</div>
                                <div class="font-bold text-slate-900 dark:text-white" x-text="activeDetailService?.therapists_required + ' Therapist' + (activeDetailService?.therapists_required > 1 ? 's' : '')"></div>
                            </div>
                        </div>
                        <template x-if="activeDetailService?.room_category_name">
                            <div class="col-span-2 sm:col-span-3 flex items-center gap-2 text-slate-600 dark:text-slate-300 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                                <i data-lucide="door-closed" class="h-4 w-4 text-zen-600 dark:text-teal-400"></i>
                                <div>
                                    <span class="text-[10px] text-slate-400 uppercase font-bold">Room Suite: </span>
                                    <span class="font-bold text-slate-900 dark:text-white" x-text="activeDetailService?.room_category_name"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Modal Footer Actions -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[10px] uppercase font-bold text-slate-400">Treatment Price</div>
                        <div class="text-lg font-extrabold text-zen-700 dark:text-teal-400">
                            ₱<span x-text="formatMoney(activeDetailService?.discount_price || activeDetailService?.price)"></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="closeServiceDetails()"
                            class="rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            Close
                        </button>
                        <button
                            type="button"
                            @click="toggleService(activeDetailService); closeServiceDetails()"
                            class="inline-flex items-center gap-1.5 rounded-xl px-5 py-2.5 text-xs font-bold text-white shadow-sm transition"
                            :class="isSelected(activeDetailService?.id)
                                ? 'bg-rose-600 hover:bg-rose-700'
                                : 'bg-zen-600 hover:bg-zen-700'"
                        >
                            <i :data-lucide="isSelected(activeDetailService?.id) ? 'x' : 'check'" class="h-4 w-4"></i>
                            <span x-text="isSelected(activeDetailService?.id) ? 'Remove Service' : 'Select Treatment'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main>


    <!-- ============================================================
         MOBILE STICKY ACTION BAR
    ============================================================= -->

    <div class="mobile-safe-bottom fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 dark:border-slate-700 bg-white/95 dark:bg-slate-900/95 shadow-[0_-10px_30px_rgba(15,23,42,0.08)] backdrop-blur-xl lg:hidden">

        <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6">

            <div class="flex items-center gap-3">

                <!-- Summary -->

                <div class="min-w-0 flex-1">
                    <template x-if="selectedServices.length === 0">
                        <div>
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                No services selected
                            </p>

                            <p class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">
                                Choose a service to continue.
                            </p>
                        </div>
                    </template>

                    <template x-if="selectedServices.length > 0">
                        <div>
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200 leading-tight">
                                <span x-text="selectedServices.length"></span>
                                <span x-text="selectedServices.length === 1 ? 'service' : 'services'"></span>

                                <template x-if="appointmentDate && selectedTime">
                                    <span>
                                        · <span x-text="formatTime(selectedTime)"></span>
                                    </span>
                                </template>
                            </p>

                            <p class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">
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
                    class="inline-flex h-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 text-sm font-bold text-slate-700 dark:text-slate-300 transition hover:bg-slate-50 dark:bg-slate-900"
                >
                    Back
                </button>


                <!-- Continue -->

                <button
                    type="button"
                    x-show="step < 4"
                    @click="nextStep()"
                    :disabled="!canContinue"
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-zen-600 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-zen-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400 dark:text-slate-500"
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

    <div class="fixed inset-x-0 bottom-0 z-30 hidden border-t border-slate-200 dark:border-slate-700 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl lg:block">

        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-8 py-4">

            <div class="min-w-0">
                <template x-if="selectedServices.length === 0">
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 dark:text-slate-500">
                        Start by selecting a service.
                    </p>
                </template>

                <template x-if="selectedServices.length > 0">
                    <div class="flex items-center gap-4">
                        <div>
                            <p class="text-xs text-slate-400 dark:text-slate-500">
                                Appointment total
                            </p>

                            <p class="text-lg font-extrabold text-slate-900 dark:text-slate-100">
                                ₱<span x-text="formatMoney(totalPrice)"></span>
                            </p>
                        </div>

                        <div class="h-8 w-px bg-slate-200"></div>

                        <div>
                            <p class="text-xs text-slate-400 dark:text-slate-500">
                                Duration
                            </p>

                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                <span x-text="totalDuration"></span> minutes
                            </p>
                        </div>

                        <template x-if="appointmentDate && selectedTime">
                            <div class="hidden items-center gap-2 md:flex">
                                <div class="h-8 w-px bg-slate-200"></div>

                                <div>
                                    <p class="text-xs text-slate-400 dark:text-slate-500">
                                        Schedule
                                    </p>

                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
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
                    class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-5 text-sm font-bold text-slate-700 dark:text-slate-300 transition hover:bg-slate-50 dark:bg-slate-900"
                >
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Back
                </button>


                <button
                    type="button"
                    x-show="step < 4"
                    @click="nextStep()"
                    :disabled="!canContinue"
                    class="inline-flex h-11 items-center gap-2 rounded-xl bg-zen-600 px-6 text-sm font-bold text-white shadow-sm transition hover:bg-zen-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400 dark:text-slate-500"
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

        <input type="hidden" name="room_id" x-bind:value="selectedRoomId">

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

        <template x-for="prefId in selectedPreferredStaffIds" :key="prefId">
            <input
                type="hidden"
                name="preferred_staff_ids[]"
                :value="prefId"
            >
        </template>

        <template x-for="(optId, grpId) in packageChoices" :key="grpId">
            <input
                type="hidden"
                :name="'package_choices[' + grpId + ']'"
                :value="optId"
            >
        </template>
    </form>


    <!-- ============================================================
         REBOOK DATA
    ============================================================= -->

    <script>
        window.bookingCategories = {!! $categoriesJson !!};

        window.bookingBusinessHours = @json($businessHours ?? []);
        window.bookingBusinessExceptions = @json($businessExceptions ?? []);

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

                businessHours: window.bookingBusinessHours || [],
                businessExceptions: window.bookingBusinessExceptions || [],

                categories: window.bookingCategories || [],

                activeCategory: null,

                selectedServices: [],

                packageChoices: {},

                activeDetailService: null,

                appointmentDate: '',

                selectedTime: '',
                selectedRoomId: null,
                


                slots: [],

                diagnostics: null,

                loadingSlots: false,
                roomPreferenceOpen: false,
                selectedPreferredStaffIds: [],
                preferredTherapists: [],
                preferredTherapistsOpen: false,
                loadingPreferredTherapists: false,
                preferredTherapistsSeq: 0,
                preferredTherapistsAbort: null,
                preferredTherapistsLastKey: '',

                availabilityTimer: null,
                availabilitySeq: 0,
                availabilityAbort: null,
                lastAvailabilitySuccess: 0,
                availabilityStale: false,
                availabilityFailures: 0,
                lastInteractionAt: Date.now(),

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

                    this.initIconObserver();

                    this.$watch('activeCategory', () => {
                        this.$nextTick(() => {
                            this.refreshIcons();
                            setTimeout(() => this.refreshIcons(), 30);
                            setTimeout(() => this.refreshIcons(), 100);
                        });
                    });

                    this.$nextTick(() => {
                        this.refreshIcons();
                        this.initializeCalendar();
                        setTimeout(() => this.refreshIcons(), 50);
                        setTimeout(() => this.refreshIcons(), 150);
                        setTimeout(() => this.refreshIcons(), 300);
                    });

                    this.initialized = true;

                    if (typeof document !== 'undefined') {
                        document.addEventListener('visibilitychange', () => {
                            if (document.hidden) {
                                this.stopAvailabilityPolling();
                            } else {
                                this.noteBookingInteraction();
                                this.loadTimeSlots({ silent: true });
                            }
                        });
                        window.addEventListener('beforeunload', () => this.stopAvailabilityPolling());
                    }
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

                openServiceDetails(service, event) {
                    if (event) {
                        event.stopPropagation();
                        event.preventDefault();
                    }
                    this.activeDetailService = service;
                    this.$nextTick(() => {
                        this.refreshIcons();
                        setTimeout(() => this.refreshIcons(), 30);
                    });
                },

                closeServiceDetails() {
                    this.activeDetailService = null;
                },


                toggleService(service) {

                    const id = Number(service.id);

                    if (this.isSelected(id)) {

                        this.selectedServices = this.selectedServices.filter(
                            serviceId => serviceId !== id
                        );

                        if (service.choice_groups) {
                            const newChoices = { ...this.packageChoices };
                            service.choice_groups.forEach(g => {
                                delete newChoices[g.id];
                            });
                            this.packageChoices = newChoices;
                        }

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
                    this.selectedRoomId = null;
                    this.selectedPreferredStaffIds = [];
                    this.preferredTherapists = [];
                    this.preferredTherapistsOpen = false;
                    this.preferredTherapistsLastKey = '';

                    if (this.appointmentDate) {
                        this.loadTimeSlots();
                    }
                    this.noteBookingInteraction();

                    this.$nextTick(() => this.refreshIcons());
                },


                selectChoiceOption(groupId, optionId) {
                    this.packageChoices = {
                        ...this.packageChoices,
                        [groupId]: Number(optionId)
                    };
                },


                arePackageChoicesValid() {
                    for (const service of this.selectedServiceObjects) {
                        if (service.is_package && Array.isArray(service.choice_groups)) {
                            for (const group of service.choice_groups) {
                                if (group.is_required && !this.packageChoices[group.id]) {
                                    return false;
                                }
                            }
                        }
                    }
                    return true;
                },


                getOptionName(group, optionId) {
                    if (!group || !group.options || !optionId) return 'Not selected';
                    const opt = group.options.find(o => Number(o.id) === Number(optionId) || Number(o.service_id) === Number(optionId));
                    return opt ? (opt.name || opt.service_name || 'Selected') : 'Not selected';
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

                get cartHasDuoService() {
                    return this.selectedServiceObjects.some(s => (s.participants_required && s.participants_required > 1) || (s.name && s.name.toLowerCase().includes('couple')));
                },

                get cartHasFourHandsService() {
                    return this.selectedServiceObjects.some(s => (!s.participants_required || s.participants_required <= 1) && (!s.name || !s.name.toLowerCase().includes('couple')) && (s.therapists_required && s.therapists_required > 1));
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

                                if (this.isDateClosed(clickedDate)) { this.showMessage("Date is unavailable", this.getClosedReason(clickedDate), "info"); return; }

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

                                if (this.isDateClosed(date)) { return ['booking-closed-day']; }

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


                selectDate(date) { if (this.isDateClosed(date)) { return; }

                    this.appointmentDate = date;

                    /*
                     * A date change invalidates the old time.
                     */

                    this.selectedTime = '';
                    this.selectedRoomId = null;
                    this.selectedPreferredStaffIds = [];
                    this.preferredTherapists = [];
                    this.preferredTherapistsOpen = false;
                    this.preferredTherapistsLastKey = '';

                    this.loadTimeSlots();
                    this.noteBookingInteraction();

                    this.$nextTick(() => {
                        this.refreshCalendarSelection();
                    });
                },


                nextDay() {
                    if (!this.appointmentDate) return;
                    const parts = this.appointmentDate.split('-');
                    const current = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                    current.setDate(current.getDate() + 1);
                    const y = current.getFullYear();
                    const m = String(current.getMonth() + 1).padStart(2, '0');
                    const d = String(current.getDate()).padStart(2, '0');
                    const nextDateStr = `${y}-${m}-${d}`;
                    this.selectDate(nextDateStr);
                    if (this.calendar) {
                        this.calendar.gotoDate(nextDateStr);
                    }
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

                async loadTimeSlots(opts = {}) {

                    const silent = opts.silent === true;

                    if (
                        !this.appointmentDate ||
                        this.selectedServices.length === 0
                    ) {
                        this.slots = [];
                        this.stopAvailabilityPolling();
                        return;
                    }

                    const requestedDate = this.appointmentDate;
                    const requestedServices = [...this.selectedServices].sort();
                    const seq = ++this.availabilitySeq;

                    if (this.availabilityAbort) {
                        this.availabilityAbort.abort();
                    }
                    this.availabilityAbort = new AbortController();

                    if (!silent) {
                        this.loadingSlots = true;
                    }

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
                                },
                                signal: this.availabilityAbort.signal
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

                        // Ignore responses that no longer match the current selection.
                        if (
                            seq !== this.availabilitySeq ||
                            requestedDate !== this.appointmentDate ||
                            JSON.stringify(requestedServices) !== JSON.stringify([...this.selectedServices].sort())
                        ) {
                            return;
                        }

                        this.lastAvailabilitySuccess = Date.now();
                        this.availabilityFailures = 0;
                        this.availabilityStale = false;

                        /*
                         * Controller currently returns either:
                         * - an array
                         * - { slots: [...] }
                         */

                        if (Array.isArray(data)) {
                            this.slots = data;
                            this.diagnostics = null;
                        } else if (Array.isArray(data.slots)) {
                            this.slots = data.slots;
                            this.diagnostics = data.diagnostics || null;
                        } else {
                            this.slots = [];
                            this.diagnostics = data?.diagnostics || null;
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
                                    slot.room_available === true;
                            }
                        );

                        if (!stillAvailable) {
                            this.selectedTime = '';
                            this.selectedRoomId = null;
                            this.roomPreferenceOpen = false;
                            this.selectedPreferredStaffIds = [];
                            this.preferredTherapists = [];
                            this.preferredTherapistsOpen = false;
                            this.preferredTherapistsLastKey = '';
                        } else if (this.selectedTime) {
                            if (this.selectedRoomId && !this.selectedSlotRooms.some(r => Number(r.id) === Number(this.selectedRoomId))) {
                                this.selectedRoomId = null;
                            }
                            if (silent && (this.preferredTherapistsOpen || this.selectedPreferredStaffIds.length > 0)) {
                                this.loadPreferredTherapists(true);
                            }
                        }

                    } catch (error) {

                        if (error && error.name === 'AbortError') {
                            return;
                        }

                        console.error(
                            'Booking slot error:',
                            error
                        );

                        // Preserve the last successful availability display.
                        this.availabilityFailures += 1;

                        if (
                            !this.lastAvailabilitySuccess ||
                            Date.now() - this.lastAvailabilitySuccess >= 45000
                        ) {
                            this.availabilityStale = true;
                        }

                        if (!silent && this.slots.length === 0) {
                            this.showMessage(
                                'Unable to load times',
                                error.message || 'We could not load the available appointment times. Please try again.',
                                'error'
                            );
                        }

                    } finally {

                        this.loadingSlots = false;

                        if (seq === this.availabilitySeq) {
                            this.scheduleAvailabilityPoll();
                        }

                        if (!silent) {
                            this.loadingSlots = false;
                            this.$nextTick(() => {
                                this.refreshIcons();
                                this.refreshCalendarSelection();
                            });
                        }
                    }
                },


                get canPollAvailability() {
                    return !!this.appointmentDate
                        && this.selectedServices.length > 0
                        && typeof document !== 'undefined'
                        && !document.hidden;
                },

                startAvailabilityPolling() {
                    if (!this.canPollAvailability) {
                        return;
                    }

                    this.scheduleAvailabilityPoll(15000);
                },

                scheduleAvailabilityPoll(delayMs = null) {
                    if (this.availabilityTimer) {
                        clearTimeout(this.availabilityTimer);
                        this.availabilityTimer = null;
                    }

                    if (!this.canPollAvailability) {
                        return;
                    }

                    // Idle visitors stop polling after four minutes.
                    if (Date.now() - this.lastInteractionAt > 240000) {
                        return;
                    }

                    const base = delayMs ?? 15000;
                    const jitter = Math.floor(Math.random() * 3000) - 1500;
                    const backoff = Math.min(this.availabilityFailures * 3000, 15000);

                    this.availabilityTimer = setTimeout(() => {
                        this.loadTimeSlots({ silent: true });
                    }, Math.max(5000, base + jitter + backoff));
                },

                stopAvailabilityPolling() {
                    if (this.availabilityTimer) {
                        clearTimeout(this.availabilityTimer);
                        this.availabilityTimer = null;
                    }
                    if (this.availabilityAbort) {
                        this.availabilityAbort.abort();
                        this.availabilityAbort = null;
                    }
                },

                noteBookingInteraction() {
                    this.lastInteractionAt = Date.now();
                    this.startAvailabilityPolling();
                },

                get availableSlots() {

                    return this.slots.filter(
                        slot => slot.room_available === true
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


                get selectedTimeGroup() {
                    if (!this.selectedTime) {
                        return null;
                    }

                    const h = this.hourOf(this.selectedTime);
                    if (h < 12) return 'morning';
                    if (h < 17) return 'afternoon';
                    return 'evening';
                },

                get selectedSlotRooms() {
                    if (!this.selectedTime) {
                        return [];
                    }

                    const slot = this.availableSlots.find(s => s.time === this.selectedTime);

                    return slot ? (slot.compatible_rooms || slot.free_rooms || []) : [];
                },


                get selectedRoomSummary() {
                    if (!this.selectedRoomId) {
                        return 'No preference';
                    }
                    const room = this.selectedSlotRooms.find(r => Number(r.id) === Number(this.selectedRoomId));
                    return room ? room.name : 'No preference';
                },

                get requiredTherapistCount() {
                    if (!this.selectedServiceObjects.length) {
                        return 1;
                    }
                    const maxReq = Math.max(...this.selectedServiceObjects.map(s => Number(s.therapists_required || 1)));
                    return Math.max(1, maxReq);
                },

                get preferredTherapistSummary() {
                    if (!this.selectedPreferredStaffIds.length) {
                        return 'No preference';
                    }
                    const names = this.selectedPreferredStaffIds
                        .map(id => this.preferredTherapists.find(t => Number(t.id) === Number(id))?.first_name)
                        .filter(Boolean);
                    return names.length ? names.join(', ') : 'No preference';
                },

                clearPreferredTherapists() {
                    this.selectedPreferredStaffIds = [];
                    this.preferredTherapistsOpen = false;
                },

                togglePreferredTherapist(therapist) {
                    if (!therapist || therapist.status !== 'available') {
                        return;
                    }
                    const id = Number(therapist.id);
                    const idx = this.selectedPreferredStaffIds.indexOf(id);
                    if (idx !== -1) {
                        this.selectedPreferredStaffIds.splice(idx, 1);
                        return;
                    }
                    if (this.selectedPreferredStaffIds.length >= this.requiredTherapistCount) {
                        return;
                    }
                    this.selectedPreferredStaffIds.push(id);
                },

                async loadPreferredTherapists(force = false) {
                    if (!this.appointmentDate || !this.selectedTime || !this.selectedServices.length) {
                        this.preferredTherapists = [];
                        this.selectedPreferredStaffIds = [];
                        this.preferredTherapistsLastKey = '';
                        return;
                    }

                    const key = `${this.appointmentDate}|${this.selectedTime}|${this.selectedServices.slice().sort().join(',')}`;
                    if (!force && key === this.preferredTherapistsLastKey && this.preferredTherapists.length) {
                        return;
                    }
                    this.preferredTherapistsLastKey = key;

                    if (this.preferredTherapistsAbort) {
                        this.preferredTherapistsAbort.abort();
                    }
                    this.preferredTherapistsAbort = new AbortController();
                    const seq = ++this.preferredTherapistsSeq;
                    this.loadingPreferredTherapists = true;

                    try {
                        const params = new URLSearchParams();
                        params.set('date', this.appointmentDate);
                        params.set('start_time', this.selectedTime);
                        this.selectedServices.forEach(id => params.append('services[]', id));

                        const res = await fetch(`{{ route('api.booking.preferred-therapists') }}?${params.toString()}`, {
                            headers: { 'Accept': 'application/json' },
                            signal: this.preferredTherapistsAbort.signal
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        if (seq !== this.preferredTherapistsSeq) return;

                        this.preferredTherapists = Array.isArray(data.therapists) ? data.therapists : [];
                        const maxAllowed = Math.max(1, Number(data.required_count || this.requiredTherapistCount));
                        const availableIds = new Set(
                            this.preferredTherapists.filter(t => t.status === 'available').map(t => Number(t.id))
                        );
                        this.selectedPreferredStaffIds = this.selectedPreferredStaffIds
                            .filter(id => availableIds.has(Number(id)))
                            .slice(0, maxAllowed);
                    } catch (e) {
                        if (e.name !== 'AbortError') {
                            this.preferredTherapists = [];
                        }
                    } finally {
                        if (seq === this.preferredTherapistsSeq) {
                            this.loadingPreferredTherapists = false;
                        }
                    }
                },

                selectSlot(slot) {

                    if (!slot || slot.room_available === false) {
                        return;
                    }

                    if (this.selectedTime === slot.time) {
                        this.selectedTime = '';
                        this.selectedRoomId = null;
                        this.roomPreferenceOpen = false;
                        this.selectedPreferredStaffIds = [];
                        this.preferredTherapists = [];
                        this.preferredTherapistsOpen = false;
                        this.preferredTherapistsLastKey = '';
                        this.preferredTherapistsSeq++;
                        if (this.preferredTherapistsAbort) {
                            this.preferredTherapistsAbort.abort();
                            this.preferredTherapistsAbort = null;
                        }
                        this.loadingPreferredTherapists = false;
                        return;
                    }

                    this.selectedTime = slot.time;
                    this.selectedRoomId = null;
                    this.roomPreferenceOpen = false;
                    this.selectedPreferredStaffIds = [];
                    this.preferredTherapistsOpen = false;
                    this.$nextTick(() => {
                        const anchor = document.querySelector(`[data-slot-anchor="${slot.time}"]`);
                        if (anchor && this.$refs.preferenceCard && anchor !== this.$refs.preferenceCard.parentElement) {
                            anchor.appendChild(this.$refs.preferenceCard);
                        }
                    });
                    this.loadPreferredTherapists(true);
                },


                /* -------------------------------------------------
                   Step navigation
                ------------------------------------------------- */

                canEnterStep(targetStep) {

                    if (targetStep <= 1) {
                        return true;
                    }

                    if (targetStep === 2) {
                        return this.selectedServices.length > 0 && this.arePackageChoicesValid();
                    }

                    if (targetStep === 3) {
                        return (
                            this.selectedServices.length > 0 &&
                            this.arePackageChoicesValid() &&
                            this.appointmentDate &&
                            this.selectedTime
                        );
                    }

                    if (targetStep === 4) {
                        return (
                            this.selectedServices.length > 0 &&
                            this.arePackageChoicesValid() &&
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
                        return this.selectedServices.length > 0 && this.arePackageChoicesValid();
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

                    if (targetStep === 2 && (!this.selectedServices.length || !this.arePackageChoicesValid())) {
                        return;
                    }

                    if (
                        targetStep >= 3 &&
                        !(
                            this.selectedServices.length &&
                            this.arePackageChoicesValid() &&
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

                        if (!this.selectedServices.length) {
                            this.showMessage(
                                'Choose a service',
                                'Please select at least one service before continuing.',
                                'info'
                            );
                            return;
                        }

                        if (!this.arePackageChoicesValid()) {
                            this.showMessage(
                                'Select Package Option',
                                'Please select your preferred treatment option for the package before continuing.',
                                'info'
                            );
                            return;
                        }

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
                                <div class="rounded-xl bg-slate-50 dark:bg-slate-900 p-4 mb-3">
                                    <p class="text-xs font-semibold text-slate-400 dark:text-slate-500">
                                        Schedule
                                    </p>
                                    <p class="mt-1 text-sm font-bold text-slate-900 dark:text-slate-100">
                                        ${this.escapeHtml(this.formatDateLong(this.appointmentDate))}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400 dark:text-slate-500">
                                        ${this.escapeHtml(this.formatTime(this.selectedTime))}
                                        – 
                                        ${this.escapeHtml(this.formatTime(this.endTime))}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-zen-50 dark:bg-teal-900/20 p-4">
                                    <p class="text-xs font-semibold text-zen-700 dark:text-teal-400">
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


                isDateClosed(dateString) {
                    if (!dateString) return false;
                    const exc = (this.businessExceptions || []).find(e => {
                        const eDate = typeof e.date === 'string' ? e.date.substring(0, 10) : '';
                        return eDate === dateString;
                    });
                    if (exc) {
                        return !!exc.is_closed;
                    }

                    const parts = dateString.split('-').map(Number);
                    const date = new Date(parts[0], parts[1] - 1, parts[2]);
                    const dayOfWeek = date.getDay();

                    const bh = (this.businessHours || []).find(h => Number(h.day_of_week) === dayOfWeek);
                    if (bh) {
                        return !!bh.is_closed;
                    }

                    return false;
                },

                getClosedReason(dateString) {
                    if (!dateString) return 'The spa is closed on this date.';
                    const exc = (this.businessExceptions || []).find(e => {
                        const eDate = typeof e.date === 'string' ? e.date.substring(0, 10) : '';
                        return eDate === dateString;
                    });
                    if (exc && exc.is_closed) {
                        return exc.notice_message || exc.title || 'The spa is closed for a special event/holiday.';
                    }

                    const parts = dateString.split('-').map(Number);
                    const date = new Date(parts[0], parts[1] - 1, parts[2]);
                    const dayOfWeek = date.getDay();
                    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                    const bh = (this.businessHours || []).find(h => Number(h.day_of_week) === dayOfWeek);
                    if (bh && bh.is_closed) {
                        return 'Spa Alexandria is closed on ' + days[dayOfWeek] + 's.';
                    }

                    return 'The spa is closed on this date.';
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

                initIconObserver() {
                    if (typeof MutationObserver === 'undefined') return;
                    let debounceTimer = null;
                    const observer = new MutationObserver(() => {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => this.refreshIcons(), 16);
                    });
                    observer.observe(document.body, { childList: true, subtree: true });
                },

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

        #booking-calendar .booking-closed-day {
            background: #fafafa !important;
        }

        #booking-calendar .booking-closed-day .fc-daygrid-day-number {
            color: #cbd5e1 !important;
        }
    </style>

    <!-- Dark Mode Script -->
    <script>
        function toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
        }
    </script>

</body>
</html>
