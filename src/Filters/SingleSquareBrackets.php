<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;
use Matecat\SubFiltering\Enum\CTypeEnum;

/**
 * Locks `[text]` placeholders into `<ph>` tags.
 *
 * The brackets must hold no whitespace, no markup and no further brackets, so `[a b]` and
 * `[[text]]` are left alone; `[[text]]` belongs to {@see DoubleSquareBrackets}. Whitespace
 * written as an entity counts too: Layer 0 stores tabs and newlines as `&#09;`, `&#10;`, `&#13;`
 * (see {@see EncodeControlCharsInXliff}), so `[a&#09;b]` and `[a&#160;b]` are left alone as well.
 *
 * The lookarounds reject a bracket that is doubled on either side:
 * - the lookbehind `(?<!\[)` leaves `[[text]` alone;
 * - the lookahead `(?!])` leaves `[text]]` alone, and does not take the `[b]` in `[[a][b]]`.
 *
 * They only look at the brackets next to the match. A `[x]` nested deeper inside double
 * brackets, such as the `[a]` in `[[x[a]y]]`, is still locked when double_square is disabled.
 */
class SingleSquareBrackets extends AbstractHandler
{
    private const string PATTERN = '/(?<!\[)\[[^\[\]<>\s]++](?!])/u';

    /**
     * @inheritDoc
     */
    public function transform(string $segment): string
    {
        // Each match is replaced where it was found. Replacing the first literal
        // occurrence instead would hit the inner `[x]` of a preceding `[[x]]`.
        return preg_replace_callback(
            self::PATTERN,
            function (array $match): string {
                // An escaped tag inside the brackets must not be swallowed into the placeholder
                if (
                    str_contains($match[0], ConstantEnum::LTPLACEHOLDER->value)
                    || str_contains($match[0], ConstantEnum::GTPLACEHOLDER->value)
                ) {
                    return $match[0];
                }

                // Whitespace written as an entity (`&#09;`, `&#10;`, `&#160;`, ...) is not seen by `\s`
                if (preg_match('/\s/u', html_entity_decode($match[0], ENT_QUOTES | ENT_HTML5, 'UTF-8'))) {
                    return $match[0];
                }

                return '<ph id="' . $this->getPipeline()->getNextId()
                    . '" ctype="' . CTypeEnum::SINGLE_SQUARE_BRACKETS->value
                    . '" equiv-text="base64:' . base64_encode($match[0]) . '"/>';
            },
            $segment
        ) ?? $segment;
    }
}
