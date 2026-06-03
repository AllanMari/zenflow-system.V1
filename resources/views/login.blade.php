<!DOCTYPE html>
<html lang="en" class="{{ session('dark_mode') === 'enabled' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Login — Spa Alexandria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (localStorage.getItem('darkMode') === 'enabled') document.documentElement.classList.add('dark');
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500;600&display=swap');
        body { font-family: 'Montserrat', sans-serif; }
        .serif { font-family: 'Cormorant Garamond', serif; }

        /* Page load fade */
        @keyframes pageFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .page-fade {
            animation: pageFadeIn 1.2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        /* Gentle floating for decorative orbs */
        @keyframes floatSlow {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }
        @keyframes floatSlowReverse {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(15px) scale(0.95); }
        }
        .float-orb {
            animation: floatSlow 8s ease-in-out infinite;
        }
        .float-orb-reverse {
            animation: floatSlowReverse 10s ease-in-out infinite;
        }

        /* Text reveal animation */
        @keyframes textReveal {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .text-reveal {
            opacity: 0;
            animation: textReveal 1s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        .text-reveal-delay-1 { animation-delay: 0.3s; }
        .text-reveal-delay-2 { animation-delay: 0.6s; }
        .text-reveal-delay-3 { animation-delay: 0.9s; }

        /* Breathing pulse for icon */
        @keyframes breathe {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.05); }
        }
        .breathe {
            animation: breathe 6s ease-in-out infinite;
        }

        /* Form element staggered entrance */
        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-enter {
            opacity: 0;
            animation: slideUpFade 0.7s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        .form-enter-d1 { animation-delay: 0.1s; }
        .form-enter-d2 { animation-delay: 0.2s; }
        .form-enter-d3 { animation-delay: 0.3s; }
        .form-enter-d4 { animation-delay: 0.4s; }
        .form-enter-d5 { animation-delay: 0.5s; }
        .form-enter-d6 { animation-delay: 0.6s; }
        .form-enter-d7 { animation-delay: 0.7s; }

        /* Input focus glow */
        .input-spa {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .input-spa:focus {
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.15), 0 4px 20px rgba(20, 184, 166, 0.1);
            transform: translateY(-1px);
        }

        /* Button hover - gentle lift and glow */
        .btn-spa {
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .btn-spa::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }
        .btn-spa:hover::before {
            width: 300px;
            height: 300px;
        }
        .btn-spa:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(20, 184, 166, 0.3);
        }
        .btn-spa:active {
            transform: translateY(0);
        }

        /* Error message slide down */
        @keyframes errorSlide {
            from { opacity: 0; transform: translateY(-10px); max-height: 0; }
            to { opacity: 1; transform: translateY(0); max-height: 200px; }
        }
        .error-animate {
            animation: errorSlide 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        /* Link hover underline animation */
        .link-spa {
            position: relative;
        }
        .link-spa::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: currentColor;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .link-spa:hover::after {
            width: 100%;
        }

        /* Password toggle icon spin */
        .eye-toggle {
            transition: transform 0.3s ease, color 0.3s ease;
        }
        .eye-toggle:hover {
            transform: scale(1.1);
        }

        /* Footer fade in */
        @keyframes footerFade {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .footer-animate {
            opacity: 0;
            animation: footerFade 1s ease 1s forwards;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex transition-colors duration-500 page-fade">

    <!-- Left Panel -->
    <div class="hidden lg:flex lg:w-5/12 xl:w-1/2 bg-teal-800 dark:bg-teal-950 sticky top-0 h-screen items-center justify-center p-20 overflow-hidden relative">
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-white/10 rounded-full blur-3xl float-orb"></div>
        <div class="absolute bottom-[-5%] right-[-5%] w-64 h-64 bg-teal-500/10 rounded-full blur-2xl float-orb-reverse"></div>
        <div class="absolute top-[30%] right-[20%] w-32 h-32 bg-teal-400/5 rounded-full blur-2xl float-orb" style="animation-duration: 12s;"></div>
        
        <div class="relative z-10 text-center text-white">
            <h1 class="text-6xl xl:text-7xl serif italic mb-6 leading-tight text-reveal text-reveal-delay-1">Welcome<br>Back</h1>
            <p class="text-teal-200 tracking-[0.3em] uppercase text-xs font-light text-reveal text-reveal-delay-2">The Alexandria Experience</p>
            
            <div class="mt-12 breathe">
                <svg class="w-20 h-20 mx-auto text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M12 6v12M6 12h12M8.5 8.5l7 7M15.5 8.5l-7 7"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="w-full lg:w-7/12 xl:w-1/2 flex items-center justify-center p-8 md:p-16 lg:p-24 bg-white dark:bg-gray-900 min-h-screen transition-colors duration-500">
        <div class="max-w-md w-full">
            
            <header class="mb-10 form-enter form-enter-d1">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Member Login</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Enter your credentials to access your sanctuary.</p>
            </header>

            <!-- NEW (matches register view) -->
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 text-sm rounded-r-lg error-animate">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-6">
                @csrf
                
                <div class="form-enter form-enter-d2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Username</label>
                    <input type="text" name="username" value="{{ old('username') }}"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-spa"
                        placeholder="e.g. allan_mari" required>
                </div>

                <div class="form-enter form-enter-d3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" 
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 pr-10 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-spa"
                            placeholder="••••••••" required>
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-3.5 text-gray-400 hover:text-teal-600 transition eye-toggle">
                            <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between form-enter form-enter-d4">
                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" name="remember" 
                            class="w-4 h-4 text-teal-600 rounded border-gray-300 focus:ring-teal-500 dark:bg-gray-800 dark:border-gray-600 transition-all duration-300 group-hover:scale-110">
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400 transition-colors duration-300 group-hover:text-teal-600 dark:group-hover:text-teal-400">Remember me</span>
                    </label>
                </div>

                <button type="submit" 
                    class="w-full bg-teal-600 text-white py-3.5 rounded-lg hover:bg-teal-700 transition font-semibold shadow-lg shadow-teal-200 dark:shadow-none btn-spa form-enter form-enter-d5">
                    Enter Sanctuary
                </button>
            </form>

            <footer class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4 footer-animate">
                <div class="text-center sm:text-left">
                    <p class="text-xs text-gray-500 dark:text-gray-500 uppercase tracking-wider mb-1">New to Alexandria?</p>
                    <a href="{{ route('customer.register') }}" class="text-teal-600 dark:text-teal-400 font-semibold link-spa">
                        Create an Account
                    </a>
                </div>
                <a href="{{ route('landing') }}" class="text-sm text-gray-400 hover:text-teal-600 transition link-spa">
                    &larr; Back to Home
                </a>
            </footer>
        </div>
    </div>

    <script>
        function togglePassword() {
            const field = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (field.type === 'password') {
                field.type = 'text';
                icon.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />';
                    icon.style.transform = 'scale(1)';
                }, 150);
            } else {
                field.type = 'password';
                icon.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
                    icon.style.transform = 'scale(1)';
                }, 150);
            }
        }
    </script>
</body>
</html>