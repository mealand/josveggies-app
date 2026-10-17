<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Staff two-factor authentication setup. The actual enable/confirm/disable endpoints are Fortify's.
 */
class SecurityController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('console.security', [
            'user' => $user,
            'pending' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
            'confirmed' => $user->hasConfirmedTwoFactor(),
        ]);
    }
}
