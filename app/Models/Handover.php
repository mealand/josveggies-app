<?php

namespace App\Models;

use App\Enums\HandoverOutcome;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $rider_id
 * @property HandoverOutcome $outcome
 * @property CarbonImmutable $handed_over_at
 * @property CarbonImmutable|null $resolved_at
 * @property string|null $notes
 * @property int|null $recorded_by
 * @property-read Order $order
 * @property-read Rider $rider
 */
class Handover extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'outcome' => HandoverOutcome::class,
            'handed_over_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Rider, $this>
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
