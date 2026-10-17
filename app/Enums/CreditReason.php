<?php

namespace App\Enums;

enum CreditReason: string
{
    case Substitution = 'substitution';
    case UnavailableAddOn = 'unavailable_add_on';
    case AppliedToOrder = 'applied_to_order';
    case OrderReleased = 'order_released';
    case ManualAdjustment = 'manual_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Substitution => 'Box item unavailable',
            self::UnavailableAddOn => 'Add-on unavailable',
            self::AppliedToOrder => 'Used on an order',
            self::OrderReleased => 'Returned from an unpaid or cancelled order',
            self::ManualAdjustment => 'Adjustment',
        };
    }
}
