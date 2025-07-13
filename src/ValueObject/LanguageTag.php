<?php

declare(strict_types=1);

namespace LHcze\BCP47\ValueObject;

use LHcze\BCP47\Exception\BCP47IanaRegistryException;
use LHcze\BCP47\Registry\IanaSubtagRegistry;

/**
 * Value object representing a valid BCP47 language tag.
 *
 * NOTE: This class is immutable and can be only created by static factory method fromValidatedParsedTag().
 */
readonly class LanguageTag extends ParsedTag
{
    private function __construct(string $language, ?string $script, ?string $region, array $variants)
    {
        parent::__construct($language, $script, $region, $variants);
    }

    /**
     * NOTE Make sure that the ParsedTag is validated using IANA subtag registry before creating a LanguageTag instance!
     *
     * You can also pass in an instance of the IanaSubtagRegistry, in such a case validation will be performed
     * automatically.
     *
     * @param ParsedTag $parsedTag The parsed tag that has been validated using IANA subtag registry
     * @param IanaSubtagRegistry|null $registry The IANA subtag registry instance to enforce validation
     * @return LanguageTag The language tag instance.
     *
     * @throws BCP47IanaRegistryException
     */
    public static function fromValidatedParsedTag(ParsedTag $parsedTag, ?IanaSubtagRegistry $registry = null): self
    {
        $registry?->validateParsedTag($parsedTag);

        return new self(
            $parsedTag->getLanguage(),
            $parsedTag->getScript(),
            $parsedTag->getRegion(),
            $parsedTag->getVariants(),
        );
    }
}
