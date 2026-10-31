<?php

namespace App\Modules\Orders\DTOs;

final readonly class CheckoutData
{
    /** @param array<string, string|null>|null $deliveryAddress */
    public function __construct(
        public string $idempotencyKey,
        public string $orderType,
        public string $paymentMethod,
        public string $customerName,
        public string $customerPhone,
        public ?string $customerEmail,
        public ?array $deliveryAddress,
        public ?string $tableLabel,
        public ?string $customerNotes,
    ) {
    }
}
