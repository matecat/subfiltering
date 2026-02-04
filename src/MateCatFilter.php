<?php

namespace Matecat\SubFiltering;

use Exception;
use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Filters\CtrlCharsPlaceHoldToAscii;
use Matecat\SubFiltering\Filters\DataRefReplace;
use Matecat\SubFiltering\Filters\DataRefRestore;
use Matecat\SubFiltering\Filters\EmojiToEntity;
use Matecat\SubFiltering\Filters\EncodeControlCharsInXliff;
use Matecat\SubFiltering\Filters\EntityToEmoji;
use Matecat\SubFiltering\Filters\FromLayer2ToRawXML;
use Matecat\SubFiltering\Filters\LtGtEncode;
use Matecat\SubFiltering\Filters\PlaceHoldXliffTags;
use Matecat\SubFiltering\Filters\RemoveDangerousChars;
use Matecat\SubFiltering\Filters\RestorePlaceHoldersToXLIFFLtGt;
use Matecat\SubFiltering\Filters\RestoreXliffTagsContent;
use Matecat\SubFiltering\Filters\SpecialEntitiesToPlaceholdersForView;

/**
 * Class Filter
 *
 * This class is meant to create subfiltering layers to allow data to be safely sent and received from 2 different Layers and real file
 *
 * # Definitions
 *
 * - Raw file, the real XML file in input, with data in XML
 * - Layer 0 is defined to be the Database. The data stored in the database should be in the same form (sanitized if needed) they come from Xliff file
 * - Layer 1 is defined to be external services and resources, for example, MT/TM server. This layer is different from layer 0, HTML subfiltering is applied here
 * - Layer 2 is defined to be the MayeCat UI.
 *
 * # Constraints
 * - We have to maintain the compatibility with PH tags placed inside the XLIff in the form <ph id="[0-9+]" equiv-text="&lt;br/&gt;"/> .
 *     Those tags are placed into the database as XML.
 * - HTML and other variables like android tags and custom features are placed into the database as encoded HTML &lt;br/&gt;
 *
 * - Data sent to the external services like MT/TM are sub-filtered:
 * -- &lt;br/&gt; become <ph id="mtc_[0-9]+" equiv-text="base64:Jmx0O2JyLyZndDs="/>
 * -- Existent tags in the XLIFF like <ph id="[0-9+]" equiv-text="&lt;br/&gt;"/> will leave as is
 *
 *
 * @package SubFiltering
 */
class MateCatFilter extends AbstractFilter
{

    /**
     * Transforms database raw XML content (Layer 0) to intermediate structures (Layer 1).
     *
     * @param string $segment The data segment to transform from Layer 0 to Layer 1.
     * @param string|null $cid Optional context identifier for the transformation process.
     *
     * @return string The transformed segment in Layer 1 format.
     * @throws Exception If the transformation process fails.
     */
    public function fromLayer0ToLayer1(string $segment, ?string $cid = null): string
    {
        $channel = new Pipeline($this->source, $this->target, $this->dataRefMap);

        $this->configureFromLayer0ToLayer1Pipeline($channel);

        // Allow the feature set to modify the pipeline for this specific transformation.
        /** @type $channel Pipeline */
        $channel = $this->featureSet->filter('fromLayer0ToLayer1', $channel);

        // Process the segment and return the result.
        return $channel->transform($segment);
    }

    /**
     * Used to transform database raw XML content (Layer 0) to the UI structures (Layer 2)
     *
     * @param string $segment
     *
     * @return string
     * @throws Exception
     */
    public function fromLayer0ToLayer2(string $segment): string
    {
        return $this->fromLayer1ToLayer2(
            $this->fromLayer0ToLayer1($segment)
        );
    }

    /**
     * Used to transform database raw XML content (Layer 0) to the UI structures (Layer 2)
     *
     * @param string $segment
     *
     * @return string
     * @throws Exception
     */
    public function fromLayer1ToLayer2(string $segment): string
    {
        $channel = new Pipeline($this->source, $this->target, $this->dataRefMap);
        $channel->addLast(SpecialEntitiesToPlaceholdersForView::class);
        $channel->addLast(EntityToEmoji::class);
        $channel->addLast(DataRefReplace::class);

        /** @type $channel Pipeline */
        $channel = $this->featureSet->filter('fromLayer1ToLayer2', $channel);

        return $channel->transform($segment);
    }

    /**
     * Used to transform UI data (Layer 2) to the XML structures (Layer 1)
     *
     * @param string $segment
     *
     * @return string
     * @throws Exception
     */
    public function fromLayer2ToLayer1(string $segment): string
    {
        $channel = new Pipeline($this->source, $this->target, $this->dataRefMap);
        $channel->addLast(CtrlCharsPlaceHoldToAscii::class);
        $channel->addLast(PlaceHoldXliffTags::class);
        $channel->addLast(FromLayer2TorawXML::class);
        $channel->addLast(EmojiToEntity::class);
        $channel->addLast(RestoreXliffTagsContent::class);
        $channel->addLast(RestorePlaceHoldersToXLIFFLtGt::class);
        $channel->addLast(DataRefRestore::class);

        /** @type $channel Pipeline */
        $channel = $this->featureSet->filter('fromLayer2ToLayer1', $channel);

        return $channel->transform($segment);
    }

