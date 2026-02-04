<?php

use Matecat\SubFiltering\Enum\InjectableFiltersTags;
use Matecat\SubFiltering\Filters\DollarCurlyBrackets;
use Matecat\SubFiltering\Filters\DoublePercentages;
use Matecat\SubFiltering\Filters\DoubleSquareBrackets;
use Matecat\SubFiltering\Filters\MarkupToPh;
use Matecat\SubFiltering\Filters\ObjectiveCNSString;
use Matecat\SubFiltering\Filters\PercentDoubleCurlyBrackets;
use Matecat\SubFiltering\Filters\RubyOnRailsI18n;
use Matecat\SubFiltering\Filters\Snails;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\HandlersSorter;
use PHPUnit\Framework\TestCase;

class HandlersSorterTest extends TestCase
{

    /**
     * This test ensures that the sorter correctly handles and reorder instances of the
     * `Matecat\SubFiltering\Filters\AbstractFilter` class.
     */
    public function testInjectedHandlersAreSortedProperly()
    {
        // Provide a shuffled list of valid handlers
        $unordered = [
            Snails::class,
            ObjectiveCNSString::class,
            RubyOnRailsI18n::class,
            SprintfToPH::class,
            DollarCurlyBrackets::class,
            DoubleSquareBrackets::class,
        ];

        // Expected order by handlersOrder
        $expected = [
            RubyOnRailsI18n::class,      // 3
            Snails::class,               // 4
            DoubleSquareBrackets::class, // 5
            DollarCurlyBrackets::class,  // 6
            ObjectiveCNSString::class,   // 8
            SprintfToPH::class,          // 11
        ];

        $sorter = new HandlersSorter($unordered);

        $reflection = new ReflectionClass(HandlersSorter::class);
        $property = $reflection->getProperty('injectedHandlers');
        $actual = $property->getValue($sorter);

        $this->assertSame($expected, $actual);
    }

    /**
     * This test ensures that the sorter correctly handles and reorder instances of the
     * `Matecat\SubFiltering\Filters\AbstractFilter` class, ignoring unknown handlers.
     */
    public function testInjectedHandlersIgnoresUnknownHandlers()
    {
        $unknownClass = stdClass::class;
        $handlers = [
            Snails::class,
            SprintfToPH::class,
            $unknownClass,
            PercentDoubleCurlyBrackets::class,
        ];

        $expected = [
            PercentDoubleCurlyBrackets::class,   // 1
            Snails::class,      // 4
            SprintfToPH::class, // 11
        ];

        $sorter = new HandlersSorter($handlers);

        $reflection = new ReflectionClass(HandlersSorter::class);
        $property = $reflection->getProperty('injectedHandlers');
        $actual = $property->getValue($sorter);

        $this->assertSame($expected, $actual);
    }

    /**
     * This test ensures that the sorter correctly handles and reorder instances of the
     * `Matecat\SubFiltering\Filters\AbstractFilter` class.
     *
     * @test
     */
    public function testGetInjectedHandlersInstances_1()
    {
        // NOTE: This test is subject to the same `quickSort` assumption mentioned above.
        $handlers = [
            MarkupToPh::class,
            PercentDoubleCurlyBrackets::class,
        ];

        $sorter = new HandlersSorter($handlers);
        $sortedHandlers = $sorter->getOrderedHandlersClassNames();

        $this->assertContains(MarkupToPh::class, $sortedHandlers);
        $this->assertContains(PercentDoubleCurlyBrackets::class, $sortedHandlers);
        $this->assertEquals(MarkupToPh::class, $sortedHandlers[0]);
        $this->assertEquals(PercentDoubleCurlyBrackets::class, $sortedHandlers[1]);
    }

    /**
     * This test ensures that the sorter correctly handles and reorder instances of the
     * `Matecat\SubFiltering\Filters\AbstractFilter` class.
     *
     * @test
     */
    public function testGetInjectedHandlersInstances_2()
    {
        $handlers = [
            DoublePercentages::class,
            SprintfToPH::class,
        ];
        $sorter = new HandlersSorter($handlers);
        $instances = $sorter->getOrderedHandlersClassNames();

        $this->assertCount(2, $instances);
        $this->assertEquals(DoublePercentages::class, $instances[0]);
        $this->assertEquals(SprintfToPH::class, $instances[1]);
    }

    /**
     * Ensure HandlersSorter can consume handlers provided via InjectableFiltersTags
     * and that unknown tag names are ignored.
     */
    public function testProvideHandlersFromFiltersTagsEnumAndIgnoreUnknown()
    {
        // Prepare a mixed set of known tags and an unknown one
        $tags = [
            InjectableFiltersTags::double_percent->value,
            'non_existing_tag', // unknown
            InjectableFiltersTags::ruby_on_rails->value,
            InjectableFiltersTags::double_square->value,
            InjectableFiltersTags::sprintf->value,
            InjectableFiltersTags::dollar_curly->value,
        ];

        // Map tags to handler class names using the enum
        $handlers = InjectableFiltersTags::classesForArrayTagNames($tags);

        // Build sorter with the resolved class names
        $sorter = new HandlersSorter($handlers);
        $orderedList = $sorter->getOrderedHandlersClassNames();

        // Unknown must be ignored (not present in the ordered list)
        $this->assertNotContains('unknown', $orderedList);

        // Expected order according to HandlersSorter::injectableHandlersOrder
        $expected = [
            RubyOnRailsI18n::class,      // position 3
            DoubleSquareBrackets::class, // position 5
            DollarCurlyBrackets::class,  // position 6
            DoublePercentages::class,    // position 9
            SprintfToPH::class,          // position 11
        ];

        $this->assertSame($expected, $orderedList);
        $this->assertCount(5, $orderedList);
    }

    public function test_forArrayNamesMapsTagsToClassesAndUnknown()
    {
        $input = [
            'markup',
            'non_existent_tag',   // should become null and filtered out
            'double_percent',
            'ruby_on_rails',
            'double_square',
            'sprintf',
        ];

        // Build sorter with the resolved class names
        $sorter = new HandlersSorter(InjectableFiltersTags::classesForArrayTagNames($input));
        $orderedList = $sorter->getOrderedHandlersClassNames();

        // Unknown must be ignored (not present in the ordered list)
        $this->assertNotContains('unknown', $orderedList);
        $this->assertNotContains(null, $orderedList);

        // Expected order according to HandlersSorter::injectableHandlersOrder
        $expected = [
            MarkupToPh::class,           // position 1
            RubyOnRailsI18n::class,      // position 3
            DoubleSquareBrackets::class, // position 5
            DoublePercentages::class,    // position 9
            SprintfToPH::class,          // position 11
        ];

        $this->assertSame($expected, $orderedList);
    }

    public function testIcuEnabledFiltersOutNonIcuCompliantHandlers()
    {
        $handlers = [
            MarkupToPh::class,           // icu_compliant => true
            RubyOnRailsI18n::class,      // icu_compliant => false
            ObjectiveCNSString::class,   // icu_compliant => false
            DoublePercentages::class,    // icu_compliant => false
        ];

        $sorter = new HandlersSorter($handlers, true);
        $ordered = $sorter->getOrderedHandlersClassNames();

        // Non-ICU-compliant handlers must be removed
        $this->assertNotContains(RubyOnRailsI18n::class, $ordered);
        $this->assertNotContains(ObjectiveCNSString::class, $ordered);
        $this->assertNotContains(DoublePercentages::class, $ordered);

        // Only the ICU-compliant handler remains and is ordered correctly
        $expected = [
            MarkupToPh::class,
        ];

        $this->assertSame($expected, $ordered);
    }

}