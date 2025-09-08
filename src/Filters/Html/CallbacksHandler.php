<?php
/**
 * Created by PhpStorm.
 * @author ostico domenico@translated.net / ostico@gmail.com
 * Date: 14/01/20
 * Time: 18:28
 *
 */

namespace Matecat\SubFiltering\Filters\Html;

use Matecat\SubFiltering\Commons\Pipeline;

/**
 * Trait CallbacksHandler
 *
 * Defines the abstract contract for a handler that processes the output of the HtmlParser.
 * A class using this trait must implement the defined abstract methods. These methods
 * are invoked by `HtmlParser` as it identifies different components of an input string,
 * such as valid tags, plain text, invalid markup, and special blocks like scripts or comments.
 *
 * This decouples the parsing logic of `HtmlParser` from the specific actions performed
 * on the parsed segments, allowing for flexible and interchangeable handling logic.
 *
 * @package Matecat\SubFiltering\Filters\Html
 */
trait CallbacksHandler {

    /**
     * @var Pipeline
     */
    protected Pipeline $pipeline;

    /**
     * Processes a buffer that has been identified as a valid and complete HTML tag.
     * This method is called by `HtmlParser` when it successfully parses a tag.
     *
     * @param string $buffer The complete HTML tag string (e.g., "<p>", "</div>").
     * @return string The processed result that should replace the tag in the output.
     */
    abstract protected function _finalizeMarkupTag( string $buffer ): string;

    /**
     * Handles buffers that are determined to be invalid or malformed markup.
     * This is the callback for error handling, for instance, when an unclosed tag is found
     * at the end of the string or an unexpected character appears within a tag.
     *
     * @param string $buffer The invalid or incomplete tag-like string.
     * @return string The processed, safe representation of the buffer.
     */
    abstract protected function _fixWrongBuffer( string $buffer ): string;

    /**
     * Validates if a given buffer constitutes a well-formed HTML tag.
     * `HtmlParser` uses this to decide whether to call `_finalizeHTMLTag` or `_fixWrongBuffer`.
     *
     * @param string $buffer The buffer to be validated.
     * @return string A truthy value if the tag is valid, falsy otherwise.
     *                (Note: The return type is string for legacy reasons but is treated as a boolean).
     */
    abstract protected function _isTagValid( string $buffer ): string;

    /**
     * Processes a buffer containing plain text found between or outside of HTML tags.
     *
     * @param string $buffer The segment of plain text.
     * @return string The processed representation of the text.
     */
    abstract protected function _finalizePlainText( string $buffer ): string;

    /**
     * Processes the entire content of special blocks, such as HTML comments (`<!-- ... -->`),
     * scripts (`<script>...</script>`), or styles (`<style>...</style>`).
     *
     * @param string $buffer The full content of the script, style, or comment block.
     * @return string The processed representation of the block.
     */
    abstract protected function _finalizeScriptTag( string $buffer ): string;

    /**
     * Signals to the processing pipeline that the current segment contains HTML markup.
     * This method assumes the class using this trait has a `pipeline` property
     * which is an instance of `Matecat\SubFiltering\Commons\Pipeline`.
     */
    protected function _setSegmentContainsMarkup() {
        $this->pipeline->_setSegmentContainsMarkup();
    }

}