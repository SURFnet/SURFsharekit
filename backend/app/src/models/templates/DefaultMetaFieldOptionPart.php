<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Class DefaultMetaFieldOptionPart
 * @package SurfSharekit\Models
 * A DataObject representing the default value on a given @see TemplateMetaField
 */
class DefaultMetaFieldOptionPart extends DataObject {

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    private static $table_name = 'SurfSharekit_DefaultMetaFieldOptionPart';

    private static $db = [
        'Value' => 'Text'
    ];

    private static $has_one = [
        'TemplateMetaField' => TemplateMetaField::class,
        'MetaFieldOption' => MetaFieldOption::class,
        'Person' => Person::class,
        'Institute' => Institute::class,
        'RepoItemFile' => RepoItemFile::class,
        'RepoItem' => RepoItem::class
    ];

    private static $summary_fields = [
        'Title'
    ];

    public function getRelatedObjectSummary() {
        if (($repoItem = $this->RepoItem()) && $repoItem->exists()) {
            return $repoItem->Summary;
        } else if (($repoItemFile = $this->RepoItemFile()) && $repoItemFile->exists()) {
            // TODO, use $repoItemFile->Summary to get this object/array?
            return [
                'title' => $repoItemFile->Name,
                'url' => $repoItemFile->getStreamURL(),
                'size' => $repoItemFile->Size,
            ];
        } else if (($person = $this->Person()) && $person->exists()) {
            // TODO, use $person->Summary to get this object/array?
            return [
                'name' => $person->Name,
                'imageURL' => $person->PersonImage->getStreamURL()
            ];
        } else if (($institute = $this->Institute()) && $institute->exists()) {
            return $institute->Summary;
        } else if (($option = $this->MetaFieldOption()) && $option->exists()) {
            // TODO, use $metafieldOption->Summary to get this object/array?
            return [
                'value' => $option->Title
            ];
        }
        return null;
    }

    public function canCreate($member = null, $context = []) {
        return true;
    }

    public function canView($member = null) {
        return $this->TemplateMetaField()->Template()->canView($member);
    }

    public function canEdit($member = null) {
        return $this->TemplateMetaField()->Template()->canEdit($member);
    }

    public function getTitle() {
        return 'Default Value: ' . $this->MetaFieldOption->Title;
    }
}