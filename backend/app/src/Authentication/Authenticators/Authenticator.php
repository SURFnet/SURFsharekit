<?php

namespace SilverStripe\Authentication\Authenticators;

use SilverStripe\Authentication\AuthenticationResult;

abstract class Authenticator {
    function authenticate(string $clientId, string $clientSecret, array $postVars = []): AuthenticationResult {
        $authenticationResult = $this->provideAuthenticationResult();

        $this->beforeAuthentication($postVars);
        $result = $this->performAuthentication($clientId, $clientSecret, $postVars, $authenticationResult);
        $this->afterAuthentication($postVars, $result);

        return $result;
    }

    abstract protected function provideAuthenticationResult(): AuthenticationResult;

    abstract protected function performAuthentication(string $clientId, string $clientSecret, array $postVars, AuthenticationResult $result): AuthenticationResult;
    protected function beforeAuthentication(array $postVars) {}
    protected function afterAuthentication(array $postVars, AuthenticationResult &$result) {}
}