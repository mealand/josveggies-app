<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $category
 * @property int|null $pack_unit_id
 * @property int $reference_price_kobo
 * @property bool $is_active
 * @property-read PackUnit|null $packUnit
 */
#[Fillable(['name', 'category', 'pack_unit_id', 'reference_price_kobo', 'is_active'])]
class Item extends Model
{
    use HasFactory;

    /**
     * Categories used to prefer like-for-like substitutions (BR-11).
     *
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'fruiting' => 'Tomatoes & peppers',
        'bulb' => 'Onions & garlic',
        'leafy' => 'Leafy greens',
        'herb' => 'Herbs',
        'root' => 'Roots & tubers',
        'salad' => 'Salad vegetables',
        'other' => 'Other',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reference_price_kobo' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<PackUnit, $this>
     */
    public function packUnit(): BelongsTo
    {
        return $this->belongsTo(PackUnit::class);
    }
}
