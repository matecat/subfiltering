<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 14.05
 *
 */

namespace Matecat\SubFiltering\Commons;

use Exception;
use Matecat\SubFiltering\Enum\ConstantEnum;

class Pipeline {

    /**
     * @var AbstractHandler[]
     */
    protected array $handlers;

    /**
     * @var int
     */
    protected int $id_number = -1;

    protected ?string $source;
    protected ?string $target;
    protected array   $dataRefMap;

    /**
     * @var bool
     */
    private bool $segmentContainsHtml = false;

    public function __construct( ?string $source = null, ?string $target = null, array $dataRefMap = [] ) {
        $this->source     = $source;
        $this->target     = $target;
        $this->dataRefMap = $dataRefMap;
    }

    public function getNextId(): string {
        $this->id_number++;

        return ConstantEnum::INTERNAL_ATTR_ID_PREFIX . $this->id_number;
    }

    public function resetId(): void {
        $this->id_number = -1;
    }

    /**
     * @return bool
     */
    public function segmentContainsHtml(): bool {
        return $this->segmentContainsHtml;
    }


    public function setSegmentContainsHtml() {
        $this->segmentContainsHtml = true;
    }

    /**
     * @return string|null
     */
    public function getSource(): ?string {
        return $this->source;
    }

    /**
     * @return string|null
     */
    public function getTarget(): ?string {
        return $this->target;
    }

    /**
     * @return array
     */
    public function getDataRefMap(): array {
        return $this->dataRefMap;
    }

    /**
     * @param AbstractHandler $handler
     *
     * @return Pipeline
     */
    public function addFirst( AbstractHandler $handler ): Pipeline {
        $this->_register( $handler );
        array_unshift( $this->handlers, $handler );

        return $this;
    }

    /**
     * @param AbstractHandler $newPipeline
     * @param AbstractHandler $before
     *
     * @return Pipeline
     */
    public function addBefore( AbstractHandler $before, AbstractHandler $newPipeline ): Pipeline {
        $this->_register( $newPipeline );
        foreach ( $this->handlers as $pos => $handler ) {
            if ( $handler->getName() == $before->getName() ) {
                array_splice( $this->handlers, $pos, 0, [ $newPipeline ] );
                break;
            }
        }

        return $this;

    }

    /**
     * @param AbstractHandler $newPipeline
     * @param AbstractHandler $after
     *
     * @return Pipeline
     */
    public function addAfter( AbstractHandler $after, AbstractHandler $newPipeline ): Pipeline {
        $this->_register( $newPipeline );
        foreach ( $this->handlers as $pos => $handler ) {
            if ( $handler->getName() == $after->getName() ) {
                array_splice( $this->handlers, $pos + 1, 0, [ $newPipeline ] );
                break;
            }
        }

        return $this;

    }

    /**
     * Remove handler from pipeline
     *
     * @param AbstractHandler $handlerToDelete
     *
     * @return $this
     */
    public function remove( AbstractHandler $handlerToDelete ): Pipeline {
        foreach ( $this->handlers as $pos => $handler ) {
            if ( $handler->getName() == $handlerToDelete->getName() ) {
                unset( $this->handlers[ $pos ] );
                $this->handlers = array_values( $this->handlers );
                break;
            }
        }

        return $this;
    }

    /**
     * @param AbstractHandler $handler
     *
     * @return Pipeline
     */
    public function addLast( AbstractHandler $handler ): Pipeline {
        $this->_register( $handler );
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * @param string $segment
     *
     * @return mixed
     * @throws Exception
     */
    public function transform( string $segment ) {
        $this->id_number = -1;
        foreach ( $this->handlers as $handler ) {
            $segment = $handler->transform( $segment );
        }

        return $this->realignIDs( $segment );
    }

    protected function realignIDs( string $segment ) {
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
     * @param AbstractHandler $handler
     *
     * @return $this
     */
    protected function _register( AbstractHandler $handler ): Pipeline {
        $handler->setPipeline( $this );

        return $this;
    }

}