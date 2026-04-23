<?php

use SilverStripe\Admin\LeftAndMainExtension;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;

class EnvironmentBannerLeftAndMainExtension extends LeftAndMainExtension {
    public function isLive() {
        return Environment::getEnv('APPLICATION_ENVIRONMENT') == 'live';
    }

    public function isDev() {
        return Environment::getEnv('APPLICATION_ENVIRONMENT') == 'dev';
    }

    public function isTest() {
        return Environment::getEnv('APPLICATION_ENVIRONMENT') == 'test';
    }

    public function isStaging() {
        return Environment::getEnv('APPLICATION_ENVIRONMENT') == 'staging';
    }

    public function isAcceptance() {
        return Environment::getEnv('APPLICATION_ENVIRONMENT') == 'acc';
    }
}