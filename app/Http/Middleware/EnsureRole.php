<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:admin,jos_ops')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowed = array_map(fn (string $role): Role => Role::from($role), $roles);

        abort_unless($user !== null && $user->is_active && $user->hasRole(...$allowed), 403);

        return $next($request);
    }
}
