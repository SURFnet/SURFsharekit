<?php

class MetaFieldTestValue
{
    private $value;
    private PHPUnit_Framework_Constraint $assertAs;

    public function __construct($value, PHPUnit_Framework_Constraint $assertAs) {
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
     * @return PHPUnit_Framework_Constraint
     */
    public function getAssertAs(): PHPUnit_Framework_Constraint {
        return $this->assertAs;
    }
}