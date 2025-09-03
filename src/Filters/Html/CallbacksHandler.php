<?php
/**
 * Created by PhpStorm.
 * @author ostico domenico@translated.net / ostico@gmail.com
 * Date: 14/01/20
 * Time: 18:28
 *
 */

namespace Matecat\SubFiltering\Filters\Html;

trait CallbacksHandler {

    abstract protected function _finalizeHTMLTag( string $buffer ): string;

    abstract protected function _fixWrongBuffer( string $buffer ): string;

    abstract protected function _isTagValid( string $buffer ): string;

    abstract protected function _finalizePlainText( string $buffer ): string;

    abstract protected function _finalizeScriptTag( string $buffer ): string;

    protected function _setSegmentContainsHtml(): bool {
        $this->pipeline->setSegmentContainsHtml();
    }

}