<?php

namespace SurfSharekit\Models;

use Exception;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Group;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
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
        if ($this->owner->InstituteID) {
            $instituteField->setDisabled(true);
        }
        $fields->insertBefore('Title', $instituteField);
    }

    public function onAfterWrite() {
        parent::onAfterWrite();
        if (!$this->owner->isChanged('ID')) {
            $this->ensureGroupsExists();
        }
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

        if ($this->owner->isInDB()) {
            $this->ensureGroupsExists();
//            $membersWithoutGroup = Person::get()->leftJoin('Group_Members', 'MemberID = Member.ID')->where('GroupID IS NULL');
//            //filter admin
//            $membersWithoutGroup = $membersWithoutGroup->where('Member.ID != 1')->filter('HasLoggedIn', 1);
//            if ($membersWithoutGroup->count() > 0) {
//                throw new Exception('Cannot have a member without a group');
//            }
        }
    }

    public function getAmountOfPersons() {
        return $this->owner->Members()->count();
    }

    public function getRoleCode() {
        foreach ($this->owner->Roles() as $role) {
            return $role->Title;
        }
        return null;
    }

    private function ensureGroupsExists() {
        //check if another group of this institute already had a conext role attributed to it
        $rolesAttributedToThisGroup = $this->getOwner()->Roles();
        foreach (Group::get()->filter('InstituteID', $this->owner->InstituteID)->toArray() as $otherGroup) {
            if ($otherGroup->ID != $this->getOwner()->ID) {
                foreach ($rolesAttributedToThisGroup as $roleOfThisGroup) {
                    foreach ($otherGroup->Roles() as $rolesOfOtherGroup) {
                        if ($rolesOfOtherGroup->ID == $roleOfThisGroup->ID) {
                            Logger::debugLog($rolesOfOtherGroup->ID . ',' . $roleOfThisGroup->ID);
                            throw new Exception("Institute already has a group for this role ( " . $otherGroup->Title . " & " . $this->owner->Title . ")");
                        }
                    }
                }
            }
        }
    }
}