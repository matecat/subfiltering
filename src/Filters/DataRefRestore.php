<?php

namespace Matecat\SubFiltering\Filters;

use Exception;
use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\Utils\DataRefReplacer;

class DataRefRestore extends AbstractHandler
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
     * @throws Exception
     */
    public function transform(string $segment): string
    {
        if (empty($this->dataRefMap)) {
            $this->dataRefMap = $this->pipeline->getDataRefMap();
        }

        if (empty($this->dataRefMap)) {
            $segment = $this->restoreXliffPhTagsFromMatecatPhTags($segment);

            return $this->restoreXliffPcTagsFromMatecatPhTags($segment);
        }

        $dataRefReplacer = new DataRefReplacer($this->dataRefMap);
        $segment = $dataRefReplacer->restore($segment);
        $segment = $this->restoreXliffPhTagsFromMatecatPhTags($segment);

        return $this->restoreXliffPcTagsFromMatecatPhTags($segment);
    }

    /**
     * This function restores <ph> tags (encoded as Matecat ph tags) without any dataRef correspondence
     * for the persistence layer (layer 0).
     *
     * Example:
     *
     * Saame nähtavuse piirangutega kontrollida, kes sisu näeb .<ph id="mtc_ph_u_1" equiv-text="base64:Jmx0O3BoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8mZ3Q7"/>
     *
     * is transformed to:
     *
     * Saame nähtavuse piirangutega kontrollida, kes sisu näeb .<ph id="source1" dataRef="source1"/>
     *
     * @param string $segment
     *
     * @return string
     */
    private function restoreXliffPhTagsFromMatecatPhTags(string $segment): string
    {
        preg_match_all(
            '|<ph[^>]+? ctype="' . CTypeEnum::ORIGINAL_PH_OR_NOT_DATA_REF . '" equiv-text="base64:(.*?)"/>|iu',
            $segment,
            $matches
        );

        if (empty($matches[0])) {
            return $segment;
        }

        foreach ($matches[0] as $index => $match) {
            $segment = str_replace($match, base64_decode($matches[1][$index]), $segment);
        }

        return $segment;
    }

    /**
     * This function restores <pc> tags (encoded as Matecat ph tags) without any dataRef correspondence
     * for the persistence layer (layer 0).
     *
     * Example:
     *
     * Testo <ph id="source1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiIGRhdGFSZWZFbmQ9InNvdXJjZTEiJmd0Ow==" dataRef="source1" equiv-text="base64:Xw=="/><ph id="mtc_u_1" equiv-text="base64:Jmx0O3BjIGlkPSIxdSIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOnUiJmd0Ow=="/><ph id="mtc_u_2" equiv-text="base64:Jmx0Oy9wYyZndDs="/><ph id="source1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source1" equiv-text="base64:Xw=="/>
     *
     * it is transformed to:
     *
     * Testo <pc id="source1" dataRefStart="source1" dataRefEnd="source1"><pc id="1u" type="fmt" subType="m:u"></pc></pc>
     *
     * @param string $segment
     *
     * @return string
     */
    private function restoreXliffPcTagsFromMatecatPhTags(string $segment): string
    {
        $matches = [];
        preg_match_all(
            '|<ph[^>]+? ctype="' . CTypeEnum::ORIGINAL_PC_OPEN_NO_DATA_REF . '" equiv-text="base64:(.*?)"/>|iu',
            $segment,
            $open_matches
        );
        preg_match_all(
            '|<ph[^>]+? ctype="' . CTypeEnum::ORIGINAL_PC_CLOSE_NO_DATA_REF . '" equiv-text="base64:(.*?)"/>|iu',
            $segment,
            $close_matches
        );

        $matches[0] = array_merge($open_matches[0], $close_matches[0]);
        $matches[1] = array_merge($open_matches[1], $close_matches[1]);

        if (empty($matches[0])) {
            return $segment;
        }

        foreach ($matches[0] as $index => $match) {
            $segment = str_replace($match, base64_decode($matches[1][$index]), $segment);
        }

        return $segment;
    }
}