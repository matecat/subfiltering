<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractPlaceholderHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;

/**
 * Locks `[[text]]` placeholders into `<ph>` tags.
 *
 * The brackets must hold no whitespace and no markup. {@see AbstractPlaceholderHandler} also
 * leaves alone whitespace written as an entity, so `[[a&#09;b]]`, `[[a&#13;b]]` and
 * `[[a&#160;b]]` stay as text.
 */
class DoubleSquareBrackets extends AbstractPlaceholderHandler
{
    /**
     * @inheritDoc
     */
    protected function getPattern(): string
    {
        return '/\[\[[^<>\s]+?\]\]/u';
    }

    /**
     * @inheritDoc
     */
    protected function getCType(): CTypeEnum
    {
        return CTypeEnum::DOUBLE_SQUARE_BRACKETS;
    }
}
