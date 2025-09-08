<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 15.30
 *
 */

namespace Matecat\SubFiltering\Filters\Html;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Commons\Pipeline;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;

/**
 * HtmlParser
 *
 * A robust HTML/text parsing utility that distinguishes between plaintext, HTML, comments, and script/style segments
 * in a given input string. It processes segments statefully, validates potential HTML tags,
 * and invokes handler callbacks for fragment finalization and error correction.
 *
 * Usage:
 *      - Register a callback handler (must consume CallbacksHandler trait).
 *      - Call transform() to process a segment and convert its contents into a safe, normalized, and well-formed state.
 *
 * State Machine:
 *      - STATE_PLAINTEXT: Outside any tag, collecting plain text.
 *      - STATE_HTML:      Inside angle brackets `<...>`, potentially a tag.
 *      - STATE_COMMENT:   Inside a comment `<!-- ... -->`.
 *      - STATE_JS_CSS:    Inside <script> or <style> tags.
 *
 * Callbacks:
 *      The handler passed in registerCallbacksHandler must implement tag validation, plain text finalization,
 *      HTML tag finalization, error correction, comment/script handling, and flagging for HTML content detection.
 *
 * @author  domenico domenico@translated.net / ostico@gmail.com
 * @package Matecat\SubFiltering\Filters\Html
 *
 * @method _isTagValid( string $buffer )            Validate whether $buffer is a correct HTML tag.
 * @method _finalizeMarkupTag( string $buffer )        Handle completion of a valid HTML tag.
 * @method _fixWrongBuffer( string $buffer )         Correct and process abnormal tag-like input.
 * @method _finalizeScriptTag( string $buffer )      Finalize a <script>/<style> or comment content.
 * @method _finalizePlainText( string $plain_buffer ) Finalize plain text collected so far.
 * @method _setSegmentContainsMarkup()                Set a flag on the parent pipeline that HTML has been found.
 */
class HtmlParser {

    /** Parser states for input processing (plaintext, HTML tag, comment, or script/style). */
    const STATE_PLAINTEXT = 0;
    const STATE_HTML      = 1;
    const STATE_COMMENT   = 2;
    const STATE_JS_CSS    = 3;

    /**
     * Processing pipeline; used for HTML presence flagging.
     * @var Pipeline|null
     */
    private ?Pipeline $pipeline;

    /**
     * The handler object providing callback implementations (must use CallbacksHandler trait).
     * @var AbstractHandler
     */
    protected AbstractHandler $callbacksHandler;

    /**
     * HtmlParser constructor.
     *
     * @param Pipeline|null $pipeline
     */
    public function __construct( ?Pipeline $pipeline = null ) {
        $this->pipeline = $pipeline;
    }

    /**
     * Registers a handler for callbacks invoked during parsing.
     * The handler must use the CallbacksHandler trait (ensured at runtime).
     *
     * @param AbstractHandler $class Handler implementing required callbacks.
     *
     * @throws RuntimeException If the handler does not use the CallbacksHandler trait.
     */
    public function registerCallbacksHandler( AbstractHandler $class ) {
        //check: $class must use CallbacksHandler trait
        if ( !in_array( CallbacksHandler::class, array_merge( class_uses( $class ), class_uses( get_parent_class( $class ) ) ) ) ) {
            throw new RuntimeException( "Class passed to " . __METHOD__ . " must use " . CallbacksHandler::class . " trait." );
        }
        $this->callbacksHandler = $class;
        $this->pipeline         = $this->callbacksHandler->getPipeline();
    }

