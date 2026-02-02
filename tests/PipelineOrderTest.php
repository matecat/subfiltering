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
use Matecat\SubFiltering\Filters\DoublePercentages;
use Matecat\SubFiltering\Filters\MarkupToPh;
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

class PipelineOrderTest extends TestCase
{

    /**
     * @var Pipeline
     */
    protected $channel;

    public function setUp(): void
    {
        $this->channel = new Pipeline('it-IT', 'en-US', []);
        $this->channel->addLast(StandardPHToMateCatCustomPH::class);
        $this->channel->addLast(PlaceHoldXliffTags::class);
        $this->channel->addLast(MarkupToPh::class);
        $this->channel->addLast(TwigToPh::class);
        $this->channel->addLast(SprintfToPH::class);
        $this->channel->addLast(RestoreXliffTagsContent::class);
        $this->channel->addLast(RestorePlaceHoldersToXLIFFLtGt::class);
    }

    /**
     * @test
     */
    public function testReOrder()
    {
        $this->channel->remove(TwigToPh::class);
        $this->channel->remove(SprintfToPH::class);

        $this->channel->addAfter(MarkupToPh::class, RubyOnRailsI18n::class);
        $this->channel->addAfter(RubyOnRailsI18n::class, DoublePercentages::class);
        $this->channel->addAfter(DoublePercentages::class, SprintfToPH::class);
        $this->channel->addAfter(SprintfToPH::class, TwigToPh::class);
        $this->channel->addAfter(TwigToPh::class, SingleCurlyBracketsToPh::class);

        $reflection = new ReflectionClass($this->channel);
        $property = $reflection->getProperty('handlers');
        $handlerList = $property->getValue($this->channel);

        $this->assertTrue($handlerList[0] instanceof StandardPHToMateCatCustomPH);
        $this->assertTrue($handlerList[1] instanceof PlaceHoldXliffTags);
        $this->assertTrue($handlerList[2] instanceof MarkupToPh);
        $this->assertTrue($handlerList[3] instanceof RubyOnRailsI18n);
        $this->assertTrue($handlerList[4] instanceof DoublePercentages);
        $this->assertTrue($handlerList[5] instanceof SprintfToPH);
        $this->assertTrue($handlerList[6] instanceof TwigToPh);
        $this->assertTrue($handlerList[7] instanceof SingleCurlyBracketsToPh);
        $this->assertTrue($handlerList[8] instanceof RestoreXliffTagsContent);
        $this->assertTrue($handlerList[9] instanceof RestorePlaceHoldersToXLIFFLtGt);
    }

    /**
     * @test
     */
    public function testReOrder2()
    {
        $this->channel->remove(TwigToPh::class);
        $this->channel->remove(SprintfToPH::class);
        $this->channel->addFirst(SprintfToPH::class);

        $this->channel->addBefore(MarkupToPh::class, RubyOnRailsI18n::class);
        $this->channel->remove(MarkupToPh::class);

        $reflection = new ReflectionClass($this->channel);
        $property = $reflection->getProperty('handlers');
        $handlerList = $property->getValue($this->channel);

        $this->assertTrue($handlerList[0] instanceof SprintfToPH);
        $this->assertTrue($handlerList[1] instanceof StandardPHToMateCatCustomPH);
        $this->assertTrue($handlerList[2] instanceof PlaceHoldXliffTags);
        $this->assertTrue($handlerList[3] instanceof RubyOnRailsI18n);
        $this->assertTrue($handlerList[4] instanceof RestoreXliffTagsContent);
        $this->assertTrue($handlerList[5] instanceof RestorePlaceHoldersToXLIFFLtGt);
    }

}