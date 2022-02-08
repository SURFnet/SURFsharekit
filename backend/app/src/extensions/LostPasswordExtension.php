<?php

use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;
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
        } else if ($member->Groups()->filter('Roles.Title', Constants::TITLE_OF_WORKSADMIN_ROLE)->count() > 0) {
            return true;
        }
        return false;
    }
}