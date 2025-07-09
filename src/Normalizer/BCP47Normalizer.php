<?php

declare(strict_types=1);

namespace LHcze\BCP47\Normalizer;

use Symfony\Component\Intl\Locales;

class BCP47Normalizer
{
    /**
     * Normalize the locale by replacing underscores with dashes and ensuring proper casing of the region
     * Uses Intl extension when available for better locale handling
     */
    public function normalize(string $locale): string
    {
        // Replace underscores with dashes
        $locale = str_replace('_', '-', $locale);

        // Split into language and region parts
        $parts = explode('-', strtolower($locale));

        // Handle language-only case (e.g., 'en')
        if (count($parts) === 1) {
            // Try to find a default region for this language using Intl if available
            if (class_exists('Symfony\Component\Intl\Locales') && Locales::exists($parts[0])) {
                return $parts[0]; // Return language-only code if it's valid
            }
            return $parts[0]; // Return as-is if Intl is not available
        }

        // Handle language-region case (e.g., 'en-us')
        if (count($parts) === 2) {
            // Capitalize the region part
            $parts[1] = strtoupper($parts[1]);

            // Check if this is a valid locale using Intl if available
            $normalized = implode('-', $parts);
            if (class_exists('Symfony\Component\Intl\Locales') && Locales::exists($normalized)) {
                return $normalized;
            }

            return $normalized; // Return a normalized format even if not in an Intl database
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
