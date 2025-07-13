<?php

declare(strict_types=1);

namespace LHcze\BCP47\Tests\Normalizer;

use LHcze\BCP47\Normalizer\BCP47Normalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BCP47NormalizerTest extends TestCase
{
    private BCP47Normalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new BCP47Normalizer();
    }

    #[DataProvider('provideLocalesForNormalization')]
    public function testNormalize(string $input, string $expected): void
    {
        $result = $this->normalizer->normalize($input);
        $this->assertSame($expected, $result);
    }

    public static function provideLocalesForNormalization(): array
    {
        return [
            'simple locale' => ['en-us', 'en-US'],
            'already normalized' => ['en-US', 'en-US'],
            'with underscore' => ['en_us', 'en-US'],
            'uppercase' => ['EN-US', 'en-US'],
            'language only wrong casing' => ['EN', 'en'],
            'three-part locale wrong  casing' => ['zH-haNS-cn', 'zh-Hans-CN'],
            'wrong grandfathered tag' => ['i-klingon', 'i-klingon'],
            'mixed case' => ['eN-uS', 'en-US'],
        ];
    }
}
