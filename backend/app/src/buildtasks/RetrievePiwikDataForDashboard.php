<?php

namespace SilverStripe\buildtasks;

use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;
use Exception;
use SilverStripe\constants\UtmContent;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\models\dashboard\InstituteDaySummary;
use SilverStripe\Piwik\PiwikCustomDimensionMapping;
use SurfSharekit\constants\RepoItemTypeConstant;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Piwik\Api\PiwikClient;
use SurfSharekit\Piwik\Api\PiwikQuery;
use SurfSharekit\Piwik\CustomEventDimension;

class RetrievePiwikDataForDashboard extends BuildTask {
    private ?DateTime $fromDate = null;
    private ?DateTime $untilDate = null;

    public function run($request): void {
        set_time_limit(0);

        $dateRange = $this->getDateRange($request);

        $piwikApi = $this->createPiwikApi();

        // Iterate over the date range and generate a daily summary for each unique institute
        /** @var DateTime $currentDateTime */
        foreach ($dateRange as $currentDateTime) {
            // Set correct time for begin and end DateTimes
            $currentFromDateTime = $currentDateTime->setTime(0, 0);
            $currentUntilDateTime = (clone $currentDateTime)->setTime(23, 59, 59);

            // Fetch data from Piwik for the current day in the iteration
            [$meta, $downloadData] = $this->getDownloadData($piwikApi, $currentFromDateTime, $currentUntilDateTime);

            // Process Piwik data
            // Get the UTM sources data for each institute
            $groupedData = $this->getUtmSourcesByInstitute($meta, $downloadData);

            foreach ($groupedData as $instituteUuid => $counts) {
                /** @var Institute|null $institute */
                $institute = Institute::get()->find('Uuid', $instituteUuid);
                if (!$institute) {
                    continue;
                }
                $this->updateOrCreateInstituteDaySummary($institute, $currentDateTime, $counts["channelDownloads"], $counts["totalDownloads"]);
            }
        }
    }

    /**
     * @param Institute $institute
     * @param DateTime $dateTime
     * @param array $channelDownloads
     * @param int $totalDownloads
     * @return void
     * @throws \SilverStripe\ORM\ValidationException
     */
    private function updateOrCreateInstituteDaySummary(Institute $institute, DateTime $dateTime, array $channelDownloads, ?int $totalDownloads) {
        /** @var InstituteDaySummary|null $summary */
        $summary = InstituteDaySummary::get()->filter([
            "Day" => (clone $dateTime)->format('Y-m-d'),
            "InstituteID" => $institute->ID
        ])->first();

        if ($summary) {
            $this->updateInstituteDaySummary($summary, $channelDownloads, $totalDownloads);
        } else {
            $this->createNewInstituteDaySummary($institute, $dateTime, $channelDownloads, $totalDownloads);
        }
    }

    /**
     * @param InstituteDaySummary $summary
     * @param array $channelDownloads
     * @param int $totalDownloads
     * @return void
     * @throws \SilverStripe\ORM\ValidationException
     */
    private function updateInstituteDaySummary(InstituteDaySummary $summary, array $channelDownloads, ?int $totalDownloads) {
        $summary->Downloads = $totalDownloads ?? 0;
        $summary->UtmSourceData = json_encode($channelDownloads) ?? null;
        $summary->write();
    }

    /**
     * @param Institute $institute
     * @param DateTime $dateTime
     * @param array $channelDownloads
     * @param int $totalDownloads
     * @return InstituteDaySummary
     * @throws \SilverStripe\ORM\ValidationException
     */
    private function createNewInstituteDaySummary(Institute $institute, DateTime $dateTime, array $channelDownloads, ?int $totalDownloads): InstituteDaySummary {
        $newInstituteDaySummary = new InstituteDaySummary();
        $newInstituteDaySummary->Day = (clone $dateTime)->format('Y-m-d');
        $newInstituteDaySummary->Downloads = $totalDownloads ?? 0;
        $newInstituteDaySummary->InstituteID = $institute->ID;
        $newInstituteDaySummary->UtmSourceData = json_encode($channelDownloads) ?? null;
        $newInstituteDaySummary->write();
        return $newInstituteDaySummary;
    }

    private function createPiwikApi(): PiwikClient
    {
        return new PiwikClient(
            Environment::getEnv('PIWIK_API_CLIENT_ID'),
            Environment::getEnv('PIWIK_API_CLIENT_SECRET'),
            Environment::getEnv('PIWIK_URL'),
            Environment::getEnv('PIWIK_SITE_ID')
        );
    }


    private function getDateRange(HTTPRequest $request): DatePeriod {
        $fromParam = $request->getVar('from');
        $untilParam = $request->getVar('until');

        $timeZone = new DateTimeZone("Europe/Amsterdam");
        if ($fromParam && $untilParam) {
            $this->fromDate = (new DateTime($fromParam, $timeZone))->setTime(0, 0);
            $this->untilDate = (new DateTime($untilParam, $timeZone))->setTime(23, 59, 59);
        } else {
            $this->fromDate = (new DateTime('yesterday', $timeZone))->setTime(0, 0);
            $this->untilDate = (new DateTime('yesterday', $timeZone))->setTime(23, 59, 59);
        }

        $interval = new DateInterval('P1D'); // 1-day interval
        return new DatePeriod($this->fromDate, $interval, $this->untilDate);
    }

