<?php

namespace Matecat\SubFiltering;


use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Filters\CtrlCharsPlaceHoldToAscii;
use Matecat\SubFiltering\Filters\DollarCurlyBrackets;
use Matecat\SubFiltering\Filters\DoubleSnail;
use Matecat\SubFiltering\Filters\DoubleSquareBrackets;
use Matecat\SubFiltering\Filters\EncodeToRawXML;
use Matecat\SubFiltering\Filters\FromViewNBSPToSpaces;
use Matecat\SubFiltering\Filters\HtmlPlainTextDecoder;
use Matecat\SubFiltering\Filters\HtmlToPh;
use Matecat\SubFiltering\Filters\LtGtDecode;
use Matecat\SubFiltering\Filters\LtGtEncode;
use Matecat\SubFiltering\Filters\MateCatCustomPHToStandardPH;
use Matecat\SubFiltering\Filters\Percentages;
use Matecat\SubFiltering\Filters\PercentNumberSnail;
use Matecat\SubFiltering\Filters\PercentSnail;
use Matecat\SubFiltering\Filters\PlaceHoldXliffTags;
use Matecat\SubFiltering\Filters\RemoveCTypeFromPhTags;
use Matecat\SubFiltering\Filters\RestoreEquivTextPhToXliffOriginal;
use Matecat\SubFiltering\Filters\RestorePlaceHoldersToXLIFFLtGt;
use Matecat\SubFiltering\Filters\RestoreXliffTagsContent;
use Matecat\SubFiltering\Filters\RestoreXliffTagsInXliff;
use Matecat\SubFiltering\Filters\RubyOnRailsI18n;
use Matecat\SubFiltering\Filters\SmartCounts;
use Matecat\SubFiltering\Filters\Snails;
use Matecat\SubFiltering\Filters\SplitPlaceholder;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\SquareSprintf;
use Matecat\SubFiltering\Filters\StandardPHToMateCatCustomPH;
use Matecat\SubFiltering\Filters\SubFilteredPhToHtml;
use Matecat\SubFiltering\Filters\TwigToPh;
use Matecat\SubFiltering\Filters\Variables;

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
class MyMemoryFilter extends AbstractFilter {

    /**
     * Used to transform database raw xml content ( Layer 0 ) to the sub filtered structures, used for server to server ( Ex: TM/MT ) communications ( Layer 1 )
     *
     * @param $segment
     * @param $cid
     *
     * @return mixed
     */
    public function fromLayer0ToLayer1( $segment, $cid = null ) {
        $channel = new Pipeline( $this->source, $this->target );
        $channel->addLast( new StandardPHToMateCatCustomPH() );
        $channel->addLast( new PlaceHoldXliffTags() );
        $channel->addLast( new LtGtDecode() );
        $channel->addLast( new HtmlToPh() );
        $channel->addLast( new Variables() );
        $channel->addLast( new TwigToPh() );

        if ( $cid == 'airbnb' ) {
            $channel->addLast( new SmartCounts() );
        }

        $channel->addLast( new RubyOnRailsI18n() );
        $channel->addLast( new Snails() );
        $channel->addLast( new DoubleSquareBrackets() );
        $channel->addLast( new DollarCurlyBrackets() );
        $channel->addLast( new PercentSnail() );
        $channel->addLast( new PercentNumberSnail() );
        $channel->addLast( new Percentages() );
        $channel->addLast( new SquareSprintf() );
        $channel->addLast( new SprintfToPH() );
        $channel->addLast( new RestoreXliffTagsContent() );
        $channel->addLast( new RestorePlaceHoldersToXLIFFLtGt() );

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
    public function fromLayer1ToLayer0( $segment ) {
        $channel = new Pipeline( $this->source, $this->target, $this->dataRefMap );
        $channel->addLast( new FromViewNBSPToSpaces() );
        $channel->addLast( new CtrlCharsPlaceHoldToAscii() );
        $channel->addLast( new MateCatCustomPHToStandardPH() );
        $channel->addLast( new SubFilteredPhToHtml() );
        $channel->addLast( new PlaceHoldXliffTags() );
        $channel->addLast( new RestoreXliffTagsInXliff() );
        $channel->addLast( new HtmlPlainTextDecoder() );
        $channel->addLast( new EncodeToRawXML() );
        $channel->addLast( new LtGtEncode() );
        $channel->addLast( new RestoreXliffTagsContent() );
        $channel->addLast( new RestoreEquivTextPhToXliffOriginal() );
        $channel->addLast( new RestorePlaceHoldersToXLIFFLtGt() );
        $channel->addLast( new SplitPlaceholder() );
        $channel->addLast( new RemoveCTypeFromPhTags() );

        /** @var $channel Pipeline */
        $channel = $this->featureSet->filter( 'fromLayer1ToLayer0', $channel );

        return $channel->transform( $segment );
    }
}