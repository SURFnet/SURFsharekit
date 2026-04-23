<?php

namespace SilverStripe\Services\Blueprint;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\processors\Blueprint\jsonPreviewProcessors\BlueprintInstituteProcessor;
use SilverStripe\processors\Blueprint\jsonPreviewProcessors\BlueprintPersonProcessor;
use SilverStripe\processors\Blueprint\jsonPreviewProcessors\BlueprintRepoItemFileProcessor;
use SilverStripe\processors\Blueprint\jsonPreviewProcessors\BlueprintRepoItemProcessor;
use SurfSharekit\Api\Exceptions\UnsupportedBlueprintTypeException;

/**
 * Service to generate blueprints for the designated dataobject
 */
class BlueprintService implements IBlueprintService
{
    use Injectable;
    use Configurable;

    protected $processorMap = [
        'Institute' => BlueprintInstituteProcessor::class,
        'Person' => BlueprintPersonProcessor::class,
        'RepoItem' => BlueprintRepoItemProcessor::class,
        'RepoItemFile' => BlueprintRepoItemFileProcessor::class
    ];

    public function createBlueprintPreviewForDataobject($dataObject): string
    {
        $dataObjectType = preg_replace('/.*\\\/', '', $dataObject->ClassName);

        if (!isset($this->processorMap[$dataObjectType])) {
            throw new UnsupportedBlueprintTypeException($dataObjectType);
        }

        $processorClass = $this->processorMap[$dataObjectType];
        $processor = $processorClass::create($dataObject, $dataObjectType);

        return $processor->convertDataObjectToJson($dataObject, $dataObjectType);
    }
}