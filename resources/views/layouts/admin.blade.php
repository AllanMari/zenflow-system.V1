<!DOCTYPE html>
<html lang="en" class="{{ session('dark_mode') === 'enabled' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - Spa Alexandria</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex transition-colors duration-300">
    <!-- MOBILE HEADER -->
    <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-teal-800 dark:bg-teal-950 text-white flex items-center justify-between px-4 z-40 shadow-lg transition-colors duration-300">
        <button onclick="toggleMobileSidebar()" class="p-2 rounded-lg hover:bg-teal-700 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <span class="font-bold text-lg">Spa Alexandria</span>
        <div class="w-10"></div>
    </div>
    
    <!-- DESKTOP SIDEBAR -->
    <aside id="desktopSidebar" class="hidden md:flex w-64 bg-teal-800 dark:bg-teal-950 min-h-screen text-white flex-col transition-colors duration-300 shrink-0 print:hidden">
        <div class="p-4 font-bold text-xl border-b border-teal-700 flex items-center gap-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Spa Admin
        </div>
        
        <nav class="mt-4 flex-1 space-y-1">
            <a href="{{ route('admin-dashboard') }}" 
            class="block px-4 py-3 {{ request()->routeIs('admin-dashboard') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                Dashboard
            </a>
            <a href="{{ route('admin.users.index') }}" 
            class="block px-4 py-3 {{ request()->routeIs('admin.users.*') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                User Management
            </a>
            <a href="{{ route('admin.schedules') }}" 
            class="block px-4 py-3 {{ request()->routeIs('admin.schedule*') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Schedule Management
            </a>
            <a href="{{ route('admin.services.index') }}" 
            class="block px-4 py-3 {{ request()->routeIs('admin.services*') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Services
            </a>
            <a href="{{ route('admin.sales') }}" 
            class="block px-4 py-3 {{ request()->routeIs('admin.sales') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Sales Report
            </a>
            <a href="{{ route('admin.landing.editor') }}" 
            class="block px-4 py-3 {{ request()->routeIs('admin.landing.*') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Landing Page Editor
            </a>
            <a href="{{ route('admin.rooms.index') }}" 
            class="block px-4 py-3 {{ request()->routeIs('admin.rooms.*') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Rooms
            </a>
        </nav>

        <div class="p-4 space-y-1 border-t border-teal-700">
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
        <div class="absolute left-0 top-0 bottom-0 w-72 bg-teal-800 dark:bg-teal-950 text-white flex flex-col overflow-y-auto transition-colors duration-300">
            <div class="p-4 font-bold text-xl border-b border-teal-700 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Spa Alexandria
                </span>
                <button onclick="closeMobileSidebar()" class="p-1 rounded-lg hover:bg-teal-700 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <nav class="mt-4 flex-1 space-y-1">
                <a href="{{ route('admin-dashboard') }}" 
                class="block px-4 py-3 {{ request()->routeIs('admin-dashboard') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('admin.users.index') }}" 
                class="block px-4 py-3 {{ request()->routeIs('admin.users.*') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    User Management
                </a>
                <a href="{{ route('admin.schedules') }}" 
                class="block px-4 py-3 {{ request()->routeIs('admin.schedule*') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Schedule Management
                </a>
                <a href="{{ route('admin.services.index') }}" 
                class="block px-4 py-3 {{ request()->routeIs('admin.services*') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Services
                </a>
                <a href="{{ route('admin.sales') }}" 
                class="block px-4 py-3 {{ request()->routeIs('admin.sales') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Sales Report
                </a>
                <a href="{{ route('admin.landing.editor') }}" 
                class="block px-4 py-3 {{ request()->routeIs('admin.landing.*') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Landing Page Editor
                </a>
                <a href="{{ route('admin.rooms.index') }}" 
                class="block px-4 py-3 {{ request()->routeIs('admin.rooms.*') ? 'bg-teal-700' : 'hover:bg-teal-700' }} transition flex items-center gap-3 rounded-lg mx-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Rooms
                </a>
            </nav>
            
            <div class="p-4 space-y-1 border-t border-teal-700">
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

    <!-- Main Content -->
    <main class="flex-1 p-6 pt-20 md:pt-6 overflow-y-auto">
        @yield('content')
    </main>

    <!-- Settings Modal -->
    <div id="settingsModal" class="fixed inset-0 z-50 hidden flex items-center justify-center print:hidden" aria-modal="true">
        <div id="settingsBackdrop" 
             class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity duration-300 opacity-0" 
             onclick="closeSettingsModal()"></div>
        
        <div id="settingsPanel" 
             class="relative bg-white dark:bg-gray-800 dark:text-white rounded-2xl p-6 w-full max-w-md shadow-2xl border border-gray-100 dark:border-gray-700 transform transition-all duration-300 scale-95 opacity-0 mx-4">
            
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold">Settings</h2>
                <button type="button" onclick="closeSettingsModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
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
                        <span class="font-semibold block dark:text-white">Night Mode</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Toggle dark theme</span>
                    </div>
                </div>
                <button type="button" onclick="toggleDarkMode()" id="darkToggle" class="w-14 h-8 bg-gray-300 dark:bg-teal-600 rounded-full relative transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    <div id="toggleCircle" class="absolute top-1 left-1 w-6 h-6 bg-white rounded-full shadow-md transition-transform duration-300 ease-out"></div>
                </button>
            </div>

            <hr class="mb-6 dark:border-gray-600">

            <form action="{{ route('admin.profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block mb-1.5 text-sm font-semibold dark:text-gray-200">First Name</label>
                    <input type="text" name="first_name" value="{{ auth()->user()->first_name }}" class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent outline-none transition" required>
                </div>
                <div class="mb-6">
                    <label class="block mb-1.5 text-sm font-semibold dark:text-gray-200">Last Name</label>
                    <input type="text" name="last_name" value="{{ auth()->user()->last_name }}" class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent outline-none transition" required>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeSettingsModal()" class="px-5 py-2.5 border rounded-lg hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white transition font-medium">Cancel</button>
                    <button type="submit" onclick="saveSettingsState()" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition font-medium shadow-lg shadow-teal-200 dark:shadow-none">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    @stack('scripts')
    
    <script>
        let snapshot = localStorage.getItem('darkMode') || 'disabled';

        function openSettingsModal() {
            snapshot = document.documentElement.classList.contains('dark') ? 'enabled' : 'disabled';
            const modal = document.getElementById('settingsModal');
            const backdrop = document.getElementById('settingsBackdrop');
            const panel = document.getElementById('settingsPanel');
            
            modal.classList.remove('hidden');
            void modal.offsetWidth;
            
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('scale-95', 'opacity-0');
            panel.classList.add('scale-100', 'opacity-100');
            
            updateToggleUI();
        }

        function closeSettingsModal() {
            if (snapshot === 'enabled') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            
            const modal = document.getElementById('settingsModal');
            const backdrop = document.getElementById('settingsBackdrop');
            const panel = document.getElementById('settingsPanel');
            
            backdrop.classList.add('opacity-0');
            panel.classList.remove('scale-100', 'opacity-100');
            panel.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
            
            updateToggleUI();
        }

        function toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
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
            
            if (circle) {
                circle.style.transform = isDark ? 'translateX(24px)' : 'translateX(0px)';
            }
            
            if (toggleBtn) {
                toggleBtn.classList.toggle('bg-teal-600', isDark);
                toggleBtn.classList.toggle('bg-gray-300', !isDark);
            }
        }

        updateToggleUI();

        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '{{ session('success') }}',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#374151'
            });
        @endif
        
        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}',
                timer: 4000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#374151'
            });
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
            }
        });
    </script>
</body>
</html>