    /**
     * Magic invoker for protected/private methods on the registered callbacks handler.
     * This enables the parser to call non-public handler methods at runtime,
     * supporting encapsulation of callback logic.
     *
     * @param string   $name      Method name to invoke.
     * @param string[] $arguments Single-element arguments array for handler callback.
     *
     * @return mixed             Return value from the handler's method.
     * @throws ReflectionException If a method cannot be found/reflected.
     */
    public function __call( string $name, array $arguments = [] ) {

        // Create a ReflectionMethod instance for the method being called on the callback handler
        $reflector = new ReflectionMethod( $this->callbacksHandler, $name );

        // If the method is not public, make it accessible
        if ( !$reflector->isPublic() ) {
            $reflector->setAccessible( true );
        }

        // Invoke the method on the callback handler with the provided arguments
        return $reflector->invoke( $this->callbacksHandler, $arguments[ 0 ] ?? null );
    }

    /**
     * Parses and transforms an input string segment, differentiating between
     * plain text, HTML tags, comments, and <script>/<style> blocks.
     * Sanitizes invalid tags, finalizes detected segments via callbacks, and
     * collects a normalized string (with external handler support).
     *
     * @param string $segment The input string to parse and transform.
     *
     * @return string         The processed segment, with tags and text handled appropriately.
     */

    public function transform( string $segment ): string {
        // Split input into Unicode codepoints for accurate char-by-char iteration.
        $originalSplit = preg_split( '//u', $segment, -1, PREG_SPLIT_NO_EMPTY );
        if ( empty( $originalSplit ) ) {
            return '';
        }

        $state             = static::STATE_PLAINTEXT;
        $html_buffer       = '';
        $plain_text_buffer = '';
        $in_quote_char     = '';
        $output            = '';
        $charCount         = count( $originalSplit );

        foreach ( $originalSplit as $idx => $char ) {
            switch ( $state ) {
                case static::STATE_PLAINTEXT:
                    $this->handlePlainTextState( $char, $state, $html_buffer, $plain_text_buffer, $output );
                    break;
                case static::STATE_HTML:
                    $this->handleHtmlState( $char, $idx, $charCount, $state, $html_buffer, $plain_text_buffer, $output, $in_quote_char );
                    break;
                case static::STATE_COMMENT:
                    $this->handleCommentState( $char, $state, $html_buffer, $output );
                    break;
                case static::STATE_JS_CSS:
                    $this->handleJsCssState( $char, $state, $html_buffer, $output );
                    break;
            }
        }

        // HTML Partial at the end, treat as invalid and preserve the string content
        if ( !empty( $html_buffer ) ) {
            if ( $this->_isTagValid( $html_buffer ) && null !== $this->pipeline ) {
                $this->_setSegmentContainsMarkup();
            }
            $output .= $this->_fixWrongBuffer( $html_buffer );
        }

        // Any trailing plain text: finalize it.
        if ( '' !== $plain_text_buffer ) {
            $output .= $this->_finalizePlainText( $plain_text_buffer );
        }

        return $output;
    }

    /**
     * Handles character processing when in the STATE_PLAINTEXT.
     */
    private function handlePlainTextState( string $char, int &$state, string &$html_buffer, string &$plain_text_buffer, string &$output ): void {
        switch ( $char ) {
            case '<':
                // Potential new tag starts; finalize plain text so far.
                $state             = static::STATE_HTML;
                $html_buffer       .= $char;
                $output            .= $this->_finalizePlainText( $plain_text_buffer );
                $plain_text_buffer = '';
                break;
            case '>':
                // Unescaped '>' in plaintext; treat as literal via error handing.
                $plain_text_buffer .= $this->_fixWrongBuffer( $char );
                break;
            default:
                // Collect as plain text.
                $plain_text_buffer .= $char;
                break;
        }
    }

