<?php

namespace Tests\Feature\Payments;

use App\Modules\Payments\Services\PaymobVodafoneCashCallbackGuard;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymobVodafoneCashCallbackGuardTest extends TestCase
{
    public function test_callback_is_rejected_until_exact_wallet_subtype_is_configured(): void
    {
        config(['paymob.expected_wallet_sub_type' => '']);
        $this->expectException(ValidationException::class);
        app(PaymobVodafoneCashCallbackGuard::class)->assertExpectedWallet([
            'obj' => ['id' => 1, 'source_data' => ['type' => 'wallet', 'sub_type' => 'Vodafone Cash']],
        ]);
    }

    public function test_callback_requires_the_expected_vodafone_wallet_subtype(): void
    {
        config(['paymob.expected_wallet_sub_type' => 'Vodafone Cash']);
        $guard = app(PaymobVodafoneCashCallbackGuard::class);

        $guard->assertExpectedWallet([
            'obj' => ['id' => 1, 'source_data' => ['type' => 'wallet', 'sub_type' => 'vodafone cash']],
        ]);

        $this->expectException(ValidationException::class);
        $guard->assertExpectedWallet([
            'obj' => ['id' => 2, 'source_data' => ['type' => 'wallet', 'sub_type' => 'Orange Cash']],
        ]);
    }

    public function test_matching_subtype_is_rejected_when_source_type_is_not_wallet(): void
    {
        config(['paymob.expected_wallet_sub_type' => 'Vodafone Cash']);
        $this->expectException(ValidationException::class);
        app(PaymobVodafoneCashCallbackGuard::class)->assertExpectedWallet([
            'obj' => ['id' => 3, 'source_data' => ['type' => 'card', 'sub_type' => 'Vodafone Cash']],
        ]);
    }
}
