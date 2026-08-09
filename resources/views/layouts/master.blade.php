<!DOCTYPE html>
<html lang="en" class="{{ session('dark_mode') === 'enabled' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $userRole ?? 'Dashboard') — Spa Alexandria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        (function() {
            if (localStorage.getItem('darkMode') === 'enabled') document.documentElement.classList.add('dark');
        })();
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: {
                            50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',
                            500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a',950:'#042f2e',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }

        /* Sidebar collapse transition */
        #desktopSidebar { transition: width 0.35s cubic-bezier(0.32, 0.72, 0, 1); }
        #desktopSidebar.collapsed { width: 4.5rem; }

        /* Collapsible text elements */
        .nav-text, .brand-text {
            transition: opacity 0.3s ease, max-width 0.35s cubic-bezier(0.32, 0.72, 0, 1), margin 0.3s ease, padding 0.3s ease;
            max-width: 200px;
            overflow: hidden;
            white-space: nowrap;
            flex-shrink: 0;
            opacity: 1;
        }
        #desktopSidebar.collapsed .nav-text,
        #desktopSidebar.collapsed .brand-text {
            opacity: 0;
            pointer-events: none;
            max-width: 0;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            min-width: 0 !important;
            display: none;
        }

        /* Sidebar footer collapse */
        .sidebar-footer {
            transition: opacity 0.3s ease, max-height 0.35s ease, padding 0.3s ease, border-width 0.3s ease;
            max-height: 300px;
            overflow: hidden;
        }
        #desktopSidebar.collapsed .sidebar-footer {
            opacity: 0;
            pointer-events: none;
            max-height: 0;
            padding-top: 0;
            padding-bottom: 0;
            border-width: 0;
        }

        /* Header in collapsed state */
        #desktopSidebar.collapsed .h-\[72px\] {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
            gap: 0;
            position: relative;
        }

        /* Keep the collapse button away from the centered logo */
        #desktopSidebar.collapsed .h-\[72px\] > button {
            position: absolute;
            right: -14px;
            top: 50%;
            transform: translateY(-50%);
            width: 28px;
            height: 28px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: white;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            opacity: 0;
            pointer-events: none;
            z-index: 50;
            transition: opacity 0.2s ease;
        }

        #desktopSidebar.collapsed .h-\[72px\]:hover > button,
        #desktopSidebar.collapsed .h-\[72px\] > button:hover {
            opacity: 1;
            pointer-events: auto;
        }

        .dark #desktopSidebar.collapsed .h-\[72px\] > button {
            background: #0f172a;
            border-color: #334155;
        }

        /* Nav items */
        .nav-item { position: relative; transition: all 0.2s ease; }
        .nav-item::before {
            content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%) scaleY(0);
            width: 3px; height: 20px; background: linear-gradient(180deg, #5eead4, #0d9488);
            border-radius: 0 4px 4px 0; transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .nav-item.active::before { transform: translateY(-50%) scaleY(1); }
        .nav-item.active {
            background: linear-gradient(90deg, rgba(20,184,166,0.12) 0%, rgba(20,184,166,0.02) 100%);
        }
        .dark .nav-item.active {
            background: linear-gradient(90deg, rgba(20,184,166,0.15) 0%, rgba(20,184,166,0.03) 100%);
        }

        /* Collapsed nav items */
        #desktopSidebar.collapsed .nav-item {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
            gap: 0 !important;
        }
        #desktopSidebar.collapsed .nav-item svg { margin: 0; }
        #desktopSidebar.collapsed .nav-item:hover,
        #desktopSidebar.collapsed .nav-item.active {
            background: transparent !important;
        }
        #desktopSidebar.collapsed .nav-item::before { display: none; }
        /* Show the red notification dot only when collapsed */
        #desktopSidebar.collapsed .collapsed-dot {
            display: block;
        }

        /* Ensure the full numbered badge fully hides to prevent alignment bugs */
        #desktopSidebar.collapsed .nav-text {
            opacity: 0;
            pointer-events: none;
            max-width: 0;
            min-width: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            display: none;
        }
        /* Tooltip Fix - Completely Stable Fade */
        #desktopSidebar.collapsed .nav-item::after {
            content: attr(data-label) !important; 
            position: absolute !important; 
            left: calc(100% + 12px) !important; 
            top: 50% !important; 
            transform: translateY(-50%) !important;
            background: #0f172a !important; 
            color: #fff !important; 
            padding: 6px 12px !important; 
            border-radius: 8px !important;
            font-size: 12px !important; 
            font-weight: 500 !important; 
            white-space: nowrap !important; 
            z-index: 9999 !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important; 
            pointer-events: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transition: opacity 0.15s ease !important;
        }

        #desktopSidebar.collapsed .nav-item:hover::after {
            opacity: 1 !important;
            visibility: visible !important;
        }

        /* Notification pulse */
        @keyframes np {
            0% { box-shadow: 0 0 0 0 rgba(239,68,68,0.5); }
            70% { box-shadow: 0 0 0 10px rgba(239,68,68,0); }
            100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); }
        }
        .np { animation: np 2.2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }

        /* Notification dropdown */
        #notifyDropdown {
            transition: opacity 0.2s ease, transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.2s;
            visibility: hidden;
        }
        #notifyDropdown.open { opacity: 1; transform: scale(1); visibility: visible; pointer-events: auto; }
        #notifyDropdown.closed { opacity: 0; transform: scale(0.96); visibility: hidden; pointer-events: none; }

        /* Skeleton */
        @keyframes sh { 0% { background-position: -200px 0; } 100% { background-position: calc(200px + 100%) 0; } }
        .sk {
            background: #e2e8f0; background-image: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
            background-size: 200px 100%; animation: sh 1.5s ease-in-out infinite; border-radius: 4px;
        }
        .dark .sk {
            background: #334155; background-image: linear-gradient(90deg, #334155 25%, #475569 50%, #334155 75%);
        }

        /* Settings modal */
        #settingsModal .mb { transition: opacity 0.25s ease; }
        #settingsModal .mp { transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease; }

        /* Main background */
        .main-bg { background: linear-gradient(160deg, #f8fafc 0%, #f0f4f8 50%, #f1f5f9 100%); }
        .dark .main-bg { background: linear-gradient(160deg, #0b1120 0%, #0f172a 50%, #1e293b 100%); }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100 dark:bg-[#0b1120] h-screen overflow-hidden flex font-sans antialiased">

<!-- Desktop Sidebar -->
<aside id="desktopSidebar" class="hidden md:flex fixed left-0 top-0 h-full w-[260px] bg-white dark:bg-[#0f172a] flex-col border-r border-gray-200/80 dark:border-gray-800/60 z-40 shadow-[4px_0_24px_rgba(0,0,0,0.04)] dark:shadow-[4px_0_24px_rgba(0,0,0,0.3)] transition-colors duration-300">
    <div class="h-[72px] flex items-center gap-3 px-5 shrink-0 border-b border-gray-100 dark:border-gray-800/60">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center shadow-lg shadow-brand-500/20 shrink-0">
            @yield('logo-icon')
        </div>
        <div class="brand-text">
            <p class="font-bold text-[15px] text-gray-800 dark:text-white tracking-tight leading-tight">Spa Alexandria</p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 font-medium tracking-wide uppercase">{{ $roleLabel ?? 'Panel' }}</p>
        </div>
        <button onclick="toggleSidebar()" class="ml-auto p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition shrink-0 group" title="Collapse">
            <svg id="collapseIcon" class="w-4 h-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
        </button>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
        @yield('sidebar-nav')
    </nav>
    <div class="sidebar-footer p-3 border-t border-gray-100 dark:border-gray-800/60 shrink-0 space-y-0.5">
        <button onclick="openSettingsModal()" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
            <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="nav-text">Settings</span>
        </button>
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold text-red-500/80 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/10 transition">
                <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                <span class="nav-text">Logout</span>
            </button>
        </form>
    </div>
</aside>

<!-- Mobile Sidebar -->
<div id="mobileSidebar" class="fixed inset-0 z-50 md:hidden print:hidden hidden">
    <div id="msBackdrop" class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity opacity-0" onclick="closeMobileSidebar()"></div>
    <div id="msPanel" class="absolute left-0 top-0 bottom-0 w-[280px] bg-white dark:bg-[#0f172a] flex flex-col shadow-2xl transform -translate-x-full transition-transform duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]">
        <div class="h-[72px] flex items-center justify-between px-5 shrink-0 border-b border-gray-100 dark:border-gray-800/60">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center shadow-lg shadow-brand-500/20">
                    @yield('logo-icon')
                </div>
                <div>
                    <p class="font-bold text-[15px] text-gray-800 dark:text-white tracking-tight">Spa Alexandria</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 font-medium uppercase tracking-wide">{{ $roleLabel ?? 'Panel' }}</p>
                </div>
            </div>
            <button onclick="closeMobileSidebar()" class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
            @yield('sidebar-nav-mobile')
        </nav>
        <div class="p-3 border-t border-gray-100 dark:border-gray-800/60 shrink-0 space-y-0.5">
            <button onclick="openSettingsModal();closeMobileSidebar();" class="w-full flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold text-gray-600 dark:text-gray-400">
                <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </button>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 rounded-xl px-3 py-3 text-[13px] font-semibold text-red-500/80">
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Main Content -->
<main id="mainContent" class="flex-1 md:ml-[260px] h-full overflow-y-auto main-bg transition-all duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]">
    <!-- Top Bar -->
    <header class="sticky top-0 z-20 bg-white/70 dark:bg-[#0f172a]/70 backdrop-blur-xl border-b border-gray-200/60 dark:border-gray-800/40 px-4 md:px-8 h-[72px] flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3 min-w-0">
            <button onclick="toggleMobileSidebar()" class="md:hidden -ml-1 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition text-gray-500 dark:text-gray-400 active:scale-95">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>
            <h1 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white tracking-tight truncate leading-tight">@yield('title', 'Dashboard')</h1>
        </div>
        <div class="flex items-center gap-1 md:gap-2 shrink-0">
            <!-- Notification -->
            <div class="relative" id="notifyWrapper">
                <button onclick="toggleNotifyDropdown(event)" class="relative p-2.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition active:scale-95 group" aria-label="Notifications" aria-expanded="false" id="notifyBellBtn">
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                    <span id="notifyBadge" class="hidden absolute top-1.5 right-1.5 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 ring-2 ring-white dark:ring-[#0f172a] np">0</span>
                </button>
                <!-- Mobile backdrop -->
                <div id="notifyBackdrop" class="hidden fixed inset-0 bg-black/20 backdrop-blur-[2px] z-40 transition-opacity sm:hidden" onclick="closeNotifyDropdown()"></div>
                <!-- Dropdown -->
                <div id="notifyDropdown" class="closed z-50 fixed left-4 right-4 top-[88px] sm:absolute sm:left-auto sm:right-0 sm:top-full sm:mt-2 sm:w-80 md:w-[360px] sm:inset-auto bg-white dark:bg-[#1e293b] rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700/50 overflow-hidden origin-top sm:origin-top-right">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between bg-gray-50/60 dark:bg-gray-800/40">
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-sm text-gray-800 dark:text-white">Notifications</h3>
                            <span id="notifyCountBadge" class="hidden px-1.5 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-[10px] font-bold rounded-md">0</span>
                        </div>
                        <button onclick="markAllRead()" class="text-xs text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 font-semibold transition px-2 py-1 rounded-lg hover:bg-brand-50 dark:hover:bg-brand-900/20">Mark all read</button>
                    </div>
                    <div id="notifyList" class="max-h-[60vh] sm:max-h-80 overflow-y-auto"></div>
                    <div class="px-3 py-2.5 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50/60 dark:bg-gray-800/40">
                        <div class="flex justify-between items-center text-xs">
                            <button onclick="loadNotifications(currentPage-1)" id="notifyPrev" class="px-3 py-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 disabled:opacity-30 disabled:cursor-not-allowed transition font-semibold" disabled>← Prev</button>
                            <span id="notifyPageInfo" class="text-gray-500 dark:text-gray-400 font-semibold tabular-nums">Page 1</span>
                            <button onclick="loadNotifications(currentPage+1)" id="notifyNext" class="px-3 py-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 disabled:opacity-30 disabled:cursor-not-allowed transition font-semibold" disabled>Next →</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1 hidden sm:block"></div>
            <!-- User -->
            <div class="flex items-center gap-2.5 pl-1">
                <div class="text-right hidden lg:block leading-tight">
                    <p class="text-[13px] font-bold text-gray-700 dark:text-gray-200">{{ auth()->user()->first_name ?? 'User' }} {{ auth()->user()->last_name ?? '' }}</p>
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider">{{ $userRole ?? 'User' }}</p>
                </div>
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-[13px] font-bold shadow-lg shadow-brand-500/20 ring-2 ring-white dark:ring-[#0f172a] select-none">
                    {{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1) . substr(auth()->user()->last_name ?? '', 0, 1)) }}
                </div>
            </div>
        </div>
    </header>
    <div class="p-4 md:p-8">
        @yield('content')
    </div>
</main>

<!-- Settings Modal -->
<div id="settingsModal" class="fixed inset-0 z-50 hidden flex items-center justify-center print:hidden" aria-modal="true">
    <div id="settingsBackdrop" class="mb absolute inset-0 bg-black/50 backdrop-blur-sm opacity-0" onclick="closeSettingsModal()"></div>
    <div id="settingsPanel" class="mp relative bg-white dark:bg-[#1e293b] dark:text-white rounded-2xl p-0 w-full max-w-md shadow-2xl border border-gray-100 dark:border-gray-700/50 transform scale-[0.97] opacity-0 mx-4 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between">
            <h2 class="text-lg font-bold tracking-tight">Settings</h2>
            <button onclick="closeSettingsModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-gray-100 dark:border-gray-700/40">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-brand-100 dark:bg-brand-900/30 rounded-xl">
                        <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                    </div>
                    <div>
                        <span class="font-bold block text-sm dark:text-white">Night Mode</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Toggle dark theme</span>
                    </div>
                </div>
                <button onclick="toggleDarkMode()" id="darkToggle" class="w-12 h-7 bg-gray-300 dark:bg-brand-600 rounded-full relative transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#1e293b]">
                    <div id="toggleCircle" class="absolute top-0.5 left-0.5 w-6 h-6 bg-white rounded-full shadow-md transition-transform duration-300 ease-out"></div>
                </button>
            </div>
            <form action="{{ route($settingsRoute ?? 'profile.update') }}" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block mb-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">First Name</label>
                    <input type="text" name="first_name" value="{{ auth()->user()->first_name }}" class="w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-xl p-3 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none transition text-sm font-medium" required>
                </div>
                <div>
                    <label class="block mb-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Last Name</label>
                    <input type="text" name="last_name" value="{{ auth()->user()->last_name }}" class="w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-xl p-3 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none transition text-sm font-medium" required>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeSettingsModal()" class="px-5 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition">Cancel</button>
                    <button type="submit" onclick="saveSettingsState()" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl transition shadow-lg shadow-brand-500/20">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@stack('scripts')

<script>
function esc(t){if(!t)return'';const d=document.createElement('div');d.textContent=t;return d.innerHTML}

// Sidebar
let sbC=localStorage.getItem('sidebarCollapsed')==='true';
const sb=document.getElementById('desktopSidebar'),mc=document.getElementById('mainContent'),ci=document.getElementById('collapseIcon');
function applySB(){if(sbC){sb.classList.add('collapsed');mc.classList.remove('md:ml-[260px]');mc.classList.add('md:ml-[4.5rem]');ci.style.transform='rotate(180deg)'}else{sb.classList.remove('collapsed');mc.classList.add('md:ml-[260px]');mc.classList.remove('md:ml-[4.5rem]');ci.style.transform='rotate(0deg)'}}
applySB();
function toggleSidebar(){sbC=!sbC;localStorage.setItem('sidebarCollapsed',sbC);applySB()}

// Mobile sidebar
function toggleMobileSidebar(){const p=document.getElementById('msPanel'),b=document.getElementById('msBackdrop'),s=document.getElementById('mobileSidebar');if(p.classList.contains('-translate-x-full')){s.classList.remove('hidden');requestAnimationFrame(()=>{b.classList.remove('opacity-0');p.classList.remove('-translate-x-full')});document.body.style.overflow='hidden'}else{closeMobileSidebar()}}
function closeMobileSidebar(){const p=document.getElementById('msPanel'),b=document.getElementById('msBackdrop'),s=document.getElementById('mobileSidebar');b.classList.add('opacity-0');p.classList.add('-translate-x-full');document.body.style.overflow='';setTimeout(()=>s.classList.add('hidden'),300)}
window.addEventListener('resize',()=>{if(window.innerWidth>=768)closeMobileSidebar()})

// Notifications
let cp=1,ndO=false;
const CSRF=document.querySelector('meta[name="csrf-token"]')?.content||'';
const nd=document.getElementById('notifyDropdown'),nw=document.getElementById('notifyWrapper'),nb=document.getElementById('notifyBellBtn'),nback=document.getElementById('notifyBackdrop');
function toggleNotifyDropdown(e){e.stopPropagation();e.preventDefault();ndO?closeNotifyDropdown():openNotifyDropdown()}
function openNotifyDropdown(){ndO=true;nb.setAttribute('aria-expanded','true');nd.classList.remove('closed');nd.classList.add('open');if(nback)nback.classList.remove('hidden');loadNotifications(1)}
function closeNotifyDropdown(){ndO=false;nb.setAttribute('aria-expanded','false');nd.classList.remove('open');nd.classList.add('closed');if(nback)nback.classList.add('hidden')}
document.addEventListener('click',e=>{if(ndO&&nw&&!nw.contains(e.target))closeNotifyDropdown()});
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&ndO)closeNotifyDropdown()});
function sk(){return[0,0,0].map(()=>`<div class="p-4 border-b border-gray-100 dark:border-gray-700/40"><div class="flex items-start gap-3"><div class="w-2 h-2 rounded-full mt-2 shrink-0 sk"></div><div class="flex-1 space-y-2"><div class="h-4 w-2/3 sk rounded"></div><div class="h-3 w-full sk rounded"></div><div class="h-3 w-1/3 sk rounded"></div></div></div></div>`).join('')}
async function loadNotifications(p=1){const l=document.getElementById('notifyList');l.innerHTML=sk();try{const r=await fetch(`/api/notifications?page=${p}`,{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}});if(!r.ok)throw new Error(`HTTP ${r.status}`);const d=await r.json();cp=d.pagination?.current_page||1;const lp=d.pagination?.last_page||1,ns=d.notifications||[];if(!ns.length){l.innerHTML=`<div class="p-10 text-center"><div class="w-14 h-14 bg-gray-100 dark:bg-gray-800/60 rounded-2xl flex items-center justify-center mx-auto mb-4"><svg class="w-7 h-7 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg></div><p class="text-sm font-bold text-gray-500 dark:text-gray-400">No notifications</p><p class="text-xs text-gray-400 dark:text-gray-500 mt-1">We'll let you know when something arrives</p></div>`}else{const c={critical:{dot:'bg-red-500',h:'hover:bg-red-50 dark:hover:bg-red-900/10'},warning:{dot:'bg-amber-400',h:'hover:bg-amber-50 dark:hover:bg-amber-900/10'},success:{dot:'bg-emerald-400',h:'hover:bg-emerald-50 dark:hover:bg-emerald-900/10'},info:{dot:'bg-blue-400',h:'hover:bg-blue-50 dark:hover:bg-blue-900/10'}};l.innerHTML=ns.map(n=>{const x=c[n.severity]||c.info;return`<div class="group p-3.5 border-b border-gray-100 dark:border-gray-700/40 ${x.h} transition cursor-pointer" onclick="handleNotifyClick('${esc(n.id)}','${esc(n.action_url||'')}')"><div class="flex items-start gap-3"><div class="w-2 h-2 rounded-full mt-1.5 shrink-0 ${x.dot}"></div><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 dark:text-gray-100 leading-snug">${esc(n.title)}</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2 leading-relaxed">${esc(n.message)}</p><p class="text-[11px] text-gray-400 dark:text-gray-500 mt-2 font-semibold">${esc(n.time)}</p></div></div></div>`}).join('')}document.getElementById('notifyPageInfo').textContent=`Page ${cp} of ${lp}`;document.getElementById('notifyPrev').disabled=cp<=1;document.getElementById('notifyNext').disabled=cp>=lp;const u=d.unread_count||0;updateBadge(u);const cb=document.getElementById('notifyCountBadge');if(cb){if(u>0){cb.textContent=u>99?'99+':u;cb.classList.remove('hidden')}else{cb.classList.add('hidden')}}}catch(err){console.error('Notify error:',err);l.innerHTML=`<div class="p-10 text-center"><div class="w-14 h-14 bg-red-100 dark:bg-red-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4"><svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.77-1.333-2.694-1.333-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg></div><p class="text-sm font-bold text-gray-500 dark:text-gray-400">Failed to load</p><button onclick="loadNotifications(${p})" class="mt-3 text-xs text-brand-600 dark:text-brand-400 font-semibold hover:underline">Retry</button></div>`}}
function updateBadge(c){const b=document.getElementById('notifyBadge');if(!b)return;if(c>0){b.textContent=c>99?'99+':c;b.classList.remove('hidden');b.classList.add('np')}else{b.classList.add('hidden');b.classList.remove('np')}}
async function handleNotifyClick(id,url){try{await fetch(`/api/notifications/${id}/read`,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}});url?window.location.href=url:loadNotifications(cp)}catch(e){console.error(e)}}
async function markAllRead(){try{await fetch('/api/notifications/read-all',{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}});loadNotifications(1);updateBadge(0)}catch(e){console.error(e)}}
setInterval(async()=>{try{const r=await fetch('/api/notifications/count',{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}});if(r.ok){const d=await r.json();updateBadge(d.unread_count||0)}}catch(e){}},30000);
document.addEventListener('DOMContentLoaded',async()=>{try{const r=await fetch('/api/notifications/count',{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}});if(r.ok){const d=await r.json();updateBadge(d.unread_count||0)}}catch(e){}});

