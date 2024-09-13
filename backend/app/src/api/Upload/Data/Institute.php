<?php

namespace SilverStripe\api\Upload\Data;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;

class Institute {
    use SerializableTrait;

    public string $id;
    public ?string $parentId;
    public string $title;
    public string $type;

    public function __construct(string $id, ?string $parentId, string $title, string $type) {
        $this->id = $id;
        $this->parentId = $parentId;
        $this->title = $title;
        $this->type = $type;
    }
}