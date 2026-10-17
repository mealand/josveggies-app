<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $order_line_id
 * @property int $original_item_id
 * @property int|null $substitute_item_id
 * @property int $quantity
 * @property int $original_value_kobo
 * @property int|null $substitute_value_kobo
 * @property int $credit_kobo
 * @property string|null $batch_id
 * @property int|null $recorded_by
 * @property CarbonImmutable|null $notified_at
 * @property-read Order $order
 * @property-read Item $originalItem
 * @property-read Item|null $substituteItem
 */
class Substitution extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'original_value_kobo' => 'integer',
            'substitute_value_kobo' => 'integer',
            'credit_kobo' => 'integer',
            'notified_at' => 'datetime',
        ];
    }

    public function isCredit(): bool
    {
        return $this->substitute_item_id === null;
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<OrderLine, $this>
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function originalItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'original_item_id');
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function substituteItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'substitute_item_id');
    }
}