    /**
     *
     * Used to transform the UI structures (Layer 2) to allow them to be stored in the database (Layer 0)
     *
     * It is assumed that the UI sends strings having XLF tags not encoded and HTML in XML encoding representation:
     * - &lt;b&gt;de <ph id="mtc_1" equiv-text="base64:JTEkcw=="/>, <x id="1" /> &lt;/b&gt;que
     *
     * @param string $segment
     *
     * @return string
     * @throws Exception
     */
    public function fromLayer2ToLayer0(string $segment): string
    {
        return $this->fromLayer1ToLayer0(
            $this->fromLayer2ToLayer1($segment)
        );
    }

    /**
     * Used to convert the raw XLIFF content from the file to an XML for the database (Layer 0)
     *
     * @param string $segment
     *
     * @return string
     * @throws Exception
     */
    public function fromRawXliffToLayer0(string $segment): string
    {
        $channel = new Pipeline($this->source, $this->target, $this->dataRefMap);
        $channel->addLast(RemoveDangerousChars::class);
        $channel->addLast(PlaceHoldXliffTags::class);
        $channel->addLast(EncodeControlCharsInXliff::class);
        $channel->addLast(RestoreXliffTagsContent::class);
        $channel->addLast(RestorePlaceHoldersToXLIFFLtGt::class);

        /** @type $channel Pipeline */
        $channel = $this->featureSet->filter('fromRawXliffToLayer0', $channel);

        return $channel->transform($segment);
    }

    /**
     * Used to export Database XML string into TMX files as valid XML
     *
     * @param string $segment
     *
     * @return string
     * @throws Exception
     */
    public function fromLayer0ToRawXliff(string $segment): string
    {
        $channel = new Pipeline($this->source, $this->target, $this->dataRefMap);
        $channel->addLast(PlaceHoldXliffTags::class);
        $channel->addLast(RemoveDangerousChars::class);
        $channel->addLast(RestoreXliffTagsContent::class);
        $channel->addLast(RestorePlaceHoldersToXLIFFLtGt::class);
        $channel->addLast(LtGtEncode::class);

        /** @type $channel Pipeline */
        $channel = $this->featureSet->filter('fromLayer0ToRawXliff', $channel);

        return $channel->transform($segment);
    }

    /**
     * Used to align the tags when created from Layer 0 to Layer 1, when converting data from the database is possible that HTML placeholders are in different positions
     * and their id are different because they are simple sequences.
     * We must place the right source tag ID in the corresponding target tags.
     *
     * The source holds the truth :D
     * realigns the target ids by matching the content of the base64.
     *
     * @param string $source
     * @param string $target
     *
     * @return string
     * @see getSegmentsController in matecat
     *
     */
    public function realignIDInLayer1(string $source, string $target): string
    {
        $pattern = '|<ph id ?= ?["\'](mtc_\d+)["\'] ?(equiv-text=["\'].+?["\'] ?)/>|ui';
        preg_match_all($pattern, $source, $src_tags, PREG_PATTERN_ORDER);
        preg_match_all($pattern, $target, $trg_tags, PREG_PATTERN_ORDER);

        if (count($src_tags[0]) != count($trg_tags[0])) {
            return $target; //WRONG NUMBER OF TAGS, in the translation there is a tag mismatch, let the user fix it
        }

        $notFoundTargetTags = [];

        $start_offset = 0;
        foreach ($trg_tags[2] as $trg_tag_position => $b64) {
            $src_tag_position = array_search($b64, $src_tags[2], true);

            if ($src_tag_position === false) {
                //this means that the content of a tag is changed in the translation
                $notFoundTargetTags[$trg_tag_position] = $b64;
                continue;
            } else {
                unset($src_tags[2][$src_tag_position]); // remove the index to allow array_search to find the equal next one if it is present
            }

            //replace ONLY ONE element AND the EXACT ONE
            $tag_position_in_string = (int)strpos($target, $trg_tags[0][$trg_tag_position], $start_offset); //force integer, false or 0 is the same for us in this case
            $target = (string)substr_replace(
                $target,
                $src_tags[0][$src_tag_position],
                $tag_position_in_string,
                strlen($trg_tags[0][$trg_tag_position])
            );
            $start_offset = $tag_position_in_string + strlen(
                    $src_tags[0][$src_tag_position]
                ); // set the next starting point
        }

        if (!empty($notFoundTargetTags)) {
            //do something ?!? how to re-align if they are changed in value and changed in position?
        }

        return $target;
    }
}