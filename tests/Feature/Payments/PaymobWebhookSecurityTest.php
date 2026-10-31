<?php

namespace Tests\Feature\Payments;

use Tests\TestCase;

class PaymobWebhookSecurityTest extends TestCase
{
    public function test_webhook_rejects_missing_hmac_before_processing_a_transaction(): void
    {
        config(['paymob.hmac_secret' => 'test-webhook-secret']);

        $this->postJson('/webhooks/paymob/transactions', [
            'type' => 'TRANSACTION',
            'obj' => ['id' => 123, 'order' => ['id' => 456]],
        ])->assertUnauthorized();
    }
}
