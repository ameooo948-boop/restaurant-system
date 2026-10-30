<?php

namespace App\Modules\Payments\DTOs;

use App\Modules\Payments\Enums\PaymentStatus;

final readonly class VerifiedPaymentNotification
{
    public function __construct(
        public string $provider,
        public string $merchantReference,
        public string $providerTransactionId,
        public int $amountMinor,
        public string $currency,
        public PaymentStatus $status,
        public string $eventId,
    ) {
    }
}
