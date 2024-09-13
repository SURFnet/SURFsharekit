<?php

namespace SilverStripe\Authentication;

class ClientAuthenticationResult extends AuthenticationResult {

    private ?Client $client = null;

    public function isSuccess(): bool {
        return $this->success;
    }

    public function getClient(): ?Client {
        return $this->client;
    }

    public function setClient(Client $client): void {
        $this->client = $client;
        $this->success = true;
    }

}