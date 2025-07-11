<?php

declare(strict_types=1);

namespace LHcze\BCP47;

use InvalidArgumentException;
use JsonSerializable;
use LHcze\BCP47\Normalizer\BCP47Normalizer;
use LHcze\BCP47\Parser\BCP47Parser;
use LHcze\BCP47\Registry\IanaSubtagRegistry;
use LHcze\BCP47\ValueObject\ParsedTag;
use Stringable;

final readonly class BCP47Tag implements Stringable, JsonSerializable
{
    /** RFC 5646 representation of a lang-tag */
    private string $locale;

    /** Original input string */
    private string $originalInput;

    /** IANA Language Subtag Registry */
    private IanaSubtagRegistry $registry;

    /**
     * @param string $locale Raw string locale input
     * @param string|null $fallbackLocale If everything fails, use this locale instead
     * @param string[]|null $knownTags A list of known canonical BCP 47 language tags
     * @param bool $requireCanonical If true and the locale is language-only (e.g., 'en'),
     *                             it will attempt to match with the first known tag with that language
     *                             and throw an exception if no match is found
     * @throws InvalidArgumentException When the locale is invalid and no fallback is provided
     */
    public function __construct(
        string $locale,
        ?string $fallbackLocale = null,
        ?array $knownTags = null,
        bool $requireCanonical = false,
    ) {
        // Initialize helper classes
        $normalizer = new BCP47Normalizer();
        $parser = new BCP47Parser($normalizer);

        // Load the IANA registry
        $this->registry = IanaSubtagRegistry::load($parser);

        // Store the original input string
        $this->originalInput = $locale;

        // Step 1: Normalize the locale string
        $normalized = $normalizer->normalize($locale);

        // Step 2: Process with known tags if available
        if ($knownTags !== null) {
            $normalizedKnownTags = $parser->parseKnownTags($knownTags);

            // Try an exact or case-insensitive match first
            $matchResult = $parser->findMatchInKnownTags($normalized, $normalizedKnownTags);
            if ($matchResult !== null) {
                $this->locale = $matchResult;
                return;
            }

            // Handle language-only case
            if (!str_contains($normalized, '-')) {
                if ($requireCanonical) {
                    // When requireCanonical is true, try to find a match or throw exception
                    $languageMatch = $parser->findLanguageOnlyMatch($normalized, $normalizedKnownTags);
                    if ($languageMatch !== null) {
                        $this->locale = $languageMatch;
                        return;
                    }

                    throw new InvalidArgumentException(
                        sprintf('No region found for language "%s" in known tags.', $normalized),
                    );
                }
                // When requireCanonical is false, keep the language-only locale as is
            }
        }

        // Step 3: Handle validation and fallback
        $this->locale = $this->handleValidationAndFallback($normalized, $locale, $fallbackLocale, $normalizer);
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
     * @internal
     * Get the lowercase variant of the normalized locale (e.g., `xx-xx`).
     */
    public function getLC(): string
    {
        return strtolower($this->locale);
    }

    /**
     * @internal
     * Get the uppercase variant of the normalized locale (e.g., `XX-XX`).
     */
    public function getUC(): string
    {
        return strtoupper($this->locale);
    }

    /**
     * @internal
     * Get the lowercase, underscore-separated variant (e.g., `xx_xx`).
     */
    public function getLCU(): string
    {
        return str_replace('-', '_', $this->getLC());
    }

    /**
     * @internal
     * Get the uppercase, underscore-separated variant (e.g., `XX_XX`).
     */
    public function getUCU(): string
    {
        return str_replace('-', '_', $this->getUC());
    }

    /**
     * Get the parsed tag as a value object
     *
     * @return ParsedTag|null The parsed tag, or null if parsing fails
     */
    public function getParsedTag(): ?ParsedTag
    {
        return $this->registry->parseLocale($this->locale);
    }

    public function jsonSerialize(): string
    {
        return $this->getNormalized();
    }

    /**
     * Handle validation of the locale and fallback if needed
     */
    private function handleValidationAndFallback(
        string $normalized,
        string $originalLocale,
        ?string $fallbackLocale,
        BCP47Normalizer $normalizer,
    ): string {
        // Validate the locale
        if (!$this->isValidLocale($normalized)) {
            // If invalid and we have a fallback, use it
            if ($fallbackLocale !== null) {
                $fallbackNormalized = $normalizer->normalize($fallbackLocale);
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
     * Validate the locale using the IANA Language Subtag Registry
     */
    private function isValidLocale(string $locale): bool
    {
        return $this->registry->isValidLocale($locale);
    }

    /**
     * Convert the locale to a string (`xx-XX` format by default).
     */
    public function __toString(): string
    {
        return $this->getNormalized();
    }
}
