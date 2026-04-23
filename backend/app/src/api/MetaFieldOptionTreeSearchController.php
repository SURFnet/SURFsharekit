<?php

namespace SurfSharekit\Api;

use SilverStripe\api\BaseController;
use SilverStripe\api\ResponseHelper;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DB;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Models\MetaFieldOption;

/**
 * Search API for tree multiselect: searches labels and descriptions within all descendants
 * of a given MetaFieldOption (by UUID). Login-protected like other internal-style APIs.
 */
class MetaFieldOptionTreeSearchController extends BaseController
{
    private const MIN_QUERY_LENGTH = 3;

    private const PAGE_SIZE = 10;

    private static $url_handlers = [
        'GET /' => 'handleGET',
    ];

    private static $allowed_actions = [
        'handleGET',
    ];

    public static function handleGET(HTTPRequest $request): HTTPResponse
    {
        $metaFieldOptionUuid = $request->getVar('metaFieldOptionUuid');
        if (!$metaFieldOptionUuid) {
            throw new BadRequestException(ApiErrorConstant::GA_BR_002, 'Missing metaFieldOptionUuid query parameter');
        }

        $query = trim((string) $request->getVar('query'));
        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            throw new BadRequestException(
                ApiErrorConstant::GA_BR_004,
                'Search query must be at least ' . self::MIN_QUERY_LENGTH . ' characters'
            );
        }

        $page = self::parsePageNumber($request);

        $scoped = InstituteScoper::getAll(MetaFieldOption::class)->filter('IsRemoved', 0);
        $root = $scoped->filter('Uuid', $metaFieldOptionUuid)->first();
        if (!$root) {
            throw new NotFoundException(ApiErrorConstant::GA_NF_002, 'MetaFieldOption not found or not accessible');
        }

        $descendantIds = self::collectDescendantIds((int) $root->ID);
        if ($descendantIds === []) {
            return self::jsonListWithPaginationHeaders([], 0, $page);
        }

        $allowedIds = array_values(array_intersect(
            $descendantIds,
            $scoped->filter('ID', $descendantIds)->column('ID')
        ));
        if ($allowedIds === []) {
            return self::jsonListWithPaginationHeaders([], 0, $page);
        }

        $likeTerm = '%' . self::escapeLikeWildcards($query) . '%';
        $likeClause = '(SurfSharekit_MetaFieldOption.Label_NL LIKE ? OR SurfSharekit_MetaFieldOption.Label_EN LIKE ? OR '
            . 'SurfSharekit_MetaFieldOption.Description_NL LIKE ? OR SurfSharekit_MetaFieldOption.Description_EN LIKE ?)';
        $likeParams = [$likeTerm, $likeTerm, $likeTerm, $likeTerm];

        // Integer IDs inlined (safe); only LIKE terms use ? placeholders. Mixing huge filter(ID=>[]) with
        // where(?,[]) breaks SilverStripe prepared statements on count() (ArgumentCountError in mysqli bind_param).
        $idList = implode(',', array_map('intval', $allowedIds));
        $baseWhere = "SurfSharekit_MetaFieldOption.ID IN ($idList) AND SurfSharekit_MetaFieldOption.IsRemoved = 0 AND $likeClause";

        $countSql = "SELECT COUNT(*) FROM SurfSharekit_MetaFieldOption WHERE $baseWhere";
        $total = (int) DB::prepared_query($countSql, $likeParams)->value();

        $offset = ($page - 1) * self::PAGE_SIZE;
        // Ascending A–Z by displayed prefLabel (NL when set, else EN), then ID for stable pages
        $orderExpr = 'COALESCE(NULLIF(TRIM(COALESCE(SurfSharekit_MetaFieldOption.Label_NL, \'\')), \'\'), '
            . 'TRIM(COALESCE(SurfSharekit_MetaFieldOption.Label_EN, \'\'))) ASC, SurfSharekit_MetaFieldOption.ID ASC';
        $pageSql = 'SELECT SurfSharekit_MetaFieldOption.ID FROM SurfSharekit_MetaFieldOption WHERE ' . $baseWhere
            . ' ORDER BY ' . $orderExpr . ' LIMIT ? OFFSET ?';
        $pageParams = array_merge($likeParams, [self::PAGE_SIZE, $offset]);
        $pageIdRows = DB::prepared_query($pageSql, $pageParams);
        $pageIds = [];
        foreach ($pageIdRows as $row) {
            $pageIds[] = (int) $row['ID'];
        }

        $payload = [];
        if ($pageIds !== []) {
            $byId = [];
            foreach (MetaFieldOption::get()->filter('ID', $pageIds)->filter('IsRemoved', 0) as $option) {
                $byId[(int) $option->ID] = $option;
            }
            foreach ($pageIds as $id) {
                $option = $byId[$id] ?? null;
                if (!$option) {
                    continue;
                }
                $payload[] = [
                    'id' => (string) $option->Uuid,
                    'prefLabel' => self::prefLabel($option),
                    'altLabel' => self::altLabel($option),
                    'breadcrumb' => self::breadcrumbFor($option),
                ];
            }
        }

        return self::jsonListWithPaginationHeaders($payload, $total, $page);
    }

    /**
     * JSON:API style: page[number] (and optional page[size], ignored — fixed at PAGE_SIZE).
     * Legacy: page=1
     */
    private static function parsePageNumber(HTTPRequest $request): int
    {
        $pageParam = $request->getVar('page');
        if (is_array($pageParam)) {
            $num = isset($pageParam['number']) ? (int) $pageParam['number'] : 1;

            return max(1, $num);
        }
        if ($pageParam !== null && $pageParam !== '') {
            return max(1, (int) $pageParam);
        }

        return 1;
    }

    /**
     * @return int[]
     */
    private static function collectDescendantIds(int $rootId): array
    {
        $result = [];
        $frontier = [$rootId];
        while ($frontier !== []) {
            $children = MetaFieldOption::get()
                ->filter(['MetaFieldOptionID' => $frontier, 'IsRemoved' => 0])
                ->column('ID');
            if ($children === []) {
                break;
            }
            foreach ($children as $id) {
                $result[] = (int) $id;
            }
            $frontier = $children;
        }

        return $result;
    }

    private static function escapeLikeWildcards(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private static function prefLabel(MetaFieldOption $option): string
    {
        $nl = trim((string) $option->Label_NL);

        return $nl !== '' ? $nl : (string) $option->Label_EN;
    }

    private static function altLabel(MetaFieldOption $option): string
    {
        $nl = trim((string) $option->Description_NL);

        return $nl !== '' ? $nl : (string) $option->Description_EN;
    }

    private static function breadcrumbFor(MetaFieldOption $option): string
    {
        $segments = [];
        $current = $option;
        while ($current && $current->exists()) {
            $segments[] = self::prefLabel($current);
            $parent = $current->MetaFieldOption();
            if (!$parent || !$parent->exists()) {
                break;
            }
            $current = $parent;
        }

        return implode(' > ', array_reverse($segments));
    }

    private static function jsonListWithPaginationHeaders(array $items, int $total, int $page): HTTPResponse
    {
        $response = ResponseHelper::responseSuccess($items);
        $response->addHeader('X-Total-Count', (string) $total);
        $response->addHeader('X-Page', (string) $page);
        $response->addHeader('X-Page-Size', (string) self::PAGE_SIZE);

        return $response;
    }
}
