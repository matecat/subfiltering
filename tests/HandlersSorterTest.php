<?php

use Matecat\SubFiltering\Filters\DollarCurlyBrackets;
use Matecat\SubFiltering\Filters\DoublePercentages;
use Matecat\SubFiltering\Filters\DoubleSquareBrackets;
use Matecat\SubFiltering\Filters\HtmlToPh;
use Matecat\SubFiltering\Filters\ObjectiveCNSString;
use Matecat\SubFiltering\Filters\RubyOnRailsI18n;
use Matecat\SubFiltering\Filters\Snails;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\Variables;
use Matecat\SubFiltering\Filters\XmlToPh;
use Matecat\SubFiltering\HandlersSorter;
use PHPUnit\Framework\TestCase;

class HandlersSorterTest extends TestCase {

    public function testInjectedHandlersAreSortedProperly() {
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
                RubyOnRailsI18n::class,      // 2
                Snails::class,               // 3
                DoubleSquareBrackets::class, // 4
                DollarCurlyBrackets::class,  // 5
                ObjectiveCNSString::class,   // 6
                SprintfToPH::class,          // 9
        ];

        $sorter = new HandlersSorter( $unordered );

        $reflection = new ReflectionClass( HandlersSorter::class );
        $property   = $reflection->getProperty( 'injectedHandlers' );
        $property->setAccessible( true );
        $actual = $property->getValue( $sorter );

        $this->assertSame( $expected, $actual );
    }

    public function testInjectedHandlersIgnoresUnknownHandlers() {
        $unknownClass = stdClass::class;
        $handlers     = [
                Snails::class,
                $unknownClass,
                Variables::class,
                SprintfToPH::class,
        ];

        $expected = [
                Variables::class,   // 0
                Snails::class,      // 3
                SprintfToPH::class, // 9
        ];

        $sorter = new HandlersSorter( $handlers );

        $reflection = new ReflectionClass( HandlersSorter::class );
        $property   = $reflection->getProperty( 'injectedHandlers' );
        $property->setAccessible( true );
        $actual = $property->getValue( $sorter );

        $this->assertSame( $expected, $actual );
    }

    /**
     * This test ensures that if only XmlToPh is provided (without HtmlToPh),
     * it is preserved by the sorter.
     *
     * @test
     */
    public function testXmlToPhIsPreservedWhenProvidedAlone() {
        // NOTE: This test is subject to the same `quickSort` assumption mentioned above.
        $handlers = [
                XmlToPh::class,
                Variables::class,
        ];

        $sorter         = new HandlersSorter( $handlers );
        $sortedHandlers = $sorter->getOrderedHandlersClassNames();

        $this->assertContains( XmlToPh::class, $sortedHandlers );
        $this->assertContains( Variables::class, $sortedHandlers );
    }

    public function testGetInjectedHandlersInstances() {
        $handlers  = [
                DoublePercentages::class,
                SprintfToPH::class,
        ];
        $sorter    = new HandlersSorter( $handlers );
        $instances = $sorter->getOrderedHandlersClassNames();

        $this->assertCount( 2, $instances );
        $this->assertEquals( DoublePercentages::class, $instances[ 0 ] );
        $this->assertEquals( SprintfToPH::class, $instances[ 1 ] );
    }
}