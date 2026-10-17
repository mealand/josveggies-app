<?php

namespace App\Services\Payments;

use App\Models\Payment;

/**
 * Abstraction over the payment provider so Flutterwave (or another gateway) can be added later.
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * Start a hosted checkout and return the URL the customer is redirected to.
     */
    public function initialize(Payment $payment, string $email, string $callbackUrl): string;

    /**
     * Ask the gateway (server-to-server) for the authoritative status of a transaction.
     */
    public function verify(string $reference): GatewayTransaction;

    /**
     * Refund a successful payment. Returns the gateway's refund reference.
     */
    public function refund(Payment $payment, int $amountKobo): string;

    /**
     * Check that a webhook request genuinely came from the gateway.
     */
    public function isValidWebhook(string $rawBody, ?string $signature): bool;
}
