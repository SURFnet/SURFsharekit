<?php

namespace SurfSharekit\Tasks;

use Exception;
use MetaFieldOptionsSyncer;
use SilverStripe\Dev\BuildTask;
use stdClass;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\MetaFieldOption;
use SurfSharekit\Models\NestingUtil;

class GetMetafieldOptionsFromJsonTask extends BuildTask {
    protected $title = 'Get metafield options from json url';
    protected $description = 'This task is responsible for converting the json data into metafield options';

    public function run($request) {
        try {
            $this->synchronizeMetaFieldOptionTrees();
            $this->synchronizeMetaFieldOptionSubTrees();
        } catch (Exception $e) {
            Logger::errorLog("An error occurred during MetaFieldOption synchronization: " . $e->getMessage());
        }
    }

    private function synchronizeMetaFieldOptionTrees() {
        $metaFields = MetaField::get()->filter(['JsonUrl:not' => [null, '']]);
        foreach ($metaFields as $metaField) {
            $jsonUrl = $metaField->JsonUrl;
            if (!empty($jsonUrl)) {
                $jsonData = file_get_contents($jsonUrl);
                $decodedJson = json_decode($jsonData, true);
                $graphArray = $decodedJson['@graph'];

                $rootNodeIds = $this->getRootNodeIds($graphArray, $metaField->JsonKey);

                $rootNodes = [];
                foreach ($graphArray as $element ) {
                    $elementId = $element['@id'];
                    $elementIsRootNode = in_array($elementId, $rootNodeIds);
                    if($elementIsRootNode) {
                        $rootNodes[] = $element;
                    }
                }

                foreach($rootNodes as $key => $rootNode){
                    $metaFieldOptionsTree = NestingUtil::nest($graphArray, '@id', 'skos:broader', 'metaFieldOptions', $rootNode['@id']);
                    $rootNode['metaFieldOptions'] = $metaFieldOptionsTree;
                    $syncer = new MetaFieldOptionsSyncer($rootNode, $metaField);
                    $syncer->run();
                }
            }
        }
    }

    private function synchronizeMetaFieldOptionSubTrees() {
        $metaFieldOptions = MetaFieldOption::get()->filter(['MetaFieldOptionSourceUrl:not' => [null, '']]);
        foreach ($metaFieldOptions as $metaFieldOption) {
            $sourceUrl = $metaFieldOption->MetaFieldOptionSourceUrl;
            $metaField = $metaFieldOption->MetaField();

            if (!empty($sourceUrl)) {
                $jsonData = file_get_contents($sourceUrl);
                $decodedJson = json_decode($jsonData, true);
                $graphArray = $decodedJson['@graph'];

                $rootNodeIds = $this->getRootNodeIds($graphArray, $metaField->JsonKey);

                $rootNodes = [];
                foreach ($graphArray as $element) {
                    $elementId = $element['@id'];
                    $elementIsRootNode = in_array($elementId, $rootNodeIds);
                    if($elementIsRootNode) {
                        $rootNodes[] = $element;
                    }
                }

                foreach($rootNodes as $key => $rootNode){
                    $metaFieldOptionsTree = NestingUtil::nest($graphArray, '@id', 'skos:broader', 'metaFieldOptions', $rootNode['@id']);
                    $rootNode['metaFieldOptions'] = $metaFieldOptionsTree;
                    $syncer = new MetaFieldOptionsSyncer($rootNode, $metaField, $metaFieldOption);
                    $syncer->run();
                }
            }
        }
    }

    function getRootNodeIds($graphArray, $metafieldJsonKey) {
        $rootNodeIds = [];

        if (!strpos($metafieldJsonKey, 'vocabulary')) {
            foreach ($graphArray as $element) {
                if (!isset($element['skos:broader'])) {
                    $rootNodeIds[] = $element['@id'];
                }
            }
        } else {
            foreach ($graphArray as $element) {
                if (array_key_exists('skos:hasTopConcept', $element)) {
                    foreach($element['skos:hasTopConcept'] as $topConcept){
                        $rootNodeIds[] = $topConcept['@id'];
                    }
                }
            }
        }

        return $rootNodeIds;
    }

}