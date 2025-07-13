<?php

declare(strict_types=1);

namespace LHcze\BCP47\Tests\ValueObject;

use LHcze\BCP47\Exception\BCP47IanaRegistryException;
use LHcze\BCP47\Registry\IanaSubtagRegistry;
use LHcze\BCP47\ValueObject\LanguageTag;
use LHcze\BCP47\ValueObject\ParsedTag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LanguageTagTest extends TestCase
{
    /**
     * @throws BCP47IanaRegistryException
     */
    #[DataProvider('provideValidParsedTags')]
    public function testFromValidatedParsedTag(
        string $language,
        ?string $script,
        ?string $region,
        array $variants
    ): void {
        $registry = IanaSubtagRegistry::load();

        $parsedTag = new ParsedTag($language, $script, $region, $variants);
        $languageTag = LanguageTag::fromValidatedParsedTag($parsedTag, $registry);

        $this->assertInstanceOf(LanguageTag::class, $languageTag);
        $this->assertSame($language, $languageTag->getLanguage());
        $this->assertSame($script, $languageTag->getScript());
        $this->assertSame($region, $languageTag->getRegion());
        $this->assertSame($variants, $languageTag->getVariants());
    }

    public static function provideValidParsedTags(): array
    {
        return [
            'language only' => [
                'en',
                null,
                null,
                [],
            ],
            'language-region' => [
                'en',
                null,
                'US',
                [],
            ],
            'language-script-region' => [
                'zh',
                'Hans',
                'CN',
                [],
            ],
            'language-region-variant' => [
                'de',
                null,
                'DE',
                ['1901'],
            ],
            'language-script-region-variant' => [
                'zh',
                'Hans',
                'CN',
                ['pinyin'],
            ],
        ];
    }
}