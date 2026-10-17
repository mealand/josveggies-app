<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduler
|--------------------------------------------------------------------------
|
| On shared cPanel hosting a single cron entry drives everything:
|   * * * * * /usr/local/bin/php /home/USER/josveggies/artisan schedule:run >> /dev/null 2>&1
|
*/

Schedule::command('josveggies:tick')
    ->everyMinute()
    ->withoutOverlapping(5);

// Process queued notifications without a long-running worker.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3 --backoff=30')
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command('queue:prune-failed --hours=720')->daily();

Schedule::command('backup:clean')->dailyAt('01:30');
Schedule::command('backup:run')->dailyAt('02:00');

Schedule::command('activitylog:clean')->monthly();
