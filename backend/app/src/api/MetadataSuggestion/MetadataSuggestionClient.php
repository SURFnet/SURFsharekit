<?php

namespace SilverStripe\api\MetadataSuggestion;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use SilverStripe\api\MetadataSuggestion\Data\Label;
use SilverStripe\api\MetadataSuggestion\Data\Metadata;
use SilverStripe\api\MetadataSuggestion\Data\Term;
use SilverStripe\Core\Environment;
use SurfSharekit\Models\Helper\Logger;
use Throwable;

class MetadataSuggestionClient {

    private Client $client;

    public function __construct(?Client $client = null) {
        if ($client === null) {
            $this->client = new Client([
                "base_uri" => Environment::getEnv("METADATA_SUGGESTION_SERVICE_BASE_URL"),
                RequestOptions::HEADERS => ["Content-Type" => "application/json"],
            ]);
        }
    }

    public function getVocabularySuggestions(string $subject, string $query): Metadata {
        $body = [
            "data" => [
                "attributes" => [
                    "subject" => $subject,
                    "query" => $query,
                ],
                "type" => "vocabulary"
            ]
        ];

        try {
            Logger::infoLog("Analysing text for metadata suggestion: " . json_encode($body));
            $metadataSuggestionResponse = $this->client->post(
                "text-analysis",
                [
                    RequestOptions::BODY => json_encode($body),
                ]
            );
            Logger::infoLog("Metadata suggestion response: " . $metadataSuggestionResponse->getBody());
            $metadataSuggestionResponseBody = json_decode($metadataSuggestionResponse->getBody(), true);
            $terms = $metadataSuggestionResponseBody["data"]["attributes"]["terms"];

            $suggestions = [];
            foreach ($terms as $term) {
                $label = new Label(
                    $term["label"]["language"],
                    $term["label"]["value"],
                );
                $suggestion = new Term(
                    $term["id"],
                    $term["type"],
                    $label
                );
                $suggestions[] = $suggestion;
            }
            return new Metadata($suggestions);
        } catch (Throwable $e) {
            Logger::infoLog("Failed analysing text for metadata suggestion: " . $e->getMessage());
            return new Metadata([]);
        }
    }
}