<?php

namespace Matecat\SubFiltering\Commons;

use Matecat\SubFiltering\Enum\CTypeEnum;

/**
 * Base for the handlers that lock a placeholder syntax, such as `[[name]]` or `@@name@@`, into `<ph>` tags.
 *
 * Each subclass gives its pattern and its ctype. Every match of the pattern is locked, in one linear scan.
 */
abstract class AbstractPlaceholderHandler extends AbstractHandler
{
    /**
     * Put this lookahead before the character class of a pattern, inside the repeated group:
     * `(?:' . self::NOT_HIDDEN . '[^<>\s])+?`.
     *
     * The placeholder content then stops where a literal space would stop it, at:
     * - an escaped tag, `##LESSTHAN##` or `##GREATERTHAN##`;
     * - a tab, line feed, carriage return, space or NBSP written as a numeric entity, decimal or hex, with
     *   any leading zeros: `&#09;`, `&#x9;`, `&#10;`, `&#xA;`, `&#13;`, `&#xD;`, `&#32;`, `&#x20;`, `&#160;`,
     *   `&#xA0;`. Layer 0 stores tabs, line feeds and carriage returns this way (see
     *   {@see \Matecat\SubFiltering\Filters\EncodeControlCharsInXliff}), so `\s` cannot see them.
     *
     * Example: in `[[a&#09;[[b]]` the first `[[` cannot reach `]]`, so `[[b]]` is locked and `[[a&#09;` stays
     * text. Any other entity, such as `&amp;` or `&#233;`, is ordinary content.
     */
    protected const string NOT_HIDDEN = '(?!##LESSTHAN##|##GREATERTHAN##|&#(?:0*(?:9|10|13|32|160)|[xX]0*(?:9|[aA]|[dD]|20|[aA]0));)';

    /**
     * The pattern of one placeholder. Its content must leave out `<` and `>`, and use {@see self::NOT_HIDDEN}.
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
            fn(array $match): string => '<ph id="' . $this->getPipeline()->getNextId()
                . '" ctype="' . $this->getCType()->value
                . '" equiv-text="base64:' . base64_encode($match[0]) . '"/>',
            $segment
        ) ?? $segment;
    }
}
