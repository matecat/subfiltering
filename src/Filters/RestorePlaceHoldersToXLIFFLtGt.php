<?php
/**
 * This file contains the RestorePlaceHoldersToXLIFFLtGt class, which is responsible
 * for restoring escaped greater-than and less-than characters in a string.
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;

/**
 * A filter handler that restores placeholders for angle brackets back to their original characters.
 *
 * This handler is a crucial part of the reverse transformation process. During sub-filtering,
 * literal '<' and '>' characters are often converted into safe placeholders (e.g., '##LESSTHAN##'
 * and '##GREATERTHAN##') to prevent them from being misinterpreted as XML/HTML tags.
 *
 * This class reverses that process, ensuring that the final output string has the correct
 * angle bracket characters, making it suitable for rendering or final storage where
 * these characters are expected.
 */
class RestorePlaceHoldersToXLIFFLtGt extends AbstractHandler
{

    /**
     * Replaces temporary placeholders for less-than and greater-than characters with
     * their original '<' and '>' equivalents.
     *
     * @param string $segment The input string containing placeholders.
     *
     * @return string The transformed string with '<' and '>' characters restored.
     */
    public function transform(string $segment): string
    {
        // Replace the less-than placeholder (e.g., '##LESSTHAN##') with the actual '<' character.
        $segment = str_replace(ConstantEnum::LTPLACEHOLDER, "<", $segment);

        // Replace the greater-than placeholder (e.g., '##GREATERTHAN##') with the actual '>' character.
        return str_replace(ConstantEnum::GTPLACEHOLDER, ">", $segment);
    }
}