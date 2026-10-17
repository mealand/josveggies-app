<?php

namespace App\Services\Sms;

interface SmsSender
{
    /**
     * Send a plain-text SMS. $to is an international number without "+", e.g. 2348031234567.
     */
    public function send(string $to, string $message): void;
}
