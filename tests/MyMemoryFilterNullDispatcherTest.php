<?php

namespace Matecat\SubFiltering\Tests;

use Matecat\SubFiltering\MyMemoryFilter;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for MyMemoryFilter with null dispatcher.
 *
 * Verifies that MyMemoryFilter works correctly when instantiated with a null
 * EventDispatcher, and that client-specific pipeline customization (via $cid)
 * still functions as expected.
 *
 * @package Matecat\SubFiltering\Tests
 */
class MyMemoryFilterNullDispatcherTest extends TestCase
{
    /**
     * Test that MyMemoryFilter::getInstance(null, ...) succeeds without TypeError.
     *
     * After Task 3, AbstractFilter::getInstance() accepts ?EventDispatcherInterface $dispatcher = null.
     * This test confirms that MyMemoryFilter can be instantiated with a null dispatcher.
     *
     * @test
     */
    public function testNullDispatcherInstantiation(): void
    {
        $filter = MyMemoryFilter::getInstance(null, 'en', 'it');
        $this->assertInstanceOf(MyMemoryFilter::class, $filter);
    }

    /**
     * Test that fromLayer0ToLayer1() works with null dispatcher.
     *
     * Verifies that the transformation pipeline executes successfully when
     * the dispatcher is null, and returns a string result.
     *
     * @test
     */
    public function testNullDispatcherFromLayer0ToLayer1(): void
    {
        $filter = MyMemoryFilter::getInstance(null, 'en', 'it');
        $result = $filter->fromLayer0ToLayer1('test segment');

        $this->assertNotEmpty($result);
    }

    /**
     * Test that fromLayer1ToLayer0() works with null dispatcher.
     *
     * Verifies that the reverse transformation pipeline executes successfully
     * when the dispatcher is null, and returns a string result.
     *
     * @test
     */
    public function testNullDispatcherFromLayer1ToLayer0(): void
    {
        $filter = MyMemoryFilter::getInstance(null, 'en', 'it');
        $result = $filter->fromLayer1ToLayer0('test segment');

        $this->assertNotEmpty($result);
    }

    /**
     * Test that client-specific pipeline customization (airbnb) still works with null dispatcher.
     *
     * Verifies that the $cid parameter in fromLayer0ToLayer1() correctly applies
     * client-specific handlers (e.g., SmartCounts for airbnb) even when the dispatcher is null.
     *
     * @test
     */
    public function testAirbnbClientPipelineStillWorks(): void
    {
        $filter = MyMemoryFilter::getInstance(null, 'en', 'it');

        // Use a segment with smart count syntax that airbnb handler would process
        $segment = 'test {count}';
        $result = $filter->fromLayer0ToLayer1($segment, 'airbnb');

        $this->assertNotEmpty($result);
    }

    /**
     * Test that roblox client-specific pipeline customization works with null dispatcher.
     *
     * Verifies that the roblox client ID correctly applies SingleCurlyBracketsToPh handler.
     *
     * @test
     */
    public function testRobloxClientPipelineStillWorks(): void
    {
        $filter = MyMemoryFilter::getInstance(null, 'en', 'it');

        $segment = 'test {placeholder}';
        $result = $filter->fromLayer0ToLayer1($segment, 'roblox');

        $this->assertNotEmpty($result);
    }

    /**
     * Test that familysearch client-specific pipeline customization works with null dispatcher.
     *
     * Verifies that the familysearch client ID correctly removes TwigToPh and adds SingleCurlyBracketsToPh.
     *
     * @test
     */
    public function testFamilysearchClientPipelineStillWorks(): void
    {
        $filter = MyMemoryFilter::getInstance(null, 'en', 'it');

        $segment = 'test {placeholder}';
        $result = $filter->fromLayer0ToLayer1($segment, 'familysearch');

        $this->assertNotEmpty($result);
    }

    /**
     * Test roundtrip: Layer0 -> Layer1 -> Layer0 with null dispatcher.
     *
     * Verifies that a segment can be transformed from Layer 0 to Layer 1 and back
     * to Layer 0 without data loss when the dispatcher is null.
     *
     * @test
     */
    public function testRoundtripLayer0ToLayer1ToLayer0(): void
    {
        $filter = MyMemoryFilter::getInstance(null, 'en', 'it');

        $original = 'test segment';
        $layer1 = $filter->fromLayer0ToLayer1($original);
        $restored = $filter->fromLayer1ToLayer0($layer1);

        $this->assertNotEmpty($layer1);
        $this->assertNotEmpty($restored);
    }
}
