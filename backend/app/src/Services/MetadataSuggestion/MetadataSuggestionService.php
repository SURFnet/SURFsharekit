<?php

namespace SilverStripe\Services\MetadataSuggestion;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use SilverStripe\api\MetadataSuggestion\Data\Metadata;
use SilverStripe\api\MetadataSuggestion\MetafieldMetadataProviderMapper;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Services\RepoItemFileService;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\MetaField;
use Throwable;

class MetadataSuggestionService implements IMetadataSuggestionService {
    use Injectable;
    use Configurable;

    /**
     * @param string $url
     * @return string
     */
    private function extractTextFromFile(string $url): string {
        $guzzleClient = new Client([
            "base_uri" => Environment::getEnv("TEXT_EXTRACTION_SERVICE_BASE_URL"),
            RequestOptions::HEADERS => ['Content-Type' => 'application/json']
        ]);

        $body = [
            "url" => $url
        ];

        try {
            $textExtractionResponse = $guzzleClient->post(
                "extract",
                [RequestOptions::BODY => json_encode($body)]
            );
            Logger::infoLog("Text extraction response: " . $textExtractionResponse->getBody());
            return json_decode($textExtractionResponse->getBody(), true)["text"];
        } catch (Throwable $e) {
            Logger::infoLog("Failed extracting text from file: " . $e->getMessage());
            return "";
        }
    }

    /**
     * @param string $metaFieldUuid
     * @param string $repoItemRepoItemFileUuid
     * @param string|null $metaFieldOptionUuid
     * @return array
     */
    public function getSuggestions(string $metaFieldUuid, string $repoItemRepoItemFileUuid, ?string $metaFieldOptionUuid = null): array {
        $repoItemFileService = RepoItemFileService::create();
        $repoItemFile = $repoItemFileService->getByRepoItemRepoItemFile($repoItemRepoItemFileUuid);

        if (!$repoItemFile) {
            return [];
        }

        $fileText = $this->extractTextFromFile($repoItemFile->getRedirectLink());

        /** @var MetaField $metaField */
        $metaField = MetaField::get()->find("Uuid", $metaFieldUuid);
        if (!$metaField) {
            return [];
        }

        $metadataProvider = MetafieldMetadataProviderMapper::getMetadataProvider($metaField->JsonKey);
        if (!$metadataProvider) {
            return [];
        }

        return $metadataProvider->getMetadataSuggestions($fileText, $metaField, $metaFieldOptionUuid);
    }
}