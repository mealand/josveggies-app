<?php

namespace App\Services;

use App\Enums\Fulfilment;
use App\Enums\OrderLineType;
use App\Enums\OrderState;
use App\Exceptions\BusinessRuleException;
use App\Models\AddOn;
use App\Models\BoxTemplate;
use App\Models\DispatchZone;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Policy;
use App\Models\Run;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Places orders. Slot booking happens under a row lock on the Run, so slots can never be
 * oversold even when many customers check out at the same moment (BR-07).
 */
class CheckoutService
{
    public const MAX_BOXES_PER_ORDER = 6;

    public function __construct(
        private CreditService $credit,
        private PaymentService $payments,
    ) {}

    /**
     * @param  array<int, int>  $boxes  box_template_id => quantity
     * @param  array<int, int>  $addOns  add_on_id => quantity
     * @param  list<string>  $acceptedPolicies  policy slugs the customer ticked
     */
    public function placeOrder(
        User $customer,
        Run $run,
        array $boxes,
        array $addOns,
        Fulfilment $fulfilment,
        ?int $dispatchZoneId,
        ?string $deliveryAddress,
        string $contactPhone,
        bool $useCredit,
        array $acceptedPolicies,
    ): Order {
        // BR-10: both policies must be accepted before paying.
        $missing = array_diff(Policy::CHECKOUT_REQUIRED, $acceptedPolicies);
        if ($missing !== []) {
            throw new BusinessRuleException('Please accept the forfeiture and substitution policies to continue.', 'BR-10');
        }

        $boxes = array_filter($boxes, fn (int $qty): bool => $qty > 0);
        $addOns = array_filter($addOns, fn (int $qty): bool => $qty > 0);
        $boxCount = array_sum($boxes);

        if ($boxCount === 0) {
            throw new BusinessRuleException('Add at least one box to your order. Add-ons are sold alongside boxes.');
        }

        if ($boxCount > self::MAX_BOXES_PER_ORDER) {
            throw new BusinessRuleException(sprintf('You can order up to %d boxes per Run.', self::MAX_BOXES_PER_ORDER));
        }

        $zone = null;
        if ($fulfilment === Fulfilment::Dispatch) {
            $zone = DispatchZone::query()->active()->find($dispatchZoneId);

            if ($zone === null || blank($deliveryAddress)) {
                throw new BusinessRuleException('Choose a delivery zone and enter your delivery address.');
            }
        }

        $order = $this->attempt(fn (): Order => DB::transaction(function () use ($customer, $run, $boxes, $addOns, $boxCount, $fulfilment, $zone, $deliveryAddress, $contactPhone, $useCredit): Order {
            /** @var Run $lockedRun */
            $lockedRun = Run::query()->lockForUpdate()->findOrFail($run->id);

            if (! $lockedRun->isAcceptingOrders()) {
                throw new BusinessRuleException('Ordering for this Run is closed.', 'BR-04');
            }

            if ($lockedRun->slotsRemaining() < $boxCount) {
                throw new BusinessRuleException(sprintf('Only %d slot(s) left in this Run.', $lockedRun->slotsRemaining()), 'BR-07');
            }

            $hasPending = Order::query()
                ->where('run_id', $lockedRun->id)
                ->where('user_id', $customer->id)
                ->where('state', OrderState::PendingPayment)
                ->exists();

            if ($hasPending) {
                throw new BusinessRuleException('You already have an order awaiting payment for this Run. Complete or let it expire first.');
            }

            $lines = $this->priceLines($lockedRun, $boxes, $addOns);
            $subtotal = array_sum(array_map(fn (OrderLine $line): int => $line->line_total_kobo, $lines));
            $dispatchFee = $zone->fee_kobo ?? 0;
            $gross = $subtotal + $dispatchFee;

            // Lock the customer row so concurrent checkouts cannot double-spend credit.
            /** @var User $lockedCustomer */
            $lockedCustomer = User::query()->lockForUpdate()->findOrFail($customer->id);
            $creditApplied = $useCredit ? min($lockedCustomer->creditBalanceKobo(), $gross) : 0;

            $order = new Order;
            $order->number = 'JV-'.Order::randomCode(6);
            $order->run_id = $lockedRun->id;
            $order->user_id = $customer->id;
            $order->state = OrderState::PendingPayment;
            $order->fulfilment = $fulfilment;
            $order->dispatch_zone_id = $zone?->id;
            $order->delivery_address = $fulfilment === Fulfilment::Dispatch ? $deliveryAddress : null;
            $order->contact_phone = $contactPhone;
            $order->slots = $boxCount;
            $order->subtotal_kobo = $subtotal;
            $order->dispatch_fee_kobo = $dispatchFee;
            $order->credit_applied_kobo = $creditApplied;
            $order->total_kobo = $gross - $creditApplied;
            $order->accepted_policies = Policy::currentVersions();
            $order->policies_accepted_at = now();
            $order->hold_expires_at = now()->addMinutes((int) Setting::get('hold_minutes'));
            $order->save();

            $order->lines()->saveMany($lines);

            if ($creditApplied > 0) {
                $this->credit->spend($lockedCustomer, $creditApplied, $order);
            }

            // BR-06: hold the slots while the customer pays.
            $lockedRun->slots_held += $boxCount;
            $lockedRun->save();

            activity('orders')->performedOn($order)->causedBy($customer)->log("Order {$order->number} placed");

            $order->setRelation('user', $customer);

            return $order;
        }), maxAttempts: 3);

        // Fully paid with credit: nothing to collect from the gateway.
        if ($order->total_kobo === 0) {
            $this->payments->confirmWithoutGateway($order);
        }

        return $order;
    }

