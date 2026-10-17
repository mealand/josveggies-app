<?php

namespace App\Enums;

enum Fulfilment: string
{
    case Pickup = 'pickup';
    case Dispatch = 'dispatch';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Pickup at hub',
            self::Dispatch => 'Rider dispatch',
        };
    }
}
