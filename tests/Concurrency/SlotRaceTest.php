<?php

use App\Models\Order;
use App\Models\User;
use Database\Seeders\PolicySeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

uses(TestCase::class, DatabaseMigrations::class);

test('BR-07: concurrent checkouts never oversell the last slot', function () {
    $this->seed(PolicySeeder::class);
    ['run' => $run, 'box' => $box] = openRun(capacity: 1);

    $workers = 6;
    $users = User::factory()->count($workers)->create();
    $startAt = microtime(true) + 3;
    $php = (new PhpExecutableFinder)->find();

    $processes = $users->map(function (User $user) use ($php, $run, $box, $startAt): Process {
        $process = new Process(
            [$php, __DIR__.'/checkout-worker.php', $run->id, $user->id, $box->id, (string) $startAt],
            base_path(),
            ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mariadb', 'DB_DATABASE' => 'josveggies_test', 'PAYMENT_GATEWAY' => 'fake', 'SMS_DRIVER' => 'log', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'CACHE_STORE' => 'array'],
        );
        $process->setTimeout(60);
        $process->start();

        return $process;
    });

    $results = $processes->map(function (Process $process): string {
        $process->wait();

        return trim($process->getOutput()) ?: 'error: '.$process->getErrorOutput();
    });

    expect($results->filter(fn ($r) => $r === 'booked'))->toHaveCount(1)
        ->and($results->filter(fn ($r) => $r === 'rejected'))->toHaveCount($workers - 1)
        ->and(Order::query()->where('run_id', $run->id)->count())->toBe(1)
        ->and($run->fresh()->slots_held)->toBe(1);
});
