<?php

namespace SilverStripe\api\Upload\Data;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;

class RepoItemStatusResponse {
    use SerializableTrait;

    public string $status;

    public function __construct(string $status) {
        $this->status = $status;
    }
}