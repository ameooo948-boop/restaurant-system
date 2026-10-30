<?php

namespace App\Modules\Payments\DTOs;

use DateTimeImmutable;

final readonly class PaymentCheckoutSession
{
    public function __construct(
        public string $redirectUrl,
        public string $providerReference,
        public ?DateTimeImmutable $expiresAt = null,
    ) {
    }
}
