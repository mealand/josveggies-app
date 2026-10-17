<?php

namespace App\Notifications;

use App\Models\Run;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to Admin and Jos Ops only (both may see the market day, BR-19).
 */
class RunClosedStaffAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Run $run) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return $notifiable->canSeeMarketDay() ? ['mail'] : [];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $run = $this->run->fresh() ?? $this->run;

        return (new MailMessage)
            ->subject("Run {$run->code} closed — {$run->slots_confirmed} boxes")
            ->line("The pre-order window for Run {$run->code} has closed with {$run->slots_confirmed} of {$run->slot_capacity} slots confirmed.")
            ->line('Market day: '.$run->market_day->format('l j F Y').'. The procurement list is ready.')
            ->action('Open procurement list', route('console.jos.procurement', $run));
    }
}
