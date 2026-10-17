<?php

use App\Enums\OrderState;
use App\Enums\PaymentStatus;
use App\Enums\RunState;
use App\Models\User;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaystackGateway;
use App\Services\PaymentService;
use App\Services\RunClock;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

const SECRET = 'sk_test_secret';

beforeEach(function () {
    Notification::fake();
    app()->instance(PaymentGateway::class, new PaystackGateway(SECRET, 'https://api.paystack.co'));
});

/**
 * Create a pending order with a Paystack payment attempt and return its reference.
 */
function pendingPaystackOrder(int $capacity = 5): array
{
    Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc']])]);
    ['run' => $run, 'box' => $box] = openRun(capacity: $capacity);
    $order = placeOrder(User::factory()->create(), $run, $box);
    app(PaymentService::class)->start($order);

    return [$order, $order->payments()->firstOrFail(), $run, $box];
}

function fakeVerify(string $reference, int $amount, string $status = 'success'): void
{
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => [
        'reference' => $reference, 'status' => $status, 'amount' => $amount, 'currency' => 'NGN', 'channel' => 'card',
    ]])]);
}

function webhook(string $reference, string $secret = SECRET)
{
    $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $reference]]);

    return test()->call('POST', '/webhooks/paystack', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, $secret),
    ], $body);
}

test('webhooks with an invalid signature are rejected', function () {
    [$order, $payment] = pendingPaystackOrder();

    webhook($payment->reference, 'wrong-secret')->assertUnauthorized();

    expect($order->fresh()->state)->toBe(OrderState::PendingPayment);
});

test('BR-08: a signed webhook confirms the order after re-verifying with Paystack', function () {
    [$order, $payment] = pendingPaystackOrder();
    fakeVerify($payment->reference, $payment->amount_kobo);

    webhook($payment->reference)->assertOk();

    expect($order->fresh()->state)->toBe(OrderState::Confirmed)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Succeeded);
    Http::assertSent(fn ($request) => str_contains($request->url(), '/transaction/verify/'.$payment->reference));
});

test('replayed webhooks are idempotent', function () {
    [$order, $payment, $run] = pendingPaystackOrder();
    fakeVerify($payment->reference, $payment->amount_kobo);

    webhook($payment->reference)->assertOk();
    webhook($payment->reference)->assertOk();

    expect($run->fresh()->slots_confirmed)->toBe(1);
});

test('a payment for the wrong amount does not confirm the order', function () {
    [$order, $payment] = pendingPaystackOrder();
    fakeVerify($payment->reference, 100);

    webhook($payment->reference)->assertOk();

    expect($order->fresh()->state)->toBe(OrderState::PendingPayment)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Failed);
});

test('a late payment is honoured when the slot is still free', function () {
    [$order, $payment, $run] = pendingPaystackOrder();
    $this->travel(11)->minutes();
    app(RunClock::class)->tick();
    expect($order->fresh()->state)->toBe(OrderState::Expired);

    fakeVerify($payment->reference, $payment->amount_kobo);
    webhook($payment->reference)->assertOk();

    expect($order->fresh()->state)->toBe(OrderState::Confirmed)
        ->and($run->fresh()->slots_confirmed)->toBe(1);
});

test('a late payment is refunded automatically when the Run is full', function () {
    [$order, $payment, $run, $box] = pendingPaystackOrder(capacity: 1);
    $this->travel(11)->minutes();
    app(RunClock::class)->tick();

    // Someone else takes the released slot.
    paidOrder(User::factory()->create(), $run->fresh(), $box);
    expect($run->fresh()->state)->toBe(RunState::Closed);

    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['reference' => $payment->reference, 'status' => 'success', 'amount' => $payment->amount_kobo, 'currency' => 'NGN']]),
        'api.paystack.co/refund' => Http::response(['status' => true, 'data' => ['id' => 987]]),
    ]);
    webhook($payment->reference)->assertOk();

    expect($order->fresh()->state)->toBe(OrderState::Cancelled)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and($run->fresh()->slots_confirmed)->toBe(1);
});
