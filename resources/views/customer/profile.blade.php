@extends('layouts.customer')

@section('title', 'My Profile')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-3xl font-bold text-teal-600 dark:text-teal-400 mb-6">My Profile</h1>

    <!-- Profile Info Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6 transition-colors duration-300">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Personal Information</h2>

        <form action="{{ route('customer.profile.update') }}" method="POST" x-data="profileForm()" x-init="init()">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Nickname</label>
                    <input type="text" name="nickname" value="{{ $customer->first_name ?? '' }}"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none"
                        placeholder="e.g. Maria" required>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">This is the name you use when booking.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Phone Number</label>
                    <input type="tel" name="phone_number" x-model="phone" @input="formatPhone" maxlength="11"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white font-mono text-sm tracking-wide focus:ring-2 focus:ring-teal-500 outline-none"
                        placeholder="09XXXXXXXXX" required>
                    <p class="text-xs mt-1 font-bold" :class="phoneValid ? 'text-green-600' : 'text-red-500'" x-text="phoneMessage"></p>
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <button type="submit" :disabled="!phoneValid"
                    class="px-6 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    Save Profile
                </button>
            </div>
        </form>
    </div>

    <!-- Medical Notes Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 transition-colors duration-300">
        <div class="flex items-center gap-2 mb-4">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Medical Notes</h2>
            <div class="relative group cursor-help">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="absolute hidden group-hover:block bg-gray-800 text-white text-xs p-3 rounded w-64 top-6 z-50">
                    Share allergies, skin conditions, pregnancy, injuries, or preferences so our therapists can prepare accordingly.
                </div>
            </div>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            This information will be saved to your profile and automatically included in future bookings. No need to repeat yourself!
        </p>

        <form action="{{ route('customer.medical-notes.update') }}" method="POST">
            @csrf
            @method('PUT')
            <textarea name="medical_notes" rows="6"
                class="w-full border rounded-lg p-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-teal-500 outline-none transition"
                placeholder="E.g., Allergic to lavender oil, prefer light pressure, currently pregnant, back injury on lower left...">{{ $customer->medical_notes ?? '' }}</textarea>

            <div class="flex justify-end mt-4">
                <button type="submit" class="px-6 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition font-medium">
                    Save Medical Notes
                </button>
            </div>
        </form>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="bg-teal-50 dark:bg-gray-800 rounded-xl p-4 text-center transition-colors duration-300">
            <p class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ $customer->appointments()->count() }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Visits</p>
        </div>
        <div class="bg-teal-50 dark:bg-gray-800 rounded-xl p-4 text-center transition-colors duration-300">
            <p class="text-2xl font-bold text-teal-600 dark:text-teal-400">₱{{ number_format($customer->appointments()->sum('total_price'), 0) }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">Lifetime Spent</p>
        </div>
        <div class="bg-teal-50 dark:bg-gray-800 rounded-xl p-4 text-center transition-colors duration-300">
            <p class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ $customer->appointments()->where('status', 'completed')->count() }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">Completed</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function profileForm() {
    return {
        phone: '{{ $customer->phone_number ?? '' }}',
        phoneValid: false,
        phoneMessage: '',

        formatPhone() {
            this.phone = this.phone.replace(/\D/g, '');
            const regex = /^09\d{9}$/;
            this.phoneValid = regex.test(this.phone);
            if (this.phone.length === 0) {
                this.phoneMessage = '';
            } else if (this.phone.length < 11) {
                this.phoneMessage = 'Need ' + (11 - this.phone.length) + ' more digits';
            } else if (this.phoneValid) {
                this.phoneMessage = '✓ Valid number';
            } else {
                this.phoneMessage = 'Must start with 09, 11 digits';
            }
        },

        init() {
            if (this.phone) this.formatPhone();
        }
    }
}
</script>
@endpush