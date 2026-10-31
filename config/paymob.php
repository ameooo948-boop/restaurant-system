<?php

return [
    'enabled' => (bool) env('PAYMOB_ENABLED', false),
    'base_url' => rtrim((string) env('PAYMOB_BASE_URL', 'https://accept.paymob.com'), '/'),
    'intention_path' => '/v1/intention/',
    'unified_checkout_path' => '/unifiedcheckout/',
    'notification_url' => env('PAYMOB_NOTIFICATION_URL'),
    'secret_key' => env('PAYMOB_SECRET_KEY'),
    'public_key' => env('PAYMOB_PUBLIC_KEY'),
    'wallet_integration_id' => (int) env('PAYMOB_VODAFONE_CASH_INTEGRATION_ID', 0),
    // Set this to the exact obj.source_data.sub_type captured from a verified Test-mode callback.
    'expected_wallet_sub_type' => trim((string) env('PAYMOB_VODAFONE_CASH_SOURCE_SUB_TYPE', '')),
    'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
    'intention_expiration_seconds' => (int) env('PAYMOB_INTENTION_EXPIRATION_SECONDS', 1800),
    'http_timeout_seconds' => (int) env('PAYMOB_HTTP_TIMEOUT_SECONDS', 20),
];
