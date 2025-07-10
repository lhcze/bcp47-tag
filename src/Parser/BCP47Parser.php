<?php

declare(strict_types=1);

namespace LHcze\BCP47\Parser;

use LHcze\BCP47\Normalizer\BCP47Normalizer;
use LHcze\BCP47\ValueObject\ParsedTag;

final readonly class BCP47Parser
{
    private BCP47Normalizer $normalizer;

    public function __construct(BCP47Normalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Normalize the known tags array
     * @param string[] $knownTags
     * @return string[]
     */
    public function parseKnownTags(array $knownTags): array
    {
        return array_map(
            fn(string $loc) => $this->normalizer->normalize($loc),
            $knownTags,
        );
    }

    /**
     * Find a match for the normalized locale in the known tags
     *
     * @param string[] $normalizedKnownTags
     */
    public function findMatchInKnownTags(string $normalized, array $normalizedKnownTags): ?string
    {
        // Try the exact match first
        if (in_array($normalized, $normalizedKnownTags, true)) {
            return $normalized;
        }

        // Try a case-insensitive match
        $lowercaseNormalized = strtolower($normalized);
        foreach ($normalizedKnownTags as $knownTag) {
            if (strtolower($knownTag) === $lowercaseNormalized) {
                return $knownTag;
            }
        }

        return null;
    }

    /**
     * Find a match for a language-only locale in the known tags
     *
     * @param string[] $normalizedKnownTags
     */
    public function findLanguageOnlyMatch(string $language, array $normalizedKnownTags): ?string
    {
        foreach ($normalizedKnownTags as $knownTag) {
            if (str_starts_with(strtolower($knownTag), strtolower($language) . '-')) {
                return $knownTag;
            }
        }

        return null;
    }

    /**
     * Parse a locale string into a ParsedTag object
     *
     * @param string $locale The locale string to parse (e.g., 'en-US', 'zh-Hans-CN')
     * @return ParsedTag The parsed tag
     */
    public function parseTag(string $locale): ParsedTag
    {
        // Normalize the locale first
        $normalized = $this->normalizer->normalize($locale);

        // Split the locale into parts
        $parts = explode('-', $normalized);

        // The first part is always the language
        $language = strtolower($parts[0]);

        // Initialize optional parts
        $script = null;
        $region = null;
        $variants = [];

        // Process remaining parts
        $remainingParts = array_slice($parts, 1);
        $partCount = count($remainingParts);

        if ($partCount > 0) {
            $index = 0;

            // Check for script (4 letters, first is uppercase, rest lowercase)
            if (strlen($remainingParts[$index]) === 4 && ctype_alpha($remainingParts[$index])) {
                $script = ucfirst(strtolower($remainingParts[$index]));
                $index++;
            }

            // Check for a region (2 letters uppercase or 3 digits)
            if ($partCount > $index) {
                $part = $remainingParts[$index];
                if ((strlen($part) === 2 && ctype_alpha($part)) || (strlen($part) === 3 && ctype_digit($part))) {
                    $region = strtoupper($part);
                    $index++;
                }
            }

            // The remaining parts are variants
            if ($partCount > $index) {
                $variants = array_slice($remainingParts, $index);
            }
        }

        // TODO: Handle extensions and private use in future versions

        return new ParsedTag($language, $script, $region, $variants);
    }
}
