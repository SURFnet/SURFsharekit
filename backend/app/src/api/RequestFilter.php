<?php

namespace SilverStripe\api;


use SilverStripe\ORM\DataList;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;

/** This is a copy from DataObjectJsonApiDescription */
class RequestFilter
{
    private array $filterableAttributes = [];

    public function __construct(array $filterableAttributes) {
        $this->filterableAttributes = $filterableAttributes;
    }

    private static array $filterModeMap = [
        'EQ' => '=',
        'NEQ' => '!=',
        'LIKE' => 'LIKE',
        'NOT LIKE' => 'NOT LIKE',
        'LT' => '<',
        'LE' => '<=',
        'GT' => '>',
        'GE' => '>='
    ];

    public static function filterDataList(DataList $dataList, $filter, $filterableAttributes = []): DataList {
        return (new self($filterableAttributes))->applyFilters($dataList, $filter);
    }

    public function applyFilters(DataList $dataList, $filter): DataList {
        foreach ($filter as $field => $value) {
            $dataList = $this->doFilter($dataList, $field, $value);
        }

        return $dataList;
    }

    public function doFilter(DataList $dataList, $field, $value): DataList {
        $whereFilter = $this->getFilterFunction($field);

        if (is_array($value)) {
            foreach ($value as $mode => $modeValue) {
                if (array_key_exists($mode, self::$filterModeMap)) {
                    $dataList = $whereFilter($dataList, $modeValue, self::$filterModeMap[$mode]);
                } else {
                    throw new BadRequestException(ApiErrorConstant::GA_BR_006, "$mode is an invalid filter modifier, use on of: [EQ, NEQ, LIKE, NOT LIKE, LT, LE, GT, GE]");
                }
            }
            return $dataList;
        }

        return $whereFilter($dataList, $value, self::$filterModeMap['EQ']);
    }

    public function getFilterFunction(string $field) {
        $fields = explode(',', $field);

        $filterableFields = $this->getFilterableAttributes();
        if (count($filterableFields) === 0) {
            throw new BadRequestException(ApiErrorConstant::GA_BR_006, "Filter not supported for this object type");
        }

        return function (DataList $datalist, $filterValue, $modifier) use ($fields, $filterableFields) {
            $filterAnyArray = [];
            foreach ($fields as $searchField) {
                if (isset($filterableFields[$searchField])) {
                    $columnDescription = $filterableFields[$searchField];
                    if ($modifier == '=' && $filterValue === 'NULL') {
                        $filterAnyArray[] = $columnDescription . ' IS NULL';
                    } else {
                        $filterAnyArray[$columnDescription . ' ' . $modifier . ' ?'] = $filterValue;
                    }
                } else {
                    throw new BadRequestException(ApiErrorConstant::GA_BR_006, "$searchField is not a supported filter, try filtering on one of: [" . implode(',', array_keys($filterableFields)) . ']');
                }
            }
            return $datalist->whereAny($filterAnyArray);
        };
    }

    public function getFilterableAttributes(): array {
        return $this->filterableAttributes;
    }

    public function setFilterableAttributes(array $filterableAttributes): void {
        $this->filterableAttributes = $filterableAttributes;
    }
}