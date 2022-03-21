<?php

namespace Matecat\SubFiltering\Filters\Sprintf;

class SprintfLocker {

    /**
     * Protection tags
     */
    const PRE_LOCK_TAG  = '_____########';
    const POST_LOCK_TAG = '########_____';

    /**
     * @var null
     */
    private $source;

    /**
     * @var null
     */
    private $target;

    /**
     * @var array
     */
    private $notAllowedMap = [];

    /**
     * @var array
     */
    private $replacementMap = [];

    /**
     * SprintfLocker constructor.
     *
     * @param null $source
     * @param null $target
     */
    public function __construct($source = null, $target = null)
    {
        $this->source = $source;
        $this->target = $target;
        $this->notAllowedMap = $this->createNotAllowedMap();
    }

    /**
     * @return array
     */
    private function createNotAllowedMap()
    {
        $map = [];

        $all = include __DIR__ . "/language/all/not_allowed.php";
        $map = array_merge($map, $all);

        if($this->source and file_exists(__DIR__ . "/language/".$this->source."/not_allowed.php")){
            $source = include __DIR__ . "/language/".$this->source."/not_allowed.php";
            $map = array_merge($map, $source);
        }

        if($this->target and file_exists(__DIR__ . "/language/".$this->target."/not_allowed.php")){
            $target = include __DIR__ . "/language/".$this->target."/not_allowed.php";
            $map = array_merge($map, $target);
        }

        return $map;
    }

    /**
     * @param $segment
     *
     * @return string
     */
    public function lock($segment)
    {
        $replacementMap = $this->createReplacementMap($segment);
        $this->replacementMap = $replacementMap;

        return str_replace( array_keys($replacementMap), array_values($replacementMap), $segment );
    }

    /**
     * @param $segment
     *
     * @return string
     */
    public function unlock($segment)
    {
        $replacementMap = $this->replacementMap;

        return str_replace( array_values($replacementMap), array_keys($replacementMap),  $segment );
    }

    /**
     * Create the replacement map
     *
     * @param $segment
     *
     * @return array
     */
    private function createReplacementMap( $segment)
    {
        $replacementMap = [];

        foreach ($this->notAllowedMap as $item => $details){

            $type = $details['type'];

            switch ($type){
                case 'exact':
                    $replacementMap[$item] = self::PRE_LOCK_TAG . $this->maskString($item) . self::POST_LOCK_TAG;
                    break;

                case 'regex':
                    preg_match_all('/'.$item.'/', $segment, $matches);

                    foreach ($matches[0] as $match){
                        $replacementMap[$match] = self::PRE_LOCK_TAG . $this->maskString($match) . self::POST_LOCK_TAG;
                    }
                    break;
            }
        }

        return $replacementMap;
    }

    /**
     * @param $string
     *
     * @return string
     */
    private function maskString($string)
    {
        return str_replace(['%','-','_'],'', $string);
    }
}