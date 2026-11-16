<?php

namespace Matecat\SubFiltering\Filters;

use Exception;
use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\Utils\DataRefReplacer;
use Matecat\XmlParser\XmlParser;
use OutOfBoundsException;

class DataRefReplace extends AbstractHandler
{

    /**
     * @var array<string,string>
     */
    private array $dataRefMap = [];

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
            $segment = $this->replacePhTagsWithoutDataRefCorrespondenceToMatecatPhTags($segment);

            return $this->replacePcTagsWithoutDataRefCorrespondenceToMatecatPhTags($segment);
        }

        $dataRefReplacer = new DataRefReplacer($this->dataRefMap);
        $segment = $dataRefReplacer->replace($segment);
        $segment = $this->replacePhTagsWithoutDataRefCorrespondenceToMatecatPhTags($segment);

        return $this->replacePcTagsWithoutDataRefCorrespondenceToMatecatPhTags($segment);
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
    private function replacePhTagsWithoutDataRefCorrespondenceToMatecatPhTags(string $segment): string
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
                    $this->getMatecatPhTag(
                        CTypeEnum::ORIGINAL_PH_OR_NOT_DATA_REF,
                        $phTag,
                        DataRefReplacer::getDisplayValue($this->getTagAttributes($phTag), 'disp', 'equiv')
                    ),
                    $segment,
                    1 // replace ONLY ONE occurrence
                ) ?? $segment;
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
        try {
            $parsed = XmlParser::parse($phTag, true);

            $attrs = $parsed[0]->attributes ?? [];
            $cType = $attrs['ctype'] ?? 'not-found';

            if (CTypeEnum::isMatecatCType($cType) || isset($attrs['equiv-text'])) {
                throw new OutOfBoundsException('Not a valid ph tag without dataRef correspondence');
            }

            if (isset($attrs['dataRef'])) {
                return !array_key_exists($attrs['dataRef'], $this->dataRefMap);
            }

            return true;
        } catch (Exception) {
            return false;
        }
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
    private function replacePcTagsWithoutDataRefCorrespondenceToMatecatPhTags(string $segment): string
    {
        preg_match_all('/<(pc .*?)>/iu', $segment, $openingPcTags);
        preg_match_all('|<(/pc)>|iu', $segment, $closingPcTags);

        if (count($openingPcTags[0]) === 0) {
            return $segment;
        }

        $closingPcDisplayValues = $this->getClosingPcDisplayValues($segment);

        foreach ($openingPcTags[0] as $openingPcTag) {
            $segment = preg_replace(
                '/' . preg_quote($openingPcTag, '/') . '/',
                $this->getMatecatPhTag(
                    CTypeEnum::ORIGINAL_PC_OPEN_NO_DATA_REF,
                    $openingPcTag,
                    DataRefReplacer::getDisplayValue($this->getTagAttributes($openingPcTag), 'dispStart', 'equivStart')
                ),
                $segment,
                1
            ) ?? $segment;
        }

        foreach ($closingPcTags[0] as $index => $closingPcTag) {
            $segment = preg_replace(
                '/' . preg_quote($closingPcTag, '/') . '/',
                $this->getMatecatPhTag(
                    CTypeEnum::ORIGINAL_PC_CLOSE_NO_DATA_REF,
                    $closingPcTag,
                    $closingPcDisplayValues[$index] ?? null
                ),
                $segment,
                1
            ) ?? $segment;
        }

        return $segment;
    }

    /**
     * Pair every </pc> (in document order) with its opening <pc> and return the dispEnd/equivEnd value of the latter
     *
     * @param string $segment
     *
     * @return array<int,?string>
     */
    private function getClosingPcDisplayValues(string $segment): array
    {
        preg_match_all('#<pc .*?>|</pc>#iu', $segment, $pcTags);

        $openingStack = [];
        $displayValues = [];

        foreach ($pcTags[0] as $pcTag) {
            if (str_starts_with($pcTag, '</')) {
                $displayValues[] = array_pop($openingStack);
            } elseif (!str_ends_with($pcTag, '/>')) {
                $openingStack[] = DataRefReplacer::getDisplayValue($this->getTagAttributes($pcTag), 'dispEnd', 'equivEnd');
            }
        }

        return $displayValues;
    }

    /**
     * @param string $tag a self-closed tag or an opening tag
     *
     * @return array<string,string>
     */
    private function getTagAttributes(string $tag): array
    {
        try {
            // add a closing tag to opening tags to not break XML integrity
            $xml = str_ends_with($tag, '/>') ? $tag : $tag . '</' . (preg_match('/^<([^\s>\/]+)/', $tag, $tagName) ? $tagName[1] : '') . '>';

            return XmlParser::parse($xml, true)[0]->attributes ?? [];
        } catch (Exception) {
            return [];
        }
    }

    /**
     * Build the Matecat <ph> tag for an XLIFF 2.0 tag without dataRef correspondence.
     *
     * When the tag has a display value (disp/equiv), it is shown in place of the original tag,
     * which is then kept in x-orig to be restored
     *
     * @param CTypeEnum $ctype
     * @param string $originalTag
     * @param string|null $displayValue
     *
     * @return string
     */
    private function getMatecatPhTag(CTypeEnum $ctype, string $originalTag, ?string $displayValue): string
    {
        $phTag = '<ph id="' . $this->getPipeline()->getNextId() . '" ctype="' . $ctype->value . '"';

        if ($displayValue === null) {
            return $phTag . ' equiv-text="base64:' . base64_encode($originalTag) . '"/>';
        }

        return $phTag . ' equiv-text="base64:' . base64_encode($displayValue) . '" x-orig="' . base64_encode($originalTag) . '"/>';
    }
}
