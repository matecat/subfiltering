<?php

use Matecat\SubFiltering\Enum\InjectableFiltersTags;
use Matecat\SubFiltering\MateCatFilter;
use Matecat\SubFiltering\MyMemoryFilter;
use PHPUnit\Framework\TestCase;

/**
 * A filter instance knows which handlers it will run on the Layer 0 to Layer 1 transition.
 * Anything that has to describe that decision to another system — a request naming the
 * handlers the text was built with — needs to read it back instead of recomputing it.
 */
class FilterResolvedHandlersTest extends TestCase
{
    /**
     * @param array<string>|null $tagNames
     */
    private function filter(?array $tagNames, bool $icuEnabled = false): MateCatFilter
    {
        /** @var MateCatFilter $filter */
        $filter = MateCatFilter::getInstance(null, 'en-US', 'it-IT', [], $tagNames, $icuEnabled);

        return $filter;
    }

    public function testNoHandlersAreReportedWhenTheFilterWasBuiltWithNull()
    {
        $this->assertSame([], $this->filter(null)->getOrderedHandlerTagNames());
    }

    public function testTheDefaultSetIsReportedByItsTagNames()
    {
        $this->assertSame(
            [
                InjectableFiltersTags::markup->value,
                InjectableFiltersTags::twig->value,
                InjectableFiltersTags::double_snail->value,
                InjectableFiltersTags::double_square->value,
                InjectableFiltersTags::double_percent->value,
            ],
            $this->filter([])->getOrderedHandlerTagNames()
        );
    }

    public function testAnExplicitListIsReportedInPositionOrder()
    {
        $tagNames = [
            InjectableFiltersTags::sprintf->value,
            InjectableFiltersTags::single_curly->value,
            InjectableFiltersTags::markup->value,
        ];

        $this->assertSame(
            [
                InjectableFiltersTags::markup->value,
                InjectableFiltersTags::single_curly->value,
                InjectableFiltersTags::sprintf->value,
            ],
            $this->filter($tagNames)->getOrderedHandlerTagNames()
        );
    }

    /**
     * The ICU reduction happened at construction time, so the instance reports what it will
     * actually run and not what it was asked for.
     */
    public function testTheIcuReductionIsVisibleOnTheInstance()
    {
        $tagNames = [
            InjectableFiltersTags::single_curly->value,
            InjectableFiltersTags::markup->value,
        ];

        $this->assertSame(
            [InjectableFiltersTags::markup->value],
            $this->filter($tagNames, true)->getOrderedHandlerTagNames()
        );
    }

    public function testAnInstanceWhoseHandlersAllReduceAwayReportsNone()
    {
        $this->assertSame(
            [],
            $this->filter([InjectableFiltersTags::single_curly->value], true)->getOrderedHandlerTagNames()
        );
    }

    /**
     * MyMemoryFilter adds per-client handlers while it configures a single transformation,
     * from the `cid` passed to that call. Those are not part of the instance configuration
     * and must not appear here, or a caller would name handlers the next call may not run.
     *
     * `dollar_curly` is enabled because the roblox branch inserts its handler after that one,
     * and Pipeline::addAfter() is a no-op when the anchor handler is absent.
     */
    public function testClientSpecificHandlersAreNotReportedByTheInstance()
    {
        /** @var MyMemoryFilter $filter */
        $filter = MyMemoryFilter::getInstance(
            null,
            'en-US',
            'it-IT',
            [],
            [InjectableFiltersTags::dollar_curly->value]
        );

        $reported = $filter->getOrderedHandlerTagNames();

        $this->assertSame([InjectableFiltersTags::dollar_curly->value], $reported);
        $this->assertStringContainsString(
            'x-curly-brackets',
            $filter->fromLayer0ToLayer1('Hello {NAME}', 'roblox'),
            'the roblox pipeline does wrap single curly brackets, from the cid, not from the instance'
        );
    }
    /**
     * A filter with no handlers still runs the transition, it just injects nothing.
     */
    public function testAFilterWithNoHandlersLeavesThePlaceholdersAlone()
    {
        $this->assertSame(
            'Hello {NAME} and %s',
            $this->filter(null)->fromLayer0ToLayer1('Hello {NAME} and %s')
        );
    }

    /**
     * A single reported handler is a single handler run: the reduction that leaves one
     * name behind must still reach the pipeline, and nothing else must ride along with it.
     */
    public function testAnInstanceWithOneHandlerRunsThatHandlerOnly()
    {
        $filter = $this->filter([InjectableFiltersTags::markup->value, InjectableFiltersTags::single_curly->value], true);

        $this->assertSame([InjectableFiltersTags::markup->value], $filter->getOrderedHandlerTagNames());

        $layer1 = $filter->fromLayer0ToLayer1('Hello <b>you</b>, {NAME}');

        $this->assertStringContainsString('ctype="x-html"', $layer1);
        $this->assertStringContainsString('{NAME}', $layer1);
    }

}
