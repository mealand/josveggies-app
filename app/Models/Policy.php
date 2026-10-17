<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Versioned policy text. Each edit creates a new version; orders record the versions accepted (BR-10).
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $body
 * @property int $version
 */
class Policy extends Model
{
    /**
     * @var array<string, string>
     */
    public const SLUGS = [
        'forfeiture' => 'Pickup & forfeiture policy',
        'substitution' => 'Substitution policy',
        'refunds' => 'Refunds & credit policy',
        'privacy' => 'Privacy policy',
        'terms' => 'Terms & conditions',
    ];

    /**
     * Policies a customer must accept at checkout.
     *
     * @var list<string>
     */
    public const CHECKOUT_REQUIRED = ['forfeiture', 'substitution'];

    protected $fillable = ['slug', 'title', 'body', 'version', 'updated_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['version' => 'integer'];
    }

    public static function current(string $slug): ?self
    {
        return static::query()->where('slug', $slug)->orderByDesc('version')->first();
    }

    /**
     * @return array<string, int>
     */
    public static function currentVersions(): array
    {
        $versions = [];

        foreach (self::CHECKOUT_REQUIRED as $slug) {
            $versions[$slug] = self::current($slug)->version ?? 0;
        }

        return $versions;
    }
}
