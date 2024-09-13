<?php

namespace SilverStripe\Authentication\Authenticators;

use SilverStripe\Authentication\AuthenticationResult;
use SilverStripe\Authentication\ClientAuthenticationResult;

abstract class ClientAuthenticator extends Authenticator {

    protected function provideAuthenticationResult(): AuthenticationResult {
        return new ClientAuthenticationResult();
    }
}