<?php

namespace SilverStripe\api\Upload\Processors;

use SilverStripe\api\ApiValidationResult;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use stdClass;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;

abstract class MetaFieldProcessor {
    use Configurable;
    use Injectable;

    private RepoItem $repoItem;
    private MetaField $metaField;
    private $value;

    public function __construct(RepoItem $repoItem, MetaField $metaField, $value = null){
        $this->metaField = $metaField;
        $this->value = $value;
        $this->repoItem = $repoItem;
    }

    /**
     * Validates the value of the value property {@see MetaFieldProcessor::$value}
     * This function is always executed before save is run {@see MetaFieldProcessor::save()}
     *
     * @return ApiValidationResult
     */
    public function validate(): ApiValidationResult {
        $validationResult = ApiValidationResult::create();

        $this->validateValueType($validationResult);

        return $validationResult;
    }

    /**
     * This function should contain the logic to correctly store the RepoItemMetaFieldValue(s) that should be
     * linked to the $repoItemMetaField parameter
     * @param RepoItemMetaField $repoItemMetaField
     * @return void
     */
    abstract public function save(RepoItemMetaField $repoItemMetaField): void;

    abstract public function convertValueToJson(RepoItemMetaField $repoItemMetaField);

    public function validateAndSave(RepoItemMetaField $repoItemMetaField) {
        $validationResult = $this->validate();
        if ($validationResult->hasErrors()) {
            throw new BadRequestException(ApiErrorConstant::GA_BR_001, $validationResult->getErrors()[0]);
        }
        $this->save($repoItemMetaField);
    }

    /**
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @return MetaField
     */
    public function getMetaField(): MetaField {
        return $this->metaField;
    }

    public function getRepoItem(): RepoItem {
        return $this->repoItem;
    }

    public static function resolveMetaFieldProcessorClassByMetaFieldType(string $type): string {
        $processorClass = null;

        foreach (ClassInfo::subclassesFor(MetaFieldProcessor::class, false) as $class) {
            if (Config::forClass($class)->get('type') === $type) {
                $processorClass = $class;
                break;
            }
        }

        if ($processorClass == null) {
            throw new \Exception("Meta field processor not found for type $type");
        }

        return $processorClass;
    }

    private function validateValueType(ApiValidationResult &$validationResult) {
        $metaField = $this->getMetaField();
        $jsonType = $metaField->JsonType;
        $jsonKey = $metaField->JsonKey;

        switch ($jsonType) {
            case null: {
                $validationResult->throwError("MetaField $jsonKey does not have a JSON type set");
                break;
            }
            case "String": {
                if (!is_string($this->getValue())) {
                    $validationResult->throwError("The value of MetaField $jsonKey should be a string");
                }
                break;
            }
            case "StringArray": {
                if (!is_array($this->getValue())) {
                    $validationResult->throwError("The value of MetaField $jsonKey should be an array of strings");
                }

                foreach ($this->getValue() as $value) {
                    if (!is_string($value)) {
                        $validationResult->throwError("The value of MetaField $jsonKey should be an array of strings");
                    }
                }
                break;
            }
            case "Object": {
                if ($this->getValue() instanceof stdClass) {
                    $validationResult->throwError("The value of MetaField $jsonKey should be a JSON object");
                }
                break;
            }
            case "ObjectArray": {
                if (!is_array($this->getValue())) {
                    $validationResult->throwError("The value of MetaField $jsonKey should be a JSON Array of JSON objects");
                }

                foreach ($this->getValue() as $value) {
                    if (!($value instanceof stdClass) && !is_array($value)) {
                        $validationResult->throwError("The value of MetaField $jsonKey should be a JSON Array of JSON objects");
                    }
                }
                break;
            }
            case "Number": {
                if (!is_numeric($this->getValue())) {
                    $validationResult->throwError("The value of MetaField $jsonKey should be a JSON Number");
                }
                break;
            }
            case "NumberArray": {
                if (!is_array($this->getValue())) {
                    $validationResult->throwError("The value of MetaField $jsonKey should be a JSON Array of JSON Numbers");
                }

                foreach ($this->getValue() as $value) {
                    if (!is_numeric($value)) {
                        $validationResult->throwError("The value of MetaField $jsonKey should be a JSON Array of JSON Numbers");
                    }
                }
                break;
            }
            case "Boolean": {
                if (!is_bool($this->getValue())) {
                    $validationResult->throwError("The value of MetaField $jsonKey should be a JSON Boolean");
                }
                break;
            }
            case "BooleanArray": {
                if (!is_bool($this->getValue())) {
                    $validationResult->throwError("The value of MetaField $jsonKey should be a JSON Array of JSON Booleans");
                }

                foreach ($this->getValue() as $value) {
                    if (!is_bool($value)) {
                        $validationResult->throwError("The value of MetaField $jsonKey should be a JSON Array of JSON Booleans");
                    }
                }
                break;
            }
        }
    }
}