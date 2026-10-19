<!DOCTYPE html>
<html lang="en" class="{{ session('dark_mode') === 'enabled' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking Request Received — Spa Alexandria</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Inter Font -->
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
        .soft-grid {
            background-image:
                linear-gradient(rgba(20, 184, 166, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(20, 184, 166, 0.035) 1px, transparent 1px);
            background-size: 32px 32px;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-300">

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

    $referenceNumber = '#SPA-' . $appointment->id;
    $clientName = $appointment->client_name ?? 'Guest';
    $contactPhone = $appointment->guest_phone ?: ($appointment->customer?->phone_number ?: 'N/A');
    $hasPhone = !empty($contactPhone) && $contactPhone !== 'N/A';
    $appointmentDate = \Carbon\Carbon::parse($appointment->appointment_date)->format('F d, Y');
    $startTime = \Carbon\Carbon::parse($appointment->start_time)->format('g:i A');
    $endTime = \Carbon\Carbon::parse($appointment->end_time)->format('g:i A');
    $hasRequestedRoom = !empty($appointment->requestedRoom);
    $hasDeposit = !empty($appointment->deposit_required) && (float)$appointment->deposit_required > 0;
@endphp

<!-- Ambient Background -->
<div class="fixed inset-0 pointer-events-none overflow-hidden">
    <div class="absolute -top-32 -left-32 w-80 h-80 bg-teal-200/20 dark:bg-teal-900/10 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-40 -right-32 w-96 h-96 bg-teal-200/20 dark:bg-teal-900/10 rounded-full blur-3xl"></div>
    <div class="absolute inset-0 soft-grid"></div>
</div>

<!-- Navigation Header -->
<header class="sticky top-0 z-50 border-b border-slate-200/80 dark:border-slate-700/80 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">

        <a href="{{ $backRoute }}" class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl text-white shadow-sm" style="background-color: #159582">
                <i data-lucide="sparkles" class="h-5 w-5"></i>
            </div>

            <div class="hidden sm:block">
                <div class="text-sm font-bold tracking-tight text-slate-900 dark:text-slate-100">
                    Spa Alexandria
                </div>

                <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                    Wellness &amp; Relaxation
                </div>
            </div>
        </a>

        <div class="flex items-center gap-4">
            <!-- Dark Mode Toggle -->
            <button type="button" onclick="toggleDarkMode()" class="w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:text-teal-600 dark:hover:text-teal-400 hover:border-teal-200 dark:hover:border-teal-700 transition" aria-label="Toggle dark mode">
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

<!-- Main Container -->
<main class="relative min-h-[calc(100vh-65px)] flex items-center justify-center py-8 px-4 sm:px-6">
    <div class="max-w-2xl w-full mx-auto space-y-6">

        <!-- Status Header Card -->
        <div class="text-center space-y-3">
            <!-- Icon Badge -->
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40 shadow-sm">
                <i data-lucide="clock-3" class="w-7 h-7"></i>
            </div>

            <!-- Lifecycle Status Badge -->
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Pending Receptionist Confirmation
                </span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                Booking Request Received
            </h1>

            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 max-w-lg mx-auto leading-relaxed">
                Thank you for your booking request! Our receptionist must review your appointment details and contact you before your booking is confirmed.
            </p>
        </div>

        <!-- Appointment Details Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            
            <!-- Reference & Notice Top Bar -->
            <div class="px-5 py-4 bg-teal-50/70 dark:bg-teal-950/30 border-b border-teal-100 dark:border-teal-900/50 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-teal-100 dark:bg-teal-900/60 text-teal-700 dark:text-teal-300 flex items-center justify-center shrink-0">
                        <i data-lucide="phone-call" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-teal-900 dark:text-teal-100">
                            We will call you to confirm
                        </div>
                        <div class="text-xs text-teal-800/80 dark:text-teal-200/80">
                            Our team will call <span class="font-bold text-teal-700 dark:text-teal-300">{{ $contactPhone }}</span> to verify your slot.
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-teal-700 dark:text-teal-400">
                        Reference
                    </span>
                    <div class="font-mono font-bold text-sm text-gray-900 dark:text-white">
                        {{ $referenceNumber }}
                    </div>
                </div>
            </div>

            <!-- Content Body -->
            <div class="p-5 sm:p-6 space-y-6">

                <!-- Client Info & Room Preference -->
                <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-700/60">
                    <div class="space-y-1">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Guest Details
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="user" class="w-4 h-4 text-teal-600 dark:text-teal-400"></i>
                            <span class="font-bold text-sm text-gray-900 dark:text-white">
                                {{ $clientName }}
                            </span>
                        </div>
                    </div>

                    @if($hasRequestedRoom)
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200 dark:bg-teal-900/30 dark:text-teal-300 dark:border-teal-800">
                            <i data-lucide="door-closed" class="w-3.5 h-3.5 text-teal-500"></i>
                            <span>Preferred Room: {{ $appointment->requestedRoom->name }}</span>
                        </div>
                    @endif
                </div>

                <!-- Date & Time Grid -->
                <div class="grid sm:grid-cols-2 gap-3">
                    <!-- Date -->
                    <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700/70 bg-gray-50/70 dark:bg-gray-800/50 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex items-center justify-center shrink-0">
                            <i data-lucide="calendar" class="w-4 h-4 text-teal-600 dark:text-teal-400"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                Appointment Date
                            </p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ $appointmentDate }}
                            </p>
                        </div>
                    </div>

                    <!-- Time -->
                    <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-700/70 bg-gray-50/70 dark:bg-gray-800/50 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex items-center justify-center shrink-0">
                            <i data-lucide="clock" class="w-4 h-4 text-teal-600 dark:text-teal-400"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                Scheduled Time
                            </p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ $startTime }} – {{ $endTime }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Selected Services -->
                <div class="space-y-2">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400"></i>
                        <span>Selected Services</span>
                    </div>

                    <div class="rounded-xl border border-gray-200 dark:border-gray-700/70 overflow-hidden divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach($appointment->services as $service)
                            <div class="px-4 py-3 flex items-center justify-between gap-3 bg-white dark:bg-gray-800/70 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                                <div class="min-w-0 flex items-center gap-2.5">
                                    <div class="w-5 h-5 rounded-md bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate">
                                        {{ $service->name }}
                                    </span>
                                    @if(!empty($service->duration_minutes))
                                        <span class="text-[10px] text-gray-400 dark:text-gray-500">
                                            ({{ $service->duration_minutes }} mins)
                                        </span>
                                    @endif
                                </div>
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 shrink-0 font-mono">
                                    ₱{{ number_format($service->pivot->price_at_booking ?? $service->price, 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Financial / Total Summary -->
                <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700/70 bg-gray-50 dark:bg-gray-900/50 space-y-2">
                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                        <span>Estimated Total</span>
                        <span class="font-bold text-base font-mono text-teal-600 dark:text-teal-400">
                            ₱{{ number_format($appointment->total_price, 2) }}
                        </span>
                    </div>

                    @if($hasDeposit)
                        <div class="flex items-center justify-between text-xs text-amber-700 dark:text-amber-400 pt-2 border-t border-gray-200 dark:border-gray-700/70">
                            <span class="flex items-center gap-1.5 font-medium">
                                <i data-lucide="info" class="w-3.5 h-3.5"></i>
                                Deposit Required to Confirm:
                            </span>
                            <span class="font-bold font-mono">
                                ₱{{ number_format($appointment->deposit_required, 2) }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Terms & Policies Notice -->
                <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700/70 bg-slate-50 dark:bg-slate-900/40 text-xs text-slate-600 dark:text-slate-400 leading-relaxed flex items-start gap-2.5">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0 mt-0.5"></i>
                    <div>
                        <span class="font-semibold text-slate-700 dark:text-slate-300">Important Reminders:</span>
                        All appointments require confirmation via phone call from our receptionist. Please arrive within 15 minutes of your scheduled time, and notify us at least 2 hours in advance if you need to cancel or reschedule.
                    </div>
                </div>

            </div>

            <!-- Action Buttons Footer -->
            <div class="px-5 py-4 bg-gray-50 dark:bg-gray-900/70 border-t border-gray-100 dark:border-gray-700/70 space-y-3">
                <div class="flex flex-col sm:flex-row gap-3">
                    <!-- Book Another -->
                    <a
                        href="{{ route('booking.wizard') }}"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-semibold text-xs transition shadow-sm"
                    >
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Book Another Appointment</span>
                    </a>

                    <!-- Contextual Dashboard or Home Link -->
                    @auth
                        @php
                            $user = auth()->user();
                            $isCustomer = $user?->roles()->where('name', 'customer')->exists();
                            $isReceptionist = $user?->roles()->where('name', 'receptionist')->exists();
                            $isAdmin = $user?->roles()->where('name', 'admin')->exists();
                        @endphp

                        @if($isCustomer)
                            <a
                                href="{{ route('customer.bookings') }}"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold text-xs transition shadow-sm"
                            >
                                <i data-lucide="calendar-days" class="w-4 h-4"></i>
                                <span>View My Bookings</span>
                            </a>
                        @elseif($isReceptionist)
                            <a
                                href="{{ route('receptionist.dashboard') }}"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold text-xs transition shadow-sm"
                            >
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                <span>Receptionist Desk</span>
                            </a>
                        @elseif($isAdmin)
                            <a
                                href="{{ route('admin-dashboard') }}"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold text-xs transition shadow-sm"
                            >
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                <span>Admin Dashboard</span>
                            </a>
                        @else
                            <a
                                href="{{ route('landing') }}"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold text-xs transition shadow-sm"
                            >
                                <i data-lucide="home" class="w-4 h-4"></i>
                                <span>Return to Home</span>
                            </a>
                        @endif
                    @else
                        <a
                            href="{{ route('landing') }}"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold text-xs transition shadow-sm"
                        >
                            <i data-lucide="home" class="w-4 h-4"></i>
                            <span>Return to Home</span>
                        </a>
                    @endauth
                </div>

                @guest
                    <p class="text-center text-xs text-gray-500 dark:text-gray-400 pt-1">
                        Have an account?
                        <a href="{{ route('login') }}" class="font-medium text-teal-600 dark:text-teal-400 hover:underline">
                            Sign in
                        </a>
                        to view and track all your spa appointments.
                    </p>
                @endguest
            </div>

        </div>

    </div>
</main>

<script>
    function toggleDarkMode() {
        const html = document.documentElement;
        const isDark = html.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });
</script>

</body>
</html>
