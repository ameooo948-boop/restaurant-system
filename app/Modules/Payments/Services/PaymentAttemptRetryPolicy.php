<?php

namespace App\Modules\Payments\Services;

use App\Modules\Orders\Models\Payment;

class PaymentAttemptRetryPolicy
{
    public function mayCreateNewAttempt(?Payment $latest): bool
    {
        if (! $latest || $latest->status !== 'failed') {
            return false;
        }

        return in_array(
            data_get($latest->metadata, 'gateway_state'),
            ['failed', 'initiation_rejected'],
            true,
        );
    }
}
