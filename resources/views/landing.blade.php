<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $hero->title ?? 'Spa Alexandria' }}</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: [
                            'Inter',
                            'ui-sans-serif',
                            'system-ui',
                            'sans-serif'
                        ],
                    }
                }
            }
        }
    </script>

    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        .page-fade {
    animation: pageFade 0.7s ease-out both;
    }

    .form-fade {
        animation: formFade 0.75s ease-out 0.08s both;
    }

    .image-content-fade {
        animation: imageContentFade 0.9s ease-out 0.12s both;
    }

    @keyframes pageFade {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes formFade {
        from {
            opacity: 0;
            transform: translateY(12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes imageContentFade {
        from {
            opacity: 0;
            transform: translateY(16px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .page-fade,
        .form-fade,
        .image-content-fade {
            animation: none;
        }
    }
        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        }

        .hero-side-gradient {
            background:
                linear-gradient(
                    90deg,
                    rgba(15, 23, 42, 0.88) 0%,
                    rgba(15, 23, 42, 0.62) 38%,
                    rgba(15, 23, 42, 0.20) 72%,
                    rgba(15, 23, 42, 0.04) 100%
                );
        }

        .hero-bottom-fade {
            background:
                linear-gradient(
                    to bottom,
                    rgba(248, 250, 252, 0) 0%,
                    rgba(248, 250, 252, 0.08) 15%,
                    rgba(248, 250, 252, 0.28) 35%,
                    rgba(248, 250, 252, 0.58) 58%,
                    rgba(248, 250, 252, 0.88) 78%,
                    #f8fafc 100%
                );
        }

        .service-card {
            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease;
        }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }
    </style>
</head>


<body class="page-fade min-h-screen overflow-x-hidden bg-slate-50 text-slate-900 antialiased">

@php

    $resolveImage = function ($path) {

        if (!$path) {
            return null;
        }

        $path = trim($path);

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $path = preg_replace(
            '#^/?storage/#i',
            '',
            $path
        );

        return asset(
            'storage/' . ltrim($path, '/')
        );
    };


    $heroImage = $resolveImage(
        $hero->image ?? null
    );


    $visibleCategories = isset($categories)
        ? $categories->filter(
            fn ($category) => (bool) $category->show_on_landing
        )
        : collect();


    $dashboardRoute = 'landing';


    if (auth()->check()) {

        $role =
            auth()->user()->roles->first()->name
            ?? 'customer';


        $dashboardRoute = match ($role) {

            'admin' =>
                'admin-dashboard',

            'receptionist' =>
                'receptionist.dashboard',

            'staff' =>
                'staff.dashboard',

            'customer' =>
                'customer-dashboard',

            default =>
                'landing',

        };

    }

@endphp



{{-- =========================================================
     HEADER
========================================================== --}}

<header class="fixed inset-x-0 top-0 z-50">

    <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">

        <nav
            class="
                flex
                items-center
                justify-between
                rounded-2xl
                border
                border-slate-200/70
                bg-white/90
                px-4
                py-3
                shadow-sm
                backdrop-blur-xl
                sm:px-6
            "
        >

            {{-- Brand --}}
            <a
                href="{{ route('landing') }}"
                class="flex items-center gap-3"
            >

                <div
                    class="
                        flex
                        h-10
                        w-10
                        shrink-0
                        items-center
                        justify-center
                        rounded-xl
                        bg-teal-700
                        text-white
                    "
                >

                    <i
                        data-lucide="sparkles"
                        class="h-5 w-5"
                    ></i>

                </div>


                <div class="leading-tight">

                    <div
                        class="
                            text-sm
                            font-extrabold
                            text-slate-900
                        "
                    >
                        Spa Alexandria
                    </div>

                </div>

            </a>



            {{-- Login + Register --}}
            <div class="flex items-center gap-4 sm:gap-6">

                @auth

                    <a
                        href="{{ route($dashboardRoute) }}"
                        class="
                            text-sm
                            font-semibold
                            text-slate-600
                            transition
                            hover:text-teal-700
                        "
                    >
                        Dashboard
                    </a>

                @else

                    <a
                        href="{{ route('login') }}"
                        class="
                            text-sm
                            font-semibold
                            text-slate-600
                            transition
                            hover:text-teal-700
                        "
                    >
                        Login
                    </a>


                    <a
                        href="{{ route('customer.register') }}"
                        class="
                            text-sm
                            font-semibold
                            text-slate-600
                            transition
                            hover:text-teal-700
                        "
                    >
                        Register
                    </a>

                @endauth

            </div>

        </nav>

    </div>

</header>



{{-- =========================================================
     HERO
========================================================== --}}

<section
    class="
        relative
        min-h-[720px]
        overflow-hidden
        bg-slate-50
    "
>

    @if($heroImage)

        <img
            src="{{ $heroImage }}"
            alt="{{ $hero->title ?? 'Spa Alexandria' }}"
            class="
                absolute
                inset-0
                h-full
                w-full
                object-cover
            "
        >


        <div
            class="
                absolute
                inset-0
                bg-slate-950/35
            "
        ></div>


        <div
            class="
                hero-side-gradient
                absolute
                inset-0
            "
        ></div>


        <div
            class="
                hero-bottom-fade
                absolute
                inset-x-0
                bottom-0
                h-72
            "
        ></div>

    @else

        <div
            class="
                absolute
                inset-0
                bg-gradient-to-br
                from-slate-950
                via-slate-900
                to-teal-950
            "
        ></div>


        <div
            class="
                absolute
                inset-x-0
                bottom-0
                h-64
                bg-gradient-to-t
                from-slate-50
                via-slate-50/70
                to-transparent
            "
        ></div>

    @endif



    <div
        class="
            relative
            mx-auto
            flex
            min-h-[720px]
            max-w-7xl
            items-center
            px-4
            pb-40
            pt-32
            sm:px-6
            lg:px-8
        "
    >

        <div class="max-w-2xl">

            @if(!empty($hero->subtitle))

                <p
                    class="
                        mb-5
                        text-sm
                        font-semibold
                        tracking-wide
                        text-teal-200
                    "
                >
                    {{ $hero->subtitle }}
                </p>

            @endif


            <h1
                class="
                    max-w-2xl
                    text-4xl
                    font-extrabold
                    leading-tight
                    tracking-tight
                    text-white
                    sm:text-5xl
                    lg:text-6xl
                "
            >
                {{ $hero->title ?? 'Relax, restore, and feel your best.' }}
            </h1>


            <p
                class="
                    mt-5
                    max-w-xl
                    text-base
                    leading-7
                    text-white/85
                    sm:text-lg
                "
            >
                Choose your services, find an available date and time,
                and book your appointment as a guest or registered customer.
            </p>


            <div
                class="
                    mt-8
                    flex
                    flex-col
                    gap-3
                    sm:flex-row
                "
            >

                <a
                    href="{{ route('booking.wizard') }}"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        gap-2
                        rounded-xl
                        bg-white
                        px-5
                        py-3
                        text-sm
                        font-extrabold
                        text-slate-900
                        shadow-lg
                        transition
                        hover:bg-slate-100
                    "
                >

                    Book an Appointment

                    <i
                        data-lucide="arrow-right"
                        class="h-4 w-4"
                    ></i>

                </a>


                <a
                    href="#services"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        gap-2
                        rounded-xl
                        border
                        border-white/30
                        bg-white/10
                        px-5
                        py-3
                        text-sm
                        font-bold
                        text-white
                        backdrop-blur-sm
                        transition
                        hover:bg-white/20
                    "
                >

                    Highlighted Services

                    <i
                        data-lucide="chevron-down"
                        class="h-4 w-4"
                    ></i>

                </a>

            </div>

        </div>

    </div>

</section>



{{-- =========================================================
     BOOKING OPTIONS
========================================================== --}}

<section class="bg-slate-50">

    <div
        class="
            mx-auto
            max-w-7xl
            px-4
            pb-10
            pt-2
            sm:px-6
            lg:px-8
        "
    >

        <div
            class="
                grid
                gap-4
                sm:grid-cols-3
            "
        >

            {{-- Guest booking --}}
            <div
                class="
                    flex
                    items-center
                    gap-3
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    px-5
                    py-4
                    shadow-sm
                "
            >

                <div
                    class="
                        flex
                        h-10
                        w-10
                        shrink-0
                        items-center
                        justify-center
                        rounded-xl
                        bg-teal-50
                        text-teal-700
                    "
                >

                    <i
                        data-lucide="user-round-check"
                        class="h-5 w-5"
                    ></i>

                </div>


                <div>

                    <p
                        class="
                            text-sm
                            font-bold
                            text-slate-900
                        "
                    >
                        Book as a guest
                    </p>


                    <p
                        class="
                            mt-0.5
                            text-xs
                            text-slate-500
                        "
                    >
                        No account required
                    </p>

                </div>

            </div>



            {{-- Date & time --}}
            <div
                class="
                    flex
                    items-center
                    gap-3
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    px-5
                    py-4
                    shadow-sm
                "
            >

                <div
                    class="
                        flex
                        h-10
                        w-10
                        shrink-0
                        items-center
                        justify-center
                        rounded-xl
                        bg-teal-50
                        text-teal-700
                    "
                >

                    <i
                        data-lucide="calendar-days"
                        class="h-5 w-5"
                    ></i>

                </div>


                <div>

                    <p
                        class="
                            text-sm
                            font-bold
                            text-slate-900
                        "
                    >
                        Choose your date & time
                    </p>


                    <p
                        class="
                            mt-0.5
                            text-xs
                            text-slate-500
                        "
                    >
                        See available appointments
                    </p>

                </div>

            </div>



            {{-- Multiple services --}}
            <div
                class="
                    flex
                    items-center
                    gap-3
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    px-5
                    py-4
                    shadow-sm
                "
            >

                <div
                    class="
                        flex
                        h-10
                        w-10
                        shrink-0
                        items-center
                        justify-center
                        rounded-xl
                        bg-teal-50
                        text-teal-700
                    "
                >

                    <i
                        data-lucide="layers-3"
                        class="h-5 w-5"
                    ></i>

                </div>


                <div>

                    <p
                        class="
                            text-sm
                            font-bold
                            text-slate-900
                        "
                    >
                        Select multiple services
                    </p>


                    <p
                        class="
                            mt-0.5
                            text-xs
                            text-slate-500
                        "
                    >
                        Build your appointment your way
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>



{{-- =========================================================
     SERVICES
========================================================== --}}

<section
    id="services"
    class="
        scroll-mt-20
        bg-slate-50
        pb-20
        pt-8
        sm:pb-24
        sm:pt-10
    "
>

    <div
        class="
            mx-auto
            max-w-7xl
            px-4
            sm:px-6
            lg:px-8
        "
    >

        <div class="mb-10">

            <p
                class="
                    text-sm
                    font-bold
                    uppercase
                    tracking-[0.18em]
                    text-teal-700
                "
            >
                Highlighted Services
            </p>


            <div
                class="
                    mt-2
                    flex
                    flex-col
                    gap-3
                    sm:flex-row
                    sm:items-end
                    sm:justify-between
                "
            >

                <h2
                    class="
                        text-3xl
                        font-extrabold
                        tracking-tight
                        text-slate-900
                        sm:text-4xl
                    "
                >
                    A selection of our services
                </h2>


                <p
                    class="
                        max-w-md
                        text-sm
                        leading-6
                        text-slate-500
                    "
                >
                    Explore a selection of services featured on our landing page.
                </p>

            </div>

        </div>



        @if($visibleCategories->isEmpty())

            <div
                class="
                    rounded-3xl
                    border
                    border-dashed
                    border-slate-300
                    bg-white
                    px-6
                    py-16
                    text-center
                "
            >

                <div
                    class="
                        mx-auto
                        flex
                        h-14
                        w-14
                        items-center
                        justify-center
                        rounded-2xl
                        bg-slate-100
                        text-slate-400
                    "
                >

                    <i
                        data-lucide="package-open"
                        class="h-6 w-6"
                    ></i>

                </div>


                <h3
                    class="
                        mt-4
                        text-lg
                        font-bold
                        text-slate-900
                    "
                >
                    No highlighted services
                </h3>


                <p
                    class="
                        mt-1
                        text-sm
                        text-slate-500
                    "
                >
                    Services selected for the landing page will appear here.
                </p>

            </div>

        @else

            <div class="space-y-12">

                @foreach($visibleCategories as $category)

                    @php

                        $categoryServices =
                            $category->services->filter(
                                fn ($service) =>
                                    (bool) $service->show_on_landing
                            );


                        $categoryColor =
                            $category->color ?: '#0f766e';

                    @endphp


                    @if($categoryServices->isNotEmpty())

                        <div>

                            <div
                                class="
                                    mb-5
                                    flex
                                    items-center
                                    gap-3
                                "
                            >

                                <span
                                    class="
                                        h-8
                                        w-1
                                        rounded-full
                                    "
                                    style="background-color: {{ $categoryColor }};"
                                ></span>


                                <h3
                                    class="
                                        text-xl
                                        font-extrabold
                                        text-slate-900
                                    "
                                >
                                    {{ $category->name }}
                                </h3>

                            </div>



                            <div
                                class="
                                    grid
                                    gap-5
                                    sm:grid-cols-2
                                    lg:grid-cols-3
                                    xl:grid-cols-4
                                "
                            >

                                @foreach($categoryServices as $service)

                                    @php

                                        $serviceImage =
                                            $resolveImage(
                                                $service->image ?? null
                                            );


                                        $hasDiscount =
                                            $service->discount_price &&
                                            $service->price &&
                                            $service->discount_price <
                                            $service->price;


                                        $displayPrice =
                                            $hasDiscount
                                                ? $service->discount_price
                                                : $service->price;

                                    @endphp


                                    <article
                                        class="
                                            service-card
                                            overflow-hidden
                                            rounded-2xl
                                            border
                                            border-slate-200
                                            bg-white
                                        "
                                    >

                                        {{-- Service image --}}
                                        <div
                                            class="
                                                relative
                                                aspect-[4/3]
                                                overflow-hidden
                                                bg-slate-100
                                            "
                                        >

                                            @if($serviceImage)

                                                <img
                                                    src="{{ $serviceImage }}"
                                                    alt="{{ $service->name }}"
                                                    loading="lazy"
                                                    class="
                                                        h-full
                                                        w-full
                                                        object-cover
                                                        transition
                                                        duration-500
                                                        hover:scale-105
                                                    "
                                                    onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden'); this.nextElementSibling.classList.add('flex');"
                                                >


                                                <div
                                                    class="
                                                        absolute
                                                        inset-0
                                                        hidden
                                                        items-center
                                                        justify-center
                                                        bg-slate-100
                                                        text-slate-300
                                                    "
                                                >

                                                    <i
                                                        data-lucide="image-off"
                                                        class="h-9 w-9"
                                                    ></i>

                                                </div>

                                            @else

                                                <div
                                                    class="
                                                        flex
                                                        h-full
                                                        items-center
                                                        justify-center
                                                        text-slate-300
                                                    "
                                                >

                                                    <i
                                                        data-lucide="image"
                                                        class="h-10 w-10"
                                                    ></i>

                                                </div>

                                            @endif

                                        </div>



                                        {{-- Service content --}}
                                        <div class="p-5">

                                            <div
                                                class="
                                                    flex
                                                    items-start
                                                    justify-between
                                                    gap-3
                                                "
                                            >

                                                <h4
                                                    class="
                                                        text-base
                                                        font-extrabold
                                                        leading-6
                                                        text-slate-900
                                                    "
                                                >
                                                    {{ $service->name }}
                                                </h4>


                                                @if($displayPrice !== null)

                                                    <div
                                                        class="
                                                            shrink-0
                                                            text-right
                                                        "
                                                    >

                                                        @if($hasDiscount)

                                                            <div
                                                                class="
                                                                    text-xs
                                                                    text-slate-400
                                                                    line-through
                                                                "
                                                            >
                                                                ₱{{ number_format($service->price, 2) }}
                                                            </div>

                                                        @endif


                                                        <div
                                                            class="
                                                                text-sm
                                                                font-extrabold
                                                                text-teal-700
                                                            "
                                                        >
                                                            ₱{{ number_format($displayPrice, 2) }}
                                                        </div>

                                                    </div>

                                                @endif

                                            </div>



                                            @if(!empty($service->landing_description))

                                                <p
                                                    class="
                                                        mt-3
                                                        line-clamp-3
                                                        text-sm
                                                        leading-6
                                                        text-slate-500
                                                    "
                                                >
                                                    {{ $service->landing_description }}
                                                </p>

                                            @elseif(!empty($service->description))

                                                <p
                                                    class="
                                                        mt-3
                                                        line-clamp-3
                                                        text-sm
                                                        leading-6
                                                        text-slate-500
                                                    "
                                                >
                                                    {{ $service->description }}
                                                </p>

                                            @endif



                                            @if(!empty($service->duration_minutes))

                                                <div
                                                    class="
                                                        mt-5
                                                        flex
                                                        items-center
                                                        border-t
                                                        border-slate-100
                                                        pt-4
                                                    "
                                                >

                                                    <div
                                                        class="
                                                            inline-flex
                                                            items-center
                                                            gap-1.5
                                                            text-xs
                                                            font-semibold
                                                            text-slate-500
                                                        "
                                                    >

                                                        <i
                                                            data-lucide="clock-3"
                                                            class="h-4 w-4"
                                                        ></i>

                                                        {{ $service->duration_minutes }}
                                                        min

                                                    </div>

                                                </div>

                                            @endif

                                        </div>

                                    </article>

                                @endforeach

                            </div>

                        </div>

                    @endif

                @endforeach

            </div>

        @endif

    </div>

</section>



{{-- =========================================================
     FOOTER
========================================================== --}}

<footer class="border-t border-slate-200 bg-white">

    <div
        class="
            mx-auto
            flex
            max-w-7xl
            flex-col
            gap-4
            px-4
            py-8
            sm:px-6
            md:flex-row
            md:items-center
            md:justify-between
            lg:px-8
        "
    >

        <div>

            <div
                class="
                    text-sm
                    font-extrabold
                    text-slate-900
                "
            >
                Spa Alexandria
            </div>


            <div
                class="
                    mt-1
                    text-xs
                    text-slate-500
                "
            >
                ZenFlow
            </div>

        </div>


        <div
            class="
                flex
                items-center
                gap-5
                text-xs
                font-semibold
                text-slate-500
            "
        >

            <a
                href="{{ route('terms') }}"
                class="transition hover:text-teal-700"
            >
                Terms
            </a>


            <a
                href="{{ route('privacy') }}"
                class="transition hover:text-teal-700"
            >
                Privacy
            </a>

        </div>

    </div>

</footer>



<script src="https://cdn.jsdelivr.net/npm/preline@3.0.1/dist/preline.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        if (window.lucide) {
            lucide.createIcons();
        }

    });
</script>

</body>
</html>