<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Customer dashboard.
     */
    public function index()
    {
        $user = auth()->user();

        $customer = Customer::where('user_id', $user->id)->first();

        $appointments = collect();
        $latest = null;

        if ($customer) {
            $appointments = Appointment::with('services')
                ->where('customer_id', $customer->id)
                ->orderBy('appointment_date', 'desc')
                ->orderBy('start_time', 'desc')
                ->get();

            $latest = $appointments->first();
        }

        return view(
            'customer.dashboard',
            compact('appointments', 'latest', 'customer')
        );
    }

    /**
     * Customer "My Booking / Booking Status" page.
     */
    public function bookings()
    {
        $user = auth()->user();
        $customer = Customer::where('user_id', $user->id)->first();

        $upcomingBookings = collect();
        $pastBookings = collect();

        if ($customer) {
            $allAppointments = Appointment::with(['services', 'staff', 'room', 'payments'])
                ->where('customer_id', $customer->id)
                ->orderBy('appointment_date', 'desc')
                ->orderBy('start_time', 'desc')
                ->get();

            $upcomingBookings = $allAppointments->filter(function ($appt) {
                $apptDateTime = \Carbon\Carbon::parse($appt->appointment_date->format('Y-m-d') . ' ' . $appt->start_time);
                return in_array($appt->status, ['pending', 'confirmed']) && $apptDateTime->isFuture();
            })->values();

            $pastBookings = $allAppointments->reject(function ($appt) use ($upcomingBookings) {
                return $upcomingBookings->contains('id', $appt->id);
            })->values();
        }

        return view('customer.bookings', compact('upcomingBookings', 'pastBookings', 'customer'));
    }

    /**
     * Customer profile page.
     */
    public function profile()
    {
        $user = auth()->user();

        $customer = Customer::where('user_id', $user->id)->first();

        /*
         * Do not save anything just by opening the profile.
         * Create an in-memory customer object if one does not exist yet.
         */
        if (!$customer) {
            $customer = Customer::make([
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name ?? '',
                'nickname' => null,
                'phone_number' => '',
            ]);
        }

        return view(
            'customer.profile',
            compact('customer')
        );
    }

    /**
     * Update customer profile.
     *
     * Customer can change:
     * - nickname
     * - phone number
     *
     * Actual first and last names remain unchanged.
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'nickname' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
            'phone_number' => [
                'required',
                'string',
                'regex:/^09\d{9}$/',
            ],
        ], [
            'nickname.required' => 'Please enter a nickname or booking name.',
            'nickname.min' => 'Your booking name must be at least 2 characters.',
            'nickname.max' => 'Your booking name cannot exceed 100 characters.',
            'phone_number.required' => 'Please enter your phone number.',
            'phone_number.regex' => 'Please enter a valid Philippine mobile number starting with 09.',
        ]);

        $user = auth()->user();

        $customer = Customer::firstOrNew([
            'user_id' => $user->id,
        ]);

        /*
         * Only set the actual name when creating a brand-new
         * customer record.
         *
         * Existing first_name / last_name are NEVER overwritten
         * when the customer changes their nickname.
         */
        if (!$customer->exists) {
            $customer->first_name = $user->first_name ?? '';
            $customer->last_name = $user->last_name ?? '';
            $customer->email = $user->email ?? null;
        }

        $customer->nickname = trim($validated['nickname']);
        $customer->phone_number = $validated['phone_number'];
        $customer->save();

        /*
         * IMPORTANT:
         *
         * We intentionally do NOT update:
         *
         * $user->first_name
         *
         * because nickname and legal/actual name are different things.
         */

        return back()->with(
            'success',
            'Your profile has been updated successfully.'
        );
    }

    /**
     * Update customer medical notes.
     */
    public function updateMedicalNotes(Request $request)
    {
        $validated = $request->validate([
            'medical_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $user = auth()->user();

        $customer = Customer::firstOrNew([
            'user_id' => $user->id,
        ]);

        /*
         * If the customer record doesn't exist yet,
         * preserve the actual account name.
         */
        if (!$customer->exists) {
            $customer->first_name = $user->first_name ?? '';
            $customer->last_name = $user->last_name ?? '';
            $customer->email = $user->email ?? null;
            $customer->phone_number = '';
        }

        $customer->medical_notes = $validated['medical_notes'] ?? null;
        $customer->save();

        return back()->with(
            'success',
            'Your medical notes have been saved.'
        );
    }

    /**
     * Cancel an appointment by the customer.
     */
    public function cancelBooking(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'cancellation_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = auth()->user();
        $customer = Customer::where('user_id', $user->id)->first();

        if (!$customer || $appointment->customer_id !== $customer->id) {
            abort(403, 'Unauthorized action.');
        }

        if (!in_array($appointment->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'This appointment can no longer be cancelled.');
        }

        $appointmentDateTime = \Carbon\Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->start_time);
        if ($appointmentDateTime->isPast()) {
            return back()->with('error', 'Cannot cancel an appointment whose scheduled time has already passed.');
        }

        $appointment->status = 'cancelled';
        $appointment->cancellation_reason = 'customer_cancelled';

        if (!empty($validated['cancellation_note'])) {
            $noteText = trim($validated['cancellation_note']);
            $prefix = "\n[Customer Cancellation Note (" . now()->format('M j, Y g:i A') . ")]: ";
            $appointment->notes = trim(($appointment->notes ?? '') . $prefix . $noteText);
        }

        $appointment->save();

        if ($appointment->room_id) {
            $stillNeeded = Appointment::where('room_id', $appointment->room_id)
                ->where('id', '!=', $appointment->id)
                ->whereDate('appointment_date', $appointment->appointment_date)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->exists();

            if (!$stillNeeded) {
                \App\Models\Room::where('id', $appointment->room_id)->update(['status' => 'available']);
            }
        }

        $reasonText = !empty($validated['cancellation_note']) ? ' Note: ' . $validated['cancellation_note'] : '';
        $formattedDate = \Carbon\Carbon::parse($appointment->appointment_date)->format('M j, Y') . ' at ' . \Carbon\Carbon::parse($appointment->start_time)->format('g:i A');

        $receptionists = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'receptionist'))->get();
        if ($receptionists->isNotEmpty()) {
            NotificationController::sendTo(
                $receptionists,
                'Customer Cancelled Booking',
                ($customer->full_name ?? $user->first_name) . ' cancelled their appointment on ' . $formattedDate . '.' . $reasonText,
                'booking', 'warning', route('receptionist.dashboard'), 'Review'
            );
        }

        if ($appointment->staff) {
            NotificationController::sendTo(
                $appointment->staff,
                'Appointment Cancelled by Customer',
                ($customer->full_name ?? $user->first_name) . ' cancelled their appointment on ' . $formattedDate . '.' . $reasonText,
                'booking', 'warning', route('staff.dashboard'), 'My Schedule'
            );
        }

        $admins = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->get();
        if ($admins->isNotEmpty()) {
            NotificationController::sendTo(
                $admins,
                'Customer Cancelled Booking',
                ($customer->full_name ?? $user->first_name) . ' cancelled their appointment on ' . $formattedDate . '.' . $reasonText,
                'booking', 'warning', route('admin.appointments', ['status' => 'cancelled']), 'Review'
            );
        }

        if ($user) {
            NotificationController::sendTo(
                $user,
                'Appointment Cancelled',
                'Your appointment on ' . \Carbon\Carbon::parse($appointment->appointment_date)->format('M j, Y') . ' has been cancelled.',
                'booking',
                'warning',
                route('customer.bookings'),
                'View Bookings'
            );
        }

        return back()->with('success', 'Your appointment has been cancelled successfully.');
    }
}