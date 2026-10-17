<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string|null $company
 * @property bool $is_active
 */
#[Fillable(['name', 'phone', 'company', 'is_active'])]
#[Hidden(['phone'])]
class Rider extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Handover, $this>
     */
    public function handovers(): HasMany
    {
        return $this->hasMany(Handover::class);
    }
}
