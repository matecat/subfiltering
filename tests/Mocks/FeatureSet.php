<?php

namespace Matecat\SubFiltering\Tests\Mocks;

use Matecat\SubFiltering\Contracts\FeatureSetInterface;
use Matecat\SubFiltering\Tests\Mocks\Features\BaseFeature;

class FeatureSet implements FeatureSetInterface
{
    /**
     * @var BaseFeature[]
     */
    private $features = [];

    /**
     * FeatureSet constructor.
     *
     * @param array $features
     */
    public function __construct( array $features = null )
    {
        if(!empty($features)){
            $this->features = $features;
        }
    }

    /**
     * @inheritDoc
     */
    public function filter( $method, $filterable )
    {
        $args = array_slice( func_get_args(), 1 );

        foreach ( $this->features as $feature ) {
            /* @var $feature BaseFeature */

            if ( !is_null( $feature ) ) {
                if ( method_exists( $feature, $method ) ) {
                    array_shift( $args );
                    array_unshift( $args, $filterable );

                    /**
                     * There may be the need to avoid a filter to be executed before or after other ones.
                     * To solve this problem we could always pass last argument to call_user_func_array which
                     * contains a list of executed feature codes.
                     *
                     * Example: $args + [ $executed_features ]
                     *
                     * This way plugins have the chance to decide wether to change the value, throw an exception or
                     * do whatever they need to based on the behaviour of the other features.
                     *
                     */

                    $filterable = call_user_func_array( [ $feature, $method ], $args );
                }
            }
        }

        return $filterable;
    }
}