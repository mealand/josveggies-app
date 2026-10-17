<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-editable settings (Maintainability NFR). Values are JSON and cached.
 */
class Setting extends Model
{
    /**
     * Known settings with their defaults.
     *
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'hold_minutes' => 10,
        'morning_reminder_time' => '09:00',
        'final_reminder_time' => '11:00',
        'pickup_deadline_time' => '12:00',
        'default_slot_capacity' => 30,
        'announcement' => '',
    ];

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', fn (): array => static::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? self::DEFAULTS[$key] ?? null;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }
}
