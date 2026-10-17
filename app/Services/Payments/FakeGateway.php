<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

/**
 * Local development gateway: a simulated hosted checkout page, so the whole order flow
 * works without Paystack keys. Never registered in production (see AppServiceProvider).
 */
class FakeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function initialize(Payment $payment, string $email, string $callbackUrl): string
    {
        Cache::put($this->key($payment->reference), ['status' => 'pending', 'callback' => $callbackUrl], now()->addHour());

        return URL::temporarySignedRoute('dev.fake-pay.show', now()->addMinutes(30), ['reference' => $payment->reference]);
    }

    public function verify(string $reference): GatewayTransaction
    {
        $state = Cache::get($this->key($reference), []);
        $payment = Payment::query()->where('reference', $reference)->first();

        return new GatewayTransaction(
            reference: $reference,
            successful: ($state['status'] ?? null) === 'success',
            amountKobo: (int) ($payment->amount_kobo ?? 0),
            currency: 'NGN',
            channel: 'card',
            raw: ['simulated' => true, 'status' => $state['status'] ?? 'unknown'],
        );
    }

    public function refund(Payment $payment, int $amountKobo): string
    {
        return 'fake-refund-'.$payment->reference;
    }

    public function isValidWebhook(string $rawBody, ?string $signature): bool
    {
        return $signature !== null && hash_equals(hash_hmac('sha512', $rawBody, (string) config('app.key')), $signature);
    }

    /**
     * Called by the simulated checkout page.
     */
    public function simulate(string $reference, bool $success): ?string
    {
        $state = Cache::get($this->key($reference));

        if ($state === null) {
            return null;
        }

        $state['status'] = $success ? 'success' : 'failed';
        Cache::put($this->key($reference), $state, now()->addHour());

        return $state['callback'];
    }

    private function key(string $reference): string
    {
        return 'fakepay:'.$reference;
    }
}
