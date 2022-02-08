<?php

namespace SurfSharekit\Api;

use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Security;
use SurfSharekit\Models\GeneratedDoi;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;

/***
 * Class DoiCreator
 * @package SurfSharekit\Api
 * Uses the datacite json-api api to create and enrich DOIS
 * https://support.datacite.org/docs/api-create-dois
 */
class DoiCreator {
    static function generateDoiFor(RepoItem $repoItem) {
        $doi = Environment::getEnv("DOI_PREFIX") . "/$repoItem->Uuid";
        $endpoint = Environment::getEnv('DOI_SERVER');
        $postData = [
            "data" => [
                "type" => "dois",
                "attributes" => [
                    "doi" => $doi
                ]
            ]
        ];
        $headers = [
            'content-type' => 'application/vnd.api+json'
        ];
        $client = new Client();

        try {
            $response = $client->request(
                'POST',
                $endpoint . '/dois',
                [
                    RequestOptions::AUTH => [Environment::getEnv('DOI_USER'), Environment::getEnv('DOI_PASSWORD')],
                    RequestOptions::HEADERS => $headers,
                    RequestOptions::BODY => json_encode($postData)
                ]
            );
            $doiObj = GeneratedDoi::get()->filter(['DOI' => $doi])->first();
            if (!$doiObj || !$doiObj->exists()) {
                $doiObj = new GeneratedDoi();
                $doiObj->DOI = $doi;
                $doiObj->PersonID = Security::getCurrentUser()->ID;
                $doiObj->RepoItemID = $repoItem->ID;
                $doiObj->write();
            }
        } catch (GuzzleException $e) {
            if ($e->getCode() == 422) {
                static::putDoiInto($repoItem, $doi);
                return $doi;
            }else {
                Logger::errorLog($e->getMessage());
                return null;
            }
        }
        if ($response->getStatusCode() == 201) {
            static::putDoiInto($repoItem, $doi);
            return $doi;
        } else {
            return null;
        }
    }

    static function hasDoi(RepoItem $repoItem){
        $doi = Environment::getEnv("DOI_PREFIX") . "/$repoItem->Uuid";
        $doiObj = GeneratedDoi::get()->filter(['DOI' => $doi])->first();
        return ($doiObj && $doiObj->exists());
    }

    static function enrichDoiFor(RepoItem $repoItem) {
        $doi = Environment::getEnv("DOI_PREFIX") . "/$repoItem->Uuid";
        $endpoint = Environment::getEnv('DOI_SERVER');
        $putData = [
            "data" => [
                "type" => "dois",
                "attributes" => [
                    "doi" => $doi,
                    /* publish - Triggers a state move from draft or registered to findable
                     * register - Triggers a state move from draft to registered
                     * hide - Triggers a state move from findable to registered
                     */
                    "event" => "publish",
                    "creators" => $repoItem->getPersonsInvolved(), //['name' => 'Joseph von wheeler']]
                    "titles" => [[
                        "title" => $repoItem->Title
                    ]],
                    "publisher" => $repoItem->Institute()->Title,
                    "publicationYear" => (new DateTime($repoItem->PublicationDate))->format('Y'), //from publication
                    "types" => [
                        "resourceTypeGeneral" => "Dataset" //right choice? https://schema.datacite.org/meta/kernel-4.1/doc/DataCite-MetadataKernel_v4.1.pdf page 18
                    ],
                    "url" => $repoItem->getPublicURL()
                ]
            ]
        ];
        $headers = [
            'content-type' => 'application/vnd.api+json'
        ];
        $client = new Client();

        try {
            $client->request(
                'PUT',
                "$endpoint/dois/$doi",
                [
                    RequestOptions::AUTH => [Environment::getEnv('DOI_USER'), Environment::getEnv('DOI_PASSWORD')],
                    RequestOptions::HEADERS => $headers,
                    RequestOptions::BODY => json_encode($putData)
                ]
            );
        } catch (GuzzleException $e) {
            Logger::errorLog($e->getMessage());
        }
    }

    private static function putDoiInto(RepoItem $repoItem, string $doi) {
        $doiMetaField = MetaField::get()->filter('MetaFieldType.Title', 'DOI')->first();
        if (!$doiMetaField || !$doiMetaField->exists()) {
            throw new Exception("No DOI field found to enter doi: $doi");
        }

        $answerForDoi = $repoItem->RepoItemMetaFields()->filter(['MetaFieldID' => $doiMetaField->ID])->first();
        if (!$answerForDoi || !$answerForDoi->exists()) {
            $answerForDoi = new RepoItemMetaField();
            $answerForDoi->RepoItemID = $repoItem->ID;
            $answerForDoi->MetaFieldID = $doiMetaField->ID;
            $answerForDoi->write();
        }

        $valueForDoi = $answerForDoi->RepoItemMetaFieldValues()->first();
        if (!$valueForDoi || !$valueForDoi->exists()) {
            $valueForDoi = new RepoItemMetaFieldValue();
            $valueForDoi->RepoItemMetaFieldID = $answerForDoi->ID;
        }

        $valueForDoi->Value = $doi;
        $valueForDoi->IsRemoved = false;
        $valueForDoi->write();
    }

    public static function getDoiOfRepoItem(RepoItem $repoItem) {
        $doiMetaField = MetaField::get()->filter('MetaFieldType.Title', 'DOI')->first();
        if (!$doiMetaField || !$doiMetaField->exists()) {
            return null;
        }

        $answerForDoi = $repoItem->RepoItemMetaFields()->filter(['MetaFieldID' => $doiMetaField->ID])->first();
        if (!$answerForDoi || !$answerForDoi->exists()) {
            return null;
        }
        $valueForDoi = $answerForDoi->RepoItemMetaFieldValues()->filter(['IsRemoved' => 0])->first();
        if (!$valueForDoi || !$valueForDoi->exists()) {
            return null;
        }
        return $valueForDoi->Value;
    }
}