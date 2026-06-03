<!DOCTYPE html>
<html lang="en" class="{{ session('dark_mode') === 'enabled' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spa Alexandria — Book Your Escape</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        if (localStorage.getItem('darkMode') === 'enabled') document.documentElement.classList.add('dark');
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { teal: { 50:'#f0fdfa',100:'#ccfbf1',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } } } } }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500;600&display=swap');
        body { font-family: 'Montserrat', sans-serif; }
        .serif { font-family: 'Cormorant Garamond', serif; }

        /* Page fade */
        @keyframes pageFadeIn { from { opacity: 0; } to { opacity: 1; } }
        .page-fade { animation: pageFadeIn 1s ease forwards; }

        /* Floating orbs */
        @keyframes floatSlow { 0%,100%{transform:translateY(0) scale(1)} 50%{transform:translateY(-20px) scale(1.05)} }
        @keyframes floatSlowReverse { 0%,100%{transform:translateY(0) scale(1)} 50%{transform:translateY(15px) scale(0.95)} }
        .float-orb { animation: floatSlow 8s ease-in-out infinite; }
        .float-orb-reverse { animation: floatSlowReverse 10s ease-in-out infinite; }

        /* Text reveal */
        @keyframes textReveal { from { opacity:0; transform:translateY(30px) } to { opacity:1; transform:translateY(0) } }
        .text-reveal { opacity:0; animation: textReveal 1s cubic-bezier(0.4,0,0.2,1) forwards; }
        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.5s; }
        .delay-3 { animation-delay: 0.8s; }

        /* Staggered entrance */
        @keyframes slideUpFade { from { opacity:0; transform:translateY(25px) } to { opacity:1; transform:translateY(0) } }
        .enter { opacity:0; animation: slideUpFade 0.7s cubic-bezier(0.4,0,0.2,1) forwards; }
        .enter-d1 { animation-delay: 0.1s; }
        .enter-d2 { animation-delay: 0.2s; }
        .enter-d3 { animation-delay: 0.3s; }
        .enter-d4 { animation-delay: 0.4s; }

        /* Card hover */
        .card-spa {
            transition: all 0.5s cubic-bezier(0.4,0,0.2,1);
        }
        .card-spa:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(20,184,166,0.12);
        }

        /* Service image zoom */
        .img-zoom { transition: transform 0.7s cubic-bezier(0.4,0,0.2,1); }
        .group:hover .img-zoom { transform: scale(1.08); }

        /* Button ripple */
        .btn-spa {
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        }
        .btn-spa::before {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            width: 0; height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            transform: translate(-50%,-50%);
            transition: width 0.6s ease, height 0.6s ease;
        }
        .btn-spa:hover::before { width: 300px; height: 300px; }
        .btn-spa:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(20,184,166,0.25);
        }

        /* Hero button ghost */
        .btn-ghost {
            transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        }
        .btn-ghost:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-2px);
        }

        /* Link underline */
        .link-spa {
            position: relative;
        }
        .link-spa::after {
            content: '';
            position: absolute;
            bottom: -2px; left: 0;
            width: 0; height: 1px;
            background: currentColor;
            transition: width 0.4s cubic-bezier(0.4,0,0.2,1);
        }
        .link-spa:hover::after { width: 100%; }

        /* Footer fade */
        @keyframes footerFade { from { opacity:0 } to { opacity:1 } }
        .footer-animate { opacity:0; animation: footerFade 1s ease 1s forwards; }

        /* Breathing icon */
        @keyframes breathe { 0%,100%{opacity:0.3;transform:scale(1)} 50%{opacity:0.5;transform:scale(1.05)} }
        .breathe { animation: breathe 6s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 transition-colors duration-500 page-fade">

    <!-- Hero -->
    <section class="relative bg-teal-800 dark:bg-teal-950 text-white overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            @if($hero->image)
                <img src="{{ $hero->image }}" class="w-full h-full object-cover" alt="Spa">
            @else
                <div class="w-full h-full bg-gradient-to-br from-teal-600 to-teal-900"></div>
            @endif
        </div>
        <!-- Floating ambient orbs -->
        <div class="absolute top-[-10%] left-[-5%] w-72 h-72 bg-white/5 rounded-full blur-3xl float-orb"></div>
        <div class="absolute bottom-[-10%] right-[-5%] w-64 h-64 bg-teal-400/5 rounded-full blur-3xl float-orb-reverse"></div>
        
        <div class="relative max-w-6xl mx-auto px-6 py-24 text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-4 tracking-tight text-reveal delay-1">{{ $hero->title }}</h1>
            <p class="text-lg md:text-xl text-teal-100 max-w-2xl mx-auto mb-8 text-reveal delay-2">{{ $hero->subtitle }}</p>
            <div class="flex flex-wrap justify-center gap-4 text-reveal delay-3">
                <a href="{{ route('booking.wizard') }}" class="px-8 py-3 bg-white text-teal-800 font-bold rounded-full hover:bg-teal-50 transition shadow-lg btn-spa">
                    Book as Guest
                </a>
                @guest
                    <a href="{{ route('login') }}" class="px-8 py-3 border-2 border-white text-white font-bold rounded-full btn-ghost">
                        Member Login
                    </a>
                @else
                    @php
                        $role = Auth::user()->roles->first()->name ?? 'customer';
                    @endphp
                    @if($role === 'admin')
                        <a href="{{ route('admin-dashboard') }}" class="px-8 py-3 border-2 border-white text-white font-bold rounded-full btn-ghost">
                            Admin Dashboard
                        </a>
                    @elseif($role === 'receptionist')
                        <a href="{{ route('receptionist.dashboard') }}" class="px-8 py-3 border-2 border-white text-white font-bold rounded-full btn-ghost">
                            Reception Desk
                        </a>
                    @elseif($role === 'staff')
                        <a href="{{ route('staff.dashboard') }}" class="px-8 py-3 border-2 border-white text-white font-bold rounded-full btn-ghost">
                            Staff Dashboard
                        </a>
                    @else
                        <a href="{{ route('customer-dashboard') }}" class="px-8 py-3 border-2 border-white text-white font-bold rounded-full btn-ghost">
                            My Dashboard
                        </a>
                    @endif
                @endguest
            </div>
        </div>
    </section>

    <!-- Why Create an Account -->
    <section class="py-16 max-w-6xl mx-auto px-6">
        <div class="text-center mb-12 enter enter-d1">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Why Create an Account?</h2>
            <p class="text-gray-600 dark:text-gray-400">Guests can book, but members get the full experience.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($benefits as $index => $b)
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow border border-gray-100 dark:border-gray-700 text-center card-spa enter enter-d{{ min($index + 2, 4) }}">
                <div class="w-12 h-12 bg-teal-100 dark:bg-teal-900/50 rounded-full flex items-center justify-center mx-auto mb-4 breathe">
                    <svg class="w-6 h-6 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $b['icon'] }}"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-1">{{ $b['title'] }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $b['desc'] }}</p>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-10 enter enter-d4">
            <a href="{{ route('customer.register') }}" class="inline-block px-6 py-3 bg-teal-600 text-white font-bold rounded-lg hover:bg-teal-700 transition btn-spa">
                Create Free Account
            </a>
            <p class="text-xs text-gray-500 dark:text-gray-500 mt-3">By signing up, you agree to our <a href="{{ route('terms') }}" class="underline hover:text-teal-600 link-spa">Terms of Service</a>.</p>
        </div>
    </section>

    <!-- Services -->
    <section class="py-16 bg-white dark:bg-gray-800 transition-colors duration-500">
        <div class="max-w-6xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-10 enter enter-d1">Our Services</h2>
            
            @php
                $landingCategories = $categories->filter(fn($c) => $c->show_on_landing && $c->services->where('show_on_landing', true)->count() > 0);
            @endphp

            @forelse($landingCategories as $category)
            <div class="mb-12 enter enter-d2">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-3 h-3 rounded-full" style="background-color: {{ $category->color ?? '#0d9488' }}"></div>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ $category->name }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($category->services->where('show_on_landing', true) as $service)
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 hover:shadow-lg transition group card-spa">
                        @if($service->image)
                            <div class="h-48 overflow-hidden">
                                <img src="{{ $service->image }}" alt="{{ $service->name }}" class="w-full h-full object-cover img-zoom">
                            </div>
                        @else
                            <div class="h-48 bg-gradient-to-br from-teal-100 to-teal-200 dark:from-teal-900 dark:to-teal-800 flex items-center justify-center">
                                <svg class="w-12 h-12 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        @endif
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-bold text-gray-900 dark:text-white">{{ $service->name }}</h4>
                                <span class="text-teal-600 font-bold text-sm">₱{{ number_format($service->price, 2) }}</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                {{ $service->landing_description ?? $service->description ?? 'Relax and rejuvenate with this premium treatment.' }}
                            </p>
                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $service->duration_minutes }} mins
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @empty
                <p class="text-center text-gray-500 enter enter-d2">No services available at the moment.</p>
            @endforelse

            <div class="text-center mt-8 enter enter-d3">
                <a href="{{ route('booking.wizard') }}" class="px-8 py-3 bg-teal-600 text-white font-bold rounded-lg hover:bg-teal-700 transition inline-block btn-spa">
                    Book an Appointment
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-10 text-sm footer-animate">
        <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <p>&copy; {{ date('Y') }} Spa Alexandria. All rights reserved.</p>
            <div class="flex gap-6">
                <a href="{{ route('terms') }}" class="hover:text-white transition link-spa">Terms of Service</a>
                <a href="#" class="hover:text-white transition link-spa">Privacy Policy</a>
            </div>
        </div>
    </footer>
</body>
</html>