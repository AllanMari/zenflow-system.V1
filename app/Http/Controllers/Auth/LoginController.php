<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(Request $request)
    {
        if ($request->input('secure') === '1') {
            return view('login');
        }
        if (Auth::check()) {
            return $this->redirectUserByRole(Auth::user());
        }

        return view('login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);
        $remember = $request->has('remember');

        // Case-sensitive lookup
        $user = \App\Models\User::whereRaw('BINARY username = ?', [$credentials['username']])->first();

        // Timing-attack protection: run Hash::check even if user not found
        if (!$user) {
            Hash::check('dummy', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
            return back()
                ->withErrors(['username' => 'Invalid credentials.'])
                ->withInput($request->only('username'));
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['username' => 'Invalid credentials.'])
                ->withInput($request->only('username'));
        }

        // CHECK BEFORE creating session — prevents the "Unauthorized" bug
        if (!$user->is_active) {
            return back()
                ->withErrors(['username' => 'This account has been deactivated. Contact administrator.'])
                ->withInput($request->only('username'));
        }

        if ($user->roles->isEmpty()) {
            return back()
                ->withErrors(['username' => 'No role assigned. Please contact administrator.'])
                ->withInput($request->only('username'));
        }

        // Safe to create session now
        Auth::login($user, $remember);
        $request->session()->regenerate();

        return $this->redirectUserByRole($user);
    }

    /**
     * Logout the user.
     */
    public function logout(Request $request)
    {
        if ($request->hasCookie('remember_web_'.Auth::getDefaultDriver())) {
            Cookie::queue(Cookie::forget('remember_web_'.Auth::getDefaultDriver()));
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        if ($request->input('secure') === '1' || $request->input('redirect') === 'login') {
            return redirect()->route('login', ['secure' => 1]);
        }
        return redirect('/login');
    }

    /**
     * Helper: Redirect based on role.
     */
    protected function redirectUserByRole($user)
    {
        if ($user->roles->isEmpty()) {
            Auth::logout();
            return redirect('/login')->withErrors([
                'username' => 'No role assigned. Please contact administrator.',
            ]);
        }

        $role = $user->roles->first()->name;

        return match($role) {
            'admin' => redirect('/admin-dashboard'),
            'receptionist' => redirect('/receptionist-dashboard'),
            'staff' => redirect('/staff/dashboard'),
            'customer' => redirect('/customer/dashboard'),
            default => redirect('/login')->withErrors([
                'username' => 'Unknown role: ' . $role . '. Contact admin.'
            ]),
        };
    }
}