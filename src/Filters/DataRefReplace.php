<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\XliffParser\Utils\HtmlParser;
use Matecat\XliffParser\XliffUtils\DataRefReplacer;

class DataRefReplace extends AbstractHandler {

    /**
     * @var array
     */
    private $dataRefMap;

    /**
     * DataRefReplace constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function transform( $segment ) {

        if ( empty( $this->dataRefMap ) ) {
            $this->dataRefMap = $this->pipeline->getDataRefMap();
        }

        // dataRefMap is present only in xliff 2.0 files
        if ( empty( $this->dataRefMap ) ) {
            $segment = $this->replaceXliffPhTagsWithoutDataRefCorrespondenceToMatecatPhTags( $segment );

            return $this->replaceXliffPcTagsToMatecatPhTags( $segment );
        }

        $dataRefReplacer = new DataRefReplacer( $this->dataRefMap );
        $segment         = $dataRefReplacer->replace( $segment );
        $segment         = $this->replaceXliffPhTagsWithoutDataRefCorrespondenceToMatecatPhTags( $segment );

        return $this->replaceXliffPcTagsToMatecatPhTags( $segment );
    }

    /**
     * This function replace encoded ph tags (from Xliff 2.0) without any dataRef correspondence
     * to regular Matecat <ph> tag for UI presentation
     *
     * Example:
     *
     * We can control who sees content when with <ph id="source1" dataRef="source1"/>Visibility Constraints.
     *
     * is transformed to:
     *
     * We can control who sees content when with &lt;ph id="mtc_ph_u_1" equiv-text="base64:PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/&gt;Visibility Constraints.
     *
     * @param $segment
     *
     * @return string|string[]
     */
    private function replaceXliffPhTagsWithoutDataRefCorrespondenceToMatecatPhTags( $segment ) {
        preg_match_all( '/<(ph .*?)>/iu', $segment, $phTags );

        if ( count( $phTags[ 0 ] ) === 0 ) {
            return $segment;
        }

        $phIndex = 1;

        foreach ( $phTags[ 0 ] as $phTag ) {
            // check if phTag has not any correspondence on dataRef map
            if ( $this->isAPhTagWithNoDataRefCorrespondence( $phTag ) ) {
                $phMatecat = '<ph id="mtc_ph_u_' . $phIndex . '" equiv-text="base64:' . base64_encode( $phTag ) . '"/>';
                $segment   = str_replace( $phTag, $phMatecat, $segment );
                $phIndex++;
            }
        }

        return $segment;
    }

    /**
     * This function checks if a ph tag with dataRef attribute
     * and without equiv-text
     * a correspondence on dataRef map
     *
     * @param string $phTag
     *
     * @return bool
     */
    private function isAPhTagWithNoDataRefCorrespondence( $phTag ) {
        $parsed = HtmlParser::parse( $phTag );

        if ( !isset( $parsed[ 0 ] ) ) {
            return false;
        }

        // if has equiv-text don't touch
        if ( isset( $parsed[ 0 ]->attributes[ 'equiv-text' ] ) ) {
            return false;
        }

        // if has dataRef attribute check if there is correspondence on dataRef map
        if ( isset( $parsed[ 0 ]->attributes[ 'dataRef' ] ) ) {
            return !array_key_exists( $parsed[ 0 ]->attributes[ 'dataRef' ], $this->dataRefMap );
        }

        return true;
    }

    /**
     * This function replace encoded pc tags (from Xliff 2.0) without any dataRef correspondence
     * to regular Matecat <ph> tag for UI presentation
     *
     * Example:
     *
     * Text &lt;ph id="source1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiIGRhdGFSZWZFbmQ9InNvdXJjZTEiJmd0Ow==" dataRef="source1" equiv-text="base64:eA=="/&gt;&lt;pc id="1u" type="fmt" subType="m:u"&gt;link&lt;/pc&gt;&lt;ph id="source1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source1" equiv-text="base64:eA=="/&gt;.
     *
     * is transformed to:
     *
     * Text &lt;ph id="source1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiIGRhdGFSZWZFbmQ9InNvdXJjZTEiJmd0Ow==" dataRef="source1" equiv-text="base64:eA=="/&gt;&lt;ph id="mtc_u_1" equiv-text="base64:Jmx0O3BjIGlkPSIxdSIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOnUiJmd0Ow=="/&gt;link&lt;ph id="mtc_u_2" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt;&lt;ph id="source1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source1" equiv-text="base64:eA=="/&gt;.
     *
     * @param $segment
     *
     * @return string|string[]
     */
    private function replaceXliffPcTagsToMatecatPhTags( $segment ) {

        preg_match_all( '/<(pc .*?)>/iu', $segment, $openingPcTags );
        preg_match_all( '/<(\/pc)>/iu', $segment, $closingPcTags );

        if ( count( $openingPcTags[ 0 ] ) === 0 ) {
            return $segment;
        }

        $phIndex = 1;

        foreach ( $openingPcTags[ 0 ] as $openingPcTag ) {
            $phMatecat = '<ph id="mtc_u_' . $phIndex . '" equiv-text="base64:' . base64_encode( $openingPcTag ) . '"/>';
            $segment   = str_replace( $openingPcTag, $phMatecat, $segment );
            $phIndex++;
        }

        foreach ( $closingPcTags[ 0 ] as $closingPcTag ) {
            $phMatecat = '<ph id="mtc_u_' . $phIndex . '" equiv-text="base64:' . base64_encode( $closingPcTag ) . '"/>';
            $segment   = str_replace( $closingPcTag, $phMatecat, $segment );
            $phIndex++;
        }

        return $segment;
    }
}