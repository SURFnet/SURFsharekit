<?php

namespace SurfSharekit\Orcid\OAuth;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;

class OrcidResourceOwner implements ResourceOwnerInterface
{
    private array $response;
    private AccessToken $token;

    public function __construct(array $response, AccessToken $token)
    {
        $this->response = $response;
        $this->token = $token;
    }

    public function getId(): ?string
    {
        return $this->token->getValues()['orcid'] ?? null;
    }

    public function toArray(): array
    {
        return $this->response;
    }

    public function getName(): ?string
    {
        return $this->response['person']['name']['given-names']['value'] ?? null;
    }

    public function getFamilyName(): ?string
    {
        return $this->response['person']['name']['family-name']['value'] ?? null;
    }

    public function getFullName(): ?string
    {
        $given = $this->getName();
        $family = $this->getFamilyName();
        
        if ($given && $family) {
            return $given . ' ' . $family;
        }
        
        return $given ?: $family;
    }

    public function getOrcidId(): ?string
    {
        return $this->getId();
    }

    public function getEmails(): array
    {
        $emails = [];
        if (isset($this->response['person']['emails']['email'])) {
            foreach ($this->response['person']['emails']['email'] as $email) {
                if ($email['verified'] === true) {
                    $emails[] = $email['email'];
                }
            }
        }
        return $emails;
    }

    public function getAffiliations(): array
    {
        $affiliations = [];
        
        // Employment
        if (isset($this->response['activities-summary']['employments']['employment-summary'])) {
            foreach ($this->response['activities-summary']['employments']['employment-summary'] as $employment) {
                $affiliations[] = [
                    'type' => 'employment',
                    'organization' => $employment['organization']['name'] ?? null,
                    'role' => $employment['role-title'] ?? null,
                    'start_date' => $employment['start-date'] ?? null,
                    'end_date' => $employment['end-date'] ?? null
                ];
            }
        }

        // Education
        if (isset($this->response['activities-summary']['educations']['education-summary'])) {
            foreach ($this->response['activities-summary']['educations']['education-summary'] as $education) {
                $affiliations[] = [
                    'type' => 'education',
                    'organization' => $education['organization']['name'] ?? null,
                    'role' => $education['role-title'] ?? null,
                    'start_date' => $education['start-date'] ?? null,
                    'end_date' => $education['end-date'] ?? null
                ];
            }
        }

        return $affiliations;
    }
}