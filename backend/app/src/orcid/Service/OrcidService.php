<?php

namespace SurfSharekit\Orcid\Service;

use SilverStripe\Core\Injector\Injectable;
use SurfSharekit\Orcid\OAuth\OrcidProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;

class OrcidService
{

    private OrcidProvider $provider;
    private LoggerInterface $logger;
    private Client $httpClient;
    private string $environment;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $redirectUri,
        string $environment = 'sandbox',
        LoggerInterface $logger = null
    ) {
        $this->environment = $environment;
        $this->provider = new OrcidProvider([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $redirectUri,
            'environment' => $environment
        ]);
        
        $this->logger = $logger ?? new \Monolog\Logger('orcid');
        $this->httpClient = new Client();
    }

    public function getAuthorizationUrl(array $scopes = ['/authenticate']): string
    {
        $options = [
            'scope' => $scopes
        ];

        $authUrl = $this->provider->getAuthorizationUrl($options);
        
        // Store state in session for CSRF protection
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['oauth2state'] = $this->provider->getState();

        $this->logger->info('Generated ORCID authorization URL', [
            'scopes' => $scopes,
            'state' => $this->provider->getState()
        ]);

        return $authUrl;
    }

    public function handleCallback(string $code, string $state): AccessToken
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verify state for CSRF protection
        if (empty($_SESSION['oauth2state']) || $state !== $_SESSION['oauth2state']) {
            unset($_SESSION['oauth2state']);
            throw new \InvalidArgumentException('Invalid state parameter');
        }

        unset($_SESSION['oauth2state']);

        try {
            $accessToken = $this->provider->getAccessToken('authorization_code', [
                'code' => $code
            ]);

            $this->logger->info('Successfully obtained ORCID access token', [
                'orcid_id' => $accessToken->getValues()['orcid'] ?? null
            ]);

            return $accessToken;
        } catch (\Exception $e) {
            $this->logger->error('Failed to obtain ORCID access token', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getProfile(AccessToken $token): OrcidProfile
    {
        try {
            $resourceOwner = $this->provider->getResourceOwner($token);

            $profile = new OrcidProfile(
                orcidId: $resourceOwner->getOrcidId(),
                name: $resourceOwner->getFullName(),
                givenName: $resourceOwner->getName(),
                familyName: $resourceOwner->getFamilyName(),
                emails: $resourceOwner->getEmails(),
                affiliations: $resourceOwner->getAffiliations()
            );

            $this->logger->info('Retrieved ORCID profile', [
                'orcid_id' => $profile->orcidId
            ]);

            return $profile;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve ORCID profile', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}