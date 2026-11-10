<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractPlaceholderHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;

/**
 * Locks `[text]` placeholders into `<ph>` tags.
 *
 * The brackets must hold no whitespace, no markup and no further brackets, so `[a b]` and
 * `[[text]]` are left alone; `[[text]]` belongs to {@see DoubleSquareBrackets}.
 *
 * The lookarounds reject a bracket that is doubled on either side:
 * - the lookbehind `(?<!\[)` leaves `[[text]` alone;
 * - the lookahead `(?!])` leaves `[text]]` alone, and does not take the `[b]` in `[[a][b]]`.
 *
 * They only look at the brackets next to the match. A `[x]` nested deeper inside double
 * brackets, such as the `[a]` in `[[x[a]y]]`, is still locked when double_square is disabled.
 */
class SingleSquareBrackets extends AbstractPlaceholderHandler
{
    /**
     * @inheritDoc
     */
    protected function getPattern(): string
    {
        return '/(?<!\[)\[[^\[\]<>\s]+?](?!])/u';
    }

    /**
     * @inheritDoc
     */
    protected function getCType(): CTypeEnum
    {
        return CTypeEnum::SINGLE_SQUARE_BRACKETS;
    }
}
