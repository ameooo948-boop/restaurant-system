<?php

namespace App\Modules\Payments\Services;

class EgyptianMobilePhoneNormalizer
{
    public function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\\D+/', '', trim((string) $phone)) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '20')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (! preg_match('/^1[0125][0-9]{8}$/', $digits)) {
            return null;
        }

        return '+20'.$digits;
    }
}
