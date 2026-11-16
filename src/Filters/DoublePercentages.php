<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 17.34
 *
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractPlaceholderHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;

/**
 * All inside percentages will be locked if there are no spaces
 *
 * TestSet:
 * <code>
 *  Dear %%customer.first_name%%, This is %agent.alias%% from Skyscanner. % this-will-not-locked % e %%ciao%% a {%this-will-not-locked%
 * </code>
 */
class DoublePercentages extends AbstractPlaceholderHandler
{
    /**
     * @inheritDoc
     */
    protected function getPattern(): string
    {
        return '/%%(?:' . self::NOT_HIDDEN . '[^<>\s%{}])+?%%/'; // removed single percentage support '/(?<!{)%[^<>\s%]+?%/'
    }

    /**
     * @inheritDoc
     */
    protected function getCType(): CTypeEnum
    {
        return CTypeEnum::PERCENTAGES;
    }
}
