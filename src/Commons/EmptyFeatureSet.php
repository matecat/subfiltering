<?php
/**
 * Created by PhpStorm.
 * @author ostico domenico@translated.net / ostico@gmail.com
 * Date: 02/03/22
 * Time: 18:39
 *
 */

namespace Matecat\SubFiltering\Commons;

use Matecat\SubFiltering\Contracts\FeatureSetInterface;

/**
 * Used from sources which want not to implement a custom object from this package
 */
class EmptyFeatureSet implements FeatureSetInterface
{
    public function customizeFromLayer0ToLayer1(Pipeline $pipeline): Pipeline
    {
        return $pipeline;
    }

    public function customizeFromLayer1ToLayer2(Pipeline $pipeline): Pipeline
    {
        return $pipeline;
    }

    public function customizeFromLayer2ToLayer1(Pipeline $pipeline): Pipeline
    {
        return $pipeline;
    }

    public function customizeFromRawXliffToLayer0(Pipeline $pipeline): Pipeline
    {
        return $pipeline;
    }

    public function customizeFromLayer0ToRawXliff(Pipeline $pipeline): Pipeline
    {
        return $pipeline;
    }

    public function customizeFromLayer1ToLayer0(Pipeline $pipeline): Pipeline
    {
        return $pipeline;
    }
}
