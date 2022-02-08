<?php

use SilverStripe\Dev\SapphireTest;
use SurfSharekit\Models\Template;

class TemplateTest extends SapphireTest {
    var $template;

    protected function setUp() {
        $this->template = Template::get()->first();
        return parent::setUp();
    }

    public function testTemplate() {
        $this->assertNotNull($this->template, 'A template should exists for this tests to run');
    }

    public function testTemplateFields() {
        foreach ($this->template->TemplateMetaFields() as $templateMetaField) {
            $metaField = $templateMetaField->MetaField;
            $this->assertTrue($metaField->isInDB(), 'TemplateMetaFields\'s original MetaField should exist');

            $this->assertTrue($metaField->MetaFieldType->isInDB(), 'MetaField should have existing MetaFieldType');

            $mayHaveOptions = in_array($metaField->MetaFieldType->Key, ['Dropdown']);
            if (!$mayHaveOptions) {
                self::assertEquals($metaField->MetaFieldOptions()->count(), 0, 'MetaField with MetaFieldType = ' . $metaField->MetaFieldType->Title . ' can\'t have MetaFieldOptions');
            }
        }
    }
}