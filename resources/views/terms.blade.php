<!DOCTYPE html>
<html lang="en" class="{{ session('dark_mode') === 'enabled' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service — Spa Alexandria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>if(localStorage.getItem('darkMode')==='enabled')document.documentElement.classList.add('dark');tailwind.config={darkMode:'class'}</script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors">
    <div class="max-w-3xl mx-auto px-6 py-16">
        <a href="{{ route('landing') }}" class="text-teal-600 hover:underline text-sm mb-6 inline-block">&larr; Back to Home</a>
        <h1 class="text-3xl font-bold mb-8">Terms of Service</h1>
        
        <div class="space-y-6 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
            <section>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">1. Booking & Confirmation</h2>
                <p>All appointments require confirmation via phone call from our receptionist. A booking request submitted through this system is not final until confirmed. We reserve the right to cancel unconfirmed bookings.</p>
            </section>
            
            <section>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">2. No-Show Policy</h2>
                <p>If you do not answer our confirmation call and fail to arrive within 15 minutes of your scheduled time, your appointment may be marked as a no-show and forfeited. Repeated no-shows may result in restrictions on future bookings.</p>
            </section>
            
            <section>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">3. Cancellations</h2>
                <p>Please notify us at least 2 hours in advance for cancellations or rescheduling. Late cancellations may incur a fee at the discretion of management.</p>
            </section>
            
            <section>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">4. Health & Safety</h2>
                <p>You agree to disclose any medical conditions, allergies, pregnancy, or skin sensitivities that may affect your treatment. We are not liable for adverse reactions resulting from undisclosed health information.</p>
            </section>
            
            <section>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">5. Liability Waiver</h2>
                <p>Spa Alexandria and its staff are not responsible for loss or damage to personal belongings. Services are provided at your own risk.</p>
            </section>
            
            <section>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">6. Data Privacy</h2>
                <p>Your personal information, including phone number and medical notes, is stored securely and used solely for appointment management and service personalization. We do not sell or share your data with third parties.</p>
            </section>
            
            <section>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">7. Age Requirement</h2>
                <p>Customers under 18 years of age must be accompanied by a parent or guardian and provide written consent for certain treatments.</p>
            </section>
            
            <section>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">8. Changes to Terms</h2>
                <p>We may update these terms at any time. Continued use of our booking system constitutes acceptance of the revised terms.</p>
            </section>
        </div>
        
        <p class="mt-10 text-xs text-gray-500">Last updated: {{ date('F d, Y') }}</p>
    </div>
</body>
</html>