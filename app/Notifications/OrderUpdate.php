<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Every customer-facing order notification (Webapp Concept §10). One class, keyed by event,
 * so wording stays consistent across email and SMS.
 *
 * Customers never see the market day (BR-19): messages only mention delivery and pickup times.
 */
class OrderUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Events important enough to also cost an SMS. Everything else is email-only.
     *
     * @var list<string>
     */
    private const SMS_EVENTS = [
        'confirmed', 'arrived', 'reminder_morning', 'reminder_final', 'out_for_delivery',
        'delivery_failed', 'forfeited', 'cancelled', 'refunded_late', 'substitution',
    ];

    public int $tries = 3;

    public function __construct(public Order $order, public string $event) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        $channels = ['mail'];

        if (in_array($this->smsEvent(), self::SMS_EVENTS, true)) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    public function toMail(User $notifiable): MailMessage
    {
        [$subject, $lines] = $this->content();

        $mail = (new MailMessage)
            ->subject($subject.' · '.$this->order->number)
            ->greeting('Hi '.str($notifiable->name)->before(' ').',');

        foreach ($lines as $line) {
            $mail->line($line);
        }

        return $mail
            ->action('View your order', route('account.orders.show', $this->order))
            ->salutation('Fresh regards, the JosVeggies team');
    }

    public function toSms(User $notifiable): string
    {
        [$subject, $lines] = $this->content();

        return 'JosVeggies: '.$subject.'. '.($lines[0] ?? '').' Order '.$this->order->number.'.';
    }

    /**
     * When packing reveals substitutions, the "packed" update becomes worth an SMS (BR-14).
     */
    private function smsEvent(): string
    {
        if ($this->event === 'packed' && $this->order->substitutions()->exists()) {
            return 'substitution';
        }

        return $this->event;
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private function content(): array
    {
        $order = $this->order->loadMissing(['run.hub', 'substitutions.originalItem', 'substitutions.substituteItem', 'lines']);
        $run = $order->run;
        $deadline = $run->pickup_ends_at?->format('l j M, g:ia');
        $delivery = $run->delivery_starts_at->format('l j M');
        $hub = $run->hub->name.', '.$run->hub->address;

        return match ($this->event) {
            'confirmed' => ['Order confirmed', [
                "We've received your payment of ".Money::format($order->total_kobo).'. Your veggies arrive on '.$delivery.'.',
                $order->fulfilment->value === 'pickup'
                    ? "You'll collect from our hub: {$hub}. We'll message your pickup code when your order is ready."
                    : "A rider will deliver to your address after arrival. We'll keep you posted.",
                'Remember: orders not collected by the pickup deadline are forfeited, as agreed at checkout.',
            ]],
            'purchased' => ['Your vegetables have been bought', ['Your vegetables have been bought fresh and are being sorted.']],
            'packed', 'substitution' => ['Your box is packed', $this->packedLines()],
            'in_transit' => ['On the way to Abuja', ['Your order is on its way to Abuja.']],
            'arrived' => $order->fulfilment->value === 'pickup'
                ? ['Ready for pickup', [
                    "Your order is ready. Pickup code: {$order->pickup_code}. Collect by {$deadline}.",
                    "Hub: {$hub}.",
                    'Show the code or the QR in your account at the hub.',
                ]]
                : ['Arrived in Abuja', ["Your order has arrived in Abuja and a rider will be assigned shortly. Pickup code: {$order->pickup_code}."]],
            'reminder_morning' => ['Pickup reminder', ["Your order is waiting at the hub. Collect by {$deadline} with code {$order->pickup_code}.", "Hub: {$hub}."]],
            'reminder_final' => ['One hour left', ["Your order will be forfeited at {$deadline} if not collected. Code: {$order->pickup_code}.", "Hub: {$hub}."]],
            'out_for_delivery' => ['Rider on the way', ['A rider has collected your order and is on the way to you.']],
            'delivery_failed' => ['Delivery attempt failed', ["The rider could not deliver your order, so it is back at the hub. Please collect by {$deadline} with code {$order->pickup_code}."]],
            'delivered' => ['Delivered', ['Your order has been delivered. Enjoy your veggies!']],
            'collected' => ['Collected', ['Thanks for collecting your order. Enjoy your veggies!']],
            'forfeited' => ['Order forfeited', ['Your order was not collected before the deadline and has been forfeited, as agreed in our pickup policy.']],
            'cancelled' => ['Order cancelled', ['We are sorry — this delivery Run was cancelled. Your payment is being refunded and any credit used has been returned to your account.']],
            'refunded_late' => ['Payment refunded', ['Your payment arrived after your slot was released and the Run is now full, so we have refunded it in full.']],
            default => ['Order update', [$order->state->customerMessage($order->fulfilment)]],
        };
    }

    /**
     * @return list<string>
     */
    private function packedLines(): array
    {
        $lines = ['Your box is packed and labelled.'];

        foreach ($this->order->substitutions as $substitution) {
            $lines[] = $substitution->substituteItem !== null
                ? "{$substitution->originalItem->name} was unavailable, so we packed {$substitution->substituteItem->name} (equal or greater value)."
                : "{$substitution->originalItem->name} was unavailable; ".Money::format($substitution->credit_kobo).' has been added to your JosVeggies credit.';
        }

        foreach ($this->order->lines->whereNotNull('unavailable_at') as $line) {
            $lines[] = "{$line->name} was unavailable; ".Money::format($line->line_total_kobo).' has been added to your JosVeggies credit.';
        }

        $this->order->substitutions()->whereNull('notified_at')->update(['notified_at' => now()]);

        return $lines;
    }
}
