<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.customer-register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'username' => [
                'required', 'string', 'max:255', 'regex:/^\S+$/u',
                function ($attribute, $value, $fail) {
                    if (User::whereRaw('LOWER(username) = LOWER(?)', [$value])->exists()) {
                        $fail('The username has already been taken.');
                    }
                },
            ],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
            'terms_accepted' => ['required', 'accepted'],
            'privacy_consented' => ['required', 'accepted'], // ← ADDED
        ], [
            'username.regex' => 'The username must not contain spaces.',
            'terms_accepted.required' => 'You must agree to the Terms of Service.',
            'privacy_consented.required' => 'You must consent to the Privacy Policy.',
        ]);

        $user = User::create([
            'username' => $request->username,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'password' => Hash::make($request->password),
        ]);

        $customerRole = \App\Models\Role::where('name', 'customer')->first();
        
        if (!$customerRole) {
            $user->delete();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Customer role not found. Please contact administrator.');
        }

        $user->roles()->attach($customerRole->id);

        \App\Models\Customer::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone_number' => 'N/A',
            'customer_type' => 'regular',
        ]);

        Auth::login($user);

        return redirect()->route('customer-dashboard');
    }
}