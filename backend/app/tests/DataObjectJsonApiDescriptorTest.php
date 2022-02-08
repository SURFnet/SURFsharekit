<?php

use SilverStripe\Dev\SapphireTest;
use SurfSharekit\Models\TemplateMetaField;

class DataObjectJsonApiDescriptorTest extends SapphireTest {
    var $dataObject;
    var $dataObjectDescriptor;
    var $dataObjectMap;
    /**
     * @var DataObjectJsonApiEncoder
     */
    var $descriptor;

    protected function setUp() {
        $this->dataObject = TemplateMetaField::get()->first();
        $this->dataObjectDescriptor = new TemplateMetaFieldJsonApiDescription();
        $this->dataObjectMap = $this->dataObject->toMap();
        $this->descriptor = new DataObjectJsonApiEncoder(['templateMetaFields']);
        return parent::setUp();

    }
}