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
 * Locks `{text}` placeholders into `<ph>` tags.
 *
 * TestSet:
 * <code>
 *  Dear {{%%customer.first_name%%}}, This is {{ "now"|date(null, "Europe/Rome") }} with {%%agent.alias%%} Airbnb. {% for user in users %} e {%%ciao%%}
 *  {# note: disabled template because we no longer use this
 *          {% for user in users %}
 *          ...
 *          {% endfor %}
 *
 * ... {variable}
 *  #}
 * </code>
 */
class SingleCurlyBracketsToPh extends AbstractPlaceholderHandler
{
    /**
     * @inheritDoc
     */
    protected function getPattern(): string
    {
        return '/{[^<>{} ]+?}/';
    }

    /**
     * @inheritDoc
     */
    protected function getCType(): CTypeEnum
    {
        return CTypeEnum::CURLY_BRACKETS;
    }
}
