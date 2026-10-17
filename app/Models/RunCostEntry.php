<?php

namespace App\Models;

use App\Enums\CostCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $run_id
 * @property CostCategory $category
 * @property int $amount_kobo
 * @property string $description
 * @property int|null $recorded_by
 */
#[Fillable(['category', 'amount_kobo', 'description'])]
class RunCostEntry extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => CostCategory::class,
            'amount_kobo' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Run, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
