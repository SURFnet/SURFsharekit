<?php

namespace SurfSharekit\Piwik;

use DataObjectCSVFileEncoder;
use Exception;
use Illuminate\Support\Arr;
use League\Csv\Writer;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use SilverStripe\Assets\File;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataObject;
use SilverStripe\Piwik\PiwikCustomDimensionMapping;
use SplTempFileObject;
use SurfSharekit\Extensions\Security;
use SurfSharekit\Models\ExportItem;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Piwik\Api\PiwikClient;
use SurfSharekit\Piwik\Api\PiwikFilter;
use SurfSharekit\Piwik\Api\PiwikQuery;

class PiwikCSVFileEncoder {

    public static function getDownloadsCSV(ExportItem $exportItem, $vars) {
        $repoType = $vars['filter']['repoType'] ?? null;
        $scope = explode(",", $vars['filter']['scope']);
        $from = $vars['filter']['downloadDate']['GE'];
        $to = $vars['filter']['downloadDate']['LE'];

        $piwikClient = new PiwikClient(
            Environment::getEnv('PIWIK_API_CLIENT_ID'),
            Environment::getEnv('PIWIK_API_CLIENT_SECRET'),
            Environment::getEnv('PIWIK_URL'),
            Environment::getEnv('PIWIK_SITE_ID')
        );

        $person = Person::get()->find("ID", $exportItem->PersonID);
        if ($person == null) {
            throw new Exception("No person was linked to export item $exportItem->Uuid");
        }

        [$meta, $data] = self::fetchPiwikData($piwikClient, $scope, $repoType, $from, $to);

        [$channels, $groupedData] = self::groupData($data, $meta);

        $csvWriter = self::getWriter();
        self::writeCSVHeaders($csvWriter, $channels);

        foreach ($groupedData as $repoItemId => $downloadData) {
            /** @var RepoItem|null $repoItem */
            $repoItem = RepoItem::get()->find('Uuid', $repoItemId);
            if (!$repoItem) {
                continue;
            }

            $canReport = $repoItem->canReport($person);
            if (!$canReport) {
                continue;
            }

            // loop files
            foreach ($downloadData["files"] as $repoItemFileId => $fileData) {
                self::writeCSVRowForFile($csvWriter, $repoItem, $repoItemFileId, $fileData, $channels);
            }

            // loop links
            foreach ($downloadData["links"] as $repoItemLinkId => $linkData) {
                self::writeCSVRowForLink($csvWriter, $repoItem, $repoItemLinkId, $linkData, $channels);
            }
        }

        $uuid = Uuid::uuid4();
        $fileName = Security::getCurrentUser()->ID . "-downloads-$uuid.csv";
        $csvString = (string)$csvWriter;

        $csvFile = tempnam(sys_get_temp_dir(), 'TMPCSV-');
        file_put_contents($csvFile, $csvString);

        $file = new File();
        $file->setFromLocalFile($csvFile, $fileName);
        $file->write();

        unlink($csvFile);

        $exportItem->FileID = $file->ID;
        $exportItem->write();

        return true;
    }

    private static function writeCSVHeaders($csvWriter, array $channels) {
        $headers = [
            'Titel',
            'Auteur 1',
            'Auteur 2',
            'Auteur 3',
            'Auteur 4',
            'Auteur 5',
            'Auteur 6',
            'Auteur 7',
            'Auteur 8',
            'Auteur 9',
            'Auteur 10',
            'Organisatie',
            'Afdeling, lectoraat, opleiding 1',
            'Afdeling, lectoraat, opleiding 2',
            'Afdeling, lectoraat, opleiding 3',
            'Afdeling, lectoraat, opleiding 4',
            'Afdeling, lectoraat, opleiding 5',
            'Afdeling, lectoraat, opleiding 6',
            'Afdeling, lectoraat, opleiding 7',
            'Afdeling, lectoraat, opleiding 8',
            'Afdeling, lectoraat, opleiding 9',
            'Afdeling, lectoraat, opleiding 10',
            'Type',
            'Bestand',
            'URL (extern)',
            'Link naar repoItem',
            'Totaal aantal downloads',
        ];

        // set headers for channels
        foreach ($channels as $channel) {
            $headers[] = "File downloads ($channel)";
            $headers[] = "Link downloads ($channel)";
        }

        $csvWriter->insertOne($headers);
    }

