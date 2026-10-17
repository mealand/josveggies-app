<?php

namespace App\Models;

use App\Enums\Fulfilment;
use App\Enums\OrderState;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Orders are created only by CheckoutService; attributes are set explicitly, never mass-assigned.
 *
 * @property int $id
 * @property string $ulid
 * @property string $number
 * @property int $run_id
 * @property int $user_id
 * @property OrderState $state
 * @property Fulfilment $fulfilment
 * @property int|null $dispatch_zone_id
 * @property string|null $delivery_address
 * @property string $contact_phone
 * @property int $slots
 * @property int $subtotal_kobo
 * @property int $dispatch_fee_kobo
 * @property int $credit_applied_kobo
 * @property int $total_kobo
 * @property array<string, int> $accepted_policies
 * @property CarbonImmutable $policies_accepted_at
 * @property CarbonImmutable|null $hold_expires_at
 * @property CarbonImmutable|null $confirmed_at
 * @property string|null $pickup_code_hash
 * @property string|null $pickup_code
 * @property CarbonImmutable|null $collected_at
 * @property int|null $collected_by
 * @property CarbonImmutable|null $forfeited_at
 * @property CarbonImmutable|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property-read Run $run
 * @property-read User $user
 * @property-read DispatchZone|null $dispatchZone
 */
#[Hidden(['pickup_code', 'pickup_code_hash', 'delivery_address', 'contact_phone'])]
class Order extends Model
{
    use HasUlids;

    /**
     * Unambiguous characters for human-typed codes (no 0/O, 1/I/L, 2/Z, 5/S, 8/B).
     */
    public const CODE_ALPHABET = 'ACDEFGHJKMNPQRTUVWXY34679';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => OrderState::class,
            'fulfilment' => Fulfilment::class,
            'delivery_address' => 'encrypted',
            'contact_phone' => 'encrypted',
            'pickup_code' => 'encrypted',
            'slots' => 'integer',
            'subtotal_kobo' => 'integer',
            'dispatch_fee_kobo' => 'integer',
            'credit_applied_kobo' => 'integer',
            'total_kobo' => 'integer',
            'accepted_policies' => 'array',
            'policies_accepted_at' => 'datetime',
            'hold_expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'collected_at' => 'datetime',
            'forfeited_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * The ULID column is the public identifier; the numeric id never leaves the server.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public static function randomCode(int $length): string
    {
        $alphabet = self::CODE_ALPHABET;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }

    public static function hashPickupCode(string $code): string
    {
        return hash_hmac('sha256', Str::upper(trim($code)), (string) config('app.key'));
    }

    /**
     * Assign a fresh pickup code. Returns the plain code.
     */
    public function assignPickupCode(): string
    {
        $code = self::randomCode(6);
        $this->pickup_code = $code;
        $this->pickup_code_hash = self::hashPickupCode($code);

        return $code;
    }

    public function totalPaidKobo(): int
    {
        return $this->subtotal_kobo + $this->dispatch_fee_kobo;
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<DispatchZone, $this>
     */
    public function dispatchZone(): BelongsTo
    {
        return $this->belongsTo(DispatchZone::class);
    }

    /**
     * @return HasMany<OrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /**
     * @return HasMany<Substitution, $this>
     */
    public function substitutions(): HasMany
    {
        return $this->hasMany(Substitution::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * @return HasMany<Handover, $this>
     */
    public function handovers(): HasMany
    {
        return $this->hasMany(Handover::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
