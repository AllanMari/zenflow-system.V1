<!DOCTYPE html>
<html lang="en">

<head>
    <script>
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | Spa Alexandria</title>

    {{-- Fonts: Fraunces (headings) + Inter (UI) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    {{-- Tailwind (pinned) --}}
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['Fraunces', 'ui-serif', 'Georgia', 'serif'],
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

    {{-- Alpine.js (toggles, caps lock, loading) + Lucide (icons) + GSAP (entrance), all pinned --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.469.0/dist/umd/lucide.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        body { font-family: Inter, ui-sans-serif, system-ui, sans-serif; }

        /* Form settles in on page load */
        .form-in { animation: formIn .6s cubic-bezier(.2, .7, .2, 1) both; }
        @keyframes formIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: none; }
        }

        /* Left panel ambience */
        .ripple { animation: ripple 9s ease-in-out infinite; }
        @keyframes ripple {
            0%   { transform: scale(.82); opacity: .0; }
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
            .form-in, .ripple, .blob { animation: none; }
            .ripple { opacity: .6; }
        }

        .form-scroll::-webkit-scrollbar { width: 6px; }
        .form-scroll::-webkit-scrollbar-track { background: transparent; }
        .form-scroll::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, .35); border-radius: 999px; }
    </style>
</head>

