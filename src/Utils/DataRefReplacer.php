<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 22/04/24
 * Time: 15:13
 *
 */

namespace Matecat\SubFiltering\Utils;

use DOMException;
use Exception;
use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;
use Matecat\XmlParser\XmlParser;

class DataRefReplacer {
    /**
     * @var array
     */
    private $map;

    /**
     * DataRefReplacer constructor.
     *
     * @param array $map
     */
    public function __construct( array $map = null ) {
        $this->map = $map;
    }

    /**
     * This function inserts a new attribute called 'equiv-text' from dataRef contained in <ph>, <sc>, <ec>, <pc> tags against the provided map array
     *
     * For a complete reference see:
     *
     * Http://docs.oasis-open.org/xliff/xliff-core/v2.1/os/xliff-core-v2.1-os.html#dataref
     *
     * @param string $string
     *
     * @return string
     */
    public function replace( $string ) {

        // if map is empty
        // or the string has not a dataRef attribute
        // return string as is
        if ( empty( $this->map ) || !$this->hasAnyDataRefAttribute( $string ) ) {
            return $string;
        }

        // try not to throw exception for wrong segments with opening tags and no closing
        try {

            $html = XmlParser::parse( $string, true );

            $dataRefEndMap = [];

            foreach ( $html as $node ) {

                // 1. Replace <ph>|<sc>|<ec> tags
                $string = $this->recursiveTransformDataRefToPhTag( $node, $string );

                // 2. Replace self-closed <pc dataRefStart="xyz" /> tags
                $string = $this->recursiveReplaceSelfClosedPcTags( $node, $string );

                // 3. Build the DataRefEndMap needed by replaceClosingPcTags function
                // (needed for correct handling of </pc> closing tags)
                // make this inline with one foreach cycle
                $this->extractDataRefMapRecursively( $node, $dataRefEndMap );

            }

            // 4. replace pc tags
            $string = $this->replaceOpeningPcTags( $string );
            $string = $this->replaceClosingPcTags( $string, $dataRefEndMap );

        } catch ( Exception $ignore ) {
            // if something fails here, do not throw exception and return the original string instead
        } finally {
            return $string;
        }

    }

    /**
     * @param string $string
     *
     * @return bool
     */
    private function hasAnyDataRefAttribute( $string ) {
        return (bool)preg_match( '/(dataRef|dataRefStart|dataRefEnd)=[\'"].*?[\'"]/', $string );
    }

    /**
     * This function adds equiv-text attribute to <ph>, <ec>, and <sc> tags.
     *
     * Please note that <ec> and <sc> tags are converted to <ph> tags (needed by Matecat);
     * in this case, another special attribute (dataType) is added just before equiv-text
     *
     * If there is no id tag, it will be copied from dataRef attribute
     *
     * @param object $node
     * @param string $string
     *
     * @return string
     */
    private function recursiveTransformDataRefToPhTag( $node, $string ) {

        if ( $node->has_children ) {

            foreach ( $node->inner_html as $childNode ) {
                $string = $this->recursiveTransformDataRefToPhTag( $childNode, $string );
            }

        } else {

            switch ( $node->tagName ) {
                case 'ph':
                case 'sc':
                case 'ec':
                    break;
                default:
                    return $string;
            }

            if ( !isset( $node->attributes[ 'dataRef' ] ) ) {
                return $string;
            }

            // if isset a value in the map calculate base64 encoded value
            // otherwise skip
            if ( !in_array( $node->attributes[ 'dataRef' ], array_keys( $this->map ) ) ) {
                return $string;
            }

            $dataRefName  = $node->attributes[ 'dataRef' ];   // map identifier. Eg: source1
            $dataRefValue = $this->map[ $dataRefName ];   // map identifier. Eg: source1

            // check if is null or an empty string, in this case, convert it to NULL string
            if ( is_null( $dataRefValue ) || $dataRefValue === '' ) {
                $this->map[ $dataRefName ] = 'NULL';
            }


            $newTag = [ '<ph' ];

            // if there is no id copy it from dataRef
            if ( !isset( $node->attributes[ 'id' ] ) ) {
                $newTag[] = 'id="' . $dataRefName . '"';
                $newTag[] = 'x-removeId="true"';
            } else {
                $newTag[] = 'id="' . $node->attributes[ 'id' ] . '"';
            }

            // introduce dataType for <ec>/<sc> tag handling
            if ( $node->tagName === 'ec' ) {
                $newTag[] = 'ctype="' . CTypeEnum::EC_DATA_REF . '"';
            } elseif ( $node->tagName === 'sc' ) {
                $newTag[] = 'ctype="' . CTypeEnum::SC_DATA_REF . '"';
            } else {
                $newTag[] = 'ctype="' . CTypeEnum::PH_DATA_REF . '"';
            }

            $newTag[] = 'equiv-text="base64:' . base64_encode( $dataRefValue ) . '"';
            $newTag[] = 'x-orig="' . base64_encode( $node->node ) . '"';

            return str_replace( $node->node, implode( " ", $newTag ) . '/>', $string );

        }

        return $string;
    }

