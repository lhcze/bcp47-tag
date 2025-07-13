<?php

declare(strict_types=1);

namespace LHcze\BCP47\Parser;

use LHcze\BCP47\Enum\GrandfatheredTag;
use LHcze\BCP47\Exception\BCP47ParserException;
use LHcze\BCP47\Normalizer\BCP47Normalizer;
use LHcze\BCP47\ValueObject\ParsedTag;
use Throwable;

/**
 * TODO Does not support extensions, transformations and private use tags yet
 * @see https://www.rfc-editor.org/rfc/bcp/bcp47.html#tag-syntax
 * @see https://www.rfc-editor.org/rfc/rfc4647.html
 * @see https://www.rfc-editor.org/rfc/rfc5646.html
 * @see https://cldr.unicode.org/development/development-process/design-proposals/bcp47-syntax-mapping
 */
final readonly class BCP47Parser
{
    public function __construct(private BCP47Normalizer $normalizer)
    {
    }

    /**
     * Normalize the known tags array
     * @param string[] $matchTags
     * @return string[]
     */
    public function parseMatchTags(array $matchTags): array
    {
        return array_map(
            fn(string $loc) => $this->normalizer->normalize($loc),
            $matchTags,
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
     * @throws BCP47ParserException
     */
    public function parseTag(string $locale): ParsedTag
    {
        try {
            // Normalize the locale first
            $normalized = $this->normalizer->normalize($locale);

            if ($normalized === '') {
                throw new BCP47ParserException('Empty locale.');
            }

            // Get parts from the normalized locale
            $parts = $this->getParts($normalized);

            // The first part is always the language
            $language = strtolower($parts[0]);

            // Process remaining parts to extract script, region, and variants
            $remainingParts = array_slice($parts, 1);
            [$script, $region, $variants] = $this->processRemainingParts($remainingParts);

            // TODO: Handle extensions and private use in future versions

            return new ParsedTag($language, $script, $region, $variants);
        } catch (Throwable $e) {
            throw new BCP47ParserException('Failed to parse locale.', 0, $e);
        }
    }

    /**
     * Get parts from a normalized locale string, handling grandfathered tags
     *
     * @param string $normalized The normalized locale string
     * @return string[] The parts of the locale
     */
    private function getParts(string $normalized): array
    {
        $grandfatheredTag = GrandfatheredTag::tryFrom($normalized);
        if ($grandfatheredTag !== null) {
            return [$grandfatheredTag->value];
        }

        return explode('-', $normalized);
    }

    /**
     * Process the remaining parts of a locale to extract a script, region, and variants
     *
     * @param string[] $remainingParts The remaining parts after the language
     * @return array{0: ?string, 1: ?string, 2: string[]} Array containing [script, region, variants]
     */
    private function processRemainingParts(array $remainingParts): array
    {
        $script = null;
        $region = null;
        $variants = [];

        $partCount = count($remainingParts);

        if ($partCount === 0) {
            return [$script, $region, $variants];
        }

        $index = 0;

        // Check for script (4 letters, first is uppercase, rest lowercase)
        if ($this->isScript($remainingParts[$index])) {
            $script = ucfirst(strtolower($remainingParts[$index]));
            $index++;
        }

        // Check for a region (2 letters uppercase or 3 digits)
        if ($partCount > $index && $this->isRegion($remainingParts[$index])) {
            $region = strtoupper($remainingParts[$index]);
            $index++;
        }

        // The remaining parts are variants
        if ($partCount > $index) {
            $variants = array_slice($remainingParts, $index);
        }

        return [$script, $region, $variants];
    }

    /**
     * Check if a part is a script subtag
     *
     * @param string $part The part to check
     * @return bool True if the part is a script subtag
     */
    private function isScript(string $part): bool
    {
        return strlen($part) === 4 && ctype_alpha($part);
    }

    /**
     * Check if a part is a region subtag
     *
     * @param string $part The part to check
     * @return bool True if the part is a region subtag
     */
    private function isRegion(string $part): bool
    {
        return (strlen($part) === 2 && ctype_alpha($part)) || (strlen($part) === 3 && ctype_digit($part));
    }
}
