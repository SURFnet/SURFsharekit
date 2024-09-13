<?php

namespace SurfSharekit\Models;

use Exception;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Group;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Helper\Logger;

/**
 * Class InstituteGroupExtension
 * @package SurfSharekit\Models
 * Extension for the Group DataObject of Silverstripe to connect it to an Insitute, this way members can be put not into institutes, but in groups of an institute
 */
class InstituteGroupExtension extends DataExtension {
    private static $has_one = [
        'Institute' => Institute::class
    ];

    private static $belongs_many_many = [
        'AutoAddedConsortiums' => Institute::class
    ];

    private static $indexes = [
        'FulltextSearch' => [
            'type' => 'fulltext',
            'columns' => ['Title']
        ]
    ];

    public function updateSummaryFields(&$fields) {
        //    parent::updateSummaryFields($fields);
        $fields = [
            'Title' => 'Title',
            'Institute.Title' => 'Institute'
        ];
    }

    public function updateCMSFields(FieldList $fields) {
        parent::updateCMSFields($fields);
        $member = Security::getCurrentUser();
        if (!Permission::checkMember($member, 'ADMIN')) {
            $fields->removeByName('ParentID');
        }
        $instituteField = new DropdownField('InstituteID', 'Institute', Institute::get()->map(), $this->owner->InstituteID);
        $instituteField->setHasEmptyDefault(true);
        if ($this->owner->InstituteID) {
            $instituteField->setDisabled(true);
        }
        $fields->insertBefore('Title', $instituteField);
    }

    /**
     *
     * As default, a group will be made for the scope of the current logged in user
     */
    public function populateDefaults() {
        parent::populateDefaults();

        $member = Security::getCurrentUser();
        if ($member && ($this->getOwner()->InstituteID == 0 || !$this->getOwner()->Institute->Exists())) {
            $this->getOwner()->InstituteID = $member->InstituteID;
        }
    }

    /**
     * @throws Exception
     * This made makes sure the group is connected to an institute and no two groups with the same role exists for a single institute
     */
    public function onBeforeWrite() {
        $institute = $this->getOwner()->Institute();
        if (!$institute) {
            throw new Exception("Please connect this group to an Institute");
        }
    }

    public function getAmountOfPersons() {
        return $this->owner->Members()->count();
    }

    public function getRoleCode() {
        $mainRole = $this->owner->Roles()->filter(["Key" => RoleConstant::MAIN_ROLES])->first();
        return $mainRole ? $mainRole->Key : RoleConstant::MEMBER;
    }
}