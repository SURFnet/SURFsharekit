<?php

namespace SurfSharekit\Extensions;

class Security extends \SilverStripe\Security\Security
{
    public function getAuthenticators() {
        $authenticators = parent::getAuthenticators();

        return [
            'oauth' => $authenticators['oauth']
        ];
    }
}
