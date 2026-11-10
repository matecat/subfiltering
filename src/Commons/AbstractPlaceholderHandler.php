<?php

namespace Matecat\SubFiltering\Commons;

use Matecat\SubFiltering\Enum\ConstantEnum;
use Matecat\SubFiltering\Enum\CTypeEnum;

/**
 * Base for the handlers that lock a placeholder syntax, such as `[[name]]` or `@@name@@`, into `<ph>` tags.
 *
 * Each subclass gives its pattern and its ctype. A match is left as text when it holds:
 * - an escaped tag, `##LESSTHAN##` or `##GREATERTHAN##`;
 * - whitespace written as a numeric entity. Layer 0 stores tabs, newlines and carriage returns as
 *   `&#09;`, `&#10;`, `&#13;` (see {@see \Matecat\SubFiltering\Filters\EncodeControlCharsInXliff}),
 *   which the pattern's `\s` cannot see.
 *
 * Each lock replaces the match where it was found, and the scan goes on after the end of every match,
 * locked or not.
 */
abstract class AbstractPlaceholderHandler extends AbstractHandler
{
    /**
     * The pattern of one placeholder. Its content must leave out `<` and `>`.
     */
    abstract protected function getPattern(): string;

    /**
     * The ctype written on the `<ph>` tags of this handler.
     */
    abstract protected function getCType(): CTypeEnum;

    /**
     * @inheritDoc
     */
    final public function transform(string $segment): string
    {
        return preg_replace_callback(
            $this->getPattern(),
            fn(array $match): string => self::hidesWhitespaceOrTag($match[0]) ? $match[0] : $this->toPh($match[0]),
            $segment
        ) ?? $segment;
    }

    private static function hidesWhitespaceOrTag(string $text): bool
    {
        if (
            str_contains($text, ConstantEnum::LTPLACEHOLDER->value)
            || str_contains($text, ConstantEnum::GTPLACEHOLDER->value)
        ) {
            return true;
        }

        preg_match_all('/&#(?:[0-9]+|[xX][0-9a-fA-F]+);/', $text, $entities);

        return preg_match('/\s/u', html_entity_decode(implode('', $entities[0]), ENT_XML1, 'UTF-8')) === 1;
    }

    private function toPh(string $text): string
    {
        return '<ph id="' . $this->getPipeline()->getNextId()
            . '" ctype="' . $this->getCType()->value
            . '" equiv-text="base64:' . base64_encode($text) . '"/>';
    }
}
