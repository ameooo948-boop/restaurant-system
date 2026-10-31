<?php

namespace Tests\Unit\Payments;

use App\Modules\Payments\Services\PaymobUnifiedCheckoutUrlBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PaymobUnifiedCheckoutUrlBuilderTest extends TestCase
{
    public function test_builds_official_regional_unified_checkout_url(): void
    {
        $url = (new PaymobUnifiedCheckoutUrlBuilder())->build(
            'https://accept.paymob.com/',
            'pk_test_example',
            'csk_test_example',
        );

        $this->assertSame(
            'https://accept.paymob.com/unifiedcheckout/?publicKey=pk_test_example&clientSecret=csk_test_example',
            $url,
        );
    }

    public function test_rejects_non_https_or_missing_checkout_credentials(): void
    {
        $builder = new PaymobUnifiedCheckoutUrlBuilder();
        $this->expectException(InvalidArgumentException::class);
        $builder->build('http://localhost', 'pk_test', 'client_secret');
    }
}
