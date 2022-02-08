<?php

use League\Csv\Writer;
use Ramsey\Uuid\Uuid;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Cache_RecordNode;
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

    public static function repoItemListToCSVFile(DataList $repoItems, $purgeCache = false) {
        $uuid = Uuid::uuid4();
        $fileName = Security::getCurrentUser()->ID . "-export-$uuid.csv";
        $canReportMethod = function (DataObject $viewMaybeObj) {
            return $viewMaybeObj->canReport(Security::getCurrentUser());
        };
        $repoItems = $repoItems->filterByCallback($canReportMethod);
        $csvString = static::getRepoItemsCSV($repoItems, $purgeCache);

        $csvFile = "assets/$fileName";
        file_put_contents($csvFile, $csvString);

        $file = new ReportFile();
        $file->setFromLocalFile($csvFile);
        $file->write();

        Controller::curr()->getResponse()->addHeader('Location', $file->getStreamURL());
        return 'redirect to download';
    }

    public static function statsDownloadsToCSVFile(DataList $downloadItems) {
        $downloadFileIds = $downloadItems->columnUnique('RepoItemFileID');
        $statsData = [];
        // TODO: performance verbetering mogelijk
        foreach ($downloadFileIds as $downloadFileId) {
            $repoItemFile = RepoItemFile::get()->filter('ID', $downloadFileId)->first();
            $repoItem = $repoItemFile->RepoItem();
            if ($repoItem && $repoItem->exists()) {
                $publicDownloads = $downloadItems->filter('RepoItemFileID', $repoItemFile->ID)->filter('IsPublic', true)->count();
                $privateDownloads = $downloadItems->filter('RepoItemFileID', $repoItemFile->ID)->filter('IsPublic', false)->count();
                $statsData[] = [$repoItemFile->getPublicStreamURL(), $repoItem->getFrontEndURL(), $repoItem->SubType, $publicDownloads, $privateDownloads];
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

        $csvFile = "assets/$fileName";
        file_put_contents($csvFile, $csvString);

        $file = new ReportFile();
        $file->setFromLocalFile($csvFile);
        $file->write();

        Controller::curr()->getResponse()->addHeader('Location', $file->getStreamURL());
        return 'redirect to download';
    }

    public static function repoItemStatsToCSVFile(DataList $repoItems, $purgeCache = false) {
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

        $csvFile = "assets/$fileName";
        file_put_contents($csvFile, $csvString);

        $file = new ReportFile();
        $file->setFromLocalFile($csvFile);
        $file->write();

        Controller::curr()->getResponse()->addHeader('Location', $file->getStreamURL());
        return 'redirect to download';
    }

    public static function getCSVRowFor($repoItem, bool $purgeCache, $describingProtocol) {
        Logger::debugLog("CSV : " . $repoItem->Uuid . ' : purge=' . $purgeCache);
        $rowData = [$repoItem->getField('Uuid'), $repoItem->getField('RepoType'), $repoItem->Institute()->Title, $repoItem->getField('Status')];
        if (!$purgeCache) {
            $cachedNode = Cache_RecordNode::get()->where(['ProtocolID' => $describingProtocol->ID, 'RepoItemID' => ($repoItem->ID), 'CachedLastEdited' => $repoItem->LastEdited])->first();
            if ($cachedNode) {
                Logger::debugLog("HIT : " . $repoItem->Uuid);
                $rowData = json_decode($cachedNode->getField('Data'));
                return $rowData;
            } else {
                Logger::debugLog("MISS : " . $repoItem->Uuid);
            }
        }
        $metaFieldLastDescribed = new MetaField();
        $sameMetaFieldCount = 0;

        foreach ($describingProtocol->ProtocolNodes()->filter('ParentNodeID', 0) as $node) {
            if ($node->MetaField()->ID > 0 && $node->MetaField()->ID == $metaFieldLastDescribed->ID) {
                $sameMetaFieldCount++;
            } else {
                $sameMetaFieldCount = 0;
            }

            $metaFieldLastDescribed = $node->MetaField();
            $jsonDescription = $node->describeUsing($repoItem, 'json');
            if (is_array($jsonDescription)) {
                $valueIndex = 0;
                $setValue = false;
                foreach ($jsonDescription as $desc) {
                    if ($sameMetaFieldCount == $valueIndex) {
                        if (is_array($desc)) {
                            $str = '';
                            foreach ($desc as $key => $value) {
                                if ($key && $value) {
                                    // seperate with hard return
                                    if (strlen($str) > 0) {
                                        $str = $str . "\r\n";
                                    }
                                    // seperate labels from values
                                    $str = $str . $key . ':' . $value;
                                }
                            }
                            $desc = $str;
                        }
                        $rowData[] = $desc;
                        $setValue = true;
                        break;
                    }
                    $valueIndex++;
                }
                if (!$setValue) {
                    $rowData[] = '';
                }
            } else {
                if ($sameMetaFieldCount == 0) {
                    $rowData[] = $jsonDescription;
                } else {
                    $rowData[] = '';
                }
            }
        }
        $cachedNode = Cache_RecordNode::get()->where(['ProtocolID' => $describingProtocol->ID, 'RepoItemID' => ($repoItem->ID)])->first();
        if (is_null($cachedNode)) {
            $cachedNode = Cache_RecordNode::create();
            Logger::debugLog("CSV : " . $repoItem->Uuid . ' create cache');
            $cachedNode->setField('RepoItemID', $repoItem->ID);
            $cachedNode->setField('ProtocolID', $describingProtocol->ID);
        } else {
            Logger::debugLog("CSV : " . $repoItem->Uuid . ' update cache');
        }

        $cachedNode->setField('Data', json_encode($rowData));
        $cachedNode->setField('ProtocolVersion', $describingProtocol->Version);
        $cachedNode->setField('CachedLastEdited', $repoItem->LastEdited);
        try {
            $cachedNode->write();
        } catch (ValidationException $e) {
            Logger::debugLog($e->getMessage());
        }
        return $rowData;
    }
}