<?php

namespace App\Modules\Payments\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymobVodafoneCashCallbackGuard
{
    public function assertExpectedWallet(array $payload): void
    {
        $sourceType = strtolower(trim((string) data_get($payload, 'obj.source_data.type', '')));
        $actual = trim((string) data_get($payload, 'obj.source_data.sub_type', ''));
        $expected = trim((string) config('paymob.expected_wallet_sub_type', ''));
        $context = [
            'transaction_id' => (string) data_get($payload, 'obj.id', ''),
            'integration_id' => (int) data_get($payload, 'obj.integration_id', 0),
            'source_data_type' => $sourceType,
            'source_data_sub_type' => $actual,
        ];

        if ($sourceType !== 'wallet' || $expected === '' || $actual === '' || strtolower($actual) !== strtolower($expected)) {
            Log::warning('Paymob wallet callback rejected: expected Vodafone Cash subtype was not confirmed.', $context);
            throw ValidationException::withMessages([
                'webhook' => 'لم يتم تأكيد نوع المحفظة الوارد من بوابة الدفع. لم يتم تحديث الطلب. راجع سجل callback التجريبي واضبط PAYMOB_VODAFONE_CASH_SOURCE_SUB_TYPE بالقيمة الدقيقة.',
            ]);
        }
    }
}
