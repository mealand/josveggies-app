<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackGateway implements PaymentGateway
{
    public function __construct(
        private string $secretKey,
        private string $baseUrl,
    ) {}

    public function name(): string
    {
        return 'paystack';
    }

    public function initialize(Payment $payment, string $email, string $callbackUrl): string
    {
        $response = $this->client()->post('/transaction/initialize', [
            'email' => $email,
            'amount' => $payment->amount_kobo,
            'currency' => $payment->currency,
            'reference' => $payment->reference,
            'callback_url' => $callbackUrl,
            'channels' => ['card', 'bank', 'ussd', 'bank_transfer'],
            'metadata' => ['order_number' => $payment->order->number],
        ]);

        $url = $response->json('data.authorization_url');

        if (! $response->successful() || ! is_string($url)) {
            throw new RuntimeException('Paystack initialize failed: '.$response->json('message', 'unknown error'));
        }

        return $url;
    }

    public function verify(string $reference): GatewayTransaction
    {
        $response = $this->client()->get('/transaction/verify/'.rawurlencode($reference));

        if (! $response->successful()) {
            throw new RuntimeException('Paystack verify failed: '.$response->json('message', 'unknown error'));
        }

        /** @var array<string, mixed> $data */
        $data = $response->json('data', []);

        return new GatewayTransaction(
            reference: (string) ($data['reference'] ?? $reference),
            successful: ($data['status'] ?? null) === 'success',
            amountKobo: (int) ($data['amount'] ?? 0),
            currency: (string) ($data['currency'] ?? ''),
            channel: isset($data['channel']) ? (string) $data['channel'] : null,
            raw: array_intersect_key($data, array_flip(['id', 'status', 'reference', 'amount', 'currency', 'channel', 'paid_at', 'gateway_response'])),
        );
    }

    public function refund(Payment $payment, int $amountKobo): string
    {
        $response = $this->client()->post('/refund', [
            'transaction' => $payment->reference,
            'amount' => $amountKobo,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Paystack refund failed: '.$response->json('message', 'unknown error'));
        }

        return (string) $response->json('data.id', '');
    }

    /**
     * Paystack signs the raw body with HMAC-SHA512 using the secret key.
     */
    public function isValidWebhook(string $rawBody, ?string $signature): bool
    {
        if ($signature === null || $signature === '' || $this->secretKey === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $rawBody, $this->secretKey), $signature);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(2, 300, throw: false);
    }
}
