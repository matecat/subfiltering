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
class MarkupToPh extends AbstractHandler {

    use CallbacksHandler;

    protected bool $isHTML = false;

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
    protected function _finalizeMarkupTag( string $buffer ): string {
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
        $isHTML       = $this->isHTML;
        $this->isHTML = false;

        return '<ph id="' . $this->getPipeline()->getNextId() . '" ctype="' . ( $isHTML ? CTypeEnum::HTML : CTypeEnum::XML ) . '" equiv-text="base64:' . base64_encode( htmlentities( $buffer, ENT_NOQUOTES | 16 /* ENT_XML1 */ ) ) . '"/>';
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
     * Validates a given tag string based on specific criteria for HTML5 and XML tags.
     *
     * The method determines whether a given tag string is valid by:
     * 1. Ensuring there are no placeholder markers (e.g., `##LESSTHAN##`, `##GREATERTHAN##`).
     * 2. Matching against a comprehensive HTML5 tag and attribute structure using regex.
     * 3. Optionally performing a stricter validation for XML tag structures.
     *
     * @param string $buffer The string representation of a tag to be validated.
     *
     * @return bool Returns true if the tag is considered valid; false otherwise.
     */
    protected function _isTagValid( string $buffer ): bool {

        // This is a safeguard against misinterpreting partially processed strings.
        // During filtering, inner tags might be replaced by placeholders (e.g., ##LESSTHAN##).
        // If such placeholders exist within what looks like a tag, it means the tag's
        // content is not yet restored, so we must not treat it as a valid, final tag.
        // For example, an original string like '&lt;a href="<x/>"&gt;' could become
        // '<a href="##LESSTHAN##x/##GREATERTHAN##">', which should not be converted to a <ph> tag.
        if ( strpos( $buffer, ConstantEnum::LTPLACEHOLDER ) !== false || strpos( $buffer, ConstantEnum::GTPLACEHOLDER ) !== false ) {
            return false;
        }

        /**
         * Validates if the given buffer contains a valid HTML5 tag.
         *
         * This method uses a regular expression to match and validate HTML5 tags, including their attributes.
         * It supports a wide range of HTML5 elements and global attributes, ensuring that the buffer adheres
         * to the HTML5 specification.
         *
         * Features:
         * - Matches all valid HTML5 tags, including opening, closing, and self-closing tags.
         * - Handles global attributes such as id, class, style, data-* attributes, ARIA attributes, and event handlers.
         * - Supports attribute values in double quotes, single quotes, or unquoted.
         * - Robust to multiple attributes, whitespace, and Unicode characters.
         *
         * Example HTML matched by the regex:
         * - `<div class="example" data-info="123">Content</div>`
         * - `<img src="image.png" alt="Image" />`
         * - `<button onclick="alert('Click!')">Click me</button>`
         *
         * @see https://regex101.com/r/o546zS/2
         *
         * @param string $buffer The string to validate as an HTML5 tag.
         *
         * @return bool Returns true if the buffer contains a valid HTML5 tag; false otherwise.
         */
        if ( preg_match( '#</?(?:a|abbr|address|area|article|aside|audio|b|base|bdi|bdo|blockquote|body|br|button|canvas|caption|cite|code|col|colgroup|data|datalist|dd|del|details|dfn|dialog|div|dl|dt|em|embed|fieldset|figcaption|figure|footer|form|h1|h2|h3|h4|h5|h6|head|header|hr|html|i|iframe|img|input|ins|kbd|label|legend|li|link|main|map|mark|menu|meta|meter|nav|noscript|object|ol|optgroup|option|output|p|param|picture|pre|progress|q|rb|rp|rt|rtc|ruby|s|samp|script|section|select|slot|small|source|span|strong|style|sub|summary|sup|table|tbody|td|template|textarea|tfoot|th|thead|time|title|tr|track|u|ul|var|video|wbr)(?:\s+[:a-z0-9\-._]+(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?)*\s*/?>#ui', $buffer ) ) {
            $this->isHTML = true;

            return true;
        }

        /**
         * Validates the general structure of an XML tag using a stricter regex.
         *
         * This validation ensures that the XML tag adheres to the following rules:
         * - The tag may optionally start with a '/' character.
         * - The tag name must NOT start with a number or a hyphen.
         * - The tag name can only contain alphanumeric characters, hyphens (-), dots (.), and underscores (_).
         * - The tag name must have at least one character.
         * - The tag must end with a letter, a digit, a single quote ('), a double quote ("), or a forward slash (/).
         * - Attributes must be defined with an equal sign and quoted values (either single or double quotes).
         *
         * Notes:
         * - Unicode letters in element and attribute names are not allowed.
         * - This validation is stricter than the HTML5 validation and is tailored for XML documents.
         * - For more details, see the XML specification: https://www.w3.org/TR/xml/#NT-Attribute
         *
         * @see https://regex101.com/r/hsk9KU/4
         *
         * @param string $buffer The string representation of the tag to validate.
         *
         * @return bool Returns true if the tag matches the stricter XML structure; false otherwise.
         */
        if ( preg_match( '#</?(?![0-9\-]+)[a-z0-9\-._:]+?(?:\s+[:a-z0-9\-._]+=(?:"[^"]*"|\'[^\']*\'))*\s*/?>#ui', $buffer ) ) {
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

        // restore < e >
        $segment = str_replace( "&lt;", "<", $segment );
        $segment = str_replace( "&gt;", ">", $segment );

        $parser = new HtmlParser();
        $parser->registerCallbacksHandler( $this );

        return $parser->transform( $segment );
    }

}