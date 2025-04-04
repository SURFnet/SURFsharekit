<?php

namespace SilverStripe\api\Upload\Data;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;

class CreateRepoItemResponse {
    use SerializableTrait;

    public string $id;

    public function __construct(string $id) {
        $this->id = $id;
    }
}