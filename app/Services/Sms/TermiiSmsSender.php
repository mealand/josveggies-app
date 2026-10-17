<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Termii (Nigerian SMS provider). The "dnd" channel delivers transactional messages to
 * numbers on the Do-Not-Disturb list and requires a registered sender ID.
 */
class TermiiSmsSender implements SmsSender
{
    public function __construct(
        private string $apiKey,
        private string $senderId,
        private string $channel,
        private string $baseUrl,
    ) {}

    public function send(string $to, string $message): void
    {
        $response = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->post('/api/sms/send', [
                'api_key' => $this->apiKey,
                'to' => $to,
                'from' => $this->senderId,
                'sms' => $message,
                'type' => 'plain',
                'channel' => $this->channel,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Termii send failed: '.$response->json('message', (string) $response->status()));
        }
    }
}
