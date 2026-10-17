<?php

namespace App\Enums;

enum OrderState: string
{
    case PendingPayment = 'pending_payment';
    case Expired = 'expired';
    case Confirmed = 'confirmed';
    case Purchased = 'purchased';
    case Packed = 'packed';
    case InTransit = 'in_transit';
    case ReadyForPickup = 'ready_for_pickup';
    case OutForDelivery = 'out_for_delivery';
    case Collected = 'collected';
    case Delivered = 'delivered';
    case Forfeited = 'forfeited';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending payment',
            self::Expired => 'Payment window expired',
            self::Confirmed => 'Confirmed',
            self::Purchased => 'Purchased',
            self::Packed => 'Packed',
            self::InTransit => 'In transit',
            self::ReadyForPickup => 'Ready',
            self::OutForDelivery => 'Out for delivery',
            self::Collected => 'Collected',
            self::Delivered => 'Delivered',
            self::Forfeited => 'Forfeited',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * What the customer sees for this state (Webapp Concept §6).
     */
    public function customerMessage(Fulfilment $fulfilment): string
    {
        return match ($this) {
            self::PendingPayment => 'Complete your payment',
            self::Expired => 'Your payment window expired',
            self::Confirmed => 'Order confirmed',
            self::Purchased => 'Your vegetables have been bought',
            self::Packed => 'Your box is packed',
            self::InTransit => 'On the way to Abuja',
            self::ReadyForPickup => $fulfilment === Fulfilment::Pickup
                ? 'Ready — show your pickup code'
                : 'Arrived in Abuja — a rider is being assigned',
            self::OutForDelivery => 'Rider on the way',
            self::Collected, self::Delivered => 'Enjoy your veggies!',
            self::Forfeited => 'Not collected in time',
            self::Cancelled => 'Order cancelled',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingPayment => [self::Confirmed, self::Expired, self::Cancelled],
            self::Expired => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Purchased, self::Cancelled],
            self::Purchased => [self::Packed, self::Cancelled],
            self::Packed => [self::InTransit, self::Cancelled],
            self::InTransit => [self::ReadyForPickup],
            self::ReadyForPickup => [self::Collected, self::OutForDelivery, self::Forfeited],
            self::OutForDelivery => [self::Delivered, self::ReadyForPickup, self::Forfeited],
            self::Collected, self::Delivered, self::Forfeited, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * Orders that have been paid for and hold a confirmed slot.
     *
     * @return list<self>
     */
    public static function paid(): array
    {
        return [
            self::Confirmed, self::Purchased, self::Packed, self::InTransit,
            self::ReadyForPickup, self::OutForDelivery, self::Collected,
            self::Delivered, self::Forfeited,
        ];
    }

    /**
     * Customer-facing tracking steps, in order.
     *
     * @return list<self>
     */
    public static function timeline(Fulfilment $fulfilment): array
    {
        return $fulfilment === Fulfilment::Pickup
            ? [self::Confirmed, self::Purchased, self::Packed, self::InTransit, self::ReadyForPickup, self::Collected]
            : [self::Confirmed, self::Purchased, self::Packed, self::InTransit, self::ReadyForPickup, self::OutForDelivery, self::Delivered];
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }
}
