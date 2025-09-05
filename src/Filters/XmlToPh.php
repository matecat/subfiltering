<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 15.30
 *
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;
use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\Filters\Html\CallbacksHandler;
use Matecat\SubFiltering\Filters\Html\HtmlParser;

/**
 * Class HtmlToPh
 *
 * This class converts HTML tags within a string into placeholder tags (<ph>).
 * It uses an HtmlParser with a set of callbacks to process different parts of the HTML content.
 *
 * @author  domenico domenico@translated.net / ostico@gmail.com
 * @package SubFiltering
 *
 */
class XmlToPh extends AbstractHandler {

    use CallbacksHandler;

    /**
     * Handles plain text content. Returns the buffer unchanged.
     *
     * @param string $buffer The plain text buffer.
     *
     * @return string The original buffer.
     */
    protected function _finalizePlainText( string $buffer ): string {
        return $buffer;
    }

    /**
     * Handles and finalizes an HTML tag.
     *
     * This method decodes HTML entities within the tag's attributes while preserving the '<' and '>' characters of the tag itself.
     * This is necessary to correctly handle encoded attribute values. For example, an attribute like `href="...?a=1&amp;amp;b=2"`
     * becomes `href="...?a=1&amp;b=2"`.
     *
     * @param string $buffer The HTML tag string.
     *
     * @return string The generated <ph> placeholder tag.
     */
    protected function _finalizeHTMLTag( string $buffer ): string {
        // Decode attributes by locking < and > first
        // Because a HTML tag has it's attributes encoded and here we get lt and gt decoded but not other parts of the string
        // Ex:
        // incoming string: <a href="/users/settings?test=123&amp;amp;foobar=1" target="_blank">
        // this should be: <a href="/users/settings?test=123&amp;foobar=1" target="_blank"> with only one ampersand encoding
        //
        $buffer = str_replace( [ '<', '>' ], [ '#_lt_#', '#_gt_#' ], $buffer );
        $buffer = html_entity_decode( $buffer, ENT_NOQUOTES | 16 /* ENT_XML1 */, 'UTF-8' );
        $buffer = str_replace( [ '#_lt_#', '#_gt_#' ], [ '<', '>' ], $buffer );

        return $this->_finalizeTag( $buffer );

    }

    /**
     * Converts a generic tag string into a <ph> placeholder.
     * The original tag is stored in the 'equiv-text' attribute, base64 encoded.
     *
     * @param string $buffer The tag string to convert.
     *
     * @return string The resulting <ph> tag.
     */
    protected function _finalizeTag( string $buffer ): string {
        return '<ph id="' . $this->getPipeline()->getNextId() . '" ctype="' . CTypeEnum::XML . '" equiv-text="base64:' . base64_encode( htmlentities( $buffer, ENT_NOQUOTES | 16 /* ENT_XML1 */ ) ) . '"/>';
    }

    /**
     * "Fixes" a buffer that was incorrectly identified as a tag by escaping its angle brackets.
     *
     * @param string $buffer The string buffer.
     *
     * @return string The fixed string with escaped angle brackets.
     */
    protected function _fixWrongBuffer( string $buffer ): string {
        $buffer = str_replace( "<", "&lt;", $buffer );

        return str_replace( ">", "&gt;", $buffer );
    }

    /**
     * Finalizes a <script> tag by converting it into a placeholder.
     *
     * @param string $buffer The script tag string.
     *
     * @return string The generated <ph> placeholder tag.
     */
    protected function _finalizeScriptTag( string $buffer ): string {
        return $this->_finalizeTag( $buffer );
    }

    /**
     * Validates if a given string is a legitimate XML or HTML-like tag.
     *
     * This method provides a robust way to identify tags, avoiding the common pitfalls of
     * simpler tools like `strip_tags` which can fail with strings such as "3 < 4". It uses a
     * two-step validation process:
     * 1. A regular expression checks for a valid tag structure (name, attributes, brackets).
     * 2. A check ensures the string doesn't contain internal placeholders, which would indicate
     *    it's a partially processed string and not a single, complete tag.
     *
     * @param string $buffer The string to validate.
     *
     * @return bool True if the buffer is a valid tag, false otherwise.
     */
    protected function _isTagValid( string $buffer ): bool {

        /*
         * accept tags start with:
         * - starting with / ( optional )
         * - NOT starting with a number
         * - containing [a-zA-Z0-9\-\._] at least 1
         * - ending with a letter a-zA-Z0-9 or a quote "' or /
         *
         * Not accept Unicode letters in attributes
         * @see https://regex101.com/r/fZGsUT/1
         */
        // This regex validates the general structure of an XML/HTML tag.
        // It checks for a valid tag name (not starting with a number), optional attributes
        // (with quoted or unquoted values), and correct opening/closing brackets.
        if ( preg_match( '#</?(?![0-9]+)[a-z0-9\-._:]+?(?:\s+[:a-z0-9\-._]+(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?)*\s*/?>#ui', $buffer ) ) {

            // This is a safeguard against misinterpreting partially processed strings.
            // During filtering, inner tags might be replaced by placeholders (e.g., ##LESSTHAN##).
            // If such placeholders exist within what looks like a tag, it means the tag's
            // content is not yet restored, so we must not treat it as a valid, final tag.
            // For example, an original string like '&lt;a href="<x/>"&gt;' could become
            // '<a href="##LESSTHAN##x/##GREATERTHAN##">', which should not be converted to a <ph> tag.
            if ( strpos( $buffer, ConstantEnum::LTPLACEHOLDER ) !== false || strpos( $buffer, ConstantEnum::GTPLACEHOLDER ) !== false ) {
                return false;
            }

            return true;
        }

        return false;

    }

    /**
     * Main transformation method.
     *
     * It instantiates an HtmlParser, registers this class as the callback handler,
     * and processes the input segment to convert HTML tags to placeholders.
     *
     * @param string $segment The input string segment to process.
     *
     * @return string The transformed segment.
     */
    public function transform( string $segment ): string {
        $parser = new HtmlParser();
        $parser->registerCallbacksHandler( $this );

        return $parser->transform( $segment );
    }

}