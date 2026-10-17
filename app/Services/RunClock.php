<?php

namespace App\Services;

use App\Enums\OrderState;
use App\Enums\RunState;
use App\Models\Order;
use App\Models\Run;
use App\Models\Setting;
use App\Notifications\OrderUpdate;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Time-based Run rules, executed every minute by the scheduler (one cPanel cron line).
 * Each step is idempotent, so a missed or repeated minute never causes double effects.
 */
class RunClock
{
    public function __construct(
        private RunService $runs,
        private PaymentService $payments,
    ) {}

    /**
     * @return array<string, int>
     */
    public function tick(): array
    {
        return [
            'opened' => $this->openDueRuns(),
            'closed' => $this->closeDueRuns(),
            'holds_expired' => $this->payments->expireStaleHolds(),
            'reminders' => $this->sendPickupReminders(),
            'pickups_closed' => $this->closeDuePickupWindows(),
        ];
    }

    private function openDueRuns(): int
    {
        return $this->each(
            Run::query()->where('state', RunState::Scheduled)->where('opens_at', '<=', now())->where('closes_at', '>', now()),
            RunState::Open,
        );
    }

    /**
     * BR-03 / BR-05: the window closes at its scheduled time even if slots remain (BR-09).
     */
    private function closeDueRuns(): int
    {
        return $this->each(Run::query()->where('state', RunState::Open)->where('closes_at', '<=', now()), RunState::Closed);
    }

    /**
     * BR-15 / BR-16: at the pickup deadline the window closes and uncollected orders are forfeited.
     */
    private function closeDuePickupWindows(): int
    {
        return $this->each(Run::query()->where('state', RunState::Arrived)->where('pickup_ends_at', '<=', now()), RunState::PickupClosed);
    }

    /**
     * Morning reminder (~9am) and final warning (~11am) on the deadline day.
     */
    private function sendPickupReminders(): int
    {
        $sent = 0;

        $runs = Run::query()->where('state', RunState::Arrived)->where('pickup_ends_at', '>', now())->get();

        foreach ($runs as $run) {
            if (! $run->pickup_ends_at->isSameDay(now())) {
                continue;
            }

            foreach (['morning' => 'morning_reminder_time', 'final' => 'final_reminder_time'] as $kind => $settingKey) {
                $column = $kind.'_reminder_sent_at';
                $dueAt = now()->setTimeFromTimeString((string) Setting::get($settingKey));

                if ($run->{$column} !== null || now()->lt($dueAt)) {
                    continue;
                }

                // Claim the reminder atomically so overlapping cron runs never send it twice.
                $claimed = Run::query()->whereKey($run->id)->whereNull($column)->update([$column => now()]);
                if ($claimed === 0) {
                    continue;
                }

                Order::query()
                    ->where('run_id', $run->id)
                    ->where('state', OrderState::ReadyForPickup)
                    ->with('user', 'run')
                    ->each(function (Order $order) use ($kind, &$sent): void {
                        $order->user->notify(new OrderUpdate($order, 'reminder_'.$kind));
                        $sent++;
                    });
            }
        }

        return $sent;
    }

    /**
     * @param  Builder<Run>  $query
     */
    private function each($query, RunState $to): int
    {
        $count = 0;

        foreach ($query->get() as $run) {
            try {
                $this->runs->transition($run, $to);
                $count++;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $count;
    }
}
