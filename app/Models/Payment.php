<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property string $provider
 * @property string $reference
 * @property int $amount_kobo
 * @property string $currency
 * @property PaymentStatus $status
 * @property string|null $channel
 * @property CarbonImmutable|null $paid_at
 * @property array<string, mixed>|null $gateway_response
 * @property int $refunded_kobo
 * @property string|null $refund_reference
 * @property CarbonImmutable|null $refunded_at
 * @property-read Order $order
 */
#[Hidden(['gateway_response'])]
class Payment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount_kobo' => 'integer',
            'refunded_kobo' => 'integer',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'gateway_response' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
