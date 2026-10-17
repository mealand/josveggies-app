<?php

namespace App\Support;

/**
 * Nigerian phone number helpers.
 */
final class Phone
{
    /**
     * Normalise to international format without "+", e.g. 08031234567 → 2348031234567.
     * Returns null when the number is not a plausible Nigerian mobile number.
     */
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (str_starts_with($digits, '234')) {
            $digits = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^[789][01]\d{8}$/', $digits) === 1 ? '234'.$digits : null;
    }

    /**
     * Mask for logs and staff screens, e.g. 234803****567.
     */
    public static function mask(?string $phone): string
    {
        $phone = (string) $phone;

        return strlen($phone) > 7 ? substr($phone, 0, 6).str_repeat('*', strlen($phone) - 9).substr($phone, -3) : '***';
    }
}
