<?php

namespace App\Http\Controllers;

use App\Services\Payments\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Server-to-server payment confirmation (BR-08).
 *
 * 1. Verify the HMAC-SHA512 signature over the raw body.
 * 2. Re-verify the transaction with the Paystack API (never trust the payload alone).
 * 3. Apply idempotently (unique reference + row locks).
 */
class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, PaymentService $payments): JsonResponse
    {
        $raw = $request->getContent();

        if (! $gateway->isValidWebhook($raw, $request->header('x-paystack-signature'))) {
            Log::warning('Rejected webhook with invalid signature', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = (string) $request->json('event');
        $reference = (string) $request->json('data.reference');

        if ($reference === '' || strlen($reference) > 64) {
            return response()->json(['message' => 'Ignored']);
        }

        try {
            match ($event) {
                'charge.success' => $payments->verifyAndApply($reference),
                'charge.failed' => $payments->markFailed($reference),
                default => null,
            };
        } catch (Throwable $e) {
            report($e);

            // A 5xx makes Paystack retry later, which is what we want for transient errors.
            return response()->json(['message' => 'Retry later'], 503);
        }

        return response()->json(['message' => 'OK']);
    }
}
