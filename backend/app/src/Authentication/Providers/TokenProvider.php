<?php

namespace SilverStripe\Authentication\Providers;

abstract class TokenProvider implements AuthProvider {

    protected int $token_life_time = 3600;

    abstract public function provideToken();

    abstract public static function validateToken(string $token);
}