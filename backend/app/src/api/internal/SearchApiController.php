<?php

namespace SurfSharekit\Api;

use DefaultMetaFieldOptionPartJsonApiDescription;
use Exception;
use GroupJsonApiDescription;
use InstituteImageJsonApiDescription;
use InstituteJsonApiDescription;
use MetaFieldJsonApiDescription;
use MetaFieldOptionJsonApiDescription;
use MetaFieldTypeJsonApiDescription;
use PersonImageJsonApiDescription;
use PersonJsonApiDescription;
use RepoItemFileJsonApiDescription;
use RepoItemJsonApiDescription;
use RepoItemSummaryJsonApiDescription;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Group;
use SilverStripe\Security\Security;
use SurfSharekit\Models\DefaultMetaFieldOptionPart;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\InstituteImage;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\MetaFieldOption;
use SurfSharekit\Models\MetaFieldType;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\PersonImage;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\RepoItemSummary;
use SurfSharekit\Models\Template;
use SurfSharekit\Models\TemplateMetaField;
use TemplateJsonApiDescription;
use TemplateMetaFieldJsonApiDescription;

/**
 * Class SearchJsonApiController
 * @package SurfSharekit\Api
 * This class is the entry point for the internal json api to GET searchResults
 */
class SearchApiController extends JsonApiController {
    protected function getApiURLSuffix() {
        return 'api/v1/search';
    }

    private static $allowed_actions = [
        'getJsonApiRequest'
    ];

    protected function getClassToDescriptionMap() {
        return [Group::class => new GroupJsonApiDescription(),
            Institute::class => new InstituteJsonApiDescription(),
            Template::class => new TemplateJsonApiDescription(),
            TemplateMetaField::class => new TemplateMetaFieldJsonApiDescription(),
            MetaField::class => new MetaFieldJsonApiDescription(),
            Person::class => new PersonJsonApiDescription(),
            PersonImage::class => new PersonImageJsonApiDescription(),
            InstituteImage::class => new InstituteImageJsonApiDescription(),
            RepoItemFile::class => new RepoItemFileJsonApiDescription(),
            MetaFieldType::class => new MetaFieldTypeJsonApiDescription(),
            MetaFieldOption::class => new MetaFieldOptionJsonApiDescription(),
            DefaultMetaFieldOptionPart::class => new DefaultMetaFieldOptionPartJsonApiDescription(),
            RepoItem::class => new RepoItemJsonApiDescription()];
    }

    protected function patchToObject($objectClass, $requestBody, $prexistingObject, $relationshipToPatch = null) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function postToObject($objectClass, $requestBody, $prexistingObject = null, $relationshipToPost = null) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function deleteToObject($objectClass, $requestBody, $prexistingObject, $relationshipToModify = null) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function getDataObject($objectToDescribe) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function getDataList($objectClass) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function getRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    protected function getObjectsBehindRelationOfDataObject($objectToDescribe, $requestedRelationName) {
        return ExternalJsonApiController::createJsonApiBodyResponseFrom(static::unsupportedAction(), 403);
    }

