<?php

use Ramsey\Uuid\Uuid;
use SilverStripe\ORM\DataList;

class InstituteReportJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'instituteReport';
    public $type_plural = 'instituteReports';

    //GET information
    public $fieldToAttributeMap = [
        'RepoItemsSummary' => 'repoItems'
    ];

    public function getFilterableAttributesToColumnMap(): array {
        return ['id' => null];
    }

    public function getFilterFunction(array $fieldsToSearchIn) {
        if (in_array('id', $fieldsToSearchIn)) {
            if (count($fieldsToSearchIn) > 1) {
                throw new Exception('Cannot mix ID filter with another filter');
            }
            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=' || $modifier == '!=')) {
                    throw new Exception('Only ?filter[id][EQ] or ...[NEQ] supported');
                }
                $filterValues = explode(',', $filterValue);
                $filters = [];
                foreach ($filterValues as $fv) {
                    if (Uuid::isValid($fv)) {
                        $filters[] = ["SurfSharekit_Institute.Uuid $modifier '$fv'"];
                    } else {
                        throw new Exception('Invalid ID, use UUID4');
                    }
                }
                return $datalist->whereAny($filters);
            };
        }
        return parent::getFilterFunction($fieldsToSearchIn);
    }
}