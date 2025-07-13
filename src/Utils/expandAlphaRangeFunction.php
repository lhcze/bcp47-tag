<?php

declare(strict_types=1);

/**
 * @internal This function is used internally by the fetch_iana_registry.php script.

 * Expand an alphabetical range like 'aa..zz' or 'Qaaa..qabx'
 *
 * @param string[] $append The array to append the generated values to.
 * @param string $start Starting code (inclusive)
 * @param string $end Ending code (inclusive)
 * @param Closure(string): string $modifier A function to apply to each generated value
 */
function expandAlphaRangeFunction(array &$append, string $start, string $end, Closure $modifier): void
{
    $current = $start;

    while (true) {
        $append[] = $modifier($current);

        if ($current === $end) {
            break;
        }

        // Increment the string
        $chars = str_split($current);
        for ($i = count($chars) - 1; $i >= 0; $i--) {
            if ($chars[$i] === 'z') {
                $chars[$i] = 'a';
            } elseif ($chars[$i] === 'Z') {
                $chars[$i] = 'A';
            } else {
                $chars[$i] = chr(ord($chars[$i]) + 1);
                break;
            }
        }

        $current = implode('', $chars);
    }
}
