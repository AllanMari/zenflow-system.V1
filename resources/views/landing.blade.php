<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $hero->title ?? 'Spa Alexandria' }}</title>

    {{-- Dark mode: apply before render to avoid a flash --}}
    <script>
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.documentElement.classList.add('dark');
        }
    </script>

    {{-- Tailwind (pinned) --}}
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                            950: '#042f2e',
                        }
                    }
                }
            }
        }
    </script>

    {{-- Swiper (service carousels), pinned --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11.1.14/swiper-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/swiper@11.1.14/swiper-bundle.min.js"></script>

    {{-- MapLibre GL (map; OpenFreeMap styles, no API key), pinned --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/maplibre-gl@4.7.1/dist/maplibre-gl.css">
    <script src="https://cdn.jsdelivr.net/npm/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>

    {{-- Lucide (icons), GSAP + ScrollTrigger (animation), Lenis (smooth scroll), all pinned --}}
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.469.0/dist/umd/lucide.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.20/dist/lenis.min.js"></script>

    <style>
        html { scroll-behavior: smooth; }
        body { font-family: Inter, ui-sans-serif, system-ui, sans-serif; }

        /* Hero photo follows the theme: it dims slightly in dark mode */
        .hero-img { transition: filter .7s ease; animation: kenburns 26s ease-in-out infinite alternate; }
        @keyframes kenburns {
            from { transform: scale(1); }
            to   { transform: scale(1.07); }
        }

        /* Map: kept below the sticky header, pin pulses, attribution follows dark mode */
        .map-shell { position: relative; z-index: 0; isolation: isolate; }
        .map-pin { position: relative; display: block; width: 18px; height: 18px; border-radius: 9999px; background: #0f766e; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(4, 47, 46, .45); }
        .map-label { position: absolute; left: 50%; top: calc(100% + 12px); transform: translateX(-50%); white-space: nowrap; padding: 4px 10px; border-radius: 9999px; background: #fff; color: #134e4a; font-size: 12px; font-weight: 600; line-height: 1.25; box-shadow: 0 2px 10px rgba(4, 47, 46, .25); pointer-events: none; }
        .dark .map-label { background: #0f172a; color: #f1f5f9; box-shadow: 0 2px 10px rgba(0, 0, 0, .5); }
        .map-pin::after { content: ""; position: absolute; inset: -3px; border-radius: 9999px; border: 2px solid #14b8a6; animation: pinPulse 2.4s ease-out infinite; }
        @keyframes pinPulse {
            0%   { transform: scale(1); opacity: .9; }
            100% { transform: scale(3.2); opacity: 0; }
        }

        .maplibregl-map { font-family: inherit; }
        .dark .maplibregl-ctrl-group { background: #0f172a; }
        .dark .maplibregl-ctrl-group button + button { border-top-color: #1e293b; }
        .dark .maplibregl-ctrl-group button .maplibregl-ctrl-icon { filter: invert(1); }
        .dark .maplibregl-ctrl-attrib { background: rgba(2, 6, 23, .7); color: #94a3b8; }
        .dark .maplibregl-ctrl-attrib a { color: #5eead4; }

        /* Service carousel dots */
        .service-dots.swiper-pagination {
            position: static !important; width: auto; transform: none;
            --swiper-pagination-color: #0f766e;
            --swiper-pagination-bullet-inactive-color: #94a3b8;
            --swiper-pagination-bullet-inactive-opacity: .55;
            --swiper-pagination-bullet-size: 9px;
            --swiper-pagination-bullet-horizontal-gap: 5px;
        }
        .dark .service-dots.swiper-pagination {
            --swiper-pagination-color: #2dd4bf;
            --swiper-pagination-bullet-inactive-color: #64748b;
        }
        .service-dots .swiper-pagination-bullet { transition: transform .3s ease, background-color .3s ease; }
        .service-dots:not(.swiper-pagination-bullets-dynamic) .swiper-pagination-bullet-active { transform: scale(1.3); }

        /* Lenis smooth scroll */
        html.lenis, html.lenis body { height: auto; }
        .lenis.lenis-smooth { scroll-behavior: auto !important; }
        .dark .hero-img { filter: brightness(.74) saturate(.88); }

        /* Hero ambience (used when there is no photo) */
        .ripple { animation: ripple 9s ease-in-out infinite; }
        @keyframes ripple {
            0%   { transform: scale(.82); opacity: 0; }
            25%  { opacity: 1; }
            100% { transform: scale(1.12); opacity: 0; }
        }
        .blob { animation: drift 18s ease-in-out infinite alternate; }
        @keyframes drift {
            from { transform: translate3d(0, 0, 0) scale(1); }
            to   { transform: translate3d(40px, -30px, 0) scale(1.12); }
        }
        .grain {
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='160' height='160'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='2' stitchTiles='stitch'/></filter><rect width='100%' height='100%' filter='url(%23n)' opacity='.5'/></svg>");
            mix-blend-mode: overlay;
            opacity: .22;
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .ripple, .blob, .map-pin::after { animation: none; }
            .ripple { opacity: .6; }
            .hero-img { transition: none; animation: none; }
        }
    </style>
</head>


<body class="min-h-screen overflow-x-hidden bg-white text-slate-900 antialiased transition-colors duration-500 dark:bg-slate-950 dark:text-slate-100">

@php

    $resolveImage = function ($path) {

        if (!$path) {
            return null;
        }

        $path = trim($path);

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $cleanPath = ltrim(preg_replace('#^/?(storage|uploads)/#i', '', $path), '/');

        if (file_exists(public_path('uploads/' . $cleanPath))) {
            return asset('uploads/' . $cleanPath);
        }

        if (file_exists(public_path('storage/' . $cleanPath))) {
            return asset('storage/' . $cleanPath);
        }

        return null;
    };

    $heroImage = $resolveImage($hero->image ?? null);

    $serviceMeta = function ($service) use ($resolveImage) {

        $hasDiscount = $service->discount_price &&
            $service->price &&
            $service->discount_price < $service->price;

        return [
            'image'        => $resolveImage($service->image ?? null),
            'hasDiscount'  => $hasDiscount,
            'displayPrice' => $hasDiscount ? $service->discount_price : $service->price,
            'description'  => !empty($service->landing_description)
                ? $service->landing_description
                : ($service->description ?? null),
        ];
    };

    $visibleCategories = isset($categories)
        ? $categories->filter(fn ($category) => (bool) $category->show_on_landing)
        : collect();

    $dashboardRoute = 'landing';

    if (auth()->check()) {

        $role = auth()->user()->roles->first()->name ?? 'customer';

        $dashboardRoute = match ($role) {
            'admin'        => 'admin-dashboard',
            'receptionist' => 'receptionist.dashboard',
            'staff'        => 'staff.dashboard',
            'customer'     => 'customer-dashboard',
            default        => 'landing',
        };
    }

@endphp



{{-- =========================================================
     HEADER
========================================================== --}}
<header id="siteHeader" class="sticky top-0 z-50 border-b border-slate-200/70 bg-white/80 backdrop-blur-xl transition-colors duration-500 dark:border-slate-800 dark:bg-slate-950/80">

    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">

        {{-- Brand --}}
        <a href="{{ route('landing') }}" class="flex items-center gap-3">
            @if(file_exists(public_path('uploads/branding/spa-alexandria-logo.png')))
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white p-1 shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                    <img src="{{ asset('uploads/branding/spa-alexandria-logo.png') }}" alt="Spa Alexandria" class="h-8 w-8 object-contain">
                </span>
            @else
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-700 text-white dark:bg-brand-600">
                    <i data-lucide="sparkles" class="h-5 w-5"></i>
                </span>
            @endif
            <span class="leading-tight">
                <span class="block text-sm font-semibold text-slate-900 dark:text-white">Spa Alexandria</span>
                <span class="block text-xs text-slate-500 dark:text-slate-400">Wellness &amp; Relaxation</span>
            </span>
        </a>

        {{-- Dark mode + account links --}}
        <div class="flex items-center gap-2 sm:gap-3">

            <button
                type="button"
                onclick="toggleDarkMode()"
                class="flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-brand-400"
                aria-label="Toggle dark mode"
            >
                <i data-lucide="sun" class="hidden h-[18px] w-[18px] dark:block"></i>
                <i data-lucide="moon" class="block h-[18px] w-[18px] dark:hidden"></i>
            </button>

            @auth

                <a href="{{ route($dashboardRoute) }}" class="rounded-full bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/30 dark:bg-brand-600 dark:hover:bg-brand-500">
                    Dashboard
                </a>

            @else

                <a href="{{ route('login') }}" class="rounded-full px-4 py-2 text-sm font-medium text-slate-600 transition hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-slate-300 dark:hover:text-brand-400">
                    Login
                </a>

                <a href="{{ route('customer.register') }}" class="rounded-full bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/30 dark:bg-brand-600 dark:hover:bg-brand-500">
                    Register
                </a>

            @endauth

        </div>

    </nav>

</header>



{{-- =========================================================
     HERO
========================================================== --}}
<section class="px-4 pt-4 sm:px-6 lg:px-8">

    <div data-hero-frame class="relative mx-auto min-h-[640px] max-w-7xl overflow-hidden rounded-[2rem] bg-brand-900">

        @if($heroImage)

            <div data-parallax class="absolute inset-x-0 -top-[8%] h-[116%]">
                <img
                    src="{{ $heroImage }}"
                    alt="{{ $hero->title ?? 'Spa Alexandria' }}"
                    class="hero-img absolute inset-0 h-full w-full object-cover"
                >
            </div>

            {{-- Two tint layers that cross-fade when dark mode is toggled --}}
            <div class="absolute inset-0 bg-brand-900/30 mix-blend-multiply"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-brand-950/80 via-brand-950/45 to-brand-950/5 transition-opacity duration-700 dark:opacity-0"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-brand-950/65 to-slate-950/25 opacity-0 transition-opacity duration-700 dark:opacity-100"></div>

        @else

            {{-- Fallback: layered teal gradient, drifting light, water ripples, grain --}}
            <div class="absolute inset-0 bg-gradient-to-br from-brand-600 via-brand-800 to-brand-950"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-brand-900 via-brand-950 to-slate-950 opacity-0 transition-opacity duration-700 dark:opacity-100"></div>

            <div class="blob absolute -left-24 -top-24 h-[28rem] w-[28rem] rounded-full bg-brand-400/30 blur-3xl transition-opacity duration-700 dark:opacity-40"></div>
            <div class="blob absolute -bottom-32 left-1/3 h-[26rem] w-[26rem] rounded-full bg-brand-300/20 blur-3xl transition-opacity duration-700 dark:opacity-30" style="animation-delay:-9s"></div>

            <div class="pointer-events-none absolute -bottom-72 -right-72 h-[44rem] w-[44rem]" aria-hidden="true">
                <div class="ripple absolute inset-[30%] rounded-full border border-white/25"></div>
                <div class="ripple absolute inset-[20%] rounded-full border border-white/20" style="animation-delay:-1.8s"></div>
                <div class="ripple absolute inset-[10%] rounded-full border border-white/15" style="animation-delay:-3.6s"></div>
                <div class="ripple absolute inset-0 rounded-full border border-white/10" style="animation-delay:-5.4s"></div>
            </div>

            <div class="grain absolute inset-0"></div>

        @endif


        <div class="relative flex min-h-[640px] items-center px-6 pb-28 pt-16 sm:px-10 lg:px-16">

            <div class="max-w-2xl">

                @if(!empty($activeNotice))
                    <div data-hero class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-300/40 bg-amber-500/20 p-4 text-amber-100 shadow-lg backdrop-blur-md">
                        <span class="mt-0.5 shrink-0 rounded-xl bg-amber-500/30 p-2 text-amber-200">
                            <i data-lucide="alert-circle" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <h4 class="flex flex-wrap items-center gap-2 text-sm font-semibold text-white">
                                <span>{{ $activeNotice['title'] }}</span>
                                <span class="rounded-full bg-amber-400/30 px-2 py-0.5 text-[10px] font-semibold text-amber-200">
                                    {{ !empty($activeNotice['is_closed']) ? 'Closed Today' : 'Special Notice Today' }}
                                </span>
                            </h4>
                            <p class="mt-1 text-xs leading-relaxed text-amber-100/90">
                                {{ $activeNotice['message'] ?? 'We have special operating hours or closure today.' }}
                            </p>
                        </div>
                    </div>
                @endif

                @if(!empty($upcomingNotices) && $upcomingNotices->isNotEmpty())
                    <div data-hero class="mb-6 space-y-3 text-left">
                        <div class="sr-only">Upcoming Schedule Notices</div>
                        @foreach($upcomingNotices as $notice)
                            <div class="rounded-2xl border border-white/20 bg-slate-900/70 backdrop-blur-md p-4 sm:p-5 shadow-xl text-white transition-colors duration-200 ease-out">
                                @php
                                    $isNoticeClosed = !empty($notice['is_closed']) || ($notice['type'] ?? '') === 'holiday';
                                    if ($isNoticeClosed) {
                                        $iconBg = 'bg-teal-500/20 text-teal-300 border border-teal-400/30';
                                        $iconName = 'calendar-x';
                                        $pillClass = 'bg-teal-500/25 text-teal-200 border-teal-400/40';
                                        $statusPill = 'CLOSED';
                                    } elseif (($notice['type'] ?? '') === 'custom_hours') {
                                        $iconBg = 'bg-cyan-500/20 text-cyan-300 border border-cyan-400/30';
                                        $iconName = 'clock';
                                        $pillClass = 'bg-cyan-500/25 text-cyan-200 border-cyan-400/40';
                                        $statusPill = 'ADJUSTED HOURS';
                                    } else {
                                        $iconBg = 'bg-purple-500/20 text-purple-300 border border-purple-400/30';
                                        $iconName = 'sparkles';
                                        $pillClass = 'bg-purple-500/25 text-purple-200 border-purple-400/40';
                                        $statusPill = 'EVENT';
                                    }
                                @endphp

                                <!-- Top Row: Icon + Eyebrow Label + Right Status Pill -->
                                <div class="flex items-center justify-between gap-3 mb-2.5">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-lg {{ $iconBg }} flex items-center justify-center shrink-0 shadow-2xs">
                                            <i data-lucide="{{ $iconName }}" class="w-4 h-4"></i>
                                        </div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-teal-200/90 truncate">
                                            SPA ALEXANDRIA · BUSINESS NOTICE
                                        </span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border shrink-0 {{ $pillClass }}">
                                        {{ $statusPill }}
                                    </span>
                                </div>

                                <!-- Main Content: Notice Title -->
                                <h4 class="text-sm sm:text-base font-bold text-white leading-snug">
                                    {{ $notice['title'] }}
                                </h4>

                                <!-- Customer-Facing Notice Message -->
                                <p class="text-xs text-white/85 mt-1 leading-relaxed">
                                    @if(!empty($notice['notice_message']))
                                        {{ $notice['notice_message'] }}
                                    @elseif($isNoticeClosed)
                                        Spa Alexandria will be closed all day for {{ $notice['title'] }}. Online and counter bookings are unavailable for this date.
                                    @elseif(!empty($notice['formatted_hours']))
                                        Please note our adjusted operating schedule for this date: {{ $notice['formatted_hours'] }}.
                                    @else
                                        Special announcement for {{ $notice['title'] }}.
                                    @endif
                                </p>

                                <!-- Compact Date Line + Hours below message -->
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2.5 pt-2.5 border-t border-white/15 text-xs text-white/75">
                                    <div class="flex items-center gap-1.5 font-medium text-white">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-white/60"></i>
                                        <span>{{ $notice['day_name'] }}, {{ $notice['formatted_date'] }}</span>
                                    </div>

                                    @if(!$isNoticeClosed && !empty($notice['formatted_hours']))
                                        <div class="flex items-center gap-1.5 font-semibold text-cyan-200 bg-cyan-900/60 px-2 py-0.5 rounded-md border border-cyan-700/50">
                                            <i data-lucide="clock" class="w-3 h-3 text-cyan-300"></i>
                                            <span>Operating Hours: {{ $notice['formatted_hours'] }}</span>
                                        </div>
                                    @elseif($isNoticeClosed)
                                        <span class="font-semibold text-teal-200 text-[11px]">
                                            Closed All Day
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($hero->subtitle))
                    <p data-hero class="mb-4 text-sm font-medium text-brand-200">
                        {{ $hero->subtitle }}
                    </p>
                @endif

                <h1 data-hero class="max-w-2xl text-5xl font-extrabold leading-[1.05] tracking-tight text-white sm:text-6xl lg:text-7xl">
                    {{ $hero->title ?? 'Relax, restore, and feel your best.' }}
                </h1>

                <p data-hero class="mt-6 max-w-xl text-base leading-7 text-white/80 sm:text-lg">
                    Choose your services, find an available date and time,
                    and book your appointment as a guest or registered customer.
                </p>

                <div data-hero class="mt-9 flex flex-col gap-3 sm:flex-row">

                    <a href="{{ route('booking.wizard') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-white px-6 text-sm font-semibold text-brand-900 shadow-sm transition hover:bg-brand-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40">
                        Book an appointment
                        <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </a>

                    <a href="#services" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/10 px-6 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40">
                        Highlighted services
                        <i data-lucide="chevron-down" class="h-4 w-4"></i>
                    </a>

                </div>

            </div>

        </div>

    </div>

</section>



{{-- =========================================================
     BOOKING OPTIONS (overlaps the bottom edge of the hero)
========================================================== --}}
<section class="relative z-10 -mt-12 px-4 sm:px-6 lg:px-8">

    <div data-reveal class="mx-auto max-w-7xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-brand-950/10 transition-colors duration-500 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/40">

        <div class="grid divide-y divide-slate-200 dark:divide-slate-800 sm:grid-cols-3 sm:divide-x sm:divide-y-0">

            {{-- Guest booking --}}
            <div class="flex items-center gap-4 px-6 py-5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                    <i data-lucide="user-round-check" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Book as a guest</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">No account required</p>
                </div>
            </div>

            {{-- Date & time --}}
            <div class="flex items-center gap-4 px-6 py-5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                    <i data-lucide="calendar-days" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Choose your date &amp; time</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">See available appointments</p>
                </div>
            </div>

            {{-- Multiple services --}}
            <div class="flex items-center gap-4 px-6 py-5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                    <i data-lucide="layers-3" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Select multiple services</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Build your appointment your way</p>
                </div>
            </div>

        </div>

    </div>

</section>



{{-- =========================================================
     SERVICES
========================================================== --}}
<section id="services" class="scroll-mt-20 pb-20 pt-20 sm:pb-24 sm:pt-24">

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div data-reveal class="mb-10 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">

            <h2 class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-5xl">
                A selection of our services
            </h2>

            <p class="max-w-md text-sm leading-6 text-slate-500 dark:text-slate-400">
                Explore a selection of services featured on our landing page.
            </p>

        </div>


        @php
            $landingCategories = $visibleCategories
                ->map(function ($category) {
                    return [
                        'category' => $category,
                        'services' => $category->services
                            ->filter(fn ($service) => (bool) $service->show_on_landing)
                            ->values(),
                    ];
                })
                ->filter(fn ($row) => $row['services']->isNotEmpty())
                ->values();
        @endphp


        @if($landingCategories->isEmpty())

            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center transition-colors duration-500 dark:border-slate-700 dark:bg-slate-900">

                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                    <i data-lucide="package-open" class="h-6 w-6"></i>
                </span>

                <h3 class="mt-4 text-lg font-semibold text-slate-900 dark:text-white">
                    No highlighted services
                </h3>

                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Services selected for the landing page will appear here.
                </p>

            </div>

        @else

            <div data-reveal>

                {{-- Category tabs --}}
                @if($landingCategories->count() > 1)
                    <div
                        data-tablist
                        role="tablist"
                        aria-label="Service categories"
                        class="relative -mx-4 mb-8 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    >
                        @foreach($landingCategories as $row)
                            <button
                                type="button"
                                role="tab"
                                id="services-tab-{{ $loop->index }}"
                                data-tab="{{ $loop->index }}"
                                aria-controls="services-panel-{{ $loop->index }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                tabindex="{{ $loop->first ? '0' : '-1' }}"
                                class="inline-flex shrink-0 items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-brand-300 hover:text-brand-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/20 aria-selected:border-brand-700 aria-selected:bg-brand-700 aria-selected:text-white dark:border-slate-700 dark:text-slate-300 dark:hover:text-brand-300 dark:aria-selected:border-brand-600 dark:aria-selected:bg-brand-600 dark:aria-selected:text-white"
                            >
                                <span class="h-2.5 w-2.5 rounded-full ring-1 ring-white/70" style="background-color: {{ $row['category']->color ?: '#0f766e' }};"></span>
                                {{ $row['category']->name }}
                            </button>
                        @endforeach
                    </div>
                @endif


                {{-- One panel per category --}}
                @foreach($landingCategories as $row)

                    @php
                        $categoryServices = $row['services'];
                        $serviceCount = $categoryServices->count();
                    @endphp

                    <div
                        data-panel
                        id="services-panel-{{ $loop->index }}"
                        role="tabpanel"
                        @if($landingCategories->count() > 1) aria-labelledby="services-tab-{{ $loop->index }}" @else aria-label="{{ $row['category']->name }}" @endif
                        class="{{ $loop->first ? '' : 'hidden' }}"
                    >

                        @if($landingCategories->count() === 1)
                            <div class="mb-6 flex items-center gap-3">
                                <span class="h-8 w-1 rounded-full" style="background-color: {{ $row['category']->color ?: '#0f766e' }};"></span>
                                <h3 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $row['category']->name }}</h3>
                            </div>
                        @endif

                        {{-- Carousel: the next cards peek in at the edge --}}
                        <div data-carousel>
                            <div class="swiper" data-swiper data-count="{{ $serviceCount }}">
                                <div class="swiper-wrapper">

                                    @foreach($categoryServices as $service)

                                        @php $meta = $serviceMeta($service); @endphp

                                        <div class="swiper-slide !h-auto">

                                            <article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition-colors duration-300 hover:border-brand-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-brand-700">

                                                <div class="relative aspect-[4/3] overflow-hidden bg-brand-50 dark:bg-brand-950/60">

                                                    @if($meta['image'])

                                                        <img
                                                            src="{{ $meta['image'] }}"
                                                            alt="{{ $service->name }}"
                                                            loading="lazy"
                                                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105 dark:brightness-90"
                                                            onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden'); this.nextElementSibling.classList.add('flex');"
                                                        >

                                                        <div class="absolute inset-0 hidden items-center justify-center bg-brand-50 text-brand-300 dark:bg-brand-950/60 dark:text-brand-800">
                                                            <i data-lucide="image-off" class="h-9 w-9"></i>
                                                        </div>

                                                    @else

                                                        <div class="flex h-full items-center justify-center text-brand-300 dark:text-brand-800">
                                                            <i data-lucide="image" class="h-10 w-10"></i>
                                                        </div>

                                                    @endif

                                                </div>

                                                <div class="flex flex-1 flex-col p-5">

                                                    <div class="flex items-start justify-between gap-3">

                                                        <h4 class="text-base font-semibold leading-6 text-slate-900 dark:text-white">
                                                            {{ $service->name }}
                                                        </h4>

                                                        @if($meta['displayPrice'] !== null)
                                                            <div class="shrink-0 text-right">
                                                                @if($meta['hasDiscount'])
                                                                    <div class="text-xs text-slate-400 line-through dark:text-slate-500">
                                                                        ₱{{ number_format($service->price, 2) }}
                                                                    </div>
                                                                @endif
                                                                <div class="text-sm font-semibold text-brand-700 dark:text-brand-400">
                                                                    ₱{{ number_format($meta['displayPrice'], 2) }}
                                                                </div>
                                                            </div>
                                                        @endif

                                                    </div>

                                                    @if(!empty($meta['description']))
                                                        <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                                            {{ $meta['description'] }}
                                                        </p>
                                                    @endif

                                                    @if(!empty($service->duration_minutes))
                                                        <div class="mt-auto flex items-center border-t border-slate-100 pt-4 dark:border-slate-800">
                                                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                                                                <i data-lucide="clock-3" class="h-4 w-4"></i>
                                                                {{ $service->duration_minutes }} min
                                                            </span>
                                                        </div>
                                                    @endif

                                                </div>

                                            </article>

                                        </div>

                                    @endforeach

                                </div>
                            </div>
                        </div>


                        {{-- Full list (filled from the same cards when "View all" opens) --}}
                        <div data-grid id="services-grid-{{ $loop->index }}" class="hidden"></div>


                        {{-- Controls --}}
                        <div class="mt-6 flex items-center justify-between gap-4">

                            <div data-controls class="flex items-center gap-3">
                                <div class="service-dots swiper-pagination" data-dots></div>

                                <button
                                    type="button"
                                    data-autoplay-toggle
                                    aria-pressed="false"
                                    aria-label="Pause slideshow"
                                    class="flex h-8 w-8 items-center justify-center rounded-full text-slate-500 transition hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-brand-400"
                                >
                                    <span data-icon-pause><i data-lucide="pause" class="h-4 w-4"></i></span>
                                    <span data-icon-play class="hidden"><i data-lucide="play" class="h-4 w-4"></i></span>
                                </button>
                            </div>

                            @if($serviceCount > 1)
                                <button
                                    type="button"
                                    data-toggle-all
                                    aria-expanded="false"
                                    aria-controls="services-grid-{{ $loop->index }}"
                                    class="inline-flex shrink-0 items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-brand-700 transition hover:border-brand-300 hover:bg-brand-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/20 dark:border-slate-700 dark:text-brand-400 dark:hover:border-brand-700 dark:hover:bg-brand-900/30"
                                >
                                    <span data-toggle-label>View all ({{ $serviceCount }})</span>
                                    <i data-lucide="layout-grid" class="h-4 w-4"></i>
                                </button>
                            @endif

                        </div>

                    </div>

                @endforeach

            </div>

        @endif

    </div>

</section>



{{-- =========================================================
     LOCATION
========================================================== --}}
@php
    // Edit the details here. Coordinates place the map pin.
    $location = [
        'name'    => 'Spa Alexandria',
        'address' => '25th Lacson St, Bacolod, Negros Occidental, Philippines',
        'lat'     => 10.6854287,
        'lng'     => 122.956771,
    ];

    $directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination='
        . urlencode($location['lat'] . ',' . $location['lng']);
@endphp

<section id="location" class="scroll-mt-20 px-4 pb-20 sm:px-6 sm:pb-24 lg:px-8">

    <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)] lg:items-stretch">

        <div data-reveal class="flex flex-col justify-center">

            <h2 class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-5xl">
                Find us in Bacolod
            </h2>

            <div class="mt-8 flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                    <i data-lucide="map-pin" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $location['name'] }}</p>
                    <p class="mt-1 max-w-xs text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $location['address'] }}</p>
                </div>
            </div>

            <a href="{{ $directionsUrl }}" target="_blank" rel="noopener" class="mt-8 inline-flex h-12 w-fit items-center justify-center gap-2 rounded-xl bg-brand-700 px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/30 dark:bg-brand-600 dark:hover:bg-brand-500">
                Get directions
                <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
            </a>

        </div>

        <div data-reveal class="map-shell overflow-hidden rounded-[2rem] border border-slate-200 shadow-sm transition-colors duration-500 dark:border-slate-800">
            <div id="locationMap" class="h-[340px] w-full sm:h-[420px] lg:h-full lg:min-h-[420px]" role="region" aria-label="Map showing the location of {{ $location['name'] }}"></div>
        </div>

    </div>

</section>



{{-- =========================================================
     BOOKING CALL TO ACTION
========================================================== --}}
<section class="px-4 pb-20 sm:px-6 sm:pb-24 lg:px-8">

    <div data-reveal class="relative mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-brand-900">

        <div class="absolute inset-0 bg-gradient-to-br from-brand-600 via-brand-800 to-brand-950"></div>
        <div class="absolute inset-0 bg-gradient-to-br from-brand-900 via-brand-950 to-slate-950 opacity-0 transition-opacity duration-700 dark:opacity-100"></div>

        <div class="blob absolute -left-20 -top-24 h-80 w-80 rounded-full bg-brand-400/30 blur-3xl transition-opacity duration-700 dark:opacity-40"></div>

        <div class="pointer-events-none absolute -right-48 top-1/2 h-[30rem] w-[30rem] -translate-y-1/2" aria-hidden="true">
            <div class="ripple absolute inset-[30%] rounded-full border border-white/25"></div>
            <div class="ripple absolute inset-[20%] rounded-full border border-white/20" style="animation-delay:-2.2s"></div>
            <div class="ripple absolute inset-[10%] rounded-full border border-white/15" style="animation-delay:-4.4s"></div>
            <div class="ripple absolute inset-0 rounded-full border border-white/10" style="animation-delay:-6.6s"></div>
        </div>

        <div class="grain absolute inset-0"></div>

        <div class="relative flex flex-col gap-8 px-6 py-14 sm:px-10 md:flex-row md:items-center md:justify-between lg:px-16 lg:py-16">

            <div class="max-w-xl">
                <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                    Ready to book your visit?
                </h2>
                <p class="mt-3 text-base leading-7 text-white/80">
                    Pick your services, choose a date and time, and book as a guest or with your account.
                </p>
            </div>

            <a href="{{ route('booking.wizard') }}" class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-6 text-sm font-semibold text-brand-900 shadow-sm transition hover:bg-brand-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/40">
                Book an appointment
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </a>

        </div>

    </div>

</section>



{{-- =========================================================
     FOOTER
========================================================== --}}
<footer class="border-t border-slate-200 bg-slate-50 transition-colors duration-500 dark:border-slate-800 dark:bg-slate-900">

    <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-8 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">

        <div class="flex items-center gap-3">
            @if(file_exists(public_path('uploads/branding/spa-alexandria-logo.png')))
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white p-1 shadow-sm border border-slate-200 dark:border-slate-700">
                    <img src="{{ asset('uploads/branding/spa-alexandria-logo.png') }}" alt="Spa Alexandria" class="h-7 w-7 object-contain">
                </span>
            @endif
            <div>
                <div class="text-sm font-semibold text-slate-900 dark:text-white">Spa Alexandria</div>
                <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">ZenFlow</div>
            </div>
        </div>

        <div class="flex items-center gap-6 text-xs font-medium text-slate-500 dark:text-slate-400">
            <a href="{{ route('terms') }}" class="transition hover:text-brand-700 dark:hover:text-brand-400">Terms</a>
            <a href="{{ route('privacy') }}" class="transition hover:text-brand-700 dark:hover:text-brand-400">Privacy</a>
        </div>

    </div>

</footer>



<script>
    function toggleDarkMode() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
    }

    document.addEventListener('DOMContentLoaded', function () {

        if (window.lucide) lucide.createIcons();

        // Header gets a soft shadow once the page scrolls
        const header = document.getElementById('siteHeader');
        const onScroll = () => header.classList.toggle('shadow-md', window.scrollY > 8);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        // Map (OpenFreeMap vector styles, no API key; follows dark mode)
        const mapEl = document.getElementById('locationMap');
        if (mapEl && window.maplibregl) {
            try {
                const loc = @json(['lat' => $location['lat'], 'lng' => $location['lng'], 'name' => $location['name']]);
                const styles = {
                    light: 'https://tiles.openfreemap.org/styles/liberty',
                    dark: 'https://tiles.openfreemap.org/styles/dark'
                };
                const isDark = () => document.documentElement.classList.contains('dark');

                const map = new maplibregl.Map({
                    container: mapEl,
                    style: isDark() ? styles.dark : styles.light,
                    center: [loc.lng, loc.lat],
                    zoom: 15.5,
                    cooperativeGestures: true
                });
                map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');

                const pin = document.createElement('span');
                pin.className = 'map-pin';
                const label = document.createElement('span');
                label.className = 'map-label';
                label.textContent = loc.name;
                pin.appendChild(label);
                new maplibregl.Marker({ element: pin, anchor: 'center' })
                    .setLngLat([loc.lng, loc.lat])
                    .addTo(map);

                let currentDark = isDark();
                new MutationObserver(() => {
                    if (isDark() !== currentDark) {
                        currentDark = isDark();
                        map.setStyle(currentDark ? styles.dark : styles.light);
                    }
                }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            } catch (e) {
                mapEl.innerHTML = '<div class="flex h-full items-center justify-center p-6 text-center text-sm text-slate-500 dark:text-slate-400">The map could not load. Use Get directions instead.</div>';
            }
        }

        // Services: category tabs + one carousel per category (6s autoplay, next cards peek in)
        (function () {
            const section = document.getElementById('services');
            const panels = section ? [...section.querySelectorAll('[data-panel]')] : [];
            if (!panels.length) return;

            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const tabs = [...section.querySelectorAll('[data-tab]')];
            const tabBar = section.querySelector('[data-tablist]');
            const pauseButtons = [...section.querySelectorAll('[data-autoplay-toggle]')];
            const AUTOPLAY_MS = 6000;

            // With reduced motion the slideshow starts paused; the Play button lets the visitor opt in
            const ui = { inView: !('IntersectionObserver' in window), userPaused: reduced };
            let current = 0;
            let autoRotate = panels.length > 1 && !reduced;
            let rotateTimer = null;

            const P = panels.map((el) => ({
                el,
                swiperEl: el.querySelector('[data-swiper]'),
                carousel: el.querySelector('[data-carousel]'),
                controls: el.querySelector('[data-controls]'),
                dotsEl: el.querySelector('[data-dots]'),
                pauseBtn: el.querySelector('[data-autoplay-toggle]'),
                toggle: el.querySelector('[data-toggle-all]'),
                grid: el.querySelector('[data-grid]'),
                count: parseInt(el.querySelector('[data-swiper]').dataset.count, 10),
                sw: null, hover: false, finished: false, expanded: false
            }));

            const blocked = (p) => !ui.inView || ui.userPaused || p.hover || p.expanded;

            const sync = () => {
                P.forEach((q, i) => {
                    if (i !== current && q.sw && q.sw.autoplay.running) q.sw.autoplay.stop();
                });
                const p = P[current];
                if (!p || !p.sw) return;
                const run = !blocked(p) && !p.finished && !p.sw.isLocked;
                if (run && !p.sw.autoplay.running) p.sw.autoplay.start();
                else if (!run && p.sw.autoplay.running) p.sw.autoplay.stop();
            };

            const renderPause = () => pauseButtons.forEach((btn) => {
                btn.setAttribute('aria-pressed', String(ui.userPaused));
                btn.setAttribute('aria-label', ui.userPaused ? 'Play slideshow' : 'Pause slideshow');
                btn.querySelector('[data-icon-pause]').classList.toggle('hidden', ui.userPaused);
                btn.querySelector('[data-icon-play]').classList.toggle('hidden', !ui.userPaused);
            });

            // Hide the slideshow controls when every card already fits on screen
            const updateUI = (i) => {
                const p = P[i];
                const locked = !!(p.sw && p.sw.isLocked);
                if (p.pauseBtn) p.pauseBtn.classList.toggle('hidden', locked);
                if (p.toggle) p.toggle.classList.toggle('hidden', locked && !p.expanded);
            };

            // After the last card, wait one beat, then go to the next category (or loop back)
            const afterEnd = () => {
                const p = P[current];
                if (!p) return;
                if (blocked(p)) { rotateTimer = setTimeout(afterEnd, 1000); return; }
                if (P.length > 1 && autoRotate) {
                    switchTo((current + 1) % P.length, { auto: true });
                } else if (p.sw && !p.sw.isLocked) {
                    p.finished = false;
                    p.sw.slideTo(0);
                    sync();
                }
            };

            const initSwiper = (i) => {
                const p = P[i];
                if (p.sw || !window.Swiper) return;

                p.sw = new Swiper(p.swiperEl, {
                    speed: reduced ? 0 : 700,
                    spaceBetween: 20,
                    grabCursor: true,
                    slidesPerView: 1.15,
                    breakpoints: {
                        640:  { slidesPerView: 2.15 },
                        1024: { slidesPerView: 3.15 },
                        1280: { slidesPerView: 4.15 }
                    },
                    keyboard: { enabled: true },
                    a11y: { enabled: true },
                    autoplay: { delay: AUTOPLAY_MS, disableOnInteraction: false, stopOnLastSlide: true },
                    pagination: { el: p.dotsEl, clickable: true, dynamicBullets: p.count > 7, dynamicMainBullets: 5 }
                });

                p.sw.autoplay.stop();

                p.sw.on('reachEnd', () => {
                    if (i !== current) return;
                    p.finished = true;
                    clearTimeout(rotateTimer);
                    rotateTimer = setTimeout(afterEnd, AUTOPLAY_MS);
                });
                p.sw.on('slideChange', () => {
                    if (!p.sw.isEnd) { p.finished = false; if (i === current) clearTimeout(rotateTimer); }
                    sync();
                });
                p.sw.on('lock unlock breakpoint', () => { updateUI(i); sync(); });

                updateUI(i);
            };

            const switchTo = (i, opts) => {
                opts = opts || {};
                if (!opts.auto) autoRotate = false;
                clearTimeout(rotateTimer);

                tabs.forEach((t, k) => {
                    t.setAttribute('aria-selected', String(k === i));
                    t.tabIndex = k === i ? 0 : -1;
                });
                panels.forEach((el, k) => el.classList.toggle('hidden', k !== i));
                current = i;

                const p = P[i];
                initSwiper(i);
                if (p.sw) { p.sw.update(); p.sw.slideTo(0, 0); p.finished = false; updateUI(i); }

                if (tabBar && tabs[i]) {
                    tabBar.scrollTo({
                        left: tabs[i].offsetLeft - (tabBar.clientWidth - tabs[i].offsetWidth) / 2,
                        behavior: reduced ? 'auto' : 'smooth'
                    });
                }
                if (window.gsap && !reduced) {
                    gsap.fromTo(panels[i], { opacity: 0, y: 14 }, { opacity: 1, y: 0, duration: 0.5, ease: 'power3.out' });
                }

                sync();
                if (autoRotate && P.length > 1 && p.sw && p.sw.isLocked) rotateTimer = setTimeout(afterEnd, AUTOPLAY_MS + 2000);
            };

            tabs.forEach((tab, i) => {
                tab.addEventListener('click', () => switchTo(i));
                tab.addEventListener('keydown', (e) => {
                    const keys = { ArrowRight: 1, ArrowLeft: -1 };
                    let next = null;
                    if (keys[e.key]) next = (i + keys[e.key] + tabs.length) % tabs.length;
                    else if (e.key === 'Home') next = 0;
                    else if (e.key === 'End') next = tabs.length - 1;
                    if (next === null) return;
                    e.preventDefault();
                    switchTo(next);
                    tabs[next].focus();
                });
            });

            P.forEach((p, i) => {
                // Hover pauses (mouse only, so touch screens never get stuck "hovering")
                p.carousel.addEventListener('pointerenter', (e) => { if (e.pointerType === 'mouse') { p.hover = true; sync(); } });
                p.carousel.addEventListener('pointerleave', (e) => { if (e.pointerType === 'mouse') { p.hover = false; sync(); } });

                if (p.pauseBtn) {
                    p.pauseBtn.addEventListener('click', () => {
                        ui.userPaused = !ui.userPaused;
                        renderPause();
                        sync();
                    });
                }

                if (p.toggle) {
                    p.toggle.addEventListener('click', () => {
                        const opening = p.grid.classList.contains('hidden');

                        if (opening && !p.grid.dataset.built) {
                            const grid = document.createElement('div');
                            grid.className = 'grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4';
                            p.el.querySelectorAll('.swiper-slide > article').forEach((card) => {
                                const clone = card.cloneNode(true);
                                clone.setAttribute('data-grid-card', '');
                                grid.appendChild(clone);
                            });
                            p.grid.appendChild(grid);
                            p.grid.dataset.built = '1';
                        }

                        p.grid.classList.toggle('hidden', !opening);
                        p.carousel.classList.toggle('hidden', opening);
                        p.controls.classList.toggle('hidden', opening);
                        p.expanded = opening;

                        p.toggle.setAttribute('aria-expanded', String(opening));
                        p.toggle.querySelector('[data-toggle-label]').textContent = opening ? 'Show less' : 'View all (' + p.count + ')';

                        if (!opening && p.sw) p.sw.update();
                        updateUI(i);
                        sync();

                        if (opening && window.gsap && !reduced) {
                            gsap.from(p.grid.querySelectorAll('[data-grid-card]'), {
                                opacity: 0, y: 20, duration: 0.6, ease: 'power3.out', stagger: 0.06
                            });
                        }
                        if (window.ScrollTrigger) ScrollTrigger.refresh();
                    });
                }
            });

            initSwiper(0);
            renderPause();
            sync();

            if ('IntersectionObserver' in window) {
                new IntersectionObserver(([entry]) => {
                    ui.inView = entry.isIntersecting;
                    sync();
                }, { threshold: 0.15 }).observe(section);
            }
        })();

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduceMotion || !window.gsap || !window.ScrollTrigger) return;

        gsap.registerPlugin(ScrollTrigger);

        // Smooth scrolling, driven by GSAP's ticker so ScrollTrigger stays in sync
        if (window.Lenis) {
            const lenis = new Lenis({ lerp: 0.1 });
            lenis.on('scroll', ScrollTrigger.update);
            gsap.ticker.add((time) => lenis.raf(time * 1000));
            gsap.ticker.lagSmoothing(0);

            document.querySelectorAll('a[href^="#"]').forEach((link) => {
                link.addEventListener('click', (e) => {
                    const target = document.querySelector(link.getAttribute('href'));
                    if (!target) return;
                    e.preventDefault();
                    lenis.scrollTo(target, { offset: -72 });
                });
            });
        }

        // Page-load entrance
        gsap.from('[data-hero]', { y: 18, opacity: 0, duration: 0.9, ease: 'power3.out', stagger: 0.12 });

        // Hero photo drifts slower than the page while scrolling
        const frame = document.querySelector('[data-hero-frame]');
        const parallax = document.querySelector('[data-parallax]');
        if (frame && parallax) {
            gsap.to(parallax, {
                yPercent: 6,
                ease: 'none',
                scrollTrigger: { trigger: frame, start: 'top top', end: 'bottom top', scrub: true }
            });
        }

        // Sections, category titles and service cards rise in as they enter the screen
        gsap.set('[data-reveal]', { opacity: 0, y: 28 });
        ScrollTrigger.batch('[data-reveal]', {
            start: 'top 90%',
            once: true,
            onEnter: (batch) => gsap.to(batch, {
                opacity: 1,
                y: 0,
                duration: 0.8,
                ease: 'power3.out',
                stagger: 0.08,
                overwrite: true
            })
        });
    });
</script>

</body>
</html>