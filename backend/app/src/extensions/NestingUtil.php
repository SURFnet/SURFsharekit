<?php

namespace SurfSharekit\Models;

use SurfSharekit\Models\Helper\Logger;

class NestingUtil
{
    /**
     * Nesting an array of records using a parent and id property to match and create a valid Tree
     *
     * Convert this:
     * [
     *   'id' => 1,
     *   'parent'=> null
     * ],
     * [
     *   'id' => 2,
     *   'parent'=> 1
     * ]
     *
     * Into this:
     * [
     *   'id' => 1,
     *   'parent'=> null
     *   'children' => [
     *     'id' => 2
     *     'parent' => 1,
     *     'children' => []
     *    ]
     * ]
     *
     * @param array  $records      array of records to apply the nesting
     * @param string $recordPropId property to read the current record_id, e.g. 'id'
     * @param string $parentPropId property to read the related parent_id, e.g. 'parent_id'
     * @param string $childWrapper name of the property to place children, e.g. 'children'
     * @param string $parentId     optional filter to filter by parent
     *
     * @return array
     */
    static function nest(&$records, $recordPropId, $parentPropId, $childWrapper, $parentId = null) {
        $nestedRecords = [];
        foreach ($records as $index => $record) {

            if (array_key_exists($parentPropId, $record) && isset($record[$parentPropId]['@id']) && $record[$parentPropId]['@id'] == $parentId) {
                unset($records[$index]);
                $record[$childWrapper] = self::nest($records, $recordPropId, $parentPropId, $childWrapper, $record[$recordPropId]);
                $nestedRecords[] = $record;
            }
        }

        return $nestedRecords;
    }
}