<?php

namespace Tests\Unit\Payments;

use App\Modules\Payments\Services\EgyptianMobilePhoneNormalizer;
use PHPUnit\Framework\TestCase;

class EgyptianMobilePhoneNormalizerTest extends TestCase
{
    public function test_normalizes_valid_egyptian_mobile_numbers_to_e164(): void
    {
        $normalizer = new EgyptianMobilePhoneNormalizer();
        $this->assertSame('+201012345678', $normalizer->normalize('01012345678'));
        $this->assertSame('+201112345678', $normalizer->normalize('+20 111 234 5678'));
        $this->assertSame('+201212345678', $normalizer->normalize('00201212345678'));
        $this->assertSame('+201512345678', $normalizer->normalize('201512345678'));
    }

    public function test_rejects_non_egyptian_or_invalid_mobile_numbers(): void
    {
        $normalizer = new EgyptianMobilePhoneNormalizer();
        $this->assertNull($normalizer->normalize('0101234567'));
        $this->assertNull($normalizer->normalize('02012345678'));
        $this->assertNull($normalizer->normalize('011123456789'));
        $this->assertNull($normalizer->normalize(''));
    }
}
