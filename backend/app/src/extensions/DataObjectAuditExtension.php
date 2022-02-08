<?php


namespace SurfSharekit\Models;


use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

class DataObjectAuditExtension extends DataExtension {

    private static $has_one = [
        "CreatedBy" => Member::class,
        "ModifiedBy" => Member::class
    ];

    public function updateCMSFields(FieldList $fields) {
        parent::updateCMSFields($fields);
        $fields->removeByName('CreatedByID');
        $fields->removeByName('ModifiedByID');
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        $member = Security::getCurrentUser();
        if($member) {
            if (!$this->owner->CreatedByID) {
                $this->owner->CreatedByID = $member->ID;
            }
            $this->owner->ModifiedByID = $member->ID;
        }
    }
}