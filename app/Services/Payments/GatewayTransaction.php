<?php

namespace App\Services\Payments;

final readonly class GatewayTransaction
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $reference,
        public bool $successful,
        public int $amountKobo,
        public string $currency,
        public ?string $channel = null,
        public array $raw = [],
    ) {}
}
