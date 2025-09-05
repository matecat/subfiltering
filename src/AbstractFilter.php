<?php

/**
 * This file contains the abstract base class for all filter implementations.
 * It provides a foundational structure for transforming string data between different
 * "layers" of representation, such as raw database content, server-to-server communication
 * formats, and UI-ready strings.
 */

namespace Matecat\SubFiltering;

use Exception;
use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Contracts\FeatureSetInterface;
use Matecat\SubFiltering\Filters\EncodeToRawXML;
use Matecat\SubFiltering\Filters\EquivTextToBase64;
use Matecat\SubFiltering\Filters\LtGtDecode;
use Matecat\SubFiltering\Filters\LtGtEncode;
use Matecat\SubFiltering\Filters\MateCatCustomPHToOriginalValue;
use Matecat\SubFiltering\Filters\PlaceHoldXliffTags;
use Matecat\SubFiltering\Filters\RestoreEquivText;
use Matecat\SubFiltering\Filters\RestorePlaceHoldersToXLIFFLtGt;
use Matecat\SubFiltering\Filters\RestoreXliffTagsContent;
use Matecat\SubFiltering\Filters\SplitPlaceholder;
use Matecat\SubFiltering\Filters\StandardPHToMateCatCustomPH;
use Matecat\SubFiltering\Filters\StandardXEquivTextToMateCatCustomPH;

/**
 * Provides a blueprint for creating specific filter implementations.
 *
 * This abstract class defines the core structure and functionality for transforming
 * string data between different logical layers. It manages a set of features,
 * source/target languages, and transformation pipelines. Subclasses must implement
 * the specific transformation logic required for their context.
 *
 * The class uses a factory method `getInstance` to create and configure filter instances,
 * which are composed of a `Pipeline` of `AbstractHandler`s.
 */
abstract class AbstractFilter {

    /**
     * @var FeatureSetInterface
     * The set of features to be applied during the filtering process.
     */
    protected FeatureSetInterface $featureSet;

    /**
     * @var string|null
     * The source language of the segment.
     */
    protected ?string $source;

    /**
     * @var string|null
     * The target language of the segment.
     */
    protected ?string $target;

    /**
     * @var array
     * A map used for replacing data references within the segment.
     */
    protected array $dataRefMap = [];

    /**
     * The processing pipeline for transforming content from Layer 0 (e.g., database)
     * to Layer 1 (e.g., for MT/TM servers).
     *
     * This pipeline holds the sequence of filter handlers that are applied to
     * convert the raw segment into its sub-filtered representation. It is typically
     * configured once and reused for multiple transformations.
     *
     * @var Pipeline
     */
    protected Pipeline $fromLayer0ToLayer1Pipeline;

    /**
     * Factory method to create and configure a new instance of the filter.
     *
     * This method instantiates a new filter object and configures it with the provided
     * feature set, source/target languages, data-ref map, and a list of handlers for
     * the Layer 0 to Layer 1 transition.
     *
     * The handler list follows specific rules:
     * - An empty array (default) populates the filter with all default handlers from HandlersSorter.
     * - `null` clears the handler list, meaning no handlers will be used.
     * - A specific array of class names will be used as the handler list.
     *
     * @param FeatureSetInterface $featureSet                                   The feature set to apply.
     * @param string|null         $source                                       The source language code (e.g., 'en-US').
     * @param string|null         $target                                       The target language code (e.g., 'it-IT').
     * @param array|null          $dataRefMap                                   A map for data-ref transformations, or null for an empty map.
     * @param array|null          $handlerClassNamesForLayer0ToLayer1Transition A list of handler classes, an empty array for defaults, or null for none.
     *
     * @return AbstractFilter The configured instance of the filter.
     */
    public static function getInstance( FeatureSetInterface $featureSet, ?string $source = null, ?string $target = null, ?array $dataRefMap = [], ?array $handlerClassNamesForLayer0ToLayer1Transition = [] ): ?AbstractFilter {
        // Create a new instance of the specific filter class (e.g., MateCatFilter).
        $newInstance = new static();

        // Configure the instance with the provided settings via direct property access.
        $newInstance->featureSet = $featureSet;
        $newInstance->source     = $source;
        $newInstance->target     = $target;
        // Use the null coalescing operator to default to an empty array if $dataRefMap is null.
        $newInstance->dataRefMap = $dataRefMap ?? [];

        // Determine which handlers to use for the Layer 0 to Layer 1 transition.
        if ( is_array( $handlerClassNamesForLayer0ToLayer1Transition ) && empty( $handlerClassNamesForLayer0ToLayer1Transition ) ) {
            // If an empty array is passed, load the default set of handlers from the sorter.
            $handlerClassNamesForLayer0ToLayer1Transition = array_keys( HandlersSorter::injectableHandlersOrder );
        } elseif ( is_null( $handlerClassNamesForLayer0ToLayer1Transition ) ) {
            // If null is passed, use no handlers.
            $handlerClassNamesForLayer0ToLayer1Transition = [];
        }
        // Otherwise, use the custom list of handlers provided.

        // Create and configure the processing pipeline for the Layer 0 to Layer 1 transformation.
        $newInstance->createFromLayer0ToLayer1Pipeline( $source, $target, $dataRefMap ?? [], $handlerClassNamesForLayer0ToLayer1Transition );

        // Return the fully configured filter instance.
        return $newInstance;
    }

