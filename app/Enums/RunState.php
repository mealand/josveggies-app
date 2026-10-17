<?php

namespace App\Enums;

enum RunState: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Open = 'open';
    case Closed = 'closed';
    case Procuring = 'procuring';
    case Packed = 'packed';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';
    case PickupClosed = 'pickup_closed';
    case Reconciled = 'reconciled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Open => 'Open',
            self::Closed => 'Closed',
            self::Procuring => 'Procuring',
            self::Packed => 'Packed',
            self::InTransit => 'In transit',
            self::Arrived => 'Arrived',
            self::PickupClosed => 'Pickup closed',
            self::Reconciled => 'Reconciled',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * The allowed next states (the Run state machine).
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Scheduled, self::Cancelled],
            self::Scheduled => [self::Open, self::Draft, self::Cancelled],
            self::Open => [self::Closed, self::Cancelled],
            self::Closed => [self::Procuring, self::Cancelled],
            self::Procuring => [self::Packed],
            self::Packed => [self::InTransit],
            self::InTransit => [self::Arrived],
            self::Arrived => [self::PickupClosed],
            self::PickupClosed => [self::Reconciled],
            self::Reconciled, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * Roles allowed to move a Run INTO this state by hand. Scheduled jobs act as the system.
     *
     * @return list<Role>
     */
    public function rolesAllowedToEnter(): array
    {
        return match ($this) {
            self::Procuring, self::Packed, self::InTransit => [Role::Admin, Role::JosOps],
            self::Arrived => [Role::Admin, Role::AbujaOps],
            default => [Role::Admin],
        };
    }

    /**
     * States shown on the public storefront calendar.
     *
     * @return list<self>
     */
    public static function publiclyVisible(): array
    {
        return [self::Scheduled, self::Open, self::Closed];
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::Draft, self::Reconciled, self::Cancelled], true);
    }
}
