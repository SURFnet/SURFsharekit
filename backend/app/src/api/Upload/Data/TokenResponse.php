<?php

namespace SilverStripe\api\Upload\Data;

class TokenResponse {
    public string $access_token;
    public string $token_type = "Bearer";
    public int $expires_at;
}