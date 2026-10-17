<?php

namespace App\Models;

use App\Enums\Role;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Role $role
 * @property string $customer_type
 * @property string|null $phone
 * @property string|null $default_address
 * @property int|null $default_dispatch_zone_id
 * @property bool $is_active
 * @property string|null $two_factor_secret
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property CarbonImmutable|null $email_verified_at
 * @property CarbonImmutable|null $last_login_at
 * @property CarbonImmutable|null $anonymised_at
 */
#[Fillable(['name', 'email', 'password', 'phone', 'default_address', 'default_dispatch_zone_id'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'phone', 'default_address'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Defaults mirrored from the migration, so new instances behave correctly before a reload.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'customer',
        'customer_type' => 'household',
        'is_active' => true,
        'phone' => null,
        'default_address' => null,
        'default_dispatch_zone_id' => null,
        'last_login_at' => null,
        'anonymised_at' => null,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
        'two_factor_confirmed_at' => null,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'phone' => 'encrypted',
            'default_address' => 'encrypted',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'anonymised_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function canSeeMarketDay(): bool
    {
        return $this->role->canSeeMarketDay();
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * BR-20: the credit balance is the sum of the append-only ledger.
     */
    public function creditBalanceKobo(): int
    {
        return (int) $this->creditEntries()->sum('amount_kobo');
    }

    /**
     * Route SMS notifications to the saved phone number.
     */
    public function routeNotificationForSms(): ?string
    {
        return $this->phone;
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<CreditLedgerEntry, $this>
     */
    public function creditEntries(): HasMany
    {
        return $this->hasMany(CreditLedgerEntry::class);
    }

    /**
     * @return HasMany<WaitlistEntry, $this>
     */
    public function waitlistEntries(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class);
    }

    /**
     * @return BelongsTo<DispatchZone, $this>
     */
    public function defaultDispatchZone(): BelongsTo
    {
        return $this->belongsTo(DispatchZone::class, 'default_dispatch_zone_id');
    }
}
