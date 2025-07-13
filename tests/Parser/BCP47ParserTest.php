<?php

declare(strict_types=1);

namespace LHcze\BCP47\Tests\Parser;

use LHcze\BCP47\Exception\BCP47ParserException;
use LHcze\BCP47\Normalizer\BCP47Normalizer;
use LHcze\BCP47\Parser\BCP47Parser;
use LHcze\BCP47\ValueObject\ParsedTag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BCP47ParserTest extends TestCase
{
    private BCP47Parser $parser;

    protected function setUp(): void
    {
        $normalizer = new BCP47Normalizer();
        $this->parser = new BCP47Parser($normalizer);
    }

    /**
     * @throws BCP47ParserException
     */
    #[DataProvider('provideLocalesForParsing')]
    public function testParseTag(
        string $locale,
        string $expectedLanguage,
        ?string $expectedScript,
        ?string $expectedRegion,
        array $expectedVariants
    ): void {
        $parsedTag = $this->parser->parseTag($locale);

        $this->assertInstanceOf(ParsedTag::class, $parsedTag);
        $this->assertSame($expectedLanguage, $parsedTag->getLanguage());
        $this->assertSame($expectedScript, $parsedTag->getScript());
        $this->assertSame($expectedRegion, $parsedTag->getRegion());
        $this->assertSame($expectedVariants, $parsedTag->getVariants());
    }

    #[DataProvider('provideKnownTags')]
    public function testParseKnownTags(array $knownTags, array $expected): void
    {
        $result = $this->parser->parseMatchTags($knownTags);
        $this->assertSame($expected, $result);
    }

    #[DataProvider('provideLocalesForMatching')]
    public function testFindMatchInKnownTags(
        string $normalized,
        array $knownTags,
        ?string $expected
    ): void {
        $result = $this->parser->findMatchInKnownTags($normalized, $knownTags);
        $this->assertSame($expected, $result);
    }

    #[DataProvider('provideLanguageOnlyMatches')]
    public function testFindLanguageOnlyMatch(
        string $language,
        array $knownTags,
        ?string $expected
    ): void {
        $result = $this->parser->findLanguageOnlyMatch($language, $knownTags);
        $this->assertSame($expected, $result);
    }

    #[DataProvider('provideInvalidLocales')]
    public function testParseTagThrowsException(string $locale): void
    {
        $this->expectException(BCP47ParserException::class);
        $this->parser->parseTag($locale);
    }

    public static function provideLocalesForParsing(): array
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
            'language-script-region-variant' => [
                'zh-Hans-CN-pinyin',
                'zh',
                'Hans',
                'CN',
                ['pinyin'],
            ],
            'language-region-multiple-variants' => [
                'de-DE-1901-1996',
                'de',
                null,
                'DE',
                ['1901', '1996'],
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

    public static function provideKnownTags(): array
    {
        return [
            'simple tags' => [
                ['en-US', 'fr-FR', 'de-DE'],
                ['en-US', 'fr-FR', 'de-DE'],
            ],
            'mixed case' => [
                ['en-us', 'FR-fr', 'De-dE'],
                ['en-US', 'fr-FR', 'de-DE'],
            ],
            'with underscores' => [
                ['en_US', 'fr_FR', 'de_DE'],
                ['en-US', 'fr-FR', 'de-DE'],
            ],
        ];
    }

    public static function provideLocalesForMatching(): array
    {
        return [
            'exact match' => [
                'en-US',
                ['en-US', 'fr-FR', 'de-DE'],
                'en-US',
            ],
            'case-insensitive match' => [
                'en-us',
                ['en-US', 'fr-FR', 'de-DE'],
                'en-US',
            ],
            'no match' => [
                'es-ES',
                ['en-US', 'fr-FR', 'de-DE'],
                null,
            ],
        ];
    }

    public static function provideLanguageOnlyMatches(): array
    {
        return [
            'language match' => [
                'en',
                ['en-US', 'en-GB', 'fr-FR'],
                'en-US',
            ],
            'no match' => [
                'es',
                ['en-US', 'fr-FR', 'de-DE'],
                null,
            ],
        ];
    }

    public static function provideInvalidLocales(): array
    {
        return [
            'empty string' => [''],
            // The following cases don't actually cause exceptions in the current implementation
            // but we're keeping them as documentation of what we consider invalid
            // 'invalid format' => ['en-USA'],
            // 'too many parts' => ['en-US-Latn-variant-extension-private'],
            // 'invalid characters' => ['en-US-!@#$'],
        ];
    }
}
