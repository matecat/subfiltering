<?php

namespace Matecat\SubFiltering\Commons;

use Matecat\SubFiltering\Enum\CTypeEnum;

/**
 * Base for the handlers that lock a placeholder syntax, such as `[[name]]` or `@@name@@`, into `<ph>` tags.
 *
 * Each subclass gives its pattern and its ctype. A placeholder cannot hold a hidden token (see
 * {@see self::HIDDEN_TOKEN}): the segment is split at those tokens, and the pattern runs on each piece
 * between them. In `[[a&#09;[[b]]` the pieces are `[[a` and `[[b]]`, so `[[b]]` is locked and `[[a&#09;`
 * stays text.
 *
 * The scan is linear: one split, then one pass of the pattern over each piece.
 */
abstract class AbstractPlaceholderHandler extends AbstractHandler
{
    /**
     * The tokens a placeholder cannot hold, captured so the split keeps them:
     * - an escaped tag, `##LESSTHAN##` or `##GREATERTHAN##`;
     * - a tab, line feed, carriage return, space or NBSP written as a numeric entity, decimal or hex, with any
     *   leading zeros: `&#09;`, `&#x9;`, `&#10;`, `&#xA;`, `&#13;`, `&#xD;`, `&#32;`, `&#x20;`, `&#160;`,
     *   `&#xA0;`. Layer 0 stores tabs, line feeds and carriage returns this way (see
     *   {@see \Matecat\SubFiltering\Filters\EncodeControlCharsInXliff}), so a pattern's `\s` cannot see them.
     *
     * Any other entity, such as `&amp;`, `&nbsp;` or `&#11;`, is ordinary content. Literal characters are left
     * to each pattern. Most stop at `\s`, which takes a literal tab, line feed, carriage return, vertical tab
     * or form feed; with the `/u` flag of {@see \Matecat\SubFiltering\Filters\SingleSquareBrackets} it also
     * takes a literal NBSP. {@see \Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh} stops only at a
     * literal space.
     *
     * No token starts or ends with a delimiter character, so a pattern's lookarounds see the same neighbours in
     * a piece as in the whole segment.
     */
    private const string HIDDEN_TOKEN = '/(##LESSTHAN##|##GREATERTHAN##|&#(?:0*(?:9|10|13|32|160)|[xX]0*(?:9|[aA]|[dD]|20|[aA]0));)/';

    /**
     * The pattern of one placeholder. Its content must leave out `<` and `>`.
     */
    abstract protected function getPattern(): string;

    /**
     * The ctype written on the `<ph>` tags of this handler.
     */
    abstract protected function getCType(): CTypeEnum;

    /**
     * Locks each match of the pattern in place. When the pattern fails on a piece, such as a `/u` pattern on
     * invalid UTF-8, the segment is returned unchanged.
     *
     * @inheritDoc
     */
    final public function transform(string $segment): string
    {
        // HIDDEN_TOKEN is a fixed alternation of literals, so the split cannot fail.
        /** @var list<string> $pieces */
        $pieces = preg_split(self::HIDDEN_TOKEN, $segment, -1, PREG_SPLIT_DELIM_CAPTURE);

        // Even indexes are the text between tokens; odd indexes are the tokens.
        for ($i = 0, $count = count($pieces); $i < $count; $i += 2) {
            $locked = preg_replace_callback(
                $this->getPattern(),
                fn(array $match): string => '<ph id="' . $this->getPipeline()->getNextId()
                    . '" ctype="' . $this->getCType()->value
                    . '" equiv-text="base64:' . base64_encode($match[0]) . '"/>',
                $pieces[$i]
            );
            if ($locked === null) {
                return $segment;
            }
            $pieces[$i] = $locked;
        }

        return implode('', $pieces);
    }
}
