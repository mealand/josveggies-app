<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $tagline
 * @property string|null $description
 * @property int $price_kobo
 * @property string|null $photo_path
 * @property string $accent
 * @property bool $is_active
 * @property int $sort_order
 */
#[Fillable(['name', 'slug', 'tagline', 'description', 'price_kobo', 'photo_path', 'accent', 'is_active', 'sort_order'])]
class BoxTemplate extends Model
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
     * @return BelongsToMany<Item, $this, Pivot, 'pivot'>
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'box_template_items')->withPivot('quantity');
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

    /**
     * Snapshot of the contents at order time, so later template edits never change past orders.
     *
     * @return list<array{item_id: int, name: string, quantity: int, unit_value_kobo: int, pack: string|null}>
     */
    public function contentsSnapshot(): array
    {
        $quantities = DB::table('box_template_items')->where('box_template_id', $this->id)->pluck('quantity', 'item_id');

        return $this->items->load('packUnit')->map(fn (Item $item): array => [
            'item_id' => $item->id,
            'name' => $item->name,
            'quantity' => (int) $quantities[$item->id],
            'unit_value_kobo' => $item->reference_price_kobo,
            'pack' => $item->packUnit?->traditional_name,
        ])->values()->all();
    }
}
