<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;
use Matecat\SubFiltering\Enum\CTypeEnum;

/**
 * Locks `[[text]]` placeholders into `<ph>` tags.
 *
 * The brackets must hold no whitespace and no markup. Whitespace written as an entity counts
 * too: Layer 0 stores tabs and newlines as `&#09;`, `&#10;`, `&#13;` (see
 * {@see EncodeControlCharsInXliff}), so `[[a&#09;b]]` and `[[a&#160;b]]` are left alone.
 */
class DoubleSquareBrackets extends AbstractHandler
{
    /**
     * @inheritDoc
     */
    public function transform(string $segment): string
    {
        // `/u` makes `\s` match Unicode whitespace too, such as a literal NBSP
        if (preg_match_all('/\[\[[^<>\s]+?\]\]/u', $segment, $html, PREG_SET_ORDER) === false) {
            return $segment;
        }

        foreach ($html as $snail_variable) {
            //check if inside twig variable there is a tag because in this case shouldn't replace the content with PH tag
            if (strstr($snail_variable[0], ConstantEnum::GTPLACEHOLDER->value)) {
                continue;
            }

            // Whitespace written as an entity (`&#09;`, `&#10;`, `&#160;`, ...) is not seen by `\s`
            if (preg_match('/\s/u', html_entity_decode($snail_variable[0], ENT_QUOTES | ENT_HTML5, 'UTF-8'))) {
                continue;
            }

            //replace subsequent elements excluding already encoded
            $segment = preg_replace(
                '/' . preg_quote($snail_variable[0], '/') . '/',
                '<ph id="' . $this->getPipeline()->getNextId(
                ) . '" ctype="' . CTypeEnum::DOUBLE_SQUARE_BRACKETS->value . '" equiv-text="base64:' . base64_encode(
                    $snail_variable[0]
                ) . '"/>',
                $segment,
                1
            ) ?? $segment;
        }

        return $segment;
    }
}
