<?php

use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataList;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Api\PermissionFilter;
use SurfSharekit\Api\SearchApiController;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\Task;

class RepoItemJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'repoItem';
    public $type_plural = 'repoItems';

    //used to go from object to json
    public $fieldToAttributeMap = [
        'AuthorName' => 'authorName',
        'Created' => 'created',
        'LastEdited' => 'lastEdited',
        'CreatedLocal' => 'createdLocal',
        'LastEditedLocal' => 'lastEditedLocal',
        'TemplateSteps' => 'steps',
        'AnswersForJsonAPI' => 'answers',
        'RepoType' => 'repoType',
        'IsRemoved' => 'isRemoved',
        'Status' => 'status',
        'DeclineReason' => 'declineReason',
        'IsHistoricallyPublished' => 'isHistoricallyPublished',
        'NeedsToBeFinished' => 'needsToBeFinished',
        'UploadedFromApi' => 'uploadedFromApi',
        'Title' => 'title',
        'IsArchived' => 'isArchived',
        'LoggedInUserPermissions' => 'permissions',
        'Summary' => 'summary',
        'PublicationDate' => 'publicationDate',
        'LastEditorSummary' => 'lastEditor',
        'CreatorSummary' => 'creator',
    ];

    public $hasOneToRelationMap = [
        'relatedTo' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Institute',
            RELATIONSHIP_RELATED_OBJECT_CLASS => Institute::class,
            RELATIONSHIP_ADD_PERMISSION_METHOD => 'canConnectToInstitute',
            RELATIONSHIP_REMOVE_PERMISSION_METHOD => 'canConnectToInstitute'
        ],
        'author' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Owner',
            RELATIONSHIP_RELATED_OBJECT_CLASS => Person::class,
            RELATIONSHIP_ADD_PERMISSION_METHOD => 'canSetOwner',
            RELATIONSHIP_REMOVE_PERMISSION_METHOD => 'canSetOwner'
        ]
    ];

    public $hasManyToRelationsMap = [
        'children' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Children',
            RELATIONSHIP_RELATED_OBJECT_CLASS => RepoItem::class,
            RELATIONSHIP_ADD_PERMISSION_METHOD => 'canAddParent',
            RELATIONSHIP_REMOVE_PERMISSION_METHOD => 'canRemoveParent'
        ],
        'parents' => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'Parents',
            RELATIONSHIP_RELATED_OBJECT_CLASS => RepoItem::class,
            RELATIONSHIP_ADD_PERMISSION_METHOD => 'canAddChild',
            RELATIONSHIP_REMOVE_PERMISSION_METHOD => 'canRemoveChild'
        ],
        "tasks" => [
            RELATIONSHIP_GET_RELATED_OBJECTS_METHOD => 'getUncompletedReviewTasks',
            RELATIONSHIP_RELATED_OBJECT_CLASS => Task::class,
        ]
    ];

    //used to go from json to object
    public $attributeToFieldMap = [
        'repoType' => 'RepoType',
        'answers' => 'AnswersFromAPI',
        'status' => 'Status',
        'declineReason' => 'DeclineReason',
        'needsToBeFinished' => 'NeedsToBeFinished',
        'uploadedFromApi' => 'UploadedFromApi',
        'isRemoved' => 'IsRemovedFromApi',
        'copyFrom' => 'CopyFrom'
    ];

    protected function getSortableAttributesToColumnMap(): array {
        return ['title' => 'Title',
            'repoType' => 'RepoType',
            'isHistoricallyPublished' => 'IsHistoricallyPublished',
            'lastEdited' => 'LastEdited',
            'status' => 'Status',
            'publicationDate' => 'PublicationDate',
            'authorName' => ['Owner.Surname', 'Owner.FirstName'],
            'institute' => 'Institute.Title'];
    }

    public function getFilterableAttributesToColumnMap(): array {
        return [
            'status' => '`SurfSharekit_RepoItem`.`Status`',
            'repoType' => null,
            'scope' => null,
            'isRemoved' => '`SurfSharekit_RepoItem`.`IsRemoved`',
            'isArchived' => '`SurfSharekit_RepoItem`.`IsArchived`',
            'search' => null,
            'lastEdited' => '`SurfSharekit_RepoItem`.`LastEdited`',
            'title' => '`SurfSharekit_RepoItem`.`Title`',
            'publicationDate' => '`SurfSharekit_RepoItem`.`PublicationDate`',
            'id' => '`SurfSharekit_RepoItem`.`Uuid`',
            'institute' => '`SurfSharekit_RepoItem`.`InstituteUuid`',
            'authorID' => '`SurfSharekit_RepoItem`.`OwnerUuid`'
        ];
    }

    function applyGeneralFilter(DataList $objectsToDescribe): DataList {
        return $objectsToDescribe->filter([
                'Status:not' => 'Migrated',
                'RepoType' => Constants::MAIN_REPOTYPES,
                'PendingForDestruction' => 0]
        );
    }

    public function getFilterFunction(array $fieldsToSearchIn) {
        if (in_array('permissions.canCopy', $fieldsToSearchIn)) {
            return function (DataList $datalist, $filterValue, $modifier) {
                $canCopyClauses = [
                    'REPOITEM_CREATE_LEARNINGOBJECT' => "SurfSharekit_RepoItem.RepoType = 'LearningObject'",
                    'REPOITEM_CREATE_PUBLICATIONRECORD' => "SurfSharekit_RepoItem.RepoType = 'PublicationRecord'",
                    'REPOITEM_CREATE_REPOITEMLEARNINGOBJECT' => "SurfSharekit_RepoItem.RepoType = 'RepoItemLearningObject'",
                    'REPOITEM_CREATE_DATASET' => "SurfSharekit_RepoItem.RepoType = 'Dataset'",
                    'REPOITEM_CREATE_PROJECT' => "SurfSharekit_RepoItem.RepoType = 'Project'",
                    'REPOITEM_CREATE_REPOITEMLINK' => "SurfSharekit_RepoItem.RepoType = 'RepoItemLink'",
                    'REPOITEM_CREATE_REPOITEMPERSON' => "SurfSharekit_RepoItem.RepoType = 'RepoItemPerson'",
                    'REPOITEM_CREATE_REPOITEMREPOITEMFILE' => "SurfSharekit_RepoItem.RepoType = 'RepoItemRepoItemFile'",
                    'REPOITEM_CREATE_RESEARCHOBJECT' => "SurfSharekit_RepoItem.RepoType = 'ResearchObject'",
                    'REPOITEM_CREATE_REPOITEMRESEARCHOBJECT' => "SurfSharekit_RepoItem.RepoType = 'RepoItemResearchObject'"
                ];
                $scopedRepoItems = PermissionFilter::leftJoinOnUserPermissions(RepoItem::get(), $canCopyClauses);
                $scopedRepoItems = PermissionFilter::filterOnClauses($scopedRepoItems, $canCopyClauses);
                $datalist = $datalist->innerJoin('(' . $scopedRepoItems->sql() . ')', 'ri.ID = SurfSharekit_RepoItem.ID', 'ri');
                return $datalist;
            };
        }
        if (in_array('id', $fieldsToSearchIn)) {
            if (count($fieldsToSearchIn) > 1) {
                throw new Exception('Cannot mix id filter with another filter');
            }
            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=' || $modifier == '!=')) {
                    throw new Exception('Only ?filter[id][EQ] supported');
                }
                $ids = explode(',', $filterValue);
                $neg = $modifier === '=' ? '' : ':not';
                return $datalist->filter('Uuid' . $neg, $ids);
            };
        }
        if (in_array('repoType', $fieldsToSearchIn)) {
            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=' || $modifier == '!=')) {
                    throw new Exception('Only ?filter[id][EQ] or ...[NEQ] supported');
                }
                $filterValues = explode(',', $filterValue);
                $filters = [];
                foreach ($filterValues as $fv) {
                    $filters[] = ["SurfSharekit_RepoItem.RepoType $modifier ?" => $fv];
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
                return $datalist->where("SurfSharekit_RepoItem.InstituteID IN ( $subInstituteFilter )");
            };
        }
        if (in_array('search', $fieldsToSearchIn)) {
            if (count($fieldsToSearchIn) > 1) {
                throw new Exception('Cannot mix search filter with another filter');
            }

            return function (DataList $datalist, $filterValue, $modifier) {
                if (!($modifier == '=')) {
                    throw new Exception('Only ?filter[search][EQ] supported');
                }

                $searchTagsWithoutPlus = SearchApiController::getSearchTagsFromSearch($filterValue);
                $datalist = $datalist->leftJoin('SurfSharekit_RepoItemMetaField', "ftsRimf.RepoItemID = SurfSharekit_RepoItem.ID", "ftsRimf")
                    ->leftJoin('SurfSharekit_RepoItemMetaFieldValue', "ftsRimfv.RepoItemMetaFieldID = ftsRimf.ID", "ftsRimfv")
                    ->leftJoin('SurfSharekit_RepoItem', "connectedRepoItem.ID = ftsRimfv.RepoItemID AND connectedRepoItem.RepoType = 'RepoItemPerson'", 'connectedRepoItem')
                    ->leftJoin('SurfSharekit_MetaFieldOption', "answero.ID = ftsRimfv.MetaFieldOptionID", 'answero')
                    ->leftJoin('SurfSharekit_MetaField', "tagField.ID = answero.MetaFieldID AND tagField.SystemKey = 'Tags'", 'tagField');

                foreach ($searchTagsWithoutPlus as $tag) {
                    if(stripos($tag, '-') !== false){
                        $matchTag =  '"' . $tag . '"';
                    }else{
                        $matchTag =  $tag . '*';
                    }
                    $datalist = $datalist->whereAny(
                        ["(MATCH(SurfSharekit_RepoItem.Title,SurfSharekit_RepoItem.Subtitle) AGAINST (? IN Boolean MODE) AND (SurfSharekit_RepoItem.Title like ? OR SurfSharekit_RepoItem.Subtitle like ?))" => ['+' . $matchTag, '%' . $tag . '%', '%' . $tag . '%'],
                            "(MATCH(connectedRepoItem.Title) AGAINST (? IN Boolean MODE) AND (connectedRepoItem.Title like ?))" => ['+' . $matchTag, '%' . $tag . '%']]);
                }
                return $datalist;
            };
        }

        return parent::getFilterFunction($fieldsToSearchIn);
    }
}