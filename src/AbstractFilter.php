<?php

namespace Matecat\SubFiltering;

use Matecat\SubFiltering\Contracts\FeatureSetInterface;

abstract class AbstractFilter
{
    /**
     * @var AbstractFilter
     */
    protected static $_INSTANCE;

    /**
     * @var FeatureSetInterface
     */
    protected $featureSet;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var string
     */
    protected $target;

    /**
     * @var array
     */
    protected $dataRefMap = [];

    /**
     * Update/Add featureSet
     *
     * @param FeatureSetInterface $featureSet
     */
    protected function setFeatureSet( FeatureSetInterface $featureSet )
    {
        $this->featureSet = $featureSet;
    }

    /**
     * @param array $dataRefMap
     */
    protected function setDataRefMap(array $dataRefMap = [])
    {
        $this->dataRefMap = $dataRefMap;
    }

    /**
     * @param string $source
     */
    protected function setSource( $source )
    {
        $this->source = $source;
    }

    /**
     * @param string $target
     */
    protected function setTarget( $target )
    {
        $this->target = $target;
    }

    /**
     * Used to transform database raw xml content ( Layer 0 ) to the sub filtered structures, used for server to server ( Ex: TM/MT ) communications ( Layer 1 )
     *
     * @param $segment
     *
     * @return mixed
     */
    abstract public function fromLayer0ToLayer1($segment);

    /**
     * Used to transform external server raw xml content ( Ex: TM/MT ) to allow them to be stored in database ( Layer 0 ), used for server to server communications ( Layer 1 )
     *
     * @param $segment
     *
     * @return mixed
     */
    abstract public function fromLayer1ToLayer0($segment);
}