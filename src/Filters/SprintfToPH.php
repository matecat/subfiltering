<?php


namespace Matecat\SubFiltering\Filters;


use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\Filters\Sprintf\SprintfLocker;

class SprintfToPH extends AbstractHandler {

    private $source;
    private $target;

    public function __construct() {
        parent::__construct();
    }

    /**
     * TestSet:
     * <code>
     * |%-4d|%-4d|
     * |%':4d|
     * |%-':4d|
     * |%-'04d|
     * %02.2f
     * %02d
     * %1$s!
     * %08b
     * 20%-os - ignored
     * 20%-dir - ignored
     * 20%-zar - ignored
     *</code>
     *
     * @see
     * - https://developer.apple.com/library/archive/documentation/Cocoa/Conceptual/Strings/Articles/formatSpecifiers.html#//apple_ref/doc/uid/TP40004265-SW1
     * - https://en.cppreference.com/w/c/io/fprintf
     * - https://www.php.net/manual/en/function.sprintf.php
     * - https://www.w3resource.com/c-programming/stdio/c_library_method_sprintf.php
     *
     * @param $segment
     *
     * @return string
     */
    public function transform( $segment ) {

        $sprintfLocker = new SprintfLocker( $this->pipeline->getSource(), $this->pipeline->getTarget() );

        // placeholding
        $segment = $sprintfLocker->lock( $segment );

        // Octal parsing is disabled due to Hungarian percentages 20%-os
        $regex = '/(?:\x25\x25)|(\x25(?:(?:[1-9]\d*)\$|\((?:[^\)]+)\))?(?:\+)?(?:0|[+-]?\'[^$])?(?:-)?(?:\d+)?(?:\.(?:\d+))?((?:[hjlqtzL]{0,2}[ac-giopsuxAC-GOSUX]{1})(?![\d\w])|(?:#@[\w]+@)|(?:@)))/';


        preg_match_all( $regex, $segment, $vars, PREG_SET_ORDER );
        foreach ( $vars as $pos => $variable ) {

            //replace subsequent elements excluding already encoded
            $segment = preg_replace(
                    '/' . preg_quote( $variable[ 0 ], '/' ) . '/',
                    '<ph id="__mtc_' . $this->getPipeline()->getNextId() . '" ctype="' . CTypeEnum::SPRINTF . '" equiv-text="base64:' . base64_encode( $variable[ 0 ] ) . '"/>',
                    $segment,
                    1
            );
        }

        //revert placeholding
        $segment = $sprintfLocker->unlock( $segment );

        return $segment;
    }

}