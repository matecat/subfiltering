<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\Filters\Sprintf\SprintfLocker;

class PercentSnail extends AbstractHandler {
    /**
     * @inheritDoc
     */
    public function transform( string $segment ): string {

        $sprintfLocker = new SprintfLocker( $this->pipeline->getSource(), $this->pipeline->getTarget() );

        //placeholding
        $segment = $sprintfLocker->lock( $segment );

        preg_match_all( '/%@/', $segment, $html, PREG_SET_ORDER );
        foreach ( $html as $percentSnailVariable ) {

            $segment = preg_replace(
                    '/' . preg_quote( $percentSnailVariable[ 0 ], '/' ) . '/',
                    '<ph id="' . $this->getPipeline()->getNextId() . '" ctype="' . CTypeEnum::PERCENT_SNAILS . '" equiv-text="base64:' . base64_encode( $percentSnailVariable[ 0 ] ) . '"/>',
                    $segment,
                    1
            );
        }

        return $sprintfLocker->unlock( $segment );
    }
}