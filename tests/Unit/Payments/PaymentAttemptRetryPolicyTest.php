<?php

namespace Tests\Unit\Payments;

use App\Modules\Orders\Models\Payment;
use App\Modules\Payments\Services\PaymentAttemptRetryPolicy;
use PHPUnit\Framework\TestCase;

class PaymentAttemptRetryPolicyTest extends TestCase
{
    public function test_only_a_definitively_failed_attempt_can_be_retried(): void
    {
        $policy = new PaymentAttemptRetryPolicy();
        $failed = new Payment(['status' => 'failed', 'metadata' => ['gateway_state' => 'failed']]);
        $rejected = new Payment(['status' => 'failed', 'metadata' => ['gateway_state' => 'initiation_rejected']]);
        $pendingExpired = new Payment(['status' => 'pending', 'metadata' => ['gateway_state' => 'intention_created']]);
        $unknown = new Payment(['status' => 'pending', 'metadata' => ['gateway_state' => 'initiation_unknown']]);
        $succeeded = new Payment(['status' => 'succeeded', 'metadata' => ['gateway_state' => 'paid']]);

        $this->assertTrue($policy->mayCreateNewAttempt($failed));
        $this->assertTrue($policy->mayCreateNewAttempt($rejected));
        $this->assertFalse($policy->mayCreateNewAttempt($pendingExpired));
        $this->assertFalse($policy->mayCreateNewAttempt($unknown));
        $this->assertFalse($policy->mayCreateNewAttempt($succeeded));
        $this->assertFalse($policy->mayCreateNewAttempt(null));
    }
}