// Settings & Dark Mode
let snap=localStorage.getItem('darkMode')||'disabled';
function openSettingsModal(){snap=document.documentElement.classList.contains('dark')?'enabled':'disabled';const m=document.getElementById('settingsModal'),b=document.getElementById('settingsBackdrop'),p=document.getElementById('settingsPanel');m.classList.remove('hidden');void m.offsetWidth;b.classList.remove('opacity-0');p.classList.remove('scale-[0.97]','opacity-0');p.classList.add('scale-100','opacity-100');updateToggle()}
function closeSettingsModal(){snap==='enabled'?document.documentElement.classList.add('dark'):document.documentElement.classList.remove('dark');const m=document.getElementById('settingsModal'),b=document.getElementById('settingsBackdrop'),p=document.getElementById('settingsPanel');b.classList.add('opacity-0');p.classList.remove('scale-100','opacity-100');p.classList.add('scale-[0.97]','opacity-0');setTimeout(()=>m.classList.add('hidden'),300);updateToggle()}
function toggleDarkMode(){const d=document.documentElement.classList.toggle('dark');localStorage.setItem('darkMode',d?'enabled':'disabled');updateToggle()}
function saveSettingsState(){const d=document.documentElement.classList.contains('dark');localStorage.setItem('darkMode',d?'enabled':'disabled');snap=d?'enabled':'disabled'}
function updateToggle(){const d=document.documentElement.classList.contains('dark'),c=document.getElementById('toggleCircle'),t=document.getElementById('darkToggle');if(c)c.style.transform=d?'translateX(20px)':'translateX(0px)';if(t){t.classList.toggle('bg-brand-600',d);t.classList.toggle('bg-gray-300',!d)}}
updateToggle();

@if(session('success'))
Swal.fire({icon:'success',title:'Success',text:'{{ session('success') }}',timer:3000,timerProgressBar:true,showConfirmButton:false,toast:true,position:'top-end',background:document.documentElement.classList.contains('dark')?'#1e293b':'#ffffff',color:document.documentElement.classList.contains('dark')?'#fff':'#374151'});
@endif
@if(session('error'))
Swal.fire({icon:'error',title:'Error',text:'{{ session('error') }}',timer:4000,timerProgressBar:true,showConfirmButton:false,toast:true,position:'top-end',background:document.documentElement.classList.contains('dark')?'#1e293b':'#ffffff',color:document.documentElement.classList.contains('dark')?'#fff':'#374151'});
@endif
</script>
</body>
</html>