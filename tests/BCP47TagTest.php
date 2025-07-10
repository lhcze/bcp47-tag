<?php

declare(strict_types=1);

namespace LHcze\BCP47\Tests;

use LHcze\BCP47\BCP47Tag;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BCP47TagTest extends TestCase
{
    #[DataProvider('provideValidLocales')]
    public function testConstructWithValidBCP47Tag(string $input, string $expected): void
    {
        $locale = new BCP47Tag($input);

        $this->assertSame($input, $locale->getOriginalInput());
        $this->assertSame($expected, $locale->getNormalized());
    }

    #[DataProvider('provideLocalesWithKnownTags')]
    public function testConstructWithKnownTags(
        string $input,
        array $knownTags,
        string $expected
    ): void {
        $locale = new BCP47Tag($input, null, $knownTags);

        $this->assertSame($input, $locale->getOriginalInput());
        $this->assertSame($expected, $locale->getNormalized());
    }

    #[DataProvider('provideLocalesWithFallback')]
    public function testConstructWithFallbackBCP47Tag(
        string $input,
        string $fallback,
        ?array $knownTags,
        string $expected
    ): void {
        $locale = new BCP47Tag($input, $fallback, $knownTags);

        $this->assertSame($input, $locale->getOriginalInput());
        $this->assertSame($expected, $locale->getNormalized());
    }

    #[DataProvider('provideInvalidLocales')]
    public function testConstructWithInvalidLocaleThrowsException(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BCP47Tag($input);
    }

    public function testConstructWithInvalidLocaleAndInvalidFallbackThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Both locale "invalid" and fallback locale "also-invalid" are invalid.');

        new BCP47Tag('invalid', 'also-invalid');
    }

    #[DataProvider('provideLocaleFormats')]
    public function testGetterMethods(string $input, string $normalized, string $underscored, string $lc, string $uc, string $lcu, string $ucu): void
    {
        $locale = new BCP47Tag($input);

        $this->assertSame($normalized, $locale->getNormalized());
        $this->assertSame($underscored, $locale->getUnderscored());
        $this->assertSame($lc, $locale->getLC());
        $this->assertSame($uc, $locale->getUC());
        $this->assertSame($lcu, $locale->getLCU());
        $this->assertSame($ucu, $locale->getUCU());
    }

    public function testJsonSerialize(): void
    {
        $locale = new BCP47Tag('en-us');

        $this->assertSame('en-US', $locale->jsonSerialize());
        $this->assertSame('en-US', json_decode(json_encode($locale)));
    }

    public function testToString(): void
    {
        $locale = new BCP47Tag('en-us');

        $this->assertSame('en-US', (string) $locale);
    }

    #[DataProvider('provideLocalesWithRequireCanonical')]
    public function testConstructWithRequireCanonical(
        string $input,
        array $knownTags,
        bool $requireCanonical,
        string $expected
    ): void {
        $locale = new BCP47Tag($input, null, $knownTags, $requireCanonical);

        $this->assertSame($input, $locale->getOriginalInput());
        $this->assertSame($expected, $locale->getNormalized());
    }

    #[DataProvider('provideLocalesWithRequireCanonicalExceptions')]
    public function testConstructWithRequireCanonicalThrowsException(
        string $input,
        array $knownTags
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('No region found for language "%s" in known tags.', $input));

        new BCP47Tag($input, null, $knownTags, true);
    }

    public static function provideValidLocales(): array
    {
        return [
            'simple locale' => ['en-us', 'en-US'],
            'already normalized' => ['en-US', 'en-US'],
            'with underscore' => ['en_us', 'en-US'],
            'uppercase' => ['EN-US', 'en-US'],
            'language only' => ['en', 'en'],
            'three-part locale' => ['zh-Hans-CN', 'zh-Hans-CN'],
            'grandfathered tag' => ['i-klingon', 'i-klingon'],
            // TODO: These are stubs for future implementation
            // 'extension tag' => ['en-US-x-private', 'en-US-x-private'],
            // 'private use' => ['x-private', 'x-private'],
        ];
    }

    public static function provideLocalesWithKnownTags(): array
    {
        return [
            'exact match' => ['en-US', ['en-US', 'fr-FR'], 'en-US'],
            'case-insensitive match' => ['en-us', ['en-US', 'fr-FR'], 'en-US'],
            'language-only match' => ['en', ['en-US', 'fr-FR'], 'en'],
            'no match, valid locale' => ['de-DE', ['en-US', 'fr-FR'], 'de-DE'],
            'with underscore in known tags' => ['en-us', ['en_US', 'fr_FR'], 'en-US'],
        ];
    }

    public static function provideLocalesWithFallback(): array
    {
        return [
            'valid locale, unused fallback' => ['en-US', 'fr-FR', null, 'en-US'],
            'invalid locale, use fallback' => ['invalid', 'fr-FR', null, 'fr-FR'],
            'valid locale not in supported, unused fallback' => ['de-DE', 'fr-FR', ['en-US'], 'de-DE'],
            'invalid locale, use fallback with supported' => ['invalid', 'fr-FR', ['en-US', 'fr-FR'], 'fr-FR'],
        ];
    }

    public static function provideInvalidLocales(): array
    {
        return [
            'completely invalid' => ['invalid'],
            'invalid format' => ['en-USA'],
            'empty string' => [''],
        ];
    }

    public static function provideLocaleFormats(): array
    {
        return [
            'standard locale' => [
                'en-us',
                'en-US',
                'en_US',
                'en-us',
                'EN-US',
                'en_us',
                'EN_US',
            ],
            'with underscore' => [
                'fr_ca',
                'fr-CA',
                'fr_CA',
                'fr-ca',
                'FR-CA',
                'fr_ca',
                'FR_CA',
            ],
        ];
    }

    public static function provideLocalesWithRequireCanonical(): array
    {
        return [
            'language only with require canonical true' => [
                'en',
                ['en-US', 'en-UK', 'fr-FR'],
                true,
                'en-US'
            ],
            'language only with require canonical false' => [
                'en',
                ['en-US', 'en-UK', 'fr-FR'],
                false,
                'en'
            ],
            'full locale with require canonical true' => [
                'en-UK',
                ['en-US', 'en-UK', 'fr-FR'],
                true,
                'en-UK'
            ],
            'full locale with require canonical false' => [
                'en-UK',
                ['en-US', 'en-UK', 'fr-FR'],
                false,
                'en-UK'
            ],
            'case insensitive with require canonical true' => [
                'EN',
                ['en-US', 'en-UK', 'fr-FR'],
                true,
                'en-US'
            ],
        ];
    }

    public static function provideLocalesWithRequireCanonicalExceptions(): array
    {
        return [
            'language only with no matching region' => [
                'de',
                ['en-US', 'en-UK', 'fr-FR']
            ],
            'language only with empty known tags' => [
                'en',
                []
            ],
        ];
    }
}
