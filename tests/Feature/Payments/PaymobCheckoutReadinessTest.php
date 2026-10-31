<?php

namespace Tests\Feature\Payments;

use App\Modules\Payments\Services\PaymobCheckoutReadiness;
use Tests\TestCase;

class PaymobCheckoutReadinessTest extends TestCase
{
    private function configureReadyTestGateway(): void
    {
        config([
            'app.env' => 'testing',
            'app.url' => 'https://restaurant.example.com',
            'paymob.enabled' => true,
            'paymob.base_url' => 'https://accept.paymob.com',
            'paymob.secret_key' => 'test_secret',
            'paymob.public_key' => 'test_public',
            'paymob.hmac_secret' => 'test_hmac',
            'paymob.wallet_integration_id' => 123,
            'paymob.notification_url' => 'https://hooks.example.com/webhooks/paymob/transactions',
            'paymob.expected_wallet_sub_type' => '',
        ]);
    }

    public function test_gateway_is_hidden_when_disabled_or_notification_url_is_not_public_https(): void
    {
        config(['paymob.enabled' => false]);
        $this->assertFalse(app(PaymobCheckoutReadiness::class)->isReady());

        $this->configureReadyTestGateway();
        config(['paymob.notification_url' => 'http://localhost/webhooks/paymob/transactions']);
        $this->assertFalse(app(PaymobCheckoutReadiness::class)->isReady());
    }

    public function test_gateway_is_ready_in_test_mode_with_public_https_callback(): void
    {
        $this->configureReadyTestGateway();
        $this->assertTrue(app(PaymobCheckoutReadiness::class)->isReady());
    }

    public function test_production_requires_the_observed_vodafone_wallet_subtype(): void
    {
        $this->configureReadyTestGateway();
        config(['app.env' => 'production', 'paymob.expected_wallet_sub_type' => '']);
        $this->assertFalse(app(PaymobCheckoutReadiness::class)->isReady());

        config(['paymob.expected_wallet_sub_type' => 'Vodafone Cash']);
        $this->assertTrue(app(PaymobCheckoutReadiness::class)->isReady());
    }
}
