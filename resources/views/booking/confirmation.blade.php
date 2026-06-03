<!DOCTYPE html>
<html lang="en" class="{{ session('dark_mode') === 'enabled' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - Spa Alexandria</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.documentElement.classList.add('dark');
        }

        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
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
        }
    </script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 min-h-screen transition-colors duration-300 flex items-center justify-center p-4">

<div class="max-w-lg w-full bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 text-center transition-colors duration-300">

    <!-- Success Icon -->
    <div class="mx-auto w-16 h-16 bg-teal-100 dark:bg-teal-900/50 rounded-full flex items-center justify-center mb-6">
        <svg class="w-8 h-8 text-teal-600 dark:text-teal-400"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7">
            </path>
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">
        Booking Request Received!
    </h1>

    <p class="text-gray-600 dark:text-gray-400 mb-6">
        We will call you at
        <span class="font-semibold text-teal-600 dark:text-teal-400">
            {{ $appointment->customer->phone_number }}
        </span>
        to confirm your appointment.
    </p>

    <!-- Appointment Card -->
    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-5 text-left mb-6 space-y-3">

        <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-400 text-sm">
                Reference #
            </span>

            <span class="font-mono font-semibold text-gray-800 dark:text-gray-200">
                {{ $appointment->id }}
            </span>
        </div>

        <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-400 text-sm">
                Date
            </span>

            <span class="font-semibold text-gray-800 dark:text-gray-200">
                {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('F d, Y') }}
            </span>
        </div>

        <div class="flex justify-between">
            <span class="text-gray-500 dark:text-gray-400 text-sm">
                Time
            </span>

            <span class="font-semibold text-gray-800 dark:text-gray-200">
                {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}
                -
                {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
            </span>
        </div>

        <div class="border-t dark:border-gray-600 pt-3">
            <span class="text-gray-500 dark:text-gray-400 text-sm block mb-2">
                Services:
            </span>

            <div class="flex flex-wrap gap-2">
                @foreach($appointment->services as $service)
                    <span class="bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 px-3 py-1 rounded text-sm text-gray-700 dark:text-gray-200">
                        {{ $service->name }}
                    </span>
                @endforeach
            </div>
        </div>

        <div class="border-t dark:border-gray-600 pt-3 flex justify-between">
            <span class="text-gray-500 dark:text-gray-400 text-sm">
                Total
            </span>

            <span class="font-bold text-teal-600 dark:text-teal-400 text-lg">
                ₱{{ number_format($appointment->total_price, 2) }}
            </span>
        </div>
    </div>

    <!-- Buttons -->
    <div class="space-y-3">

        <a href="{{ route('booking.wizard') }}"
           class="block w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-3 rounded-lg transition">
            Book Another Appointment
        </a>

        @auth

        @php
            $user = auth()->user();
            $role = strtolower($user?->roles()->first()->name ?? '');
        @endphp

        @if($role === 'admin')

            <a href="{{ route('admin-dashboard') }}"
            class="block w-full border py-3 rounded-lg">
                Go to Admin Dashboard
            </a>

        @elseif($role === 'receptionist')

            <a href="{{ route('receptionist.dashboard') }}"
            class="block w-full border py-3 rounded-lg">
                Go to Receptionist Dashboard
            </a>

        @elseif($role === 'staff')

            <a href="{{ route('staff.dashboard') }}"
            class="block w-full border py-3 rounded-lg">
                Go to Staff Dashboard
            </a>

        @else

            <a href="{{ route('customer-dashboard') }}"
            class="block w-full border py-3 rounded-lg">
                Go to My Dashboard
            </a>

        @endif

        @else

            <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">
                <a href="{{ route('login') }}"
                   class="text-teal-600 dark:text-teal-400 hover:underline font-medium">
                    Sign in
                </a>
                to manage your bookings and save your details for faster checkout next time.
            </p>

        @endauth

    </div>

    <p class="text-xs text-gray-400 dark:text-gray-500 mt-6">
        Please arrive 15 minutes before your scheduled time.
        Cancellations must be made at least 2 hours in advance.
    </p>

</div>

</body>
</html>