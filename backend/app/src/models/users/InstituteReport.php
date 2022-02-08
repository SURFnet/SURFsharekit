<?php

namespace SurfSharekit\Models;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SurfSharekit\Api\InstituteScoper;

/**
 * Class InstituteReport
 * @package SurfSharekit\Models
 * This class and corresponding DataClass are created so to not need a table in the database
 * Using the overriden get methods, the Insitute table will be queried and then the resulting objects will be 'cast' to InsituteReports when using a JsonApiController
 */
class InstituteReport extends Institute {
    /**
     * @param null $callerClass
     * @param string $filter
     * @param string $sort
     * @param string $join
     * @param null $limit
     * @param string $containerClass
     * @return DataList|InstituteReportDataList
     * Overriden to query the InstituteTable
     */
    public static function get(
        $callerClass = null,
        $filter = "",
        $sort = "",
        $join = "",
        $limit = null,
        $containerClass = DataList::class
    ) {
        // Validate arguments
        if ($callerClass == null) {
            if ($filter || $sort || $join || $limit || ($containerClass !== DataList::class)) {
                throw new InvalidArgumentException('If calling <classname>::get() then you shouldn\'t pass any other' . ' arguments');
            }
        } elseif ($callerClass === self::class) {
            throw new InvalidArgumentException('DataObject::get() cannot query non-subclass DataObject directly');
        }
        if ($join) {
            throw new InvalidArgumentException(
                'The $join argument has been removed. Use leftJoin($table, $joinClause) instead.'
            );
        }

        // Build and decorate with args
        $result = InstituteReportDataList::create(Institute::class);
        if ($filter) {
            $result = $result->where($filter);
        }
        if ($sort) {
            $result = $result->sort($sort);
        }
        if ($limit && strpos($limit, ',') !== false) {
            $limitArguments = explode(',', $limit);
            $result = $result->limit($limitArguments[1], $limitArguments[0]);
        } elseif ($limit) {
            $result = $result->limit($limit);
        }

        return $result;
    }

    /**
     * @param int|string $classOrID
     * @param null $idOrCache
     * @param bool $cache
     * @return DataObject|Institute|InstituteReport|null
     * Overriden to query the InstituteTable
     */
    public static function get_by_id($classOrID, $idOrCache = null, $cache = true) {
        return Institute::get_by_id($classOrID, $idOrCache, $cache);
    }

    /**
     * @return string
     * Method to mirror Institute permissions
     */
    function getPermissionObjectName() {
        try {
            $reflect = new ReflectionClass(new Institute());
        } catch (ReflectionException $e) {
            return $e->getMessage();
        }
        return $reflect->getShortName();
    }

    /**
     * Don't add this DataObject to the database
     */
    public function requireTable() {
    }

    public function getRepoItemsSummary() {
        $repoItems = InstituteScoper::getDataListScopedTo(RepoItem::class, [$this->ID])->filter(['Status:not'=>'Migrated', 'IsRemoved'=>0]);
        $publicationRecordCount = $repoItems->filter(['RepoType' => 'PublicationRecord'])->count();
        $learningObjectCount = $repoItems->filter(['RepoType' => 'LearningObject'])->count();
        $researchObjectsCount = $repoItems->filter(['RepoType' => 'ResearchObject'])->count();
        return [
            'total' => ($publicationRecordCount + $learningObjectCount + $researchObjectsCount),
            'publicationRecords' => $publicationRecordCount,
            'learningObjects' => $learningObjectCount,
            'researchObjects' => $researchObjectsCount
        ];
    }

    protected function onAfterWrite() {
        parent::onAfterWrite();
        if($this->isChanged()) {
            ScopeCache::removeCachedViewable(InstituteReport::class);
            ScopeCache::removeCachedDataList(InstituteReport::class);
        }
    }
}

/***
 * Class InstituteReportDataList
 * @package SurfSharekit\Models
 * DataList that is able to query Institutes, but 'casts' them as if they were InstituteReports
 */
class InstituteReportDataList extends DataList {
    /**
     * Create a DataObject from the given SQL row
     *
     * @param array $row
     * @return DataObject
     */
    public function createDataObject($row) {
        $this->dataClass = InstituteReport::class; //Query has been done, now we fake delivering 'InstituteReport' items
        $class = InstituteReport::class;

        if (empty($row['ClassName'])) {
            $row['ClassName'] = $class;
        }

        // Failover from RecordClassName to ClassName
        if (empty($row['RecordClassName'])) {
            $row['RecordClassName'] = $row['ClassName'];
        }

        return Injector::inst()->create(InstituteReport::class, $row, false, $this->getQueryParams());
    }

    public function dataClass() {
        return InstituteReport::class;
    }
}