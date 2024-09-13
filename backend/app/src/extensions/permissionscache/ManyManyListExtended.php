<?php

/**
 * Source: https://stackoverflow.com/questions/32324867/update-a-field-after-linking-unlinking-many-many-records-in-silverstripe
 */

use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Group;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;

/**
 * When adding or removing elements on a many to many relationship
 * neither side of the relationship is updated (written or deleted).
 * SilverStripe does not provide any built-in actions to get information
 * that such event occurs. This is why this class is created.
 *
 * When it is uses together with SilverStripe Injector mechanism it can provide
 * additional actions to run on many-to-many relations (see: class ManyManyList).
 */
class ManyManyListExtended extends ManyManyList {
    /**
     * Overwritten method for adding new element to many-to-many relationship.
     *
     * This is called for all many-to-many relationships combinations.
     * 'joinTable' field is used to make actions on specific relation only.
     *
     * @param mixed $item
     * @param null $extraFields
     * @throws Exception
     */

    private const ACTION_REMOVE = 'REMOVE';
    private const ACTION_ADD = 'ADD';

    public function add($item, $extraFields = null) {
        parent::add($item, $extraFields);
        if ($this->isChangingGroupPermissions()) {
            $this->deleteGroupPermissions($item);
        }

        if ($this->isChangingGroupMembers()) {
            $groupID = $this->getMembershipID($item, 'GroupID');
            $memberID = $this->getMembershipID($item, 'MemberID');
            $this->editMemberRootInstitutes(self::ACTION_ADD, $groupID, $memberID);
        }
    }

    /**
     * Overwritten method for removing item from many-to-many relationship.
     *
     * This is called for all many-to-many relationships combinations.
     * 'joinTable' field is used to make actions on specific relation only.
     */
    public function remove($item) {
        parent::remove($item);
        if ($this->isChangingGroupPermissions()) {
            $this->deleteGroupPermissions($item);
        }
    }

    public function removeByID($itemID) {
        parent::removeByID($itemID);
        if ($this->isChangingGroupMembers()) {
            if($this->getForeignKey() === "MemberID") {
                $memberID = $this->getForeignID();
                $groupID = $itemID;
            } else {
                $memberID = $itemID;
                $groupID = $this->getForeignID();
            }
            $this->editMemberRootInstitutes(self::ACTION_REMOVE, $groupID, $memberID);
        }
    }

    private function isChangingGroupPermissions() {
        return $this->getJoinTable() === 'Group_Roles';
    }

    private function isChangingGroupMembers() {
        return $this->getJoinTable() === 'Group_Members';
    }

    /**
     * Get the actual ID for many-to-many relationship part - local or foreign key value.
     *
     * This works both ways: make action on a Member being element of a Group OR
     * make action on a Group being part of a Member.
     */
    private function getMembershipID($item, $keyName) {
        if ($this->getLocalKey() === $keyName)
            return is_object($item) ? $item->ID : $item;
        if ($this->getForeignKey() === $keyName)
            return $this->getForeignID();
        return false;
    }

    private function deleteGroupPermissions($item) {
        $groupID = $this->getMembershipID($item, 'GroupID');
        if ($groupID && ($group = Group::get_by_id($groupID)) && $group) {
            $group->removeCachedPermissions();
        }
    }

    /**
     * This function checks if the group belonging to this join table entry belongs to a root institute.
     * If it does, the RootInstitutes relation on Person is updated according to the action on this entry (add or remove)
    */
    private function editMemberRootInstitutes(string $actionType, $groupID, $memberID) {
        if ($groupID && ($group = Group::get_by_id($groupID)) && $group) {
            $institute = Institute::get_by_id($group->InstituteID);
            if ($institute) {
                $rootInstitute = $institute->getRootInstitute();
                if ($rootInstitute) {
                    if ($memberID && ($person = Person::get_by_id($memberID)) && $person) {
                        switch ($actionType) {
                            case self::ACTION_ADD: {
                                $memberRootInstituteIDs = $person->RootInstitutes()->getIDList();
                                if (!in_array($rootInstitute->ID, $memberRootInstituteIDs)) {
                                    $person->RootInstitutes()->add($rootInstitute);
                                }
                                break;
                            }
                            case self::ACTION_REMOVE: {
                                if (!in_array($rootInstitute->ID, $person->Groups()->column('InstituteID'))) {
                                    $person->RootInstitutes()->remove($rootInstitute);
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
}