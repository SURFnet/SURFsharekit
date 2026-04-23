<?php

namespace SurfSharekit\Extensions;

class Security extends \SilverStripe\Security\Security
{
    public function getAuthenticators(): array|null {
        $return = null;
        if (getenv('APPLICATION_ENVIRONMENT') !== 'dev') {
            $authenticators = parent::getAuthenticators();

            $return = [
                'oauth' => $authenticators['oauth']
            ];
        }
        return $return;
    }
}
