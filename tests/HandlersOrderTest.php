<?php
/**
 * Created by PhpStorm.
 * @author ostico domenico@translated.net / ostico@gmail.com
 * Date: 08/02/22
 * Time: 18:30
 *
 */

namespace Matecat\SubFiltering\Tests;

use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Filters\HtmlToPh;
use Matecat\SubFiltering\Filters\LtGtDecode;
use Matecat\SubFiltering\Filters\Percentages;
use Matecat\SubFiltering\Filters\PlaceHoldXliffTags;
use Matecat\SubFiltering\Filters\RestorePlaceHoldersToXLIFFLtGt;
use Matecat\SubFiltering\Filters\RestoreXliffTagsContent;
use Matecat\SubFiltering\Filters\RubyOnRailsI18n;
use Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\StandardPHToMateCatCustomPH;
use Matecat\SubFiltering\Filters\TwigToPh;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class HandlersOrderTest extends TestCase {

    /**
     * @var Pipeline
     */
    protected $channel;

    public function setUp(): void {
        $this->channel = new Pipeline( 'it-IT', 'en-US', [] );
        $this->channel->addLast( new StandardPHToMateCatCustomPH() );
        $this->channel->addLast( new PlaceHoldXliffTags() );
        $this->channel->addLast( new LtGtDecode() );
        $this->channel->addLast( new HtmlToPh() );
        $this->channel->addLast( new TwigToPh() );
        $this->channel->addLast( new SprintfToPH() );
        $this->channel->addLast( new RestoreXliffTagsContent() );
        $this->channel->addLast( new RestorePlaceHoldersToXLIFFLtGt() );
    }

    /**
     * @test
     */
    public function testReOrder() {

        $this->channel->remove( new TwigToPh() );
        $this->channel->remove( new SprintfToPH() );

        $this->channel->addAfter( new HtmlToPh(), new RubyOnRailsI18n() );
        $this->channel->addAfter( new RubyOnRailsI18n(), new Percentages() );
        $this->channel->addAfter( new Percentages(), new SprintfToPH() );
        $this->channel->addAfter( new SprintfToPH(), new TwigToPh() );
        $this->channel->addAfter( new TwigToPh(), new SingleCurlyBracketsToPh() );

        $reflection = new ReflectionClass( $this->channel );
        $property   = $reflection->getProperty( 'handlers' );
        $property->setAccessible( true );
        $handlerList = $property->getValue( $this->channel );

        $this->assertTrue( $handlerList[ 0 ] instanceof StandardPHToMateCatCustomPH );
        $this->assertTrue( $handlerList[ 1 ] instanceof PlaceHoldXliffTags );
        $this->assertTrue( $handlerList[ 2 ] instanceof LtGtDecode );
        $this->assertTrue( $handlerList[ 3 ] instanceof HtmlToPh );
        $this->assertTrue( $handlerList[ 4 ] instanceof RubyOnRailsI18n );
        $this->assertTrue( $handlerList[ 5 ] instanceof Percentages );
        $this->assertTrue( $handlerList[ 6 ] instanceof SprintfToPH );
        $this->assertTrue( $handlerList[ 7 ] instanceof TwigToPh );
        $this->assertTrue( $handlerList[ 8 ] instanceof SingleCurlyBracketsToPh );
        $this->assertTrue( $handlerList[ 9 ] instanceof RestoreXliffTagsContent );
        $this->assertTrue( $handlerList[ 10 ] instanceof RestorePlaceHoldersToXLIFFLtGt );

    }

    /**
     * @test
     */
    public function testReOrder2() {

        $this->channel->remove( new TwigToPh() );
        $this->channel->remove( new SprintfToPH() );
        $this->channel->addFirst( new SprintfToPH() );

        $this->channel->addBefore( new HtmlToPh(), new RubyOnRailsI18n() );
        $this->channel->remove( new HtmlToPh() );

        $reflection = new ReflectionClass( $this->channel );
        $property   = $reflection->getProperty( 'handlers' );
        $property->setAccessible( true );
        $handlerList = $property->getValue( $this->channel );

        $this->assertTrue( $handlerList[ 0 ] instanceof SprintfToPH );
        $this->assertTrue( $handlerList[ 1 ] instanceof StandardPHToMateCatCustomPH );
        $this->assertTrue( $handlerList[ 2 ] instanceof PlaceHoldXliffTags );
        $this->assertTrue( $handlerList[ 3 ] instanceof LtGtDecode );
        $this->assertTrue( $handlerList[ 4 ] instanceof RubyOnRailsI18n );
        $this->assertTrue( $handlerList[ 5 ] instanceof RestoreXliffTagsContent );
        $this->assertTrue( $handlerList[ 6 ] instanceof RestorePlaceHoldersToXLIFFLtGt );

    }

}