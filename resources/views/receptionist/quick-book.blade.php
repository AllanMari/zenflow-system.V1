@extends('layouts.receptionist')

@section('title', 'Quick Book')

@section('content')
<div x-data="quickBook()" x-init="init()" class="max-w-7xl mx-auto">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Quick Book</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Fast booking for walk-in and phone customers</p>
        </div>
        <a href="{{ route('receptionist.dashboard') }}" class="text-sm text-teal-600 dark:text-teal-400 hover:underline">&larr; Back to Dashboard</a>
    </div>

    <!-- Mode Toggle -->
    <div class="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-xl p-1 mb-6 max-w-md">
        <button @click="mode = 'now'" :class="mode === 'now' ? 'bg-white dark:bg-gray-600 shadow-sm text-teal-700 dark:text-teal-300' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'" 
                class="flex-1 py-2.5 px-4 rounded-lg text-sm font-bold transition">
            Right Now
        </button>
        <button @click="mode = 'future'" :class="mode === 'future' ? 'bg-white dark:bg-gray-600 shadow-sm text-teal-700 dark:text-teal-300' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'" 
                class="flex-1 py-2.5 px-4 rounded-lg text-sm font-bold transition">
            Future Date
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Customer -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/50 text-teal-600 dark:text-teal-400 flex items-center justify-center text-sm font-bold">1</span>
                    Customer
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="relative">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Phone Number</label>
                        <input type="tel" x-model="customer.phone" @input="searchCustomer" maxlength="11"
                            class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white font-mono text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none"
                            placeholder="09XXXXXXXXX">

                        <div x-show="customerSuggestions.length > 0" @click.away="customerSuggestions = []"
                             class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="c in customerSuggestions" :key="c.id">
                                <button type="button" @click="selectCustomer(c)"
                                        class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                    <p class="font-bold text-sm text-gray-900 dark:text-white" x-text="c.name"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="c.phone"></p>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Full Name</label>
                        <input type="text" x-model="customer.name"
                            class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none"
                            placeholder="Customer name">
                    </div>
                </div>

                <div class="mt-3">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Medical Notes</label>
                    <textarea x-model="customer.medical_notes" rows="2"
                        class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none resize-none"
                        placeholder="Allergies, conditions..."></textarea>
                </div>
            </div>

            <!-- Services -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/50 text-teal-600 dark:text-teal-400 flex items-center justify-center text-sm font-bold">2</span>
                    Services
                </h2>

                <div class="space-y-3">
                    @foreach($categories as $cat)
                    <div class="rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <button type="button" @click="toggleCat({{ $cat->id }})" 
                                class="w-full p-4 flex items-center justify-between text-left select-none group relative overflow-hidden">
                            <div class="absolute inset-0 opacity-10 transition-opacity group-hover:opacity-20" style="background-color: {{ $cat->color ?? '#0d9488' }}"></div>
                            <div class="flex items-center gap-4 relative z-10">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg" 
                                     style="background-color: {{ $cat->color ?? '#0d9488' }}">
                                    {{ strtoupper(substr($cat->name, 0, 1)) }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-lg">{{ $cat->name }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ $cat->services->count() }} services</p>
                                </div>
                            </div>
                            <div class="relative z-10 w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <svg :id="'chevron-{{ $cat->id }}'" class="chevron w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>

                        <div id="services-{{ $cat->id }}" class="category-services bg-gray-50/50 dark:bg-gray-900/20">
                            <div class="p-4 grid grid-cols-1 gap-3">
                                @foreach($cat->services as $s)
                                @php
                                    $servicePrice = $s->discount_price ?? $s->price ?? 0;
                                @endphp
                                <label :class="{ 'selected': isSelected({{ $s->id }}) }" 
                                       class="service-card relative flex items-center gap-4 p-4 rounded-xl border-2 border-transparent bg-white dark:bg-gray-800 cursor-pointer hover:border-teal-200 dark:hover:border-teal-800">
                                    
                                    <div class="check-indicator absolute -top-2 -right-2 w-7 h-7 bg-gradient-to-br from-teal-500 to-teal-600 rounded-full flex items-center justify-center shadow-lg z-10">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>

                                    <input type="checkbox" value="{{ $s->id }}" 
                                        data-srv-name="{{ $s->name }}"
                                        data-srv-duration="{{ $s->duration_minutes ?? 0 }}"
                                        data-srv-price="{{ $s->discount_price ?? $s->price ?? 0 }}"
                                        data-srv-requires-room="{{ $s->requires_room ? '1' : '0' }}"
                                        data-srv-room-cat="{{ $s->room_category_id ?? '' }}"
                                        @change="toggleServiceFromCheckbox($event.target)"
                                        :checked="isSelected({{ $s->id }})"
                                        class="custom-checkbox">

                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start gap-3">
                                            <p class="font-bold text-gray-900 dark:text-white text-[15px]">{{ $s->name }}</p>
                                            <span class="text-base font-bold text-teal-600 dark:text-teal-400 whitespace-nowrap">
                                                ₱{{ number_format($servicePrice, 2) }}
                                            </span>
                                        </div>
                                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-700 mt-1 inline-block">
                                            {{ $s->duration_minutes }} min
                                        </span>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div x-show="selectedServices.length > 0" class="mt-4 p-3 bg-teal-50 dark:bg-teal-900/20 rounded-xl border border-teal-200 dark:border-teal-800">
                    <p class="text-sm font-bold text-teal-800 dark:text-teal-300">
                        Total: <span x-text="totalDuration"></span> min | ₱<span x-text="totalPrice.toFixed(2)"></span>
                    </p>
                </div>
            </div>

            <!-- Schedule -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/50 text-teal-600 dark:text-teal-400 flex items-center justify-center text-sm font-bold">3</span>
                    Schedule
                </h2>

                <!-- RIGHT NOW: Smart Availability -->
                <div x-show="mode === 'now'" class="mb-4 space-y-4">
                    
                    <div x-show="selectedServices.length === 0" class="p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-600 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-bold">Select services above to see availability</p>
                    </div>

                    <div x-show="loadingNext && selectedServices.length > 0" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <template x-for="i in 3" :key="i">
                            <div class="h-24 shimmer rounded-xl"></div>
                        </template>
                    </div>

                    <div x-show="!loadingNext && selectedServices.length > 0 && nextSlots.length > 0">
                        <p class="text-xs font-bold text-teal-600 dark:text-teal-400 uppercase tracking-wide mb-2">Start Immediately — Next Available</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <template x-for="slot in nextSlots" :key="slot.time + '-' + slot.staff_id">
                                <button type="button" @click="selectQuickSlot(slot)"
                                    :class="selectedSlot && selectedSlot.time === slot.time && selectedStaff == slot.staff_id ? 'ring-2 ring-teal-500 border-teal-500 bg-teal-50 dark:bg-teal-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-teal-300 bg-white dark:bg-gray-700'"
                                    class="text-left p-4 rounded-xl border-2 transition">
                                    <p class="text-lg font-bold text-gray-900 dark:text-white" x-text="slot.display.split('–')[0].trim()"></p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500" x-text="'Ends ' + slot.display.split('–')[1].trim()"></p>
                                    <p class="text-sm font-medium text-teal-600 dark:text-teal-400" x-text="slot.staff_name"></p>
                                    <p x-show="slot.free_rooms.length > 0" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <span x-text="slot.free_rooms.length"></span> room<span x-show="slot.free_rooms.length > 1">s</span> free
                                    </p>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div x-show="!loadingNext && selectedServices.length > 0 && nextSlots.length === 0" 
                         class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800 text-center">
                        <p class="text-sm font-bold text-amber-800 dark:text-amber-300">No walk-in slots available right now</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1" x-text="nextDayHint ? 'Next opening: ' + nextDayHint : 'All staff are off-duty or fully booked'"></p>
                        <button x-show="nextDayRaw" @click="jumpToNextDay()" type="button" 
                            class="mt-3 px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-bold hover:bg-amber-700 transition">
                            Switch to Future Booking
                        </button>
                    </div>

                    <hr x-show="selectedServices.length > 0" class="border-gray-200 dark:border-gray-700">
                </div>

                <!-- FUTURE: Calendar -->
                <div x-show="mode === 'future'" class="mb-4">
                    <div id="calendar" class="rounded-xl overflow-hidden"></div>
                </div>

                <!-- Specific Staff -->
                <div x-show="selectedServices.length > 0" class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Assign Staff <span class="font-normal text-gray-400">(optional)</span></label>
                    <select x-model="selectedStaff" @change="loadGaps"
                        class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none">
                        <option value="">— Any staff —</option>
                        @foreach($staff as $s)
                            <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->last_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="loadingGaps" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <template x-for="i in 6" :key="i">
                        <div class="h-12 shimmer"></div>
                    </template>
                </div>

                <div x-show="!loadingGaps && gaps.length === 0 && selectedStaff" class="text-center py-8 bg-gray-50 dark:bg-gray-700/30 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-600">
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-bold">No available time slots</p>
                    <p class="text-xs text-gray-400 mt-1">Try a different staff or date</p>
                </div>

                <div x-show="gaps.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <template x-for="gap in gaps" :key="gap.time">
                        <button type="button" @click="selectSlot(gap)"
                                :class="{
                                    'selected': selectedSlot && selectedSlot.time === gap.time,
                                    'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-teal-300': !(selectedSlot && selectedSlot.time === gap.time)
                                }"
                                class="time-slot border-2 rounded-xl p-3 text-center text-sm font-bold transition">
                            <span x-text="gap.display"></span>
                            <span x-show="gap.free_rooms.length > 0" class="block text-[10px] font-normal mt-0.5 opacity-80">
                                <span x-text="gap.free_rooms.length"></span> room<span x-show="gap.free_rooms.length > 1">s</span>
                            </span>
                        </button>
                    </template>
                </div>

                <div x-show="selectedSlot && requiresRoom" class="mt-4">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Select Room</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <template x-for="room in selectedSlot.free_rooms" :key="room.id">
                            <button type="button" @click="selectedRoom = room.id"
                                    :class="selectedRoom === room.id ? 'bg-gradient-to-r from-teal-600 to-teal-700 text-white border-teal-600 shadow-md' : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-teal-300'"
                                    class="border-2 rounded-xl p-3 text-center text-sm font-bold transition">
                                <span x-text="room.name"></span>
                            </button>
                        </template>
                    </div>
                    <p x-show="selectedSlot && selectedSlot.free_rooms.length === 0" class="text-sm text-red-600 dark:text-red-400 mt-2">
                        No rooms available for this time slot.
                    </p>
                </div>
            </div>
        </div>

        <!-- RIGHT: Sticky Summary -->
        <div class="lg:col-span-5">
            <div class="sticky top-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
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
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-gray-800 dark:text-gray-200 truncate" x-text="s.name"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="s.duration + ' min'"></p>
                                    </div>
                                    <span class="text-sm font-bold text-teal-600 dark:text-teal-400">₱<span x-text="s.price.toFixed(2)"></span></span>
                                </div>
                            </template>
                        </div>

                        <div x-show="selectedServices.length > 0" class="space-y-2.5 text-sm mb-5">
                            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-gray-500 dark:text-gray-400">Date</span>
                                <span class="font-bold text-gray-800 dark:text-gray-200" x-text="displayDate"></span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-gray-500 dark:text-gray-400">Time</span>
                                <span class="font-bold text-gray-800 dark:text-gray-200" x-text="selectedSlot ? selectedSlot.display : 'Not set'"></span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-gray-500 dark:text-gray-400">Staff</span>
                                <span class="font-bold text-gray-800 dark:text-gray-200" x-text="selectedStaffName || 'Not set'"></span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-gray-500 dark:text-gray-400">Customer</span>
                                <span class="font-bold text-gray-800 dark:text-gray-200 truncate max-w-[150px]" x-text="customer.name || 'Not set'"></span>
                            </div>
                        </div>

                        <div x-show="selectedServices.length > 0" class="pt-4 border-t-2 border-gray-100 dark:border-gray-700 mb-5">
                            <div class="flex justify-between items-end">
                                <span class="text-gray-500 dark:text-gray-400 text-sm">Total</span>
                                <span class="text-3xl font-bold text-teal-600 dark:text-teal-400">₱<span x-text="totalPrice.toFixed(2)"></span></span>
                            </div>
                        </div>

                        <div x-show="selectedServices.length > 0" class="space-y-3 mb-5">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Payment Method</label>
                                <select x-model="payment.method"
                                    class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 dark:bg-gray-700 dark:text-white text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="gcash">GCash</option>
                                    <option value="paymaya">PayMaya</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">Amount Received (₱)</label>
                                <input type="number" x-model="payment.amount" step="0.01" min="0"
                                    class="w-full border border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700 dark:text-white font-bold text-lg focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none"
                                    :placeholder="totalPrice.toFixed(2)">
                            </div>
                        </div>

                        <button type="button" @click="submitBooking"
                                :disabled="!canSubmit || submitting"
                                :class="canSubmit && !submitting ? 'bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white shadow-lg shadow-teal-500/25' : 'bg-gray-300 dark:bg-gray-700 text-gray-500 cursor-not-allowed'"
                                class="w-full py-4 rounded-xl font-bold text-sm uppercase tracking-wide transition-all flex items-center justify-center gap-2">
                            <svg x-show="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="submitting ? 'Processing...' : (mode === 'now' ? 'Confirm Walk-in' : 'Confirm Booking')"></span>
                            <svg x-show="!submitting && canSubmit" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>

                        <p x-show="errorMessage" x-text="errorMessage" class="mt-3 text-sm text-red-600 dark:text-red-400 text-center"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div x-show="showSuccess" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" style="display: none;">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden text-center">
            <div class="bg-gradient-to-r from-teal-600 to-teal-700 p-6 text-white">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold">Booking Confirmed!</h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" x-text="successMessage"></p>
                <button @click="window.location.href = redirectUrl" type="button"
                        class="w-full py-3.5 bg-gradient-to-r from-teal-600 to-teal-700 text-white rounded-xl font-bold text-sm hover:from-teal-700 hover:to-teal-800 transition shadow-lg">
                    View Active Sessions
                </button>
                <p class="text-xs text-gray-400 mt-3">Redirecting automatically...</p>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