    /**
     * Handles character processing when in the STATE_HTML.
     */
    private function handleHtmlState( string $char, int $idx, int $charCount, int &$state, string &$html_buffer, string &$plain_text_buffer, string &$output, string &$in_quote_char ): void {
        switch ( $char ) {
            case '<':
                // If we found a second less than symbol, the first one IS NOT a tag.
                // See https://www.w3.org/TR/xml/#charsets
                $output      .= $this->_fixWrongBuffer( $html_buffer );
                $html_buffer = $char;
                break;
            case '>':
                // End of current tag. Special-case for <script> or <style> blocks.
                if ( $this->isScriptOrStyleTag( $html_buffer ) ) {
                    $html_buffer .= $char;
                    $state       = static::STATE_JS_CSS;
                    break;
                }

                $in_quote_char = '';
                $state         = static::STATE_PLAINTEXT;
                $html_buffer   .= $char;

                // Validate and finalize HTML tag. Invalid tags are corrected/errors handled.
                if ( $this->_isTagValid( $html_buffer ) ) {
                    $output .= $this->_finalizeMarkupTag( $html_buffer );
                    if ( null !== $this->pipeline ) {
                        $this->_setSegmentContainsMarkup();
                    }
                } else {
                    $output .= $this->_fixWrongBuffer( $html_buffer );
                }
                $html_buffer = '';
                break;
            case '"':
            case '\'':
                // Track entry/exit into quoted attributes.
                if ( $char == $in_quote_char ) {
                    $in_quote_char = ''; // Exiting quote
                } elseif ( $in_quote_char == '' ) {
                    $in_quote_char = $char; // Entering quote
                }
                $html_buffer .= $char;
                break;
            case '-':
                // Detect HTML comment opening ('<!--').
                if ( $html_buffer === '<!-' ) {
                    $state = static::STATE_COMMENT;
                }
                $html_buffer .= $char;
                break;
            case ' ':
            case "\n":
                // Space or newline immediately after '<' is invalid.
                if ( $html_buffer === '<' ) {
                    $state       = static::STATE_PLAINTEXT;
                    $output      .= $this->_fixWrongBuffer( '<' . $char );
                    $html_buffer = '';
                    if ( null !== $this->pipeline ) {
                        $this->_setSegmentContainsMarkup();
                    }
                    break;
                }
                $html_buffer .= $char;
                break;
            default:
                $html_buffer .= $char;
                // End of input: treat buffer as plain text if not a valid tag.
                if ( $idx === ( $charCount - 1 ) && !$this->_isTagValid( $html_buffer ) ) {
                    $state             = static::STATE_PLAINTEXT; // Error: not a valid tag
                    $plain_text_buffer .= $this->_fixWrongBuffer( $html_buffer );
                    $html_buffer       = '';
                }
                break;
        }
    }

    /**
     * Handles character processing when in the STATE_COMMENT.
     */
    private function handleCommentState( string $char, int &$state, string &$html_buffer, string &$output ): void {
        $html_buffer .= $char;
        // Check for the end of a comment: '-->'
        if ( $char === '>' && substr( $html_buffer, -3 ) === '-->' ) {
            $state       = static::STATE_PLAINTEXT;
            $output      .= $this->_finalizeScriptTag( $html_buffer );
            $html_buffer = '';
            if ( null !== $this->pipeline ) {
                $this->_setSegmentContainsMarkup();
            }
        }
    }

    /**
     * Handles character processing when in the STATE_JS_CSS.
     */
    private function handleJsCssState( string $char, int &$state, string &$html_buffer, string &$output ): void {
        $html_buffer .= $char;
        // Detect close: e.g., '</script>' or '</style>'
        if ( $char === '>' ) {
            if ( in_array( substr( $html_buffer, -6 ), [ 'cript>', 'style>' ], true ) ) {
                $state = static::STATE_PLAINTEXT;
                $this->_isTagValid( $html_buffer );
                $output      .= $this->_finalizeScriptTag( $html_buffer );
                $html_buffer = '';
                if ( null !== $this->pipeline ) {
                    $this->_setSegmentContainsMarkup();
                }
            }
        }
    }

    /**
     * Checks if the buffered HTML is the beginning of a script or style tag.
     */
    private function isScriptOrStyleTag( string $html_buffer ): bool {
        // A tag starts with '<script' or '<style'. This also covers variants with spaces or attributes.
        return in_array( substr( $html_buffer, 0, 8 ), [ '<script ', '<style', '<script', '<style ' ] );
    }
}
