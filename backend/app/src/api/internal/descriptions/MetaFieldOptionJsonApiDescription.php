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

    private array $treeContextByMetaFieldId = [];

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
            ->leftJoin('SurfSharekit_MetaField', "{$randomTempTableName}SurfSharekit_MetaField.ID = SurfSharekit_MetaFieldOption.MetaFieldID", "{$randomTempTableName}SurfSharekit_MetaField");

        $whereFunction = function (DataList $datalist, $modeValue, $modifier) use ($attribute, $randomTempTableName) {
            if (strtolower($attribute) == 'fieldkey') {
                return $datalist->where(["{$randomTempTableName}SurfSharekit_MetaField.Uuid $modifier ?" => $modeValue]);
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

        $filters = $params["filter"] ?? null;
        $includeChildren = $this->isTruthyFilter($filters["includeChildren"] ?? null);
        $sortField = $params["sort"] ?? null;
        $treeContext = null;
        if ($includeChildren) {
            $treeContext = $this->getTreeContext($dataObject, $filters, $sortField);
            $uuid = (string)$dataObject->Uuid;
            if (isset($treeContext['nodesByUuid'][$uuid])) {
                return $this->describeNodeFromTreeContext($uuid, $treeContext, true);
            }
        }

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

        $attributes["children"] = [];

        if ($includeChildren) {
            $attributes['children'] = [];
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

    private function isTruthyFilter($value): bool {
        if (is_array($value)) {
            foreach (['EQ', 'eq', 'value'] as $key) {
                if (array_key_exists($key, $value)) {
                    return $this->isTruthyFilter($value[$key]);
                }
            }
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (!is_string($value)) {
            return false;
        }

        $normalized = strtolower(trim($value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function getTreeContext(ViewableData $dataObject, ?array $filters, ?string $sortField): array {
        $metaFieldId = (int)$dataObject->MetaFieldID;
        if (isset($this->treeContextByMetaFieldId[$metaFieldId])) {
            return $this->treeContextByMetaFieldId[$metaFieldId];
        }

        $isRemovedFilter = $this->extractIsRemovedFilterValue($filters);
        $metaField = $dataObject->MetaField();
        $metaFieldUuid = (string)$metaField->Uuid;

        $allOptions = $metaField->MetaFieldOptions();
        if ($isRemovedFilter !== null) {
            $allOptions = $allOptions->filter(["IsRemoved" => $isRemovedFilter]);
        }

        $nodesByUuid = [];
        $childrenByParentUuid = [];
        $parentByUuid = [];

        foreach ($allOptions as $option) {
            $uuid = (string)$option->Uuid;
            $parentUuid = $option->MetaFieldOptionUuid ? (string)$option->MetaFieldOptionUuid : null;
            $parentKey = $this->getParentMapKey($parentUuid);

            $nodesByUuid[$uuid] = [
                'Identifier' => $uuid,
                'Value' => $option->Value,
                'Label_EN' => $option->Label_EN,
                'Label_NL' => $option->Label_NL,
                'Description_EN' => $option->Description_EN,
                'Description_NL' => $option->Description_NL,
                'FieldKey' => $metaFieldUuid,
                'IsRemoved' => (int)$option->IsRemoved,
                'MetaFieldOptionUuid' => $parentUuid,
                'Icon' => $option->Icon,
                'MetaFieldOptionCategory' => $option->MetaFieldOptionCategoryID ?: null
            ];
            $parentByUuid[$uuid] = $parentUuid;
            if (!array_key_exists($parentKey, $childrenByParentUuid)) {
                $childrenByParentUuid[$parentKey] = [];
            }
            $childrenByParentUuid[$parentKey][] = $uuid;
        }

        $this->sortChildrenMap($childrenByParentUuid, $nodesByUuid, $sortField);

        $hasChildrenByUuid = [];
        foreach ($childrenByParentUuid as $parentUuidKey => $children) {
            if ($parentUuidKey === $this->getParentMapKey(null)) {
                continue;
            }
            $hasChildrenByUuid[$parentUuidKey] = count($children) > 0;
        }

        $rootNodeByUuid = [];
        $coalescedLabelENByUuid = [];
        $coalescedLabelNLByUuid = [];
        foreach ($nodesByUuid as $uuid => $_node) {
            $this->computeNodePathAttributes($uuid, $nodesByUuid, $parentByUuid, $rootNodeByUuid, $coalescedLabelENByUuid, $coalescedLabelNLByUuid);
        }

        $context = [
            'nodesByUuid' => $nodesByUuid,
            'childrenByParentUuid' => $childrenByParentUuid,
            'hasChildrenByUuid' => $hasChildrenByUuid,
            'rootNodeByUuid' => $rootNodeByUuid,
            'coalescedLabelENByUuid' => $coalescedLabelENByUuid,
            'coalescedLabelNLByUuid' => $coalescedLabelNLByUuid
        ];
        $this->treeContextByMetaFieldId[$metaFieldId] = $context;
        return $context;
    }

    private function extractIsRemovedFilterValue(?array $filters): ?int {
        if (!$filters || !array_key_exists('isRemoved', $filters)) {
            return null;
        }

        $isRemoved = $filters['isRemoved'];
        if (is_array($isRemoved)) {
            if (!array_key_exists('EQ', $isRemoved)) {
                return null;
            }
            $isRemoved = $isRemoved['EQ'];
        }

        if ($isRemoved === null || $isRemoved === '') {
            return null;
        }

        return (int)$isRemoved;
    }

    private function computeNodePathAttributes(
        string $uuid,
        array $nodesByUuid,
        array $parentByUuid,
        array &$rootNodeByUuid,
        array &$coalescedLabelENByUuid,
        array &$coalescedLabelNLByUuid
    ): void {
        if (array_key_exists($uuid, $rootNodeByUuid)) {
            return;
        }

        $node = $nodesByUuid[$uuid] ?? null;
        if (!$node) {
            return;
        }

        $parentUuid = $parentByUuid[$uuid] ?? null;
        if (!$parentUuid || !array_key_exists($parentUuid, $nodesByUuid)) {
            $rootNodeByUuid[$uuid] = $uuid;
            $coalescedLabelENByUuid[$uuid] = (string)($node['Label_EN'] ?? '');
            $coalescedLabelNLByUuid[$uuid] = (string)($node['Label_NL'] ?? '');
            return;
        }

        $this->computeNodePathAttributes($parentUuid, $nodesByUuid, $parentByUuid, $rootNodeByUuid, $coalescedLabelENByUuid, $coalescedLabelNLByUuid);

        $rootNodeByUuid[$uuid] = $rootNodeByUuid[$parentUuid] ?? $uuid;
        $parentLabelEN = $coalescedLabelENByUuid[$parentUuid] ?? '';
        $parentLabelNL = $coalescedLabelNLByUuid[$parentUuid] ?? '';
        $nodeLabelEN = (string)($node['Label_EN'] ?? '');
        $nodeLabelNL = (string)($node['Label_NL'] ?? '');
        $coalescedLabelENByUuid[$uuid] = $parentLabelEN ? ($parentLabelEN . ' - ' . $nodeLabelEN) : $nodeLabelEN;
        $coalescedLabelNLByUuid[$uuid] = $parentLabelNL ? ($parentLabelNL . ' - ' . $nodeLabelNL) : $nodeLabelNL;
    }

    private function describeNodeFromTreeContext(string $uuid, array $treeContext, bool $includeChildren): array {
        $nodeData = $treeContext['nodesByUuid'][$uuid] ?? null;
        if (!$nodeData) {
            return ['children' => []];
        }

        $attributes = [];
        foreach ($this->fieldToAttributeMap as $field => $attribute) {
            if (is_int($field)) {
                $attributes[$attribute] = null;
                continue;
            }

            if ($field === 'HasChildren') {
                $attributes[$attribute] = $treeContext['hasChildrenByUuid'][$uuid] ?? false;
                continue;
            }

            if ($field === 'RootNode') {
                $attributes[$attribute] = $treeContext['rootNodeByUuid'][$uuid] ?? $uuid;
                continue;
            }

            if ($field === 'CoalescedLabel_EN') {
                $attributes[$attribute] = $treeContext['coalescedLabelENByUuid'][$uuid] ?? ($nodeData['Label_EN'] ?? null);
                continue;
            }

            if ($field === 'CoalescedLabel_NL') {
                $attributes[$attribute] = $treeContext['coalescedLabelNLByUuid'][$uuid] ?? ($nodeData['Label_NL'] ?? null);
                continue;
            }

            $attributes[$attribute] = $nodeData[$field] ?? null;
        }

        $attributes["children"] = [];
        if (!$includeChildren) {
            return $attributes;
        }

        $children = $treeContext['childrenByParentUuid'][$this->getParentMapKey($uuid)] ?? [];
        foreach ($children as $childUuid) {
            if (!isset($treeContext['nodesByUuid'][$childUuid])) {
                continue;
            }
            $attributes['children'][] = $this->describeNodeFromTreeContext($childUuid, $treeContext, true);
        }

        return $attributes;
    }

    private function sortChildrenMap(array &$childrenByParentUuid, array $nodesByUuid, ?string $sortField): void {
        if (!$sortField) {
            return;
        }

        $sortDirection = substr($sortField, 0, 1) === '-' ? 'DESC' : 'ASC';
        $sortField = ltrim($sortField, '-');
        $sortFieldMap = [
            'labelEN' => 'Label_EN',
            'labelNL' => 'Label_NL'
        ];
        $mappedSortField = $sortFieldMap[$sortField] ?? $sortField;

        foreach ($childrenByParentUuid as $parentUuid => $siblings) {
            usort($siblings, function ($a, $b) use ($mappedSortField, $sortDirection, $nodesByUuid) {
                $leftValue = (string)($nodesByUuid[$a][$mappedSortField] ?? '');
                $rightValue = (string)($nodesByUuid[$b][$mappedSortField] ?? '');
                $comparison = strcmp($leftValue, $rightValue);
                return $sortDirection === 'DESC' ? -$comparison : $comparison;
            });
            $childrenByParentUuid[$parentUuid] = $siblings;
        }
    }

    private function getParentMapKey(?string $parentUuid): string {
        return $parentUuid === null ? '__root__' : $parentUuid;
    }

    protected function getSortableAttributesToColumnMap(): array {
        return [
            'characterCount' => 'LENGTH(SurfSharekit_MetaFieldOption.Label_EN)',
            'labelEN' => 'Label_EN',
            'labelNL' => 'Label_NL',
            'sortOrder' => 'SortOrder'
        ];
    }
}