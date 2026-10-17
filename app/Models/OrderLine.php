<?php

namespace App\Models;

use App\Enums\OrderLineType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $order_id
 * @property OrderLineType $type
 * @property int|null $box_template_id
 * @property int|null $add_on_id
 * @property string $name
 * @property int $quantity
 * @property int $unit_price_kobo
 * @property int $line_total_kobo
 * @property list<array{item_id: int, name: string, quantity: int, unit_value_kobo: int, pack: string|null}>|null $contents
 * @property CarbonImmutable|null $unavailable_at
 * @property-read Order $order
 * @property-read AddOn|null $addOn
 */
class OrderLine extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OrderLineType::class,
            'quantity' => 'integer',
            'unit_price_kobo' => 'integer',
            'line_total_kobo' => 'integer',
            'contents' => 'array',
            'unavailable_at' => 'datetime',
        ];
    }

    public function isBox(): bool
    {
        return $this->type === OrderLineType::Box;
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<BoxTemplate, $this>
     */
    public function boxTemplate(): BelongsTo
    {
        return $this->belongsTo(BoxTemplate::class);
    }

    /**
     * @return BelongsTo<AddOn, $this>
     */
    public function addOn(): BelongsTo
    {
        return $this->belongsTo(AddOn::class);
    }

    /**
     * @return HasMany<Substitution, $this>
     */
    public function substitutions(): HasMany
    {
        return $this->hasMany(Substitution::class);
    }
}
