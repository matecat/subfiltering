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
use Matecat\SubFiltering\Filters\ObjectiveCNSString;
use Matecat\SubFiltering\Filters\RubyOnRailsI18n;
use Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh;
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
    protected const injectableHandlersOrder = [
            XmlToPh::class                 => [ 'position' => 0, 'default_enabled' => true ],
            Variables::class               => [ 'position' => 1, 'default_enabled' => true ],
            TwigToPh::class                => [ 'position' => 2, 'default_enabled' => true ],
            RubyOnRailsI18n::class         => [ 'position' => 3, 'default_enabled' => true ],
            Snails::class                  => [ 'position' => 4, 'default_enabled' => true ],
            DoubleSquareBrackets::class    => [ 'position' => 5, 'default_enabled' => true ],
            DollarCurlyBrackets::class     => [ 'position' => 6, 'default_enabled' => true ],
            SingleCurlyBracketsToPh::class => [ 'position' => 7, 'default_enabled' => false ], // Disabled by default because it may conflict with other curly braces handlers
            ObjectiveCNSString::class      => [ 'position' => 8, 'default_enabled' => true ],
            DoublePercentages::class       => [ 'position' => 9, 'default_enabled' => true ],
            SquareSprintf::class           => [ 'position' => 10, 'default_enabled' => true ],
            SprintfToPH::class             => [ 'position' => 11, 'default_enabled' => true ],
    ];

    /**
     * Retrieves the default handlers that are enabled by default from the injectable handlers order.
     *
     * @return array The array of handlers that are enabled by default.
     */
    public static function getDefaultInjectedHandlers(): array {
        return array_filter( self::injectableHandlersOrder, function ( $settings ) {
            return $settings[ 'default_enabled' ];
        } );
    }

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

        // Sort the final list of handlers according to their predefined execution order.
        $this->injectedHandlers = $this->quickSort( $injectedHandlers );

    }

    /**
     * Sorts the given list of handlers based on their defined priorities.
     *
     * This method filters the input array to include only those handlers that have a defined
     * priority in the `defaultInjectedHandlers` property. It then sorts the filtered list
     * using a custom comparison function based on the priority values.
     *
     * @param string[] $handlersList An array of handler class names to be filtered and sorted.
     *
     * @return string[] The sorted list of handler class names based on their priorities.
     */
    private function quickSort( array $handlersList ): array {
        // Filter the list to include only valid handlers.
        $filteredHandlers = array_filter( $handlersList, function ( $handler ) {
            return array_key_exists( $handler, $this->defaultInjectedHandlers );
        } );

        // Sort the handlers based on their priority using a custom comparison function.
        usort( $filteredHandlers, function ( $a, $b ) {
            // The spaceship operator (<=>) returns -1, 0, or 1, which is what usort expects.
            return $this->defaultInjectedHandlers[ $a ] <=> $this->defaultInjectedHandlers[ $b ];
        } );

        return $filteredHandlers;
    }

    /**
     * Returns the final, sorted list of handler class names.
     *
     * @return class-string<AbstractHandler>[] An array of handler class names ready to be added to a pipeline.
     */
    public function getOrderedHandlersClassNames(): array {
        return $this->injectedHandlers;
    }

}