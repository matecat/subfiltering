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
     * @var Map
     */
    private Map $map;

    /**
     * DataRefReplacer constructor.
     *
     * @param array $map
     */
    public function __construct( array $map = [] ) {
        $this->map = Map::instance( $this->sanitizeMap( $map ) );
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
    public function replace( string $string ): string {

        // if the map is empty
        // or the string has not a dataRef attribute
        // return string as is
        if ( $this->map->isEmpty() || !$this->hasAnyDataRefAttribute( $string ) ) {
            return $string;
        }

        // try not to throw exception for wrong segments with opening tags and no closing
        try {

            $html = XmlParser::parse( $string, true );

            $dataRefEndMap = new ArrayList();

            foreach ( $html as $node ) {

                // 1. Replace <ph>|<sc>|<ec> tags
                $string = $this->recursiveTransformDataRefToPhTag( $node, $string );

                // 2. Replace self-closed <pc dataRefStart="xyz" /> tags
                $string = $this->recursiveReplaceSelfClosedPcTags( $node, $string );

                // 3. Build the DataRefEndMap needed by replaceClosingPcTags function
                // (needed for correct handling of </pc> closing tags)
                // make this inline with one foreach cycle
                $this->extractDataRefMapRecursively( $node, $string, $dataRefEndMap );

            }

            // 4. replace pc tags
            $string = $this->replaceOpeningPcTags( $string );
            $string = $this->replaceClosingPcTags( $string, $dataRefEndMap );

        } catch ( Exception $ignore ) {
            // if something fails here, do not throw an exception and return the original string instead
//            var_dump( $ignore );
        } finally {
            return $string;
        }

    }

    /**
     * @param string $string
     *
     * @return bool
     */
    private function hasAnyDataRefAttribute( string $string ): bool {
        return (bool)preg_match( '/(dataRef|dataRefStart|dataRefEnd)=[\'"].*?[\'"]/', $string );
    }

    /**
     * This function adds equiv-text attribute to <ph>, <ec>, and <sc> tags.
     *
     * Please note that <ec> and <sc> tags are converted to <ph> tags (needed by Matecat);
     * in this case, another special attribute (dataType) is added just before equiv-text
     *
     * If there is no id tag, it will be copied from the dataRef attribute
     *
     * @param object $node
     * @param string $string
     *
     * @return string
     */
    private function recursiveTransformDataRefToPhTag( object $node, string $string ): string {

        if ( $node->has_children ) {

            foreach ( $node->inner_html as $childNode ) {
                $string = $this->recursiveTransformDataRefToPhTag( $childNode, $string );
            }

        } else {

            // accept only those tags
            switch ( $node->tagName ) {
                case 'ph':
                    $ctype = CTypeEnum::PH_DATA_REF;
                    break;
                case 'sc':
                    $ctype = CTypeEnum::SC_DATA_REF;
                    break;
                case 'ec':
                    $ctype = CTypeEnum::EC_DATA_REF;
                    break;
                default:
                    return $string;
            }

            // if isset a value in the map, proceed with conversion, otherwise skip
            $attributesMap = Map::instance( $node->attributes );
            if ( !$this->map->get( $attributesMap->get( 'dataRef' ) ) ) {
                return $string;
            }

            $dataRefName = $node->attributes[ 'dataRef' ];   // map identifier. Eg: source1

            return $this->replaceNewTagString(
                    $node->node,
                    $attributesMap->getOrDefault( 'id', $dataRefName ),
                    $this->map->getOrDefault( $node->attributes[ 'dataRef' ], 'NULL' ),
                    $ctype,
                    $string,
                    null
            );

        }

        return $string;
    }

    /**
     * Check if values in the map are null or an empty string, in that case, convert them to NULL string
     *
     * @param array $map
     *
     * @return array
     */
    private function sanitizeMap( array $map ): array {

        foreach ( $map as $name => $value ) {
            if ( is_null( $value ) || $value === '' ) {
                $map[ $name ] = 'NULL';
            }
        }

        return $map;
    }

    /**
     * @param object $node
     * @param string $string
     *
     * @return string
     * @throws DOMException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    private function recursiveReplaceSelfClosedPcTags( object $node, string $string ): string {

        if ( $node->has_children ) {

            foreach ( $node->inner_html as $childNode ) {
                $string = $this->recursiveReplaceSelfClosedPcTags( $childNode, $string );
            }

        } elseif ( $node->tagName == 'pc' && $node->self_closed === true ) {

            $attributesMap = Map::instance( $node->attributes );

            if ( $dataRefStartValue = $this->map->get( $node->attributes[ 'dataRefStart' ] ) ) {

                $string = $this->replaceNewTagString(
                        $node->node,
                        $attributesMap->get( 'id' ),
                        $dataRefStartValue,
                        CTypeEnum::PC_SELF_CLOSE_DATA_REF,
                        $string
                );

            }

        }

        return $string;

    }

    /**
     * Extract (recursively) the dataRefEnd map from a single node
     *
     * @param object    $node
     * @param string    $completeString
     * @param ArrayList $dataRefEndMap
     */
    private function extractDataRefMapRecursively( object $node, string $completeString, ArrayList $dataRefEndMap ) {

        // we have to build the map for the closing pc tag, so get the children first
        if ( $node->has_children ) {
            foreach ( $node->inner_html as $nestedNode ) {
                $this->extractDataRefMapRecursively( $nestedNode, $completeString, $dataRefEndMap );
            }
        }

        // EXCLUDE self-closing <pc id='xx'/> by checking for `$node->self_closed === false`
        // BUT here we have an ambiguity on self-closing pc tags when the inner text node is empty, so we must use a strpos to check for those false self-closing matches.
        //
        // Remove '/>' if present, add a closing tag '></pc>' and guess the match on the original string, if it succeeds, the tag was an empty `<pc id='yy'></pc>` pair
        // EX:
        // <pc id="source5" dataRefStart="source5"/>
        //   becomes
        // <pc id="source5" dataRefStart="source5"></pc>
        //
        $isATagPairWithEmptyTextNode = strpos( $completeString, substr( $node->node, 0, -2 ) . '></pc>' ) !== false;

        if ( $node->tagName === 'pc' && ( $node->self_closed === false || $isATagPairWithEmptyTextNode ) ) {

            $attributesMap = Map::instance( $node->attributes );
            $dataRefEnd    = $attributesMap->getOrDefault( 'dataRefEnd', $attributesMap->get( 'dataRefStart' ) );

            $dataRefEndMap[] = [
                    'id'         => $attributesMap->get( 'id' ),
                    'dataRefEnd' => $dataRefEnd,
            ];

        }

    }

    /**
     * Replace opening <pc> tags with the correct reference in the $string
     *
     * @param string $string
     *
     * @return string
     * @throws DOMException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    private function replaceOpeningPcTags( string $string ): string {

        preg_match_all( '|<pc ([^>/]+?)>|iu', $string, $openingPcMatches );

        foreach ( $openingPcMatches[ 0 ] as $match ) {

            $node = XmlParser::parse( $match . '</pc>', true )[ 0 ]; // add a closing tag to not break XML integrity

            // CASE 1 - Missing `dataRefStart`
            if ( isset( $node->attributes[ 'dataRefEnd' ] ) && !isset( $node->attributes[ 'dataRefStart' ] ) ) {
                $node->attributes[ 'dataRefStart' ] = $node->attributes[ 'dataRefEnd' ];
            }

            // CASE 2 - Missing `dataRefEnd`
            if ( isset( $node->attributes[ 'dataRefStart' ] ) && !isset( $node->attributes[ 'dataRefEnd' ] ) ) {
                $node->attributes[ 'dataRefEnd' ] = $node->attributes[ 'dataRefStart' ];
            }

            if ( isset( $node->attributes[ 'dataRefStart' ] ) ) {

                $attributesMap = Map::instance( $node->attributes );
                $string        = $this->replaceNewTagString(
                        $match,
                        $attributesMap->get( 'id' ),
                        $this->map->getOrDefault( $node->attributes[ 'dataRefStart' ], 'NULL' ),
                        CTypeEnum::PC_OPEN_DATA_REF,
                        $string
                );

            }
        }

        return $string;
    }

    /**
     * Replace closing </pc> tags with the correct reference in the $string
     * thanks to $dataRefEndMap
     *
     * @param string    $string
     * @param ArrayList $dataRefEndMap
     *
     * @return string
     */
    private function replaceClosingPcTags( string $string, ArrayList $dataRefEndMap ): string {

        preg_match_all( '|</pc>|iu', $string, $closingPcMatches, PREG_OFFSET_CAPTURE );
        $delta = 0;

        foreach ( $closingPcMatches[ 0 ] as $index => $match ) {

            $offset = $match[ 1 ];
            $length = 5; // strlen of '</pc>'

            $attr = $dataRefEndMap->get( $index );
            if ( !empty( $attr ) && isset( $attr[ 'dataRefEnd' ] ) ) {

                // conversion for opening <pc> tag
                $completeTag = $this->getNewTagString(
                        '</pc>',
                        $attr[ 'id' ],
                        $this->map->getOrDefault( $attr[ 'dataRefEnd' ], 'NULL' ),
                        CTypeEnum::PC_CLOSE_DATA_REF,
                        '_2'
                );

                $realOffset = ( $delta === 0 ) ? $offset : ( $offset + $delta );
                $string     = (string)substr_replace( $string, $completeTag, $realOffset, $length );
                $delta      = $delta + strlen( $completeTag ) - $length;

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
    public function restore( string $string ): string {

        // if the map is empty return string as is
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
     * @param string $string
     *
     * @return string
     */
    private function recursiveRestoreOriginalTags( object $node, string $string ): string {

        if ( $node->has_children ) {

            foreach ( $node->inner_html as $childNode ) {
                $string = $this->recursiveRestoreOriginalTags( $childNode, $string );
            }

        } else {

            $nodeAttributesMap = Map::instance( $node->attributes );

            if ( !$nodeAttributesMap->get( 'x-orig' ) ) {
                return $string;
            }

            $cType = $nodeAttributesMap->get( 'ctype' );

            if ( CTypeEnum::isLayer2Constant( $cType ?? '' ) ) {
                return preg_replace( '/' . preg_quote( $node->node, '/' ) . '/', base64_decode( $nodeAttributesMap->get( 'x-orig' ) ), $string, 1 );
            }

        }

        return $string;

    }

    /**
     * @param string      $actualNodeString
     * @param string|null $id
     * @param string      $dataRefValue
     * @param string      $ctype
     * @param string|null $upCountIdValue
     *
     * @return string
     */
    private function getNewTagString( string $actualNodeString, ?string $id, string $dataRefValue, string $ctype, ?string $upCountIdValue = null ): string {

        $newTag = [ '<ph' ];

        if ( isset( $id ) ) {
            $newTag[] = 'id="' . $id . $upCountIdValue . '"';
        }

        $newTag[] = 'ctype="' . $ctype . '"';
        $newTag[] = 'equiv-text="base64:' . base64_encode( $dataRefValue ) . '"';
        $newTag[] = 'x-orig="' . base64_encode( $actualNodeString ) . '"';

        return implode( " ", $newTag ) . '/>';

    }

    private function replaceNewTagString( $actualNodeString, $id, $dataRefValue, $ctype, $originalString, $upCountIdValue = '_1' ) {
        return str_replace( $actualNodeString, $this->getNewTagString( $actualNodeString, $id, $dataRefValue, $ctype, $upCountIdValue ), $originalString );
    }

}