<?php

use Matecat\SubFiltering\Enum\InjectableFiltersTags;
use Matecat\SubFiltering\Filters\DoublePercentages;
use Matecat\SubFiltering\Filters\DoubleSquareBrackets;
use Matecat\SubFiltering\Filters\MarkupToPh;
use Matecat\SubFiltering\Filters\Snails;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh;
use Matecat\SubFiltering\Filters\TwigToPh;
use Matecat\SubFiltering\HandlersSorter;
use PHPUnit\Framework\TestCase;

/**
 * `HandlersSorter::resolveClassNames()` holds the rule that turns the tag names travelling
 * on an API into the handler classes a filter will run: the two sentinel values, the
 * fallback to the default set and the ICU reduction.
 *
 * The rule used to live inside `AbstractFilter::getInstance()`, where a caller that has to
 * predict the outcome — a server-to-server request that must name the same handlers the
 * text was built with — could only re-implement it.
 */
class ResolveClassNamesTest extends TestCase
{
    /**
     * @return class-string[]
     */
    private function defaultClasses(): array
    {
        return [
            MarkupToPh::class,          // 0
            TwigToPh::class,            // 2
            Snails::class,              // 4
            DoubleSquareBrackets::class, // 5
            DoublePercentages::class,   // 9
        ];
    }

    /**
     * null asks for no handlers at all, which is not the same request as an empty list.
     */
    public function testNullResolvesToNoHandlers()
    {
        $this->assertSame([], HandlersSorter::resolveClassNames(null));
    }

    /**
     * An empty list asks for the default set, in position order.
     */
    public function testEmptyListResolvesToTheDefaultSet()
    {
        $this->assertSame($this->defaultClasses(), HandlersSorter::resolveClassNames([]));
    }

    public function testAnExplicitListIsMappedAndSorted()
    {
        $resolved = HandlersSorter::resolveClassNames([
            InjectableFiltersTags::sprintf->value,
            InjectableFiltersTags::single_curly->value,
            InjectableFiltersTags::markup->value,
        ]);

        $this->assertSame(
            [
                MarkupToPh::class,                // 0
                SingleCurlyBracketsToPh::class,   // 7
                SprintfToPH::class,               // 11
            ],
            $resolved
        );
    }

    /**
     * A list that maps to nothing is indistinguishable from an empty one, so it takes the
     * same fallback.
     */
    public function testAListOfUnknownTagNamesFallsBackToTheDefaultSet()
    {
        $this->assertSame($this->defaultClasses(), HandlersSorter::resolveClassNames(['not_a_handler']));
    }

    public function testUnknownTagNamesAreDroppedWhenSomethingElseMaps()
    {
        $resolved = HandlersSorter::resolveClassNames([
            'not_a_handler',
            InjectableFiltersTags::markup->value,
        ]);

        $this->assertSame([MarkupToPh::class], $resolved);
    }

    /**
     * Only MarkupToPh leaves ICU syntax alone, so it is the whole ICU-compliant default set.
     */
    public function testTheDefaultSetIsReducedWhenIcuIsEnabled()
    {
        $this->assertSame([MarkupToPh::class], HandlersSorter::resolveClassNames([], true));
    }

    public function testNonCompliantHandlersAreDroppedFromAnExplicitListWhenIcuIsEnabled()
    {
        $resolved = HandlersSorter::resolveClassNames(
            [
                InjectableFiltersTags::single_curly->value,
                InjectableFiltersTags::markup->value,
                InjectableFiltersTags::twig->value,
            ],
            true
        );

        $this->assertSame([MarkupToPh::class], $resolved);
    }

    /**
     * The reduction can empty a non-empty list. That is not a request for the defaults: the
     * fallback is decided before the ICU filter runs.
     */
    public function testAnExplicitListCanReduceToNothing()
    {
        $resolved = HandlersSorter::resolveClassNames(
            [
                InjectableFiltersTags::single_curly->value,
                InjectableFiltersTags::twig->value,
            ],
            true
        );

        $this->assertSame([], $resolved);
    }

    public function testIcuIsDisabledByDefault()
    {
        $this->assertSame(
            HandlersSorter::resolveClassNames([], false),
            HandlersSorter::resolveClassNames([])
        );
    }

    /**
     * The resolution must stay the single source of truth for what a filter runs, so the
     * instance built from the same tag names holds exactly the resolved list.
     */
    public function testTheResolutionMatchesWhatTheSorterHolds()
    {
        $tagNames = [
            InjectableFiltersTags::single_curly->value,
            InjectableFiltersTags::markup->value,
        ];

        $sorter = new HandlersSorter(HandlersSorter::resolveClassNames($tagNames));

        $this->assertSame(
            HandlersSorter::resolveClassNames($tagNames),
            $sorter->getOrderedHandlersClassNames()
        );
    }
    public function testNullResolvesToNoHandlersWithIcuEnabledToo()
    {
        $this->assertSame([], HandlersSorter::resolveClassNames(null, true));
    }

    public function testUnknownTagNamesFallBackToTheReducedDefaultSetWhenIcuIsEnabled()
    {
        $this->assertSame([MarkupToPh::class], HandlersSorter::resolveClassNames(['not_a_handler'], true));
    }

    /**
     * The sorter also guards against class names that are not in its order map at all, which
     * the tag-name resolution above can never produce.
     */
    public function testTheSorterDropsClassNamesItDoesNotKnow()
    {
        $sorter = new HandlersSorter([MarkupToPh::class, 'Not\\A\\Handler'], false);

        $this->assertSame([MarkupToPh::class], $sorter->getOrderedHandlersClassNames());
    }
}
