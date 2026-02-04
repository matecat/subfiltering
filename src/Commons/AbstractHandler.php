<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 14.08
 *
 */

namespace Matecat\SubFiltering\Commons;


abstract class AbstractHandler
{

    protected string $name;

    /**
     * @var Pipeline
     */
    protected Pipeline $pipeline;

    /**
     * @param string $segment
     *
     * @return string
     *
     */
    abstract public function transform(string $segment): string;

    /**
     * AbstractHandler constructor.
     */
    public function __construct()
    {
        $this->name = get_class($this);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param Pipeline $pipeline
     */
    public function setPipeline(Pipeline $pipeline): void
    {
        $this->pipeline = $pipeline;
    }

    /**
     * @return Pipeline
     */
    public function getPipeline(): Pipeline
    {
        return $this->pipeline;
    }

}
