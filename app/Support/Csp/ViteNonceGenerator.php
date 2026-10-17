<?php

namespace App\Support\Csp;

use Illuminate\Support\Facades\Vite;
use Spatie\Csp\Nonce\NonceGenerator;

/**
 * Share one nonce per request between the CSP header, Vite script tags and Livewire.
 */
class ViteNonceGenerator implements NonceGenerator
{
    public function generate(): string
    {
        return Vite::cspNonce() ?? Vite::useCspNonce();
    }
}
