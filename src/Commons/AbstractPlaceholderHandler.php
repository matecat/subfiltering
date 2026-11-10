<?php

namespace Matecat\SubFiltering\Commons;

use Matecat\SubFiltering\Enum\ConstantEnum;
use Matecat\SubFiltering\Enum\CTypeEnum;

/**
 * Base for the handlers that lock a placeholder syntax, such as `[[name]]` or `@@name@@`, into `<ph>` tags.
 *
 * Each subclass gives a pattern whose content leaves out whitespace and markup. The pattern cannot
 * see two things that Layer 0 holds in escaped form:
 * - whitespace written as an entity: Layer 0 stores tabs, newlines and carriage returns as `&#09;`,
 *   `&#10;`, `&#13;` (see {@see \Matecat\SubFiltering\Filters\EncodeControlCharsInXliff}), and a
 *   no-break space can be `&#160;` or `&nbsp;`;
 * - an escaped tag, written as `##LESSTHAN##...##GREATERTHAN##`.
 *
 * A match that holds either one is not a placeholder. The scan then starts again right after that
 * token, which is where the pattern stops on a literal space or tag: `[[a&#09;[[b]]` still locks
 * `[[b]]`, as `[[a [[b]]` does.
 *
 * Each lock replaces the match where it was found. IDs are taken only after the whole scan
 * succeeded: when the pattern fails (invalid UTF-8, backtrack limit), the segment comes back
 * unchanged and no ID is used.
 */
abstract class AbstractPlaceholderHandler extends AbstractHandler
{
    /**
     * Entities and escaped tags: the tokens that a pattern's content class lets through.
     */
    private const string HIDDEN_TOKENS = '/&(?:#[0-9]+|#x[0-9a-f]+|[a-z][a-z0-9]*);|##LESSTHAN##|##GREATERTHAN##/i';

    /**
     * The pattern of one placeholder. It must use the `/u` flag, so `\s` matches a literal NBSP too,
     * and its content must leave out `\s`, `<` and `>`.
     */
    abstract protected function getPattern(): string;

    /**
     * The ctype written on the `<ph>` tags of this handler.
     */
    abstract protected function getCType(): CTypeEnum;

    /**
     * @inheritDoc
     */
    public function transform(string $segment): string
    {
        $locks = [];
        $offset = 0;

        while (($found = preg_match($this->getPattern(), $segment, $match, PREG_OFFSET_CAPTURE, $offset)) === 1) {
            [$text, $start] = $match[0];
            $blockedUntil = self::findBlockingTokenEnd($text);

            if ($blockedUntil === null) {
                $locks[] = $match[0];
                $offset = $start + strlen($text);
            } else {
                // Every pattern starts with an ASCII delimiter, so this offset never splits a UTF-8 character
                $offset = $start + $blockedUntil;
            }
        }

        if ($found === false) {
            return $segment;
        }

        $result = '';
        $copiedUntil = 0;

        foreach ($locks as [$text, $start]) {
            $result .= substr($segment, $copiedUntil, $start - $copiedUntil) . $this->toPh($text);
            $copiedUntil = $start + strlen($text);
        }

        return $result . substr($segment, $copiedUntil);
    }

    /**
     * Returns where the first escaped tag or whitespace entity in the match ends, or null when there
     * is none.
     */
    private static function findBlockingTokenEnd(string $text): ?int
    {
        preg_match_all(self::HIDDEN_TOKENS, $text, $tokens, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($tokens as [[$token, $at]]) {
            if (
                $token === ConstantEnum::LTPLACEHOLDER->value
                || $token === ConstantEnum::GTPLACEHOLDER->value
                || self::isWhitespaceEntity($token)
            ) {
                return $at + strlen($token);
            }
        }

        return null;
    }

    /**
     * ENT_HTML5 knows the named entities (`&nbsp;`, `&Tab;`) but leaves `&#13;` as it is, while
     * ENT_XML1 decodes `&#13;` and knows no named entity besides the XML ones: each covers what the
     * other misses.
     */
    private static function isWhitespaceEntity(string $entity): bool
    {
        // An entity a doctype does not know comes back as it is, and holds no whitespace
        $decoded = html_entity_decode($entity, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . html_entity_decode($entity, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return preg_match('/\s/u', $decoded) === 1;
    }

    private function toPh(string $text): string
    {
        return '<ph id="' . $this->getPipeline()->getNextId()
            . '" ctype="' . $this->getCType()->value
            . '" equiv-text="base64:' . base64_encode($text) . '"/>';
    }
}
