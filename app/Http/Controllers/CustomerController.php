<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
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

        return view('customer.dashboard', compact('appointments', 'latest', 'customer'));
    }

    public function profile()
    {
        $user = auth()->user();
        $customer = Customer::where('user_id', $user->id)->first();

        if (!$customer) {
            $customer = Customer::make([
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name ?? '',
                'phone_number' => '',
            ]);
        }

        return view('customer.profile', compact('customer'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'nickname' => 'required|string|max:100',
            'phone_number' => 'required|string|max:20',
        ]);

        $user = auth()->user();

        $customer = Customer::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $request->nickname,
                'last_name' => '',
                'phone_number' => $request->phone_number,
            ]
        );

        // Also update the user record so the sidebar name stays in sync
        $user->update([
            'first_name' => $request->nickname,
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updateMedicalNotes(Request $request)
    {
        $request->validate([
            'medical_notes' => 'nullable|string|max:2000',
        ]);

        $user = auth()->user();

        $customer = Customer::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name ?? '',
            ]
        );

        $customer->update(['medical_notes' => $request->medical_notes]);

        return back()->with('success', 'Medical notes saved successfully.');
    }
}