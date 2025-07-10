# BCP47Tag Project Worklog

## 1. 2025-07-09: Project Setup

1. Initialized Composer project with name "lhcze/bcp47-tag", type "library", and license "MIT"
2. Fixed namespace in composer.json to match "LHcze\\BCP47\\"
3. Added production dependencies:
   - PHP 8.3+
   - ext-intl
   - symfony/intl (^6.4)
   - symfony/validator (^6.4)
4. Added development dependencies:
   - friendsofphp/php-cs-fixer
   - php-parallel-lint/php-parallel-lint
   - phpmd/phpmd
   - phpstan/phpstan
   - phpunit/phpunit
   - psy/psysh
   - slevomat/coding-standard
   - squizlabs/php_codesniffer
5. Created base directory structure:
   - src/
   - src/Parser/
   - src/Registry/
   - src/Normalizer/
   - src/ValueObject/
   - resources/
   - tests/
6. Created empty files:
   - src/BCP47Tag.php
   - src/Parser/BCP47Parser.php
   - src/Registry/IanaSubtagRegistry.php
   - src/Normalizer/BCP47Normalizer.php
   - src/ValueObject/ParsedTag.php
   - resources/iana.json (with dummy '{}')
7. Created test skeletons:
   - tests/BCP47TagTest.php
   - tests/Parser/BCP47ParserTest.php
   - tests/Registry/IanaSubtagRegistryTest.php
   - tests/Normalizer/BCP47NormalizerTest.php
8. Added autoload-dev section to composer.json for tests
9. Ran composer dump-autoload and validated the composer.json file
10. Confirmed PHPUnit runs (with expected errors due to empty class skeletons)

Project structure is now ready for implementing the actual functionality.

## 2. 2025-07-09: QA tools and initial code quality
1. Updated version of dev required tools to current major + wildcard
2. added qa-related script into composer for quickly running  phpcbf, phpcs, parallel, phpmd, phpstan & phpunit
3. added code standards / rulesets into qatools
4. matched the code quality according to qatools
5. all unit tests passed
6. git initialized and current state commited

Project is ready for the major refactor.

## 3. 2025-07-10: Modularize BCP47Tag Internals

1. Implemented BCP47Normalizer class with:
   - normalize(string $locale): string - Moved the normalizeLocale logic from BCP47Tag

2. Implemented BCP47Parser class with:
   - parseSupportedLocales(array $supportedLocales): array - Normalizes supported locales using BCP47Normalizer
   - findMatchInSupportedLocales(string $normalized, array $supported): ?string - Finds exact or case-insensitive matches
   - findLanguageOnlyMatch(string $language, array $supported): ?string - Finds matches for language-only locales

3. Created IanaSubtagRegistry stub with:
   - isValidLocale(string $locale): bool - Simple stub method for future implementation

4. Refactored BCP47Tag to use the new helper classes:
   - Removed private methods that were moved to helper classes
   - Added dependencies on BCP47Normalizer and BCP47Parser
   - Kept the public API exactly the same
   - Added TODO comment to replace isValidLocale with IanaSubtagRegistry later

5. Ran tests and code quality tools:
   - All 33 tests passed with 67 assertions
   - Fixed code style issues with composer cbf
   - All quality checks passed (phpcs, lint, phpmd, phpstan level 8, phpunit)

The refactoring successfully modularized the BCP47Tag internals while maintaining the same external behavior, setting the foundation for full RFC 5646 compliance later.

## 4. 2025-07-11: Implement IANA Subtag Registry

1. Created a script to download and parse the IANA Language Subtag Registry:
   - Created bin/fetch_iana_registry.php
   - Implemented downloading from the official URL using cURL
   - Parsed the registry into sections by '%%'
   - Extracted subtags by type (language, script, region, variant, grandfathered)
   - Saved as JSON to resources/iana.json

2. Implemented the IanaSubtagRegistry class:
   - Added private readonly arrays for languages, scripts, regions, variants, grandfathered
   - Implemented static loadFromFile method to load the JSON data
   - Implemented validation methods for each subtag type (isValidLanguage, isValidScript, isValidRegion, isValidVariant, isGrandfathered)
   - Implemented isValidLocale method to validate a complete locale using the parser

