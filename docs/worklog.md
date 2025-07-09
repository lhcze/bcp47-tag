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