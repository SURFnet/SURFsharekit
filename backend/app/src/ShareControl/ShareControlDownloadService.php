<?php

namespace SurfSharekit\ShareControl;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use SilverStripe\api\Exceptions\InternalServerErrorException;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\ForbiddenException;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Api\Exceptions\UnauthorizedException;
use SurfSharekit\Models\LogItem;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\ShareControl\ShareControlApiCommunicatorImpl;
use SurfSharekit\ShareControl\ShareControlApiException;

class ShareControlDownloadService {
    use Injectable;

    /**
     * Checks if the given URL is a valid Share Control URL.
     *
     * @param string $url The URL to be validated.
     * @return bool Returns true if the URL starts with one of the specified Share Control URL prefixes, false otherwise.
     */
    public function isShareControlUrl(string $url): bool {
        $url = trim($url);

        return str_starts_with($url, 'https://api.acc.nlbron.nl/nlbron-surf-acc-ssk/items/')
            || str_starts_with($url, 'https://api.nlbron.nl/nlbron-surf-ssk/items/');
    }

    /**
     * Streams a file from the given URL, applying required authentication headers and handling
     * the response for content streaming to the client. Handles errors based on the HTTP status
     * code returned from the server.
     *
     * @param string $url The URL of the file to be streamed.
     * @param RepoItem $repoItemLink An object representing the repository item link associated with the file.
     * @return HTTPResponse A response object that concludes the file streaming operation.
     * @throws UnauthorizedException If the API key is missing or if the server returns a 401 status.
     * @throws ForbiddenException If access to the resource is forbidden (403 response).
     * @throws InternalServerErrorException If a server error occurs (500, 502, 503, 504 responses).
     * @throws BadRequestException For any other unrecoverable errors (statuses outside the 2xx range).
     */
    public function streamFile(string $url, RepoItem $repoItemLink): HTTPResponse {
        $apiKey = Environment::getEnv('SHARECONTROL_API_KEY');
        if (!$apiKey) {
            LogItem::errorLog('Missing SHARECONTROL_API_KEY for ShareControl link streaming.', __CLASS__, __FUNCTION__);
            throw new UnauthorizedException(ApiErrorConstant::GA_UA_001);
        }

        $response = $this->directGet($url, $apiKey);

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            LogItem::errorLog("ShareControl download request returned $statusCode for $url", __CLASS__, __FUNCTION__);
            match ($statusCode) {
                401 => throw new UnauthorizedException(ApiErrorConstant::GA_UA_001),
                403 => throw new ForbiddenException(ApiErrorConstant::GA_FB_001),
                404 => throw new NotFoundException(ApiErrorConstant::GA_NF_002),
                500 => throw new InternalServerErrorException(ApiErrorConstant::GA_ISE_001),
                default => throw new BadRequestException(ApiErrorConstant::GA_BR_008)
            };
        }

        $contentType = $response->getHeaderLine('Content-Type') ?: 'application/octet-stream';
        $contentLength = $response->getHeaderLine('Content-Length');
        $filename = $this->buildShareControlFilename($repoItemLink, $url);

        $headers = [
            'Content-Type' => $contentType,
            'Content-Length' => $contentLength ?: null,
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            'X-Content-Type-Options' => 'nosniff'
        ];
        foreach ($headers as $name => $value) {
            if ($value !== null && $value !== '') {
                header($name . ': ' . $value);
            }
        }

        $body = $response->getBody();
        while (!$body->eof()) {
            echo $body->read(8192);
            flush();
        }

        return HTTPResponse::create();
    }

    private function directGet(string $url, string $apiKey): ResponseInterface {
        try {
            $client = new Client();
            return $client->request('GET', $url, [
                'headers' => [
                    'apikey' => $apiKey
                ],
                'stream' => true,
                'http_errors' => false
            ]);
        } catch (GuzzleException $e) {
            LogItem::errorLog("Direct ShareControl request failed for $url: {$e->getMessage()}", __CLASS__, __FUNCTION__);
            throw new InternalServerErrorException(ApiErrorConstant::GA_ISE_001);
        }
    }

    /**
     * Builds a sanitized filename for ShareControl file downloads based on the repository item and URL.
     * Ensures the filename is cleaned of invalid characters and includes the correct file extension.
     *
     * @param RepoItem $repoItemLink An object representing the repository item, used to determine the filename.
     * @param string $url The URL of the file, used to extract the file extension if applicable.
     * @return string A sanitized and valid filename.
     */
    private function buildShareControlFilename(RepoItem $repoItemLink, string $url): string {
        $title = trim((string)$repoItemLink->getTitle());
        $filename = $title !== '' ? $title : 'download';
        $filename = preg_replace('/[\r\n]+/', ' ', $filename);
        $filename = preg_replace('/[\/\\\\]+/', '-', $filename);
        $filename = preg_replace('/[^A-Za-z0-9 ._-]/', '', $filename);
        $filename = trim($filename);

        if ($filename === '') {
            $filename = 'download';
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if ($extension && !str_ends_with(strtolower($filename), '.' . strtolower($extension))) {
            $filename .= '.' . $extension;
        }

        return $filename;
    }
}