function quickBook() {
    return {
        mode: 'now',
        today: new Date().toISOString().split('T')[0],
        selectedDate: new Date().toISOString().split('T')[0],

        customer: { id: null, name: '', phone: '', medical_notes: '' },
        customerSuggestions: [],

        staffList: [],
        openCats: [],
        selectedServices: [],

        selectedStaff: '',
        selectedStaffName: '',
        selectedSlot: null,
        selectedRoom: null,

        gaps: [],
        loadingGaps: false,

        // NEW: next available slots for walk-ins
        nextSlots: [],
        loadingNext: false,
        nextDayRaw: '',
        nextDayHint: '',

        payment: { method: 'cash', amount: 0 },

        errorMessage: '',
        showSuccess: false,
        successMessage: '',
        redirectUrl: '',
        submitting: false,
        calendar: null,

        get totalDuration() {
            return this.selectedServices.reduce((a, s) => a + s.duration, 0);
        },

        get totalPrice() {
            return this.selectedServices.reduce((a, s) => a + s.price, 0);
        },

        get requiresRoom() {
            return this.selectedServices.some(s => s.requiresRoom);
        },

        get canSubmit() {
            return this.customer.name && this.customer.phone && 
                   this.selectedServices.length > 0 && this.selectedStaff && 
                   this.selectedSlot && (!this.requiresRoom || this.selectedRoom) &&
                   this.payment.amount >= 0;
        },

        get displayDate() {
            if (this.mode === 'now') return 'Today (' + new Date().toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }) + ')';
            return this.selectedDate ? new Date(this.selectedDate + 'T00:00:00').toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' }) : 'Not set';
        },

        init() {
            this.staffList = @json($staff->map(fn($s) => ['id' => $s->id, 'name' => trim($s->first_name . ' ' . $s->last_name)]));

            this.$watch('selectedServices', () => {
                if (this.selectedStaff) this.loadGaps();
                if (this.mode === 'now') this.loadNextSlots();
            });

            this.$watch('selectedStaff', () => {
                if (this.selectedServices.length > 0) this.loadGaps();
            });

            this.$watch('mode', (val) => {
                this.selectedSlot = null;
                this.selectedRoom = null;
                if (val === 'now') {
                    this.selectedDate = this.today;
                    this.loadNextSlots();
                    if (this.selectedStaff && this.selectedServices.length > 0) this.loadGaps();
                } else {
                    this.$nextTick(() => this.initCalendar());
                }
            });

            // Initial load
            if (this.mode === 'now') this.loadNextSlots();
            if (this.mode === 'now' && this.selectedStaff && this.selectedServices.length > 0) {
                this.loadGaps();
            }
        },

        isSelected(id) {
            return this.selectedServices.some(s => s.id === id);
        },

        toggleCat(catId) {
            const el = document.getElementById('services-' + catId);
            const chevron = document.getElementById('chevron-' + catId);
            if (el) el.classList.toggle('open');
            if (chevron) chevron.classList.toggle('rotated');
        },

        toggleServiceFromCheckbox(el) {
            const id = parseInt(el.value);
            const name = el.getAttribute('data-srv-name');
            const duration = parseInt(el.getAttribute('data-srv-duration')) || 0;
            const price = parseFloat(el.getAttribute('data-srv-price')) || 0;
            const requiresRoom = el.getAttribute('data-srv-requires-room') === '1';
            const roomCategoryId = el.getAttribute('data-srv-room-cat') || null;

            if (el.checked) {
                this.selectedServices.push({ id, name, duration, price, requiresRoom, roomCategoryId });
            } else {
                const i = this.selectedServices.findIndex(s => s.id === id);
                if (i > -1) this.selectedServices.splice(i, 1);
            }
        },

        async searchCustomer() {
            this.customerSuggestions = [];
            const phone = this.customer.phone.replace(/\D/g, '');
            if (phone.length < 6) return;

            try {
                const res = await fetch(`/api/customers/lookup?phone=${phone}`);
                if (res.ok) this.customerSuggestions = await res.json();
            } catch (e) {
                console.error('Customer search failed', e);
            }
        },

        selectCustomer(c) {
            this.customer.id = c.id;
            this.customer.name = c.name;
            this.customer.phone = c.phone;
            this.customer.medical_notes = c.medical_notes || '';
            this.customerSuggestions = [];
        },

        // NEW: load next available across all staff
        async loadNextSlots() {
            if (this.mode !== 'now') return;
            if (this.selectedServices.length === 0) {
                this.nextSlots = [];
                return;
            }

            this.loadingNext = true;
            try {
                const duration = this.totalDuration || 60;
                const serviceIds = this.selectedServices.map(s => s.id);
                const params = new URLSearchParams({
                    date: this.today,
                    duration: duration,
                    buffer_minutes: 5
                });
                serviceIds.forEach(id => params.append('services[]', id));

                const res = await fetch(`/api/booking/next-slots?${params}`);
                if (!res.ok) throw new Error();
                const data = await res.json();

                this.nextSlots = data.slots || [];
                this.nextDayRaw = data.next_day_hint || '';
                this.nextDayHint = data.next_day_hint
                    ? new Date(data.next_day_hint + 'T00:00:00').toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' }) +
                      (data.next_open_time ? ' at ' + data.next_open_time : '')
                    : '';

            } catch (e) {
                this.nextSlots = [];
                this.nextDayRaw = '';
                this.nextDayHint = '';
            } finally {
                this.loadingNext = false;
            }
        },

        // NEW: one-click select a next-available slot
        selectQuickSlot(slot) {
            this.selectedStaff = slot.staff_id;
            this.selectedStaffName = slot.staff_name;
            this.selectedSlot = {
                time: slot.time,
                end_time: slot.end_time,
                display: slot.display,
                free_rooms: slot.free_rooms
            };
            if (slot.free_rooms && slot.free_rooms.length === 1) {
                this.selectedRoom = slot.free_rooms[0].id;
            } else {
                this.selectedRoom = null;
            }
            // Also load the traditional gap grid so everything stays in sync
            this.loadGaps();
        },

        // NEW: jump to tomorrow when no slots today
        jumpToNextDay() {
            if (!this.nextDayRaw) return;
            this.mode = 'future';
            this.selectedDate = this.nextDayRaw;
            this.selectedSlot = null;
            this.selectedRoom = null;
            this.$nextTick(() => {
                this.initCalendar();
                if (this.calendar) {
                    this.calendar.gotoDate(this.nextDayRaw);
                }
                if (this.selectedStaff && this.selectedServices.length > 0) {
                    this.loadGaps();
                }
            });
        },

        initCalendar() {
            if (this.calendar) return;
            const el = document.getElementById('calendar');
            if (!el) return;

            const format = d => {
                const y = d.getFullYear();
                const m = String(d.getMonth()+1).padStart(2,'0');
                const day = String(d.getDate()).padStart(2,'0');
                return `${y}-${m}-${day}`;
            };

            const endDate = new Date();
            endDate.setDate(endDate.getDate() + 60);

            this.calendar = new FullCalendar.Calendar(el, {
                initialView: 'dayGridMonth',
                height: 'auto',
                headerToolbar: { left: 'title', center: '', right: 'prev,next' },
                validRange: { start: this.today, end: format(endDate) },
                dateClick: (info) => {
                    this.selectedDate = info.dateStr;
                    this.selectedSlot = null;
                    this.selectedRoom = null;
                    if (this.selectedServices.length > 0 && this.selectedStaff) this.loadGaps();
                    document.querySelectorAll('.fc-day-selected').forEach(e => e.classList.remove('fc-day-selected'));
                    const cell = document.querySelector(`[data-date="${info.dateStr}"]`);
                    if (cell) cell.classList.add('fc-day-selected');
                }
            });
            this.calendar.render();
        },

        async loadGaps() {
            if (!this.selectedStaff || this.selectedServices.length === 0) return;

            this.loadingGaps = true;
            this.gaps = [];
            this.selectedSlot = null;
            this.selectedRoom = null;

            try {
                const duration = this.totalDuration;
                const serviceIds = this.selectedServices.map(s => s.id);
                const params = new URLSearchParams({
                    date: this.selectedDate,
                    duration: duration,
                    staff_id: this.selectedStaff
                });
                serviceIds.forEach(id => params.append('services[]', id));

                // Walk-in buffer for today
                if (this.mode === 'now') {
                    params.append('buffer_minutes', '5');
                }

                const res = await fetch(`/api/booking/staff-gaps?${params}`);
                if (!res.ok) throw new Error('Failed to load availability');

                const data = await res.json();
                this.gaps = data.gaps || [];

                const staff = this.staffList.find(s => s.id == this.selectedStaff);
                this.selectedStaffName = staff ? staff.name : '';

            } catch (e) {
                console.error(e);
                this.errorMessage = 'Could not load availability. Please try again.';
            } finally {
                this.loadingGaps = false;
            }
        },

        selectSlot(gap) {
            this.selectedSlot = gap;
            this.selectedRoom = null;
            if (gap.free_rooms && gap.free_rooms.length === 1) {
                this.selectedRoom = gap.free_rooms[0].id;
            }
        },

        async submitBooking() {
            this.errorMessage = '';
            if (!this.canSubmit) {
                this.errorMessage = 'Please fill in all required fields.';
                return;
            }

            this.submitting = true;

            let endTime = this.selectedSlot.end_time;
            if (!endTime && this.selectedSlot.time) {
                const [h, m] = this.selectedSlot.time.split(':').map(Number);
                const d = new Date(this.selectedDate + 'T00:00:00');
                d.setHours(h, m + this.totalDuration);
                endTime = d.toTimeString().slice(0, 5);
            }

            const formData = {
                source: 'receptionist',
                services: this.selectedServices.map(s => s.id),
                appointment_date: this.selectedDate,
                start_time: this.selectedSlot.time,
                end_time: endTime,
                guest_first_name: this.customer.name,
                guest_phone: this.customer.phone,
                medical_notes: this.customer.medical_notes,
                staff_id: this.selectedStaff,
                room_id: this.selectedRoom,
                payment_method: this.payment.method,
                payment_amount: parseFloat(this.payment.amount) || 0,
                payment_type: 'full',
                walk_in_now: this.mode === 'now'
            };

            try {
                const res = await fetch('{{ route('booking.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(formData)
                });

                const contentType = res.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await res.text();
                    console.error('Non-JSON response:', text.substring(0, 500));
                    throw new Error('Server returned an invalid response. Check console.');
                }

                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Booking failed');

                this.successMessage = data.message || 'Appointment confirmed!';
                this.redirectUrl = data.redirect || '{{ route('receptionist.active') }}';
                this.showSuccess = true;

                setTimeout(() => {
                    window.location.href = this.redirectUrl;
                }, 1500);

            } catch (e) {
                this.errorMessage = e.message || 'Something went wrong. Please try again.';
                console.error('Booking error:', e);
            } finally {
                this.submitting = false;
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
@endsection