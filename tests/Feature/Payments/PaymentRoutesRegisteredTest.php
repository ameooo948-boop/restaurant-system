<?php

namespace Tests\Feature\Payments;

use Tests\TestCase;

class PaymentRoutesRegisteredTest extends TestCase
{
    public function test_paymob_webhook_and_guest_payment_retry_routes_survive_application_refresh(): void
    {
        $this->refreshApplication();
        $this->assertSame('webhooks.paymob.transactions', app('router')->getRoutes()->getByName('webhooks.paymob.transactions')?->getName());
        $this->assertSame('store.payments.vodafone_cash.retry', app('router')->getRoutes()->getByName('store.payments.vodafone_cash.retry')?->getName());
    }
}
