<?php

namespace App\Services;

use App\Enums\Fulfilment;
use App\Enums\HandoverOutcome;
use App\Enums\OrderState;
use App\Enums\RunState;
use App\Exceptions\BusinessRuleException;
use App\Models\Handover;
use App\Models\Order;
use App\Models\Rider;
use App\Models\User;
use App\Notifications\OrderUpdate;
use Illuminate\Support\Facades\DB;

/**
 * Abuja hub operations: pickup verification, rider handovers and delivery outcomes.
 */
class FulfilmentService
{
    public function __construct(private OrderStateMachine $orders) {}

    /**
     * Find the order for a pickup code among Runs currently in their pickup window.
     */
    public function findByPickupCode(string $code): ?Order
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');

        if (strlen($code) !== 6) {
            return null;
        }

        return Order::query()
            ->where('pickup_code_hash', Order::hashPickupCode($code))
            ->whereHas('run', fn ($q) => $q->where('state', RunState::Arrived))
            ->with(['user', 'lines', 'run'])
            ->first();
    }

    public function markCollected(Order $order, User $actor): Order
    {
        return DB::transaction(function () use ($order, $actor): Order {
            /** @var Order $locked */
            $locked = Order::query()->with('run', 'user')->lockForUpdate()->findOrFail($order->id);

            if ($locked->run->state !== RunState::Arrived) {
                throw new BusinessRuleException('Collection is only possible during the pickup window.');
            }

            if ($locked->state !== OrderState::ReadyForPickup) {
                throw new BusinessRuleException("Order {$locked->number} is {$locked->state->label()}, not ready for collection.");
            }

            $this->orders->transition($locked, OrderState::Collected, $actor);
            $locked->user->notify((new OrderUpdate($locked, 'collected'))->afterCommit());

            return $locked;
        });
    }

    /**
     * Hand a dispatch order to a rider.
     */
    public function assignRider(Order $order, Rider $rider, User $actor): Handover
    {
        return DB::transaction(function () use ($order, $rider, $actor): Handover {
            /** @var Order $locked */
            $locked = Order::query()->with('run', 'user')->lockForUpdate()->findOrFail($order->id);

            if ($locked->fulfilment !== Fulfilment::Dispatch) {
                throw new BusinessRuleException('Only dispatch orders can be handed to a rider.');
            }

            if ($locked->run->state !== RunState::Arrived || $locked->state !== OrderState::ReadyForPickup) {
                throw new BusinessRuleException("Order {$locked->number} is not waiting at the hub.");
            }

            if (! $rider->is_active) {
                throw new BusinessRuleException('This rider is inactive.');
            }

            $handover = new Handover;
            $handover->order_id = $locked->id;
            $handover->rider_id = $rider->id;
            $handover->outcome = HandoverOutcome::OutForDelivery;
            $handover->handed_over_at = now();
            $handover->recorded_by = $actor->id;
            $handover->save();

            $this->orders->transition($locked, OrderState::OutForDelivery, $actor);
            $locked->user->notify((new OrderUpdate($locked, 'out_for_delivery'))->afterCommit());

            activity('handovers')->performedOn($handover)->causedBy($actor)
                ->withProperties(['order' => $locked->number, 'rider' => $rider->name])
                ->log('handover.out');

            return $handover;
        });
    }

    /**
     * Record the result of a delivery. A failed attempt returns to the hub (BR-17).
     */
    public function recordOutcome(Handover $handover, HandoverOutcome $outcome, User $actor, ?string $notes = null): Handover
    {
        if ($outcome === HandoverOutcome::OutForDelivery) {
            throw new BusinessRuleException('Choose delivered or failed.');
        }

        return DB::transaction(function () use ($handover, $outcome, $actor, $notes): Handover {
            /** @var Handover $locked */
            $locked = Handover::query()->with('order.user')->lockForUpdate()->findOrFail($handover->id);

            if ($locked->outcome !== HandoverOutcome::OutForDelivery) {
                throw new BusinessRuleException('This handover has already been resolved.');
            }

            $locked->outcome = $outcome;
            $locked->resolved_at = now();
            $locked->notes = $notes;
            $locked->save();

            $order = $locked->order;
            if ($outcome === HandoverOutcome::Delivered) {
                $this->orders->transition($order, OrderState::Delivered, $actor);
                $order->user->notify((new OrderUpdate($order, 'delivered'))->afterCommit());
            } else {
                $this->orders->transition($order, OrderState::ReadyForPickup, $actor, 'Delivery failed');
                $order->user->notify((new OrderUpdate($order, 'delivery_failed'))->afterCommit());
            }

            activity('handovers')->performedOn($locked)->causedBy($actor)
                ->withProperties(['order' => $order->number, 'outcome' => $outcome->value, 'notes' => $notes])
                ->log('handover.resolved');

            return $locked;
        });
    }
}
