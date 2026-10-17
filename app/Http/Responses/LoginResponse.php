<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

/**
 * Staff land in the Operations Console; customers return to where they were.
 */
class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        if ($user->isStaff()) {
            $request->session()->put('staff_last_seen', time());

            return redirect()->route('console.dashboard');
        }

        return redirect()->intended(route('home'));
    }
}
