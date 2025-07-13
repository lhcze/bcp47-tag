<?php

declare(strict_types=1);

namespace LHcze\BCP47\Tests;

use LHcze\BCP47\BCP47Tag;
use LHcze\BCP47\Exception\BCP47IanaRegistryException;
use LHcze\BCP47\Exception\BCP47InvalidArgumentException;
use LHcze\BCP47\Exception\BCP47InvalidFallbackLocaleException;
use LHcze\BCP47\Exception\BCP47InvalidLocaleException;
use LHcze\BCP47\Exception\BCP47InvalidMatchingTagException;
use LHcze\BCP47\ValueObject\LanguageTag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BCP47TagTest extends TestCase
{
    /**
     * @throws BCP47InvalidArgumentException
     * @throws BCP47InvalidLocaleException
     * @throws BCP47InvalidFallbackLocaleException
     * @throws BCP47IanaRegistryException
     * @throws BCP47InvalidMatchingTagException
     */
    #[DataProvider('provideValidLocales')]
    public function testConstructWithValidBCP47Tag(string $input, string $expected): void
    {
        $locale = new BCP47Tag($input);

        $this->assertSame($input, $locale->getInputLocale());
        $this->assertSame($expected, $locale->getNormalized());
    }

    /**
     * @throws BCP47InvalidLocaleException
     * @throws BCP47InvalidMatchingTagException
     * @throws BCP47InvalidFallbackLocaleException
     * @throws BCP47IanaRegistryException
     * @throws BCP47InvalidArgumentException
     */
    #[DataProvider('provideLocalesWithKnownTags')]
    public function testConstructWithKnownTags(
        string $input,
        array $matchingTags,
        string $expected
    ): void {
        $locale = new BCP47Tag($input, useCanonicalMatchTags: $matchingTags);

        $this->assertSame($input, $locale->getInputLocale());
        $this->assertSame($expected, $locale->getNormalized());
    }

    /**
     * @throws BCP47InvalidLocaleException
     * @throws BCP47InvalidMatchingTagException
     * @throws BCP47InvalidFallbackLocaleException
     * @throws BCP47IanaRegistryException
     * @throws BCP47InvalidArgumentException
     */
    #[DataProvider('provideLocalesWithFallback')]
    public function testConstructWithFallbackBCP47Tag(
        string $input,
        string $fallback,
        ?array $useCanonicalMatchTags,
        string $expected
    ): void {
        $locale = new BCP47Tag($input, $fallback, $useCanonicalMatchTags);

        $this->assertSame($input, $locale->getInputLocale());
        $this->assertSame($expected, $locale->getNormalized());
    }

    /**
     * @throws BCP47InvalidFallbackLocaleException
     * @throws BCP47IanaRegistryException
     * @throws BCP47InvalidMatchingTagException
     * @throws BCP47InvalidArgumentException
     */
    #[DataProvider('provideInvalidLocales')]
    public function testConstructWithInvalidLocaleThrowsException(string $input): void
    {
        $this->expectException(BCP47InvalidLocaleException::class);

        new BCP47Tag($input);
    }

    public function testConstructWithInvalidLocaleAndInvalidFallbackThrowsException(): void
    {
        $this->expectException(\LHcze\BCP47\Exception\BCP47InvalidFallbackLocaleException::class);
        $this->expectExceptionMessage('Both locale "invalid" and fallback locale "also-invalid" are invalid.');

        new BCP47Tag('invalid', 'also-invalid');
    }

    /**
     * @throws BCP47InvalidArgumentException
     * @throws BCP47InvalidLocaleException
     * @throws BCP47InvalidMatchingTagException
     * @throws BCP47InvalidFallbackLocaleException
     * @throws BCP47IanaRegistryException
     */
    #[DataProvider('provideLocaleFormats')]
    public function testGetterMethods(string $input, string $normalized, string $underscored, string $lc, string $uc, string $lcu, string $ucu): void
    {
        $locale = new BCP47Tag($input);

        $this->assertSame($normalized, $locale->getNormalized());
        $this->assertSame($underscored, $locale->getICUformat());
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

    /**
     * @throws BCP47InvalidLocaleException
     * @throws BCP47InvalidMatchingTagException
     * @throws BCP47InvalidFallbackLocaleException
     * @throws BCP47IanaRegistryException
     * @throws BCP47InvalidArgumentException
     */
    #[DataProvider('provideLocalesForLanguageTag')]
    public function testGetLanguageTag(string $input, string $expectedLanguage, ?string $expectedScript, ?string $expectedRegion, array $expectedVariants): void
    {
        $locale = new BCP47Tag($input);
        $languageTag = $locale->getLanguageTag();

        $this->assertInstanceOf(LanguageTag::class, $languageTag);
        $this->assertSame($expectedLanguage, $languageTag->getLanguage());
        $this->assertSame($expectedScript, $languageTag->getScript());
        $this->assertSame($expectedRegion, $languageTag->getRegion());
        $this->assertSame($expectedVariants, $languageTag->getVariants());
    }

    /**
     * @throws BCP47InvalidLocaleException
     * @throws BCP47InvalidFallbackLocaleException
     * @throws BCP47IanaRegistryException
     * @throws BCP47InvalidMatchingTagException
     * @throws BCP47InvalidArgumentException
     */
    #[DataProvider('provideLocalesWithCanonicalMatchTags')]
    public function testConstructWithCanonicalMatchTags(
        string $input,
        array $canonicalMatchTags,
        string $expected
    ): void {
        $locale = new BCP47Tag($input, useCanonicalMatchTags: $canonicalMatchTags);

        $this->assertSame($input, $locale->getInputLocale());
        $this->assertSame($expected, $locale->getNormalized());
    }

    /**
     * @throws BCP47InvalidLocaleException
     * @throws BCP47IanaRegistryException
     * @throws BCP47InvalidFallbackLocaleException
     * @throws BCP47InvalidArgumentException
     */
    #[DataProvider('provideLocalesWithCanonicalMatchTagsExceptions')]
    public function testConstructWithCanonicalMatchTagsThrowsException(
        string $input,
        array $canonicalMatchTags
    ): void {
        $this->expectException(BCP47InvalidMatchingTagException::class);

        new BCP47Tag($input, null, $canonicalMatchTags);
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
            'language-only match with invalid casing' => ['EN', ['en-us', 'fr-FR'], 'en-US'],
//            'no match, valid locale' => ['de-DE', ['en-US', 'fr-FR'], 'de-DE'],
            'with underscore in known tags' => ['en-us', ['en_US', 'fr_FR'], 'en-US'],
        ];
    }

    public static function provideLocalesWithFallback(): array
    {
        return [
            'valid locale, unused fallback' => ['en-US', 'fr-FR', null, 'en-US'],
            'invalid locale, use fallback' => ['invalid', 'fr-FR', null, 'fr-FR'],
            'invalid locale, use fallback with matchTags' => ['invalid', 'fr-FR', ['en-US','fr-fr'], 'fr-FR'],
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

    public static function provideLocalesWithCanonicalMatchTags(): array
    {
        return [
            'language only with canonical match tags' => [
                'en',
                ['en-US', 'en-GB', 'fr-FR'],
                'en-US'
            ],
            'full locale with canonical match tags' => [
                'en-GB',
                ['en-US', 'en-GB', 'fr-FR'],
                'en-GB'
            ],
            'case insensitive with canonical match tags' => [
                'EN',
                ['en-US', 'en-GB', 'fr-FR'],
                'en-US'
            ],
        ];
    }

    public static function provideLocalesWithCanonicalMatchTagsExceptions(): array
    {
        return [
            'language only with no matching language' => [
                'de',
                ['en-US', 'en-UK', 'fr-FR']
            ],
            'language only with empty canonical match tags' => [
                'en',
                []
            ],
        ];
    }

    public static function provideLocalesForLanguageTag(): array
    {
        return [
            'language only' => [
                'en',
                'en',
                null,
                null,
                [],
            ],
            'language-region' => [
                'en-US',
                'en',
                null,
                'US',
                [],
            ],
            'language-script-region' => [
                'zh-Hans-CN',
                'zh',
                'Hans',
                'CN',
                [],
            ],
            'language-region-variant' => [
                'de-DE-1901',
                'de',
                null,
                'DE',
                ['1901'],
            ],
            'mixed case' => [
                'eN-uS',
                'en',
                null,
                'US',
                [],
            ],
        ];
    }
}
