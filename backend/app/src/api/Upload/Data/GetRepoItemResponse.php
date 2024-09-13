<?php

namespace SilverStripe\api\Upload\Data;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;

class GetRepoItemResponse
{
    use SerializableTrait;

    public string $id;
    public string $title;
    public string $repoItemType;
    public array $metadata;

    public function __construct(string $id, string $title, string $repoItemType, array $metadata) {
        $this->id = $id;
        $this->title = $title;
        $this->repoItemType = $repoItemType;
        $this->metadata = $metadata;
    }
}