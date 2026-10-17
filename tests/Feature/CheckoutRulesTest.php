<?php

use App\Enums\CreditReason;
use App\Enums\Fulfilment;
use App\Enums\OrderState;
use App\Enums\RunState;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\CreditService;
use App\Services\PaymentService;
use App\Services\RunClock;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

test('BR-01: one box uses one slot and add-ons use none', function () {
    ['run' => $run, 'box' => $box, 'addOn' => $addOn] = openRun();

    placeOrder(User::factory()->create(), $run, $box, boxes: 2, addOns: [$addOn->id => 3]);

    expect($run->fresh()->slots_held)->toBe(2);
});

test('BR-02 and BR-07: slots are never oversold', function () {
    ['run' => $run, 'box' => $box] = openRun(capacity: 3);

    placeOrder(User::factory()->create(), $run, $box, boxes: 2);

    expect(fn () => placeOrder(User::factory()->create(), $run, $box, boxes: 2))
        ->toThrow(BusinessRuleException::class, 'Only 1 slot(s) left');

    placeOrder(User::factory()->create(), $run, $box, boxes: 1);

    expect($run->fresh()->slotsRemaining())->toBe(0)
        ->and(fn () => placeOrder(User::factory()->create(), $run, $box))->toThrow(BusinessRuleException::class);
});

test('BR-04: the window closes the moment every slot is confirmed', function () {
    ['run' => $run, 'box' => $box] = openRun(capacity: 2);

    paidOrder(User::factory()->create(), $run, $box);
    expect($run->fresh()->state)->toBe(RunState::Open);

    paidOrder(User::factory()->create(), $run, $box);

    $run->refresh();
    expect($run->state)->toBe(RunState::Closed)
        ->and($run->slots_confirmed)->toBe(2)
        ->and($run->slots_held)->toBe(0);
});

test('BR-06: an unpaid hold is released after it expires', function () {
    ['run' => $run, 'box' => $box] = openRun(capacity: 1);
    $order = placeOrder(User::factory()->create(), $run, $box);

    expect($run->fresh()->slotsRemaining())->toBe(0);

    $this->travel(11)->minutes();
    app(RunClock::class)->tick();

    expect($order->fresh()->state)->toBe(OrderState::Expired)
        ->and($run->fresh()->slotsRemaining())->toBe(1);
});

test('BR-08: an order is not confirmed until the gateway confirms payment', function () {
    ['run' => $run, 'box' => $box] = openRun();
    $order = placeOrder(User::factory()->create(), $run, $box);

    app(PaymentService::class)->start($order);
    expect($order->fresh()->state)->toBe(OrderState::PendingPayment);

    expect(payOrder($order)->state)->toBe(OrderState::Confirmed)
        ->and($order->fresh()->pickup_code)->toHaveLength(6);
});

test('BR-09: a Run proceeds even when few slots are filled', function () {
    ['run' => $run, 'box' => $box] = openRun(capacity: 30);
    paidOrder(User::factory()->create(), $run, $box);

    $this->travelTo($run->closes_at->addMinute());
    app(RunClock::class)->tick();

    expect($run->fresh()->state)->toBe(RunState::Closed);
});

test('BR-10: both policies must be accepted before paying', function () {
    ['run' => $run, 'box' => $box] = openRun();

    expect(fn () => app(CheckoutService::class)->placeOrder(
        User::factory()->create(), $run, [$box->id => 1], [], Fulfilment::Pickup, null, null, '2348031234567', false, ['forfeiture'],
    ))->toThrow(BusinessRuleException::class, 'accept');
});

test('prices always come from the database, never the client', function () {
    ['run' => $run, 'box' => $box, 'addOn' => $addOn] = openRun();

    $order = placeOrder(User::factory()->create(), $run, $box, boxes: 2, addOns: [$addOn->id => 1]);

    expect($order->subtotal_kobo)->toBe(2 * 1500000 + 250000)
        ->and($order->total_kobo)->toBe($order->subtotal_kobo);
});

test('add-ons alone cannot be ordered and closed runs reject orders', function () {
    ['run' => $run, 'box' => $box, 'addOn' => $addOn] = openRun();
    $customer = User::factory()->create();

    expect(fn () => app(CheckoutService::class)->placeOrder($customer, $run, [], [$addOn->id => 1], Fulfilment::Pickup, null, null, '2348031234567', false, ['forfeiture', 'substitution']))
        ->toThrow(BusinessRuleException::class, 'at least one box');

    $run->forceFill(['state' => RunState::Closed])->save();
    expect(fn () => placeOrder($customer, $run->fresh(), $box))->toThrow(BusinessRuleException::class, 'closed');
});

test('BR-20: credit is applied at checkout and returned if the order is never paid', function () {
    ['run' => $run, 'box' => $box] = openRun();
    $customer = User::factory()->create();
    app(CreditService::class)->grant($customer, 500000, CreditReason::Substitution);

    $order = placeOrder($customer, $run, $box, useCredit: true);
    expect($order->credit_applied_kobo)->toBe(500000)
        ->and($order->total_kobo)->toBe(1000000)
        ->and($customer->creditBalanceKobo())->toBe(0);

    $this->travel(PaymentService::EXPIRED_GRACE_MINUTES + 15)->minutes();
    app(RunClock::class)->tick();
    app(RunClock::class)->tick();

    expect($order->fresh()->state)->toBe(OrderState::Cancelled)
        ->and($customer->creditBalanceKobo())->toBe(500000);
});

test('an order fully covered by credit is confirmed without the gateway', function () {
    ['run' => $run, 'box' => $box] = openRun();
    $customer = User::factory()->create();
    app(CreditService::class)->grant($customer, 2000000, CreditReason::Substitution);

    $order = placeOrder($customer, $run, $box, useCredit: true);

    expect($order->fresh()->state)->toBe(OrderState::Confirmed)
        ->and($order->total_kobo)->toBe(0)
        ->and($customer->creditBalanceKobo())->toBe(500000)
        ->and($run->fresh()->slots_confirmed)->toBe(1);
});

test('a customer cannot hoard slots with several unpaid orders', function () {
    ['run' => $run, 'box' => $box] = openRun();
    $customer = User::factory()->create();
    placeOrder($customer, $run, $box);

    expect(fn () => placeOrder($customer, $run, $box))->toThrow(BusinessRuleException::class, 'awaiting payment');
});
