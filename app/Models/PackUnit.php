<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $traditional_name
 * @property string $container
 * @property int|null $min_weight_g
 * @property int|null $max_weight_g
 * @property string|null $photo_path
 */
#[Fillable(['traditional_name', 'container', 'min_weight_g', 'max_weight_g', 'photo_path'])]
class PackUnit extends Model
{
    /**
     * A short human description, e.g. "1 Mudu · 10×10 cm box · 1.2–1.4 kg".
     */
    public function summary(): string
    {
        $parts = [$this->traditional_name, $this->container];

        if ($this->min_weight_g && $this->max_weight_g) {
            $parts[] = sprintf('%s–%s kg', rtrim(rtrim(number_format($this->min_weight_g / 1000, 2), '0'), '.'), rtrim(rtrim(number_format($this->max_weight_g / 1000, 2), '0'), '.'));
        }

        return implode(' · ', $parts);
    }

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
