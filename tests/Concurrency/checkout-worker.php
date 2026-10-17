<?php

/**
 * Worker for the BR-07 concurrency test: waits for a shared start time, then tries to
 * book one box. Prints "booked" or "rejected".
 *
 * Usage: php checkout-worker.php {runId} {userId} {boxId} {startAtMicrotime}
 */

use App\Enums\Fulfilment;
use App\Exceptions\BusinessRuleException;
use App\Models\Run;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Notification;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
Notification::fake();

[, $runId, $userId, $boxId, $startAt] = $argv;

// Barrier: every worker fires at the same instant.
while (microtime(true) < (float) $startAt) {
    usleep(500);
}

try {
    app(CheckoutService::class)->placeOrder(
        User::query()->findOrFail($userId),
        Run::query()->findOrFail($runId),
        [(int) $boxId => 1],
        [],
        Fulfilment::Pickup,
        null,
        null,
        '2348031234567',
        false,
        ['forfeiture', 'substitution'],
    );
    echo 'booked';
} catch (BusinessRuleException) {
    echo 'rejected';
}
