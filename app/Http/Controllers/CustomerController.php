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
}