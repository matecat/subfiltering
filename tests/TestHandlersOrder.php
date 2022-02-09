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

class TestHandlersOrder extends TestCase {

    /**
     * @var Pipeline
     */
    protected $channel;

    public function setUp() {
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
        $handlersList = $property->getValue( $this->channel );

        $this->assertTrue( $handlersList[ 0 ] instanceof StandardPHToMateCatCustomPH );
        $this->assertTrue( $handlersList[ 1 ] instanceof PlaceHoldXliffTags );
        $this->assertTrue( $handlersList[ 2 ] instanceof LtGtDecode );
        $this->assertTrue( $handlersList[ 3 ] instanceof HtmlToPh );
        $this->assertTrue( $handlersList[ 4 ] instanceof RubyOnRailsI18n );
        $this->assertTrue( $handlersList[ 5 ] instanceof Percentages );
        $this->assertTrue( $handlersList[ 6 ] instanceof SprintfToPH );
        $this->assertTrue( $handlersList[ 7 ] instanceof TwigToPh );
        $this->assertTrue( $handlersList[ 8 ] instanceof SingleCurlyBracketsToPh );
        $this->assertTrue( $handlersList[ 9 ] instanceof RestoreXliffTagsContent );
        $this->assertTrue( $handlersList[ 10 ] instanceof RestorePlaceHoldersToXLIFFLtGt );

    }

}