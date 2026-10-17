<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects the Operations Console:
 *  - only active staff accounts get in;
 *  - staff must have confirmed two-factor authentication before using any other screen;
 *  - idle staff sessions are signed out.
 */
class StaffSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isStaff() || ! $user->is_active) {
            abort(403);
        }

        $idleLimit = (int) config('josveggies.staff.idle_minutes') * 60;
        $lastSeen = (int) $request->session()->get('staff_last_seen', time());

        if (time() - $lastSeen > $idleLimit) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('console.login')->with('status', 'You were signed out after a period of inactivity.');
        }

        $request->session()->put('staff_last_seen', time());

        if (! $user->hasConfirmedTwoFactor() && ! $request->routeIs('console.security*')) {
            return redirect()->route('console.security')
                ->with('status', 'Set up two-factor authentication to use the Operations Console.');
        }

        return $next($request);
    }
}
