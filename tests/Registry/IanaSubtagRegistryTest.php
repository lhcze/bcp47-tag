<?php

declare(strict_types=1);

namespace LHcze\BCP47\Tests\Registry;

use LHcze\BCP47\Normalizer\BCP47Normalizer;
use LHcze\BCP47\Parser\BCP47Parser;
use LHcze\BCP47\Registry\IanaSubtagRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IanaSubtagRegistryTest extends TestCase
{
    private static IanaSubtagRegistry $registry;

    public static function setUpBeforeClass(): void
    {
        $normalizer = new BCP47Normalizer();
        $parser = new BCP47Parser($normalizer);
        self::$registry = IanaSubtagRegistry::loadFromFile(__DIR__ . '/../../resources/iana_test.json', $parser);
    }

    public function testRegistryLoading(): void
    {
        $this->assertInstanceOf(IanaSubtagRegistry::class, self::$registry);
    }

    #[DataProvider('provideValidLanguages')]
    public function testIsValidLanguage(string $language, bool $expected): void
    {
        $this->assertSame($expected, self::$registry->isValidLanguage($language));
    }

    #[DataProvider('provideValidScripts')]
    public function testIsValidScript(string $script, bool $expected): void
    {
        $this->assertSame($expected, self::$registry->isValidScript($script));
    }

    #[DataProvider('provideValidRegions')]
    public function testIsValidRegion(string $region, bool $expected): void
    {
        $this->assertSame($expected, self::$registry->isValidRegion($region));
    }

    #[DataProvider('provideValidVariants')]
    public function testIsValidVariant(string $variant, bool $expected): void
    {
        $this->assertSame($expected, self::$registry->isValidVariant($variant));
    }

    #[DataProvider('provideGrandfatheredTags')]
    public function testIsGrandfathered(string $tag, bool $expected): void
    {
        $this->assertSame($expected, self::$registry->isGrandfathered($tag));
    }

    #[DataProvider('provideValidLocales')]
    public function testIsValidLocale(string $locale, bool $expected): void
    {
        $this->assertSame($expected, self::$registry->isValidLocale($locale));
    }

    public static function provideValidLanguages(): array
    {
        return [
            'valid language' => ['en', true],
            'valid language uppercase' => ['EN', true],
            'invalid language' => ['zzzz', false],
        ];
    }

    public static function provideValidScripts(): array
    {
        return [
            'valid script' => ['Latn', true],
            'valid script lowercase' => ['latn', true],
            'invalid script' => ['Zzzz', false],
        ];
    }

    public static function provideValidRegions(): array
    {
        return [
            'valid region' => ['US', true],
            'valid region lowercase' => ['us', true],
            'invalid region' => ['ZZ', false],
        ];
    }

    public static function provideValidVariants(): array
    {
        return [
            'valid variant' => ['1901', true],
            'invalid variant' => ['9999', false],
        ];
    }

    public static function provideGrandfatheredTags(): array
    {
        return [
            'valid grandfathered' => ['i-klingon', true],
            'invalid grandfathered' => ['i-invalid', false],
        ];
    }

    public static function provideValidLocales(): array
    {
        return [
            'valid language only' => ['en', true],
            'valid language-region' => ['en-US', true],
            'valid language-script-region' => ['zh-Hans-CN', true],
            'invalid language' => ['zz-US', false],
            'invalid script' => ['en-Zzzz-US', false],
            'invalid region' => ['en-ZZ', false],
        ];
    }
}
