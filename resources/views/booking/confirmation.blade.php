```blade
<!DOCTYPE html>
<html lang="en" class="{{ session('dark_mode') === 'enabled' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking Request Received - Spa Alexandria</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Preline -->
    <script src="https://cdn.jsdelivr.net/npm/preline@2.7.0/dist/preline.min.js"></script>

    <!-- Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        teal: {
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
        };

        if (localStorage.getItem('darkMode') === 'enabled') {
            document.documentElement.classList.add('dark');
        }
    </script>

    <style>
        * {
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        .success-ring {
            animation: successRing 0.7s ease-out forwards;
        }

        .success-check {
            animation: successCheck 0.45s 0.35s ease-out both;
        }

        @keyframes successRing {
            from {
                transform: scale(0.7);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes successCheck {
            from {
                stroke-dashoffset: 40;
                opacity: 0;
            }

            to {
                stroke-dashoffset: 0;
                opacity: 1;
            }
        }

        .soft-grid {
            background-image:
                linear-gradient(rgba(20, 184, 166, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(20, 184, 166, 0.035) 1px, transparent 1px);
            background-size: 32px 32px;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-300">

    <!-- Background -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-32 -left-32 w-80 h-80 bg-teal-200/20 dark:bg-teal-900/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -right-32 w-96 h-96 bg-teal-200/20 dark:bg-teal-900/10 rounded-full blur-3xl"></div>
        <div class="absolute inset-0 soft-grid"></div>
    </div>

    <!-- Header -->
    <header class="relative border-b border-gray-200/80 dark:border-gray-800 bg-white/80 dark:bg-gray-950/80 backdrop-blur-xl">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3">
            <div class="flex items-center justify-between gap-4">

                <!-- Brand -->
                <a href="{{ route('landing') }}" class="flex items-center gap-2.5 group">

                    <div class="w-9 h-9 rounded-lg bg-teal-600 text-white flex items-center justify-center shadow-sm group-hover:bg-teal-700 transition">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                    </div>

                    <div>
                        <div class="font-bold text-sm text-gray-900 dark:text-white leading-tight">
                            Spa Alexandria
                        </div>

                        <div class="text-[10px] text-gray-500 dark:text-gray-400">
                            Relax. Restore. Rejuvenate.
                        </div>
                    </div>

                </a>

                <!-- Dark Mode -->
                <button
                    type="button"
                    onclick="toggleDarkMode()"
                    class="w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 hover:border-teal-200 dark:hover:border-teal-800 transition"
                    aria-label="Toggle dark mode"
                >
                    <i data-lucide="sun" class="w-4 h-4 hidden dark:block"></i>
                    <i data-lucide="moon" class="w-4 h-4 dark:hidden"></i>
                </button>

            </div>
        </div>
    </header>


    <!-- Main -->
    <main class="relative min-h-[calc(100vh-61px)] flex items-center py-5 sm:py-7">

        <div class="max-w-5xl w-full mx-auto px-4 sm:px-6">

            <!-- Compact Success Header -->
            <div class="text-center max-w-2xl mx-auto mb-5 sm:mb-6">

                <!-- Success Icon -->
                <div class="relative mx-auto w-14 h-14 mb-3">

                    <div class="success-ring absolute inset-0 rounded-full bg-teal-100 dark:bg-teal-900/40"></div>

                    <div class="absolute inset-1.5 rounded-full bg-teal-600 flex items-center justify-center shadow-md shadow-teal-600/20">

                        <svg
                            class="w-7 h-7 text-white"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                class="success-check"
                                d="M5 12.5l4.5 4.5L19 7.5"
                                stroke-dasharray="40"
                                stroke-dashoffset="40"
                            />
                        </svg>

                    </div>

                </div>

                <!-- Badge -->
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-teal-50 dark:bg-teal-900/30 border border-teal-100 dark:border-teal-800 text-teal-700 dark:text-teal-300 text-[10px] font-semibold mb-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                    Booking request received
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                    You're all set!
                </h1>

                <p class="mt-1.5 text-xs sm:text-sm leading-5 text-gray-600 dark:text-gray-400">
                    Thank you for choosing Spa Alexandria. Your appointment request has been received and is waiting for confirmation.
                </p>

            </div>


            <!-- Main Card -->
            <div class="max-w-3xl mx-auto">

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl sm:rounded-2xl shadow-lg shadow-gray-900/5 dark:shadow-black/20 overflow-hidden">

                    <!-- Confirmation Notice -->
                    <div class="px-4 py-3.5 sm:px-5 sm:py-4 bg-teal-50/70 dark:bg-teal-950/30 border-b border-teal-100 dark:border-teal-900">

                        <div class="flex gap-3">

                            <div class="shrink-0 w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/60 flex items-center justify-center">
                                <i data-lucide="phone-call" class="w-4 h-4 text-teal-700 dark:text-teal-300"></i>
                            </div>

                            <div class="min-w-0">
                                <h2 class="text-sm font-semibold text-teal-900 dark:text-teal-100">
                                    We'll contact you to confirm
                                </h2>

                                <p class="mt-0.5 text-xs leading-4 text-teal-800/80 dark:text-teal-200/80">
                                    We will call you at
                                    <span class="font-bold text-teal-700 dark:text-teal-300">
                                        {{ $appointment->customer->phone_number }}
                                    </span>
                                    to confirm your appointment.
                                </p>
                            </div>

                        </div>

                    </div>


                    <!-- Reference -->
                    <div class="px-4 py-3.5 sm:px-5 sm:py-4 border-b border-gray-100 dark:border-gray-800">

                        <div class="flex items-center justify-between gap-3">

                            <div>
                                <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-400 dark:text-gray-500">
                                    Booking reference
                                </p>

                                <p class="mt-0.5 text-lg font-bold font-mono tracking-tight text-gray-900 dark:text-white">
                                    #{{ $appointment->id }}
                                </p>
                            </div>

                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-900/40 text-amber-700 dark:text-amber-300 text-[10px] font-semibold whitespace-nowrap">
                                <i data-lucide="clock-3" class="w-3.5 h-3.5"></i>
                                Pending confirmation
                            </div>

                        </div>

                    </div>


                    <!-- Appointment Details -->
                    <div class="px-4 py-4 sm:px-5 sm:py-4">

                        <div class="grid sm:grid-cols-2 gap-2.5">

                            <!-- Date -->
                            <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 p-3">

                                <div class="flex items-center gap-2.5">

                                    <div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex items-center justify-center">
                                        <i data-lucide="calendar-days" class="w-4 h-4 text-teal-600 dark:text-teal-400"></i>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400">
                                            Date
                                        </p>

                                        <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('F d, Y') }}
                                        </p>
                                    </div>

                                </div>

                            </div>


                            <!-- Time -->
                            <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 p-3">

                                <div class="flex items-center gap-2.5">

                                    <div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex items-center justify-center">
                                        <i data-lucide="clock-4" class="w-4 h-4 text-teal-600 dark:text-teal-400"></i>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400">
                                            Time
                                        </p>

                                        <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}
                                            -
                                            {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
                                        </p>
                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- Services -->
                        <div class="mt-3 rounded-lg border border-gray-200 dark:border-gray-800 overflow-hidden">

                            <div class="px-3.5 py-2 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800">

                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400"></i>

                                    <h3 class="text-xs font-semibold text-gray-900 dark:text-white">
                                        Selected services
                                    </h3>
                                </div>

                            </div>

                            <div class="px-3.5 py-2.5">

                                <div class="space-y-1.5">

                                    @foreach($appointment->services as $service)

                                        <div class="flex items-center justify-between gap-3">

                                            <div class="flex items-center gap-2 min-w-0">

                                                <div class="w-6 h-6 shrink-0 rounded-md bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center">
                                                    <i data-lucide="check" class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400"></i>
                                                </div>

                                                <span class="text-xs font-medium text-gray-700 dark:text-gray-200 truncate">
                                                    {{ $service->name }}
                                                </span>

                                            </div>

                                            @if(isset($service->price))
                                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                    ₱{{ number_format($service->price, 2) }}
                                                </span>
                                            @endif

                                        </div>

                                    @endforeach

                                </div>

                            </div>

                        </div>


                        <!-- Total -->
                        <div class="mt-2.5 flex items-center justify-between rounded-lg bg-gray-900 dark:bg-gray-800 px-3.5 py-3">

                            <div>
                                <p class="text-[10px] text-gray-400">
                                    Estimated total
                                </p>

                                <p class="mt-0.5 text-xs font-medium text-white">
                                    Appointment total
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-lg font-bold text-teal-400">
                                    ₱{{ number_format($appointment->total_price, 2) }}
                                </p>
                            </div>

                        </div>

                    </div>


                    <!-- What Happens Next -->
                    <div class="px-4 pb-4 sm:px-5">

                        <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-800 px-3.5 py-2.5">

                            <div class="flex gap-2.5">

                                <div class="shrink-0">
                                    <div class="w-7 h-7 rounded-md bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex items-center justify-center">
                                        <i data-lucide="info" class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400"></i>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-xs font-semibold text-gray-900 dark:text-white">
                                        What happens next?
                                    </h3>

                                    <p class="mt-0.5 text-[11px] sm:text-xs leading-4 text-gray-500 dark:text-gray-400">
                                        Our receptionist will contact you using the phone number you provided. Your appointment becomes confirmed after the details have been reviewed with you.
                                    </p>
                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- Actions -->
                    <div class="px-4 py-4 sm:px-5 bg-gray-50/70 dark:bg-gray-950/40 border-t border-gray-200 dark:border-gray-800">

                        <div class="grid sm:grid-cols-2 gap-2.5">

                            <!-- Book Another -->
                            <a
                                href="{{ route('booking.wizard') }}"
                                class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white text-xs font-semibold shadow-sm shadow-teal-600/20 transition"
                            >
                                <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i>
                                Book another appointment
                            </a>


                            @auth

                                @php
                                    $user = auth()->user();
                                    $role = strtolower($user?->roles()->first()->name ?? '');
                                @endphp


                                @if($role === 'admin')

                                    <a
                                        href="{{ route('admin-dashboard') }}"
                                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 text-xs font-semibold transition"
                                    >
                                        <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
                                        Admin dashboard
                                    </a>


                                @elseif($role === 'receptionist')

                                    <a
                                        href="{{ route('receptionist.dashboard') }}"
                                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 text-xs font-semibold transition"
                                    >
                                        <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
                                        Receptionist dashboard
                                    </a>


                                @elseif($role === 'staff')

                                    <a
                                        href="{{ route('staff.dashboard') }}"
                                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 text-xs font-semibold transition"
                                    >
                                        <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
                                        Staff dashboard
                                    </a>


                                @else

                                    <a
                                        href="{{ route('customer-dashboard') }}"
                                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 text-xs font-semibold transition"
                                    >
                                        <i data-lucide="user-round" class="w-3.5 h-3.5"></i>
                                        My dashboard
                                    </a>

                                @endif


                            @else

                                <a
                                    href="{{ route('login') }}"
                                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 text-xs font-semibold transition"
                                >
                                    <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
                                    Sign in
                                </a>

                            @endauth

                        </div>


                        @guest

                            <p class="mt-2.5 text-center text-[11px] text-gray-500 dark:text-gray-400">

                                Already have an account?

                                <a
                                    href="{{ route('login') }}"
                                    class="font-semibold text-teal-600 dark:text-teal-400 hover:underline"
                                >
                                    Sign in
                                </a>

                                to manage your bookings and save your details for your next visit.

                            </p>

                        @endguest

                    </div>

                </div>


                <!-- Footer Notes -->
                <div class="mt-3 text-center space-y-1.5">

                    <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5 text-[10px] text-gray-400 dark:text-gray-500">

                        <span class="inline-flex items-center gap-1">
                            <i data-lucide="clock" class="w-3 h-3"></i>
                            Arrive 15 minutes early
                        </span>

                        <span class="hidden sm:block">•</span>

                        <span class="inline-flex items-center gap-1">
                            <i data-lucide="phone" class="w-3 h-3"></i>
                            Confirmation by phone
                        </span>

                        <span class="hidden sm:block">•</span>

                        <span class="inline-flex items-center gap-1">
                            <i data-lucide="calendar-x" class="w-3 h-3"></i>
                            Cancel 2 hours in advance
                        </span>

                    </div>

                    <p class="text-[9px] text-gray-400 dark:text-gray-600">
                        Please keep your booking reference for future inquiries.
                    </p>

                </div>

            </div>

        </div>

    </main>


    <!-- Scripts -->
    <script>
        lucide.createIcons();

        function toggleDarkMode() {
            const html = document.documentElement;

            html.classList.toggle('dark');

            if (html.classList.contains('dark')) {
                localStorage.setItem('darkMode', 'enabled');
            } else {
                localStorage.setItem('darkMode', 'disabled');
            }

            lucide.createIcons();
        }
    </script>

</body>
</html>
```