    /**
     * @param $node
     * @param $string
     *
     * @return string
     * @throws DOMException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    private function recursiveReplaceSelfClosedPcTags( $node, $string ) {

        if ( $node->has_children ) {

            foreach ( $node->inner_html as $childNode ) {
                $string = $this->recursiveReplaceSelfClosedPcTags( $childNode, $string );
            }

        } elseif ( $node->tagName == 'pc' && $node->self_closed === true ) {

            if ( isset( $node->attributes[ 'dataRefStart' ] ) && array_key_exists( $node->attributes[ 'dataRefStart' ], $this->map ) ) {

                $newTag = [ '<ph' ];

                if ( isset( $node->attributes[ 'id' ] ) ) {
                    $newTag[] = 'id="' . $node->attributes[ 'id' ] . '_1"';
                }

                $newTag[] = 'ctype="' . CTypeEnum::PC_SELF_CLOSE_DATA_REF . '"';
                $newTag[] = 'equiv-text="base64:' . base64_encode( $this->map[ $node->attributes[ 'dataRefStart' ] ] ) . '"';
                $newTag[] = 'x-orig="' . base64_encode( $node->node ) . '"';

                $string = str_replace( $node->node, implode( " ", $newTag ) . '/>', $string );
            }

        }

        return $string;

    }

    /**
     * Extract (recursively) the dataRefEnd map from single nodes
     *
     * @param object $node
     * @param        $dataRefEndMap
     */
    private function extractDataRefMapRecursively( $node, &$dataRefEndMap ) {

        // we have to build the map for the closing pc tag, so get the children first
        if ( $node->has_children ) {
            foreach ( $node->inner_html as $nestedNode ) {
                $this->extractDataRefMapRecursively( $nestedNode, $dataRefEndMap );
            }
        }

        // EXCLUDE self closed <pc/>
        if ( $node->tagName === 'pc' && $node->self_closed === false ) {
            if ( isset( $node->attributes[ 'dataRefEnd' ] ) ) {
                $dataRefEnd = $node->attributes[ 'dataRefEnd' ];
            } elseif ( isset( $node->attributes[ 'dataRefStart' ] ) ) {
                $dataRefEnd = $node->attributes[ 'dataRefStart' ];
            } else {
                $dataRefEnd = null;
            }

            $dataRefEndMap[] = [
                    'id'         => isset( $node->attributes[ 'id' ] ) ? $node->attributes[ 'id' ] : null,
                    'dataRefEnd' => $dataRefEnd,
            ];

        }

    }

