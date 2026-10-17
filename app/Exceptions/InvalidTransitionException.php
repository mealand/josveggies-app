<?php

namespace App\Exceptions;

use BackedEnum;

class InvalidTransitionException extends BusinessRuleException
{
    public static function between(string $subject, BackedEnum $from, BackedEnum $to): self
    {
        return new self(sprintf('%s cannot move from "%s" to "%s".', $subject, $from->value, $to->value));
    }
}
