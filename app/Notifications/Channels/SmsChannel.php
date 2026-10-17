<?php

namespace App\Notifications\Channels;

use App\Services\Sms\SmsSender;
use App\Support\Phone;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(private SmsSender $sender) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $to = Phone::normalize($notifiable->routeNotificationFor('sms', $notification));

        if ($to === null || ! method_exists($notification, 'toSms')) {
            return;
        }

        $this->sender->send($to, $notification->toSms($notifiable));
    }
}
