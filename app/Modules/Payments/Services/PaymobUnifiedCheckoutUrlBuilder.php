<?php

namespace App\Modules\Payments\Services;

use InvalidArgumentException;

class PaymobUnifiedCheckoutUrlBuilder
{
    public function build(string $regionalBaseUrl, string $publicKey, string $clientSecret, string $path = '/unifiedcheckout/'): string
    {
        $parts = parse_url($regionalBaseUrl);
        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) {
            throw new InvalidArgumentException('Paymob regional base URL must be an HTTPS origin.');
        }
        if ($publicKey === '' || $clientSecret === '') {
            throw new InvalidArgumentException('Paymob Public Key and client secret are required.');
        }

        $path = '/'.trim($path, '/').'/';
        return rtrim($regionalBaseUrl, '/').$path.'?'.http_build_query([
            'publicKey' => $publicKey,
            'clientSecret' => $clientSecret,
        ]);
    }
}
