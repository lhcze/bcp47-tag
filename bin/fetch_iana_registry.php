<?php
/**
 * This file is part of the BCP47tag package.
 * @see https://github.com/lhcze/bcp47-tag
 *
 * (c) <Lukas Hudecek <<hudecek.lukas@gmail.com>>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * TODO
 *  Swap array index keys for actual tag/subtag for faster lookups
 *  That would also allow adding a tag/subtag additional data such as preferred-Value, prefix, description, etc.
 *
 */

declare(strict_types=1);

require __DIR__ . '/../src/Utils/expandAlphaRangeFunction.php';;

/**
 * This script downloads the IANA Language Subtag Registry and converts it to a PHP file
 * with a return statement for faster loading in the IanaSubtagRegistry class.
 */

// URL of the IANA Language Subtag Registry
$registryUrl = 'https://www.iana.org/assignments/language-subtag-registry/language-subtag-registry';

// Output file paths
$outputFile = __DIR__ . '/../src/Resources/IanaSubtagRegistryResource.php';
$enumOutputFile = __DIR__ . '/../src/Enum/GrandfatheredTag.php';

echo "Downloading IANA Language Subtag Registry...\n";

// Use cURL instead of file_get_contents to have more control over the request
$ch = curl_init($registryUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt(
    $ch,
    CURLOPT_USERAGENT,
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$registryContent = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($registryContent === false || $httpCode !== 200) {
    echo "Error: Failed to download the registry file. HTTP Code: $httpCode, Error: $error\n";
    exit(1);
}

echo sprintf("Parsing registry data... size (Kb): %s\n", round(strlen($registryContent) / 1024, 2));

// Initialize arrays to store different types of subtags
$languages = [];
$scripts = [];
$regions = [];
$variants = [];
$grandfathered = [];

// Split the registry into sections by '%%'
$sections = explode('%%', $registryContent);

/// Process each section
foreach ($sections as $section) {
    // Skip empty sections
    if (trim($section) === '') {
        continue;
    }

    // Parse the section into key-value pairs
    $lines = explode("\n", $section);
    $data = [];
    $currentKey = null;
    $currentValue = '';

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        // Check if this is a new key
        if (preg_match('/^([A-Za-z-]+):(.*)$/', $line, $matches)) {
            // Save the previous key-value pair if it exists
            if ($currentKey !== null) {
                $data[$currentKey] = trim($currentValue);
            }

            $currentKey = trim($matches[1]);
            $currentValue = trim($matches[2]);
        } else {
            // This is a continuation of the previous value
            $currentValue .= ' ' . $line;
        }
    }

    // Save the last key-value pair
    if ($currentKey !== null) {
        $data[$currentKey] = trim($currentValue);
    }

    // Check if this section is a valid subtag entry
    if (!isset($data['Type'])) {
        continue;
    }

    $type = $data['Type'];

    // Handle grandfathered tags separately
    if ($type === 'grandfathered' && isset($data['Tag'])) {
        $grandfathered[] = strtolower($data['Tag']);
        continue;
    }

    // Handle entries with a Subtag
    if (!isset($data['Subtag'])) {
        continue;
    }

    $subtag = $data['Subtag'];

    // If the subtag is a range (e.g., 'Qaaa..Qabx'), expand it ('qaa..qtz' → ['qaa', 'qab', ..., 'qtz'])
    if (str_contains($subtag, '..')) {
        [$start, $end] = explode('..', $subtag);

        switch ($type) {
            case 'language': expandAlphaRangeFunction($languages, $start, $end, fn($s) => strtolower($s)); break;
            case 'script': expandAlphaRangeFunction($scripts, $start, $end, fn($s) => ucfirst(strtolower($s))); break;
            case 'region': expandAlphaRangeFunction($regions, $start, $end, fn($s) => strtoupper($s)); break;
        }

        continue;
    }

    // Regular single subtag entry
    switch ($type) {
        case 'language': $languages[] = strtolower($subtag); break;
        case 'script': $scripts[] = ucfirst(strtolower($subtag)); break;
        case 'region': $regions[] = strtoupper($subtag); break;
        case 'variant': $variants[] = strtolower($subtag); break;
    }
}

// Sort arrays for faster lookups
sort($languages);
sort($scripts);
sort($regions);
sort($variants);
sort($grandfathered);

// Create the final data structure
$data = [
    'languages' => array_values(array_unique($languages)),
    'scripts' => array_values(array_unique($scripts)),
    'regions' => array_values(array_unique($regions)),
    'variants' => array_values(array_unique($variants)),
    'grandfathered' => array_values(array_unique($grandfathered)),
];

$data = var_export($data, true);
if ($data === null) {
    $data = [
        'languages' => [],
        'scripts' => [],
        'regions' => [],
        'variants' => [],
        'grandfathered' => [],
    ];
}

// Generate PHP file content
$phpContent = "<?php\n\ndeclare(strict_types=1);\n\n";
$phpContent .= "// This file is auto-generated by bin/fetch_iana_registry.php\n";
$phpContent .= "// Do not edit manually. Run composer iana:refresh to update the resource file.\n\n";
$phpContent .= "return " . $data . ";\n";

// Save to a PHP file
if (file_put_contents($outputFile, $phpContent) === false) {
    echo "Error: Failed to write to output file.\n";
    exit(1);
}

echo sprintf(
    "IANA Language Tag Registry static array saved to src/Resources/IanaSubtagRegistryResource.php (%s KB)\n",
    round(strlen($phpContent) / 1024, 2),
);

// Generate Enum class for grandfathered tags
echo "Generating GrandfatheredTag Enum class...\n";

// Create enum content
$enumContent = "<?php\n\ndeclare(strict_types=1);\n\n";
$enumContent .= "namespace LHcze\\BCP47\\Enum;\n\n";
$enumContent .= "/**\n";
$enumContent .= " * Enum for grandfathered language tags from IANA Language Subtag Registry.\n";
$enumContent .= " * This file is auto-generated by bin/fetch_iana_registry.php\n";
$enumContent .= " * Do not edit manually. Run composer iana:refresh to update.\n";
$enumContent .= " */\n";
$enumContent .= "enum GrandfatheredTag: string\n";
$enumContent .= "{\n";

// Add cases for each grandfathered tag
foreach ($grandfathered as $tag) {
    // Convert tag to a valid PHP enum case name
    $caseName = str_replace(['-', '.'], '_', strtoupper($tag));

    // Ensure the case name starts with a letter or underscore
    if (!preg_match('/^[a-zA-Z_]/', $caseName)) {
        $caseName = 'TAG_' . $caseName;
    }

    // Replace any remaining invalid characters
    $caseName = preg_replace('/[^a-zA-Z0-9_]/', '_', $caseName);

    $enumContent .= "    case $caseName = '$tag';\n";
}

$enumContent .= "}\n";

// Save to a PHP file
if (file_put_contents($enumOutputFile, $enumContent) === false) {
    echo "Error: Failed to write to enum output file.\n";
    exit(1);
}

echo sprintf(
    "GrandfatheredTag Enum saved to src/Enum/GrandfatheredTag.php (%s KB)\n",
    round(strlen($enumContent) / 1024, 2),
);
echo "Done!\n";
