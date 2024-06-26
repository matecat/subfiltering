<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 14.08
 *
 */

namespace Matecat\SubFiltering\Commons;


use Exception;

abstract class AbstractHandler {

    protected $name;

    /**
     * @var Pipeline
     */
    protected $pipeline;

    /**
     * @param $segment
     *
     * @return string
     *
     * @throws Exception
     */
    public abstract function transform( $segment );

    /**
     * AbstractHandler constructor.
     */
    public function __construct() {
        $this->name = get_class( $this );
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param Pipeline $pipeline
     */
    public function setPipeline( Pipeline $pipeline ) {
        $this->pipeline = $pipeline;
    }

    /**
     * @return Pipeline
     */
    public function getPipeline() {
        return $this->pipeline;
    }

}