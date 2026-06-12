<!DOCTYPE html>
<html lang="en" class="{{ session('dark_mode') === 'enabled' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Staff') - Spa Alexandria</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @keyframes bell-ring {
            0% { transform: rotate(0); }
            15% { transform: rotate(18deg); }
            30% { transform: rotate(-18deg); }
            45% { transform: rotate(12deg); }
            60% { transform: rotate(-12deg); }
            75% { transform: rotate(6deg); }
            85% { transform: rotate(-6deg); }
            100% { transform: rotate(0); }
        }
        .bell-ring {
            animation: bell-ring 2.5s ease-in-out infinite;
            transform-origin: top center;
        }
        @keyframes badge-pop {
            0% { transform: scale(0.5); opacity: 0; }
            60% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        .badge-pop {
            animation: badge-pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
    </style>
    <script>
        (function() {
            const isDark = localStorage.getItem('darkMode') === 'enabled';
            if (isDark) document.documentElement.classList.add('dark');
        })();
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
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
    @stack('styles')
</head>
<body class="bg-gray-100 dark:bg-gray-900 h-screen overflow-hidden flex transition-colors duration-300">

    <!-- MOBILE HEADER -->
    <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-teal-800 dark:bg-teal-950 text-white flex items-center justify-between px-4 z-40 shadow-lg transition-colors duration-300">
        <button onclick="toggleMobileSidebar()" class="p-2 rounded-lg hover:bg-teal-700 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <span class="font-bold text-lg">Spa Alexandria</span>
        <!-- Mobile notification bell -->
        <button id="mobileNotifyBtn"
                onclick="toggleNotifyDropdown('mobile')" 
                class="relative p-2.5 rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur-sm transition-all duration-200 group border border-white/10 hover:border-white/20 shadow-sm hover:shadow-md active:scale-95"
                title="Notifications">
            <svg id="mobileBellIcon" class="w-5 h-5 text-white group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span id="mobileNotifyBadge" class="hidden absolute -top-1.5 -right-1.5 min-w-[20px] h-5 bg-gradient-to-br from-red-500 to-red-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 shadow-lg ring-2 ring-teal-800 dark:ring-teal-950">0</span>
        </button>
    </div>

    <!-- DESKTOP SIDEBAR -->
    <aside id="desktopSidebar" class="hidden md:flex fixed left-0 top-0 h-full w-64 bg-teal-800 dark:bg-teal-950 text-white flex-col overflow-hidden transition-colors duration-300 shrink-0 print:hidden z-30">
        <div class="p-4 font-bold text-xl border-b border-teal-700 flex items-center justify-between shrink-0">
            <span class="flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Spa Staff
            </span>
            <!-- Desktop notification bell -->
            <div class="relative" id="desktopNotifyContainer">
                <button id="desktopNotifyBtn"
                        onclick="toggleNotifyDropdown('desktop')" 
                        class="relative p-2.5 rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur-sm transition-all duration-200 group border border-white/10 hover:border-white/20 shadow-sm hover:shadow-md active:scale-95"
                        title="Notifications">
                    <svg id="desktopBellIcon" class="w-5 h-5 text-white group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span id="desktopNotifyBadge" class="hidden absolute -top-1.5 -right-1.5 min-w-[20px] h-5 bg-gradient-to-br from-red-500 to-red-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 shadow-lg ring-2 ring-teal-800 dark:ring-teal-950">0</span>
                </button>
            </div>
        </div>

        <nav class="mt-4 flex-1 space-y-1 overflow-y-auto">
            <a href="{{ route('staff.dashboard') }}"
               class="block px-4 py-3 {{ request()->routeIs('staff.dashboard') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                Dashboard
            </a>
            <a href="{{ route('staff.appointments') }}"
               class="block px-4 py-3 {{ request()->routeIs('staff.appointments') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                My Appointments
            </a>
            <a href="{{ route('staff.schedule') }}"
               class="block px-4 py-3 {{ request()->routeIs('staff.schedule') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                My Schedule
            </a>
        </nav>

        <div class="p-4 space-y-1 border-t border-teal-700 shrink-0">
            <button onclick="openSettingsModal()"
                    class="w-full text-left px-4 py-3 hover:bg-teal-700 transition flex items-center gap-3 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31-2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Settings
            </button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left hover:bg-teal-700 text-red-300 transition px-4 py-3 rounded-lg flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </button>
            </form>
        </div>
    </aside>

    <!-- MOBILE SIDEBAR OVERLAY -->
    <div id="mobileSidebar" class="fixed inset-0 z-50 transform -translate-x-full transition-transform duration-300 md:hidden print:hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeMobileSidebar()"></div>
        <div class="absolute left-0 top-0 bottom-0 w-72 bg-teal-800 dark:bg-teal-950 text-white flex flex-col overflow-hidden transition-colors duration-300">
            <div class="p-4 font-bold text-xl border-b border-teal-700 flex items-center justify-between shrink-0">
                <span class="flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Spa Alexandria
                </span>
                <button onclick="closeMobileSidebar()" class="p-1 rounded-lg hover:bg-teal-700 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <nav class="mt-4 flex-1 space-y-1 overflow-y-auto">
                <a href="{{ route('staff.dashboard') }}"
                   class="block px-4 py-3 {{ request()->routeIs('staff.dashboard') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('staff.appointments') }}"
                   class="block px-4 py-3 {{ request()->routeIs('staff.appointments') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    My Appointments
                </a>
                <a href="{{ route('staff.schedule') }}"
                   class="block px-4 py-3 {{ request()->routeIs('staff.schedule') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    My Schedule
                </a>
            </nav>

            <div class="p-4 space-y-1 border-t border-teal-700 shrink-0">
                <button onclick="openSettingsModal(); closeMobileSidebar();"
                        class="w-full text-left px-4 py-3 hover:bg-teal-700 transition flex items-center gap-3 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31-2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Settings
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left hover:bg-teal-700 text-red-300 transition px-4 py-3 rounded-lg flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main -->
    <main class="flex-1 md:ml-64 h-full overflow-y-auto p-6 pt-20 md:pt-6">       
         @yield('content')
    </main>

    <!-- NOTIFICATION DROPDOWN -->
    <div id="notifyDropdown" class="hidden fixed z-[60] w-[90vw] max-w-sm bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-200" style="top: 0; left: 0;">
        <div class="p-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-700/30">
            <h3 class="font-semibold text-sm text-gray-800 dark:text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Notifications
            </h3>
            <button onclick="markAllRead()" class="text-xs text-teal-600 dark:text-teal-400 hover:underline font-medium">Mark all read</button>
        </div>
        <div id="notifyList" class="max-h-64 overflow-y-auto">
            <div class="p-6 text-center text-sm text-gray-400 dark:text-gray-500 flex flex-col items-center gap-2">
                <svg class="w-8 h-8 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                No notifications
            </div>
        </div>
        <div class="p-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
            <div id="notifyPagination" class="flex justify-between items-center text-xs">
                <button onclick="loadNotifications(currentPage - 1)" id="notifyPrev" class="text-gray-500 dark:text-gray-400 disabled:opacity-30 hover:text-teal-600 dark:hover:text-teal-400 transition" disabled>← Prev</button>
                <span id="notifyPageInfo" class="text-gray-500 dark:text-gray-400 font-medium">Page 1</span>
                <button onclick="loadNotifications(currentPage + 1)" id="notifyNext" class="text-gray-500 dark:text-gray-400 disabled:opacity-30 hover:text-teal-600 dark:hover:text-teal-400 transition" disabled>Next →</button>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div id="settingsModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
        <div id="settingsBackdrop" class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity duration-300 opacity-0" onclick="closeSettingsModal()"></div>
        <div id="settingsPanel" class="relative bg-white dark:bg-gray-800 dark:text-white rounded-2xl p-6 w-full max-w-md shadow-2xl border border-gray-100 dark:border-gray-700 transform transition-all duration-300 scale-95 opacity-0 mx-4">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold">Settings</h2>
                <button onclick="closeSettingsModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl mb-6">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-teal-100 dark:bg-teal-900/50 rounded-lg">
                        <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="font-semibold block">Night Mode</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Toggle dark theme</span>
                    </div>
                </div>
                <button onclick="toggleDarkMode()" id="darkToggle" class="w-14 h-8 bg-gray-300 dark:bg-teal-600 rounded-full relative transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    <div id="toggleCircle" class="absolute top-1 left-1 w-6 h-6 bg-white rounded-full shadow-md transition-transform duration-300 ease-out"></div>
                </button>
            </div>

            <hr class="mb-6 dark:border-gray-600">

            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block mb-1.5 text-sm font-semibold dark:text-gray-200">First Name</label>
                    <input type="text" name="first_name" value="{{ auth()->user()->first_name }}" class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none" required>
                </div>
                <div class="mb-6">
                    <label class="block mb-1.5 text-sm font-semibold dark:text-gray-200">Last Name</label>
                    <input type="text" name="last_name" value="{{ auth()->user()->last_name }}" class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none" required>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeSettingsModal()" class="px-5 py-2.5 border rounded-lg hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white font-medium">Cancel</button>
                    <button type="submit" onclick="saveSettingsState()" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium shadow-lg shadow-teal-200 dark:shadow-none">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    @stack('scripts')
    <script>
        // ==================== NOTIFICATION SYSTEM ====================
        let currentPage = 1;
        let notifyDropdownOpen = false;
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

        function toggleNotifyDropdown(source) {
            const dropdown = document.getElementById('notifyDropdown');

            // If already open, close it (toggle behavior)
            if (notifyDropdownOpen) {
                dropdown.classList.add('hidden');
                notifyDropdownOpen = false;
                return;
            }

            notifyDropdownOpen = true;

            const isMobile = !source || source === 'mobile' || window.innerWidth < 768;

            if (isMobile) {
                // Mobile: center below the mobile header
                dropdown.style.top = '72px';
                dropdown.style.left = '50%';
                dropdown.style.right = 'auto';
                dropdown.style.transform = 'translateX(-50%)';
            } else {
                // Desktop: position relative to the desktop bell button
                const btn = document.getElementById('desktopNotifyBtn');
                const rect = btn.getBoundingClientRect();
                const dropdownWidth = 384; // max-w-sm
                const viewportWidth = window.innerWidth;
                const sidebarWidth = 256; // w-64

                // Calculate right edge position from viewport left
                // We want the dropdown's right edge to align with the button's right edge
                let leftPos = rect.right - dropdownWidth;

                // But ensure it doesn't go left of the sidebar
                const minLeft = sidebarWidth + 8;
                if (leftPos < minLeft) {
                    leftPos = minLeft;
                }

                // And ensure it doesn't go off the right edge of viewport
                if (leftPos + dropdownWidth > viewportWidth - 8) {
                    leftPos = viewportWidth - dropdownWidth - 8;
                }

                dropdown.style.top = (rect.bottom + 8) + 'px';
                dropdown.style.left = leftPos + 'px';
                dropdown.style.right = 'auto';
                dropdown.style.transform = 'none';
            }

            dropdown.classList.remove('hidden');
            loadNotifications(1);
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notifyDropdown');
            const mobileBtn = document.getElementById('mobileNotifyBtn');
            const desktopBtn = document.getElementById('desktopNotifyBtn');

            // Check if click is inside dropdown or on either bell button
            const clickedDropdown = dropdown.contains(e.target);
            const clickedMobileBtn = mobileBtn && mobileBtn.contains(e.target);
            const clickedDesktopBtn = desktopBtn && desktopBtn.contains(e.target);

            if (!clickedDropdown && !clickedMobileBtn && !clickedDesktopBtn) {
                dropdown.classList.add('hidden');
                notifyDropdownOpen = false;
            }
        });

        async function loadNotifications(page) {
            try {
                const res = await fetch(`/api/notifications?page=${page}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
                });

                const contentType = res.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error('Notification API returned non-JSON:', await res.text());
                    document.getElementById('notifyList').innerHTML = 
                        '<div class="p-4 text-center text-sm text-red-400 dark:text-red-400">Server error. Check console.</div>';
                    return;
                }

                const data = await res.json();

                if (!data.pagination) {
                    console.error('Invalid notification response:', data);
                    document.getElementById('notifyList').innerHTML = 
                        '<div class="p-4 text-center text-sm text-red-400 dark:text-red-400">Invalid response format.</div>';
                    return;
                }

                currentPage = data.pagination.current_page;
                const list = document.getElementById('notifyList');

                if (!data.notifications || data.notifications.length === 0) {
                    list.innerHTML = `<div class="p-6 text-center text-sm text-gray-400 dark:text-gray-500 flex flex-col items-center gap-2">
                        <svg class="w-8 h-8 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        No notifications
                    </div>`;
                } else {
                    list.innerHTML = data.notifications.map(n => `
                        <div class="p-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer" onclick="handleNotifyClick('${n.id}', '${n.action_url || ''}')">
                            <div class="flex items-start gap-2">
                                <div class="w-2 h-2 rounded-full mt-1.5 shrink-0 ${n.severity === 'critical' ? 'bg-red-500' : (n.severity === 'warning' ? 'bg-orange-400' : 'bg-blue-400')}"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white truncate">${n.title}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">${n.message}</p>
                                    <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">${n.time}</p>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }

                document.getElementById('notifyPageInfo').textContent = `Page ${currentPage} of ${data.pagination.last_page || 1}`;
                document.getElementById('notifyPrev').disabled = currentPage <= 1;
                document.getElementById('notifyNext').disabled = !data.pagination.has_more;
                updateNotifyBadges(data.unread_count);
            } catch (err) {
                console.error('Failed to load notifications:', err);
                document.getElementById('notifyList').innerHTML = 
                    '<div class="p-4 text-center text-sm text-red-400 dark:text-red-400">Failed to load. Check connection.</div>';
            }
        }

        function updateNotifyBadges(count) {
            const desktopBadge = document.getElementById('desktopNotifyBadge');
            const mobileBadge = document.getElementById('mobileNotifyBadge');
            const desktopBell = document.getElementById('desktopBellIcon');
            const mobileBell = document.getElementById('mobileBellIcon');

            [desktopBadge, mobileBadge].forEach(badge => {
                if (badge) {
                    if (count > 0) {
                        const oldText = badge.textContent;
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.classList.remove('hidden');
                        if (oldText !== badge.textContent && oldText === '0') {
                            badge.classList.remove('badge-pop');
                            void badge.offsetWidth;
                            badge.classList.add('badge-pop');
                        }
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            });

            [desktopBell, mobileBell].forEach(bell => {
                if (bell) {
                    if (count > 0) bell.classList.add('bell-ring');
                    else bell.classList.remove('bell-ring');
                }
            });
        }

        async function handleNotifyClick(id, url) {
            try {
                await fetch(`/api/notifications/${id}/read`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
                });
                if (url) window.location.href = url;
                else loadNotifications(currentPage);
            } catch (err) {
                console.error('Failed to mark as read:', err);
            }
        }

        async function markAllRead() {
            try {
                await fetch('/api/notifications/read-all', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
                });
                loadNotifications(1);
                updateNotifyBadges(0);
            } catch (err) {
                console.error('Failed to mark all read:', err);
            }
        }

        // Poll for new notifications every 30 seconds
        setInterval(async () => {
            try {
                const res = await fetch('/api/notifications/count', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
                });
                const data = await res.json();
                updateNotifyBadges(data.unread_count);
            } catch (err) {
                // Silently fail on poll
            }
        }, 30000);

        // Initial load
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const res = await fetch('/api/notifications/count', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
                });
                const data = await res.json();
                updateNotifyBadges(data.unread_count);
            } catch (err) {}
        });

        // ==================== EXISTING SETTINGS & SIDEBAR ====================
        let snapshot = localStorage.getItem('darkMode') || 'disabled';
        function openSettingsModal() {
            snapshot = document.documentElement.classList.contains('dark') ? 'enabled' : 'disabled';
            const modal = document.getElementById('settingsModal');
            const backdrop = document.getElementById('settingsBackdrop');
            const panel = document.getElementById('settingsPanel');
            modal.classList.remove('hidden'); void modal.offsetWidth;
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('scale-95','opacity-0');
            panel.classList.add('scale-100','opacity-100');
            updateToggleUI();
        }
        function closeSettingsModal() {
            if (snapshot === 'enabled') document.documentElement.classList.add('dark');
            else document.documentElement.classList.remove('dark');
            const modal = document.getElementById('settingsModal');
            const backdrop = document.getElementById('settingsBackdrop');
            const panel = document.getElementById('settingsPanel');
            backdrop.classList.add('opacity-0');
            panel.classList.remove('scale-100','opacity-100');
            panel.classList.add('scale-95','opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
            updateToggleUI();
        }
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
            updateToggleUI();
        }
        function saveSettingsState() {
            const isDarkNow = document.documentElement.classList.contains('dark');
            localStorage.setItem('darkMode', isDarkNow ? 'enabled' : 'disabled');
            snapshot = isDarkNow ? 'enabled' : 'disabled';
        }
        function updateToggleUI() {
            const isDark = document.documentElement.classList.contains('dark');
            const circle = document.getElementById('toggleCircle');
            const toggleBtn = document.getElementById('darkToggle');
            if (circle) circle.style.transform = isDark ? 'translateX(24px)' : 'translateX(0px)';
            if (toggleBtn) {
                toggleBtn.classList.toggle('bg-teal-600', isDark);
                toggleBtn.classList.toggle('bg-gray-300', !isDark);
            }
        }
        updateToggleUI();

        @if(session('success'))
            Swal.fire({icon:'success',title:'Success',text:'{{ session('success') }}',timer:3000,timerProgressBar:true,showConfirmButton:false,toast:true,position:'top-end',background:document.documentElement.classList.contains('dark')?'#1f2937':'#ffffff',color:document.documentElement.classList.contains('dark')?'#fff':'#374151'});
        @endif
        @if(session('error'))
            Swal.fire({icon:'error',title:'Error',text:'{{ session('error') }}',timer:4000,timerProgressBar:true,showConfirmButton:false,toast:true,position:'top-end',background:document.documentElement.classList.contains('dark')?'#1f2937':'#ffffff',color:document.documentElement.classList.contains('dark')?'#fff':'#374151'});
        @endif

        // MOBILE SIDEBAR FUNCTIONS
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('mobileSidebar');
            sidebar.classList.toggle('-translate-x-full');
            document.body.style.overflow = sidebar.classList.contains('-translate-x-full') ? '' : 'hidden';
        }

        function closeMobileSidebar() {
            const sidebar = document.getElementById('mobileSidebar');
            sidebar.classList.add('-translate-x-full');
            document.body.style.overflow = '';
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                closeMobileSidebar();
                // Also close notification dropdown on resize to desktop
                const dropdown = document.getElementById('notifyDropdown');
                dropdown.classList.add('hidden');
                notifyDropdownOpen = false;
            }
        });
    </script>
</body>
</html>