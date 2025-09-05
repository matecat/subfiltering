<?php

/**
 * The ObjectiveCNSString class is a filter handler that processes Objective-C NSString
 * format specifiers within a segment to convert them into placeholder XML tags
 * for translation and reassembly purposes.
 *
 * It uses the `SprintfLocker` to temporarily lock and unlock sprintf placeholders
 * to prevent misinterpretation during transformation.
 *
 * The transformation includes detecting and converting NSString format specifiers
 * (e.g., `%@` or `%2$@`) into a `<ph>` tag with attributes providing contextual information.
 * These tags are given unique identifiers and base64-encoded representations of the original specifiers.
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\Filters\Sprintf\SprintfLocker;

/**
 * Handles Objective-C NSString placeholders within segments for processing.
 *
 * This class processes Objective-C NSString placeholders (such as `%@` or `%1$@`)
 * by locking, transforming, and unlocking them in a segment of text. The transformation
 * replaces placeholders with XML-compliant placeholder tags containing additional
 * metadata like ctype and base64-encoded original content.
 *
 * Extends the AbstractHandler to use its pipeline and processing capabilities.
 */
class ObjectiveCNSString extends AbstractHandler {
    /**
     * @see https://developer.apple.com/library/archive/documentation/Cocoa/Conceptual/Strings/Articles/formatSpecifiers.html#//apple_ref/doc/uid/TP40004265-SW2
     *
     * @inheritDoc
     */
    public function transform( string $segment ): string {

        $sprintfLocker = new SprintfLocker( $this->pipeline->getSource(), $this->pipeline->getTarget() );

        // placeholding
        $segment = $sprintfLocker->lock( $segment );


        preg_match_all( '/%\d+\$@|%@/', $segment, $html, PREG_SET_ORDER );
        foreach ( $html as $percentNumberSnailVariable ) {

            $segment = preg_replace(
                    '/' . preg_quote( $percentNumberSnailVariable[ 0 ], '/' ) . '/',
                    '<ph id="' . $this->getPipeline()->getNextId() . '" ctype="' . CTypeEnum::OBJECTIVE_C_NSSTRING . '" equiv-text="base64:' . base64_encode( $percentNumberSnailVariable[ 0 ] ) . '"/>',
                    $segment,
                    1
            );
        }

        //revert placeholding
        return $sprintfLocker->unlock( $segment );
    }
}