<?php

declare(strict_types=1);

namespace LHcze\BCP47\Tests\Registry;

use LHcze\BCP47\Exception\BCP47IanaRegistryException;
use LHcze\BCP47\Exception\BCP47ParserException;
use LHcze\BCP47\Normalizer\BCP47Normalizer;
use LHcze\BCP47\Parser\BCP47Parser;
use LHcze\BCP47\Registry\IanaSubtagRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IanaSubtagRegistryTest extends TestCase
{
    private static IanaSubtagRegistry $registry;

    /**
     * @throws BCP47IanaRegistryException
     */
    public static function setUpBeforeClass(): void
    {
        self::$registry = IanaSubtagRegistry::load();
    }

    public function testRegistryLoading(): void
    {
        $this->assertInstanceOf(IanaSubtagRegistry::class, self::$registry);
    }

    #[DataProvider('provideLanguages')]
    public function testIsValidLanguage(string $language, bool $expected): void
    {
        $this->assertSame($expected, self::$registry->isValidLanguage($language));
    }

    #[DataProvider('provideScripts')]
    public function testIsValidScript(string $script, bool $expected): void
    {
        $this->assertSame($expected, self::$registry->isValidScript($script));
    }

    #[DataProvider('provideRegions')]
    public function testIsValidRegion(string $region, bool $expected): void
    {
        $this->assertSame($expected, self::$registry->isValidRegion($region));
    }

    #[DataProvider('provideVariants')]
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
    public function testIsValidParsedTag(string $locale, bool $expected): void
    {
        $normalizer = new BCP47Normalizer();
        $parser = new BCP47Parser($normalizer);

        try {
            $parsedTag = $parser->parseTag($locale);
            $result = self::$registry->isValidParsedTag($parsedTag);
            $this->assertSame($expected, $result);
        } catch (BCP47ParserException $e) {
            // If parsing fails, the locale is invalid
            $this->assertFalse($expected);
        }
    }

    #[DataProvider('provideValidLocalesWithRequirements')]
    public function testIsValidParsedTagWithRequirements(string $locale, bool $requireRegion, bool $requireScript, bool $expected): void
    {
        $normalizer = new BCP47Normalizer();
        $parser = new BCP47Parser($normalizer);

        try {
            $parsedTag = $parser->parseTag($locale);
            $result = self::$registry->isValidParsedTag($parsedTag, $requireRegion, $requireScript);
            $this->assertSame($expected, $result);
        } catch (BCP47ParserException $e) {
            // If parsing fails, the locale is invalid
            $this->assertFalse($expected);
        }
    }

    #[DataProvider('provideInvalidLocales')]
    public function testValidateParsedTagThrowsException(string $locale): void
    {
        $normalizer = new BCP47Normalizer();
        $parser = new BCP47Parser($normalizer);

        try {
            $parsedTag = $parser->parseTag($locale);
            $this->expectException(BCP47IanaRegistryException::class);
            self::$registry->validateParsedTag($parsedTag);
        } catch (BCP47ParserException $e) {
            // If parsing fails, we can't test validateParsedTag
            $this->markTestSkipped('Cannot test validateParsedTag because parsing failed');
        }
    }

    public static function provideLanguages(): array
    {
        return [
            'valid language' => ['en', true],
            'invalid language uppercase' => ['EN', false],
            'invalid language casing' => ['EN', false],
            'invalid language' => ['zzzz', false],
        ];
    }

    /**
     * 0: script
     * 1: expected result
     */
    public static function provideScripts(): array
    {
        return [
            'valid script' => ['Latn', true],
            'invalid script lowercase' => ['latn', false],
            'invalid script uppercase' => ['LATN', false],
            'valid uncoded script' => ['Zzzz', true],
        ];
    }

    public static function provideRegions(): array
    {
        return [
            'valid region' => ['US', true],
            'valid region lowercase' => ['us', false],
            'invalid region casing' => ['uS', false],
            'valid unknown region' => ['ZZ', true],
            'invalid unknown region' => ['Zz', false],
        ];
    }

    public static function provideVariants(): array
    {
        return [
            'valid variant 1901' => ['1901', true],
            'valid variant 1994' => ['1994', true],
            'valid variant 1996' => ['1996', true],
            'invalid variant 1999' => ['9999', false],
            'valid viennese variant' => ['viennese', true],
            'invalid viennese variant' => ['Viennese', false],
            'valid newfound variant' => ['newfound', true],
            'invalid newfound variant' => ['NEWfound', false],
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
            'valid Unknown or Invalid Territory Script in language-script-region' => ['en-Zzzz-US', true],
            'valid Unknown or Invalid Territory Region in language-region' => ['en-ZZ', true],
            'invalid language' => ['zz-US', false],
        ];
    }

    public static function provideValidLocalesWithRequirements(): array
    {
        return [
            'language only, no requirements' => ['en', false, false, true],
            'language only, require region' => ['en', true, false, false],
            'language only, require script' => ['en', false, true, false],
            'language only, require both' => ['en', true, true, false],
            'language-region, no requirements' => ['en-US', false, false, true],
            'language-region, require region' => ['en-US', true, false, true],
            'language-region, require script' => ['en-US', false, true, false],
            'language-region, require both' => ['en-US', true, true, false],
            'language-script, no requirements' => ['zh-Hans', false, false, true],
            'language-script, require region' => ['zh-Hans', true, false, false],
            'language-script, require script' => ['zh-Hans', false, true, true],
            'language-script, require both' => ['zh-Hans', true, true, false],
            'language-script-region, no requirements' => ['zh-Hans-CN', false, false, true],
            'language-script-region, require region' => ['zh-Hans-CN', true, false, true],
            'language-script-region, require script' => ['zh-Hans-CN', false, true, true],
            'language-script-region, require both' => ['zh-Hans-CN', true, true, true],
        ];
    }

    public static function provideInvalidLocales(): array
    {
        return [
            'invalid language' => ['00-US'],
            'invalid script' => ['en-0000-US'],
            'invalid region' => ['en-00'],
            'invalid variant' => ['en-US-00000'],
        ];
    }
}
