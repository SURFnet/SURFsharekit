<?php

namespace SurfSharekit\ApiCache;

use Exception;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\RepoItem;

class ProtocolDataReaderCSV implements ProtocolDataReader {
    const CSVSystemKey = "CSV";
    // Use commas inside CSV cells to avoid conflicting with the CSV delimiter (which is ';' in DataObjectCSVFileEncoder)
    private const MULTIVALUE_SEPARATOR = ', ';

    // Array of Uuid => [LastEdited => string, Data => string]
    private static $dataCache = [];

    public static function getProtocolSystemKey(): string {
        return static::CSVSystemKey;
    }

    /**
     * Extracts and returns the data for the given item. If the item is of type Person,
     * an empty string is returned. For RepoItem instances, it checks for cached data
     * and returns it if valid. Otherwise, the data is constructed, cached, and then returned.
     *
     * @param RepoItem|Person $item The item for which data is to be extracted. This can be an instance of RepoItem or Person.
     * @return string The extracted data for the RepoItem, or an empty string if the item is a Person.
     */
    public static function extractItemData(RepoItem|Person $item): string {
        // The CSV protocol only supports RepoItems for now
        if ($item instanceof Person) {
            return "";
        }

        // Check if the data is already in memory
        if (isset(static::$dataCache[$item->Uuid])) {
            $cacheItem = static::$dataCache[$item->Uuid];
            if ($cacheItem["LastEdited"] == $item->LastEdited) {
                return $cacheItem["Data"];
            }
        }

        // Extract the data for the RepoItem and put it in mem cache
        $data = static::constructRepoItemData($item);
        static::$dataCache[$item->Uuid] = [
            "LastEdited" => $item->LastEdited,
            "Data" => $data
        ];
        return $data;
    }

    /**
     * Constructs a JSON-encoded representation of repository item data, using metadata fields and protocol definitions.
     *
     * @param RepoItem $repoItem The repository item to construct data for.
     *
     * @return string A JSON-encoded string representing the repository item's data.
     *
     * @throws Exception If the CSV protocol does not exist or the protocol cannot be retrieved.
     */
    private static function constructRepoItemData(RepoItem $repoItem) {
        $rowData = [
            $repoItem->getField('Uuid'),
            $repoItem->getField('RepoType'),
            $repoItem->Institute()->Title,
            $repoItem->getField('Status')
        ];

        $protocol = Protocol::get()->find("SystemKey", static::CSVSystemKey);
        if (!$protocol || !$protocol->exists()) {
            throw new Exception('CSV Protocol does not exist');
        }

        $rootNodes = $protocol->ProtocolNodes()->filter('ParentNodeID', 0);
        $metaFieldColumnCounts = static::getMetaFieldColumnCounts($rootNodes);

        $lastMetaFieldKey = null;
        $sameMetaFieldIndex = 0;

        foreach ($rootNodes as $node) {
            $metaFieldKey = static::getMetaFieldKeyFromNode($node);
            $sameMetaFieldIndex = ($metaFieldKey && $metaFieldKey === $lastMetaFieldKey) ? $sameMetaFieldIndex + 1 : 0;
            $lastMetaFieldKey = $metaFieldKey;

            $jsonDescription = $node->describeUsing($repoItem, 'json');
            $rowData[] = static::resolveCellValue($jsonDescription, $metaFieldKey, $sameMetaFieldIndex, $metaFieldColumnCounts);
        }

        return json_encode($rowData);
    }

