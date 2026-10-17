<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    */

    'brand' => [
        'name' => 'JosVeggies',
        'tagline' => 'Fresh from Jos. Delivered to Abuja.',
        'powered_by' => 'SPREZZ INTEGRATED LIMITED',
        'support_whatsapp' => env('SUPPORT_WHATSAPP_NUMBER', '2348000000000'),
        'support_email' => env('SUPPORT_EMAIL', 'hello@josveggies.ng'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Run rules (defaults; most can be overridden by Admin in Settings)
    |--------------------------------------------------------------------------
    |
    | BR-03: a pre-order window lasts at most `max_window_hours`.
    | BR-05: the window closes at least `min_close_gap_hours` before market day.
    | BR-06: a slot is held for `hold_minutes` during checkout.
    | BR-15: the pickup window ends at `pickup_deadline_time` the day after arrival.
    |
    */

    'runs' => [
        'max_window_hours' => 72,
        'min_close_gap_hours' => 48,
        'hold_minutes' => 10,
        'pickup_deadline_time' => '12:00',
    ],

    'substitutions' => [
        'max_items_per_box' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrations
    |--------------------------------------------------------------------------
    */

    'payments' => [
        'gateway' => env('PAYMENT_GATEWAY', 'fake'),
        'currency' => 'NGN',
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
    ],

    'staff' => [
        'idle_minutes' => (int) env('STAFF_SESSION_IDLE_MINUTES', 30),
    ],

];
