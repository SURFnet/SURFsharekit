<?php

namespace SurfSharekit\ShareControl;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\LogItem;
use SurfSharekit\Models\Person;
use SurfSharekit\ShareControl\Model\Item;
use Throwable;
use const SurfSharekit\Models\AUTHENTICATION_LOG;
use const SurfSharekit\Models\GENERAL_LOG;

class ShareControlApiCommunicatorImpl implements ShareControlApiCommunicator {
    use Injectable;
    const DEFAULT_PAGE_SIZE = 20;

    /**
     * Search for items in Surf Sharecontrol using a given search term
     *
     * @param string $searchTerm
     * @return array<Item>
     * @throws Exception
     */
    public static function searchItems(string $iBron, string $searchTerm, int $page = 0, int $pageSize = 0): array {
        $queryParams = [
//            'ibronId' => 'nlbron-surf-acc',
//            'sourceId' => 'surfsharekit-acc',
            'from' => '2021-01-01T00:00:00Z',
            'to' => '2055-09-03T08:35:35.432664Z',
            'page' => $page - 1,
            'size' => $pageSize ?: static::DEFAULT_PAGE_SIZE,
            'order' => 'ASC',
            'searchText' => $searchTerm
        ];

        $options = [
            'query' => $queryParams
        ];
        $endpoint = "ibron/$iBron/items/search";
        $response = static::get($endpoint, $options);

        try {
            $responseArray = json_decode($response->getBody()->getContents(), true);
            $returnArray = [];
            foreach ($responseArray as $item) {
                $returnArray[] = Item::fromJSON($item);
            }
            return $returnArray;
        } catch (Exception $e) {
            static::onException($endpoint, $options, $e);
        }
    }

    /**
     * Flag an item in Surf Sharecontrol so that it creates a new RepoItem with this item as file
     *
     * @param string $uuid The UUID of the item to flag
     * @param string $ownerUuid The owner of the to be created RepoItem
     * @throws GuzzleException
     */
    public static function flagItem(string $iBron, string $uuid, string $ownerUuid): bool {
        $ownerObject = Person::get()->filter('Uuid', $ownerUuid)->first();
        $res = static::post(sprintf('ibron/%s/items/%s/add-availability?sskRepoItemOwnerUuid=%s', $iBron, $uuid, $ownerUuid), []);
        if ($res->getStatusCode() >= 200 && $res->getStatusCode() < 300) {
            LogItem::debugLog("Flagged item $uuid in ibron $iBron with owner uuid $ownerUuid and name: $ownerObject->FirstName $ownerObject->Surname", __CLASS__, __FUNCTION__);
            return true;
        } else {
            return false;
        }
    }

    private static function get($path, array $extraOptions = []): ResponseInterface {
        return static::doRequest('GET', $path, $extraOptions);
    }

    /**
     * @param string $path
     * @param array $body
     * @param array $extraOptions
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private static function post(string $path, array $body, array $extraOptions = []): ResponseInterface {
        $extraOptions = array_merge_recursive($extraOptions, [
            'json' => $body
        ]);
        return static::doRequest('POST', $path, $extraOptions);
    }

    /**
     * @param $path
     * @param array $extraOptions
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private static function delete($path, array $extraOptions = []): ResponseInterface {
        return static::doRequest('DELETE', $path, $extraOptions);
    }

    private static function doRequest($method, $path, array $extraOptions = []): ResponseInterface {
        try {
            /** @var Client $client */
            $client = Injector::inst()->create(Client::class);

            $options = [
                'headers' => [
                    'apikey' => Environment::getEnv("SHARECONTROL_API_KEY")
                ]
            ];

            $options = array_merge_recursive($options, $extraOptions);
            $url = Environment::getEnv("SHARECONTROL_API_URL");

            if (!str_starts_with($path, '/')) {
                $path = "/$path";
            }
            if (str_ends_with($url, "/")) {
                $url = substr($url, 0, -1);
            }

            $response = $client->request(
                $method,
                $url . $path,
                $options
            );

            // Guzzle normally throws if the status code is 4xx or 5xx, but just to be sure
            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode > 299) {
                throw new InvalidStatusCodeException("Client responded with a $statusCode status");
            }
            return $response;
        } catch (GuzzleException|InvalidStatusCodeException $e) {
            static::onException($method . " " . $path, $options ?? $extraOptions, $e);
        }
    }

    /**
     * @throws ShareControlApiException
     */
    public static function onException(string $uri, array $options, Exception $e) {
        if ($e instanceof ShareControlApiException) throw $e;

        array_walk_recursive($options, function(&$val) {
            if ($val == Environment::getEnv("SHARECONTROL_API_KEY")) {
                $val = "<redacted>";
            }
        });

        LogItem::errorLog([
            "--- ShareControlApiCommunicator exception ---",
            "[API REQUEST URI]  " . $uri,
            "[API REQUEST OPTIONS] " . json_encode($options),
            "[API EXCEPTION]    " . $e->getMessage(),
            "---------------------------------------------"
        ], __CLASS__, __FUNCTION__);

        throw new ShareControlApiException("Unable to fulfill the ShareControl api request", previous: $e);
    }
}