<?php

namespace SurfSharekit\Models;

use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\ORM\DataObject;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchHandler;

class RepoItemUploadConfig extends DataObject {
    private static $table_name = 'SurfSharekit_RepoItemUploadConfig';

    private static $has_many = [
        'RepoItemUploadFields' => RepoItemUploadField::class
    ];


    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->removeByName('Main');
        $metaFieldOptionsGridField = $fields->dataFieldByName('RepoItemUploadFields');
        $metaFieldOptionsGridFieldConfig = $metaFieldOptionsGridField->getConfig();
        $metaFieldOptionsGridFieldConfig->removeComponentsByType([GridFieldAddExistingAutocompleter::class, GridFieldAddExistingSearchHandler::class]);
        return $fields;
    }

}
