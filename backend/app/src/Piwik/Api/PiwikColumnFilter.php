<?php

namespace SurfSharekit\Piwik\Api;
class PiwikColumnFilter
{
    public function __construct(
        string $columnId,
        string $operator,
        string $value
    )
    {
        $this->columnId = $columnId;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function toArray()
    {
        return [
            "column_id" => $this->columnId,
            "condition" => [
                "operator" => $this->operator,
                "value" => $this->value
            ]
        ];
    }
}