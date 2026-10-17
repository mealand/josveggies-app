<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Spatie\Csp\Value;

/**
 * Strict Content Security Policy.
 *
 * Scripts: only same-origin files carrying the per-request nonce (no inline handlers, no eval;
 * Livewire runs Alpine in CSP-safe mode). Styles allow inline because GSAP and Livewire
 * animate through style attributes; style injection cannot execute code.
 */
class JosVeggiesPolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::CONNECT, Keyword::SELF)
            ->add(Directive::FONT, Keyword::SELF)
            ->add(Directive::FORM_ACTION, [Keyword::SELF, 'https://checkout.paystack.com'])
            ->add(Directive::FRAME_ANCESTORS, Keyword::NONE)
            ->add(Directive::FRAME, Keyword::NONE)
            ->add(Directive::IMG, [Keyword::SELF, 'data:', 'blob:'])
            ->add(Directive::MEDIA, [Keyword::SELF, 'blob:'])
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->addNonce(Directive::SCRIPT)
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE])
            ->add(Directive::WORKER, [Keyword::SELF, 'blob:'])
            ->add(Directive::MANIFEST, Keyword::SELF);

        if (app()->isProduction()) {
            $policy->add(Directive::UPGRADE_INSECURE_REQUESTS, Value::NO_VALUE);
        }
    }
}
