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
use SurfSharekit\ShareControl\Model\Item;

interface ShareControlApiCommunicator {

    /**
     * Search for items in Surf Sharecontrol using a given search term
     *
     * @param string $searchTerm
     * @param int $page
     * @param int $pageSize
     * @return array<Item>
     * @throws Exception
     */
    public static function searchItems(string $iBron, string $searchTerm, int $page = 0, int $pageSize = 0): array;

    /**
     * Flag an item in Surf Sharecontrol
     *
     * @param string $uuid The UUID of the item to flag
     * @param string $ownerUuid The to-be owner of the RepoItem that gets created
     * @throws Exception
     */
    public static function flagItem(string $iBron, string $uuid, string $ownerUuid): bool;
}