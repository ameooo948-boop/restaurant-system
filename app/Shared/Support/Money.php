<?php

namespace App\Shared\Support;

use InvalidArgumentException;

final class Money
{
    public static function toMinor(string|int|float $amount): int
    {
        $value = trim((string) $amount);
        if (! preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', $value, $matches)) {
            throw new InvalidArgumentException('Invalid monetary amount.');
        }

        $fraction = str_pad($matches[3] ?? '', 2, '0');
        $minor = ((int) $matches[2] * 100) + (int) $fraction;

        return ($matches[1] ?? '') === '-' ? -$minor : $minor;
    }

    public static function fromMinor(int $minor): string
    {
        $absolute = abs($minor);
        $value = intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);

        return $minor < 0 ? '-'.$value : $value;
    }
}