    /**
     * Get the download data for a given date range.
     *
     * @param PiwikClient $piwikApi The Piwik client to use.
     * @param DateTime $from
     * @param DateTime $until
     * @return array All the results from Piwik
     * @throws Exception If the Piwik API query fails.
     */
    private function getDownloadData($piwikApi, DateTime $from, DateTime $until): array {

        $limit = 10000;
        $offset = 0;
        $data = [];
        $meta = [];

        while (true) {
            $piwikResponse = $piwikApi->query()
                ->from($this->getFormattedDateTimeUTC($from))
                ->to($this->getFormattedDateTimeUTC($until))
                ->columns(
                    ["column_id" => "timestamp", "transformation_id" => "to_date"],
                    ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_FILE_ID)->getRetrievalKey()],
                    ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_ID)->getRetrievalKey()],
                    ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_TYPE)->getRetrievalKey()],
                    ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::ROOT_INSTITUTE_ID)->getRetrievalKey()],
                    ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::UTM_SOURCE)->getRetrievalKey()],
                    ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::UTM_CONTENT)->getRetrievalKey()],
                    ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_LINK_ID)->getRetrievalKey()],
                    ["column_id" => "custom_events"]
                )
                ->limit($limit, $offset)
                ->execute();

            if (!in_array($piwikResponse->getStatusCode(), range(200, 299))) {
                $logMessage = "A paginated request to Piwik failed: begin: {$this->getFormattedDateTimeUTC($from)}, end: {$this->getFormattedDateTimeUTC($until)}, limit: $limit, offset: $offset";
                Logger::warnLog($logMessage);
                throw new Exception($logMessage);
            }

            $body = json_decode($piwikResponse->getBody(), true);
            $meta = $body['meta'];
            $data = [...$data, ...$body['data']];

            if (count($body['data']) < $limit) {
                break;
            }

            $offset += 10000;
        }

        return [$meta, $data];
    }

    /**
     * Takes the download data and returns an array with the UTM sources, grouped by institute.
     *
     * The resulting array will have the following structure:
     * [
     *     'institute1' => [
     *         totalDownloads => 40
     *         channelDownloads => [
     *             'utm-source-1' => [
     *                  'total' => 5,
     *                  'download' => 3,
     *                  'link' => 2
     *              ],
     *              'utm-source-2' => [
     *                  'total' => 2,
     *                  'download' => 1,
     *                  'link' => 1
     *              ]
     *     ],
     *     'institute2' => [...]
     * ]
     *
     * @param array $data The download data from the Piwik API.
     * @return array The UTM sources grouped by institute.
     */
    private function getUtmSourcesByInstitute($meta, $data): array {
        $utmSourcesByInstitute = [];

        foreach ($data as $row) {
            $rootInstituteIdColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::ROOT_INSTITUTE_ID)->getRetrievalKey());
            $utmSourceColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::UTM_SOURCE)->getRetrievalKey());
            $utmContentColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::UTM_CONTENT)->getRetrievalKey());

            $repoItemIdColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_ID)->getRetrievalKey());
            $repoItemFileIdColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_FILE_ID)->getRetrievalKey());
            $repoItemLinkIdColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_LINK_ID)->getRetrievalKey());
            $repoTypeColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_TYPE)->getRetrievalKey());
            $customEventsColumnIndex = PiwikQuery::getColumnIndex($meta, "custom_events");


            $institute = $row[$rootInstituteIdColumnIndex];
            $utmSource = $row[$utmSourceColumnIndex] ?: 'unknown';
            $utmContent = $row[$utmContentColumnIndex] ?: UtmContent::DOWNLOAD;
            $repoItemUuid = $row[$repoItemIdColumnIndex];
            $repoItemFileUuid = $row[$repoItemFileIdColumnIndex];
            $repoItemLinkUuid = $row[$repoItemLinkIdColumnIndex];
            $repoType = $row[$repoTypeColumnIndex];
            $evenCount = $row[$customEventsColumnIndex];

            // Skip if the download event does not contain the necessary information, an event can have an empty utm_source and still be valid
            // Old events did not contain utm_content, this is why it has a default
            if (!$institute || !$repoItemUuid || !$repoType || (!$repoItemFileUuid && !$repoItemLinkUuid)) {
                continue;
            }

            if (!isset($utmSourcesByInstitute[$institute])) {
                $utmSourcesByInstitute[$institute] = [
                    "totalDownloads" => 0,
                    "channelDownloads" => []
                ];
            }

            if (!isset($utmSourcesByInstitute[$institute]["channelDownloads"][$utmSource])) {
                $utmSourcesByInstitute[$institute]["channelDownloads"][$utmSource] = [
                    'total' => 0,
                    'download' => 0,
                    'link' => 0
                ];
            }

            $utmSourcesByInstitute[$institute]['totalDownloads'] += $evenCount;
            $utmSourcesByInstitute[$institute]["channelDownloads"][$utmSource]['total'] += $evenCount;
            $utmSourcesByInstitute[$institute]["channelDownloads"][$utmSource][$utmContent] += $evenCount;
        }

        return $utmSourcesByInstitute;
    }

    private function getFormattedDateTimeUTC(DateTime $dateTime): string {
        $clonedDateTime = clone $dateTime;
        $clonedDateTime->setTimezone(new DateTimeZone("UTC"));
        return $clonedDateTime->format('Y-m-d H:i:s');
    }
}