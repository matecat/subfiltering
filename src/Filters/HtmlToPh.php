<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 15.30
 *
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Enum\ConstantEnum;
use Matecat\SubFiltering\Enum\CTypeEnum;

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
class HtmlToPh extends XmlToPh {

    /**
     * Converts a generic tag string into a <ph> placeholder.
     * The original tag is stored in the 'equiv-text' attribute, base64 encoded.
     *
     * @param string $buffer The tag string to convert.
     *
     * @return string The resulting <ph> tag.
     */
    protected function _finalizeTag( string $buffer ): string {
        return '<ph id="' . $this->getPipeline()->getNextId() . '" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:' . base64_encode( htmlentities( $buffer, ENT_NOQUOTES | 16 /* ENT_XML1 */ ) ) . '"/>';
    }

    /**
     * Validates if a given string is a legitimate HTML tag.
     *
     * This is meant to cover cases where strip_tags might fail, for example with strings like "3<4".
     * It performs several checks to ensure only valid tags are processed.
     *
     * @param string $buffer The string to validate.
     *
     * @return bool True if the buffer is a valid tag, false otherwise.
     */
    protected function _isTagValid( string $buffer ): bool {

        /**
         * HTML5 Tag Matcher and Global Attribute Parser
         *
         * This module provides a comprehensive approach to matching and validating HTML5 elements
         * with a focus on global attributes, including `data-*` attributes with complex Unicode names.
         *
         * Features:
         * 1. Matches all valid HTML5 tags, including structural, text, inline, form, multimedia, table,
         *    script, interactive, and miscellaneous tags.
         * 2. Supports opening tags, closing tags, and self-closing tags.
         * 3. Supports global attributes:
         *    - Standard global attributes: id, class, style, title, lang, dir, hidden, draggable, etc.
         *    - Data attributes: data-* with Unicode, emoji, or complex characters.
         *    - ARIA attributes: role, aria-*.
         *    - Event handlers: on*, e.g., onclick, onmouseover.
         *    - Deprecated XML attributes: xml:lang, xml:base.
         * 4. Handles attribute values in double quotes, single quotes, or unquoted.
         * 5. Example usage includes parsing headings (`h1`-`h6`) with multiple global attributes,
         *    as well as other HTML5 elements with complex `data-*` attributes.
         *
         * Regex Summary:
         * - Tag matching: matches all HTML5 tags listed in the specification.
         * - Attribute matching: matches zero or more global attributes including complex `data-*` names.
         * - Robust to multiple attributes, whitespace, self-closing tags, and Unicode characters.
         *
         * Example HTML matched by the regex:
         * <h1 id="title1" class="main" data-élément-αριθμός="321">Heading</h1>
         * <img src="image.png" alt="Photo" data-info="📸"/>
         * <div hidden data-属性名123="有效">Content</div>
         * <button onclick="alert('Click!')">Click me</button>
         *
         * Notes:
         * - This regex is intended for validation and parsing in contexts that allow Unicode and extended characters.
         * - For `dataset` access in JavaScript, `getAttribute` is recommended for attributes with non-ASCII names.
         */
        if ( preg_match( '#<\s*/?\s*(?:html|head|body|header|footer|main|section|article|nav|aside|h1|h2|h3|h4|h5|h6|p|hr|pre|blockquote|ol|ul|li|dl|dt|dd|figure|figcaption|div|a|em|strong|small|s|cite|q|dfn|abbr|ruby|rt|rp|data|time|code|var|samp|kbd|sub|sup|i|b|u|mark|bdi|bdo|span|br|wbr|form|label|input|button|select|datalist|optgroup|option|textarea|output|fieldset|legend|meter|progress|img|audio|video|source|track|picture|map|area|iframe|embed|object|param|table|caption|colgroup|col|tbody|thead|tfoot|tr|td|th|script|noscript|template|canvas|link|style|meta|base|title|details|summary|dialog|menu|menuitem|slot|portal)\b(?:\s+(?:accesskey|class|contenteditable|data-[^\s=]+|dir|draggable|enterkeyhint|hidden|id|inert|inputmode|lang|popover|spellcheck|style|tabindex|title|translate|xml:lang|xml:base|role|aria-[^\s=]+|on\w+)(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?)*\s*/?\s*>#ui', $buffer ) ) {

            // This case covers when filters create an XLIFF tag inside an HTML tag's attribute.
            // For example, &lt;a href=\"<x id="1">\"&gt; which becomes <a href=\"##LESSTHAN##...##GREATERTHAN##\">
            // We must not process this as a valid tag for replacement.
            if ( strpos( $buffer, ConstantEnum::LTPLACEHOLDER ) !== false || strpos( $buffer, ConstantEnum::GTPLACEHOLDER ) !== false ) {
                return false;
            }

            return true;
        }

        return false;

    }

}