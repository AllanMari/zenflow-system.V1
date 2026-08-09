<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    private const MAX_REGISTRATIONS_PER_HOUR = 3;

    public function showRegistrationForm()
    {
        return view('auth.customer-register');
    }

    public function register(Request $request)
    {
        $ip = $request->ip();
        $rateKey = 'register:' . $ip;

        if (RateLimiter::tooManyAttempts($rateKey, self::MAX_REGISTRATIONS_PER_HOUR)) {
            $seconds = RateLimiter::availableIn($rateKey);
            return back()
                ->withErrors(['username' => 'Too many registration attempts from this location. Please try again in ' . ceil($seconds / 60) . ' minute(s).'])
                ->withInput();
        }

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
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                // Optional: ->uncompromised(3), // checks Have I Been Pwned; requires internet
            ],
            'terms_accepted' => ['required', 'accepted'],
            'privacy_consented' => ['required', 'accepted'],
        ], [
            'username.regex' => 'The username must not contain spaces.',
            'terms_accepted.required' => 'You must agree to the Terms of Service.',
            'privacy_consented.required' => 'You must consent to the Privacy Policy.',
            'password.mixed' => 'The password must contain both uppercase and lowercase letters.',
            'password.numbers' => 'The password must contain at least one number.',
            'password.symbols' => 'The password must contain at least one symbol (e.g. !@#$%).',
        ]);

        RateLimiter::hit($rateKey, 3600);

        $user = User::create([
            'username' => strtolower($request->username),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'password' => Hash::make($request->password),
            'terms_accepted_at' => now(),
            'privacy_consented_at' => now(),
            'session_version' => 0,
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
            session()->put('session_version', $user->session_version);

        return redirect()->route('customer-dashboard');
    }
}