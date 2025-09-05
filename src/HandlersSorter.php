<?php
/**
 * This file contains the HandlersSorter class, which is responsible for ordering
 * a set of "injectable" filter handlers based on a predefined priority.
 */

namespace Matecat\SubFiltering;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Filters\DollarCurlyBrackets;
use Matecat\SubFiltering\Filters\DoublePercentages;
use Matecat\SubFiltering\Filters\DoubleSquareBrackets;
use Matecat\SubFiltering\Filters\HtmlToPh;
use Matecat\SubFiltering\Filters\ObjectiveCNSString;
use Matecat\SubFiltering\Filters\RubyOnRailsI18n;
use Matecat\SubFiltering\Filters\Snails;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\SquareSprintf;
use Matecat\SubFiltering\Filters\TwigToPh;
use Matecat\SubFiltering\Filters\Variables;
use Matecat\SubFiltering\Filters\XmlToPh;

/**
 * Manages the sorting of filter handlers according to a predefined execution order.
 *
 * This class takes an array of handler class names and sorts them based on the
 * priorities defined in the `injectableHandlersOrder` constant. It also handles
 * special cases, such as the mutual exclusivity of `XmlToPh` and `HtmlToPh`,
 * ensuring the pipeline is built in the correct sequence.
 */
class HandlersSorter {

    /**
     * Defines the execution order for injectable handlers. Lower numbers have higher priority and run first.
     *
     * This order is critical, as handlers often depend on the output of previous ones.
     * For example, generic XML/HTML tag conversion should happen before more specific
     * variable substitutions that might be present within those tags.
     *
     * @var int[] A map of handler class names to their integer priority.
     */
    public const injectableHandlersOrder = [
            XmlToPh::class              => 0,
            Variables::class            => 1,
            TwigToPh::class             => 2,
            RubyOnRailsI18n::class      => 3,
            Snails::class               => 4,
            DoubleSquareBrackets::class => 5,
            DollarCurlyBrackets::class  => 6,
            ObjectiveCNSString::class   => 7,
            DoublePercentages::class    => 8,
            SquareSprintf::class        => 9,
            SprintfToPH::class          => 10,
    ];

    /**
     * @var array The final map of priorities used for sorting, which may be modified from the default constant.
     */
    private array $defaultInjectedHandlers;

    /**
     * @var string[] The sorted array of handler class names.
     */
    private array $injectedHandlers;

    /**
     * HandlersSorter constructor.
     *
     * Initializes the sorter with a given list of handlers. It sorts the handlers according
     * to the `injectableHandlersOrder` and handles special rules, like giving `HtmlToPh`
     * precedence over `XmlToPh`.
     *
     * @param class-string[] $injectedHandlers An array of handler class names to be sorted.
     */
    public function __construct( array $injectedHandlers = [] ) {

        // Start with the default order of handlers.
        $this->defaultInjectedHandlers = self::injectableHandlersOrder;

        // Special rule: HtmlToPh and XmlToPh are mutually exclusive because HtmlToPh is a
        // more specific and capable version of XmlToPh. If a user requests HtmlToPh, we
        // must ensure XmlToPh is not also run to prevent redundant or conflicting processing.
        if ( in_array( HtmlToPh::class, $injectedHandlers ) ) {

            // Find the key of the XmlToPh handler in the input array.
            if ( ( $key = array_search( XmlToPh::class, $injectedHandlers ) ) !== false ) {
                // If found, remove it from the array to be sorted.
                unset( $injectedHandlers[ $key ] );
                // Re-index the array to ensure it remains a zero-based, consecutive list.
                $injectedHandlers = array_values( $injectedHandlers );
            }

            // To ensure HtmlToPh is placed correctly in the sequence, assign it the same
            // priority as XmlToPh and remove XmlToPh from the priority map for this instance.
            $this->defaultInjectedHandlers[ HtmlToPh::class ] = $this->defaultInjectedHandlers[ XmlToPh::class ];
            unset( $this->defaultInjectedHandlers[ XmlToPh::class ] );
        }

        // Sort the final list of handlers according to their predefined execution order.
        $this->injectedHandlers = $this->quickSort( $injectedHandlers );
    }

    /**
     * Sorts the handler's list using the QuickSort algorithm.
     *
     * It recursively partitions the array of handlers based on their priority values
     * defined in the `defaultInjectedHandlers` map. Handlers not present in the map are ignored.
     *
     * @param class-string[] $handlersList The list of handler class names to sort.
     *
     * @return class-string[] The sorted list of handlers.
     */
    private function quickSort( array $handlersList ): array {
        $length = count( $handlersList );

        // Base case: if the array has 0 or 1 elements, it's already sorted.
        if ( $length < 2 ) {
            return $handlersList;
        }

        // Select the first element as the pivot.
        $pivot       = $handlersList[ 0 ];
        $leftBucket  = []; // Elements with lower or equal priority
        $rightBucket = []; // Elements with higher priority

        // Partition the rest of the array around the pivot.
        for ( $i = 1; $i < $length; $i++ ) {
            // Skip any handler not in our defined priority list.
            if ( !array_key_exists( $handlersList[ $i ], $this->defaultInjectedHandlers ) ) {
                continue;
            }

            // Compare the pivot's priority with the current element's priority.
            if ( $this->defaultInjectedHandlers[ $pivot ] > $this->defaultInjectedHandlers[ $handlersList[ $i ] ] ) {
                $leftBucket[] = $handlersList[ $i ];
            } else {
                $rightBucket[] = $handlersList[ $i ];
            }
        }

        // Recursively sort the partitions and merge them back together with the pivot.
        return array_merge( $this->quickSort( $leftBucket ), [ $pivot ], $this->quickSort( $rightBucket ) );
    }

    /**
     * Returns the final, sorted list of handler class names.
     *
     * @return class-string<AbstractHandler>[] An array of handler class names ready to be added to a pipeline.
     */
    public function getOrderedHandlersClassNames(): array {
        // This creates a copy of the sorted handlers array.
        $handlers = [];
        foreach ( $this->injectedHandlers as $handler ) {
            $handlers[] = $handler;
        }

        return $handlers;
    }
}