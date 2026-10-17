<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $item_id
 * @property string $name
 * @property int $quantity
 * @property int $price_kobo
 * @property bool $is_active
 * @property int $sort_order
 * @property-read Item $item
 */
#[Fillable(['item_id', 'name', 'quantity', 'price_kobo', 'is_active', 'sort_order'])]
class AddOn extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_kobo' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsToMany<Run, $this>
     */
    public function runs(): BelongsToMany
    {
        return $this->belongsToMany(Run::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('price_kobo');
    }
}
