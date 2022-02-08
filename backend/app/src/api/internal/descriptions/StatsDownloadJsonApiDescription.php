<?php

use SilverStripe\ORM\DataList;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Models\Institute;

class StatsDownloadJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'statsDownload';
    public $type_plural = 'statsDownloads';

    public function getFilterableAttributesToColumnMap(): array {
        return [
            'downloadDate' => 'DownloadDate'
        ];
    }

    public function getFilterFunction(array $fieldsToSearchIn) {
        if (in_array('repoType', $fieldsToSearchIn)) {
            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=' || $modifier == '!=')) {
                    throw new Exception('Only ?filter[id][EQ] or ...[NEQ] supported');
                }
                $filterValues = explode(',', $filterValue);
                $filters = [];
                foreach ($filterValues as $fv) {
                    $filters[] = ["SurfSharekit_StatsDownload.RepoType $modifier '$fv'"];
                }
                return $datalist->whereAny($filters);
            };
        }
        if (in_array('scope', $fieldsToSearchIn)) {
            if (count($fieldsToSearchIn) > 1) {
                throw new Exception('Cannot mix scope filter with another filter');
            }
            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=')) {
                    throw new Exception('Only ?filter[scope][EQ] supported');
                }
                $instituteUuids = explode(',', $filterValue);
                $instituteIDs = Institute::get()->filter(['Uuid' => $instituteUuids])->column('ID');
                $subInstituteFilter = InstituteScoper::getScopeFilter($instituteIDs);
                return $datalist->where("SurfSharekit_StatsDownload.InstituteID IN ( $subInstituteFilter )");
            };
        }

        return parent::getFilterFunction($fieldsToSearchIn);
    }
}