    private static function writeCSVRowForFile($csvWriter, RepoItem $repoItem, string $repoItemFileId, array $downloadData, array $channels) {
        /** @var RepoItemFile|null $repoItemFile */
        $repoItemFile = RepoItemFile::get()->find('Uuid', $repoItemFileId);
        if (!$repoItemFile) {
            return;
        }
        self::writeCSVRow($csvWriter, $repoItem, $repoItemFile, $downloadData, $channels);
    }

    private static function writeCSVRowForLink($csvWriter, RepoItem $repoItem, string $repoItemLinkId, array $downloadData, array $channels) {
        /** @var RepoItem|null $repoItemLink */
        $repoItemLink = RepoItem::get()->find('Uuid', $repoItemLinkId);
        if (!$repoItemLink) {
            return;
        }

        self::writeCSVRow($csvWriter, $repoItem, null, $downloadData, $channels);
    }

    private static function writeCSVRow($csvWriter, RepoItem $repoItem, ?RepoItemFile $repoItemFile, array $downloadData, array $channels) {
        $repoItemSummary = $repoItem->Summary;

        $authors = Arr::get($repoItemSummary, 'extra.authors');
        if (is_string($authors)) {
            $authors = [$authors];
        }

        $organisations = Arr::get($repoItemSummary, 'extra.organisations');
        if (is_string($organisations)) {
            $organisations = [$organisations];
        }

        $rowData = [
            Arr::get($repoItemSummary, 'title'),
            $authors[0] ?? null,
            $authors[1] ?? null,
            $authors[2] ?? null,
            $authors[3] ?? null,
            $authors[4] ?? null,
            $authors[5] ?? null,
            $authors[6] ?? null,
            $authors[7] ?? null,
            $authors[8] ?? null,
            $authors[9] ?? null,
            $repoItem->Institute->RootInstitute->Title,
            $organisations[0] ?? null,
            $organisations[1] ?? null,
            $organisations[2] ?? null,
            $organisations[3] ?? null,
            $organisations[4] ?? null,
            $organisations[5] ?? null,
            $organisations[6] ?? null,
            $organisations[7] ?? null,
            $organisations[8] ?? null,
            $organisations[9] ?? null,
            $repoItem->RepoType,
            $repoItemFile ? $repoItemFile->getPublicStreamURL() : null,
            $downloadData["url"] ?? null,
            $repoItem->getFrontEndURL(),
            $downloadData["totalDownloads"]
        ];

        // loop channels and check for each channel the channels array of a specific repoItem
        foreach ($channels as $channel) {
            $channelData = $downloadData["channelDownloads"][$channel] ?? null;
            if ($channelData === null) {
                $rowData[] = 0;
                $rowData[] = 0;
            } else {
                $rowData[] = $channelData["fileDownloads"];
                $rowData[] = $channelData["linkDownloads"];
            }
        }

        $csvWriter->insertOne($rowData);
    }

    private static function fetchPiwikData(PiwikClient $piwikClient, $scope, $repoType, $from, $to): array {
        $limit = 10000;
        $offset = 0;
        $data = [];

        while (true) {
            $piwikResponse = self::iteratePiwikData($piwikClient, $scope, $repoType, $from, $to, $offset, $limit);
            $body = json_decode($piwikResponse->getBody(), true);

            $meta = $body['meta'];
            $data = [...$data, ...$body['data']];

            if (count($body['data']) < $limit) {
                break;
            }

            $offset += 10000;
        }

        return [
            $meta,
            $data,
        ];
    }

