<?php

namespace Matecat\SubFiltering;

use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Contracts\FeatureSetInterface;
use Matecat\SubFiltering\Filters\CtrlCharsPlaceHoldToAscii;
use Matecat\SubFiltering\Filters\DataRefReplace;
use Matecat\SubFiltering\Filters\DataRefRestore;
use Matecat\SubFiltering\Filters\EncodeToRawXML;
use Matecat\SubFiltering\Filters\FromLayer2ToRawXML;
use Matecat\SubFiltering\Filters\FromViewNBSPToSpaces;
use Matecat\SubFiltering\Filters\HtmlPlainTextDecoder;
use Matecat\SubFiltering\Filters\HtmlToPh;
use Matecat\SubFiltering\Filters\LtGtDecode;
use Matecat\SubFiltering\Filters\LtGtDoubleEncode;
use Matecat\SubFiltering\Filters\LtGtEncode;
use Matecat\SubFiltering\Filters\MateCatCustomPHToStandardPH;
use Matecat\SubFiltering\Filters\PlaceBreakingSpacesInXliff;
use Matecat\SubFiltering\Filters\PlaceHoldXliffTags;
use Matecat\SubFiltering\Filters\RemoveDangerousChars;
use Matecat\SubFiltering\Filters\RestoreEquivTextPhToXliffOriginal;
use Matecat\SubFiltering\Filters\RestorePlaceHoldersToXLIFFLtGt;
use Matecat\SubFiltering\Filters\RestoreTabsPlaceholders;
use Matecat\SubFiltering\Filters\RestoreXliffTagsContent;
use Matecat\SubFiltering\Filters\RestoreXliffTagsForView;
use Matecat\SubFiltering\Filters\SpacesToNBSPForView;
use Matecat\SubFiltering\Filters\SplitPlaceholder;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\StandardPHToMateCatCustomPH;
use Matecat\SubFiltering\Filters\SubFilteredPhToHtml;
use Matecat\SubFiltering\Filters\TwigToPh;

/**
 * Class MyMemoryFilter
 *
 * Specific Filter class used by MyMemory
 *
 * Please note that this is the BASIC filter. To add specific filter functions use specific FeatureSet
 *(see MyMemorySubFilteringTest example)
 *
 * https://mymemory.translated.net/
 *
 * @package Matecat\SubFiltering
 */
class MyMemoryFilter extends Filter {

    /**
     * Used to transform database raw xml content ( Layer 0 ) to the sub filtered structures, used for server to server ( Ex: TM/MT ) communications ( Layer 1 )
     *
     * @param $segment
     *
     * @return mixed
     * @throws \Exception
     */
    public function fromLayer0ToLayer1($segment)
    {
        $channel = new Pipeline();
        $channel->addLast( new StandardPHToMateCatCustomPH() );
        $channel->addLast( new PlaceHoldXliffTags() );
        $channel->addLast( new LtGtDecode() );
        $channel->addLast( new HtmlToPh() );
        $channel->addLast( new TwigToPh() );
        $channel->addLast( new SprintfToPH($this->source, $this->target) );
        $channel->addLast( new RestoreXliffTagsContent() );
        $channel->addLast( new RestorePlaceHoldersToXLIFFLtGt() );

        /** @var $channel Pipeline */
        $channel = $this->_featureSet->filter( 'fromLayer0ToLayer1', $channel );

        return $channel->transform( $segment );
    }

    /**
     * Used to transform external server raw xml content ( Ex: TM/MT ) to allow them to be stored in database ( Layer 0 ), used for server to server communications ( Layer 1 )
     *
     * @param $segment
     *
     * @return mixed
     * @throws \Exception
     */
    public function fromLayer1ToLayer0($segment)
    {
        $channel = new Pipeline();
        $channel->addLast( new FromViewNBSPToSpaces() );
        $channel->addLast( new CtrlCharsPlaceHoldToAscii() );
        $channel->addLast( new MateCatCustomPHToStandardPH() );
        $channel->addLast( new SubFilteredPhToHtml() );
        $channel->addLast( new PlaceHoldXliffTags() );
        $channel->addLast( new HtmlPlainTextDecoder() );
        $channel->addLast( new EncodeToRawXML() );
        $channel->addLast( new LtGtEncode() );
        $channel->addLast( new RestoreXliffTagsContent() );
        $channel->addLast( new RestoreEquivTextPhToXliffOriginal() );
        $channel->addLast( new RestorePlaceHoldersToXLIFFLtGt() );
        $channel->addLast( new SplitPlaceholder() );

        /** @var $channel Pipeline */
        $channel = $this->_featureSet->filter( 'fromLayer1ToLayer0', $channel );

        return $channel->transform( $segment );
    }
}