<?php

namespace SurfSharekit\Piwik\Api;

use Closure;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class PiwikQuery {
    private PiwikAPI $api;
    private string $endpoint;

    private $dateFrom = null;
    private $dateTo = null;

    private array $columns = [];

    private PiwikFilter $filter;

    private int $limit = 10;
    private int $offset = 0;

    private string $format = "json";

    public function __construct(PiwikAPI $api, string $endpoint = "query") {
        $this->api = $api;
        $this->endpoint = $endpoint;
    }

    public function execute(): ResponseInterface {
        if (empty($this->getDateFrom()) || empty($this->getDateTo())) {
            throw new Exception("No date from or date to specified");
        }

        $columns = [];

        foreach ($this->getColumns() as $column) {
            /** @var PiwikColumn $column */
            $set = ["column_id" => $column->getColumnId()];

            if ($transformationId = $column->getTransformationId()) {
                $set["transformation_id"] = $transformationId;
            }

            $columns[] = $set;
        }

        $reqBody = [
            "website_id" => $this->getApi()->getSiteId(),
            "date_from" => $this->getDateFrom(),
            "date_to" => $this->getDateTo(),
            "columns" => $columns,
            "offset" => $this->getOffset(),
            "limit" => $this->getLimit(),
            "format" => $this->getFormat(),
            "options" => [
                "sampling" => 1
            ]
        ];

        if (isset($this->filter)) {
            $reqBody['filters'] = $this->filter->toArray();
        }

        $response = (new Client())->post($this->getApi()->getUrl() . '/api/analytics/v1/' . $this->getEndpoint(), [
            RequestOptions::JSON => $reqBody,
            RequestOptions::HEADERS => [
                "Authorization" => "Bearer " . $this->getApi()->getAccessToken()->getAccessToken()
            ]
        ]);

        return $response;
    }

    public function andFilter(Closure $closure): self {
        $this->filter = (new PiwikFilter("and", $closure));

        return $this;
    }

    public function orFilter(Closure $closure): self {
        $this->filter = (new PiwikFilter("or", $closure));

        return $this;
    }

    public function columns(): self {
        $piwikColumns = [];

        foreach (func_get_args() as $column) {
            if (is_string($column)) {
                $piwikColumns[] = new PiwikColumn($column);
            } else if ($column instanceof PiwikColumn) {
                $piwikColumns[] = $column;
            } else if (is_array($column)) {
                $piwikColumns[] = new PiwikColumn($column['column_id'], $column['transformation_id'] ?? null);
            }
        }

        $this->columns = $piwikColumns;

        return $this;
    }

    public function getColumns() {
        return $this->columns;
    }

    public function limit(int $limit, int $offset = 0): self {
        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    public function getLimit(): int {
        return $this->limit;
    }

    public function getOffset(): int {
        return $this->offset;
    }

    public function setFormat(string $format): self {
        if (!in_array($format, ["json", "xml", "csv", "json-kv"])) {
            throw new Exception("Invalid format, chose one of: 'json', 'xml', 'csv', 'json-kv'");
        }

        $this->format = $format;

        return $this;
    }

    public function getFormat(): string {
        return $this->format;
    }

    public function getDateFrom() {
        return $this->dateFrom;
    }

    public function from($dateFrom): self {
        $this->dateFrom = $dateFrom instanceof DateTime ? $dateFrom->format("Y-m-d") : $dateFrom;

        return $this;
    }

    public function getDateTo() {
        return $this->dateTo;
    }

    public function to($dateTo): self {
        $this->dateTo = $dateTo instanceof DateTime ? $dateTo->format("Y-m-d") : $dateTo;

        return $this;
    }

    public function getApi(): PiwikAPI {
        return $this->api;
    }

    public function getEndpoint(): string {
        return $this->endpoint;
    }

    public static function getColumnIndex(array $meta, $columnId) {
        if (in_array($columnId, $meta['columns'])) {
            return array_search($columnId, $meta['columns']);
        }

        return null;
    }
}