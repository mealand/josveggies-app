<?php

namespace App\Enums;

enum Role: string
{
    case Customer = 'customer';
    case Admin = 'admin';
    case JosOps = 'jos_ops';
    case AbujaOps = 'abuja_ops';
    case Rider = 'rider';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Admin => 'Admin',
            self::JosOps => 'Jos Ops',
            self::AbujaOps => 'Abuja Ops',
            self::Rider => 'Rider',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }

    /**
     * BR-19: the market day is visible only to Admin and Jos Ops.
     */
    public function canSeeMarketDay(): bool
    {
        return in_array($this, [self::Admin, self::JosOps], true);
    }

    /**
     * @return list<self>
     */
    public static function staff(): array
    {
        return [self::Admin, self::JosOps, self::AbujaOps, self::Rider];
    }
}
