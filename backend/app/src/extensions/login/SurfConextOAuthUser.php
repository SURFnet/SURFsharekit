<?php

namespace SurfSharekit\extensions\login;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class SurfConextOAuthUser implements ResourceOwnerInterface
{

    private array $response;

    public function __construct(array $response) {
        $this->response = $response;
    }

    public function getId() {
        return $this->response['sub'];
    }

    public function getFirstName() {
        return $this->response['given_name'];
    }

    public function getSurname() {
        return $this->response['family_name'];
    }

    public function getEmail() {
        return $this->response['email'];
    }

    public function toArray() {
        return [
            'FirstName' => $this->getFirstName(),
            'Surname' => $this->getSurname(),
            'Email' => $this->getEmail()
        ];
    }
}