<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Restaurant Defaults
    |--------------------------------------------------------------------------
    |
    | These are application defaults. Branch-specific values belong in the
    | branch tables and must not be inferred from the current browser locale.
    |
    */
    'currency' => env('RESTAURANT_CURRENCY', 'EGP'),
    'timezone' => env('RESTAURANT_TIMEZONE', 'Africa/Cairo'),
    'locale' => env('RESTAURANT_LOCALE', 'ar'),
    'fallback_locale' => env('RESTAURANT_FALLBACK_LOCALE', 'en'),
    'supported_locales' => ['ar', 'en'],
    'allow_guest_checkout' => (bool) env('RESTAURANT_ALLOW_GUEST_CHECKOUT', true),

    'order_types' => [
        'delivery',
        'pickup',
        'dine_in',
    ],
];