    private static function iteratePiwikData(PiwikClient $piwikClient, $scope, $repoType, $from, $to, $offset, $limit = 100): ResponseInterface {
        return $piwikClient->query()
            ->from($from)
            ->to($to)
            ->columns(
                ["column_id" => "timestamp"],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_ID)->getRetrievalKey()],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_FILE_ID)->getRetrievalKey()],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_TYPE)->getRetrievalKey()],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::ROOT_INSTITUTE_ID)->getRetrievalKey()],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::UTM_SOURCE)->getRetrievalKey()],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::UTM_CONTENT)->getRetrievalKey()],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_LINK_ID)->getRetrievalKey()],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_LINK_URL)->getRetrievalKey()],
                ["column_id" => "custom_events"]
            )
            ->andFilter(function (PiwikFilter $filter) use ($scope, $repoType) {
                if ($repoType) {
                    $filter->filter(PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_TYPE)->getRetrievalKey(), "eq", ucfirst($repoType));
                }

                foreach ($scope as $id) {
                    $filter->filter(PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::ROOT_INSTITUTE_ID)->getRetrievalKey(), "eq", $id);
                }
            })
            ->limit($limit, $offset)
            ->execute();
    }

    private static function getWriter(): Writer {
        return Writer::createFromFileObject(new SplTempFileObject())
            ->setDelimiter(DataObjectCSVFileEncoder::DELIMITER)
            ->setEnclosure(DataObjectCSVFileEncoder::ENCLOSURE)
            ->setNewline("\r\n")
            ->setOutputBOM(Writer::BOM_UTF8);
    }

    private static function groupData(array $data, array $meta) {
        $repoItemIdColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_ID)->getRetrievalKey());
        $repoItemFileIdColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_FILE_ID)->getRetrievalKey());
        $repoItemLinkIdColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_LINK_ID)->getRetrievalKey());
        $repoItemLinkUrlColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_LINK_URL)->getRetrievalKey());
        $utmSourceColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::UTM_SOURCE)->getRetrievalKey());
        $utmContentColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::UTM_CONTENT)->getRetrievalKey());
        $repoTypeColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_TYPE)->getRetrievalKey());
        $customEventsColumnIndex = PiwikQuery::getColumnIndex($meta, "custom_events");

        $groupedDownloadData = [];
        $channels = [];

        foreach ($data as $row) {
            $repoItemId = $row[$repoItemIdColumnIndex];
            $repoType = $row[$repoTypeColumnIndex];
            $repoItemFileId = $row[$repoItemFileIdColumnIndex];
            $repoItemLinkId = $row[$repoItemLinkIdColumnIndex];
            $repoItemLinkUrl = $row[$repoItemLinkUrlColumnIndex];
            $utmSource = $row[$utmSourceColumnIndex];
            $utmContent = $row[$utmContentColumnIndex];
            $eventCount = $row[$customEventsColumnIndex];

            if (!$repoItemFileId && !$repoItemLinkId) {
                continue;
            }

            if ($utmSource === "") {
                $utmSource = "unknown";
            }

            $groupedDownloadData[$repoItemId]["repoType"] = $repoType;

            if (!isset($groupedDownloadData[$repoItemId]["links"])) {
                $groupedDownloadData[$repoItemId]["links"] = [];
            }

            if (!isset($groupedDownloadData[$repoItemId]["files"])) {
                $groupedDownloadData[$repoItemId]["files"] = [];
            }

            if ($repoItemFileId) {
                if (!isset($groupedDownloadData[$repoItemId]["files"][$repoItemFileId]["totalDownloads"])) {
                    $groupedDownloadData[$repoItemId]["files"][$repoItemFileId]["totalDownloads"] = 0;
                }

                if (!isset($groupedDownloadData[$repoItemId]["files"][$repoItemFileId]["channelDownloads"][$utmSource])) {
                    $groupedDownloadData[$repoItemId]["files"][$repoItemFileId]["channelDownloads"][$utmSource] = [
                        "linkDownloads" => 0,
                        "fileDownloads" => 0
                    ];
                }

                $groupedDownloadData[$repoItemId]["files"][$repoItemFileId]["channelDownloads"][$utmSource]["fileDownloads"] += $eventCount;

                // add row event count to total downloads
                $groupedDownloadData[$repoItemId]["files"][$repoItemFileId]["totalDownloads"] += $row[$customEventsColumnIndex];

            } elseif ($repoItemLinkId) {
                if (!isset($groupedDownloadData[$repoItemId]["links"][$repoItemLinkId]["totalDownloads"])) {
                    $groupedDownloadData[$repoItemId]["links"][$repoItemLinkId]["totalDownloads"] = 0;
                }

                if (!isset($groupedDownloadData[$repoItemId]["links"][$repoItemLinkId]["channelDownloads"][$utmSource])) {
                    $groupedDownloadData[$repoItemId]["links"][$repoItemLinkId]["channelDownloads"][$utmSource] = [
                        "linkDownloads" => 0,
                        "fileDownloads" => 0
                    ];
                }

                $groupedDownloadData[$repoItemId]["links"][$repoItemLinkId]["channelDownloads"][$utmSource]["linkDownloads"] += $eventCount;
                $groupedDownloadData[$repoItemId]["links"][$repoItemLinkId]["url"] = $repoItemLinkUrl;

                // add row event count to total downloads
                $groupedDownloadData[$repoItemId]["links"][$repoItemLinkId]["totalDownloads"] += $row[$customEventsColumnIndex];
            }

            // Add channel source to the channel array if not already present, this list of channels is later used for correctly writing the csv
            if (!in_array($utmSource, $channels)) {
                $channels[] = $utmSource;
            }
        }

        sort($channels);
        return [$channels, $groupedDownloadData];
    }
}