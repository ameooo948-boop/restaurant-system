<?php

namespace App\Modules\Payments\DTOs;

final readonly class PaymentCheckoutRequest
{
    /**
     * @param array{name: string, phone: string, email?: string|null} $customer
     */
    public function __construct(
        public string $merchantReference,
        public int $amountMinor,
        public string $currency,
        public array $customer,
        public string $returnUrl,
        public string $notificationUrl,
    ) {
        if ($this->amountMinor < 1) {
            throw new \InvalidArgumentException('Payment amount must be at least one minor currency unit.');
        }

        if (strlen($this->currency) !== 3) {
            throw new \InvalidArgumentException('Currency must be a three-letter ISO code.');
        }
    }
}
