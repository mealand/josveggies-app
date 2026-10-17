<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers for every response (CSP is added by spatie/laravel-csp).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        // The camera is only needed on the hub pickup-scan screen.
        $headers->set('Permissions-Policy', $request->routeIs('console.abuja.pickup')
            ? 'camera=(self), microphone=(), geolocation=(), payment=()'
            : 'camera=(), microphone=(), geolocation=(), payment=()');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->user()?->isStaff() || $request->is('account/*', 'checkout/*')) {
            $headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
