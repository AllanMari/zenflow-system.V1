<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | Spa Alexandria</title>

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
                        }
                    }
                }
            }
        }
    </script>

    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        html,
        body {
            min-height: 100%;
        }

        body {
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        }

        /* Minimal entrance animation */
        .soft-fade {
            animation: softFade 0.5s ease-out both;
        }

        @keyframes softFade {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .soft-fade {
                animation: none;
            }
        }

        .image-overlay {
            background:
                linear-gradient(
                    180deg,
                    rgba(15, 23, 42, 0.04) 0%,
                    rgba(15, 23, 42, 0.15) 45%,
                    rgba(15, 23, 42, 0.45) 100%
                );
        }

        .image-side-fade {
            background:
                linear-gradient(
                    90deg,
                    rgba(255, 255, 255, 0) 0%,
                    rgba(255, 255, 255, 0.03) 55%,
                    rgba(248, 250, 252, 0.25) 100%
                );
        }

        .form-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .form-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .form-scroll::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.35);
            border-radius: 999px;
        }
    </style>
</head>


<body class="min-h-screen overflow-x-hidden bg-slate-50 text-slate-900 antialiased">

    <main class="min-h-screen lg:grid lg:h-screen lg:grid-cols-2 lg:overflow-hidden">

        {{-- =====================================================
             LEFT IMAGE SIDE
        ====================================================== --}}

        <section class="relative hidden h-screen overflow-hidden lg:block">

            @php
                $registerImage = null;

                if (isset($hero) && !empty($hero->image)) {
                    $registerImage = trim($hero->image);

                    if (!preg_match('/^https?:\/\//i', $registerImage)) {
                        $registerImage = preg_replace(
                            '#^/?storage/#i',
                            '',
                            $registerImage
                        );

                        $registerImage = asset(
                            'storage/' . ltrim($registerImage, '/')
                        );
                    }
                }
            @endphp


            @if($registerImage)

                <img
                    src="{{ $registerImage }}"
                    alt="Spa Alexandria"
                    class="absolute inset-0 h-full w-full object-cover"
                >

                <div class="image-overlay absolute inset-0"></div>

                <div class="image-side-fade absolute inset-0"></div>

            @else

                <div
                    class="
                        absolute
                        inset-0
                        bg-gradient-to-br
                        from-teal-900
                        via-teal-800
                        to-slate-900
                    "
                ></div>

            @endif


            {{-- Brand --}}
            <div
                class="
                    absolute
                    left-8
                    top-8
                    z-10
                    xl:left-14
                    xl:top-14
                "
            >

                <a
                    href="{{ route('landing') }}"
                    class="soft-fade flex items-center gap-3"
                >

                    <div
                        class="
                            flex
                            h-11
                            w-11
                            shrink-0
                            items-center
                            justify-center
                            rounded-2xl
                            bg-white/90
                            text-brand-700
                            shadow-lg
                            shadow-black/10
                            backdrop-blur
                        "
                    >

                        <i
                            data-lucide="sparkles"
                            class="h-5 w-5"
                        ></i>

                    </div>


                    <div>

                        <div
                            class="
                                text-sm
                                font-extrabold
                                tracking-wide
                                text-white
                            "
                        >
                            Spa Alexandria
                        </div>


                        <div
                            class="
                                mt-0.5
                                text-[11px]
                                font-medium
                                uppercase
                                tracking-[0.16em]
                                text-white/65
                            "
                        >
                            Wellness & Care
                        </div>

                    </div>

                </a>

            </div>



            {{-- Main image text --}}
            <div
                class="
                    soft-fade
                    absolute
                    left-8
                    top-1/2
                    z-10
                    max-w-lg
                    -translate-y-1/2
                    xl:left-14
                "
            >

                <h1
                    class="
                        max-w-xl
                        text-4xl
                        font-extrabold
                        leading-[1.08]
                        tracking-tight
                        text-white
                        xl:text-5xl
                    "
                >
                    Make every visit easier.
                </h1>


                <p
                    class="
                        mt-5
                        max-w-md
                        text-sm
                        leading-7
                        text-white/75
                        xl:text-base
                    "
                >
                    Create your Spa Alexandria account to keep your customer details together and manage your appointments in one place.
                </p>

            </div>



            {{-- Copyright --}}
            <div
                class="
                    soft-fade
                    absolute
                    bottom-8
                    left-8
                    z-10
                    text-xs
                    font-medium
                    text-white/50
                    xl:left-14
                "
            >
                © {{ date('Y') }} Spa Alexandria
            </div>

        </section>



        {{-- =====================================================
             RIGHT REGISTER SIDE
        ====================================================== --}}

        <section
            class="
                form-scroll
                flex
                min-h-screen
                w-full
                items-center
                justify-center
                px-4
                py-8
                sm:px-6
                lg:h-screen
                lg:overflow-y-auto
                lg:px-12
                xl:px-20
            "
        >

            <div class="soft-fade w-full max-w-md">

                {{-- Mobile brand --}}
                <div class="mb-8 w-full lg:hidden">

                    <a
                        href="{{ route('landing') }}"
                        class="
                            flex
                            w-full
                            items-center
                            gap-2.5
                            sm:gap-3
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
                                bg-brand-700
                                text-white
                                shadow-sm
                            "
                        >

                            <i
                                data-lucide="sparkles"
                                class="h-5 w-5"
                            ></i>

                        </div>


                        <div class="min-w-0 flex-1">

                            <div
                                class="
                                    text-sm
                                    font-extrabold
                                    tracking-wide
                                    leading-5
                                    text-slate-900
                                "
                            >
                                Spa Alexandria
                            </div>


                            <div
                                class="
                                    text-[9px]
                                    font-medium
                                    uppercase
                                    leading-4
                                    tracking-[0.10em]
                                    text-slate-400
                                    sm:text-[11px]
                                    sm:tracking-[0.14em]
                                "
                            >
                                Wellness & Care
                            </div>

                        </div>

                    </a>

                </div>



                {{-- Register header --}}
                <div class="mb-7">

                    <div
                        class="
                            mb-3
                            flex
                            h-11
                            w-11
                            items-center
                            justify-center
                            rounded-2xl
                            bg-brand-50
                            text-brand-700
                        "
                    >

                        <i
                            data-lucide="user-plus"
                            class="h-5 w-5"
                        ></i>

                    </div>


                    <p
                        class="
                            text-sm
                            font-bold
                            text-brand-700
                        "
                    >
                        Welcome
                    </p>


                    <h2
                        class="
                            mt-1.5
                            text-3xl
                            font-extrabold
                            tracking-tight
                            text-slate-900
                        "
                    >
                        Create your account
                    </h2>


                    <p
                        class="
                            mt-2
                            text-sm
                            leading-6
                            text-slate-500
                        "
                    >
                        Register as a customer to manage your Spa Alexandria appointments.
                    </p>

                </div>



                {{-- Validation errors --}}
                @if($errors->any())

                    <div
                        class="
                            mb-6
                            rounded-2xl
                            border
                            border-red-200
                            bg-red-50
                            p-4
                            text-sm
                            text-red-700
                        "
                    >

                        <div class="flex items-start gap-3">

                            <i
                                data-lucide="circle-alert"
                                class="mt-0.5 h-5 w-5 shrink-0"
                            ></i>


                            <div class="space-y-1">

                                @foreach($errors->all() as $error)

                                    <p>
                                        {{ $error }}
                                    </p>

                                @endforeach

                            </div>

                        </div>

                    </div>

                @endif



                {{-- Registration form --}}
                <form
                    method="POST"
                    action="{{ route('customer.register') }}"
                    class="space-y-5"
                >

                    @csrf


                    {{-- First + Last Name --}}
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        {{-- First name --}}
                        <div>

                            <label
                                for="first_name"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                First Name
                            </label>


                            <div class="relative">

                                <div
                                    class="
                                        pointer-events-none
                                        absolute
                                        inset-y-0
                                        left-0
                                        flex
                                        items-center
                                        pl-4
                                        text-slate-400
                                    "
                                >

                                    <i
                                        data-lucide="user"
                                        class="h-5 w-5"
                                    ></i>

                                </div>


                                <input
                                    id="first_name"
                                    name="first_name"
                                    type="text"
                                    value="{{ old('first_name') }}"
                                    autocomplete="given-name"
                                    required
                                    placeholder="First name"
                                    class="
                                        w-full
                                        rounded-2xl
                                        border
                                        border-slate-200
                                        bg-white
                                        py-3.5
                                        pl-12
                                        pr-4
                                        text-sm
                                        text-slate-900
                                        shadow-sm
                                        outline-none
                                        transition
                                        placeholder:text-slate-400
                                        hover:border-slate-300
                                        focus:border-brand-500
                                        focus:ring-4
                                        focus:ring-brand-500/10
                                    "
                                >

                            </div>

                        </div>


                        {{-- Last name --}}
                        <div>

                            <label
                                for="last_name"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Last Name
                            </label>


                            <div class="relative">

                                <div
                                    class="
                                        pointer-events-none
                                        absolute
                                        inset-y-0
                                        left-0
                                        flex
                                        items-center
                                        pl-4
                                        text-slate-400
                                    "
                                >

                                    <i
                                        data-lucide="user"
                                        class="h-5 w-5"
                                    ></i>

                                </div>


                                <input
                                    id="last_name"
                                    name="last_name"
                                    type="text"
                                    value="{{ old('last_name') }}"
                                    autocomplete="family-name"
                                    required
                                    placeholder="Last name"
                                    class="
                                        w-full
                                        rounded-2xl
                                        border
                                        border-slate-200
                                        bg-white
                                        py-3.5
                                        pl-12
                                        pr-4
                                        text-sm
                                        text-slate-900
                                        shadow-sm
                                        outline-none
                                        transition
                                        placeholder:text-slate-400
                                        hover:border-slate-300
                                        focus:border-brand-500
                                        focus:ring-4
                                        focus:ring-brand-500/10
                                    "
                                >

                            </div>

                        </div>

                    </div>



                    {{-- Username --}}
                    <div>

                        <label
                            for="username"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Username
                        </label>


                        <div class="relative">

                            <div
                                class="
                                    pointer-events-none
                                    absolute
                                    inset-y-0
                                    left-0
                                    flex
                                    items-center
                                    pl-4
                                    text-slate-400
                                "
                            >

                                <i
                                    data-lucide="at-sign"
                                    class="h-5 w-5"
                                ></i>

                            </div>


                            <input
                                id="username"
                                name="username"
                                type="text"
                                value="{{ old('username') }}"
                                autocomplete="username"
                                required
                                placeholder="Choose a username"
                                class="
                                    w-full
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    bg-white
                                    py-3.5
                                    pl-12
                                    pr-4
                                    text-sm
                                    text-slate-900
                                    shadow-sm
                                    outline-none
                                    transition
                                    placeholder:text-slate-400
                                    hover:border-slate-300
                                    focus:border-brand-500
                                    focus:ring-4
                                    focus:ring-brand-500/10
                                "
                            >

                        </div>

                    </div>



                    {{-- Password --}}
                    <div>

                        <label
                            for="password"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Password
                        </label>


                        <div class="relative">

                            <div
                                class="
                                    pointer-events-none
                                    absolute
                                    inset-y-0
                                    left-0
                                    flex
                                    items-center
                                    pl-4
                                    text-slate-400
                                "
                            >

                                <i
                                    data-lucide="lock-keyhole"
                                    class="h-5 w-5"
                                ></i>

                            </div>


                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                required
                                placeholder="Create a password"
                                class="
                                    w-full
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    bg-white
                                    py-3.5
                                    pl-12
                                    pr-12
                                    text-sm
                                    text-slate-900
                                    shadow-sm
                                    outline-none
                                    transition
                                    placeholder:text-slate-400
                                    hover:border-slate-300
                                    focus:border-brand-500
                                    focus:ring-4
                                    focus:ring-brand-500/10
                                "
                            >


                            <button
                                id="togglePassword"
                                type="button"
                                class="
                                    absolute
                                    inset-y-0
                                    right-0
                                    flex
                                    items-center
                                    px-4
                                    text-slate-400
                                    transition
                                    hover:text-slate-700
                                "
                                aria-label="Show password"
                            >

                                <i
                                    data-lucide="eye"
                                    class="h-5 w-5"
                                ></i>

                            </button>

                        </div>

                    </div>



                    {{-- Confirm Password --}}
                    <div>

                        <label
                            for="password_confirmation"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Confirm Password
                        </label>


                        <div class="relative">

                            <div
                                class="
                                    pointer-events-none
                                    absolute
                                    inset-y-0
                                    left-0
                                    flex
                                    items-center
                                    pl-4
                                    text-slate-400
                                "
                            >

                                <i
                                    data-lucide="shield-check"
                                    class="h-5 w-5"
                                ></i>

                            </div>


                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                required
                                placeholder="Re-enter your password"
                                class="
                                    w-full
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    bg-white
                                    py-3.5
                                    pl-12
                                    pr-12
                                    text-sm
                                    text-slate-900
                                    shadow-sm
                                    outline-none
                                    transition
                                    placeholder:text-slate-400
                                    hover:border-slate-300
                                    focus:border-brand-500
                                    focus:ring-4
                                    focus:ring-brand-500/10
                                "
                            >


                            <button
                                id="toggleConfirmation"
                                type="button"
                                class="
                                    absolute
                                    inset-y-0
                                    right-0
                                    flex
                                    items-center
                                    px-4
                                    text-slate-400
                                    transition
                                    hover:text-slate-700
                                "
                                aria-label="Show password"
                            >

                                <i
                                    data-lucide="eye"
                                    class="h-5 w-5"
                                ></i>

                            </button>

                        </div>

                    </div>



                    {{-- Terms --}}
                    <div
                        class="
                            rounded-2xl
                            border
                            border-slate-200
                            bg-white
                            p-4
                        "
                    >

                        <label
                            class="
                                flex
                                cursor-pointer
                                items-start
                                gap-3
                            "
                        >

                            <input
                                type="checkbox"
                                name="terms"
                                value="1"
                                required
                                class="
                                    mt-0.5
                                    h-4
                                    w-4
                                    shrink-0
                                    rounded
                                    border-slate-300
                                    text-brand-700
                                    focus:ring-brand-500
                                "
                                {{ old('terms') ? 'checked' : '' }}
                            >


                            <span
                                class="
                                    text-xs
                                    leading-5
                                    text-slate-500
                                "
                            >

                                I agree to the

                                <a
                                    href="{{ route('terms') }}"
                                    target="_blank"
                                    class="
                                        font-bold
                                        text-brand-700
                                        hover:text-brand-800
                                    "
                                >
                                    Terms of Service
                                </a>

                                and

                                <a
                                    href="{{ route('privacy') }}"
                                    target="_blank"
                                    class="
                                        font-bold
                                        text-brand-700
                                        hover:text-brand-800
                                    "
                                >
                                    Privacy Policy
                                </a>.

                            </span>

                        </label>

                    </div>



                    {{-- Submit --}}
                    <button
                        type="submit"
                        class="
                            flex
                            w-full
                            items-center
                            justify-center
                            gap-2
                            rounded-2xl
                            bg-brand-700
                            px-5
                            py-3.5
                            text-sm
                            font-bold
                            text-white
                            shadow-sm
                            shadow-brand-700/20
                            transition
                            hover:bg-brand-800
                            focus:outline-none
                            focus:ring-4
                            focus:ring-brand-500/20
                            active:scale-[0.99]
                        "
                    >

                        Create Account

                        <i
                            data-lucide="arrow-right"
                            class="h-4 w-4"
                        ></i>

                    </button>

                </form>



                {{-- Login --}}
                <div
                    class="
                        mt-6
                        border-t
                        border-slate-200
                        pt-5
                        text-center
                    "
                >

                    <p class="text-sm text-slate-500">

                        Already have an account?

                        <a
                            href="{{ route('login') }}"
                            class="
                                ml-1
                                font-bold
                                text-brand-700
                                transition
                                hover:text-brand-800
                            "
                        >
                            Sign in
                        </a>

                    </p>

                </div>



                {{-- Back --}}
                <div class="mt-5 text-center">

                    <a
                        href="{{ route('landing') }}"
                        class="
                            inline-flex
                            items-center
                            gap-2
                            text-xs
                            font-semibold
                            text-slate-400
                            transition
                            hover:text-slate-700
                        "
                    >

                        <i
                            data-lucide="arrow-left"
                            class="h-3.5 w-3.5"
                        ></i>

                        Back to Spa Alexandria

                    </a>

                </div>

            </div>

        </section>

    </main>



    <script>
        document.addEventListener('DOMContentLoaded', function () {

            if (window.lucide) {
                lucide.createIcons();
            }


            function setupPasswordToggle(buttonId, inputId) {

                const button =
                    document.getElementById(buttonId);

                const input =
                    document.getElementById(inputId);


                if (!button || !input) {
                    return;
                }


                button.addEventListener('click', function () {

                    const shouldShow =
                        input.type === 'password';


                    input.type =
                        shouldShow
                            ? 'text'
                            : 'password';


                    button.setAttribute(
                        'aria-label',
                        shouldShow
                            ? 'Hide password'
                            : 'Show password'
                    );


                    button.innerHTML =
                        shouldShow
                            ? '<i data-lucide="eye-off" class="h-5 w-5"></i>'
                            : '<i data-lucide="eye" class="h-5 w-5"></i>';


                    if (window.lucide) {
                        lucide.createIcons();
                    }

                });

            }


            setupPasswordToggle(
                'togglePassword',
                'password'
            );


            setupPasswordToggle(
                'toggleConfirmation',
                'password_confirmation'
            );

        });
    </script>

</body>
</html>