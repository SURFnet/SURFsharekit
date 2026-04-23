<?php

namespace SilverStripe\processors\Blueprint;

abstract class BlueprintConverterProcessor
{
    /** converts a blueprint object to the designated Dataobject
     * @param $blueprintObject
     * @return mixed
     */
    abstract public function convert($blueprintObject);

    /** Get the Dataobject for conversion
     * @return mixed
     */
    abstract public function getTargetClass();
}