    /**
     * @param  array<int, int>  $boxes
     * @param  array<int, int>  $addOns
     * @return list<OrderLine>
     */
    private function priceLines(Run $run, array $boxes, array $addOns): array
    {
        $lines = [];

        $offeredBoxes = $run->boxTemplates()->where('is_active', true)->with('items.packUnit')->whereIn('box_templates.id', array_keys($boxes))->get()->keyBy('id');
        foreach ($boxes as $boxId => $quantity) {
            /** @var BoxTemplate|null $box */
            $box = $offeredBoxes->get($boxId);
            if ($box === null) {
                throw new BusinessRuleException('One of the boxes in your cart is not available for this Run.');
            }

            $line = new OrderLine;
            $line->type = OrderLineType::Box;
            $line->box_template_id = $box->id;
            $line->name = $box->name;
            $line->quantity = $quantity;
            $line->unit_price_kobo = $box->price_kobo;
            $line->line_total_kobo = $box->price_kobo * $quantity;
            $line->contents = $box->contentsSnapshot();
            $lines[] = $line;
        }

        $offeredAddOns = $run->addOns()->where('is_active', true)->whereIn('add_ons.id', array_keys($addOns))->get()->keyBy('id');
        foreach ($addOns as $addOnId => $quantity) {
            /** @var AddOn|null $addOn */
            $addOn = $offeredAddOns->get($addOnId);
            if ($addOn === null) {
                throw new BusinessRuleException('One of the add-ons in your cart is not available for this Run.');
            }

            $line = new OrderLine;
            $line->type = OrderLineType::AddOn;
            $line->add_on_id = $addOn->id;
            $line->name = $addOn->name;
            $line->quantity = $quantity;
            $line->unit_price_kobo = $addOn->price_kobo;
            $line->line_total_kobo = $addOn->price_kobo * $quantity;
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * Retry on deadlocks or the (very unlikely) order-number collision.
     *
     * @param  callable(): Order  $callback
     */
    private function attempt(callable $callback, int $maxAttempts): Order
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return $callback();
            } catch (QueryException $e) {
                $retryable = in_array($e->errorInfo[1] ?? null, [1213, 1205, 1062], true);
                if (! $retryable || $attempt >= $maxAttempts) {
                    throw $e;
                }
            }
        }
    }
}
