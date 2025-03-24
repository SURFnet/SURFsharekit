<?php

namespace SurfSharekit\Piwik\Api;

use Closure;

class PiwikFilter {

    private string $operator;
    private Closure $closure;

    public function __construct(
        string  $operator,
        Closure $closure
    ) {
        $this->operator = $operator;
        $this->closure = $closure;
    }

    public function toArray() {
        ($this->closure)($this);

        $conditions = [];
        foreach ($this->getConditions() as $registeredCondition) {
            $conditions[] = $registeredCondition->toArray();
        }

        if (empty($conditions)) {
            throw new Exception("Conditions can not be empty, please return the filter in the given closure.");
        }

        return [
            "operator" => $this->getOperator(),
            "conditions" => $conditions
        ];
    }

    private function getOperator(): string {
        return $this->operator;
    }

    private function getConditions(): array {
        return $this->conditions;
    }

    public function andFilter(Closure $closure): self {
        $this->conditions[] = (new PiwikFilter("and", $closure));

        return $this;
    }

    public function orFilter(Closure $closure): self {
        $this->conditions[] = (new PiwikFilter("or", $closure));

        return $this;
    }

    public function filter(string $columnId, string $operator, string $value): self {
        $this->conditions[] = (new PiwikColumnFilter($columnId, $operator, $value));

        return $this;
    }
}