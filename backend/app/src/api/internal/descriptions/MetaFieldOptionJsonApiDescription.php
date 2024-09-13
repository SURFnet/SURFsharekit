<?php

use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataList;

class MetaFieldOptionJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'metaFieldOption';
    public $type_plural = 'metaFieldOptions';
    public $filterCount = 0;

    public $fieldToAttributeMap = [
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
        if (!in_array(strtolower($attribute), ['fieldkey', 'value', 'isremoved', 'parentoption'])) {
            throw new Exception('Filter on ' . $attribute . ' not supported, only filter on FieldKey supported at this point in time');
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

    protected function getSortableAttributesToColumnMap(): array {
        return [
            'characterCount' => 'LENGTH(SurfSharekit_MetaFieldOption.Label_EN)',
            'labelEN' => 'Label_EN',
            'labelNL' => 'Label_NL'
        ];
    }
}