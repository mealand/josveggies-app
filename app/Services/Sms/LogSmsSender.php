<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Local development driver: writes messages to storage/logs/sms.log instead of sending them.
 */
class LogSmsSender implements SmsSender
{
    public function send(string $to, string $message): void
    {
        Log::build(['driver' => 'single', 'path' => storage_path('logs/sms.log')])
            ->info("SMS to {$to}: {$message}");
    }
}
