<?php

namespace SurfSharekit\Models;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Group;
use SilverStripe\Security\PermissionRole;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Helper\Logger;

class PermissionRoleExtension extends DataExtension {
    private static $db = [
        'Label_NL' => 'Varchar(255)',
        'Label_EN' => 'Varchar(255)',
        'UpdateGroupLabels' => 'Boolean',
        'Key' => 'Varchar(255)',
        'IsDefault' => 'Boolean(0)',
    ];

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        //Hasmany relationship is written before onbefore write
        Logger::debugLog("Permission role changed");
        foreach (Group::get()->filter('Roles.ID', $this->owner->ID) as $group) {
            $group->removeCachedPermissions();
        }

        if($this->owner->isChanged('Label_NL') || $this->owner->isChanged('Label_EN')) {
            // only execute if $this is certain roles
            $this->owner->UpdateGroupLabels = true;
        }
    }

    public function onAfterDelete() {
        Logger::debugLog("Permission role deleted ".$this->owner->ID);
        foreach (Group::get()->filter('Roles.ID', $this->owner->ID) as $group) {
            $group->removeCachedPermissions();
        }
    }

    public function updateCMSFields(FieldList $fields) {
        parent::updateCMSFields($fields);
        if ($this->owner->isInDB()) {
            $fields->makeFieldReadonly('Title');
        }

        $subRoles = array_combine(RoleConstant::SUB_ROLES, RoleConstant::SUB_ROLES);
        $mainRoles = array_combine(RoleConstant::DEFAULT_INSTITUTE_ROLES, RoleConstant::DEFAULT_INSTITUTE_ROLES);
        $allRoleKeyOptions = array_merge($subRoles, $mainRoles);

        $usedRoleKeys = PermissionRole::get()->filter(['Key' => $allRoleKeyOptions])->exclude("Key", $this->owner->Key)->column("Key");

        $keyDropdownField = new DropdownField("Key", "Key", $allRoleKeyOptions);
        $keyDropdownField->setEmptyString("-- Select a default role key --");
        $keyDropdownField->setDescription("Only select a value if this is a default role");
        $keyDropdownField->setDisabledItems($usedRoleKeys);
        $fields->replaceField("Key", $keyDropdownField);

        $fields->replaceField("IsDefault", $fields->dataFieldByName("IsDefault")->performReadonlyTransformation());
        if ($this->owner->IsDefault) {
            $fields->replaceField("Key", $fields->dataFieldByName("Key")->performReadonlyTransformation());
        }

        $fields->dataFieldByName('UpdateGroupLabels')->setDisabled(true);
        $labelNL = $fields->dataFieldByName('Label_NL');
        $labelEN = $fields->dataFieldByName('Label_EN');
        $fields->insertAfter('Title', $labelNL);
        $fields->insertAfter('Label_NL', $labelEN);
        return $fields;
    }
}