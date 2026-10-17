<?php

use App\Enums\OrderState;
use App\Enums\PaymentStatus;
use App\Enums\RunState;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\InvalidTransitionException;
use App\Models\Run;
use App\Models\User;
use App\Notifications\OrderUpdate;
use App\Notifications\RunOpened;
use App\Services\RunClock;
use App\Services\RunScheduleRules;
use App\Services\RunService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

test('BR-03: the pre-order window lasts at most 3 days', function () {
    $opens = CarbonImmutable::parse('2026-11-02 08:00');

    expect(RunScheduleRules::violations($opens, $opens->addDays(3), $opens->addDays(7), $opens->addDays(7)->setTime(18, 0)))->toBe([])
        ->and(RunScheduleRules::violations($opens, $opens->addDays(3)->addMinute(), $opens->addDays(8), $opens->addDays(8)))->toHaveKey('closes_at');
});

test('BR-05: orders close at least 2 days before market day (gap chosen by Admin)', function () {
    $opens = CarbonImmutable::parse('2026-11-02 08:00');
    $closes = $opens->addDays(2)->setTime(20, 0); // Wed 4 Nov 20:00

    expect(RunScheduleRules::violations($opens, $closes, CarbonImmutable::parse('2026-11-07'), CarbonImmutable::parse('2026-11-07 18:00')))->toBe([])
        ->and(RunScheduleRules::violations($opens, $closes, CarbonImmutable::parse('2026-11-06'), CarbonImmutable::parse('2026-11-06 18:00')))->toHaveKey('market_day');
});

test('the Run state machine rejects impossible transitions', function () {
    ['run' => $run] = openRun();

    expect(fn () => app(RunService::class)->transition($run, RunState::Arrived))
        ->toThrow(InvalidTransitionException::class);
});

test('roles may only make the transitions they own', function () {
    ['run' => $run] = openRun();
    $run->forceFill(['state' => RunState::Closed])->save();

    expect(fn () => app(RunService::class)->transition($run, RunState::Procuring, User::factory()->abujaOps()->create()))
        ->toThrow(AuthorizationException::class);

    app(RunService::class)->transition($run, RunState::Procuring, User::factory()->josOps()->create());
    expect($run->fresh()->state)->toBe(RunState::Procuring);
});

test('a Run cannot be packed before purchases are recorded', function () {
    ['run' => $run] = openRun();
    $run->forceFill(['state' => RunState::Procuring])->save();

    expect(fn () => app(RunService::class)->transition($run, RunState::Packed, User::factory()->josOps()->create()))
        ->toThrow(BusinessRuleException::class);
});

test('a full market day moves every paid order through the timeline', function () {
    ['run' => $run, 'box' => $box] = openRun(capacity: 5);
    $order = paidOrder(User::factory()->create(), $run, $box);
    $jos = User::factory()->josOps()->create();
    $abuja = User::factory()->abujaOps()->create();
    $runs = app(RunService::class);

    $runs->transition($run, RunState::Closed);
    $runs->transition($run, RunState::Procuring, $jos);
    $runs->markPurchased($run, $jos);
    expect($order->fresh()->state)->toBe(OrderState::Purchased);

    $runs->transition($run, RunState::Packed, $jos);
    $runs->transition($run, RunState::InTransit, $jos);
    $runs->transition($run, RunState::Arrived, $abuja);

    expect($order->fresh()->state)->toBe(OrderState::ReadyForPickup);
    Notification::assertSentTo($order->user, OrderUpdate::class, fn (OrderUpdate $n) => $n->event === 'arrived');
});

test('the scheduler opens Runs and tells the waitlist', function () {
    ['run' => $run] = openRun();
    $run->forceFill(['state' => RunState::Scheduled, 'opens_at' => now()->addMinutes(5)])->save();
    $waiting = User::factory()->create();
    $waiting->waitlistEntries()->create(['run_id' => null]);

    $this->travel(6)->minutes();
    app(RunClock::class)->tick();

    expect($run->fresh()->state)->toBe(RunState::Open);
    Notification::assertSentTo($waiting, RunOpened::class);
});

test('cancelling a Run refunds paid orders and frees their slots', function () {
    ['run' => $run, 'box' => $box] = openRun();
    $order = paidOrder(User::factory()->create(), $run, $box);

    app(RunService::class)->transition($run, RunState::Cancelled, User::factory()->admin()->create());

    expect($order->fresh()->state)->toBe(OrderState::Cancelled)
        ->and($order->payments()->first()->status)->toBe(PaymentStatus::Refunded)
        ->and(Run::query()->find($run->id)->slots_confirmed)->toBe(0);
});
