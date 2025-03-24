<?php

namespace SurfSharekit\Models;

use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataObject;

/**
 * Class ProtocolFilter
 * @package SurfSharekit\Models
 * @method MetaField MetaField
 * DataObject representing a single value added to a @see Protocol
 */
class ProtocolFilter extends DataObject {
    private static $table_name = 'SurfSharekit_ProtocolFilter';

    private static $db = [
        'Title' => 'Varchar(255)',
        'VirtualMetaField' => "Enum(
        'dii:Identifier,
        dcterms:modified,
        mods:namePart:family,
        mods:namePart:given,
        mods:displayForm,
        lom:languageString,
        lom:Identifier,
        lom:encaseInStringNode,
        vCard,
        lom:technical,
        didl:resource:file,didl:resource:link,mods:genre:thesis,hbo:namePart:departmentFromLowerInstitute,dai:identifier,mods:name:personal,orcid:identifier',null)",
        'RepoItemAttribute' => "Enum('RepoType', null)"
    ];

    private static $has_one = [
        'Protocol' => Protocol::class,
        'MetaField' => MetaField::class,
        'ChildMetaField' => MetaField::class
    ];

    private static $has_many = [

    ];

    private static $summary_fields = [
        'Title'
    ];

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        /** @var DropdownField $virtualMetaField */
        $virtualMetaField = $fields->dataFieldByName('VirtualMetaField');
        $virtualMetaField->setEmptyString('Select a virtual metafield');
        $virtualMetaField->setHasEmptyDefault(true);

        $repoItemAttributeDropdown = $fields->dataFieldByName('RepoItemAttribute');
        $repoItemAttributeDropdown->setHasEmptyDefault(true);

        return MetaField::ensureDropdownField($this, $fields, 'ChildMetaFieldID', 'ChildMetaField');
    }

    public function canCreate($member = null, $context = []) {
        return true;
    }

    public function canEdit($member = null) {
        return true;
    }

    public function canView($member = null) {
        return true;
    }
}