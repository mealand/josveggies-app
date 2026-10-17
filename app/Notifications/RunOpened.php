<?php

namespace App\Notifications;

use App\Models\Run;
use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells waitlisted customers that a new Run is open.
 */
class RunOpened extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Run $run) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail', SmsChannel::class];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A new JosVeggies Run is open')
            ->greeting('Good news!')
            ->line("Pre-orders are open for delivery on {$this->delivery()}. Only {$this->run->slotsRemaining()} slots are available and orders close {$this->closes()}.")
            ->action('Order now', route('home'))
            ->line('Slots go fast — first come, first served.');
    }

    public function toSms(User $notifiable): string
    {
        return "JosVeggies: a new Run is open! Delivery {$this->delivery()}, orders close {$this->closes()}. Order at ".route('home');
    }

    private function delivery(): string
    {
        return $this->run->delivery_starts_at->format('D j M');
    }

    private function closes(): string
    {
        return $this->run->closes_at->format('D j M, g:ia');
    }
}
