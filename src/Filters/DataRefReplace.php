<?php

namespace Matecat\SubFiltering\Filters;

use Exception;
use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\Utils\DataRefReplacer;
use Matecat\XmlParser\XmlParser;

class DataRefReplace extends AbstractHandler
{
    /**
     * @var array<string,string>
     */
    private array $dataRefMap = [];

    /**
     * DataRefReplace constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function transform(string $segment): string
    {
        if (empty($this->dataRefMap)) {
            $this->dataRefMap = $this->pipeline->getDataRefMap();
        }

        // dataRefMap is present only in xliff 2.0 files
        if (empty($this->dataRefMap)) {
            $segment = $this->replace_Ph_TagsWithoutDataRefCorrespondenceToMatecatPhTags($segment);

            return $this->replace_Pc_TagsWithoutDataRefCorrespondenceToMatecatPhTags($segment);
        }

        $dataRefReplacer = new DataRefReplacer($this->dataRefMap);
        $segment = $dataRefReplacer->replace($segment);
        $segment = $this->replace_Ph_TagsWithoutDataRefCorrespondenceToMatecatPhTags($segment);

        return $this->replace_Pc_TagsWithoutDataRefCorrespondenceToMatecatPhTags($segment);
    }

    /**
     * This function replaces encoded ph tags (from Xliff 2.0) without any dataRef correspondence
     * to the regular Matecat < ph > tag for UI presentation
     *
     * Example:
     *
     * We can control who sees content when with <ph id="source1" dataRef="source1"/>Visibility Constraints.
     *
     * Is transformed to:
     *
     * We can control who sees content when with &lt;ph id="mtc_ph_u_1" equiv-text="base64:PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/&gt;Visibility Constraints.
     *
     * @param string $segment
     *
     * @return string
     */
    private function replace_Ph_TagsWithoutDataRefCorrespondenceToMatecatPhTags(string $segment): string
    {
        preg_match_all('/<(ph .*?)>/iu', $segment, $phTags);

        if (count($phTags[0]) === 0) {
            return $segment;
        }

        foreach ($phTags[0] as $phTag) {
            // check if phTag has not any correspondence on dataRef map
            if ($this->isAValidPhTag($phTag)) {
                $segment = preg_replace(
                    '/' . preg_quote($phTag, '/') . '/',
                    '<ph id="' . $this->getPipeline()->getNextId() .
                    '" ctype="' . CTypeEnum::ORIGINAL_PH_OR_NOT_DATA_REF .
                    '" equiv-text="base64:' . base64_encode($phTag) .
                    '"/>',
                    $segment,
                    1 // replace ONLY ONE occurrence
                );
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
    private function isAValidPhTag(string $phTag): bool
    {
        // try not to throw exception for wrong segments with opening tags and no closing
        try {
            $parsed = XmlParser::parse($phTag, true);
        } catch (Exception) {
            return false;
        }

        // check for matecat ctype
        $cType = isset($parsed[0]->attributes['ctype']) ? $parsed[0]->attributes['ctype'] : 'not-found';
        if (CTypeEnum::isMatecatCType($cType)) {
            return false;
        }

        // if has equiv-text don't touch
        if (isset($parsed[0]->attributes['equiv-text'])) {
            return false;
        }

        if (isset($parsed[0]->attributes['dataRef'])) {
            return !array_key_exists($parsed[0]->attributes['dataRef'], $this->dataRefMap);
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
     * @param string $segment
     *
     * @return string
     */
    private function replace_Pc_TagsWithoutDataRefCorrespondenceToMatecatPhTags(string $segment): string
    {
        preg_match_all('/<(pc .*?)>/iu', $segment, $openingPcTags);
        preg_match_all('|<(/pc)>|iu', $segment, $closingPcTags);

        if (count($openingPcTags[0]) === 0) {
            return $segment;
        }

        foreach ($openingPcTags[0] as $openingPcTag) {
            $segment = preg_replace(
                '/' . preg_quote($openingPcTag, '/') . '/',
                '<ph id="' . $this->getPipeline()->getNextId() .
                '" ctype="' . CTypeEnum::ORIGINAL_PC_OPEN_NO_DATA_REF .
                '" equiv-text="base64:' . base64_encode($openingPcTag) .
                '"/>',
                $segment,
                1
            );
        }

        foreach ($closingPcTags[0] as $closingPcTag) {
            $segment = preg_replace(
                '/' . preg_quote($closingPcTag, '/') . '/',
                '<ph id="' . $this->getPipeline()->getNextId() .
                '" ctype="' . CTypeEnum::ORIGINAL_PC_CLOSE_NO_DATA_REF .
                '" equiv-text="base64:' . base64_encode($closingPcTag) .
                '"/>',
                $segment,
                1
            );
        }

        return $segment;
    }
}