    /**
     * Transforms a segment from Layer 1 (server-to-server format) back to Layer 0 (database raw XML).
     *
     * This method defines the standard pipeline for reverting sub-filtered content,
     * restoring placeholders, and re-encoding XML entities to make it safe for database storage.
     *
     * @param string $segment The segment in Layer 1 format.
     *
     * @return string The transformed segment in Layer 0 format.
     * @throws Exception If any handler in the pipeline fails.
     */
    public function fromLayer1ToLayer0( string $segment ): string {
        // Initialize a new pipeline for this transformation.
        $channel = new Pipeline( $this->source, $this->target, $this->dataRefMap );

        // Add handlers to reverse the sub-filtering process.
        $channel->addLast( MateCatCustomPHToOriginalValue::class ); // Restore original PH values
        $channel->addLast( PlaceHoldXliffTags::class );             // Isolate XLIFF tags
        $channel->addLast( EncodeToRawXML::class );                 // Encode for raw XML storage
        $channel->addLast( LtGtEncode::class );                     // Encode '<' and '>'
        $channel->addLast( RestoreXliffTagsContent::class );        // Restore original XLIFF content
        $channel->addLast( RestorePlaceHoldersToXLIFFLtGt::class ); // Restore placeholders for '<' and '>'
        $channel->addLast( SplitPlaceholder::class );               // Handle split placeholders
        $channel->addLast( RestoreEquivText::class );               // Restore equiv-text content

        // Allow the current feature set to modify the pipeline (e.g., add or remove handlers).
        /** @var $channel Pipeline */
        $channel = $this->featureSet->filter( 'fromLayer1ToLayer0', $channel );

        // Process the segment through the pipeline and return the result.
        return $channel->transform( $segment );
    }

    /**
     * Transforms a segment from Layer 0 (database raw XML) to Layer 1 (sub-filtered format).
     *
     * This method uses the pre-configured pipeline to process the segment. It also allows
     * the feature set to apply any final modifications to the pipeline before transformation.
     *
     * @param string      $segment The segment in Layer 0 format.
     * @param string|null $cid     An optional client/context identifier for further customization.
     *
     * @return string The transformed segment in Layer 1 format.
     * @throws Exception If any handler in the pipeline fails.
     */
    public function fromLayer0ToLayer1( string $segment, ?string $cid = null ): string {
        // Retrieve the pre-built pipeline for this transformation.
        $channel = $this->getFromLayer0ToLayer1Pipeline();

        // Allow the feature set to modify the pipeline for this specific transformation.
        /** @var $channel Pipeline */
        $channel = $this->featureSet->filter( 'fromLayer0ToLayer1', $channel );

        // Process the segment and return the result.
        return $channel->transform( $segment );
    }

    /**
     * Returns the configured pipeline for transforming content from Layer 0 to Layer 1.
     *
     * This pipeline is used to process segments as they are transformed from their raw
     * database representation (Layer 0) to a sub-filtered format suitable for server-to-server
     * communications (Layer 1). The pipeline consists of a series of handlers that perform
     * specific transformations on the segment data.
     *
     * @return Pipeline The pipeline configured for Layer 0 to Layer 1 transformations.
     */
    public function getFromLayer0ToLayer1Pipeline(): Pipeline {
        return $this->fromLayer0ToLayer1Pipeline;
    }

    /**
     * Creates and configures the pipeline for transforming content from Layer 0 to Layer 1.
     *
     * This is the default configuration method of MateCatFilter for setting up the pipeline that processes segments.
     * MyMemoryFilter or override this method to customize the pipeline as needed.
     *
     * This method builds the default pipeline for the Layer 0 to Layer 1 transformation.
     * It adds a series of standard handlers and then incorporates any custom handlers,
     * ensuring they are correctly ordered via `HandlersSorter`.
     *
     * @param string|null $source                                       The source language code.
     * @param string|null $target                                       The target language code.
     * @param array       $dataRefMap                                   A map for data-ref transformations.
     * @param array       $handlerClassNamesForLayer0ToLayer1Transition A list of handler classes to include in the pipeline.
     */
    protected function createFromLayer0ToLayer1Pipeline( ?string $source, ?string $target, array $dataRefMap, array $handlerClassNamesForLayer0ToLayer1Transition ) {
        // Initialize the pipeline with language and data-ref context.
        $this->fromLayer0ToLayer1Pipeline = new Pipeline( $source, $target, $dataRefMap );

        // Add initial handlers for standard XLIFF and placeholder normalization.
        $this->fromLayer0ToLayer1Pipeline->addLast( StandardPHToMateCatCustomPH::class );
        $this->fromLayer0ToLayer1Pipeline->addLast( StandardXEquivTextToMateCatCustomPH::class );
        $this->fromLayer0ToLayer1Pipeline->addLast( PlaceHoldXliffTags::class );
        $this->fromLayer0ToLayer1Pipeline->addLast( LtGtDecode::class );

        // Sort and add the dynamic feature-based handlers.
        $sorter = new HandlersSorter( $handlerClassNamesForLayer0ToLayer1Transition );
        foreach ( $sorter->getOrderedHandlersClassNames() as $handler ) {
            $this->fromLayer0ToLayer1Pipeline->addLast( $handler );
        }

        // Add final handlers to restore XLIFF content and encode for the target layer.
        $this->fromLayer0ToLayer1Pipeline->addLast( RestoreXliffTagsContent::class );
        $this->fromLayer0ToLayer1Pipeline->addLast( RestorePlaceHoldersToXLIFFLtGt::class );
        $this->fromLayer0ToLayer1Pipeline->addLast( EquivTextToBase64::class );
    }
}