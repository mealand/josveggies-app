<?php

namespace App\Models;

use App\Enums\CreditReason;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only ledger: entries are never updated or deleted, so every balance can be explained.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $order_id
 * @property int $amount_kobo
 * @property CreditReason $reason
 * @property string|null $description
 * @property int|null $created_by
 * @property CarbonImmutable $created_at
 */
class CreditLedgerEntry extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Credit ledger entries are immutable.'));
        static::deleting(fn () => throw new LogicException('Credit ledger entries are immutable.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'reason' => CreditReason::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
