<?php

namespace App\Modules\Payments\Services;

class PaymobHmacVerifier
{
    /** Ordered exactly as documented for Paymob Transaction Processed callbacks. */
    private const FIELDS = [
        'amount_cents',
        'created_at',
        'currency',
        'error_occured',
        'has_parent_transaction',
        'obj.id',
        'integration_id',
        'is_3d_secure',
        'is_auth',
        'is_capture',
        'is_refunded',
        'is_standalone_payment',
        'is_voided',
        'order.id',
        'owner',
        'pending',
        'source_data.pan',
        'source_data.sub_type',
        'source_data.type',
        'success',
    ];

    public function verify(array $payload, ?string $providedHmac, ?string $secret = null): bool
    {
        if (! is_string($providedHmac) || trim($providedHmac) === '') {
            return false;
        }

        $secret ??= (string) config('paymob.hmac_secret', '');
        if ($secret === '') {
            return false;
        }

        $expected = hash_hmac('sha512', $this->concatenatedValues($payload), $secret);

        return hash_equals(strtolower($expected), strtolower(trim($providedHmac)));
    }

    public function concatenatedValues(array $payload): string
    {
        $transaction = isset($payload['obj']) && is_array($payload['obj'])
            ? $payload['obj']
            : $payload;

        $values = [];
        foreach (self::FIELDS as $path) {
            $lookup = $path === 'obj.id' ? 'id' : $path;
            $value = data_get($transaction, $lookup);

            if (is_bool($value)) {
                $values[] = $value ? 'true' : 'false';
            } elseif ($value === null || is_array($value) || is_object($value)) {
                $values[] = '';
            } else {
                $values[] = (string) $value;
            }
        }

        return implode('', $values);
    }
}
