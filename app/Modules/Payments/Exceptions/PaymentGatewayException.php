<?php

namespace App\Modules\Payments\Exceptions;

use RuntimeException;

class PaymentGatewayException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $outcomeUnknown = false)
    {
        parent::__construct($message);
    }
}
