<?php

namespace App\Modules\Payments\Contracts;

use App\Modules\Payments\DTOs\PaymentCheckoutRequest;
use App\Modules\Payments\DTOs\PaymentCheckoutSession;
use App\Modules\Payments\DTOs\VerifiedPaymentNotification;

interface PaymentGateway
{
    public function createCheckout(PaymentCheckoutRequest $request): PaymentCheckoutSession;

    /**
     * Verify the provider signature before returning a normalized notification.
     * Implementations must throw an exception when the signature is invalid.
     *
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    public function verifyAndParseWebhook(array $payload, array $headers): VerifiedPaymentNotification;
}
