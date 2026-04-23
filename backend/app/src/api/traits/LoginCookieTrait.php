<?php

namespace SurfSharekit\Api\Traits;

use SilverStripe\Core\Environment;
use SurfSharekit\Models\LogItem;
use const SurfSharekit\Models\AUTHENTICATION_LOG;

trait LoginCookieTrait {
    /**
     * Sets the authentication cookie for the logged in user
     * 
     * @param string $token The authentication token to set
     * @return void
     */
    protected function setAuthenticationCookie(string $token): void {
        // Only use Secure flag in production
        $isSecure = Environment::getEnv('APPLICATION_ENVIRONMENT') === 'live';
        
        LogItem::debugLog([
            'Setting cookie with:',
            'token value: ' . $token,
            'environment: ' . Environment::getEnv('APPLICATION_ENVIRONMENT'),
            'isSecure: ' . ($isSecure ? 'true' : 'false')
        ], static::class, __FUNCTION__, AUTHENTICATION_LOG);

        // Set cookie directly in response
        $response = $this->getResponse();
        $expiry = time() + (86400 * 1); // 1 day in seconds

        $domainParts = explode(':', $_SERVER['HTTP_HOST']);
        $domain = $domainParts[0];
        $cookieString = sprintf(
            'sharekit-access-token=%s; Path=/api/; Expires=%s; HttpOnly; SameSite=%s%s',
            $token,
            gmdate('D, d M Y H:i:s \G\M\T', $expiry),
            'None',
            '; Secure'
        );

        // Only add Domain if not running on localhost or 127.0.0.1, and domain does not include a port
        if ($domain !== 'localhost' && $domain !== '127.0.0.1') {
            $cookieString = sprintf(
                'sharekit-access-token=%s; Path=/api/; Domain=%s; Expires=%s; HttpOnly; SameSite=%s%s',
                $token,
                $domain,
                gmdate('D, d M Y H:i:s \G\M\T', $expiry),
                'None',
                '; Secure'
            );
        }

        $response->addHeader('Set-Cookie', $cookieString);

        // Log response headers to verify cookie is being set
        $headers = $response->getHeaders();
        LogItem::debugLog([
            'Response headers after setting cookie:',
            'headers: ' . json_encode($headers),
            'cookie domain: ' . $domain,
            'request path: ' . $_SERVER['REQUEST_URI'],
            'request host: ' . $_SERVER['HTTP_HOST']
        ], static::class, __FUNCTION__, AUTHENTICATION_LOG);
    }
} 