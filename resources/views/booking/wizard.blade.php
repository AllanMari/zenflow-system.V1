<!DOCTYPE html>
<html lang="en" x-data="bookingSystem({{ $categoriesJson }})" x-init="init()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Spa Alexandria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = { 
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        teal: {
                            50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4',
                            300: '#5eead4', 400: '#2dd4bf', 500: '#14b8a6',
                            600: '#0d9488', 700: '#0f766e', 800: '#115e59',
                            900: '#134e4a', 950: '#042f2e',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .dark ::-webkit-scrollbar-thumb { background: #4b5563; }
        
        .stagger-item {
            opacity: 0;
            transform: translateY(16px);
            animation: slideUp 0.45s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .category-services {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.5s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.35s ease;
            opacity: 0;
        }
        .category-services.open {
            grid-template-rows: 1fr;
            opacity: 1;
        }
        .category-services > div { overflow: hidden; }
        .chevron { transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .chevron.rotated { transform: rotate(180deg); }

        .custom-checkbox {
            appearance: none;
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid #d1d5db;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
            flex-shrink: 0;
        }
        .custom-checkbox:checked {
            background-color: #0d9488;
            border-color: #0d9488;
        }
        .custom-checkbox:checked::after {
            content: '';
            position: absolute;
            left: 5px;
            top: 1px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        .dark .custom-checkbox { border-color: #6b7280; }

        .service-card {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px -8px rgba(0, 0, 0, 0.1);
        }
        .service-card.selected {
            border-color: #0d9488;
            background: linear-gradient(135deg, #f0fdfa 0%, #ffffff 100%);
            box-shadow: 0 0 0 2px #0d9488, 0 12px 28px -8px rgba(13, 148, 136, 0.2);
        }
        .dark .service-card.selected {
            background: linear-gradient(135deg, #134e4a20 0%, #1f2937 100%);
            box-shadow: 0 0 0 2px #14b8a6, 0 12px 28px -8px rgba(13, 148, 136, 0.25);
        }
        .service-card .check-indicator {
            opacity: 0;
            transform: scale(0.5) rotate(-10deg);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .service-card.selected .check-indicator {
            opacity: 1;
            transform: scale(1) rotate(0deg);
        }

        .time-slot {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            font-variant-numeric: tabular-nums;
        }
        .time-slot:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.18);
        }
        .time-slot.occupied {
            background: #fef2f2 !important;
            color: #b91c1c !important;
            cursor: not-allowed;
            opacity: 0.45;
            text-decoration: line-through;
            border-color: #fecaca !important;
        }
        .dark .time-slot.occupied {
            background: rgba(153, 27, 27, 0.12) !important;
            border-color: rgba(248, 113, 113, 0.12) !important;
            color: #f87171 !important;
        }
        .time-slot.selected {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%) !important;
            color: white !important;
            border-color: #0d9488 !important;
            box-shadow: 0 6px 16px rgba(13, 148, 136, 0.35);
            font-weight: 700;
        }

        .shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.4s infinite;
            border-radius: 0.75rem;
        }
        .dark .shimmer {
            background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
            background-size: 200% 100%;
        }
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .fc { background: white; max-width: 100%; font-family: 'Inter', sans-serif !important; }
        .fc-daygrid-day {
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }
        .fc-daygrid-day:hover {
            background: #f0fdfa !important;
            transform: scale(1.03);
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.12);
            z-index: 1;
        }
        .fc-day-selected { 
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%) !important; 
            color: white !important; 
            border-radius: 12px !important;
            box-shadow: 0 6px 16px rgba(13, 148, 136, 0.35);
            font-weight: 700;
        }
        .dark .fc { background: #1f2937; color: #e5e7eb; }
        .dark .fc-daygrid-day { background: #374151; border-color: #4b5563; }
        .dark .fc-daygrid-day:hover { background: #4b5563 !important; }
        .dark .fc-day-today {
            background: #374151 !important; 
            border: 2px solid #0d9488 !important;
            color: white !important;
            font-weight: 700;
            border-radius: 10px !important;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-950 min-h-screen font-sans transition-colors duration-300" x-cloak>

    <!-- Header -->
    <header class="bg-gradient-to-r from-teal-600 to-teal-700 text-white shadow-lg sticky top-0 z-50">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="{{ route('landing') }}" class="text-xl font-bold tracking-tight hover:opacity-90 flex items-center gap-2.5 transition">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Spa Alexandria
                </a>
                <div class="flex items-center gap-3">
                    @auth
                        @if(auth()->user()->roles()->where('name', 'customer')->exists())
                            <a href="{{ route('customer-dashboard') }}" class="text-sm font-semibold hover:text-teal-200 transition bg-white/10 hover:bg-white/20 px-4 py-2 rounded-full">My Dashboard</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold hover:text-teal-200 transition bg-white/10 hover:bg-white/20 px-4 py-2 rounded-full">Member Login</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Progress Stepper -->
    <div class="sticky top-16 z-40 bg-gray-50/90 dark:bg-gray-950/90 backdrop-blur-md border-b border-gray-200/50 dark:border-gray-800/50 py-3.5 px-4">
        <div class="max-w-6xl mx-auto flex items-center justify-center gap-1 sm:gap-2">
            <div class="flex items-center gap-2">
                <div :class="selectedServices.length > 0 ? 'bg-teal-600 text-white scale-110' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'" 
                     class="w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-xs sm:text-sm font-bold transition-all duration-300 shadow-sm">1</div>
                <span class="text-xs sm:text-sm font-semibold hidden sm:block" :class="selectedServices.length > 0 ? 'text-teal-700 dark:text-teal-400' : 'text-gray-400'">Services</span>
            </div>
            <div class="w-6 sm:w-10 h-1 rounded-full transition-all" :class="selectedServices.length > 0 ? 'bg-teal-500' : 'bg-gray-200 dark:bg-gray-700'"></div>
            <div class="flex items-center gap-2">
                <div :class="guest_name && phoneValid ? 'bg-teal-600 text-white scale-110' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'" 
                     class="w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-xs sm:text-sm font-bold transition-all duration-300 shadow-sm">2</div>
                <span class="text-xs sm:text-sm font-semibold hidden sm:block" :class="guest_name && phoneValid ? 'text-teal-700 dark:text-teal-400' : 'text-gray-400'">Details</span>
            </div>
            <div class="w-6 sm:w-10 h-1 rounded-full transition-all" :class="start_time ? 'bg-teal-500' : 'bg-gray-200 dark:bg-gray-700'"></div>
            <div class="flex items-center gap-2">
                <div :class="start_time ? 'bg-teal-600 text-white scale-110' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'" 
                     class="w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-xs sm:text-sm font-bold transition-all duration-300 shadow-sm">3</div>
                <span class="text-xs sm:text-sm font-semibold hidden sm:block" :class="start_time ? 'text-teal-700 dark:text-teal-400' : 'text-gray-400'">Schedule</span>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-6xl mx-auto">
            
            <div class="text-center mb-10">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2 tracking-tight">Book Your Appointment</h1>
                <p class="text-gray-500 dark:text-gray-400">Select your services, pick a time, and we'll handle the rest</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                <!-- LEFT COLUMN -->
                <div class="lg:col-span-7 space-y-6">

                    <!-- Services -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/50 text-teal-600 dark:text-teal-400 flex items-center justify-center text-sm font-bold">1</span>
                                    Select Services
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-10">Click a category to explore services</p>
                            </div>
                            <span class="text-xs font-bold bg-gray-100 dark:bg-gray-700 px-3 py-1.5 rounded-full transition-all" 
                                  :class="selectedServices.length > 0 ? 'bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300' : 'text-gray-500 dark:text-gray-400'"
                                  x-text="selectedServices.length + ' selected'"></span>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(cat, catIndex) in categories" :key="cat.id">
                                <div class="rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 stagger-item" :style="'animation-delay: ' + (catIndex * 80) + 'ms'">
                                    <button type="button" @click="toggleCategory(cat.id)" 
                                            class="w-full p-4 flex items-center justify-between text-left select-none group relative overflow-hidden">
                                        <div class="absolute inset-0 opacity-10 transition-opacity group-hover:opacity-20" :style="'background-color: ' + cat.color"></div>
                                        <div class="flex items-center gap-4 relative z-10">
                                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg" 
                                                 :style="'background-color: ' + cat.color">
                                                <span x-text="cat.name.charAt(0).toUpperCase()"></span>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-gray-900 dark:text-white text-lg" x-text="cat.name"></h3>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                                    <span x-text="cat.services.length"></span> service<span x-show="cat.services.length !== 1">s</span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="relative z-10 w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                            <svg :id="'chevron-' + cat.id" class="chevron w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </button>

                                    <div :id="'services-' + cat.id" class="category-services bg-gray-50/50 dark:bg-gray-900/20">
                                        <div class="p-4 grid grid-cols-1 gap-3">
                                            <template x-for="(s, sIndex) in cat.services" :key="s.id">
                                                <label :data-service-id="s.id" 
                                                       :class="{ 'selected': isSelected(s.id) }" 
                                                       class="service-card relative flex gap-4 p-4 rounded-xl border-2 border-transparent bg-white dark:bg-gray-800 cursor-pointer hover:border-teal-200 dark:hover:border-teal-800 stagger-item"
                                                       :style="'animation-delay: ' + ((catIndex * 80) + (sIndex * 50) + 100) + 'ms'">
                                                    
                                                    <div class="check-indicator absolute -top-2 -right-2 w-7 h-7 bg-gradient-to-br from-teal-500 to-teal-600 rounded-full flex items-center justify-center shadow-lg z-10">
                                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                    </div>

                                                    <input type="checkbox" :checked="isSelected(s.id)" @change="toggleService(s)" class="custom-checkbox mt-1">
                                                    
                                                    <div class="shrink-0 w-20 h-20 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 relative">
                                                        <img x-show="s.image && s.image.toString().trim() !== ''"
                                                            :src="s.image" :alt="s.name" 
                                                            class="w-full h-full object-cover"
                                                            onerror="this.style.display='none'">
                                                        <div x-show="!s.image || s.image.toString().trim() === ''" 
                                                             class="w-full h-full flex items-center justify-center text-gray-400">
                                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex justify-between items-start gap-3 mb-1">
                                                            <p class="font-bold text-gray-900 dark:text-white text-[15px]" x-text="s.name"></p>
                                                            <span class="text-base font-bold text-teal-600 dark:text-teal-400 whitespace-nowrap" 
                                                                  x-text="'₱' + parseFloat(s.discount_price || s.price).toLocaleString('en-PH', {minimumFractionDigits: 2})"></span>
                                                        </div>
                                                        
                                                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-700">
                                                                <span x-text="s.duration_minutes + ' min'"></span>
                                                            </span>
                                                            <template x-if="hasDeposit(s)">
                                                                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-700">
                                                                    <span x-text="getDepositText(s)"></span>
                                                                </span>
                                                            </template>
                                                        </div>
                                                        
                                                        <p x-show="s.description" x-text="s.description" 
                                                           class="text-[13px] text-gray-500 dark:text-gray-400 mt-2 line-clamp-2"></p>
                                                    </div>
                                                </label>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Step 2 & 3 -->
                    <div x-show="selectedServices.length > 0" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-6"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="space-y-6">

                        <!-- Customer Info -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                            <div class="flex items-center gap-3 mb-5">
                                <span class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/50 text-teal-600 dark:text-teal-400 flex items-center justify-center text-sm font-bold">2</span>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Your Details</h2>
                            </div>
                            
                            @auth
                                @if(auth()->user()->roles()->where('name', 'customer')->exists())
                                    <div class="mb-4 p-4 bg-teal-50 dark:bg-teal-900/20 rounded-xl border border-teal-200 dark:border-teal-800">
                                        <p class="text-sm font-bold text-teal-800 dark:text-teal-300">Welcome back, {{ auth()->user()->first_name }}!</p>
                                        <p class="text-xs text-teal-600 dark:text-teal-400">Your details are pre-filled.</p>
                                    </div>
                                @endif
                            @endauth

                            @guest
                                <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-600 flex justify-between items-center">
                                    <div>
                                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300">Booking as Guest</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">No account needed.</p>
                                    </div>
                                    <a href="{{ route('customer.register') }}" class="text-sm font-bold text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-900/30 px-4 py-2 rounded-lg">Create account &rarr;</a>
                                </div>
                            @endguest

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Full Name</label>
                                    <input type="text" x-model="guest_name" class="w-full p-3.5 border border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none" placeholder="e.g. Maria Santos">
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Phone Number</label>
                                    <div class="relative">
                                        <input type="tel" x-model="guest_phone" @input="formatPhone" maxlength="11"
                                            class="w-full pl-4 pr-4 py-3.5 border border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white font-mono text-sm tracking-wide outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500" 
                                            placeholder="09XXXXXXXXX">
                                    </div>
                                    <p class="text-xs mt-1.5 font-bold" :class="phoneValid ? 'text-green-600' : 'text-red-500'" x-text="phoneMessage"></p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Medical Notes / Preferences</label>
                                    <textarea x-model="medical_notes" rows="3" 
                                        class="w-full p-3.5 border border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white text-sm outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 resize-none"
                                        placeholder="Allergies, pregnancy, skin conditions..."></textarea>
                                </div>
                            </div>

                            <div class="mt-5 p-4 bg-amber-50 dark:bg-amber-900/15 border border-amber-200 dark:border-amber-800/50 rounded-xl flex gap-3 items-start">
                                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/15 border border-blue-200 dark:border-blue-800/50 rounded-xl flex gap-3 items-start">
                                    <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-bold text-blue-800 dark:text-blue-300">Data Privacy Notice</p>
                                        <p class="text-xs text-blue-700 dark:text-blue-400 mt-0.5">
                                            We collect your name, phone number, and medical notes solely for appointment fulfillment and your safety. 
                                            By proceeding with this booking, you consent to this processing under the Data Privacy Act of 2012 (RA 10173).
                                            <a href="{{ route('privacy') }}" target="_blank" class="underline font-semibold">Read our Privacy Policy</a>.
                                        </p>
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-bold text-amber-800 dark:text-amber-300">Confirmation Policy</p>
                                    <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">A receptionist will call you to confirm. If you do not answer within 30 minutes of your appointment time, you may be marked as a no-show.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                            <div class="flex items-center gap-3 mb-5">
                                <span class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/50 text-teal-600 dark:text-teal-400 flex items-center justify-center text-sm font-bold">3</span>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Select Date & Time</h2>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Choose your preferred schedule</p>
                                </div>
                            </div>
                            
                            <div id="calendar" class="rounded-xl overflow-hidden"></div>
                            
                            <div x-show="appointment_date" class="mt-5 p-4 bg-teal-50 dark:bg-teal-900/15 rounded-xl border border-teal-100 dark:border-teal-800/30 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-teal-100 dark:bg-teal-800 flex items-center justify-center text-teal-600 dark:text-teal-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-teal-600 dark:text-teal-400 font-bold uppercase">Selected Date</p>
                                    <p class="text-lg font-bold text-teal-800 dark:text-teal-200" x-text="formatDate(appointment_date)"></p>
                                </div>
                            </div>
                            
                            <!-- Time Slots -->
                            <div x-show="appointment_date" class="mt-5">
                                <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Available Times
                                </p>
                                
                                <div x-show="loadingSlots" class="grid grid-cols-3 sm:grid-cols-4 gap-2.5">
                                    <template x-for="i in 8" :key="i">
                                        <div class="h-12 shimmer"></div>
                                    </template>
                                </div>
                                
                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5" x-show="!loadingSlots">
                                    <template x-for="slot in timeSlots" :key="slot.time">
                                        <button type="button" @click="!slot.occupied && setTime(slot.time)"
                                                :disabled="slot.occupied"
                                                :class="{
                                                    'occupied': slot.occupied,
                                                    'selected': start_time === slot.time && !slot.occupied,
                                                    'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:border-teal-300': !slot.occupied && start_time !== slot.time
                                                }"
                                                class="time-slot py-3 px-2 rounded-xl border-2 text-sm font-bold text-center"
                                                :title="slot.reason || ''">
                                            <span x-text="slot.display"></span>
                                        </button>
                                    </template>
                                </div>
                                
                                <div x-show="timeSlots.length === 0 && !loadingSlots" class="text-center py-10 bg-gray-50 dark:bg-gray-700/30 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-600">
                                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-sm text-gray-500 font-bold">No available slots</p>
                                    <p class="text-xs text-gray-400 mt-1">Try selecting a different date</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- RIGHT COLUMN - Summary -->
                <div class="lg:col-span-5">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 sticky top-36 overflow-hidden">
                        <div class="bg-gradient-to-r from-teal-600 to-teal-700 p-6 text-white">
                            <h2 class="text-lg font-bold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Booking Summary
                            </h2>
                        </div>

                        <div class="p-6">
                            <div x-show="selectedServices.length === 0" class="text-center py-10">
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-bold">No services selected yet</p>
                                <p class="text-xs text-gray-400 mt-1">Choose from the categories on the left</p>
                            </div>

                            <div x-show="selectedServices.length > 0" class="space-y-3 max-h-64 overflow-y-auto pr-1 mb-5">
                                <template x-for="s in selectedServices" :key="s.id">
                                    <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-100 dark:border-gray-600/50">
                                        <div class="w-10 h-10 rounded-lg bg-gray-200 dark:bg-gray-600 overflow-hidden shrink-0">
                                            <img x-show="s.image" :src="s.image" class="w-full h-full object-cover">
                                            <div x-show="!s.image" class="w-full h-full flex items-center justify-center text-gray-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200 truncate" x-text="s.name"></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="s.duration_minutes + ' min'"></p>
                                        </div>
                                        <span class="text-sm font-bold text-teal-600 dark:text-teal-400" x-text="'₱' + parseFloat(s.discount_price || s.price).toLocaleString('en-PH', {minimumFractionDigits: 2})"></span>
                                    </div>
                                </template>
                            </div>

                            <div x-show="selectedServices.length > 0" class="space-y-2.5 text-sm mb-5">
                                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                    <span class="text-gray-500 dark:text-gray-400">Date</span>
                                    <span class="font-bold text-gray-800 dark:text-gray-200" x-text="formatDate(appointment_date) || 'Not selected'"></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                    <span class="text-gray-500 dark:text-gray-400">Time</span>
                                    <span class="font-bold text-gray-800 dark:text-gray-200" x-text="start_time ? convertTo12Hour(start_time) : 'Not selected'"></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                    <span class="text-gray-500 dark:text-gray-400">Name</span>
                                    <span class="font-bold text-gray-800 dark:text-gray-200" x-text="guest_name || 'Not entered'"></span>
                                </div>
                            </div>

                            <div x-show="selectedServices.length > 0" class="pt-4 border-t-2 border-gray-100 dark:border-gray-700">
                                <div class="flex justify-between items-end">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">Total</span>
                                    <span class="text-3xl font-bold text-teal-600 dark:text-teal-400">₱<span x-text="parseFloat(animatedPrice).toLocaleString('en-PH', {minimumFractionDigits: 2})"></span></span>
                                </div>
                            </div>

                            <button type="button" @click="showConfirmation = true"
                                    class="w-full mt-6 py-4 rounded-xl text-white font-bold text-sm uppercase tracking-wide transition-all flex items-center justify-center gap-2"
                                    :class="(start_time && guest_name && phoneValid) ? 'bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 shadow-lg shadow-teal-500/25' : 'bg-gray-300 dark:bg-gray-700 cursor-not-allowed'"
                                    :disabled="!(start_time && guest_name && phoneValid)">
                                Review & Confirm
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Confirmation Modal -->
    <div x-show="showConfirmation" 
         class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             @click.away="showConfirmation = false">
            
            <div class="bg-gradient-to-r from-teal-600 to-teal-700 p-6 text-white">
                <h2 class="text-xl font-bold">Confirm Your Booking</h2>
                <p class="text-teal-100 text-sm mt-1">Please review your appointment details</p>
            </div>

            <div class="p-6">
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between py-2.5 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400 text-sm">Services</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200 text-sm text-right" x-text="selectedServices.map(s => s.name).join(', ')"></span>
                    </div>
                    <div class="flex justify-between py-2.5 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400 text-sm">Date</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200 text-sm" x-text="formatDate(appointment_date)"></span>
                    </div>
                    <div class="flex justify-between py-2.5 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400 text-sm">Time</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200 text-sm" x-text="convertTo12Hour(start_time)"></span>
                    </div>
                    <div class="flex justify-between items-center pt-2 bg-gray-50 dark:bg-gray-700/30 p-3 rounded-xl">
                        <span class="text-gray-600 dark:text-gray-400 font-bold">Total</span>
                        <span class="font-bold text-teal-600 dark:text-teal-400 text-2xl">₱<<span x-text="parseFloat(animatedPrice).toLocaleString('en-PH', {minimumFractionDigits: 2})"></span></span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button @click="showConfirmation = false" class="flex-1 py-3.5 border-2 border-gray-200 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold text-sm">
                        Go Back
                    </button>
                    <form method="POST" action="{{ route('booking.store') }}" class="flex-1" @submit.prevent="$el.submit()">
                        @csrf
                        <input type="hidden" name="source" value="public">
                        <template x-for="s in selectedServices">
                            <input type="hidden" name="services[]" :value="s.id">
                        </template>
                        <input type="hidden" name="appointment_date" :value="appointment_date">
                        <input type="hidden" name="start_time" :value="start_time">
                        <input type="hidden" name="end_time" :value="endTime">
                        <input type="hidden" name="guest_first_name" :value="guest_name">
                        <input type="hidden" name="guest_phone" :value="guest_phone">
                        <input type="hidden" name="medical_notes" :value="medical_notes">
                        
                        <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-teal-600 to-teal-700 text-white rounded-xl font-bold text-sm shadow-lg">
                            Confirm Booking
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function bookingSystem(cats) {
            return {
                categories: cats,
                selectedServices: [],
                timeSlots: [],
                appointment_date: null,
                start_time: null,
                guest_name: @json($defaultName),
                guest_phone: @json($defaultPhone),
                medical_notes: @json($customerMedicalNotes),
                preselectedIds: @json($preselectedIds),
                showConfirmation: false,
                phoneValid: false,
                phoneMessage: '',
                loadingSlots: false,
                calendar: null,
                animatedPrice: '0.00',

                hasDeposit(service) {
                    const min = parseFloat(service.deposit_percentage_min) || 0;
                    return min > 0;
                },

                getDepositText(service) {
                    const min = parseFloat(service.deposit_percentage_min) || 0;
                    const max = parseFloat(service.deposit_percentage_max) || 0;
                    if (min > 0 && max > 0 && max !== min) return min + '%-' + max + '%';
                    return min + '%';
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const date = new Date(dateStr + 'T00:00:00');
                    return date.toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                },

                convertTo12Hour(time24) {
                    if (!time24) return '';
                    const [h, m] = time24.split(':').map(Number);
                    const period = h >= 12 ? 'PM' : 'AM';
                    const hour12 = h % 12 || 12;
                    return `${hour12}:${String(m).padStart(2, '0')} ${period}`;
                },

                isSelected(serviceId) {
                    return this.selectedServices.some(s => s.id === serviceId);
                },

                toggleService(service) {
                    const i = this.selectedServices.findIndex(s => s.id === service.id);
                    if (i === -1) {
                        this.selectedServices.push(service);
                    } else {
                        this.selectedServices.splice(i, 1);
                    }
                    this.$nextTick(() => {
                        if (this.selectedServices.length > 0 && !this.calendar) {
                            this.initCalendar();
                        }
                    });
                },

                toggleCategory(catId) {
                    const servicesDiv = document.getElementById('services-' + catId);
                    const chevron = document.getElementById('chevron-' + catId);
                    servicesDiv.classList.toggle('open');
                    chevron.classList.toggle('rotated');
                },

                formatPhone() {
                    this.guest_phone = this.guest_phone.replace(/\D/g, '');
                    const regex = /^09\d{9}$/;
                    this.phoneValid = regex.test(this.guest_phone);
                    if (this.guest_phone.length === 0) {
                        this.phoneMessage = '';
                    } else if (this.guest_phone.length < 11) {
                        this.phoneMessage = 'Need ' + (11 - this.guest_phone.length) + ' more digits';
                    } else if (this.phoneValid) {
                        this.phoneMessage = '✓ Valid number';
                    } else {
                        this.phoneMessage = 'Must start with 09, 11 digits';
                    }
                },

                setTime(time) {
                    this.start_time = time;
                },

                get endTime() {
                    if (!this.start_time || this.selectedServices.length === 0) return null;
                    const totalDuration = this.selectedServices.reduce((a, s) => a + (s.duration_minutes || 60), 0);
                    const [h, m] = this.start_time.split(':').map(Number);
                    const end = new Date(2000, 0, 1, h, m + totalDuration);
                    return String(end.getHours()).padStart(2, '0') + ':' + String(end.getMinutes()).padStart(2, '0');
                },

                get totalPrice() {
                    return this.selectedServices.reduce((a, s) => a + parseFloat(s.discount_price || s.price), 0).toFixed(2);
                },

                async loadTimeSlots() {
                    if (!this.appointment_date || this.selectedServices.length === 0) return;
                    
                    this.loadingSlots = true;
                    this.timeSlots = [];
                    this.start_time = null;
                    
                    try {
                        const duration = this.selectedServices.reduce((a, s) => a + (s.duration_minutes || 60), 0);
                        const serviceIds = this.selectedServices.map(s => s.id);
                        const serviceParams = serviceIds.map(id => `services[]=${id}`).join('&');
                        
                        const res = await fetch(`{{ route('booking.slots') }}?date=${this.appointment_date}&duration=${duration}&${serviceParams}`);
                        const data = await res.json();
                        
                        if (!res.ok) throw new Error(data.message || 'Failed to load slots');
                        
                        const dateSlots = data.slots.filter(s => s.date === this.appointment_date);
                        
                        this.timeSlots = dateSlots.map(slot => {
                            const isBlocked = !slot.room_available;
                            let reason = null;
                            if (isBlocked) reason = 'No room available';
                            
                            return { 
                                ...slot, 
                                occupied: isBlocked,
                                reason: reason
                            };
                        });
                        
                    } catch (err) {
                        console.error('Failed to load slots:', err);
                        this.timeSlots = [];
                    } finally {
                        this.loadingSlots = false;
                    }
                },

                async init() {
                    // Preselected services from rebook
                    if (this.preselectedIds.length > 0) {
                        for (let cat of this.categories) {
                            for (let s of cat.services) {
                                if (this.preselectedIds.includes(s.id)) {
                                    this.selectedServices.push(s);
                                }
                            }
                        }
                        if (this.selectedServices.length > 0) {
                            this.$nextTick(() => this.initCalendar());
                        }
                    }
                    
                    if (this.guest_phone) this.formatPhone();

                    // Watch services to reload slots when date already selected
                    this.$watch('selectedServices', () => {
                        this.animatedPrice = this.totalPrice;
                        if (this.appointment_date && this.selectedServices.length > 0) {
                            this.loadTimeSlots();
                        } else if (this.selectedServices.length === 0) {
                            this.timeSlots = [];
                            this.start_time = null;
                            this.appointment_date = null;
                        }
                    }, { deep: true });

                    // Watch date to load slots
                    this.$watch('appointment_date', (val) => {
                        if (val && this.selectedServices.length > 0) {
                            this.loadTimeSlots();
                        }
                    });
                },

                initCalendar() {
                    const today = new Date();
                    const format = d => {
                        const y = d.getFullYear();
                        const m = String(d.getMonth()+1).padStart(2,'0');
                        const day = String(d.getDate()).padStart(2,'0');
                        return `${y}-${m}-${day}`;
                    };

                    let days = [];
                    let temp = new Date(today);
                    while (days.length < 14) {
                        if (temp.getDay() !== 0) { // Skip Sundays
                            days.push(format(temp));
                        }
                        temp.setDate(temp.getDate() + 1);
                    }

                    const el = document.getElementById('calendar');
                    this.calendar = new FullCalendar.Calendar(el, {
                        initialView: 'dayGridMonth',
                        height: 'auto',
                        headerToolbar: { left: 'title', center: '', right: 'prev,next' },
                        validRange: { start: days[0], end: days[days.length - 1] },
                        dateClick: (info) => {
                            this.appointment_date = info.dateStr;
                            this.start_time = null;
                            document.querySelectorAll('.fc-day-selected').forEach(e => e.classList.remove('fc-day-selected'));
                            const cell = document.querySelector(`[data-date="${this.appointment_date}"]`);
                            if (cell) cell.classList.add('fc-day-selected');
                        }
                    });
                    this.calendar.render();
                }
            }
        }
    </script>
</body>
</html>