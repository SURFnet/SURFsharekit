<?php

namespace SurfSharekit\Piwik\Api;
class PiwikAccessToken {
    private int $expiresIn;
    private string $accessToken;
    private int $expiresAt;

    public function __construct(
        int    $expiresIn,
        string $accessToken,
        int    $expiresAt
    ) {
        $this->expiresIn = $expiresIn;
        $this->accessToken = $accessToken;
        $this->expiresAt = $expiresAt;
    }

    public static function fromJson(array $json): self {
        $expiresIn = $json['expires_in'];
        return new self($expiresIn, $json['access_token'], time() + $expiresIn);
    }

    public function getExpiresIn(): int {
        return $this->expiresIn;
    }

    public function getAccessToken(): string {
        return $this->accessToken;
    }

    public function getExpiresAt(): int {
        return $this->expiresAt;
    }

}