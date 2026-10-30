<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Online Payment Configuration
    |--------------------------------------------------------------------------
    |
    | Vodafone Cash is the only online wallet method planned for the first
    | release. Paymob is the initial integration candidate, subject to the
    | restaurant obtaining merchant approval and valid credentials.
    |
    */
    'currency' => env('PAYMENT_CURRENCY', 'EGP'),
    'online_method' => env('PAYMENT_ONLINE_METHOD', 'vodafone_cash'),
    'gateway' => env('PAYMENT_GATEWAY', 'paymob'),

    'paymob' => [
        'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com'),
        'secret_key' => env('PAYMOB_SECRET_KEY'),
        'public_key' => env('PAYMOB_PUBLIC_KEY'),
        'wallet_integration_id' => env('PAYMOB_WALLET_INTEGRATION_ID'),
        'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
        'timeout_seconds' => (int) env('PAYMOB_TIMEOUT_SECONDS', 20),
    ],
];