    /**
     * Build a map of (MetaFieldID:SubMetaFieldID) => number of CSV columns configured for that metafield.
     */
    private static function getMetaFieldColumnCounts($rootNodes): array {
        $counts = [];
        foreach ($rootNodes as $node) {
            $key = static::getMetaFieldKeyFromNode($node);
            if (!$key) {
                continue;
            }
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        return $counts;
    }

    /**
     * Return a stable key for a node's MetaField/SubMetaField pair, or null when the node isn't metafield-backed.
     */
    private static function getMetaFieldKeyFromNode($node): ?string {
        $metaFieldId = (int)($node->MetaFieldID ?? 0);
        if ($metaFieldId <= 0) {
            return null;
        }
        $subMetaFieldId = (int)($node->SubMetaFieldID ?? 0);
        return $metaFieldId . ':' . $subMetaFieldId;
    }

    /**
     * Resolve a protocol node's JSON description into the scalar value that should go in a single CSV cell.
     *
     * - If the description is an array and there is only 1 CSV column for this MetaField, flatten all values into one cell.
     * - If the description is an array and there are multiple CSV columns, pick the Nth value based on node order
     * - If the description is scalar, only the first column for a repeated MetaField gets a value.
     */
    private static function resolveCellValue(mixed $jsonDescription, ?string $metaFieldKey, int $sameMetaFieldIndex, array $metaFieldColumnCounts): string {
        if (is_array($jsonDescription)) {
            $columnCount = $metaFieldKey ? ($metaFieldColumnCounts[$metaFieldKey] ?? 0) : 0;
            if ($columnCount <= 1) {
                return static::flattenMultiValueDescription($jsonDescription);
            }
            return static::stringifyCsvValue(static::pickIndexedArrayValue($jsonDescription, $sameMetaFieldIndex));
        }

        // Scalar values: only show in the first column of a repeated MetaField.
        if ($sameMetaFieldIndex > 0) {
            return '';
        }
        return static::stringifyCsvValue($jsonDescription);
    }

    /**
     * Retrieves a value from an indexed array based on the given index.
     *
     * @param array $jsonDescription The input associative array to extract values from.
     * @param int $index The index of the desired value in the array values.
     *
     * @return mixed The value at the specified index, or null if the index is not found.
     */
    private static function pickIndexedArrayValue(array $jsonDescription, int $index): mixed {
        $values = array_values($jsonDescription);
        return $values[$index] ?? null;
    }

    /**
     * Flattens a multi-value JSON description array into a single string using a specified separator.
     *
     * @param array $jsonDescription The multi-value description array to be flattened.
     * @return string A flattened string representation of the given multi-value description.
     */
    private static function flattenMultiValueDescription(array $jsonDescription): string {
        $values = [];
        foreach (array_values($jsonDescription) as $desc) {
            $values[] = static::stringifyCsvValue($desc);
        }
        $values = array_values(array_filter($values, fn($v) => $v !== ''));
        return count($values) ? implode(self::MULTIVALUE_SEPARATOR, $values) : '';
    }

    /**
     * Convert the protocol node "json" description into a scalar value suitable for a CSV cell.
     */
    private static function stringifyCsvValue(mixed $desc): string {
        if ($desc === null) {
            return '';
        }

        // ProtocolNode may return a structure like ['@' => attrs, '#' => value] when node attributes exist.
        if (is_array($desc) && array_key_exists('#', $desc) && (array_key_exists('@', $desc) || count($desc) <= 2)) {
            return static::stringifyCsvValue($desc['#']);
        }

        if (is_array($desc)) {
            return static::stringifyArrayValue($desc);
        }

        if (is_bool($desc)) {
            return $desc ? '1' : '0';
        }

        return (string)$desc;
    }

    /**
     * Converts an associative or indexed array into a string representation, with each key-value
     * pair formatted and separated by new line characters (\r\n). Numeric keys will only include
     * the value, while string keys will be formatted as "key:value".
     *
     * @param array $desc The input array containing keys and values to be converted to a string.
     * @return string The resulting string representation of the array.
     */
    private static function stringifyArrayValue(array $desc): string {
        $lines = [];
        foreach ($desc as $key => $value) {
            $valueStr = static::stringifyCsvValue($value);
            if ($valueStr === '') {
                continue;
            }
            // Numeric arrays / unkeyed values: just use the value.
            if (is_int($key)) {
                $lines[] = $valueStr;
                continue;
            }
            $keyStr = (string)$key;
            $lines[] = ($keyStr !== '') ? ($keyStr . ':' . $valueStr) : $valueStr;
        }
        return implode("\r\n", $lines);
    }
}