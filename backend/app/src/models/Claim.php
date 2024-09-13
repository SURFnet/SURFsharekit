<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\NotificationEventCreator;
use UuidExtension;

class Claim extends DataObject {
    private static $table_name = 'SurfSharekit_Claim';

    private static $db = [
        'Status' => 'Enum(array("Submitted", "Approved", "Declined"), "Submitted")',
        'ReasonOfDecline' => 'Text'
    ];

    private static $has_one = [
        'Object' => DataObject::class, // object to claim
        'Institute' => Institute::class // context to claim object for
    ];

    protected function onAfterWrite() {
        parent::onAfterWrite();

        if ($this->isChanged('ID') || $this->isChanged('Status')) {
            NotificationEventCreator::getInstance()->create(Constants::CLAIM_STATUS_CHANGED_EVENT, $this);

            if ($this->Status == 'Approved') {
                $this->handleClaimApproval();
            }
        }

        if($this->isChanged('ID')) {
            TaskCreator::getInstance()->createClaimTask($this);
        }
    }

    public function getPersonsToEditClaim() {
        $permissionsChecks[] = "(Permission.Code = 'PERSON_CLAIM_OTHER' OR PermissionRoleCode.Code = 'PERSON_CLAIM_OTHER')";
        return Person::get()
            ->innerJoin('Group_Members', 'Group_Members.MemberID = SurfSharekit_Person.ID')
            ->innerJoin('Group', 'Group_Members.GroupID = Group.ID')
            ->innerJoin('(' . InstituteScoper::getInstitutesOfUpperScope($this->Object()->extend('getInstituteIdentifiers')[0])->sql() . ')', 'gi.ID = Group.InstituteID', 'gi')
            //get parents of groups
            ->leftJoin('Group_Roles', 'Group_Roles.GroupID = Group.ID')
            //join on permissions
            ->leftJoin('PermissionRoleCode', 'PermissionRoleCode.RoleID = Group_Roles.PermissionRoleID')
            ->leftJoin('Permission', 'Permission.GroupID = Group_Roles.GroupID')
            ->whereAny($permissionsChecks);
    }

    public function canView($member = null) {
        if ($member) {
            return parent::canView($member) || $member->ID == $this->CreatedByID || $this->getPersonsToEditClaim()->filter('ID', $member->ID)->count();
        }
        return parent::canView($member);
    }

    public function canEdit($member = null) {
        if ($member) {
            return parent::canEdit($member) || $member->ID == $this->CreatedByID || $this->getPersonsToEditClaim()->filter('ID', $member->ID)->count();
        }
        return parent::canEdit($member);
    }

    function getObjectUuid() {
        return $this->Object()->Uuid;
    }

    public function handleClaimApproval() {
        $personToClaim = $this->Object();
        $claimInstitute = $this->Institute();
        $groupToAddPersonTo = $claimInstitute->getRootInstitute()->Groups()->filter(['Roles.Title' => RoleConstant::MEMBER])->first();
        $groupToAddPersonTo->Members()->add($personToClaim);
        $personToClaim->write();
    }

    public function canCreate($member = null, $context = []) {
        return Permission::check('PERSON_CLAIM_OTHER');
    }

    public function setPersonFromApi($value) {
        $this->ObjectClass = Person::class;
        $this->ObjectID = UuidExtension::getByUuid(Person::class, $value)->ID;
    }

    public function setInstituteFromApi($value) {
        $this->InstituteID = UuidExtension::getByUuid(Institute::class, $value)->ID;
    }

    public function populateDefaults() {
        parent::populateDefaults();
        $this->Status = 'Submitted';
        return $this;
    }
}
