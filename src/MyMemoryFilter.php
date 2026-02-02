<?php

namespace Matecat\SubFiltering;

use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Filters\DollarCurlyBrackets;
use Matecat\SubFiltering\Filters\PercentDoubleCurlyBrackets;
use Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh;
use Matecat\SubFiltering\Filters\SmartCounts;
use Matecat\SubFiltering\Filters\TwigToPh;

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
class MyMemoryFilter extends AbstractFilter
{

    /**
     * Converts a segment from Layer 0 format to Layer 1 format, applying an optional client-specific pipeline configuration.
     *
     * This method processes the given segment through a transformation pipeline, which may be customized
     * based on the provided Client ID (`cid`) to handle specific placeholder or syntax rules.
     *
     * @param string $segment The segment in Layer 0 format to be transformed.
     * @param string|null $cid An optional Client ID for customizing the transformation pipeline. If provided.
     *                             this adjusts the pipeline to account for client-specific rules.
     *
     * @return string The transformed segment in Layer 1 format.
     */
    public function fromLayer0ToLayer1(string $segment, ?string $cid = null): string
    {
        $channel = new Pipeline($this->source, $this->target, $this->dataRefMap);

        $this->configureFromLayer0ToLayer1Pipeline($channel, $cid);

        // Process the segment and return the result.
        return $channel->transform($segment);
    }

    /**
     * Transforms a segment from Layer 0 to Layer 1, applying client-specific rules.
     *
     * This method overrides the parent implementation to dynamically adjust the
     * `fromLayer0ToLayer1Pipeline` based on the provided Client ID (`cid`). This allows for
     * tailored placeholder handling for different clients before the main transformation occurs.
     *
     * @param Pipeline $channel
     * @param string|null $cid An optional Client ID to trigger specific filtering rules:
     *                             - 'airbnb': Adds `SmartCounts` handler for advanced variable processing.
     *                             - 'roblox': Adds `SingleCurlyBracketsToPh` handler for `{placeholder}` style variables.
     *                             - 'familysearch': Removes `TwigToPh` and adds `SingleCurlyBracketsToPh`.
     *
     */
    protected function configureFromLayer0ToLayer1Pipeline(Pipeline $channel, ?string $cid = null): void
    {
        parent::configureFromLayer0ToLayer1Pipeline($channel);

        switch ($cid) {
            case 'airbnb':
                // For Airbnb, add the SmartCounts handler to process specific variable syntax
                // that looks like `%{smart_count}`.
                if (!$channel->contains(SmartCounts::class)) {
                    $channel->addAfter(PercentDoubleCurlyBrackets::class, SmartCounts::class);
                }
                break;

            case 'roblox':
                // For Roblox, add a handler to convert single curly bracket placeholders
                // (e.g., `{placeholder}`) into standard <ph> tags.
                if (!$channel->contains(SingleCurlyBracketsToPh::class)) {
                    $channel->addAfter(DollarCurlyBrackets::class, SingleCurlyBracketsToPh::class);
                }
                break;

            case 'familysearch':
                // For FamilySearch, customize the pipeline by removing Twig support and adding
                // support for single curly bracket placeholders.
                if (!$channel->contains(SingleCurlyBracketsToPh::class)) {
                    $channel->remove(TwigToPh::class);
                    $channel->addAfter(DollarCurlyBrackets::class, SingleCurlyBracketsToPh::class);
                }
                break;
        }
    }

}