<?php

namespace Matecat\SubFiltering\Tests;

use DomainException;
use Matecat\SubFiltering\Utils\ArrayList;
use PHPUnit\Framework\TestCase;

class ArrayListTest extends TestCase {

    /**
     * Tests that the constructor correctly initializes with a valid list
     * and the instance factory method works.
     */
    public function testConstructorWithValidList() {
        $listData  = [ 'a', 'b', 'c' ];
        $arrayList = ArrayList::instance( $listData );

        $this->assertInstanceOf( ArrayList::class, $arrayList );
        $this->assertCount( 3, $arrayList );
        $this->assertEquals( 'a', $arrayList[ 0 ] );
    }

    /**
     * Tests that an empty ArrayList can be created successfully.
     */
    public function testConstructorWithEmptyList() {
        $arrayList = new ArrayList();
        $this->assertInstanceOf( ArrayList::class, $arrayList );
        $this->assertCount( 0, $arrayList );
    }

    /**
     * Tests that the constructor throws a DomainException when an
     * associative array is provided instead of a list.
     */
    public function testConstructorThrowsExceptionForAssociativeArray() {
        $this->expectException( DomainException::class );
        $this->expectExceptionMessage( "Invalid list provided" );

        new ArrayList( [ 'a' => 1, 'b' => 2 ] );
    }

    /**
     * Tests the add() method to ensure it appends elements correctly
     * and returns true.
     */
    public function testAdd() {
        $arrayList = new ArrayList();
        $this->assertCount( 0, $arrayList );

        $result1 = $arrayList->add( 'first_item' );
        $this->assertTrue( $result1 );
        $this->assertCount( 1, $arrayList );
        $this->assertEquals( 'first_item', $arrayList[ 0 ] );

        $result2 = $arrayList->add( 'second_item' );
        $this->assertTrue( $result2 );
        $this->assertCount( 2, $arrayList );
        $this->assertEquals( 'second_item', $arrayList[ 1 ] );
    }

    /**
     * Tests the get() method and direct array access for retrieving elements.
     * It also verifies that null is returned for non-existent keys.
     */
    public function testGetAndOffsetGet() {
        $arrayList = new ArrayList( [ 'foo', 'bar' ] );

        // Test the get() method
        $this->assertEquals( 'foo', $arrayList->get( 0 ) );
        $this->assertEquals( 'bar', $arrayList->get( 1 ) );

        // Test direct array access (offsetGet)
        $this->assertEquals( 'foo', $arrayList[ 0 ] );

        // Test non-existent keys
        $this->assertNull( $arrayList->get( 2 ) );
        $this->assertNull( $arrayList[ 99 ] );
    }

    /**
     * Tests that the ArrayList behaves correctly when used in a foreach loop.
     */
    public function testIteration() {
        $data         = [ 'one', 'two', 'three' ];
        $arrayList    = new ArrayList( $data );
        $iteratedData = [];

        foreach ( $arrayList as $key => $value ) {
            $iteratedData[ $key ] = $value;
        }

        $this->assertSame( $data, $iteratedData );
    }
}
