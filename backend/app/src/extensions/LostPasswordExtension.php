<?php

use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Helper\Constants;

/**
 * Class LostPasswordExtension
 * @package SurfSharekit\Models
 * This class ensures not all frontend users can login
 */
class LostPasswordExtension extends Extension {
    function forgotPassword(Member $member) {
        if ($member->isDefaultAdmin()) {
            return true;
        } else if ($member->Groups()->filter('Roles.Title', RoleConstant::WORKSADMIN)->count() > 0) {
            return true;
        }
        return false;
    }
}