3. Implemented the ParsedTag value object:
   - Added readonly properties for language, script, region, variants
   - Added getters for all properties
   - Added helper methods (hasScript, hasRegion, hasVariants)

4. Implemented BCP47Parser.parseTag method:
   - Added method to parse a locale string into a ParsedTag object
   - Handled language, script, region, and variants
   - Added TODO for handling extensions and private use in future versions

5. Updated BCP47Tag to use IanaSubtagRegistry:
   - Added IanaSubtagRegistry property
   - Loaded the registry in constructor
   - Updated handleValidationAndFallback to use the registry
   - Replaced isValidLocale method with one that uses the registry
   - Removed Symfony Intl and Validator dependencies

6. Added tests for the new functionality:
   - Tests for IanaSubtagRegistry loading and validation
   - Tests for BCP47Parser.parseTag
   - Tests for BCP47Tag with real registry
   - Added test for grandfathered tag
   - Added stubs for extension and private-use tests

7. Updated documentation:
   - Created README.md with usage examples and feature list
   - Updated worklog.md

8. Ran tests and quality checks:
   - All tests passed
   - Code quality checks passed

The implementation now uses the official IANA Language Subtag Registry for validation, making it more accurate and compliant with the BCP 47 standard. The foundation is set for supporting extensions and private use subtags in the future.

## 5. 2025-07-12: Standardize Naming to Match BCP 47 Terminology

1. Updated BCP47Tag constructor parameters:
   - Renamed `$supportedLocales` to `$knownTags` to match BCP 47 terminology
   - Renamed `$regionRequired` to `$requireCanonical` for clarity and consistency

2. Updated internal references in BCP47Tag:
   - Renamed `$supportedLocales` to `$knownTags`
   - Renamed `$normalizedSupportedLocales` to `$normalizedKnownTags`
   - Renamed `$regionRequired` to `$requireCanonical`
   - Updated error messages to use "known tags" instead of "supported locales"

3. Updated BCP47Parser method names and parameters:
   - Renamed `parseSupportedLocales()` to `parseKnownTags()`
   - Renamed `findMatchInSupportedLocales()` to `findMatchInKnownTags()`
   - Updated parameter names and variable names to use "knownTags" consistently

4. Updated tests to match the new terminology:
   - Updated test method names and data providers
   - Updated parameter names in test methods
   - Updated exception messages in tests
   - Updated test data keys to use "require canonical" instead of "region required"

5. Ran tests and quality checks:
   - All 67 tests passed with 130 assertions
   - Code style checks passed
   - Some PHPStan issues were identified for future improvement

The naming standardization ensures that the library uses consistent terminology that matches the official BCP 47 specification, making the API more intuitive for developers familiar with the standard.

## 6. 2025-07-13: Add ParsedTag Access Method

1. Added a new method to IanaSubtagRegistry:
   - parseLocale(string $locale): ?ParsedTag - Parses a locale string into a ParsedTag object

2. Added a new method to BCP47Tag:
   - getParsedTag(): ?ParsedTag - Returns the ParsedTag value object for the current locale

3. Added tests for the new functionality:
   - Added test for getParsedTag method
   - Added data provider for various locale formats

4. Updated documentation:
   - Updated worklog.md to document the changes

5. Ran tests and quality checks:
   - All tests passed
   - Code quality checks passed

This enhancement allows users to access the parsed components of a BCP47 tag directly, providing more flexibility when working with language tags in applications.

## 7. 2025-07-14: Optimize ParsedTag Creation in IanaSubtagRegistry

1. Refactored IanaSubtagRegistry to avoid duplicate ParsedTag creation:
   - Added private parseTagInternal(string $locale): ?ParsedTag method
   - Modified parseLocale() and isValidLocale() to use the shared parseTagInternal() method
   - Ensured ParsedTag is created only once when both methods are called for the same locale

2. Improved type handling in isValidLocale():
   - Added proper null checks before accessing ParsedTag properties
   - Ensured type safety when passing values to validation methods

3. Ran tests and quality checks:
   - All 72 tests passed with 155 assertions
   - No regressions introduced by the changes

This optimization improves performance by avoiding redundant parsing operations and ensures consistent behavior between the parseLocale() and isValidLocale() methods.