    public function getJsonApiRequest() {
        $request = $this->getRequest();
        $this->classToDescriptionMap = $this->getClassToDescriptionMap();
        //Only allow non-students to search in groups and persons
        $extendedSearch = false;
        foreach (Security::getCurrentUser()->Groups() as $group) {
            foreach ($group->Roles() as $role) {
                if (!in_array($role->Title, [Constants::TITLE_OF_STUDENT_ROLE, Constants::TITLE_OF_MEMBER_ROLE])) {
                    $extendedSearch = true;
                    break;
                }
            }
        }

        //https://jsonapi.org/format/#content-negotiation-clients
        $stringOfIncludedRelationships = $this->request->requestVar("include") ?: "";
        if ($stringOfIncludedRelationships) {
            return $this->createJsonApiBodyResponseFrom(static::unsupportedAction("No 'include' parameter supporteds"), 400);
        }

        //$dataObjectJsonApiDescriptor = new DataObjectJsonApiEncoder($this->classToDescriptionMap, $listOfIncludedRelationships);

        $requestedObjectClass = $request->param("Action");

        if ($requestedObjectClass) {
            return $this->createJsonApiBodyResponseFrom(static::unsupportedAction(), 400);
        }

        $requestVars = $request->getVars();
        if ($requestVars && isset($requestVars['fields'])) {
            $sparseFieldsPerType = $requestVars['fields'];
            if (!is_array($sparseFieldsPerType)) {
                return $this->createJsonApiBodyResponseFrom(static::invalidSparseFieldsJsonApiBodyError(), 400);
            }
            $this->sparseFields = $sparseFieldsPerType;
        }

        if ($requestVars && isset($requestVars['filter'])) {
            $filtersPerAttribute = $requestVars['filter'];
            if (!is_array($filtersPerAttribute)) {
                return $this->createJsonApiBodyResponseFrom(static::invalidFiltersJsonApiBodyError(), 400);
            }
            $this->filters = $filtersPerAttribute;
        }

        if ($requestVars && isset($requestVars['page'])) {
            if (!is_array($requestVars['page'])) {
                return $this->createJsonApiBodyResponseFrom(static::invalidPaginationJsonApiBodyError(), 400);
            }
            $paginationQuery = $requestVars['page'];

            if (isset($paginationQuery['number'])) {
                $number = intval($paginationQuery['number']);
                if ($number) {
                    $this->pageNumber = $number;
                }
            }

            if (isset($paginationQuery['size'])) {
                $size = intval($paginationQuery['size']);
                if ($size) {
                    $this->pageSize = $size;
                }
            }

            if (!$this->pageNumber || !$this->pageSize) {
                return $this->createJsonApiBodyResponseFrom(static::invalidPaginationJsonApiBodyError(), 400);
            }
        }
        //Not retrieving single object, thus retrieving multiple objects

        $this->pageSize = $this->pageSize ?? 10;
        $this->pageNumber = $this->pageNumber ?? 1;

        try {
            $searchQuery = (isset($this->filters['query']) ? $this->filters['query'] : '');

            $filterOnIsRemoved = isset($this->filters['isRemoved']) ? $this->filters['isRemoved'] : null;
            if ($filterOnIsRemoved && !($filterOnIsRemoved == 1 || $filterOnIsRemoved == 0)) {
                return $this->createJsonApiBodyResponseFrom(static::invalidFiltersJsonApiBodyError('Use filter[isRemoved] only with values 1 or 0'), 400);
            }

            $repoItems = InstituteScoper::getAll(RepoItemSummary::class);
            $repoItemDescription = new RepoItemSummaryJsonApiDescription();
            $repoItems = $repoItemDescription->applyGeneralFilter($repoItems);
            $repoItems = $repoItems->whereAny(["SurfSharekit_RepoItem.RepoType = 'PublicationRecord'", "SurfSharekit_RepoItem.RepoType = 'ResearchObject'", "SurfSharekit_RepoItem.RepoType = 'LearningObject'"]);
            if ($filterOnIsRemoved != null) {
                $repoItems = $repoItems->where(['SurfSharekit_RepoItem.IsRemoved' => $filterOnIsRemoved]);
            }
            $repoItems = $repoItemDescription->applyFilter($repoItems, "search", $searchQuery);
            $repoItems = $repoItems->setQueriedColumns(['ID', 'ClassName', 'LastEdited']);

            $repoItemsQuery = $repoItems->dataQuery()->query()->sql($repoItemsQueryParams);
            function before($subject, $needle) {
                return substr($subject, 0, strpos($subject, $needle));
            }

            $repoItemsQuery = str_replace(before($repoItemsQuery, " FROM "), "SELECT SurfSharekit_RepoItem.ID, SurfSharekit_RepoItem.LastEdited, COALESCE(`SurfSharekit_RepoItem`.`ClassName`, 'SurfSharekit\\Models\\RepoItem') AS `RecordClassName`", $repoItemsQuery);

            if ($extendedSearch) {
                $persons = InstituteScoper::getAll(Person::class);
                $personsDescription = new PersonJsonApiDescription();
                $persons = $personsDescription->applyFilter($persons, "search", $searchQuery);
                $persons = $persons->setQueriedColumns(['ID', 'ClassName', 'LastEdited']);
                if ($filterOnIsRemoved != null) {
                    $persons = $persons->where(['Member.IsRemoved' => $filterOnIsRemoved]);
                }
                $personsQuery = $persons->dataQuery()->query()->sql($personsQueryParams);
                $personsQuery = str_replace(before($personsQuery, " FROM "), "SELECT Member.ID, Member.LastEdited, COALESCE(`Member`.`ClassName`, 'SilverStripe\\Security\\Member') AS `RecordClassName`", $personsQuery);

                $groups = InstituteScoper::getAll(Group::class);
                $groupsDescription = new GroupJsonApiDescription();
                $groups = $groupsDescription->applyFilter($groups, "search", $searchQuery);
                $groups = $groups->setQueriedColumns(['ID', 'ClassName', 'LastEdited']);
                if ($filterOnIsRemoved != null) {
                    $groups = $persons->where(['`Group`.IsRemoved' => $filterOnIsRemoved]);
                }
                $groupsQuery = $groups->dataQuery()->query()->sql($groupsQueryParams);
                $groupsQuery = str_replace(before($groupsQuery, " FROM "), "SELECT `Group`.ID, `Group`.LastEdited, COALESCE(`Group`.`ClassName`, 'SilverStripe\\Security\\Group') AS `RecordClassName`", $groupsQuery);

                $searchQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM (($repoItemsQuery) UNION ($personsQuery) UNION ($groupsQuery)) temp";

                $searchQueryParams = array_merge($repoItemsQueryParams, $personsQueryParams);
                $searchQueryParams = array_merge($searchQueryParams, $groupsQueryParams);
            } else {
                $searchQuery = $repoItemsQuery;
                $searchQueryParams = $repoItemsQueryParams;
            }

            /**
             * Add sort
             */
            $sort = $request->getVar('sort');
            $sortString = '';
            if ($sort && strtolower($sort) == 'lastedited') {
                //Temporarily removed due to time constraints. works in docker, but not in TEST or STAGING
//                $sortString = " ORDER BY LastEdited ASC";
            } else if (strtolower($sort) == '-lastedited') {
                //Temporarily removed due to time constraints. works in docker, but not in TEST or STAGING
//                $sortString = " ORDER BY LastEdited DESC";
            } else if ($sort) {
                return $this->createJsonApiBodyResponseFrom(static::invalidSortJsonApiBodyError("Only lastEdited and -lastEdited supported"), 401);
            }
            $searchQuery .= $sortString;

            /**
             * Add limit
             */

            $limitString = " LIMIT ?";
            $searchQueryParams[] = $this->pageSize;
            if ($this->pageNumber > 1) {
                $limitString .= " OFFSET ?";
                $searchQueryParams[] = ($this->pageNumber - 1) * $this->pageSize;
            }
            $searchQuery .= $limitString;

            $foundObjects = DB::prepared_query($searchQuery, $searchQueryParams);
            $this->totalCount = DB::query("SELECT FOUND_ROWS()")->value();

            $encoder = $this->getDataObjectJsonApiEncoder();
            $response = [];

            $linkSizeAddition = '?page[size]=' . $this->pageSize;
            $linkNumberAddition = '&page[number]=';

            $response[JsonApi::TAG_META][JsonApi::TAG_TOTAL_COUNT] = $this->totalCount;
            $response[JsonApi::TAG_LINKS][JsonApi::TAG_LINKS_FIRST] = 'search' . $linkSizeAddition . $linkNumberAddition . '1';
            if ($this->pageNumber > 1) {
                $response[JsonApi::TAG_LINKS][JsonApi::TAG_LINKS_PREVIOUS] = 'search' . $linkSizeAddition . $linkNumberAddition . ($this->pageNumber - 1);
            }
            $response[JsonApi::TAG_LINKS][JsonApi::TAG_LINKS_SELF] = 'search' . $linkSizeAddition . $linkNumberAddition . $this->pageNumber;

            $lastpage = ceil($this->totalCount / $this->pageSize);
            if ($this->pageNumber < $lastpage) {
                $response[JsonApi::TAG_LINKS][JsonApi::TAG_LINKS_NEXT] = 'search' . $linkSizeAddition . $linkNumberAddition . ($this->pageNumber + 1);
            }
            $response[JsonApi::TAG_LINKS][JsonApi::TAG_LINKS_LAST] = 'search' . $linkSizeAddition . $linkNumberAddition . $lastpage;

            foreach ($foundObjects as $foundObject) {
                $obj = $foundObject['RecordClassName']::get_by_id($foundObject['ID']);
                if ($obj && $obj->exists()) {
                    $response[JsonApi::TAG_DATA][] = $encoder->describeDataObjectAsData($obj, BASE_URL . $this->getApiURLSuffix());
                }
            }
            return JsonApiController::createJsonApiBodyResponseFrom($response, 200);
        } catch (Exception $e) {
            return $this->createJsonApiBodyResponseFrom(static::noPermissionJsonApiBodyError($e->getMessage()), 403);
        }
    }

