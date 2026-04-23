<?php

namespace SilverStripe\processors\Blueprint\jsonPreviewProcessors;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataObject;

abstract class BlueprintProcessor
{
    use Configurable;
    use Injectable;

    protected DataObject $dataObject;
    private string $type;

    public function __construct(DataObject $dataObject, string $type) {
        $this->dataObject = $dataObject;
        $this->type = $type;
    }

    protected function createBlueprintJsonResponse(array $data): string
    {
        return json_encode([
            'blueprintType' => $this->type,
            'data' => $data,
        ], JSON_PRETTY_PRINT);
    }

    abstract public function convertDataObjectToJson(): string;
}