<?php

use App\Enums\OrderLineType;
use App\Enums\RunState;
use App\Exceptions\BusinessRuleException;
use App\Models\BoxTemplate;
use App\Models\Item;
use App\Models\Run;
use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\OrderUpdate;
use App\Services\ProcurementService;
use App\Services\RunService;
use App\Services\SubstitutionService;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

function boxLine($order)
{
    return $order->lines()->where('type', OrderLineType::Box)->firstOrFail();
}

test('BR-11: substitutes must be of equal or greater value', function () {
    ['run' => $run, 'box' => $box, 'items' => [$tomatoes]] = openRun();
    $order = paidOrder(User::factory()->create(), $run, $box);
    $cheaper = Item::factory()->create(['category' => 'fruiting', 'reference_price_kobo' => 50000]);

    expect(fn () => app(SubstitutionService::class)->substitute(boxLine($order), $tomatoes, $cheaper, User::factory()->josOps()->create()))
        ->toThrow(BusinessRuleException::class, 'worth less');
});

test('BR-11: at most two items per box can be substituted', function () {
    $items = Item::factory()->count(3)->create(['reference_price_kobo' => 100000]);
    $box = BoxTemplate::factory()->withItems($items->all())->create();
    $run = Run::factory()->open()->offering($box)->create();
    $order = paidOrder(User::factory()->create(), $run->fresh(), $box);
    $jos = User::factory()->josOps()->create();
    $service = app(SubstitutionService::class);

    $service->substitute(boxLine($order), $items[0], null, $jos);
    $service->substitute(boxLine($order), $items[1], null, $jos);

    expect(fn () => $service->substitute(boxLine($order), $items[2], null, $jos))
        ->toThrow(BusinessRuleException::class, 'At most 2');
});

test('BR-12: without a fair substitute the value becomes credit', function () {
    ['run' => $run, 'box' => $box, 'items' => [$tomatoes]] = openRun();
    $customer = User::factory()->create();
    $order = paidOrder($customer, $run, $box, boxes: 2);

    app(SubstitutionService::class)->substitute(boxLine($order), $tomatoes, null, User::factory()->josOps()->create());

    // 2 boxes × 1 pack × ₦1,000 reference value at order time.
    expect($customer->creditBalanceKobo())->toBe(200000);
});

test('a valid substitution changes what is bought and packed', function () {
    ['run' => $run, 'box' => $box, 'items' => [$tomatoes]] = openRun();
    $order = paidOrder(User::factory()->create(), $run, $box);
    $peppers = Item::factory()->create(['name' => 'Tatashe', 'category' => 'fruiting', 'reference_price_kobo' => 120000]);

    app(SubstitutionService::class)->substitute(boxLine($order), $tomatoes, $peppers, User::factory()->josOps()->create());

    $list = app(ProcurementService::class)->procurementList($run)->pluck('quantity', 'name');
    expect($list)->toHaveKey('Tatashe')->not->toHaveKey('Tomatoes');
});

test('BR-13: unavailable add-ons are credited in full, never substituted', function () {
    ['run' => $run, 'box' => $box, 'addOn' => $addOn] = openRun();
    $customer = User::factory()->create();
    paidOrder($customer, $run, $box, addOns: [$addOn->id => 2]);

    $result = app(SubstitutionService::class)->markAddOnUnavailableAcrossRun($run, $addOn->id, User::factory()->josOps()->create());

    expect($result['applied'])->toBe(1)
        ->and($customer->creditBalanceKobo())->toBe(2 * 250000);
});

test('BR-14: customers hear about substitutions when their box is packed', function () {
    ['run' => $run, 'box' => $box, 'items' => [$tomatoes]] = openRun();
    $order = paidOrder(User::factory()->create(), $run, $box);
    $jos = User::factory()->josOps()->create();
    $runs = app(RunService::class);

    $runs->transition($run, RunState::Closed);
    $runs->transition($run, RunState::Procuring, $jos);
    app(SubstitutionService::class)->substitute(boxLine($order), $tomatoes, null, $jos);
    $runs->markPurchased($run, $jos);
    $runs->transition($run, RunState::Packed, $jos);

    Notification::assertSentTo($order->user, OrderUpdate::class, fn (OrderUpdate $n) => $n->event === 'packed'
        && in_array(SmsChannel::class, $n->via($order->user), true));
});