    /**
     * Replace opening <pc> tags with correct reference in the $string
     *
     * @param string $string
     *
     * @return string
     * @throws DOMException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    private function replaceOpeningPcTags( $string ) {

        preg_match_all( '|<pc ([^>/]+?)>|iu', $string, $openingPcMatches );

        foreach ( $openingPcMatches[ 0 ] as $match ) {

            $node = XmlParser::parse( $match . '</pc>', true )[ 0 ]; // add a closing tag to not break xml integrity

            // CASE 1 - Missing `dataRefStart`
            if ( isset( $node->attributes[ 'dataRefEnd' ] ) && !isset( $node->attributes[ 'dataRefStart' ] ) ) {
                $node->attributes[ 'dataRefStart' ] = $node->attributes[ 'dataRefEnd' ];
            }

            // CASE 2 - Missing `dataRefEnd`
            if ( isset( $node->attributes[ 'dataRefStart' ] ) && !isset( $node->attributes[ 'dataRefEnd' ] ) ) {
                $node->attributes[ 'dataRefEnd' ] = $node->attributes[ 'dataRefStart' ];
            }

            if ( isset( $node->attributes[ 'dataRefStart' ] ) ) {

                $startValue = $this->map[ $node->attributes[ 'dataRefStart' ] ] ?: 'NULL'; //handling null values in original data map

                $newTag = [ '<ph' ];

                if ( isset( $node->attributes[ 'id' ] ) ) {
                    $newTag[] = 'id="' . $node->attributes[ 'id' ] . '_1"';
                }

                $newTag[] = 'ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '"';
                $newTag[] = 'equiv-text="base64:' . base64_encode( $startValue ) . '"';
                $newTag[] = 'x-orig="' . base64_encode( $match ) . '"';

                // conversion for opening <pc> tag
                $string = str_replace( $match, implode( " ", $newTag ) . '/>', $string );

            }
        }

        return $string;
    }

    /**
     * Replace closing </pc> tags with correct reference in the $string
     * thanks to $dataRefEndMap
     *
     * @param string $string
     * @param array  $dataRefEndMap
     *
     * @return string
     */
    private function replaceClosingPcTags( $string, $dataRefEndMap = [] ) {

        preg_match_all( '|</pc>|iu', $string, $closingPcMatches, PREG_OFFSET_CAPTURE );
        $delta = 0;

        foreach ( $closingPcMatches[ 0 ] as $index => $match ) {

            $offset = $match[ 1 ];
            $length = 5; // strlen of '</pc>'

            $attr = isset( $dataRefEndMap[ $index ] ) ? $dataRefEndMap[ $index ] : null;

            if ( !empty( $attr ) && isset( $attr[ 'dataRefEnd' ] ) ) {

                $endValue = !empty( $this->map[ $attr[ 'dataRefEnd' ] ] ) ? $this->map[ $attr[ 'dataRefEnd' ] ] : 'NULL';

                $newTag = [ '<ph' ];

                if ( isset( $attr[ 'id' ] ) ) {
                    $newTag[] = 'id="' . $attr[ 'id' ] . '_2"';
                }

                $newTag[] = 'ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '"';
                $newTag[] = 'equiv-text="base64:' . base64_encode( $endValue ) . '"';
                $newTag[] = 'x-orig="' . base64_encode( '</pc>' ) . '"';

                // conversion for opening <pc> tag
                $completeTag = implode( " ", $newTag ) . '/>';
                $realOffset  = ( $delta === 0 ) ? $offset : ( $offset + $delta );
                $string      = substr_replace( $string, $completeTag, $realOffset, $length );
                $delta       = $delta + strlen( $completeTag ) - $length;

            }

        }

        return $string;

    }

    /**
     * @param string $string
     *
     * @return string
     * @throws DOMException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    public function restore( $string ) {

        // if map is empty return string as is
        if ( empty( $this->map ) ) {
            return $string;
        }

        $html = XmlParser::parse( $string, true );

        foreach ( $html as $node ) {
            $string = $this->recursiveRestoreOriginalTags( $node, $string );
        }

        return $string;
    }

    /**
     * @param object $node
     * @param        $string
     *
     * @return string
     */
    private function recursiveRestoreOriginalTags( $node, $string ) {

        if ( $node->has_children ) {

            foreach ( $node->inner_html as $childNode ) {
                $string = $this->recursiveRestoreOriginalTags( $childNode, $string );
            }

        } else {

            $cType = isset( $node->attributes[ 'ctype' ] ) ? $node->attributes[ 'ctype' ] : null;

            if ( $cType ) {

                switch ( $node->attributes[ 'ctype' ] ) {
                    case CTypeEnum::ORIGINAL_PC_OPEN:
                    case CTypeEnum::ORIGINAL_PC_CLOSE:
                    case CTypeEnum::ORIGINAL_PH_OR_NOT_DATA_REF:
                    case CTypeEnum::PH_DATA_REF:
                    case CTypeEnum::PC_OPEN_DATA_REF:
                    case CTypeEnum::PC_CLOSE_DATA_REF:
                    case CTypeEnum::PC_SELF_CLOSE_DATA_REF:
                    case CTypeEnum::SC_DATA_REF:
                    case CTypeEnum::EC_DATA_REF:
                        return preg_replace( '/' . preg_quote( $node->node, '/' ) . '/', base64_decode( $node->attributes[ 'x-orig' ] ), $string, 1 );
                }

            }

        }

        return $string;

    }

}