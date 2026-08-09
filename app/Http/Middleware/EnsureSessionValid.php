<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class EnsureSessionValid
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            // CRITICAL: Auth::user() is deserialized from the session — it has stale data.
            // We MUST fetch session_version fresh from the database.
            $currentVersion = User::where('id', Auth::id())->value('session_version');
            $sessionVersion = session('session_version');

            if ($sessionVersion === null) {
                session()->put('session_version', $currentVersion);
                return $next($request);
            }

            if ((int) $sessionVersion !== (int) $currentVersion) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Session expired. Please log in again.'], 401);
                }

                return redirect('/login')->withErrors([
                    'username' => 'Your session has expired due to a security update. Please log in again.',
                ]);
            }
        }

        return $next($request);
    }
}