<body class="min-h-screen bg-white text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">

    <main class="min-h-screen lg:grid lg:h-screen lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)] lg:overflow-hidden">

        {{-- =====================================================
             LEFT: VISUAL PANEL
        ====================================================== --}}
        <section class="hidden p-4 lg:block lg:h-screen">

            @php
                $registerImage = null;

                if (isset($hero) && !empty($hero->image)) {
                    $registerImage = trim($hero->image);

                    if (!preg_match('/^https?:\/\//i', $registerImage)) {
                        $registerImage = preg_replace('#^/?storage/#i', '', $registerImage);
                        $registerImage = asset('storage/' . ltrim($registerImage, '/'));
                    }
                }
            @endphp

            <div class="relative h-full overflow-hidden rounded-[2rem] bg-brand-900">

                @if($registerImage)
                    <img src="{{ $registerImage }}" alt="Spa Alexandria" class="absolute inset-0 h-full w-full object-cover">
                    <div class="absolute inset-0 bg-brand-900/30 mix-blend-multiply"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-950/85 via-brand-950/25 to-brand-950/10"></div>
                @else
                    {{-- Fallback: layered teal gradient, drifting light, water ripples, grain --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-brand-600 via-brand-800 to-brand-950"></div>

                    <div class="blob absolute -left-24 -top-24 h-[28rem] w-[28rem] rounded-full bg-brand-400/30 blur-3xl"></div>
                    <div class="blob absolute -bottom-32 left-1/3 h-[26rem] w-[26rem] rounded-full bg-brand-300/20 blur-3xl" style="animation-delay:-9s"></div>

                    <div class="pointer-events-none absolute -bottom-72 -right-72 h-[44rem] w-[44rem]" aria-hidden="true">
                        <div class="ripple absolute inset-[30%] rounded-full border border-white/25"></div>
                        <div class="ripple absolute inset-[20%] rounded-full border border-white/20" style="animation-delay:-1.8s"></div>
                        <div class="ripple absolute inset-[10%] rounded-full border border-white/15" style="animation-delay:-3.6s"></div>
                        <div class="ripple absolute inset-0 rounded-full border border-white/10" style="animation-delay:-5.4s"></div>
                    </div>

                    <div class="grain absolute inset-0"></div>
                @endif

                {{-- Brand --}}
                <a href="{{ route('landing') }}" data-hero class="absolute left-8 top-8 z-10 flex items-center gap-3 xl:left-10 xl:top-10">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white/15 text-white ring-1 ring-white/30 backdrop-blur">
                        <i data-lucide="sparkles" class="h-5 w-5"></i>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-white">Spa Alexandria</span>
                        <span class="block text-xs text-white/70">Wellness &amp; Care</span>
                    </span>
                </a>

                {{-- Headline --}}
                <div class="absolute left-8 top-1/2 z-10 max-w-lg -translate-y-1/2 xl:left-10">
                    <h1 data-hero class="font-display text-5xl font-medium leading-[1.05] tracking-tight text-white xl:text-6xl">
                        Make every visit easier.
                    </h1>
                    <p data-hero class="mt-5 max-w-md text-base leading-7 text-white/80">
                        Create your Spa Alexandria account to keep your customer details together and manage your appointments in one place.
                    </p>
                </div>

                {{-- Copyright --}}
                <p data-hero class="absolute bottom-6 left-8 z-10 text-xs text-white/60 xl:left-10">
                    &copy; {{ date('Y') }} Spa Alexandria
                </p>
            </div>
        </section>


        {{-- =====================================================
             RIGHT: REGISTER FORM
        ====================================================== --}}
        <section class="form-scroll flex min-h-screen w-full items-center justify-center px-5 py-10 sm:px-8 lg:h-screen lg:overflow-y-auto lg:px-12 xl:px-20">

            <div class="form-in w-full max-w-md">

                {{-- Mobile brand --}}
                <a href="{{ route('landing') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-700 text-white dark:bg-brand-600">
                        <i data-lucide="sparkles" class="h-5 w-5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold leading-5 text-slate-900 dark:text-white">Spa Alexandria</span>
                        <span class="block text-xs leading-4 text-slate-500 dark:text-slate-400">Wellness &amp; Care</span>
                    </span>
                </a>

                {{-- Header --}}
                <div class="mb-7">
                    <h2 class="font-display text-4xl font-medium tracking-tight text-slate-900 dark:text-white">
                        Create your account
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                        Register as a customer to manage your Spa Alexandria appointments.
                    </p>
                </div>


                {{-- Validation errors --}}
                @if($errors->any())
                    <div role="alert" class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800/40 dark:bg-red-900/20 dark:text-red-400">
                        <i data-lucide="circle-alert" class="mt-0.5 h-5 w-5 shrink-0"></i>
                        <div class="space-y-1">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif


                {{-- Registration form --}}
                <form
                    method="POST"
                    action="{{ route('customer.register') }}"
                    class="space-y-5"
                    x-data="{ showPassword: false, showConfirm: false, caps: '', loading: false }"
                    x-on:submit="loading = true"
                    x-on:pageshow.window="loading = false"
                >
                    @csrf

                    {{-- First + Last name --}}
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        <div>
                            <label for="first_name" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                First name
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 dark:text-slate-500">
                                    <i data-lucide="user" class="h-[18px] w-[18px]"></i>
                                </span>
                                <input
                                    id="first_name"
                                    name="first_name"
                                    type="text"
                                    value="{{ old('first_name') }}"
                                    autocomplete="given-name"
                                    required
                                    placeholder="First name"
                                    class="h-12 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500 dark:hover:border-slate-600"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="last_name" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Last name
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 dark:text-slate-500">
                                    <i data-lucide="user" class="h-[18px] w-[18px]"></i>
                                </span>
                                <input
                                    id="last_name"
                                    name="last_name"
                                    type="text"
                                    value="{{ old('last_name') }}"
                                    autocomplete="family-name"
                                    required
                                    placeholder="Last name"
                                    class="h-12 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500 dark:hover:border-slate-600"
                                >
                            </div>
                        </div>

                    </div>

                    {{-- Username --}}
                    <div>
                        <label for="username" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Username
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 dark:text-slate-500">
                                <i data-lucide="at-sign" class="h-[18px] w-[18px]"></i>
                            </span>
                            <input
                                id="username"
                                name="username"
                                type="text"
                                value="{{ old('username') }}"
                                autocomplete="username"
                                required
                                placeholder="Choose a username"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500 dark:hover:border-slate-600"
                            >
                        </div>
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Password
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 dark:text-slate-500">
                                <i data-lucide="lock-keyhole" class="h-[18px] w-[18px]"></i>
                            </span>
                            <input
                                id="password"
                                name="password"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                type="password"
                                autocomplete="new-password"
                                required
                                placeholder="Create a password"
                                x-on:keydown="caps = ($event.getModifierState && $event.getModifierState('CapsLock')) ? 'password' : ''"
                                x-on:keyup="caps = ($event.getModifierState && $event.getModifierState('CapsLock')) ? 'password' : ''"
                                x-on:blur="caps = ''"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-12 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500 dark:hover:border-slate-600"
                            >
                            <button
                                type="button"
                                x-on:click="showPassword = !showPassword"
                                x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
                                aria-label="Show password"
                                class="absolute inset-y-0 right-0 flex items-center rounded-r-xl px-4 text-slate-400 transition hover:text-slate-700 focus:outline-none focus-visible:text-brand-700 dark:text-slate-500 dark:hover:text-slate-300 dark:focus-visible:text-brand-400"
                            >
                                <i data-lucide="eye" class="h-[18px] w-[18px]" x-show="!showPassword"></i>
                                <i data-lucide="eye-off" class="h-[18px] w-[18px]" x-show="showPassword" x-cloak></i>
                            </button>
                        </div>
                        <p x-show="caps === 'password'" x-cloak role="status" class="mt-2 flex items-center gap-1.5 text-xs font-medium text-amber-600 dark:text-amber-400">
                            <i data-lucide="arrow-big-up-dash" class="h-4 w-4"></i>
                            Caps Lock is on
                        </p>
                    </div>

                    {{-- Confirm password --}}
                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Confirm password
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 dark:text-slate-500">
                                <i data-lucide="shield-check" class="h-[18px] w-[18px]"></i>
                            </span>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                x-bind:type="showConfirm ? 'text' : 'password'"
                                type="password"
                                autocomplete="new-password"
                                required
                                placeholder="Re-enter your password"
                                x-on:keydown="caps = ($event.getModifierState && $event.getModifierState('CapsLock')) ? 'confirm' : ''"
                                x-on:keyup="caps = ($event.getModifierState && $event.getModifierState('CapsLock')) ? 'confirm' : ''"
                                x-on:blur="caps = ''"
                                class="h-12 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-12 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500 dark:hover:border-slate-600"
                            >
                            <button
                                type="button"
                                x-on:click="showConfirm = !showConfirm"
                                x-bind:aria-label="showConfirm ? 'Hide password' : 'Show password'"
                                aria-label="Show password"
                                class="absolute inset-y-0 right-0 flex items-center rounded-r-xl px-4 text-slate-400 transition hover:text-slate-700 focus:outline-none focus-visible:text-brand-700 dark:text-slate-500 dark:hover:text-slate-300 dark:focus-visible:text-brand-400"
                            >
                                <i data-lucide="eye" class="h-[18px] w-[18px]" x-show="!showConfirm"></i>
                                <i data-lucide="eye-off" class="h-[18px] w-[18px]" x-show="showConfirm" x-cloak></i>
                            </button>
                        </div>
                        <p x-show="caps === 'confirm'" x-cloak role="status" class="mt-2 flex items-center gap-1.5 text-xs font-medium text-amber-600 dark:text-amber-400">
                            <i data-lucide="arrow-big-up-dash" class="h-4 w-4"></i>
                            Caps Lock is on
                        </p>
                    </div>

                    {{-- Terms --}}
                    <label class="flex cursor-pointer items-start gap-3">
                        <input
                            type="checkbox"
                            name="terms"
                            value="1"
                            required
                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 accent-brand-600 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:border-slate-600 dark:focus:ring-offset-slate-950"
                            {{ old('terms') ? 'checked' : '' }}
                        >
                        <span class="text-xs leading-5 text-slate-500 dark:text-slate-400">
                            I agree to the
                            <a href="{{ route('terms') }}" target="_blank" rel="noopener" class="font-semibold text-brand-700 underline-offset-4 hover:underline dark:text-brand-400">Terms of Service</a>
                            and
                            <a href="{{ route('privacy') }}" target="_blank" rel="noopener" class="font-semibold text-brand-700 underline-offset-4 hover:underline dark:text-brand-400">Privacy Policy</a>.
                        </span>
                    </label>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        x-bind:disabled="loading"
                        x-bind:aria-busy="loading"
                        class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/30 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-80 dark:bg-brand-600 dark:hover:bg-brand-500"
                    >
                        <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity=".3" stroke-width="3"></circle>
                            <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                        <span x-text="loading ? 'Creating account...' : 'Create account'">Create account</span>
                        <i data-lucide="arrow-right" class="h-4 w-4" x-show="!loading"></i>
                    </button>
                </form>


                {{-- Sign in --}}
                <p class="mt-7 text-center text-sm text-slate-500 dark:text-slate-400">
                    Already have an account?
                    <a href="{{ route('login') }}" class="ml-1 font-semibold text-brand-700 underline-offset-4 transition hover:underline dark:text-brand-400">
                        Sign in
                    </a>
                </p>


                {{-- Back --}}
                <div class="mt-6 text-center">
                    <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 text-xs font-medium text-slate-500 transition hover:text-slate-800 dark:text-slate-500 dark:hover:text-slate-300">
                        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                        Back to Spa Alexandria
                    </a>
                </div>

            </div>
        </section>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) lucide.createIcons();

            if (window.gsap && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                gsap.from('[data-hero]', {
                    y: 18,
                    opacity: 0,
                    duration: 0.9,
                    ease: 'power3.out',
                    stagger: 0.12
                });
            }
        });
    </script>

</body>
</html>