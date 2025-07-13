<?php

declare(strict_types=1);

namespace LHcze\BCP47\Registry;

use LHcze\BCP47\Exception\BCP47IanaRegistryException;
use LHcze\BCP47\ValueObject\ParsedTag;

final class IanaSubtagRegistry
{
    /**
     *
     * Static cache for the registry data.
     * Default yet empty structure ensures the safety.
     *
     * NOTE: keep the order of the keys in the array the same as arguments in the __construct() method!
     *
     * @var array<string,string[]>
     */
    private static array $cache = [
        'languages' => [],
        'scripts' => [],
        'regions' => [],
        'variants' => [],
        'grandfathered' => [],
    ];

    /**
     * Static instance of the registry
     */
    private static ?self $instance = null;

    /**
     * IANA Language Subtag Registry [RFC5646]
     * Registry file: https://www.iana.org/assignments/language-subtag-registry
     *
     * TODO
     *  Validates that each of the components exists in the IANA LSR registry, however, some variants predicate that
     *  the region or language subtag (prefix) is mandatory.
     *  Same goes for the language suppress-script subtag, preferred, collection,...
     *  This is not in the current scope of this library, but its planned to be added in the future.
     * @see https://www.reecedunn.co.uk/schema/2013/iana
     *
     * In the future, this registry should support additional IANA properties:
     * - 'extlang' An ISO 639-3 language code that belongs to a macrolanguage.
     * - 'comments' Notes about the subtag.
     * - 'deprecated' The date at which the subtag was deprecated.
     * - 'description'
     * - 'macrolanguage' The associated parent language.
     * - 'preferred-Value' The value that should be used instead of this subtag.
     * - 'prefix' The subtag occurring before this subtag.
     * - 'supress-script' The default script for the subtag.
     *
     * Private constructor to enforce using the static factory method
     *
     * @param string[] $languages Array of language subtags
     * @param string[] $scripts Array of script subtags
     * @param string[] $regions Array of region subtags
     * @param string[] $variants Array of variant subtags
     * @param string[] $grandfathered Array of grandfathered tags
     */
    private function __construct(
        private readonly array $languages,
        private readonly array $scripts,
        private readonly array $regions,
        private readonly array $variants,
        private readonly array $grandfathered,
    ) {
    }

    /**
     * Load the registry data and populates the cache.
     *
     * @return IanaSubtagRegistry The registry instance with the loaded data.
     *
     * @throws BCP47IanaRegistryException
     */
    public static function load(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $registryFile = __DIR__ . '/../Resources/IanaSubtagRegistryResource.php';
        if (!file_exists($registryFile)) {
            throw new BCP47IanaRegistryException("Registry file not found: $registryFile");
        }

        $registryData = require $registryFile;
        if (!is_array($registryData)) {
            throw new BCP47IanaRegistryException("Registry resource file must return an array: $registryFile");
        }

        self::cacheRegistryData($registryData);

        return self::$instance = new IanaSubtagRegistry(...self::$cache);
    }

    /**
     * Check if a language subtag is valid
     *
     * @param string $language Language subtag to check
     * @return bool True if the language is valid
     */
    public function isValidLanguage(string $language): bool
    {
        return in_array($language, $this->languages, true);
    }

    /**
     * Check if a script subtag is valid
     *
     * @param string $script Script subtag to check
     * @return bool True if the script is valid
     */
    public function isValidScript(string $script): bool
    {
        return in_array($script, $this->scripts, true);
    }

    /**
     * Check if a region subtag is valid
     *
     * @param string $region Region subtag to check
     * @return bool True if the region is valid
     */
    public function isValidRegion(string $region): bool
    {
        return in_array($region, $this->regions, true);
    }

    /**
     * Check if a variant subtag is valid
     *
     * @param string $variant Variant subtag to check
     * @return bool True if the variant is valid
     */
    public function isValidVariant(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }

    /**
     * Check if a tag is a grandfathered tag
     *
     * @param string $tag Tag to check
     * @return bool True if the tag is grandfathered
     */
    public function isGrandfathered(string $tag): bool
    {
        return in_array($tag, $this->grandfathered, true);
    }

    /**
     * Check if a locale is valid, according to IANA registry
     *
     * @param ParsedTag $parsedTag Locale to check
     * @param bool $requireRegion If true, the locale region subtag is mandatory. Otherwise optional
     * @return bool True if the locale is valid
     */
    public function isValidParsedTag(
        ParsedTag $parsedTag,
        bool $requireRegion = false,
        bool $requireScript = false,
    ): bool {
        // First, check if it's a grandfathered tag
        if ($this->isGrandfathered((string) $parsedTag)) {
            return true;
        }

        // Validate each component
        if (!$this->isValidLanguage($parsedTag->getLanguage())) {
            return false;
        }

        $region = $parsedTag->getRegion();
        if (($requireRegion === true && ($region === null || !$this->isValidRegion($region))) ||
            ($region !== null && !$this->isValidRegion($region))
        ) {
            return false;
        }

        $script = $parsedTag->getScript();
        if (($requireScript === true && ($script === null || !$this->isValidscript($script))) ||
            ($script !== null && !$this->isValidscript($script))
        ) {
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
     * @throws BCP47IanaRegistryException
     */
    public function validateParsedTag(ParsedTag $parsedTag): void
    {
        if (!$this->isValidParsedTag($parsedTag)) {
            throw new BCP47IanaRegistryException('Invalid parsed tag');
        }
    }

    /**
     * Validate and put registry data into the static cache
     *
     * @param mixed[] $registryData The registry data to cache
     *
     * @SuppressWarnings("PHPMD.UnusedLocalVariable")
     */
    private static function cacheRegistryData(array $registryData): void
    {
        // Only allow known keys
        /** @var array<string, array<string[]>> $allowedKeys */
        $allowedKeys = array_flip(array_keys(self::$cache));

        foreach ($registryData as $rawKey => $items) {
            $part = strtolower((string) $rawKey);

            if (!isset($allowedKeys[$part]) || !is_array($items)) {
                continue; // skip unwanted and invalid keys
            }

            // Duplicate values but before, convert them to strings
            $deduplicated = array_flip(
                array_map(
                    static fn($v) => (string) $v,
                    array_filter($items, static fn($v) => is_numeric($v) || is_string($v)),
                ),
            );

            // Sort the values by their original index
            foreach ($deduplicated as $value => $originalIndex) {
                self::$cache[$part][] = (string) $value;
            }
        }
    }
}
