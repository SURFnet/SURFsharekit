<?php

use League\Csv\Writer;
use Ramsey\Uuid\Uuid;
use SilverStripe\Assets\File;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Security;
use SurfSharekit\ApiCache\ApiCacheController;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\ExportItem;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\RepoItemFile;
use SurfSharekit\Models\ReportFile;

/**
 * Class DataObjectCSVFileEncoder
 * This class creates a CSV file based on a list of RepoItems
 */
class DataObjectCSVFileEncoder {
    const DELIMITER = ';';
    const ENCLOSURE = '"';

    public static function getRepoItemsCSV($repoItems, $purgeCache = false) {
        $csvWriter = Writer::createFromFileObject(new SplTempFileObject());
        $csvWriter->setDelimiter(DataObjectCSVFileEncoder::DELIMITER);
        $csvWriter->setEnclosure(DataObjectCSVFileEncoder::ENCLOSURE);
        $csvWriter->setNewline("\r\n"); //use windows line endings for compatibility with some csv libraries
        $csvWriter->setOutputBOM('');

        $describingProtocol = Protocol::get()->filter('SystemKey', 'CSV')->first();

        $headers = ['identifier', 'type', 'organisation', 'status'];
        if (!$describingProtocol || !$describingProtocol->exists()) {
            return 'CSV Protocol does not exist';
        }

        foreach ($describingProtocol->ProtocolNodes()->filter('ParentNodeID', 0) as $node) {
            $headers[] = $node->NodeTitle;
        }
        $csvWriter->insertOne($headers);

        foreach ($repoItems as $repoItem) {
            $rowData = static::getCSVRowFor($repoItem, $purgeCache, $describingProtocol);
            Logger::debugLog($rowData);
            $csvWriter->insertOne($rowData);
        }
        return (string)$csvWriter;
    }

    public static function repoItemListToCSVFile(ExportItem $exportItem, DataList $repoItems, $purgeCache = false) {
        $uuid = Uuid::uuid4();
        $fileName = Security::getCurrentUser()->ID . "-export-$uuid.csv";
        $canReportMethod = function (DataObject $viewMaybeObj) {
            return $viewMaybeObj->canReport(Security::getCurrentUser());
        };
        $repoItems = $repoItems->filterByCallback($canReportMethod);
        $csvString = static::getRepoItemsCSV($repoItems, $purgeCache);

        $file = new File();
        $file->setFromString($csvString, $fileName);
        $file->write();

        $exportItem->FileID = $file->ID;
        $exportItem->write();

        return true;
    }

    public static function statsDownloadsToCSVFile(ExportItem $exportItem, DataList $downloadItems) {
        $downloadFileIds = $downloadItems->columnUnique('RepoItemFileID');
        $statsData = [];
        // TODO: performance verbetering mogelijk
        foreach ($downloadFileIds as $downloadFileId) {
            $repoItemFile = RepoItemFile::get()->filter('ID', $downloadFileId)->first();
            // removed files cannot be counted, so exclude
            if($repoItemFile) {
                $repoItem = $repoItemFile->RepoItem();
                if ($repoItem && $repoItem->exists()) {
                    $publicDownloads = $downloadItems->filter('RepoItemFileID', $repoItemFile->ID)->filter('IsPublic', true)->count();
                    $privateDownloads = $downloadItems->filter('RepoItemFileID', $repoItemFile->ID)->filter('IsPublic', false)->count();
                    $statsData[] = [$repoItemFile->getPublicStreamURL(), $repoItem->getFrontEndURL(), $repoItem->SubType, $publicDownloads, $privateDownloads];
                }
            }
        }

        $csvWriter = Writer::createFromFileObject(new SplTempFileObject());
        $csvWriter->setDelimiter(DataObjectCSVFileEncoder::DELIMITER);
        $csvWriter->setEnclosure(DataObjectCSVFileEncoder::ENCLOSURE);
        $csvWriter->setNewline("\r\n"); //use windows line endings for compatibility with some csv libraries
        $csvWriter->setOutputBOM(Writer::BOM_UTF8);

        $headers = ['Download url', 'Publicatierecord', 'Type', 'Public', 'Private'];

        $csvWriter->insertOne($headers);

        foreach ($statsData as $stats) {
            Logger::debugLog("CSV downloads: " . print_r($stats, true));
//            $rowData = [$instituteTitle];
//            $rowData[] = array_key_exists('public', $stats) ? $stats['public'] : 0;
//            $rowData[] = array_key_exists('private', $stats) ? $stats['private'] : 0;
            $csvWriter->insertOne($stats);
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

    public static function repoItemStatsToCSVFile(ExportItem $exportItem, DataList $repoItems, $purgeCache = false) {
        $canReportMethod = function (DataObject $viewMaybeObj) {
            return $viewMaybeObj->canReport(Security::getCurrentUser());
        };

        $repoItems = $repoItems->filterByCallback($canReportMethod);

        $statsData = [];
        $repoTypes = [];
        foreach ($repoItems as $repoItem) {
            $instituteTitle = $repoItem->Institute()->Title;
            if (!array_key_exists($instituteTitle, $statsData)) {
                $statsData[$instituteTitle] = [];
            }
            $repoType = $repoItem->getField('RepoType');
            if (!array_key_exists($repoType, $statsData[$instituteTitle])) {
                $statsData[$instituteTitle][$repoType] = 0;
            }
            if (!array_key_exists($repoType, $repoTypes)) {
                $repoTypes[$repoType] = $repoType;
            }
            $statsData[$instituteTitle][$repoType] = $statsData[$instituteTitle][$repoType] + 1;
        }

        $csvWriter = Writer::createFromFileObject(new SplTempFileObject());
        $csvWriter->setDelimiter(DataObjectCSVFileEncoder::DELIMITER);
        $csvWriter->setEnclosure(DataObjectCSVFileEncoder::ENCLOSURE);
        $csvWriter->setNewline("\r\n"); //use windows line endings for compatibility with some csv libraries
        $csvWriter->setOutputBOM(Writer::BOM_UTF8);

        foreach ($repoTypes as $repoType) {
            $headers[] = $repoType;
        }
        $csvWriter->insertOne($headers);

        foreach ($statsData as $instituteTitle => $stats) {
            Logger::debugLog("CSV stats : " . $instituteTitle . ' : ' . print_r($stats, true));
            $rowData = [$instituteTitle];
            foreach ($repoTypes as $repoType) {
                $rowData[] = array_key_exists($repoType, $stats) ? $stats[$repoType] : 0;
            }
            $csvWriter->insertOne($rowData);
        }

        $uuid = Uuid::uuid4();
        $fileName = Security::getCurrentUser()->ID . "-stats-$uuid.csv";
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

    public static function getCSVRowFor($repoItem, bool $purgeCache, $describingProtocol): array {
        Logger::debugLog("CSV : " . $repoItem->Uuid . ' : purge=' . $purgeCache);
        $res = ApiCacheController::getRepoItemData($describingProtocol, $repoItem, $purgeCache);
        return json_decode($res, true)??[];
    }
}