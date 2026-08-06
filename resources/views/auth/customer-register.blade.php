<!DOCTYPE html>
<html lang="en" class="{{ session('dark_mode') === 'enabled' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — Spa Alexandria</title>
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
        @keyframes floatDrift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(10px, -15px) scale(1.03); }
            66% { transform: translate(-5px, 10px) scale(0.97); }
        }
        .float-orb {
            animation: floatSlow 8s ease-in-out infinite;
        }
        .float-orb-reverse {
            animation: floatSlowReverse 10s ease-in-out infinite;
        }
        .float-drift {
            animation: floatDrift 14s ease-in-out infinite;
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
        .form-enter-d8 { animation-delay: 0.8s; }

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
            to { opacity: 1; transform: translateY(0); max-height: 400px; }
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

        /* Terms box gentle pulse on hover */
        .terms-box {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .terms-box:hover {
            box-shadow: 0 0 20px rgba(20, 184, 166, 0.1);
            transform: translateY(-1px);
        }

        /* Checkbox custom animation */
        .checkbox-spa {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .checkbox-spa:checked {
            transform: scale(1.1);
        }

        /* Footer fade in */
        @keyframes footerFade {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .footer-animate {
            opacity: 0;
            animation: footerFade 1s ease 1.2s forwards;
        }

        /* Input label float animation on focus */
        .input-group:focus-within label {
            color: rgb(20, 184, 166);
            transform: translateX(4px);
        }
        .input-group label {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex transition-colors duration-500 page-fade">

    <!-- Left Panel -->
    <div class="hidden lg:flex lg:w-5/12 xl:w-1/2 bg-teal-800 dark:bg-teal-950 sticky top-0 h-screen items-center justify-center p-20 overflow-hidden relative">
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-white/10 rounded-full blur-3xl float-orb"></div>
        <div class="absolute bottom-[-5%] right-[-5%] w-64 h-64 bg-teal-500/10 rounded-full blur-2xl float-orb-reverse"></div>
        <div class="absolute top-[20%] right-[15%] w-40 h-40 bg-teal-300/5 rounded-full blur-3xl float-drift"></div>
        <div class="absolute bottom-[25%] left-[10%] w-24 h-24 bg-white/5 rounded-full blur-2xl float-orb" style="animation-duration: 12s;"></div>
        
        <div class="relative z-10 text-center text-white">
            <h1 class="text-6xl xl:text-7xl serif italic mb-6 leading-tight text-reveal text-reveal-delay-1">Begin Your<br>Journey</h1>
            <p class="text-teal-200 tracking-[0.3em] uppercase text-xs font-light text-reveal text-reveal-delay-2">Join the Alexandria Community</p>
            
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
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Create Account</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Fill in the details below to secure your place in our sanctuary.</p>
            </header>

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 text-sm rounded-r-lg error-animate">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('customer.register') }}" method="POST" class="space-y-5">
                @csrf
                
                <div class="input-group form-enter form-enter-d2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Username</label>
                    <input type="text" name="username" value="{{ old('username') }}"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-spa"
                        placeholder="Choose a unique username" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 form-enter form-enter-d3">
                    <div class="input-group">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-spa"
                            placeholder="John" required>
                    </div>
                    <div class="input-group">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-spa"
                            placeholder="Doe" required>
                    </div>
                </div>

                <div class="input-group form-enter form-enter-d4 relative">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="registerPassword"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 pr-10 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-spa"
                            placeholder="Minimum 6 characters" required>
                        <button type="button" onclick="togglePassword('registerPassword', this)" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg class="w-5 h-5 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-3.925 4.472m-5.858-9.9l-3.29-3.29"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="input-group form-enter form-enter-d5 relative">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Confirm Password</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="registerPasswordConfirm"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-3 pr-10 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white input-spa"
                            placeholder="••••••••" required>
                        <button type="button" onclick="togglePassword('registerPasswordConfirm', this)" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg class="w-5 h-5 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-3.925 4.472m-5.858-9.9l-3.29-3.29"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Terms of Service -->
                <div class="p-4 bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 rounded-lg terms-box form-enter form-enter-d6">
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" name="terms_accepted" value="1" {{ old('terms_accepted') ? 'checked' : '' }}
                            class="mt-0.5 w-4 h-4 text-teal-600 rounded border-gray-300 focus:ring-teal-500 dark:bg-gray-800 dark:border-gray-600 checkbox-spa" required>
                        <div class="text-sm text-gray-700 dark:text-gray-300 transition-colors duration-300 group-hover:text-gray-900 dark:group-hover:text-gray-100">
                            I have read and agree to the 
                            <a href="{{ route('terms') }}" target="_blank" class="text-teal-600 dark:text-teal-400 font-semibold link-spa">Terms of Service</a>.
                            I understand the booking confirmation policy, no-show policy, and cancellation terms.
                        </div>
                    </label>
                </div>

                <!-- Privacy Consent -->
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg terms-box form-enter form-enter-d6">
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" name="privacy_consented" value="1" {{ old('privacy_consented') ? 'checked' : '' }}
                            class="mt-0.5 w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 checkbox-spa" required>
                        <div class="text-sm text-gray-700 dark:text-gray-300 transition-colors duration-300 group-hover:text-gray-900 dark:group-hover:text-gray-100">
                            I consent to the collection and processing of my personal information 
                            (name, contact details, and booking history) for account management, 
                            appointment scheduling, and service improvement, as described in the 
                            <a href="{{ route('privacy') }}" target="_blank" class="text-blue-600 dark:text-blue-400 font-semibold link-spa">Privacy Policy</a>.
                        </div>
                    </label>
                </div>

                <button type="submit" 
                    class="w-full bg-teal-600 text-white py-3.5 rounded-lg hover:bg-teal-700 transition font-semibold shadow-lg shadow-teal-200 dark:shadow-none btn-spa form-enter form-enter-d7">
                    Create Account
                </button>
            </form>

            <footer class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4 footer-animate">
                <div class="text-center sm:text-left">
                    <p class="text-xs text-gray-500 dark:text-gray-500 uppercase tracking-wider mb-1">Already a member?</p>
                    <a href="{{ route('login') }}" class="text-teal-600 dark:text-teal-400 font-semibold link-spa">
                        Return to Sanctuary
                    </a>
                </div>
                <a href="{{ route('landing') }}" class="text-sm text-gray-400 hover:text-teal-600 transition link-spa">
                    &larr; Back to Home
                </a>
            </footer>
        </div>
    </div>
</body>
<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    
    const openEyes = btn.querySelectorAll('.eye-open');
    const closedEyes = btn.querySelectorAll('.eye-closed');
    
    openEyes.forEach(eye => eye.classList.toggle('hidden'));
    closedEyes.forEach(eye => eye.classList.toggle('hidden'));
}
</script>
</html>