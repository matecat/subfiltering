<?php

/**
 * This file contains the abstract base class for all filter implementations.
 * It provides a foundational structure for transforming string data between different
 * "layers" of representation, such as raw database content, server-to-server communication
 * formats, and UI-ready strings.
 */

namespace Matecat\SubFiltering;

use Exception;
use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Contracts\FeatureSetInterface;
use Matecat\SubFiltering\Enum\InjectableFiltersTags;
use Matecat\SubFiltering\Filters\EncodeToRawXML;
use Matecat\SubFiltering\Filters\EquivTextToBase64;
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
abstract class AbstractFilter
{

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
     * @var array<string,string>
     * A map used for replacing data references within the segment.
     */
    protected array $dataRefMap = [];

    /**
     * @var class-string<AbstractHandler>[]
     * An ordered list of handler class names for the Layer 0 to Layer 1 transition.
     */
    protected array $orderedHandlersForLayer0ToLayer1Transition = [];

    /**
     * AbstractFilter constructor.
     */
    final public function __construct()
    {
    }

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
     * @param FeatureSetInterface $featureSet The feature set to apply.
     * @param string|null $source The source language code (e.g., 'en-US').
     * @param string|null $target The target language code (e.g., 'it-IT').
     * @param array<string,string>|null $dataRefMap A map for data-ref transformations, or null for an empty map.
     * @param string[]|null $handlerTagNamesForLayer0ToLayer1Transition A list of handler tag names for the Layer 0 to Layer 1 transition.
     * @param bool $icu_enabled
     *
     * @return AbstractFilter The configured instance of the filter.
     */
    public static function getInstance(
        FeatureSetInterface $featureSet,
        ?string $source = null,
        ?string $target = null,
        ?array $dataRefMap = [],
        ?array $handlerTagNamesForLayer0ToLayer1Transition = [],
        bool $icu_enabled = false
    ): AbstractFilter {
        // Create a new instance of the specific filter class (e.g., MateCatFilter).
        $newInstance = new static();

        // Configure the instance with the provided settings via direct property access.
        $newInstance->featureSet = $featureSet;
        $newInstance->source = $source;
        $newInstance->target = $target;
        // Use the null coalescing operator to default to an empty array if $dataRefMap is null.
        $newInstance->dataRefMap = $dataRefMap ?? [];

        $handlerClassNamesForLayer0ToLayer1Transition = InjectableFiltersTags::classesForArrayTagNames(
            $handlerTagNamesForLayer0ToLayer1Transition
        );

        // Determine which handlers to use for the Layer 0 to Layer 1 transition.
        if (is_array(
                $handlerClassNamesForLayer0ToLayer1Transition
            ) && empty($handlerClassNamesForLayer0ToLayer1Transition)) {
            // If an empty array is passed, load the default set of handlers from the sorter.
            $handlerClassNamesForLayer0ToLayer1Transition = array_keys(HandlersSorter::getDefaultInjectedHandlers());
        } elseif (is_null($handlerClassNamesForLayer0ToLayer1Transition)) {
            // If null is passed, use no handlers.
            $handlerClassNamesForLayer0ToLayer1Transition = [];
        }
        // Otherwise, use the custom list of handlers provided.

        // Sort the dynamic feature-based handlers.
        $sorter = new HandlersSorter($handlerClassNamesForLayer0ToLayer1Transition, $icu_enabled);
        $newInstance->orderedHandlersForLayer0ToLayer1Transition = $sorter->getOrderedHandlersClassNames();

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
    public function fromLayer1ToLayer0(string $segment): string
    {
        // Initialize a new pipeline for this transformation.
        $channel = new Pipeline($this->source, $this->target, $this->dataRefMap);

        // Add handlers to reverse the sub-filtering process.
        $channel->addLast(MateCatCustomPHToOriginalValue::class); // Restore original PH values
        $channel->addLast(PlaceHoldXliffTags::class);             // Isolate XLIFF tags
        $channel->addLast(EncodeToRawXML::class);                 // Encode for raw XML storage
        $channel->addLast(LtGtEncode::class);                     // Encode '<' and '>'
        $channel->addLast(RestoreXliffTagsContent::class);        // Restore original XLIFF content
        $channel->addLast(RestorePlaceHoldersToXLIFFLtGt::class); // Restore placeholders for '<' and '>'
        $channel->addLast(SplitPlaceholder::class);               // Handle split placeholders
        $channel->addLast(RestoreEquivText::class);               // Restore equiv-text content

        // Allow the current feature set to modify the pipeline (e.g., add or remove handlers).
        /** @type $channel Pipeline */
        $channel = $this->featureSet->filter('fromLayer1ToLayer0', $channel);

        // Process the segment through the pipeline and return the result.
        return $channel->transform($segment);
    }


    /**
     * Transforms a segment from Layer 0 to Layer 1.
     *
     * This method performs the conversion of a segment from the input pre-processed stage (Layer 0)
     * to Layer 1, where additional processing and standardization are applied. It may use various
     * processing pipelines or handlers to achieve this transformation, depending on the implementation.
     *
     * @param string $segment The input segment to be transformed from Layer 0 to Layer 1.
     * @param string|null $cid An optional identifier for context or further processing specific to the segment.
     *
     * @return string The transformed segment after processing from Layer 0 to Layer 1.
     */
    public abstract function fromLayer0ToLayer1(string $segment, ?string $cid = null): string;

    /**
     * Configures the pipeline for transforming content from Layer 0 to Layer 1.
     *
     * This is the default configuration method of MateCatFilter for setting up the pipeline that processes segments.
     * MyMemoryFilter or override this method to customize the pipeline as needed.
     *
     * This method builds the default pipeline for the Layer 0 to Layer 1 transformation.
     * It adds a series of standard handlers and then incorporates any custom handlers,
     * ensuring they are correctly ordered via `HandlersSorter`.
     *
     * @param Pipeline $channel
     * @param string|null $cid
     */
    protected function configureFromLayer0ToLayer1Pipeline(Pipeline $channel, ?string $cid = null): void
    {
        // Add initial handlers for standard XLIFF and placeholder normalization.
        $channel->addLast(StandardPHToMateCatCustomPH::class);
        $channel->addLast(StandardXEquivTextToMateCatCustomPH::class);
        $channel->addLast(PlaceHoldXliffTags::class);

        // Add the dynamic feature-based handlers.
        foreach ($this->orderedHandlersForLayer0ToLayer1Transition as $handler) {
            $channel->addLast($handler);
        }

        // Add final handlers to restore XLIFF content and encode for the target layer.
        $channel->addLast(RestoreXliffTagsContent::class);
        $channel->addLast(RestorePlaceHoldersToXLIFFLtGt::class);
        $channel->addLast(EquivTextToBase64::class);
    }

}