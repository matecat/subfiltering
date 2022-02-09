<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 14.05
 *
 */

namespace Matecat\SubFiltering\Commons;

class Pipeline {

    /**
     * @var AbstractHandler[]
     */
    protected $handlers;

    /**
     * @var int
     */
    protected $id_number = -1;

    protected $source;
    protected $target;
    protected $dataRefMap;

    /**
     * @var bool
     */
    private $segmentContainsHtml = false;

    public function __construct( $source = null, $target = null, $dataRefMap = [] ) {
        $this->source = $source;
        $this->target = $target;
        $this->dataRefMap = $dataRefMap;
    }

    public function getNextId(){
        $this->id_number++;
        return $this->id_number;
    }

    public function resetId(){
        $this->id_number = -1;
    }

    /**
     * @return bool
     */
    public function segmentContainsHtml()
    {
        return $this->segmentContainsHtml;
    }


    public function setSegmentContainsHtml()
    {
        $this->segmentContainsHtml = true;
    }

    /**
     * @return mixed|null
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * @return mixed|null
     */
    public function getTarget() {
        return $this->target;
    }

    /**
     * @return array|mixed
     */
    public function getDataRefMap() {
        return $this->dataRefMap;
    }

    /**
     * @param AbstractHandler $handler
     *
     * @return Pipeline
     */
    public function addFirst( AbstractHandler $handler ) {
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
    public function addBefore( AbstractHandler $before, AbstractHandler $newPipeline ) {
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
    public function addAfter( AbstractHandler $after, AbstractHandler $newPipeline ) {
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
    public function remove( AbstractHandler $handlerToDelete ) {
        foreach ( $this->handlers as $pos => $handler ) {
            if ( $handler->getName() == $handlerToDelete->getName() ) {
                unset($this->handlers[$pos]);
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
    public function addLast( AbstractHandler $handler ) {
        $this->_register( $handler );
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * @param $segment
     *
     * @return mixed
     */
    public function transform( $segment ) {
        $this->id_number = -1;
        foreach ( $this->handlers as $handler ) {
            $segment = $handler->transform( $segment );
        }
        return $this->realignIDs( $segment );
    }

    protected function realignIDs( $segment ){
        if( $this->id_number > -1 ){
            preg_match_all( '/"__mtc_[0-9]+"/', $segment, $html, PREG_SET_ORDER );
            foreach ( $html as $pos => $tag_id ) {
                //replace subsequent elements excluding already encoded
                $segment = preg_replace( '/' . $tag_id[ 0 ] . '/', '"mtc_' . ( $pos + 1 ). '"', $segment, 1 );
            }
        }
        return $segment;
    }

    /**
     * @param AbstractHandler $handler
     *
     * @return $this
     */
    protected function _register( AbstractHandler $handler ) {
        $handler->setPipeline( $this );

        return $this;
    }

}