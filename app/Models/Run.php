<?php

namespace App\Models;

use App\Enums\OrderState;
use App\Enums\RunState;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Run is one complete delivery cycle and the central object of the system.
 *
 * BR-19: `market_day` is confidential. It is hidden from serialisation and must only be
 * rendered in views guarded by `User::canSeeMarketDay()`. Customers see `delivery_starts_at`.
 *
 * @property int $id
 * @property string $code
 * @property RunState $state
 * @property int $hub_id
 * @property int $slot_capacity
 * @property int $slots_confirmed
 * @property int $slots_held
 * @property int|null $break_even_boxes
 * @property CarbonImmutable $opens_at
 * @property CarbonImmutable $closes_at
 * @property CarbonImmutable $market_day
 * @property CarbonImmutable $delivery_starts_at
 * @property CarbonImmutable|null $closed_at
 * @property CarbonImmutable|null $purchased_at
 * @property CarbonImmutable|null $packed_at
 * @property CarbonImmutable|null $departed_at
 * @property CarbonImmutable|null $arrived_at
 * @property CarbonImmutable|null $pickup_ends_at
 * @property CarbonImmutable|null $morning_reminder_sent_at
 * @property CarbonImmutable|null $final_reminder_sent_at
 * @property CarbonImmutable|null $waitlist_notified_at
 * @property CarbonImmutable|null $reconciled_at
 * @property CarbonImmutable|null $cancelled_at
 * @property string|null $internal_notes
 * @property-read Hub $hub
 */
#[Fillable([
    'code', 'hub_id', 'slot_capacity', 'break_even_boxes', 'opens_at', 'closes_at',
    'market_day', 'delivery_starts_at', 'internal_notes',
])]
#[Hidden(['market_day', 'internal_notes', 'purchased_at', 'packed_at', 'departed_at'])]
class Run extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => RunState::class,
            'slot_capacity' => 'integer',
            'slots_confirmed' => 'integer',
            'slots_held' => 'integer',
            'break_even_boxes' => 'integer',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'market_day' => 'date',
            'delivery_starts_at' => 'datetime',
            'closed_at' => 'datetime',
            'purchased_at' => 'datetime',
            'packed_at' => 'datetime',
            'departed_at' => 'datetime',
            'arrived_at' => 'datetime',
            'pickup_ends_at' => 'datetime',
            'morning_reminder_sent_at' => 'datetime',
            'final_reminder_sent_at' => 'datetime',
            'waitlist_notified_at' => 'datetime',
            'reconciled_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function slotsRemaining(): int
    {
        return max(0, $this->slot_capacity - $this->slots_confirmed - $this->slots_held);
    }

    public function isFull(): bool
    {
        return $this->slotsRemaining() === 0;
    }

    /**
     * Whether customers can place orders right now (BR-04, BR-05).
     */
    public function isAcceptingOrders(): bool
    {
        return $this->state === RunState::Open
            && now()->lt($this->closes_at)
            && $this->slotsRemaining() > 0;
    }

    public function fillPercent(): int
    {
        if ($this->slot_capacity === 0) {
            return 0;
        }

        return (int) round(($this->slots_confirmed / $this->slot_capacity) * 100);
    }

    /**
     * Runs a customer can see on the storefront, soonest first.
     *
     * @param  Builder<self>  $query
     */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->whereIn('state', RunState::publiclyVisible())
            ->where('delivery_starts_at', '>', now())
            ->orderBy('opens_at');
    }

    /**
     * @return BelongsTo<Hub, $this>
     */
    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function paidOrders(): HasMany
    {
        return $this->orders()->whereIn('state', OrderState::paid());
    }

    /**
     * @return BelongsToMany<BoxTemplate, $this>
     */
    public function boxTemplates(): BelongsToMany
    {
        return $this->belongsToMany(BoxTemplate::class);
    }

    /**
     * @return BelongsToMany<AddOn, $this>
     */
    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(AddOn::class);
    }

    /**
     * @return HasMany<RunCostEntry, $this>
     */
    public function costEntries(): HasMany
    {
        return $this->hasMany(RunCostEntry::class);
    }

    /**
     * @return HasMany<WaitlistEntry, $this>
     */
    public function waitlistEntries(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class);
    }
}
