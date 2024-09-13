<?php

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Claim;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemSummary;

class TaskJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'task';
    public $type_plural = 'tasks';

    public $fieldToAttributeMap = [
        'OwnerUuid' => 'ownerId',
        'ClaimedBy' => 'claimedBy',
        'Type' => 'type',
        'ReasonOfDecline' => 'reasonOfDecline',
        'State' => 'state',
        'Action' => 'action',
        'InstituteTitle' => 'institute',
        'Material' => 'material',
        'AssociationUuid' => 'associationUuid',
        'Created' => 'date',
        'Data' => 'data'
    ];

    public $attributeToFieldMap = [
        'reasonOfDecline' => 'ReasonOfDecline',
        'action' => 'Action'
    ];

    function applyGeneralFilter(DataList $objectsToDescribe): DataList {
        return $objectsToDescribe->filter([
            'OwnerID' => Security::getCurrentUser()->ID
        ]);
    }

    public $hasOneToRelationMap = [
        "completedBy" => [
            RELATIONSHIP_RELATED_OBJECT_CLASS => Person::class,
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'CompletedBy'
        ],
        'person' => [
            RELATIONSHIP_RELATED_OBJECT_CLASS => Person::class,
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Person'
        ],
        'repoItem' => [
            RELATIONSHIP_RELATED_OBJECT_CLASS => RepoItem::class,
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'RepoItem'
        ],
        'claim' => [
            RELATIONSHIP_RELATED_OBJECT_CLASS => Claim::class,
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Claim'
        ]
    ];

    public function getFilterableAttributesToColumnMap(): array {
        return [
            'claimedBy' => '`SurfSharekit_Task`.`ClaimedBy`',
            'type' => '`SurfSharekit_Task`.`Type`',
            'state' => '`SurfSharekit_Task`.`State`',
            'associationUuid' => '`SurfSharekit_Task`.`AssociationUuid`'
        ];
    }

    public function getFilterFunction(array $fieldsToSearchIn) {
        if (in_array('material', $fieldsToSearchIn)) {
            return function (DataList $dataList, $filterValue, $modifier) use ($fieldsToSearchIn) {
                return $dataList->filter(['RepoItem.RepoType' => $filterValue]);
            };
        }

        if (in_array('organization', $fieldsToSearchIn)) {
            return function (DataList $datalist, $filterValue, $modifier) use ($fieldsToSearchIn) {
                return $datalist->filterAny([
                    'RepoItem.Institute.Uuid' => $filterValue,
                    'Claim.Institute.Uuid' => $filterValue
                ]);
            };
        }

        if (in_array('includesFile', $fieldsToSearchIn)) {
            return function (DataList $dataList, $filterValue, $modified) use ($fieldsToSearchIn) {
                if ($filterValue === "false") {
                    return $dataList;
                }

                $baseTableName = Config::inst()->get($dataList->dataClass(), 'table_name');
                $repoItemSummaryTableName = Config::inst()->get(RepoItemSummary::class, 'table_name');

                $dataList = $dataList
                    ->innerJoin($repoItemSummaryTableName, "$repoItemSummaryTableName.RepoItemID = $baseTableName.RepoItemID");

                return $dataList->where(["JSON_VALUE($repoItemSummaryTableName.Summary, '$.includesFile') = ?" => 1]);
            };
        }

        if (in_array('includesUrl', $fieldsToSearchIn)) {
            return function (DataList $dataList, $filterValue, $modified) use ($fieldsToSearchIn) {
                if ($filterValue === "false") {
                    return $dataList;
                }

                $baseTableName = Config::inst()->get($dataList->dataClass(), 'table_name');
                $repoItemSummaryTableName = Config::inst()->get(RepoItemSummary::class, 'table_name');

                $dataList = $dataList
                    ->innerJoin($repoItemSummaryTableName, "$repoItemSummaryTableName.RepoItemID = $baseTableName.RepoItemID");

                return $dataList->where(["JSON_VALUE($repoItemSummaryTableName.Summary, '$.includesUrl') = ?" => 1]);
            };
        }

        return parent::getFilterFunction($fieldsToSearchIn);
    }

    protected function getSortableAttributesToColumnMap(): array {
        return [
            'created' => 'Created DESC',
        ];
    }

    public function getPossibleFilters(DataList $objectsToDescribe): array {
        return [
            'material' => [
                'type' => 'dropdown',
                'options' => $this->getMaterialTypeFilterOptions($objectsToDescribe)
            ],
            'type' => [
                'type' => 'dropdown',
                'options' => $this->getTypeFilterOptions($objectsToDescribe)
            ],
            'organization' => [
                'type' => 'dropdown',
                'options' => $this->getOrganizationFilterOptions($objectsToDescribe)
            ],
            'includesFile' => [
                'type' => 'checkbox'
            ],
            'includesUrl' => [
                'type' => 'checkbox'
            ]
        ];
    }

    private function getTypeFilterOptions(DataList $objectsToDescribe) {
        $resultArr = [];

        $types = $objectsToDescribe->column('Type');

        $typeCount = array_count_values($types);

        foreach ($typeCount as $type => $count) {
            $resultArr[] = [
                "label" => "dashboard.tasks.type." . strtolower($type),
                "value" => $type,
                "count" => $count
            ];
        }

        return $resultArr;
    }

    private function getMaterialTypeFilterOptions(DataList $objectsToDescribe) {
        $resultArr = [];

        $materialTypes = $objectsToDescribe
            ->innerJoin("SurfSharekit_RepoItem", "SurfSharekit_RepoItem.ID = SurfSharekit_Task.RepoItemID")
            ->where('SurfSharekit_RepoItem.RepoType IS NOT NULL')
            ->columnUnique('SurfSharekit_RepoItem.RepoType');

        $materialTypeCount = array_count_values($materialTypes);

        foreach ($materialTypeCount as $type => $count) {
            $resultArr[] = [
                "label" => "repoitem.type." . strtolower($type),
                "value" => $type,
                "count" => $count
            ];
        }

        return $resultArr;
    }

    private function getOrganizationFilterOptions(DataList $objectsToDescribe) {
        $resultArr = [];

        $organizations = $objectsToDescribe
            ->leftJoin("SurfSharekit_RepoItem", "SurfSharekit_RepoItem.ID = SurfSharekit_Task.RepoItemID")
            ->leftJoin("SurfSharekit_Claim", "SurfSharekit_Claim.ID = SurfSharekit_Task.ClaimID")
            ->innerJoin("SurfSharekit_Institute", "SurfSharekit_Institute.ID = SurfSharekit_Claim.InstituteID OR SurfSharekit_Institute.ID = SurfSharekit_RepoItem.InstituteID")
            ->where('SurfSharekit_Institute.Uuid IS NOT NULL')
            ->columnUnique('SurfSharekit_Institute.Uuid');

        if (count($objectsToDescribe) == 0) {
            return [];
        }

        $organizationCount = array_count_values($organizations);
        $organizationUuids = !empty(array_keys($organizationCount)) ? array_keys($organizationCount) : [-1];

        $institutes = Institute::get()->filter(['Uuid' => $organizationUuids])->map('Uuid', 'Title')->toArray();

        foreach ($organizationCount as $organization => $count) {
            $resultArr[] = [
                "label" => $institutes[$organization],
                "value" => $organization,
                "count" => $count
            ];
        }

        return $resultArr;
    }
}