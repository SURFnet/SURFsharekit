<?php

namespace SilverStripe\models\blueprints;

use PermissionProviderTrait;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;

class Blueprint extends DataObject implements PermissionProvider
{
    use PermissionProviderTrait;

    private static $table_name = 'SurfSharekit_Blueprint';

    private static $db = [
        'Title' => 'Varchar(255)',
        'JSON'  => 'Text'
    ];

    private static $json_description = 'This is a JSON view for %s.';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        if ($titleField = $fields->dataFieldByName('Title')) {
            $titleField->setDescription('This is the title of the blueprint. You can name this whatever you want.');
        }

        if ($jsonField = $fields->dataFieldByName('JSON')) {
            $desc = sprintf('This is a JSON view for %s.', $this->i18n_singular_name());
            $jsonField->setDescription($desc);
        }

        return $fields;
    }

    public function canCreate($member = null, $context = [])
    {
        if (parent::canCreate($member)) {
            return true;
        }
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isWorksAdmin()) {
            return true;
        }

        return false;
    }

    public function canEdit($member = null) {
        if (parent::canEdit($member)) {
            return true;
        }
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isWorksAdmin()) {
            return true;
        }

        return false;
    }

    public function canDelete($member = null) {
        if (parent::canDelete($member)) {
            return true;
        }
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isWorksAdmin()) {
            return true;
        }

        return false;
    }

    public function canView($member = null, $context = []) {
        if (parent::canView($member)) {
            return true;
        }
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isWorksAdmin()) {
            return true;
        }

        return false;
    }
}