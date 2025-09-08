<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 14.05
 *
 */

namespace Matecat\SubFiltering\Commons;

use Matecat\SubFiltering\Enum\ConstantEnum;

/**
 * Class Pipeline
 *
 * Orchestrates a sequence of handler objects for segment transformation,
 * manages handler order, tracks transformation state, and realigns segment IDs.
 *
 * @package Matecat\SubFiltering\Commons
 */
class Pipeline {

    /**
     * Registered handler instances that make up the processing pipeline.
     *
     * @var AbstractHandler[]
     */
    protected array $handlers;

    /**
     * Tracks the current segment/internal ID number for realignment.
     *
     * @var int
     */
    protected int $id_number = -1;

    /**
     * The optional source string the Pipeline operates on.
     *
     * @var string|null
     */
    protected ?string $source;

    /**
     * The optional target string the Pipeline operates on.
     *
     * @var string|null
     */
    protected ?string $target;

    /**
     * A key/value map used for reference during segment processing.
     *
     * @var array
     */
    protected array $dataRefMap;

    /**
     * True if the processed segment contains HTML markup.
     *
     * @var bool
     */
    private bool $segmentContainsMarkup = false;

    /**
     * Constructor.
     *
     * @param string|null $source     The source segment, if available.
     * @param string|null $target     The target segment, if available.
     * @param array       $dataRefMap Optional reference map relevant to the segment.
     */
    public function __construct( ?string $source = null, ?string $target = null, array $dataRefMap = [] ) {
        $this->source     = $source;
        $this->target     = $target;
        $this->dataRefMap = $dataRefMap;
    }

    /**
     * Gets and increments the next unique internal identifier for segment elements.
     *
     * @return string The generated identifier.
     */
    public function getNextId(): string {
        $this->id_number++;

        return ConstantEnum::INTERNAL_ATTR_ID_PREFIX . $this->id_number;
    }

    /**
     * Sets the segmentContainsMarkup flag for the current segment.
     *
     * @return void
     */
    public function _setSegmentContainsMarkup() {
        $this->segmentContainsMarkup = true;
    }

    /**
     * Returns the configured source segment.
     *
     * @return string|null Source segment or null if not set.
     */
    public function getSource(): ?string {
        return $this->source;
    }

    /**
     * Returns the configured target segment.
     *
     * @return string|null Target segment or null if not set.
     */
    public function getTarget(): ?string {
        return $this->target;
    }

    /**
     * Returns the mapping array provided to the pipeline for external reference.
     *
     * @return array The data reference map.
     */
    public function getDataRefMap(): array {
        return $this->dataRefMap;
    }

    /**
     * Checks if a handler of the specified class is already registered in the pipeline.
     *
     * @param class-string<AbstractHandler> $handlerClass The handler
     */
    public function contains( string $handlerClass ): bool {
        return !empty( array_filter( $this->handlers, function ( $handler ) use ( $handlerClass ) {
            return $handlerClass == $handler->getName();
        } ) );
    }

    /**
     * Prepends a handler instance to the beginning of the pipeline.
     *
     * @param class-string<AbstractHandler> $handler Class name (FQN) of the handler to add.
     *
     * @return $this This pipeline instance (for method chaining).
     */
    public function addFirst( string $handler ): Pipeline {
        $handlerInstance = $this->_register( $handler );
        array_unshift( $this->handlers, $handlerInstance );

        return $this;
    }

    /**
     * Inserts a handler into the pipeline before another specified handler.
     *
     * @param class-string<AbstractHandler> $before      Class name (FQN) of the handler before which the new handler will be inserted.
     * @param class-string<AbstractHandler> $newPipeline Class name (FQN) of the new handler to insert.
     *
     * @return $this This pipeline instance (for method chaining).
     */
    public function addBefore( string $before, string $newPipeline ): Pipeline {
        $newPipelineHandler = $this->_register( $newPipeline );
        foreach ( $this->handlers as $pos => $handler ) {
            if ( $handler->getName() == $before ) {
                array_splice( $this->handlers, $pos, 0, [ $newPipelineHandler ] );
                break;
            }
        }

        return $this;

    }

    /**
     * Inserts a handler into the pipeline after another specified handler.
     *
     * @param class-string<AbstractHandler> $after       Class name (FQN) of the handler after which the new handler will be inserted.
     * @param class-string<AbstractHandler> $newPipeline Class name (FQN) of the new handler to insert.
     *
     * @return $this This pipeline instance (for method chaining).
     */
    public function addAfter( string $after, string $newPipeline ): Pipeline {
        $newPipelineHandler = $this->_register( $newPipeline );
        foreach ( $this->handlers as $pos => $handler ) {
            if ( $handler->getName() == $after ) {
                array_splice( $this->handlers, $pos + 1, 0, [ $newPipelineHandler ] );
                break;
            }
        }

        return $this;

    }

    /**
     * Removes the specified handler class from the pipeline.
     *
     * @param class-string<AbstractHandler> $handlerToDelete Handler class name (FQN) to remove.
     *
     * @return $this This pipeline instance (for method chaining).
     */
    public function remove( string $handlerToDelete ): Pipeline {
        foreach ( $this->handlers as $pos => $handler ) {
            if ( $handler->getName() == $handlerToDelete ) {
                unset( $this->handlers[ $pos ] );
                $this->handlers = array_values( $this->handlers );
                break;
            }
        }

        return $this;
    }

    /**
     * Appends a handler instance to the end of the pipeline.
     *
     * @param class-string<AbstractHandler> $handler Class name (FQN) of the handler to add.
     *
     * @return $this This pipeline instance (for method chaining).
     */
    public function addLast( string $handler ): Pipeline {
        $newHandler       = $this->_register( $handler );
        $this->handlers[] = $newHandler;

        return $this;
    }

    /**
     * Transforms the provided segment by sequentially applying all registered handlers
     * and realigns IDs afterward.
     *
     * @param string $segment The input segment string to process.
     *
     * @return string The processed segment after all transformations.
     */
    public function transform( string $segment ): string {
        $this->id_number = -1;
        foreach ( $this->handlers as $handler ) {
            $segment = $handler->transform( $segment );
        }

        return $this->realignIDs( $segment );
    }

    /**
     * Adjusts and realigns ID tags in the provided segment string,
     * so IDs are sequential and formatted as required.
     *
     * @param string $segment The input string containing ID tags to realign.
     *
     * @return string The string with realigned and updated ID tags.
     */
    protected function realignIDs( string $segment ): string {
        if ( $this->id_number > -1 ) {
            preg_match_all( '/"__mtc_[0-9]+"/', $segment, $html, PREG_SET_ORDER );
            foreach ( $html as $pos => $tag_id ) {
                //replace subsequent elements excluding already encoded
                $segment = preg_replace( '/' . $tag_id[ 0 ] . '/', '"mtc_' . ( $pos + 1 ) . '"', $segment, 1 );
            }
        }

        return $segment;
    }

    /**
     * Instantiates a handler by class name and registers this pipeline with it.
     *
     * @template T of AbstractHandler
     * @param class-string<T> $handler Handler class name to instantiate.
     *
     * @return T An instantiated handler object with the pipeline set.
     */
    protected function _register( string $handler ): AbstractHandler {
        $handler = new $handler();
        $handler->setPipeline( $this );

        return $handler;
    }

}