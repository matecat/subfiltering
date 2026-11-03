<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;
use Matecat\SubFiltering\Enum\CTypeEnum;

/**
 * Locks `[text]` placeholders into `<ph>` tags.
 *
 * The brackets must hold no whitespace, no markup and no further brackets, so `[a b]` and
 * `[[text]]` are left alone: the latter belongs to {@see DoubleSquareBrackets}, and the
 * lookarounds keep its inner part from being taken here when that handler is disabled.
 */
class SingleSquareBrackets extends AbstractHandler
{
    private const string PATTERN = '/(?<!\[)\[[^\[\]<>\s]+?](?!])/u';

    /**
     * @inheritDoc
     */
    public function transform(string $segment): string
    {
        // Each match is replaced where it was found. Replacing the first literal
        // occurrence instead would hit the inner `[x]` of a preceding `[[x]]`.
        return preg_replace_callback(
            self::PATTERN,
            function (array $match): string {
                // An escaped tag inside the brackets must not be swallowed into the placeholder
                if (
                    str_contains($match[0], ConstantEnum::LTPLACEHOLDER->value)
                    || str_contains($match[0], ConstantEnum::GTPLACEHOLDER->value)
                ) {
                    return $match[0];
                }

                return '<ph id="' . $this->getPipeline()->getNextId()
                    . '" ctype="' . CTypeEnum::SINGLE_SQUARE_BRACKETS->value
                    . '" equiv-text="base64:' . base64_encode($match[0]) . '"/>';
            },
            $segment
        ) ?? $segment;
    }
}
