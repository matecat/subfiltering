<?php

namespace Matecat\SubFiltering;

use Exception;
use Matecat\SubFiltering\Filters\DollarCurlyBrackets;
use Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh;
use Matecat\SubFiltering\Filters\SmartCounts;
use Matecat\SubFiltering\Filters\TwigToPh;
use Matecat\SubFiltering\Filters\Variables;

/**
 * A specific filter implementation tailored for MyMemory services.
 *
 * This class extends the base `AbstractFilter` to provide custom, client-specific
 * filtering logic by dynamically modifying the transformation pipeline. It is designed
 * to handle variations in placeholder syntax and filtering requirements from different
 * clients (such as Airbnb, Roblox, etc.).
 *
 * It uses a Client ID (`cid`) passed during the transformation to adjust the
 * pipeline on the fly, ensuring the correct set of handlers is used for each case.
 *
 * @package Matecat\SubFiltering
 */
class MyMemoryFilter extends AbstractFilter {

    /**
     * Transforms a segment from Layer 0 to Layer 1, applying client-specific rules.
     *
     * This method overrides the parent implementation to dynamically adjust the
     * `fromLayer0ToLayer1Pipeline` based on the provided Client ID (`cid`). This allows for
     * tailored placeholder handling for different clients before the main transformation occurs.
     *
     * @param string      $segment The input string from Layer 0 (e.g., from a database).
     * @param string|null $cid     An optional Client ID to trigger specific filtering rules:
     *                             - 'airbnb': Adds `SmartCounts` handler for advanced variable processing.
     *                             - 'roblox': Adds `SingleCurlyBracketsToPh` handler for `{placeholder}` style variables.
     *                             - 'familysearch': Removes `TwigToPh` and adds `SingleCurlyBracketsToPh`.
     *
     * @return string The transformed segment in Layer 1 format.
     *
     * @throws Exception If any handler in the pipeline fails during transformation.
     */
    public function fromLayer0ToLayer1( string $segment, ?string $cid = null ): string {

        // For Airbnb, add the SmartCounts handler to process specific variable syntax
        // that looks like `%{smart_count}`.
        if ( $cid === 'airbnb' ) {
            if ( $this->fromLayer0ToLayer1Pipeline->contains( Variables::class ) === false ) {
                $this->fromLayer0ToLayer1Pipeline->addAfter( Variables::class, SmartCounts::class );
            }
        }

        // For Roblox, add a handler to convert single curly bracket placeholders
        // (e.g., `{placeholder}`) into standard <ph> tags.
        if ( $cid === 'roblox' ) {
            if ( $this->fromLayer0ToLayer1Pipeline->contains( SingleCurlyBracketsToPh::class ) === false ) {
                $this->fromLayer0ToLayer1Pipeline->addAfter( DollarCurlyBrackets::class, SingleCurlyBracketsToPh::class );
            }
        }

        // For FamilySearch, customize the pipeline by removing Twig support and adding
        // support for single curly bracket placeholders.
        if ( $cid === 'familysearch' ) {
            if ( $this->fromLayer0ToLayer1Pipeline->contains( SingleCurlyBracketsToPh::class ) === false ) {
                $this->fromLayer0ToLayer1Pipeline->remove( TwigToPh::class );
                $this->fromLayer0ToLayer1Pipeline->addAfter( DollarCurlyBrackets::class, SingleCurlyBracketsToPh::class );
            }
        }

        // After applying any client-specific modifications to the pipeline,
        // call the parent method to execute the actual transformation.
        return parent::fromLayer0ToLayer1( $segment, $cid );
    }
}