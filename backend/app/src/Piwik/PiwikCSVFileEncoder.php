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
use SilverStripe\Piwik\PiwikCustomDimensionMapping;
use SplTempFileObject;
use SurfSharekit\Extensions\Security;
use SurfSharekit\Models\ExportItem;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\RepoItemSummary;
use SurfSharekit\Piwik\Api\PiwikAPI;
use SurfSharekit\Piwik\Api\PiwikFilter;
use SurfSharekit\Piwik\Api\PiwikQuery;

class PiwikCSVFileEncoder
{
    public static function getDownloadsCSV(ExportItem $exportItem, $vars) {
        $repoType = $vars['filter']['repoType'] ?? null;
        $scope = explode(",", $vars['filter']['scope']);
        $from = $vars['filter']['downloadDate']['GE'];
        $to = $vars['filter']['downloadDate']['LE'];

        $csvWriter = self::getWriter();

        $headers = [
            'Titel',
            'Auteur 1',
            'Auteur 2',
            'Auteur 3',
            'Auteur 4',
            'Auteur 5',
            'Organisatie',
            'Afdeling, lectoraat, opleiding 1',
            'Afdeling, lectoraat, opleiding 2',
            'Afdeling, lectoraat, opleiding 3',
            'Afdeling, lectoraat, opleiding 4',
            'Afdeling, lectoraat, opleiding 5',
            'Type',
            'Link naar bestand',
            'Link naar repoItem',
            'Aantal downloads'
        ];
        $csvWriter->insertOne($headers);

        $piwikApi = new PiwikAPI(
            Environment::getEnv('PIWIK_API_CLIENT_ID'),
            Environment::getEnv('PIWIK_API_CLIENT_SECRET'),
            Environment::getEnv('PIWIK_URL'),
            Environment::getEnv('PIWIK_SITE_ID')
        );

        $person = Person::get()->find("ID", $exportItem->PersonID);
        if ($person == null) {
            throw new Exception("No person was linked to export item $exportItem->Uuid");
        }

        [$meta, $data] = self::fetchPiwikData($piwikApi, $scope, $repoType, $from, $to);

        $groupedData = self::groupData($data, $meta);

        foreach ($groupedData as $downloadData) {
            $repoItemFileID = $downloadData[PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_FILE_ID)->getRetrievalKey())];
            $repoItemID = $downloadData[PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_ID)->getRetrievalKey())];
            $repoType = $downloadData[PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_TYPE)->getRetrievalKey())];
            $events = $downloadData[PiwikQuery::getColumnIndex($meta, "custom_events")];

            $repoItemFile = RepoItemFile::get()->find('Uuid', $repoItemFileID);
            $repoItem = RepoItem::get()->find('Uuid', $repoItemID);

            if (!$repoItem || !$repoItemFile) {
                continue;
            }

            if (!$repoItem->exists() || !$repoItemFile->exists()) {
                continue;
            }

            $canReport = $repoItem->canReport($person);
            if (!$canReport) {
                continue;
            }

            $repoItemSummary = $repoItem->Summary;

            $authors = Arr::get($repoItemSummary, 'extra.authors');
            if (is_string($authors)) {
                $authors = [$authors];
            }

            $organisations = Arr::get($repoItemSummary, 'extra.organisations');
            if (is_string($organisations)) {
                $organisations = [$organisations];
            }

            $csvWriter->insertOne([
                Arr::get($repoItemSummary, 'title'),
                $authors[0] ?? null,
                $authors[1] ?? null,
                $authors[2] ?? null,
                $authors[3] ?? null,
                $authors[4] ?? null,
                $repoItem->Institute->RootInstitute->Title,
                $organisations[0] ?? null,
                $organisations[1] ?? null,
                $organisations[2] ?? null,
                $organisations[3] ?? null,
                $organisations[4] ?? null,
                $repoType,
                $repoItemFile->getPublicStreamURL(),
                $repoItem->getFrontEndURL(),
                $events
            ]);
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

    private static function fetchPiwikData(PiwikAPI $piwikAPI, $scope, $repoType, $from, $to): array {
        $limit = 100;
        $offset = 0;
        $data = [];

        while (true) {
            $piwikResponse = self::iteratePiwikData($piwikAPI, $scope, $repoType, $from, $to, $offset, $limit);
            $body = json_decode($piwikResponse->getBody(), true);

            $meta = $body['meta'];
            $data = [...$data, ...$body['data']];

            if (count($body['data']) < $limit) {
                break;
            }

            $offset += 100;
        }

        return [
            $meta,
            $data,
        ];
    }

    private static function iteratePiwikData(PiwikAPI $piwikAPI, $scope, $repoType, $from, $to, $offset, $limit = 100): ResponseInterface {
        return $piwikAPI->query()
            ->from($from)
            ->to($to)
            ->columns(
                ["column_id" => "timestamp"],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_ID)->getRetrievalKey()],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_FILE_ID)->getRetrievalKey()],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_TYPE)->getRetrievalKey()],
                ["column_id" => PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::ROOT_INSTITUTE_ID)->getRetrievalKey()],
                ["column_id" => "custom_events"]
            )
            ->andFilter(function (PiwikFilter $filter) use ($scope, $repoType) {
                if ($repoType) {
                    $filter->filter(PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_TYPE)->getRetrievalKey(), "eq", ucfirst($repoType));
                }

                $filter->filter(PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_FILE_ID)->getRetrievalKey(), "neq", "")
                    ->orFilter(function (PiwikFilter $filter) use ($scope) {
                        foreach ($scope as $id) {
                            $filter->filter(PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::ROOT_INSTITUTE_ID)->getRetrievalKey(), "eq", $id);
                        }
                    });
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
        $repoItemFileIdColumnIndex = PiwikQuery::getColumnIndex($meta, PiwikCustomDimensionMapping::getCustomDimension(CustomEventDimension::REPO_ITEM_FILE_ID)->getRetrievalKey());
        $customEventsColumnIndex = PiwikQuery::getColumnIndex($meta, "custom_events");
        $grouped = [];

        foreach ($data as $row) {
            if (!isset($grouped[$row[$repoItemFileIdColumnIndex]])) {
                $grouped[$row[$repoItemFileIdColumnIndex]] = $row;
            } else {
                $grouped[$row[$repoItemFileIdColumnIndex]][$customEventsColumnIndex] += $row[$customEventsColumnIndex];
            }
        }

        return $grouped;
    }
}