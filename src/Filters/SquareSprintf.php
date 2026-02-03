<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;

class SquareSprintf extends AbstractHandler
{
    /**
     * @inheritDoc
     */
    public function transform(string $segment): string
    {
        $tags = [
            '\[%s\]',
            '\[%\d+\$s\]',
            '\[%\d+\$i\]',
            '\[%\d+\$s:[a-z_]+\]',
            '\[%\d+\$i:[a-z_]+\]',
            '\[%s:[a-z_]+\]',
            '\[%i\]',
            '\[%i:[a-z_]+\]',
            '\[%f\]',
            '\[%f:[a-z_]+\]',
            '\[%.\d+f\]',
            '\[%\d+\$.\d+f\]',
            '\[%\d+\$.\d+f:[a-z_]+\]',
            '\[%.\d+f:[a-z_]+\]',
            '\[%[a-z_]+:\d+%\]',
        ];

        $regex = '/' . implode("|", $tags) . '/iu';

        preg_match_all($regex, $segment, $html, PREG_SET_ORDER);

        foreach ($html as $squaredSprintf) {
            $segment = preg_replace(
                '/' . preg_quote($squaredSprintf[0], '/') . '/',
                '<ph id="' . $this->getPipeline()->getNextId(
                ) . '" ctype="' . CTypeEnum::SQUARE_SPRINTF . '" equiv-text="base64:' . base64_encode(
                    $squaredSprintf[0]
                ) . '"/>',
                $segment,
                1
            );
        }

        return $segment;
    }
}
