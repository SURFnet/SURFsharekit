<?php

namespace SilverStripe\api\Upload\Data\MetaFields;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;

class MetaField
{
    use SerializableTrait;

    public string $id;
    public ?string $title;
    public ?string $type;
    public ?string $jsonType;
    public ?string $jsonKey;
    public ?string $description;
    public $exampleValue;
    public ?array $options;

}