    protected function canViewObjectToDescribe($objectToDescribe) {
        return $objectToDescribe->canView(Security::getCurrentUser());
    }

    /**
     * @param string $searchQuery e.g. '"the could " be this '
     * @return array e.g. [the could, be, this]
     * Function to create search 'tags' from a string,
     */
    public static function getSearchTagsFromSearch(string $searchQuery): array {
        $amountOfQuotationMarks = substr_count($searchQuery, '"');
        if ($searchQuery == "") {
            return [];
        } else if ($amountOfQuotationMarks < 2) { //  "multi word tag" not applicable
            $resultArrayUntrimmed = explode(" ", $searchQuery);
        } else {
            $multiWordTag = static::getFirstStringBetween($searchQuery, '"', '"');
            $resultArray = [$multiWordTag];
            $shortenedSearchQuery = str_replace('"' . $multiWordTag . '"', "", $searchQuery);
            $resultArray2 = self::getSearchTagsFromSearch($shortenedSearchQuery);
            $resultArrayUntrimmed = array_merge($resultArray, $resultArray2);
        }
        $resultArrayTrimmed = [];
        foreach ($resultArrayUntrimmed as $tag) {
            $trimmedTag = trim($tag);
            if (strlen($trimmedTag)) {
                $resultArrayTrimmed[] = $trimmedTag;
            }
        }
        return $resultArrayTrimmed;
    }

    private static function getFirstStringBetween($haystack, $needleStart, $needleEnd) {
        $subtringStart = strpos($haystack, $needleStart);
        //Adding the strating index of the strating word to
        //its length would give its ending index
        $subtringStart += strlen($needleStart);
        //Length of our required sub string
        $size = strpos($haystack, $needleEnd, $subtringStart) - $subtringStart;
        // Return the substring from the index substring_start of length size
        return substr($haystack, $subtringStart, $size);
    }
}