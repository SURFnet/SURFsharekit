<?php

namespace SurfSharekit\Api\Exceptions;

use InvalidArgumentException;

class UnsupportedBlueprintTypeException extends InvalidArgumentException
{
    public function __construct($type)
    {
        parent::__construct("Unsupported blueprint type: {$type}");
    }
}