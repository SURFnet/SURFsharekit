<?php

use SilverStripe\ORM\DataList;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Api\SearchApiController;
use SurfSharekit\Models\Institute;
use SurfSharekit\Api\PermissionFilter;
use SurfSharekit\Models\RepoItem;

class RepoItemSummaryJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'repoItemSummary';
    public $type_plural = 'repoItemSummaries';

    //used to go from object to json
    public $fieldToAttributeMap = [
        'Summary.created' => 'created',
        'Summary.authorName' => 'authorName',
        'Summary.lastEdited' => 'lastEdited',
        'Summary.repoType' => 'repoType',
        'Summary.isRemoved' => 'isRemoved',
        'Summary.status' => 'status',
        'Summary.title' => 'title',
        'Summary.isArchived' => 'isArchived',
        'Summary.publicationDate' => 'publicationDate',
        'Summary.extra' => 'extra', //publisher, suborganisations en authors
        'Summary.accessRight' => 'accessRight',
        'LoggedInUserPermissions' => 'permissions' //Overrides the cached permission information
    ];

    public function applyGeneralFilter(DataList $objectsToDescribe): \SilverStripe\ORM\DataList {
        return $objectsToDescribe
            ->innerJoin('SurfSharekit_RepoItem', 'SurfSharekit_RepoItem.ID = SurfSharekit_RepoItemSummary.RepoItemID')
            ->where(['SurfSharekit_RepoItem.PendingForDestruction = ?' => 0]);
    }

    protected function getSortableAttributesToColumnMap(): array {
        return ['title' => 'RepoItem.Title',
            'repoType' => 'RepoItem.RepoType',
            'lastEdited' => 'RepoItem.LastEdited',
            'status' => 'Status',
            'publicationDate' => 'RepoItem.PublicationDate',
            'authorName' => ['Owner.Surname', 'Owner.FirstName'],
            'institute' => 'Institute.Title'];
    }

    public function getFilterableAttributesToColumnMap(): array {
        return ['status' => '`SurfSharekit_RepoItemSummary`.`Status`',
            'isRemoved' => '`SurfSharekit_RepoItemSummary`.`IsRemoved`',
            'lastEdited' => '`SurfSharekit_RepoItem`.`LastEdited`',
            'title' => '`SurfSharekit_RepoItem`.`Title`',
            'publicationDate' => '`SurfSharekit_RepoItem`.`PublicationDate`',
            'id' => '`SurfSharekit_RepoItem`.`Uuid`',
            'institute' => '`SurfSharekit_RepoItemSummary`.`InstituteUuid`',
            'authorID' => '`SurfSharekit_RepoItemSummary`.`OwnerUuid`'];
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
                return $datalist->filter('RepoItem.Uuid' . $neg, $ids);
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
                    $filters[] = ["SurfSharekit_RepoItem.RepoType $modifier '$fv'"];
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
                return $datalist->where("SurfSharekit_RepoItemSummary.InstituteID IN ( $subInstituteFilter )");
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

                $datalist = $datalist->innerJoin('SurfSharekit_SearchObject', "SurfSharekit_SearchObject.RepoItemID = SurfSharekit_RepoItemSummary.RepoItemID");

                foreach ($searchTagsWithoutPlus as $tag) {
                    if(stripos($tag, '-') !== false){
                        $matchTag =  '"' . $tag . '"';
                    }else{
                        $matchTag =  $tag . '*';
                    }

                    $datalist = $datalist->where(["(MATCH(SurfSharekit_SearchObject.SearchText) AGAINST (? IN Boolean MODE) AND SurfSharekit_SearchObject.SearchText like ?)" => ['+' . $matchTag, '%' . $tag . '%']]);
                }
                return $datalist;
            };
        }
        return parent::getFilterFunction($fieldsToSearchIn);
    }
}