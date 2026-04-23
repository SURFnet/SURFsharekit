<?php

namespace SurfSharekit\Orcid\OAuth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class OrcidProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    protected string $environment;
    
    // Add constants for better maintainability
    private const PRODUCTION_BASE_URL = 'https://orcid.org';
    private const SANDBOX_BASE_URL = 'https://sandbox.orcid.org';
    private const PRODUCTION_API_URL = 'https://pub.orcid.org/v3.0';
    private const SANDBOX_API_URL = 'https://pub.sandbox.orcid.org/v3.0';

    public function __construct(array $options = [], array $collaborators = [])
    {
        $this->environment = $options['environment'] ?? 'sandbox';
        
        // Validate environment
        if (!in_array($this->environment, ['production', 'sandbox'], true)) {
            throw new \InvalidArgumentException('Environment must be either "production" or "sandbox"');
        }
        
        parent::__construct($options, $collaborators);
    }

    public function getBaseAuthorizationUrl(): string
    {
        $baseUrl = $this->environment === 'production' 
            ? self::PRODUCTION_BASE_URL
            : self::SANDBOX_BASE_URL;
        
        return $baseUrl . '/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        $baseUrl = $this->environment === 'production'
            ? self::PRODUCTION_BASE_URL
            : self::SANDBOX_BASE_URL;
        
        return $baseUrl . '/oauth/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        $baseUrl = $this->environment === 'production'
            ? self::PRODUCTION_API_URL
            : self::SANDBOX_API_URL;
        
        $orcidId = $token->getValues()['orcid'] ?? '';
        
        // Validate ORCID ID format
        if (empty($orcidId) || !$this->isValidOrcidId($orcidId)) {
            throw new \InvalidArgumentException('Invalid or missing ORCID ID in token');
        }
        
        return $baseUrl . '/' . $orcidId . '/record';
    }

    protected function getDefaultScopes(): array
    {
        return ['/authenticate'];
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            $errorMessage = $data['error_description'] ?? $data['error'] ?? $response->getReasonPhrase();
            throw new IdentityProviderException(
                $errorMessage,
                $response->getStatusCode(),
                $response
            );
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token): OrcidResourceOwner
    {
        return new OrcidResourceOwner($response, $token);
    }
    
    /**
     * Returns the default headers used by this provider.
     * ORCID API requires Accept: application/json header to return JSON responses.
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
    
    /**
     * Validate ORCID ID format (XXXX-XXXX-XXXX-XXXX)
     */
    private function isValidOrcidId(string $orcidId): bool
    {
        return preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $orcidId) === 1;
    }
    
    /**
     * Get the environment being used
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }
}