<?php

declare(strict_types=1);

namespace LHcze\BCP47\ValueObject;

use JsonSerializable;
use Stringable;

/**
 * @internal Parsed tag is internally recognized only as a semantically correct, but not necessarily valid language tag.
 * It's usually used as an argument for IanaSubtagRegistry::validate() method or for carrying around the code base as
 * a value object.
 *
 * NOTE: While JSON representation includes variants, the __toString() method DOES NOT - only language-region-script.
 * If you want to get the full tag string, use __toStringWithVariants() method.
 */
readonly class ParsedTag implements Stringable, JsonSerializable
{
    /**
     * Create a new ParsedTag instance.
     *
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

    /**
     * @return array<string, string|string[]|null>
     */
    public function jsonSerialize(): array
    {
        return [
            'language' => $this->language,
            'script' => $this->script,
            'region' => $this->region,
            'variants' => $this->variants,
        ];
    }

    /**
     * Get the full tag string including variants.
     *
     * @return string The full tag string including variants.
     *
     * NOTE: The __toString() method DOES NOT include variants, only language-region-script.
     * If you want to get the full tag string, use this method.
     *
     * @see ParsedTag::__toString()
     */
    public function __toStringWithVariants(): string
    {
        if (!$this->hasVariants()) {
            return $this->__toString();
        }

        return $this->__toString() . '-' . implode('-', $this->variants);
    }

    /**
     * Get the full tag string without variants
     */
    public function __toString(): string
    {
        $result = [];

        $result[] = $this->language;

        if ($this->hasScript()) {
            $result[] = $this->script;
        }

        if ($this->hasRegion()) {
            $result[] = $this->region;
        }

        return implode('-', $result);
    }
}
