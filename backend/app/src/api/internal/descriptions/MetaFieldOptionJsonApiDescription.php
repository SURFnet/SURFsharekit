<?php

use SilverStripe\Assets\Image;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;
use SurfSharekit\Models\SimpleCacheItem;

class MetaFieldOptionJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'metaFieldOption';
    public $type_plural = 'metaFieldOptions';
    public $filterCount = 0;

    public $fieldToAttributeMap = [
        'Identifier' => 'id',
        'Value' => 'value',
        'Label_EN' => 'labelEN',
        'Label_NL' => 'labelNL',
        'Description_EN' => 'descriptionEN',
        'Description_NL' => 'descriptionNL',
        'FieldKey' => 'fieldKey',
        'IsRemoved' => 'isRemoved',
        'MetaFieldOptionUuid' => 'parentOption',
        'CoalescedLabel_EN' => 'coalescedLabelEN',
        'CoalescedLabel_NL' => 'coalescedLabelNL',
        'RootNode' => 'rootNode',
        'HasChildren' => 'hasChildren',
        'Icon' => 'icon',
        'MetaFieldOptionCategory' => 'metafieldOptionCategory'
    ];

    public $hasOneToRelationMap = [
    ];

    public $hasManyToRelationsMap = [
    ];

    public function applyFilter(DataList $objectsToDescribe, $attribute, $value): DataList {
        $this->filterCount++;
        //field
        $randomTempTableName = $this->filterCount;
        if (!in_array(strtolower($attribute), ['fieldkey', 'value', 'isremoved', 'parentoption', 'includechildren', 'includerootnode'])) {
            throw new Exception('Filter on ' . $attribute . ' not supported, only filter on FieldKey supported at this point in time');
        }

        if (strtolower($attribute) == 'includechildren') {
            return $objectsToDescribe;
        }

        $joinedQuery = $objectsToDescribe
            //join answers
            ->leftJoin('SurfSharekit_MetaField', "${randomTempTableName}SurfSharekit_MetaField.ID = SurfSharekit_MetaFieldOption.MetaFieldID", "${randomTempTableName}SurfSharekit_MetaField");

        $whereFunction = function (DataList $datalist, $modeValue, $modifier) use ($attribute, $randomTempTableName) {
            if (strtolower($attribute) == 'fieldkey') {
                return $datalist->where(["${randomTempTableName}SurfSharekit_MetaField.Uuid $modifier ?" => $modeValue]);
            } else if (strtolower($attribute) == 'value') {
                return $datalist->whereAny(["SurfSharekit_MetaFieldOption.Label_EN $modifier ?" => $modeValue, "SurfSharekit_MetaFieldOption.Label_NL $modifier ?" => $modeValue]);
            } else if (strtolower($attribute) == 'isremoved') {
                return $datalist->where(["SurfSharekit_MetaFieldOption.IsRemoved $modifier ?" => $modeValue]);
            } else {
                if(strtolower($modeValue) == 'null'){
                    if($modifier == '='){
                        $modifier = 'IS';
                    } else if ($modifier == '!='){
                        $modifier = 'IS NOT';
                    } else {
                        throw new Exception('filter not supported');
                    }
                    return $datalist->where(["SurfSharekit_MetaFieldOption.MetaFieldOptionUuid $modifier NULL" ]);
                }
                return $datalist->where(["SurfSharekit_MetaFieldOption.MetaFieldOptionUuid $modifier ?" => $modeValue]);
            }
        };

        $modeMap = [
            'EQ' => '=',
            'NEQ' => '!=',
            'LIKE' => 'LIKE',
            'LIKE BINARY' => 'LIKE BINARY',
            'NOT LIKE' => 'NOT LIKE',
            'LT' => '<',
            'LE' => '<=',
            'GT' => '>',
            'GE' => '>='
        ];

        if (is_array($value)) {
            foreach ($value as $mode => $modeValue) {
                if (isset($modeMap[$mode])) {
                    $joinedQuery = $whereFunction($joinedQuery, $modeValue, $modeMap[$mode]);
                } else {
                    throw new Exception("$mode is an invalid filter modifier, use on of: [EQ, NEQ, LIKE, LIKE BINARY, NOT LIKE, LT, LE, GT, GE]");
                }
            }

            return $joinedQuery;
        }
        return $whereFunction($joinedQuery, $value, $modeMap['EQ']);
    }

    /**
     * @param DataObject $dataObject
     * @return array
     * Method to loop through all @see DataObjectJsonApiDescription::$fieldToAttributeMap to describe fields of a single dataobject to JsonApi attributes
     */
    public function describeAttributesOfDataObject(ViewableData $dataObject) {
        $params = Controller::curr()->getRequest()->requestVars();

        $attributes = [];
        foreach ($this->fieldToAttributeMap as $field => $attribute) {
            $isCachable = false;
            if (property_exists($dataObject, 'jsonApiCachableAttributes') && $jsonApiCachableAttributes = $dataObject::$jsonApiCachableAttributes) {
                if (in_array($field, $jsonApiCachableAttributes)) {
                    $isCachable = true;
                }
            }

            if ($isCachable) {
                $SimpleCacheItem = SimpleCacheItem::get()->filter(['DataObjectID' => $dataObject->ID, 'Key' => $field])->first();
                if ($SimpleCacheItem && $SimpleCacheItem->exists()) {
                    $attributes[$attribute] = $SimpleCacheItem->Value;
                    continue;
                }
            }

            if (is_int($field)) {
                $attributes[$attribute] = $this->describeAttribute($dataObject, $attribute);
            } else {
                $attributes[$attribute] = $dataObject->$field;
            }

            if ($isCachable) {
                SimpleCacheItem::cacheFor($dataObject, $field, $attributes[$attribute]);
            }
        }

        // recurse if includeChildren is set to 1
        $filters = $params["filter"] ?? null;
        $includeChildren = $filters["includeChildren"] ?? null;
        $attributes["children"] = [];
        $sortField = $params["sort"] ?? null;

        if ($includeChildren) {
            $metaFieldOptions = $dataObject->MetaFieldOptions();

            if ($sortField) {
                $metaFieldOptions = $this->sortMetaFieldOptionsRecursively($metaFieldOptions, $sortField);
            }

            foreach ($metaFieldOptions as $metaFieldOption) {
                $attributes['children'][] = $this->describeAttributesOfDataObject($metaFieldOption);
            }
        }

        return $attributes;
    }

    private function sortMetaFieldOptionsRecursively($metaFieldOptions, $sortField) {
        $sortFieldMap = [
            'labelEN' => 'Label_EN',
            'labelNL' => 'Label_NL'
        ];

        $mappedSortField = $sortFieldMap[$sortField] ?? $sortField;
        $sortedOptions = $metaFieldOptions->sort($mappedSortField);

        foreach ($sortedOptions as $option) {
            $children = $option->MetaFieldOptions();
            if ($children->exists() && $children->count() > 0) {
                $sortedChildren = $this->sortMetaFieldOptionsRecursively($children, $sortField);
                $option->MetaFieldOptions = $sortedChildren;
            }
        }

        return $sortedOptions;
    }

    protected function getSortableAttributesToColumnMap(): array {
        return [
            'characterCount' => 'LENGTH(SurfSharekit_MetaFieldOption.Label_EN)',
            'labelEN' => 'Label_EN',
            'labelNL' => 'Label_NL'
        ];
    }
}