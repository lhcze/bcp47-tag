<?php

declare(strict_types=1);

namespace LHcze\BCP47;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Validator\Constraints\Locale as LocaleConstraint;
use Symfony\Component\Validator\Validation;

final readonly class BCP47Tag implements Stringable, JsonSerializable
{
    /** RFC 5646 representation of a lang-tag */
    private string $locale;

    /** Original input string */
    private string $originalInput;

    /**
     * This should also be able to handle situations when raw input is `en-us` and we have only `en-US` in the
     * supportedLocales list. In that case, we should return `en-US` as the normalized locale.
     *
     * Only when everything fails, we could use the fallbackLocale. But first, an attempt to make a proper conversion
     * of raw locale is a priority.
     *
     * If optional arguments are not provided, we should only validate that locale has xx-xx format and normalize it
     * to xx-XX. But if it's just xx for example and we have no supported locales to compare it against and validate it,
     * let's throw an exception.
     *
     * It is possible to use PHP's Intl extension if it makes sense and would do the jobs faster and better than we
     * would.
     *
     * @param string $locale Raw locale input which could be anything like `en`, `En`, `EN`, `en-en`, etc.
     * @param string|null $fallbackLocale If everything fails, use this locale instead.
     * @param string[]|null $supportedLocales Comes as an array list with values in the format of
     * `xx-XX`, `xx_XX`, `xx-xx`, `xx_xx`
     * @param bool $regionRequired If true and the locale is language-only (e.g., 'en'),
     *                             it will attempt to match with the first supported locale with that language
     *                             and throw an exception if no match is found
     * @throws InvalidArgumentException When the locale is invalid and no fallback is provided
     */
    public function __construct(
        string $locale,
        ?string $fallbackLocale = null,
        ?array $supportedLocales = null,
        bool $regionRequired = false,
    ) {
        // Store the original input string
        $this->originalInput = $locale;

        // Step 1: Normalize the locale string
        $normalized = $this->normalizeLocale($locale);

        // Step 2: Process with supported locales if available
        if ($supportedLocales !== null) {
            $normalizedSupportedLocales = $this->getNormalizedSupportedLocales($supportedLocales);

            // Try an exact or case-insensitive match first
            $matchResult = $this->findMatchInSupportedLocales($normalized, $normalizedSupportedLocales);
            if ($matchResult !== null) {
                $this->locale = $matchResult;
                return;
            }

            // Handle language-only case
            if (!str_contains($normalized, '-')) {
                if ($regionRequired) {
                    // When regionRequired is true, try to find a match or throw exception
                    $languageMatch = $this->findLanguageOnlyMatch($normalized, $normalizedSupportedLocales);
                    if ($languageMatch !== null) {
                        $this->locale = $languageMatch;
                        return;
                    }

                    throw new InvalidArgumentException(
                        sprintf('No region found for language "%s" in supported locales.', $normalized),
                    );
                }
                // When the regionRequired is false, keep the language-only locale as is
            }
        }

        // Step 3: Handle validation and fallback
        $this->locale = $this->handleValidationAndFallback($normalized, $locale, $fallbackLocale);
    }

    public function getOriginalInput(): string
    {
        return $this->originalInput;
    }

    /**
     * Get the normalized locale (default storage format: `xx-XX`).
     */
    public function getNormalized(): string
    {
        return $this->locale;
    }

    /**
     * Get the underscore-separated format (`xx_XX`).
     */
    public function getUnderscored(): string
    {
        return str_replace('-', '_', $this->locale);
    }

    /**
     * Get the lowercase variant of the normalized locale (e.g., `xx-xx`).
     */
    public function getLC(): string
    {
        return strtolower($this->locale);
    }

    /**
     * Get the uppercase variant of the normalized locale (e.g., `XX-XX`).
     */
    public function getUC(): string
    {
        return strtoupper($this->locale);
    }

    /**
     * Get the lowercase, underscore-separated variant (e.g., `xx_xx`).
     */
    public function getLCU(): string
    {
        return str_replace('-', '_', $this->getLC());
    }

    /**
     * Get the uppercase, underscore-separated variant (e.g., `XX_XX`).
     */
    public function getUCU(): string
    {
        return str_replace('-', '_', $this->getUC());
    }

    public function jsonSerialize(): string
    {
        return $this->getNormalized();
    }

    /**
     * Normalize the supported locales array
     * @param string[] $supportedLocales
     * @return string[]
     */
    private function getNormalizedSupportedLocales(array $supportedLocales): array
    {
        return array_map(
            fn(string $loc) => $this->normalizeLocale($loc),
            $supportedLocales,
        );
    }

    /**
     * Find a match for the normalized locale in the supported locales
     *
     * @param string[] $normalizedSupportedLocales
     */
    private function findMatchInSupportedLocales(string $normalized, array $normalizedSupportedLocales): ?string
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
    private function findLanguageOnlyMatch(string $language, array $normalizedSupportedLocales): ?string
    {
        foreach ($normalizedSupportedLocales as $supportedLocale) {
            if (str_starts_with(strtolower($supportedLocale), strtolower($language) . '-')) {
                return $supportedLocale;
            }
        }

        return null;
    }

    /**
     * Handle validation of the locale and fallback if needed
     */
    private function handleValidationAndFallback(string $normalized, string $originalLocale, ?string $fallbackLocale): string
    {
        // Validate the locale
        if (!$this->isValidLocale($normalized)) {
            // If invalid and we have a fallback, use it
            if ($fallbackLocale !== null) {
                $fallbackNormalized = $this->normalizeLocale($fallbackLocale);
                if ($this->isValidLocale($fallbackNormalized)) {
                    return $fallbackNormalized;
                }
                throw new InvalidArgumentException(
                    sprintf('Both locale "%s" and fallback locale "%s" are invalid.', $originalLocale, $fallbackLocale),
                );
            }

            throw new InvalidArgumentException(sprintf('Invalid locale format: "%s".', $originalLocale));
        }

        // Use the normalized locale if it's valid and no match was found in supported locales
        return $normalized;
    }

    /**
     * Normalize the locale by replacing underscores with dashes and ensuring proper casing of the region
     * Uses Intl extension when available for better locale handling
     */
    private function normalizeLocale(string $locale): string
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

    /**
     * Validate the locale format using Symfony's Locale constraint and Intl extension when available
     */
    private function isValidLocale(string $locale): bool
    {
        // First try with Intl extension if available
        if (class_exists('Symfony\Component\Intl\Locales')) {
            // Check if it's a language-only code (e.g., 'en')
            if (!str_contains($locale, '-')) {
                return Locales::exists($locale);
            }

            // Check if it's a full locale code (e.g., 'en-US')
            if (Locales::exists($locale)) {
                return true;
            }
        }

        // Additional validation for region format (must be 2 characters for standard locales)
        $parts = explode('-', $locale);
        if (count($parts) === 2 && strlen($parts[1]) !== 2) {
            return false;
        }

        // Fallback to Symfony's validator
        $validator = Validation::createValidator();
        $violations = $validator->validate($locale, new LocaleConstraint());

        return count($violations) === 0;
    }

    /**
     * Convert the locale to a string (`xx-XX` format by default).
     */
    public function __toString(): string
    {
        return $this->getNormalized();
    }
}
