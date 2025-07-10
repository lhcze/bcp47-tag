<?php

declare(strict_types=1);

namespace LHcze\BCP47\ValueObject;

/**
 * Value object representing a parsed BCP47 language tag
 */
readonly class ParsedTag
{
    /**
     * @param string $language The language subtag (e.g., 'en')
     * @param string|null $script The script subtag (e.g., 'Latn')
     * @param string|null $region The region subtag (e.g., 'US')
     * @param string[] $variants The variant subtags
     */
    public function __construct(
        private string $language,
        private ?string $script = null,
        private ?string $region = null,
        private array $variants = [],
    ) {
    }

    /**
     * Get the language subtag
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Get the script subtag
     */
    public function getScript(): ?string
    {
        return $this->script;
    }

    /**
     * Get the region subtag
     */
    public function getRegion(): ?string
    {
        return $this->region;
    }

    /**
     * Get the variant subtags
     *
     * @return string[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    /**
     * Check if the tag has a script subtag
     */
    public function hasScript(): bool
    {
        return $this->script !== null;
    }

    /**
     * Check if the tag has a region subtag
     */
    public function hasRegion(): bool
    {
        return $this->region !== null;
    }

    /**
     * Check if the tag has any variant subtags
     */
    public function hasVariants(): bool
    {
        return count($this->variants) > 0;
    }
}
