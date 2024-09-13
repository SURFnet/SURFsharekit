<?php

namespace SilverStripe\api\Upload\Data\Channels;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;

class Channel {
    use SerializableTrait;

    public string $id;
    public string $title;
}