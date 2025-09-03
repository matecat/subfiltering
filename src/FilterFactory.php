<?php
/**
 * Created by PhpStorm.
 * @author Domenico Lupinetti (hashashiyyin) domenico@translated.net / ostico@gmail.com
 * Date: 02/09/25
 * Time: 15:18
 *
 */

namespace Matecat\SubFiltering;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Filters\DollarCurlyBrackets;
use Matecat\SubFiltering\Filters\DoubleSquareBrackets;
use Matecat\SubFiltering\Filters\Percentages;
use Matecat\SubFiltering\Filters\PercentNumberSnail;
use Matecat\SubFiltering\Filters\RubyOnRailsI18n;
use Matecat\SubFiltering\Filters\Snails;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\SquareSprintf;
use Matecat\SubFiltering\Filters\TwigToPh;
use Matecat\SubFiltering\Filters\Variables;

class FilterFactory {

    private static $injectableHandlersOrder = [
            Variables::class,
            TwigToPh::class,
            RubyOnRailsI18n::class,
            Snails::class,
            DoubleSquareBrackets::class,
            DollarCurlyBrackets::class,
            PercentNumberSnail::class,
            Percentages::class,
            SquareSprintf::class,
            SprintfToPH::class
    ];

    /**
     * @var AbstractHandler[]
     */
    private array $injectedHandlers;

    /**
     * FilterFactory constructor.
     *
     * @param AbstractHandler[] $injectedHandlers
     */
    public function __construct( array $injectedHandlers = [] ) {
        $this->injectedHandlers = $this->quickSort( $injectedHandlers );
    }


    /**
     * Warning Recursion, memory overflow if there are a lot of features (but this is impossible)
     *
     */
    private function quickSort( array $handlersList ): array {

        $length = count( $handlersList );
        if ( $length < 2 ) {
            return $handlersList;
        }

        $firstInList        = $handlersList[ 0 ];
        $ObjectFeatureFirst = $firstInList->toNewObject();

        $leftBucket = $rightBucket = [];

        for ( $i = 1; $i < $length; $i++ ) {

            if ( in_array( $handlersList[ $i ]->feature_code, $ObjectFeatureFirst::getDependencies() ) ) {
                $leftBucket[] = $handlersList[ $i ];
            } else {
                $rightBucket[] = $handlersList[ $i ];
            }

        }

        return array_merge( $this->quickSort( $leftBucket ), [ $firstInList ], $this->quickSort( $rightBucket ) );

    }

}