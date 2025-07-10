<?php

declare(strict_types=1);

namespace LHcze\BCP47\Normalizer;

final readonly class BCP47Normalizer
{
    /**
     * Normalize the locale by replacing underscores with dashes and ensuring proper casing of the region
     */
    public function normalize(string $locale): string
    {
        // Replace underscores with dashes
        $locale = str_replace('_', '-', $locale);

        // Special handling for grandfathered tags (e.g., 'i-klingon')
        // These should be kept as-is except for underscore replacement
        if (str_starts_with(strtolower($locale), 'i-') || str_starts_with(strtolower($locale), 'x-')) {
            return $locale;
        }

        // Split into language and region parts
        $parts = explode('-', strtolower($locale));

        // Handle language-only case (e.g., 'en')
        if (count($parts) === 1) {
            return $parts[0]; // Return language-only code
        }

        // Handle language-region case (e.g., 'en-us')
        if (count($parts) === 2) {
            // Capitalize the region part
            $parts[1] = strtoupper($parts[1]);
            return implode('-', $parts);
        }

        // Handle more complex cases (e.g., 'zh-Hans-CN')
        // For now, just normalize the first two parts and keep the rest as they are
        if (count($parts) > 2) {
            $parts[0] = strtolower($parts[0]); // Language code in the lowercase
            $parts[1] = ucfirst($parts[1]); // Script in Title Case
            if (count($parts) > 2) {
                $parts[2] = strtoupper($parts[2]); // Region in UPPERCASE
            }

            return implode('-', $parts);
        }

        // Fallback for any other case
        return implode('-', $parts);
    }
}
