<?php

declare(strict_types=1);

namespace LHcze\BCP47\Parser;

use LHcze\BCP47\Normalizer\BCP47Normalizer;

class BCP47Parser
{
    private BCP47Normalizer $normalizer;

    public function __construct(BCP47Normalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Normalize the supported locales array
     * @param string[] $supportedLocales
     * @return string[]
     */
    public function parseSupportedLocales(array $supportedLocales): array
    {
        return array_map(
            fn(string $loc) => $this->normalizer->normalize($loc),
            $supportedLocales,
        );
    }

    /**
     * Find a match for the normalized locale in the supported locales
     *
     * @param string[] $normalizedSupportedLocales
     */
    public function findMatchInSupportedLocales(string $normalized, array $normalizedSupportedLocales): ?string
    {
        // Try the exact match first
        if (in_array($normalized, $normalizedSupportedLocales, true)) {
            return $normalized;
        }

        // Try a case-insensitive match
        $lowercaseNormalized = strtolower($normalized);
        foreach ($normalizedSupportedLocales as $supportedLocale) {
            if (strtolower($supportedLocale) === $lowercaseNormalized) {
                return $supportedLocale;
            }
        }

        return null;
    }

    /**
     * Find a match for a language-only locale in the supported locales
     *
     * @param string[] $normalizedSupportedLocales
     */
    public function findLanguageOnlyMatch(string $language, array $normalizedSupportedLocales): ?string
    {
        foreach ($normalizedSupportedLocales as $supportedLocale) {
            if (str_starts_with(strtolower($supportedLocale), strtolower($language) . '-')) {
                return $supportedLocale;
            }
        }

        return null;
    }
}
