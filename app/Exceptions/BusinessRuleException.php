<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A business rule prevented the action. The message is safe to show to the user.
 */
class BusinessRuleException extends RuntimeException
{
    public function __construct(string $message, public readonly ?string $rule = null)
    {
        parent::__construct($message);
    }
}
