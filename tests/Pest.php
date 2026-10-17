<?php

use App\Enums\Fulfilment;
use App\Models\AddOn;
use App\Models\BoxTemplate;
use App\Models\Item;
use App\Models\Order;
use App\Models\Run;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\Payments\GatewayTransaction;
use App\Services\PaymentService;
use Database\Seeders\PolicySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seed(PolicySeeder::class))
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * An open Run offering one box (two items, ₦1,000 each) and one add-on.
 *
 * @return array{run: Run, box: BoxTemplate, addOn: AddOn, items: list<Item>}
 */
function openRun(int $capacity = 10, array $runAttributes = []): array
{
    $items = [
        Item::factory()->create(['name' => 'Tomatoes', 'category' => 'fruiting', 'reference_price_kobo' => 100000]),
        Item::factory()->create(['name' => 'Ugu', 'category' => 'leafy', 'reference_price_kobo' => 50000]),
    ];
    $box = BoxTemplate::factory()->withItems($items)->create(['name' => 'Soup Pot Box', 'price_kobo' => 1500000]);
    $addOn = AddOn::factory()->create(['item_id' => $items[0]->id, 'name' => 'Extra tomatoes', 'price_kobo' => 250000]);
    $run = Run::factory()->open()->offering($box, $addOn)->create(['slot_capacity' => $capacity, ...$runAttributes]);

    return ['run' => $run->fresh(), 'box' => $box, 'addOn' => $addOn, 'items' => $items];
}

/**
 * Place an order through the real checkout service (policies accepted, pickup).
 *
 * @param  array<int, int>  $addOns
 */
function placeOrder(User $customer, Run $run, BoxTemplate $box, int $boxes = 1, array $addOns = [], bool $useCredit = false): Order
{
    return app(CheckoutService::class)->placeOrder(
        $customer, $run, [$box->id => $boxes], $addOns, Fulfilment::Pickup, null, null,
        '2348031234567', $useCredit, ['forfeiture', 'substitution'],
    );
}

/**
 * Simulate the gateway confirming a successful payment for the order.
 */
function payOrder(Order $order): Order
{
    $payments = app(PaymentService::class);
    $payments->start($order);
    $payment = $order->payments()->latest('id')->firstOrFail();
    $payments->applySuccessfulCharge(new GatewayTransaction($payment->reference, true, $payment->amount_kobo, 'NGN', 'card'));

    return $order->fresh();
}

/**
 * Place and pay for an order in one step.
 */
function paidOrder(User $customer, Run $run, BoxTemplate $box, int $boxes = 1, array $addOns = []): Order
{
    return payOrder(placeOrder($customer, $run, $box, $boxes, $addOns));
}
