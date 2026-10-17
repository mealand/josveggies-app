<?php

namespace App\Enums;

enum CostCategory: string
{
    case Purchases = 'purchases';
    case Transport = 'transport';
    case Packaging = 'packaging';
    case Labour = 'labour';
    case Dispatch = 'dispatch';
    case Other = 'other';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
