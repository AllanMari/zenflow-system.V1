<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_MINUTES = 1;

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
        $username = strtolower($credentials['username']);
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // Rate limit: 5 attempts per IP+username per minute
        $rateKey = 'login:' . $ip . ':' . $username;
        if (RateLimiter::tooManyAttempts($rateKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($rateKey);
            $this->logAttempt(null, $username, $ip, $userAgent, false, 'rate_limited');
            return back()
                ->withErrors(['username' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minute(s).'])
                ->withInput($request->only('username'));
        }

        // Case-insensitive lookup
        $user = \App\Models\User::whereRaw('LOWER(username) = LOWER(?)', [$credentials['username']])->first();

        // Determine failure reason (unified public message, detailed internal log)
        $success = false;
        $reason = 'invalid_password';
        $userId = null;

        if (!$user) {
            $reason = 'user_not_found';
            Hash::check('dummy', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
        } elseif (!$user->is_active) {
            $reason = 'inactive';
            $userId = $user->id;
            Hash::check('dummy', $user->password);
        } elseif ($user->roles->isEmpty()) {
            $reason = 'no_role';
            $userId = $user->id;
            Hash::check('dummy', $user->password);
        } elseif (!Hash::check($credentials['password'], $user->password)) {
            $reason = 'invalid_password';
            $userId = $user->id;
        } else {
            $success = true;
            $reason = 'success';
            $userId = $user->id;
        }

        $this->logAttempt($userId, $username, $ip, $userAgent, $success, $reason);

        $loginCount = cache()->increment('global_login_counter');
        if ($loginCount >= 100) {
            cache()->put('global_login_counter', 0);
            \App\Models\LoginAttempt::where('created_at', '<', now()->subDays(90))->delete();
        }

        if (!$success) {
            RateLimiter::hit($rateKey, self::DECAY_MINUTES * 60);
            return back()
                ->withErrors(['username' => 'Invalid credentials.'])
                ->withInput($request->only('username'));
        }

        // Success: clear rate limiter, bump session version, store in session
        RateLimiter::clear($rateKey);
        $user->increment('session_version');
        $user->update(['password_changed_at' => now()]);

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('session_version', $user->session_version);

        return $this->redirectUserByRole($user);
    }

    /**
     * Logout the user.
     */
    public function logout(Request $request)
    {
        // Properly purge remember-me cookie
        if (Auth::guard()->getRecallerName()) {
            Cookie::queue(Cookie::forget(Auth::guard()->getRecallerName()));
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

    /**
     * Write login attempt to audit trail.
     */
    private function logAttempt(?int $userId, string $username, string $ip, ?string $userAgent, bool $success, string $reason): void
    {
        LoginAttempt::create([
            'user_id' => $userId,
            'username' => $username,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'success' => $success,
            'reason' => $reason,
        ]);
    }
}