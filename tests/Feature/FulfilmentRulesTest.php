<?php

use App\Enums\Fulfilment;
use App\Enums\HandoverOutcome;
use App\Enums\OrderState;
use App\Enums\RunState;
use App\Models\DispatchZone;
use App\Models\Order;
use App\Models\Rider;
use App\Models\Run;
use App\Models\User;
use App\Notifications\OrderUpdate;
use App\Services\CheckoutService;
use App\Services\FulfilmentService;
use App\Services\RunClock;
use App\Services\RunService;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

/**
 * Drive a Run with one paid order of each fulfilment type through to arrival.
 *
 * @return array{run: Run, pickup: Order, dispatch: Order}
 */
function arrivedRun(): array
{
    ['run' => $run, 'box' => $box] = openRun();
    $zone = DispatchZone::factory()->create(['fee_kobo' => 150000]);
    $pickup = paidOrder(User::factory()->create(), $run, $box);
    $dispatch = payOrder(app(CheckoutService::class)->placeOrder(
        User::factory()->create(), $run, [$box->id => 1], [], Fulfilment::Dispatch, $zone->id, '5 Example Street, Wuse', '2348031234567', false, ['forfeiture', 'substitution'],
    ));

    $jos = User::factory()->josOps()->create();
    $runs = app(RunService::class);
    $runs->transition($run, RunState::Closed);
    $runs->transition($run, RunState::Procuring, $jos);
    $runs->markPurchased($run, $jos);
    $runs->transition($run, RunState::Packed, $jos);
    $runs->transition($run, RunState::InTransit, $jos);
    $runs->transition($run, RunState::Arrived, User::factory()->abujaOps()->create());

    return ['run' => $run->fresh(), 'pickup' => $pickup->fresh(), 'dispatch' => $dispatch->fresh()];
}

test('BR-18: dispatch fees are fixed per zone', function () {
    ['dispatch' => $dispatch] = arrivedRun();

    expect($dispatch->dispatch_fee_kobo)->toBe(150000)
        ->and($dispatch->total_kobo)->toBe(1500000 + 150000);
});

test('BR-15: the pickup window starts at arrival and ends at noon the next day', function () {
    $this->travelTo(now()->setTime(19, 30));
    ['run' => $run] = arrivedRun();

    expect($run->pickup_ends_at->toDateTimeString())->toBe(now()->addDay()->setTime(12, 0)->toDateTimeString());
});

test('hub staff collect orders with the pickup code', function () {
    ['pickup' => $pickup] = arrivedRun();
    $service = app(FulfilmentService::class);

    $found = $service->findByPickupCode(strtolower($pickup->pickup_code));
    expect($found?->id)->toBe($pickup->id)
        ->and($service->findByPickupCode('ZZZZZZ'))->toBeNull();

    $service->markCollected($found, User::factory()->abujaOps()->create());
    expect($pickup->fresh()->state)->toBe(OrderState::Collected);
});

test('BR-16: uncollected orders are forfeited automatically at the deadline', function () {
    ['run' => $run, 'pickup' => $pickup] = arrivedRun();

    $this->travelTo($run->pickup_ends_at->addMinute());
    app(RunClock::class)->tick();

    expect($pickup->fresh()->state)->toBe(OrderState::Forfeited)
        ->and($run->fresh()->state)->toBe(RunState::PickupClosed);
});

test('BR-17: a failed delivery returns to the hub under the same deadline', function () {
    ['run' => $run, 'dispatch' => $dispatch] = arrivedRun();
    $service = app(FulfilmentService::class);
    $abuja = User::factory()->abujaOps()->create();

    $handover = $service->assignRider($dispatch, Rider::factory()->create(), $abuja);
    expect($dispatch->fresh()->state)->toBe(OrderState::OutForDelivery);

    $service->recordOutcome($handover, HandoverOutcome::Failed, $abuja);
    expect($dispatch->fresh()->state)->toBe(OrderState::ReadyForPickup);

    $this->travelTo($run->pickup_ends_at->addMinute());
    app(RunClock::class)->tick();
    expect($dispatch->fresh()->state)->toBe(OrderState::Forfeited);
});

test('pickup reminders go out once at 9am and 11am on deadline day', function () {
    $this->travelTo(now()->setTime(19, 0));
    ['pickup' => $pickup] = arrivedRun();

    $this->travelTo(now()->addDay()->setTime(9, 1));
    app(RunClock::class)->tick();
    app(RunClock::class)->tick();
    $this->travelTo(now()->setTime(11, 1));
    app(RunClock::class)->tick();

    // confirmed, purchased, packed, in_transit, arrived + one morning + one final (no duplicates).
    Notification::assertSentToTimes($pickup->user, OrderUpdate::class, 7);
    Notification::assertSentTo($pickup->user, OrderUpdate::class, fn (OrderUpdate $n) => $n->event === 'reminder_morning');
    Notification::assertSentTo($pickup->user, OrderUpdate::class, fn (OrderUpdate $n) => $n->event === 'reminder_final');
});
