<?php

use SilverStripe\Admin\LeftAndMainExtension;
use SilverStripe\Control\Director;

class EnvironmentBannerLeftAndMainExtension extends LeftAndMainExtension {
    public function isLive() {
        return Director::isLive();
    }

    public function isDev() {
        return Director::isDev();
    }

    public function isTest() {
        return Director::isTest();
    }

    public function isStaging() {
        return Director::get_environment_type() === 'staging';
    }

    public function isAcceptance() {
        return Director::get_environment_type() === 'acceptance';
    }
}