<?php

declare(strict_types=1);

namespace LHcze\BCP47;

use JsonSerializable;
use LHcze\BCP47\Exception\BCP47IanaRegistryException;
use LHcze\BCP47\Exception\BCP47InvalidArgumentException;
use LHcze\BCP47\Exception\BCP47InvalidFallbackLocaleException;
use LHcze\BCP47\Exception\BCP47InvalidLocaleException;
use LHcze\BCP47\Exception\BCP47InvalidMatchingTagException;
use LHcze\BCP47\Exception\BCP47ParserException;
use LHcze\BCP47\Normalizer\BCP47Normalizer;
use LHcze\BCP47\Parser\BCP47Parser;
use LHcze\BCP47\Registry\IanaSubtagRegistry;
use LHcze\BCP47\ValueObject\LanguageTag;
use LHcze\BCP47\ValueObject\ParsedTag;
use Stringable;

final readonly class BCP47Tag implements Stringable, JsonSerializable
{
    /** RFC 5646 representation of a lang-tag */
    private LanguageTag $locale;

    /** IANA Language Subtag Registry */
    private IanaSubtagRegistry $registry;

    /** BCP47 Normalizer */
    private BCP47Normalizer $normalizer;

    /** BCP47 Parser */
    private BCP47Parser $parser;

    /**
     * Create a new BCP47Tag instance with a LanguageTag value object
     *
     * It's intended to carry the BCP47Tag instance around as is, but you can also use LanguageTag directly if you
     * want something more lightweight and verbally simple.
     *
     * NOTE: One specific behavior for fallback locale is, that when the inputLocale is valid, but not to be found
     * in the matchTags, the fallback won't be used.
     * This is because the purpose of fallback is to only replace inputLocale if it's not valid.
     *
     * @param string $inputLocale Raw string locale input
     * @param string|null $fallbackLocale If everything fails, use this locale instead
     * @param string[]|null $useCanonicalMatchTags Locale must match or resolve to one of the canonical tags in the list
     *
     * @throws BCP47InvalidLocaleException When the locale is invalid and no fallback is provided
     * @throws BCP47InvalidFallbackLocaleException When the locale is invalid and so is the provided fallback
     * @throws BCP47InvalidMatchingTagException When one of the provided known tags is invalid
     * @throws BCP47IanaRegistryException When the IANA registry cannot be loaded
     * @throws BCP47InvalidArgumentException
     */
    public function __construct(
        private string $inputLocale,
        ?string $fallbackLocale = null,
        ?array $useCanonicalMatchTags = null,
    ) {
        // Initialize hard-working class objects
        $this->normalizer = new BCP47Normalizer();
        $this->parser = new BCP47Parser($this->normalizer);

        $this->validateOptionalArguments($fallbackLocale, $useCanonicalMatchTags);

        // Load the IANA registry
        $this->registry = IanaSubtagRegistry::load();

        $languageTag = $this->handleValidationNormalizationAndFallback($this->inputLocale, $fallbackLocale);

        // Function is enabled if it's not null
        if ($useCanonicalMatchTags !== null) {
            $languageTag = $this->matchCanonicalTag($languageTag, $useCanonicalMatchTags);
        }

        // Final version of the locale value object
        $this->locale = $languageTag;
    }

    public function getInputLocale(): string
    {
        return $this->inputLocale;
    }

    /**
     * Get the normalized locale
     */
    public function getNormalized(): string
    {
        return (string) $this->locale;
    }

    /**
     * Get the underscore-separated format (`xx_XX`).
     */
    public function getICUformat(): string
    {
        return str_replace('-', '_', (string) $this->locale);
    }

    /**
     * @internal
     * Get the lowercase variant of the normalized locale (e.g., `xx-xx`).
     */
    public function getLC(): string
    {
        return strtolower((string) $this->locale);
    }

    /**
     * @internal
     * Get the uppercase variant of the normalized locale (e.g., `XX-XX`).
     */
    public function getUC(): string
    {
        return strtoupper((string) $this->locale);
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
     * @return LanguageTag Language tag value object
     */
    public function getLanguageTag(): LanguageTag
    {
        return $this->locale;
    }

    public function jsonSerialize(): string
    {
        return $this->getNormalized();
    }

    /**
     * If matchTags and fallback are both provided, validate that fallback is one of the matchTags.
     * Before the search, normalize both arguments.
     *
     * @param string[]|null $useCanonicalMatchTags
     * @throws BCP47InvalidArgumentException
     */
    public function validateOptionalArguments(?string $fallbackLocale, ?array $useCanonicalMatchTags): void
    {
        if ($fallbackLocale === null || $useCanonicalMatchTags === null) {
            return;
        }

        $fallbackLocale = $this->normalizer->normalize($fallbackLocale);
        $useCanonicalMatchTags = $this->parser->parseMatchTags($useCanonicalMatchTags);

        if (!in_array($fallbackLocale, $useCanonicalMatchTags, true)) {
            throw new BCP47InvalidArgumentException(sprintf(
                'If you want to use matchTags and fallback locale, the fallback must be one of the matchTags. 
				Fallback: "%s", matchTags: "%s"',
                $fallbackLocale,
                implode(', ', $useCanonicalMatchTags),
            ));
        }
    }

    /**
     * Handle validation of the locale and fallback if needed
     * @throws BCP47InvalidLocaleException
     * @throws BCP47InvalidFallbackLocaleException
     * @throws BCP47IanaRegistryException
     */
    private function handleValidationNormalizationAndFallback(
        string $inputLocale,
        ?string $fallbackLocale,
    ): LanguageTag {
        $parsedTag = $this->getValidParsedTag($inputLocale);

        if ($parsedTag === null) {
            if ($fallbackLocale === null) {
                throw new BCP47InvalidLocaleException(
                    sprintf('Invalid locale format: "%s". No fallback to go to.', $inputLocale),
                );
            }
            $parsedTag = $this->getValidParsedTag($fallbackLocale);

            if ($parsedTag === null) {
                throw new BCP47InvalidFallbackLocaleException(
                    sprintf(
                        'Both locale "%s" and fallback locale "%s" are invalid.',
                        $inputLocale,
                        $fallbackLocale,
                    ),
                );
            }
        }

        return LanguageTag::fromValidatedParsedTag($parsedTag);
    }

    private function getValidParsedTag(string $languageTag): ?ParsedTag
    {
        try {
            $parsedTag = $this->parser->parseTag($languageTag);
            if ($this->registry->isValidParsedTag($parsedTag)) {
                return $parsedTag;
            }
        } catch (BCP47ParserException) {
            // Intentionally left empty
        }
        return null;
    }

    /**
     * @throws BCP47ParserException
     * @throws BCP47IanaRegistryException
     */
    private function getParsedTag(string $languageTag): ParsedTag
    {
        $parsedTag = $this->parser->parseTag($languageTag);
        $this->registry->validateParsedTag($parsedTag);

        return $parsedTag;
    }

    /**
     * Normalize, validate and evaluate best matching language tag
     *
     * @param LanguageTag $languageTag Language tag to match
     * @param string[] $canonicalMatchTags List of canonical tags to match against
     *
     * @return LanguageTag The best matching language tag
     * @throws BCP47InvalidMatchingTagException
     */
    private function matchCanonicalTag(LanguageTag $languageTag, array $canonicalMatchTags): LanguageTag
    {
        $bestMatch = null;
        $bestScore = -1;

        foreach ($canonicalMatchTags as $matchingTag) {
            $candidateTag = $this->parseMatchingTag($matchingTag);

            // Basic language must-match — skip otherwise
            if ($languageTag->getLanguage() !== $candidateTag->getLanguage()) {
                continue;
            }

            $score = $this->calculateScore($languageTag, $candidateTag);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $candidateTag;
            }
        }

        if ($bestMatch === null) {
            throw new BCP47InvalidMatchingTagException(sprintf(
                'No matching language tag (%s) found in known tags. Provided: %s',
                $languageTag,
                implode(', ', $canonicalMatchTags),
            ));
        }

        return LanguageTag::fromValidatedParsedTag($bestMatch);
    }

    private function calculateScore(LanguageTag $a, ParsedTag $b): int
    {
        // A hundred points for a language match
        $score = 100;

        // +10 if a region matches
        if ($a->getRegion() !== null && $b->getRegion() !== null && $a->getRegion() === $b->getRegion()) {
            $score += 10;
        }

        // +1 if a script matches
        if ($a->getScript() !== null && $b->getScript() !== null && $a->getScript() === $b->getScript()) {
            $score += 1;
        }

        return $score;
    }

    /**
     * @throws BCP47InvalidMatchingTagException
     */
    private function parseMatchingTag(string $matchingTag): ParsedTag
    {
        try {
            return $this->getParsedTag($matchingTag);
        } catch (BCP47IanaRegistryException $e) {
            throw new BCP47InvalidMatchingTagException(
                sprintf('Matching language tag "%s" is not a valid IANA tag.', $matchingTag),
                previous: $e,
            );
        } catch (BCP47ParserException $e) {
            throw new BCP47InvalidMatchingTagException(
                sprintf('Matching language tag "%s" input is invalid.', $matchingTag),
                previous: $e,
            );
        }
    }

    /**
     * Convert the locale to a string (`xx-XX` format by default).
     */
    public function __toString(): string
    {
        return $this->getNormalized();
    }
}
