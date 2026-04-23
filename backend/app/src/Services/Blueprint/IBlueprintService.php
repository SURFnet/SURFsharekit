<?php

namespace SilverStripe\Services\Blueprint;

use SilverStripe\ORM\DataObject;
use SurfSharekit\Api\Exceptions\UnsupportedBlueprintTypeException;

interface IBlueprintService
{

    /**
     * Creates a blueprint for a given DataObject based on its type.
     *
     * @param DataObject $dataObject
     * @return string
     *
     * @throws UnsupportedBlueprintTypeException
     */
    public function createBlueprintPreviewForDataobject($dataObject): string;
}