<?php

namespace Symbiote\GridFieldExtensions;

/**
 * Calls a callback function after reordering
 **/
class WritingGridFieldOrdereableRows extends GridFieldOrderableRows {
    var $callback = null;

    protected function reorderItems($list, array $values, array $sortedIDs) {
        parent::reorderItems($list, $values, $sortedIDs);
        if ($this->callback) {
            call_user_func($this->callback);
        }
    }


}
