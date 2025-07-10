<?php

declare(strict_types=1);

namespace LHcze\BCP47\Registry;

use LHcze\BCP47\Normalizer\BCP47Normalizer;
use LHcze\BCP47\Parser\BCP47Parser;
use LHcze\BCP47\ValueObject\ParsedTag;
use RuntimeException;
use Throwable;

final class IanaSubtagRegistry
{
    /**
     * Array of valid language subtags
     * @var string[]
     */
    private readonly array $languages;

    /**
     * Array of valid script subtags
     * @var string[]
     */
    private readonly array $scripts;

    /**
     * Array of valid region subtags
     * @var string[]
     */
    private readonly array $regions;

    /**
     * Array of valid variant subtags
     * @var string[]
     */
    private readonly array $variants;

    /**
     * Array of grandfathered tags
     * @var string[]
     */
    private readonly array $grandfathered;

    /**
     * Parser for BCP47 tags
     */
    private readonly BCP47Parser $parser;

    /**
     * Static cache for the registry data
     * @var array<string, array<string>>|null
     */
    private static ?array $cache = null;

    /**
     * Static instance of the registry
     */
    private static ?self $instance = null;

    /**
     * Private constructor to enforce using the static factory method
     *
     * @param array<string> $languages Array of language subtags
     * @param array<string> $scripts Array of script subtags
     * @param array<string> $regions Array of region subtags
     * @param array<string> $variants Array of variant subtags
     * @param array<string> $grandfathered Array of grandfathered tags
     * @param BCP47Parser $parser Parser for BCP47 tags
     */
    private function __construct(
        array $languages,
        array $scripts,
        array $regions,
        array $variants,
        array $grandfathered,
        BCP47Parser $parser,
    ) {
        $this->languages = $languages;
        $this->scripts = $scripts;
        $this->regions = $regions;
        $this->variants = $variants;
        $this->grandfathered = $grandfathered;
        $this->parser = $parser;
    }

    /**
     * Load the registry data
     *
     * @param BCP47Parser|null $parser Optional parser instance (will create one if not provided)
     * @return self The registry instance
     */
    public static function load(?BCP47Parser $parser = null): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if (self::$cache === null) {
            $registryFile = __DIR__ . '/../Resources/IanaSubtagRegistry.php';
            if (!file_exists($registryFile)) {
                throw new RuntimeException("Registry file not found: $registryFile");
            }

            self::$cache = require $registryFile;
        }

        // Create a parser if one wasn't provided
        if ($parser === null) {
            $normalizer = new BCP47Normalizer();
            $parser = new BCP47Parser($normalizer);
        }

        self::$instance = new self(
            self::$cache['languages'] ?? [],
            self::$cache['scripts'] ?? [],
            self::$cache['regions'] ?? [],
            self::$cache['variants'] ?? [],
            self::$cache['grandfathered'] ?? [],
            $parser,
        );

        return self::$instance;
    }

    /**
     * For backward compatibility - load from a JSON file
     *
     * @param string $path Path to the JSON file
     * @param BCP47Parser|null $parser Optional parser instance (will create one if not provided)
     * @throws RuntimeException If the file cannot be read or parsed
     * @deprecated Use load() instead
     */
    public static function loadFromFile(string $path, ?BCP47Parser $parser = null): self
    {
        return self::load($parser);
    }

    /**
     * Check if a language subtag is valid
     *
     * @param string $language Language subtag to check
     * @return bool True if the language is valid
     */
    public function isValidLanguage(string $language): bool
    {
        return in_array(strtolower($language), $this->languages, true);
    }

    /**
     * Check if a script subtag is valid
     *
     * @param string $script Script subtag to check
     * @return bool True if the script is valid
     */
    public function isValidScript(string $script): bool
    {
        return in_array(ucfirst(strtolower($script)), $this->scripts, true);
    }

    /**
     * Check if a region subtag is valid
     *
     * @param string $region Region subtag to check
     * @return bool True if the region is valid
     */
    public function isValidRegion(string $region): bool
    {
        return in_array(strtoupper($region), $this->regions, true);
    }

    /**
     * Check if a variant subtag is valid
     *
     * @param string $variant Variant subtag to check
     * @return bool True if the variant is valid
     */
    public function isValidVariant(string $variant): bool
    {
        return in_array(strtolower($variant), $this->variants, true);
    }

    /**
     * Check if a tag is a grandfathered tag
     *
     * @param string $tag Tag to check
     * @return bool True if the tag is grandfathered
     */
    public function isGrandfathered(string $tag): bool
    {
        return in_array(strtolower($tag), $this->grandfathered, true);
    }

    /**
     * Parse a locale string into a ParsedTag object
     *
     * @param string $locale The locale string to parse
     * @return ParsedTag|null The parsed tag, or null if parsing fails
     */
    public function parseLocale(string $locale): ?ParsedTag
    {
        return $this->parseTagInternal($locale);
    }

    /**
     * Check if a locale is valid according to IANA registry
     *
     * @param string $locale Locale to check
     * @return bool True if the locale is valid
     */
    public function isValidLocale(string $locale): bool
    {
        // First check if it's a grandfathered tag
        if ($this->isGrandfathered($locale)) {
            return true;
        }

        // Parse the locale into its components
        $parsedTag = $this->parseTagInternal($locale);

        // If parsing failed, the locale is invalid
        if ($parsedTag === null) {
            return false;
        }

        // Validate each component
        if (!$this->isValidLanguage($parsedTag->getLanguage())) {
            return false;
        }

        $script = $parsedTag->getScript();
        if ($script !== null && !$this->isValidScript($script)) {
            return false;
        }

        $region = $parsedTag->getRegion();
        if ($region !== null && !$this->isValidRegion($region)) {
            return false;
        }

        // Validate all variants
        foreach ($parsedTag->getVariants() as $variant) {
            if (!$this->isValidVariant($variant)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Internal method to parse a locale string into a ParsedTag object
     * This is used by both parseLocale() and isValidLocale() to avoid duplicate parsing
     *
     * @param string $locale The locale string to parse
     * @return ParsedTag|null The parsed tag, or null if parsing fails
     */
    private function parseTagInternal(string $locale): ?ParsedTag
    {
        try {
            return $this->parser->parseTag($locale);
        } catch (Throwable) {
            return null;
        }
    }
}
