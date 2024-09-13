<?php

namespace SilverStripe\api\Upload\Data\Traits;

trait SerializableTrait {
    public function toJson() {
        return json_encode($this);
    }

    static public function fromJson($json) {
        return json_decode($json, true);
    }
}