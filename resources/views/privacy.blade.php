<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — Spa Alexandria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">
    <div class="max-w-3xl mx-auto px-6 py-16">
        <a href="{{ route('landing') }}" class="text-teal-600 font-semibold text-sm hover:underline mb-8 inline-block">&larr; Back to Home</a>
        
        <h1 class="text-3xl font-bold mb-2">Privacy Policy</h1>
        <p class="text-sm text-gray-500 mb-10">Last updated: {{ now()->format('F d, Y') }}</p>

        <div class="space-y-8">
            <section>
                <h2 class="text-xl font-bold mb-3">1. Information We Collect</h2>
                <p class="text-gray-600 leading-relaxed mb-3">We collect the following personal information solely for the purpose of providing spa services:</p>
                <ul class="list-disc list-inside text-gray-600 space-y-1 ml-2">
                    <li><strong>Identity:</strong> First name, last name, and username (for registered accounts).</li>
                    <li><strong>Contact:</strong> Phone number used for booking confirmation and reminders.</li>
                    <li><strong>Medical Notes:</strong> Allergies, skin conditions, pregnancy status, or other health preferences you voluntarily provide for your safety during treatments.</li>
                    <li><strong>Booking History:</strong> Appointments, services availed, and payment records.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold mb-3">2. How We Use Your Information</h2>
                <p class="text-gray-600 leading-relaxed">Your information is used strictly for:</p>
                <ul class="list-disc list-inside text-gray-600 space-y-1 ml-2 mt-2">
                    <li>Appointment scheduling, confirmation, and reminders;</li>
                    <li>Ensuring your safety by matching services to your disclosed medical conditions;</li>
                    <li>Processing payments and generating sales reports for business operations;</li>
                    <li>Maintaining your account and booking history for your convenience.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold mb-3">3. Who Can Access Your Data</h2>
                <p class="text-gray-600 leading-relaxed mb-3">Access is limited by role under our Role-Based Access Control (RBAC) system:</p>
                <div class="bg-white border border-gray-200 rounded-xl p-5 space-y-3">
                    <div class="flex gap-3">
                        <span class="font-bold text-teal-700 w-28 shrink-0">You</span>
                        <span class="text-gray-600">Can view and edit your own profile, medical notes, appointment history, and payment records.</span>
                    </div>
                    <div class="flex gap-3">
                        <span class="font-bold text-teal-700 w-28 shrink-0">Receptionist</span>
                        <span class="text-gray-600">Can access your contact details and medical notes as needed to confirm bookings and ensure service safety.</span>
                    </div>
                    <div class="flex gap-3">
                        <span class="font-bold text-teal-700 w-28 shrink-0">Therapist</span>
                        <span class="text-gray-600">Can view medical notes only for customers assigned to their own confirmed appointments.</span>
                    </div>
                    <div class="flex gap-3">
                        <span class="font-bold text-teal-700 w-28 shrink-0">Administrator</span>
                        <span class="text-gray-600">Has full access for system management, user support, and compliance oversight.</span>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="text-xl font-bold mb-3">4. Data Security</h2>
                <p class="text-gray-600 leading-relaxed">We implement the following safeguards:</p>
                <ul class="list-disc list-inside text-gray-600 space-y-1 ml-2 mt-2">
                    <li>Passwords are hashed using bcrypt before storage;</li>
                    <li>All data transmission is encrypted via HTTPS/TLS;</li>
                    <li>Session cookies are secured and HTTP-only in production;</li>
                    <li>The database is hosted on access-restricted infrastructure.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold mb-3">5. Your Rights Under RA 10173</h2>
                <p class="text-gray-600 leading-relaxed">As a data subject, you have the right to:</p>
                <ul class="list-disc list-inside text-gray-600 space-y-1 ml-2 mt-2">
                    <li>Be informed of what data is collected and why;</li>
                    <li>Access and correct your own personal information;</li>
                    <li>Object to processing and request deletion of your data (subject to legal and operational retention requirements);</li>
                    <li>File a complaint with the National Privacy Commission if you believe your rights have been violated.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold mb-3">6. Data Retention</h2>
                <p class="text-gray-600 leading-relaxed">We retain your personal data only for as long as necessary to fulfill the purposes for which it was collected, including legal, accounting, and reporting requirements. When data is no longer needed, it may be anonymized or securely disposed of in line with NPC guidance.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold mb-3">7. Contact</h2>
                <p class="text-gray-600 leading-relaxed">For questions, corrections, or deletion requests regarding your personal data, please contact the Spa Alexandria Administrator.</p>
            </section>
        </div>
    </div>
</body>
</html>