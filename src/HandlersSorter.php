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
use Matecat\SubFiltering\Filters\MarkupToPh;
use Matecat\SubFiltering\Filters\ObjectiveCNSString;
use Matecat\SubFiltering\Filters\PercentDoubleCurlyBrackets;
use Matecat\SubFiltering\Filters\RubyOnRailsI18n;
use Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh;
use Matecat\SubFiltering\Filters\Snails;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\SquareSprintf;
use Matecat\SubFiltering\Filters\TwigToPh;

/**
 * Manages the sorting of filter handlers according to a predefined execution order.
 *
 * This class takes an array of handler class names and sorts them based on the
 * priorities defined in the `injectableHandlersOrder` constant. It also handles
 * special cases, such as the mutual exclusivity of `MarkupToPh` and `HtmlToPh`,
 * ensuring the pipeline is built in the correct sequence.
 *
 * @phpstan-type HandlersOrderMap array<class-string<AbstractHandler>, array{position:int, default_enabled:bool, icu_compliant:bool}>
 *
 */
class HandlersSorter
{

    /**
     * Defines the execution order for injectable handlers. Lower numbers have higher priority and run first.
     *
     * This order is critical, as handlers often depend on the output of previous ones.
     * For example, generic XML/HTML tag conversion should happen before more specific
     * variable substitutions that might be present within those tags.
     *
     * @var HandlersOrderMap
     *
     * A map of handler class names to their integer priority and whether they are enabled by default.
     */
    protected const array injectableHandlersOrder = [
        MarkupToPh::class => ['position' => 0, 'default_enabled' => true, 'icu_compliant' => true],
        PercentDoubleCurlyBrackets::class => ['position' => 1, 'default_enabled' => true, 'icu_compliant' => false],
        TwigToPh::class => ['position' => 2, 'default_enabled' => true, 'icu_compliant' => false],
        RubyOnRailsI18n::class => ['position' => 3, 'default_enabled' => true, 'icu_compliant' => false],
        Snails::class => ['position' => 4, 'default_enabled' => true, 'icu_compliant' => false],
        DoubleSquareBrackets::class => ['position' => 5, 'default_enabled' => true, 'icu_compliant' => false],
        DollarCurlyBrackets::class => ['position' => 6, 'default_enabled' => true, 'icu_compliant' => false],
        SingleCurlyBracketsToPh::class => ['position' => 7, 'default_enabled' => false, 'icu_compliant' => false],
        // Disabled by default because it may conflict with other curly braces handlers
        ObjectiveCNSString::class => ['position' => 8, 'default_enabled' => true, 'icu_compliant' => false],
        DoublePercentages::class => ['position' => 9, 'default_enabled' => true, 'icu_compliant' => false],
        SquareSprintf::class => ['position' => 10, 'default_enabled' => true, 'icu_compliant' => false],
        SprintfToPH::class => ['position' => 11, 'default_enabled' => true, 'icu_compliant' => false],
    ];

    /**
     * Retrieves the default handlers that are enabled by default from the injectable handlers order.
     *
     * @return HandlersOrderMap The array of handlers that are enabled by default.
     */
    public static function getDefaultInjectedHandlers(): array
    {
        return array_filter(self::injectableHandlersOrder, function ($settings) {
            return $settings['default_enabled'];
        });
    }

    /**
     * @var HandlersOrderMap The final map of priorities used for sorting, which may be modified from the default constant.
     */
    private array $defaultInjectedHandlers;

    /**
     * @var class-string<AbstractHandler>[] The sorted array of handler class names.
     */
    private array $injectedHandlers;

    /**
     * HandlersSorter constructor.
     *
     * Initializes the sorter with a given list of handlers. It sorts the handlers according
     * to the `injectableHandlersOrder` and handles special rules, like giving `HtmlToPh`
     * precedence over `MarkupToPh`.
     *
     * @param class-string<AbstractHandler>[] $injectedHandlers An array of handler class names to be sorted.
     */
    public function __construct(array $injectedHandlers = [], bool $icu_enabled = false)
    {
        // Start with the default order of handlers.
        $this->defaultInjectedHandlers = self::injectableHandlersOrder;

        // Sort the final list of handlers according to their predefined execution order.
        $this->injectedHandlers = $this->quickSort($injectedHandlers, $icu_enabled);
    }

    /**
     * Sorts the given list of handlers based on their defined priorities.
     *
     * This method filters the input array to include only those handlers that have a defined
     * priority in the `defaultInjectedHandlers` property. It then sorts the filtered list
     * using a custom comparison function based on the priority values.
     *
     * @param class-string<AbstractHandler>[] $handlersList An array of handler class names to be filtered and sorted.
     *
     * @return class-string<AbstractHandler>[] The sorted list of handler class names based on their priorities.
     */
    private function quickSort(array $handlersList, bool $icu_enabled): array
    {
        // Filter the list to include only valid handlers.
        $filteredHandlers = array_filter($handlersList, function ($handler) use ($icu_enabled) {
            $handlerExists = array_key_exists($handler, $this->defaultInjectedHandlers);
            if ($handlerExists && $icu_enabled && !$this->defaultInjectedHandlers[$handler]['icu_compliant']) {
                return false;
            }
            return $handlerExists;
        });

        // Sort the handlers based on their priority using a custom comparison function.
        usort($filteredHandlers, function ($a, $b) {
            // The spaceship operator (<=>) returns -1, 0, or 1, which is what usort expects.
            return $this->defaultInjectedHandlers[$a] <=> $this->defaultInjectedHandlers[$b];
        });

        return $filteredHandlers;
    }

    /**
     * Returns the final, sorted list of handler class names.
     *
     * @return class-string<AbstractHandler>[] An array of handler class names ready to be added to a pipeline.
     */
    public function getOrderedHandlersClassNames(): array
    {
        return $this->injectedHandlers;
    }

}