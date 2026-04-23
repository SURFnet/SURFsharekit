<?php

use PHPUnit\Framework\Constraint\Constraint;

class MetaFieldTestValue
{
    private $value;
    private Constraint $assertAs;

    public function __construct($value, Constraint $assertAs) {
        $this->value = $value;
        $this->assertAs = $assertAs;
    }

    /**
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @return Constraint
     */
    public function getAssertAs(): Constraint {
        return $this->assertAs;
    }
}