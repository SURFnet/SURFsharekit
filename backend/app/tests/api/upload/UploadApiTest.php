<?php

class UploadApiTest extends \SilverStripe\Dev\FunctionalTest
{
    private ?string $accessToken = null;

    public function authenticate() {
        $response = $this->post('/api/upload/v1/auth/token', [
            "client_id" => "54bc549871f6b4d95fd85db7a473f071",
            "client_secret" => "749241adf9afeabd4bb1a17eed3858af3757870f3a25fa62c003985cf8e136b5",
            "grant_type" => "client_credentials",
            "institute" => "9bc007df-82c3-4bcb-9b94-1dfd5d77f9ca"
        ]);

        $this->setAccessToken(json_decode($response->getBody(), true)['accessToken']);
    }

    public function post($url, $data, $headers = null, $session = null, $body = null, $cookies = null) {
        if ($this->getAccessToken()) {
            $headers['Authorization'] = "Bearer " . $this->getAccessToken();
        }

        $headers['Content-Type'] = 'application/json';

        return parent::post($url, $data, $headers, $session, $body, $cookies);
    }

    public function getAccessToken(): ?string {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void {
        $this->accessToken = $accessToken;
    }

}