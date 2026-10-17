<?php

namespace App\Enums;

enum HandoverOutcome: string
{
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed attempt',
        };
    }
}
