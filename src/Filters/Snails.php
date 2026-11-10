<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractPlaceholderHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;

/**
 * Locks `@@text@@` placeholders into `<ph>` tags.
 */
class Snails extends AbstractPlaceholderHandler
{
    /**
     * @inheritDoc
     */
    protected function getPattern(): string
    {
        return '/@@[^<>\s]+?@@/u';
    }

    /**
     * @inheritDoc
     */
    protected function getCType(): CTypeEnum
    {
        return CTypeEnum::SNAILS;
    }
}
