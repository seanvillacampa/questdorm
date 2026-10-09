<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * LoginRequest::authenticate() blocks a disabled account AT login time, but
 * a session created before the account was disabled would otherwise stay
 * valid until it expires. This middleware closes that gap by checking
 * is_active on every authenticated request, not just at login.
 *
 * Register it as global (or on the 'auth' middleware group) in
 * bootstrap/app.php — see SETUP.md.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'This account has been disabled. Please contact the dormitory office.',
            ]);
        }

        return $next($request);
    }
}
