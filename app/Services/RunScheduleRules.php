<?php

namespace App\Services;

use Carbon\CarbonInterface;

/**
 * Timing rules for a Run's pre-order window.
 *
 * BR-03: the window lasts at most 3 days.
 * BR-05 (amended for market-day secrecy): the window closes AT LEAST 2 days before market day;
 * the Admin chooses the exact gap so it is not a fixed, guessable offset.
 */
class RunScheduleRules
{
    /**
     * @return array<string, string> field => message
     */
    public static function violations(CarbonInterface $opensAt, CarbonInterface $closesAt, CarbonInterface $marketDay, CarbonInterface $deliveryStartsAt): array
    {
        $errors = [];
        $maxWindowHours = (int) config('josveggies.runs.max_window_hours');
        $minGapHours = (int) config('josveggies.runs.min_close_gap_hours');

        if ($closesAt->lte($opensAt)) {
            $errors['closes_at'] = 'The order window must close after it opens.';
        } elseif ($opensAt->diffInMinutes($closesAt) > $maxWindowHours * 60) {
            $errors['closes_at'] = sprintf('The order window can be open for at most %d days (BR-03).', intdiv($maxWindowHours, 24));
        }

        if ($closesAt->gt($marketDay->copy()->startOfDay()->subHours($minGapHours))) {
            $errors['market_day'] = sprintf('Orders must close at least %d days before market day (BR-05).', intdiv($minGapHours, 24));
        }

        if ($deliveryStartsAt->lt($marketDay->copy()->startOfDay())) {
            $errors['delivery_starts_at'] = 'Delivery cannot start before market day.';
        }

        return $errors;
    }
}
