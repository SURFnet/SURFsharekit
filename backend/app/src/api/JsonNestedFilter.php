<?php

namespace Zooma\SilverStripe\Models;

use SilverStripe\ORM\DataList;

/**
 * Base on: http://www.crnk.io/releases/stable/documentation/#_nested_filtering
 * without attribute filtering!!
 */

class JsonNestedFilter
{
    const AND_OPERATOR = "AND";
    const OR_OPERATOR = "OR";


    private $filter;
    private $allowedFilters = [];
    private $parsedFilter;
    private $params = [];
    private $query = "";

    public function __construct(array $allowedFilters, array $filter) {
        $this->filter = $filter;
        $this->allowedFilters = $allowedFilters;

        $this->query .= $this->parse($this->filter, self::AND_OPERATOR);
    }

    public function parse($filter = null, string $operator = null): string {
        $subQuery = "";

        $subFilters = [
            self::OR_OPERATOR => $filter[self::OR_OPERATOR] ?? null,
            self::AND_OPERATOR => $filter[self::AND_OPERATOR] ?? null
        ];

        unset($filter[self::OR_OPERATOR]);
        unset($filter[self::AND_OPERATOR]);

        if (count($filter) > 0) {
            $primaryQuery = $this->implodeSubQuery($operator ?? "AND", $filter);
        } else {
            $primaryQuery = "";
        }

        foreach ($subFilters as $reservedOperator => $subFilter) {
            if ($subFilter) {
                if ($this->isAssoc($subFilter) == false) {
                    throw new \Exception("Operator value should be associative array");
                }

                if ($subQuery !== "") {
                    $subQuery .= " $operator ";
                }

                $subQuery .= $this->parse($subFilter, $reservedOperator);
            }
        }

        if ($subQuery !== "" && count($filter) > 0) {
            $subQuery = "$operator " . $subQuery;
        }

        return "($primaryQuery $subQuery)";
    }

    private function implodeSubQuery(string $operator, array $filters): string {
        $preparedFilterValues = [];
        foreach ($filters as $column => $value) {
            $preparedFilterValues[] = $this->getStatement($column, $value);
            $this->addParam($this->prepareParam($value));
        }

        return implode("$operator ", $preparedFilterValues);
    }

    private function getStatement($key, $value): string {
        $statementKey = $this->getMappedKey($key);
        $statementOperator = is_array($value) ? "IN" : "=";
        $statementVariableWrapper = is_array($value) ? "(?)" : "?";

        return " $statementKey $statementOperator $statementVariableWrapper ";
    }

    function isAssoc(array $array) {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    private function prepareParam($param) {
        if (is_array($param)) {
            foreach ($param as $k => &$p) {
                if (is_string($param[$k])) {
                    $p = "'$param[$k]'";
                }
            }

            return implode(", ", $param);
        }

        return $param;
    }

    private function getMappedKey($key) {
        if (!array_key_exists($key, $this->allowedFilters)) {
            throw new \Exception("$key not in allowFilters");
        }

        return $this->allowedFilters[$key];
    }

    public function getQuery(): string {
        return $this->query;
    }

    public function getParams(): array {
        return $this->params;
    }

    public function addParam($param): void {
        $this->params[] = $param;
    }

    public static function filterDataList($allowedFilters, $filter, $dataList): DataList {
        $jsonNestedFilter = new JsonNestedFilter($allowedFilters, json_decode($filter, true));

        $query = $jsonNestedFilter->getQuery();
        $params = $jsonNestedFilter->getParams();

        return $dataList->where([$query => $params]);
    }
}