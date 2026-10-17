<?php

namespace App\Console\Commands;

use App\Services\RunClock;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('josveggies:tick')]
#[Description('Apply time-based Run rules: open/close windows, expire holds, send reminders, forfeit uncollected orders')]
class RunTick extends Command
{
    public function handle(RunClock $clock): int
    {
        $result = $clock->tick();

        if (array_sum($result) > 0) {
            $this->info(collect($result)->map(fn (int $count, string $step): string => "{$step}: {$count}")->implode(', '));
        }

        return self::SUCCESS;
    }
}
