<?php

namespace App\Support;

/**
 * Money is stored as integer kobo (1 naira = 100 kobo) and only formatted for display.
 */
final class Money
{
    public static function format(int $kobo): string
    {
        $naira = intdiv(abs($kobo), 100);
        $remainder = abs($kobo) % 100;
        $sign = $kobo < 0 ? '−' : '';

        return $sign.'₦'.number_format($naira).($remainder > 0 ? '.'.str_pad((string) $remainder, 2, '0', STR_PAD_LEFT) : '');
    }

    /**
     * Convert a naira amount typed by staff (e.g. "12,500.50") into kobo.
     */
    public static function toKobo(string|int|float $naira): int
    {
        $clean = str_replace([',', '₦', ' '], '', (string) $naira);

        return (int) round(((float) $clean) * 100);
    }

    public static function toNaira(int $kobo): string
    {
        $value = number_format($kobo / 100, 2, '.', '');

        return str_ends_with($value, '.00') ? substr($value, 0, -3) : $value;
    }
}
