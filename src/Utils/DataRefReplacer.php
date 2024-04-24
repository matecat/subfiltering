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
     * http://docs.oasis-open.org/xliff/xliff-core/v2.1/os/xliff-core-v2.1-os.html#dataref
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

        // (recursively) clean string from equiv-text eventually present
        $string = $this->cleanFromEquivText( $string );

        $html = XmlParser::parse( $string, true );

        // 1. Replace <ph>|<sc>|<ec> tags
        foreach ( $html as $node ) {
            $string = $this->recursiveAddEquivTextToPhTag( $node, $string );
        }

        // 2. Replace <pc> tags
        if ( $this->stringContainsPcTags( $string ) ) {

            // replace self-closed <pc />
            $string = $this->replaceSelfClosedPcTags( $string );

            // create a dataRefEnd map
            // (needed for correct handling of </pc> closing tags)
            $dataRefEndMap = $this->buildDataRefEndMap( $html );
            $string        = $this->replaceOpeningPcTags( $string );
            $string        = $this->replaceClosingPcTags( $string, $dataRefEndMap );
        }

        return $string;
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
     * @param string $string
     *
     * @return string
     * @throws DOMException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    private function cleanFromEquivText( $string ) {
        $html = XmlParser::parse( $string, true );

        foreach ( $html as $node ) {
            $string = $this->recursiveCleanFromEquivText( $node, $string );
        }

        return $string;
    }

    /**
     * This function add equiv-text attribute to <ph>, <ec>, and <sc> tags.
     *
     * Please note that <ec> and <sc> tags are converted to <ph> tags (needed by Matecat);
     * in this case another special attribute (dataType) is added just before equiv-text
     *
     * If there is no id tag, it will be copied from dataRef attribute
     *
     * @param object $node
     * @param string $string
     *
     * @return string
     */
    private function recursiveAddEquivTextToPhTag( $node, $string ) {
        if ( $node->has_children ) {
            foreach ( $node->inner_html as $childNode ) {
                $string = $this->recursiveAddEquivTextToPhTag( $childNode, $string );
            }
        } else {
            if ( $node->tagName === 'ph' || $node->tagName === 'sc' || $node->tagName === 'ec' ) {
                if ( !isset( $node->attributes[ 'dataRef' ] ) ) {
                    return $string;
                }

                $a = $node->node;  // complete match. Eg:  <ph id="source1" dataRef="source1"/>
                $b = $node->attributes[ 'dataRef' ];   // map identifier. Eg: source1

                // if isset a value in the map calculate base64 encoded value
                // otherwise skip
                if ( !in_array( $b, array_keys( $this->map ) ) ) {
                    return $string;
                }

                // check if is null, in this case convert it to NULL string
                if ( is_null( $this->map[ $b ] ) ) {
                    $this->map[ $b ] = 'NULL';
                }

                $value              = $this->map[ $b ];
                $base64EncodedValue = base64_encode( $value );

                if ( empty( $base64EncodedValue ) ) {
                    return $string;
                }

                // if there is no id copy it from dataRef
                $id = ( !isset( $node->attributes[ 'id' ] ) ) ? ' id="' . $b . '" removeId="true"' : '';

                // introduce dataType for <ec>/<sc> tag handling
                $dataType = ( $this->isAEcOrScTag( $node ) ) ? ' dataType="' . $node->tagName . '"' : '';

                // replacement
                $d = str_replace( '/', $id . $dataType . ' equiv-text="base64:' . $base64EncodedValue . '"/', $a );
                $a = $this->removeAngleBrackets( $a );
                $d = $this->removeAngleBrackets( $d );

                // convert <ec>/<sc> into <ph>
                if ( $this->isAEcOrScTag( $node ) ) {
                    $d = 'ph' . substr( $d, 2 );
                    $d = trim( $d );
                }

                return str_replace( $a, $d, $string );
            }
        }

        return $string;
    }

    /**
     * @param $string
     *
     * @return bool
     */
    private function stringContainsPcTags( $string ) {
        preg_match_all( '/<pc [^>]+?>/iu', $string, $openingPcMatches );

        return ( isset( $openingPcMatches[ 0 ] ) && count( $openingPcMatches[ 0 ] ) > 0 );
    }

    /**
     * @param $string
     *
     * @return mixed
     * @throws DOMException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    private function replaceSelfClosedPcTags( $string ) {

        $regex = '|<pc[^>]+?/>|iu';
        preg_match_all( $regex, $string, $selfClosedPcMatches );

        foreach ( $selfClosedPcMatches[ 0 ] as $match ) {

            $html       = XmlParser::parse( $match, true );
            $node       = $html[ 0 ];
            $attributes = $node->attributes;

            if ( isset( $attributes[ 'dataRefStart' ] ) && array_key_exists( $node->attributes[ 'dataRefStart' ], $this->map ) ) {
                $replacement = '<ph id="' . $attributes[ 'id' ] . '" dataType="pcSelf" originalData="' . base64_encode( $match ) . '" dataRef="' . $attributes[ 'dataRefStart' ] . '" equiv-text="base64:' . base64_encode( $this->map[ $node->attributes[ 'dataRefStart' ] ] ) . '"/>';
                $string      = str_replace( $match, $replacement, $string );
            }
        }

        return $string;
    }

    /**
     * Build the DataRefEndMap needed by replaceClosingPcTags function
     * (only for <pc> tags handling)
     *
     * @param $html
     *
     * @return array
     */
    private function buildDataRefEndMap( $html ) {
        $dataRefEndMap = [];

        foreach ( $html as $index => $node ) {
            if ( $node->tagName === 'pc' ) {
                $this->extractDataRefMapRecursively( $node, $dataRefEndMap );
            }
        }

        return $dataRefEndMap;
    }

    /**
     * Extract (recursively) the dataRefEnd map from single nodes
     *
     * @param object $node
     * @param        $dataRefEndMap
     */
    private function extractDataRefMapRecursively( $node, &$dataRefEndMap ) {
        if ( $this->nodeContainsNestedPcTags( $node ) ) {
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
     * @param object $node
     * @param        $string
     *
     * @return string|string[]
     */
    private function recursiveCleanFromEquivText( $node, $string ) {

        if ( $node->tagName == '#text' ) {
            return $string;
        }

        if ( $node->has_children ) {
            foreach ( $node->inner_html as $childNode ) {
                $string = $this->recursiveCleanFromEquivText( $childNode, $string );
            }
        } else {
            if ( isset( $node->attributes[ 'dataRef' ] ) && array_key_exists( $node->attributes[ 'dataRef' ], $this->map ) ) {
                $cleaned = preg_replace( '/ equiv-text="(.*?)"/', '', $node->node );
                $string  = str_replace( $node->node, $cleaned, $string );
            }
        }

        return $string;
    }

    /**
     * Replace opening <pc> tags with correct reference in the $string
     *
     * @param string $string
     *
     * @return string
     */
    private function replaceOpeningPcTags( $string ) {

        preg_match_all( '|<pc ([^>/]+?)>|iu', $string, $openingPcMatches );

        foreach ( $openingPcMatches[ 0 ] as $index => $match ) {

            preg_match_all( '|([a-zA-Z]+?)\s*=\s*[\'"](.+?)[\'"]|', $openingPcMatches[ 1 ][ $index ], $_attr, PREG_SET_ORDER );

            $attr = [];
            foreach ( $_attr as $attrGroup ) {
                $attr[ $attrGroup[ 1 ] ] = $attrGroup[ 2 ];
            }

            // CASE 1 - Missing `dataRefStart`
            if ( isset( $attr[ 'dataRefEnd' ] ) && !isset( $attr[ 'dataRefStart' ] ) ) {
                $attr[ 'dataRefStart' ] = $attr[ 'dataRefEnd' ];
            }

            // CASE 2 - Missing `dataRefEnd`
            if ( isset( $attr[ 'dataRefStart' ] ) && !isset( $attr[ 'dataRefEnd' ] ) ) {
                $attr[ 'dataRefEnd' ] = $attr[ 'dataRefStart' ];
            }

            if ( isset( $attr[ 'dataRefStart' ] ) ) {
                $startOriginalData       = $match; // opening <pc>
                $startValue              = $this->map[ $attr[ 'dataRefStart' ] ] ?: 'NULL'; //handling null values in original data map
                $base64EncodedStartValue = base64_encode( $startValue );
                $base64StartOriginalData = base64_encode( $startOriginalData );

                // conversion for opening <pc> tag
                $openingPcConverted = '<ph ' . ( ( isset( $attr[ 'id' ] ) ) ? 'id="' . $attr[ 'id' ] . '_1"' : '' ) . ' dataType="pcStart" originalData="' . $base64StartOriginalData . '" dataRef="'
                        . $attr[ 'dataRefStart' ] . '" equiv-text="base64:'
                        . $base64EncodedStartValue . '"/>';

                $string = str_replace( $startOriginalData, $openingPcConverted, $string );
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
            $length = strlen( $match[ 0 ] );
            $attr   = $dataRefEndMap[ $index ];

            if ( !empty( $attr ) && isset( $attr[ 'dataRefEnd' ] ) ) {
                $endOriginalData       = $match[ 0 ]; // </pc>
                $endValue              = $this->map[ $attr[ 'dataRefEnd' ] ] ?: 'NULL';
                $base64EncodedEndValue = base64_encode( $endValue );
                $base64EndOriginalData = base64_encode( $endOriginalData );

                // conversion for closing <pc> tag
                $closingPcConverted = '<ph ' . ( ( isset( $attr[ 'id' ] ) ) ? 'id="' . $attr[ 'id' ] . '_2"' : '' ) . ' dataType="pcEnd" originalData="' . $base64EndOriginalData . '" dataRef="'
                        . $attr[ 'dataRefEnd' ] . '" equiv-text="base64:' . $base64EncodedEndValue . '"/>';

                $realOffset = ( $delta === 0 ) ? $offset : ( $offset + $delta );

                $string = substr_replace( $string, $closingPcConverted, $realOffset, $length );
                $delta  = $delta + strlen( $closingPcConverted ) - $length;
            }
        }

        return !is_array( $string ) ? $string : implode( $string );
    }

    /**
     * @param object $node
     *
     * @return bool
     */
    private function nodeContainsNestedPcTags( $node ) {
        if ( !$node->has_children ) {
            return false;
        }

        foreach ( $node->inner_html as $nestedNode ) {
            if ( $nestedNode->tagName === 'pc' && ( isset( $node->attributes[ 'dataRefEnd' ] ) || isset( $node->attributes[ 'dataRefStart' ] ) ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function restore( $string ) {
        // if map is empty return string as is
        if ( empty( $this->map ) ) {
            return $string;
        }

        // replace eventual empty equiv-text=""
        $string = str_replace( ' equiv-text=""', '', $string );
        $html   = XmlParser::parse( $string, true );

        foreach ( $html as $node ) {
            $string = $this->recursiveRemoveOriginalData( $node, $string );
        }

        return $string;
    }

    /**
     * @param object $node
     * @param        $string
     *
     * @return string|string[]
     */
    private function recursiveRemoveOriginalData( $node, $string ) {
        if ( $node->has_children ) {
            foreach ( $node->inner_html as $childNode ) {
                $string = $this->recursiveRemoveOriginalData( $childNode, $string );
            }
        } else {

            if ( !isset( $node->attributes[ 'dataRef' ] ) ) {
                return $string;
            }

            $a = $node->node;                  // complete match. Eg:  <ph id="source1" dataRef="source1"/>
            $b = $node->attributes[ 'dataRef' ]; // map identifier. Eg: source1

            // if isset a value in the map calculate base64 encoded value
            // or it is an empty string
            // otherwise skip
            if ( !in_array( $b, array_keys( $this->map ) ) ) {
                return $string;
            }

            // check if is null, in this case convert it to NULL string
            if ( is_null( $this->map[ $b ] ) ) {
                $this->map[ $b ] = 'NULL';
            }

            // remove id?
            $removeId = ( isset( $node->attributes[ 'removeId' ] ) && $node->attributes[ 'removeId' ] === "true" ) ? ' id="' . $b . '" removeId="true"' : '';

            // grab dataType attribute for <ec>/<sc> tag handling
            $dataType = ( $this->wasAEcOrScTag( $node ) ) ? ' dataType="' . $node->attributes[ 'dataType' ] . '"' : '';

            $d = str_replace( $removeId . $dataType . ' equiv-text="base64:' . base64_encode( $this->map[ $b ] ) . '"/>', '/>', $a );

            // replace original <ec>/<sc> tag
            if ( $this->wasAEcOrScTag( $node ) ) {
                $d = $node->attributes[ 'dataType' ] . substr( $d, 3 );
                $d = trim( $d );
            }

            // replace only content tag, no matter if the string is encoded or not
            // in this way we can handle string with mixed tags (encoded and not-encoded)
            // in the same string
            $a = $this->removeAngleBrackets( $a );
            $d = $this->removeAngleBrackets( $d );

            $string = str_replace( $a, $d, $string );

            // restoring <pc/> self-closed here
            if ( CatUtils::contains( 'dataType="pcSelf"', $d ) ) {
                preg_match( '/\s?originalData="(.*?)"\s?/', $d, $originalDataMatches );

                if ( isset( $originalDataMatches[ 1 ] ) ) {
                    $originalData = base64_decode( $originalDataMatches[ 1 ] );
                    $originalData = $this->removeAngleBrackets( $originalData );
                    $string       = str_replace( $d, $originalData, $string );
                }
            }

            // restoring <pc> tags here
            // if <ph> tag has originalData and originalType is pcStart or pcEnd,
            // replace with original data
            if ( CatUtils::contains( 'dataType="pcStart"', $d ) || CatUtils::contains( 'dataType="pcEnd"', $d ) ) {
                preg_match( '/\s?originalData="(.*?)"\s?/', $d, $originalDataMatches );

                if ( isset( $originalDataMatches[ 1 ] ) ) {
                    $originalData = base64_decode( $originalDataMatches[ 1 ] );
                    $originalData = $this->removeAngleBrackets( $originalData );
                    $string       = str_replace( $d, $originalData, $string );
                }
            }
        }

        return $string;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function removeAngleBrackets( $string ) {
        return str_replace( [ '<', '>', '&lt;', '&gt;' ], '', $string );
    }

    /**
     * This function checks if a node is a tag <ec> or <sc>
     *
     * @param $node
     *
     * @return bool
     */
    private function isAEcOrScTag( $node ) {
        return ( $node->tagName === 'ec' || $node->tagName === 'sc' );
    }

    /**
     * This function checks if a <ph> tag node
     * was originally a <ec> or <sc>
     *
     * @param $node
     *
     * @return bool
     */
    private function wasAEcOrScTag( $node ) {
        return ( isset( $node->attributes[ 'dataType' ] ) && ( $node->attributes[ 'dataType' ] === 'ec' || $node->attributes[ 'dataType' ] === 'sc' ) );
    }
}