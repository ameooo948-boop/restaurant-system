<?php

namespace Tests\Unit\Payments;

use App\Modules\Payments\Services\PaymobHmacVerifier;
use PHPUnit\Framework\TestCase;

class PaymobHmacVerifierTest extends TestCase
{
    public function test_transaction_processed_payload_is_concatenated_using_paymobs_documented_nested_obj_shape(): void
    {
        $payload = [
            'type' => 'TRANSACTION',
            'obj' => [
                'amount_cents' => 100000,
                'created_at' => '2024-06-13T11:33:44.592345',
                'currency' => 'EGP',
                'error_occured' => false,
                'has_parent_transaction' => false,
                'id' => 192036465,
                'integration_id' => 4097558,
                'is_3d_secure' => true,
                'is_auth' => false,
                'is_capture' => false,
                'is_refunded' => false,
                'is_standalone_payment' => true,
                'is_voided' => false,
                'order' => ['id' => 217503754],
                'owner' => 302852,
                'pending' => false,
                'source_data' => ['pan' => '2346', 'sub_type' => 'MasterCard', 'type' => 'card'],
                'success' => true,
            ],
        ];

        $expected = '1000002024-06-13T11:33:44.592345EGPfalsefalse1920364654097558truefalsefalsefalsetruefalse217503754302852false2346MasterCardcardtrue';
        $verifier = new PaymobHmacVerifier();

        $this->assertSame($expected, $verifier->concatenatedValues($payload));

        $signature = hash_hmac('sha512', $expected, 'unit-test-secret');
        $this->assertTrue($verifier->verify($payload, $signature, 'unit-test-secret'));
        $this->assertFalse($verifier->verify($payload, str_repeat('0', 128), 'unit-test-secret'));
        $this->assertFalse($verifier->verify($payload, $signature, 'wrong-secret'));
    }
}
