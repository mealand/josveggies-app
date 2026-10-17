<?php

namespace App\Services;

use App\Enums\OrderState;
use App\Exceptions\InvalidTransitionException;
use App\Models\Order;
use App\Models\User;

/**
 * The single place where an Order changes state. Records an audit entry for every change.
 */
class OrderStateMachine
{
    public function transition(Order $order, OrderState $to, ?User $actor = null, ?string $note = null): Order
    {
        $from = $order->state;

        if ($from === $to) {
            return $order;
        }

        if (! $from->canTransitionTo($to)) {
            throw InvalidTransitionException::between('Order '.$order->number, $from, $to);
        }

        $order->state = $to;

        match ($to) {
            OrderState::Confirmed => $order->confirmed_at = now(),
            OrderState::Collected, OrderState::Delivered => $order->collected_at = now(),
            OrderState::Forfeited => $order->forfeited_at = now(),
            OrderState::Cancelled => $order->cancelled_at = now(),
            default => null,
        };

        if ($to === OrderState::Collected) {
            $order->collected_by = $actor?->id;
        }

        if ($to === OrderState::Cancelled && $note !== null) {
            $order->cancellation_reason = $note;
        }

        $order->save();

        activity('orders')
            ->performedOn($order)
            ->causedBy($actor)
            ->event('state_changed')
            ->withProperties(['from' => $from->value, 'to' => $to->value, 'note' => $note])
            ->log("Order {$order->number}: {$from->value} → {$to->value}");

        return $order;
    }
}
