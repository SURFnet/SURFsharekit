<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Piwik\PiwikCustomDimensionMapping;
use SurfSharekit\Piwik\Api\PiwikAPI;
use SurfSharekit\Piwik\Api\PiwikFilter;
use SurfSharekit\Piwik\CustomEventDimension;

class PiwikProDebugTask extends BuildTask
{
    private \stdClass $auth;

    public function run($request) {
        $piwikApi = new PiwikAPI(
            Environment::getEnv('PIWIK_API_CLIENT_ID'),
            Environment::getEnv('PIWIK_API_CLIENT_SECRET'),
            Environment::getEnv('PIWIK_URL'),
            Environment::getEnv('PIWIK_SITE_ID')
        );

        $response = $piwikApi->query()
            ->from("2024-10-11")
            ->to("2024-10-31")
            ->columns(
                ["column_id" => "timestamp", "transformation_id" => "to_date"],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_ID)->getRetrievalKey()],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_FILE_ID)->getRetrievalKey()],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_TYPE)->getRetrievalKey()],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::ROOT_INSTITUTE_ID)->getRetrievalKey()],
                ["column_id" => "custom_events"]
            )
            ->andFilter(function (PiwikFilter $filter) {
                $filter->andFilter(function (PiwikFilter $filter) {
                    $filter->filter(PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_FILE_ID)->getRetrievalKey(), "neq", "");
                });
            })
            ->execute();

        echo "<pre>";
        print_r(json_decode($response->getBody(), true));
        echo "</pre>";
    }
}