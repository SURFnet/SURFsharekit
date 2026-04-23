<?php

namespace SilverStripe\EnvironmentExport\Tasks;

use DateTime;
use DateTimeInterface;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\EnvironmentExport\DataObjects\EnvironmentExportFile;
use SilverStripe\EnvironmentExport\DataObjects\EnvironmentExportRequest;
use SilverStripe\EnvironmentExport\ExportDataFormatter;
use SilverStripe\EnvironmentExport\JSONExportDataFormatter;
use SilverStripe\EnvironmentExport\RelationType;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ValidationException;
use SurfSharekit\Models\Helper\Logger;
use Throwable;

class EnvironmentExportTask extends BuildTask {

    private string $processID;
    private string $exportStartedDateTime;

    private int $totalObjectsToExport = 0;

    private array $exportJson = [
        "meta" => [],
        "data" => [],
    ];

    public function __construct() {
        parent::__construct();
        $this->processID = Uuid::uuid4()->toString();
        $this->exportStartedDateTime = (new DateTime())->format(DateTimeInterface::ATOM);
    }

    public function run($request) {
        set_time_limit(0);
        ini_set("max_execution_time", 0);

        // Claim queued EnvironmentExportRequest
        $this->claimQueuedEnvironmentExportRequest();

        /** @var EnvironmentExportRequest $environmentExportRequest */
        $environmentExportRequest = EnvironmentExportRequest::get()->find("ProcessID", $this->processID);
        if (!$environmentExportRequest) {
            return;
        }

        $this->collectDataToExport();
        $this->addExportMetadata();
        $file = $this->writeExportFile();
        $this->writeEnvironmentExportRequest($environmentExportRequest, $file->ID);
    }

    /**
     * Writes the EnvironmentExportRequest after updating the status and linking the generated export file object
     * @param EnvironmentExportRequest $environmentExportRequest
     * @param int $exportFileID
     * @return void
     * @throws ValidationException
     */
    private function writeEnvironmentExportRequest(EnvironmentExportRequest $environmentExportRequest, int $exportFileID) {
        $environmentExportRequest->EnvironmentExportFileID = $exportFileID;
        $environmentExportRequest->Status = "COMPLETED";
        $environmentExportRequest->write();
    }

    /**
     * Writes the export file
     * @return void
     * @throws ValidationException
     */
    private function writeExportFile(): EnvironmentExportFile {
        $environment = Environment::getEnv('APPLICATION_ENVIRONMENT');
        $file = EnvironmentExportFile::create();
        $file->setFromString(json_encode($this->exportJson), "sharekit-{$environment}-export-{$this->processID}.json");
        $file->write();
        return $file;
    }

    /**
     * Collects all data and metadata needed for the environment export
     * @return void
     * @throws ReflectionException
     */
    private function collectDataToExport() {
        $formatter = new JSONExportDataFormatter();
        $dataObjectClassesToExport = $formatter->getAllExportableDataObjectClasses();

        for ($i = 0; $i < count($dataObjectClassesToExport); $i++) {
            /** @var DataObject $dataObjectClass */
            $dataObjectClass = $dataObjectClassesToExport[$i];

            $dataList = $dataObjectClass::get()->sort("ID ASC");

            if (method_exists($dataObjectClass, 'updateDataListForExport')) {
                $dataObjectClass::updateDataListForExport($dataList);
            } else {
                // Invokes the method on all Extensions, useful for Member
                $dataObjectClass::singleton()->extend('updateDataListForExport', $dataList);
            }

            // Add data for this class
            Logger::debugLog("Exporting DataObjects of class $dataObjectClassesToExport[$i]");
            $this->addDataObjectClassDataToExport($formatter, $dataList, $i);

            // Add meta information for this class
            $this->addDataObjectClassMetadataToExport($formatter, $dataObjectClass, $i, $dataList);
        }
    }

    /**
     * Adds some metadata to the export json
     * @return void
     */
    private function addExportMetadata() {
        $environment = Environment::getEnv('APPLICATION_ENVIRONMENT');
        $this->exportJson["meta"]["environment"] = $environment;
        $this->exportJson["meta"]["exportStarted"] = $this->exportStartedDateTime;
        $this->exportJson["meta"]["exportCompleted"] = (new DateTime())->format(DateTimeInterface::ATOM);
        $this->exportJson["meta"]["exportedBy"] = "System";
        $this->exportJson["meta"]["totalCount"] = $this->totalObjectsToExport;
    }

    /**
     * Adds metadata to the export for a particular DataObject class
     * @param ExportDataFormatter $formatter
     * @param string $dataObjectClass
     * @param int $dataObjectClassIndex
     * @param DataList $dataClassDataList
     * @return void
     */
    private function addDataObjectClassMetadataToExport(ExportDataFormatter $formatter, string $dataObjectClass, int $dataObjectClassIndex, DataList $dataClassDataList) {
        $count = $dataClassDataList->count();
        $this->exportJson["data"][$dataObjectClassIndex]["meta"]["class"] = $dataObjectClass;
        $this->exportJson["data"][$dataObjectClassIndex]["meta"]["count"] = $count;
        $this->exportJson["data"][$dataObjectClassIndex]["meta"]["table"] = DataObject::getSchema()->tableName($dataObjectClass);
        $hasOneRelations = singleton($dataObjectClass)->hasOne();

        $removeFields = $formatter->getRemoveFields();
        $includeFields = $formatter->getCustomFields();
        foreach ($hasOneRelations as $hasOneRelationName => $hasOneRelationClass) {
            if ($includeFields && count($includeFields) && !in_array($hasOneRelationName, $includeFields)) {
                continue;
            }
            if ($removeFields && in_array($hasOneRelationName, $removeFields)) {
                continue;
            }
            $this->exportJson["data"][$dataObjectClassIndex]["meta"]["relations"][] = [
                "type" => RelationType::HAS_ONE,
                "table" => DataObject::getSchema()->tableName($hasOneRelationClass),
                "class" => $hasOneRelationClass,
                "name" => $hasOneRelationName,
            ];
        }
        $this->totalObjectsToExport += $count;
    }

    /**
     * Adds the actual data to the export for a particular DataObject class
     * @param JSONExportDataFormatter $formatter
     * @param DataList $dataList
     * @param int $dataObjectClassIndex
     * @return void
     */
    private function addDataObjectClassDataToExport(JSONExportDataFormatter $formatter, DataList $dataList, int $dataObjectClassIndex) {
        $this->exportJson["data"][$dataObjectClassIndex]["data"] = []; // set empty array as default
        $dataObjectTotal = $dataList->count();
        $limit = 10000;

        $iterations = 0;
        $count =  0;
        while ($count < $dataObjectTotal) {
            $iterations++;
            $count += $limit;
        }

        Logger::debugLog("total for {$dataList->dataClass()}: $dataObjectTotal");
        Logger::debugLog("iterations for {$dataList->dataClass()}: $iterations");

        // Run the convert call once so that the formatter knows all fields and relations, even
        // if the list is empty
        $formatter->convertDataObject(Injector::inst()->get($dataList->dataClass()));
        for ($i = 0; $i < $iterations; $i++) {
            Logger::debugLog("iteration $i");
            $dataListSubset = $dataList->limit($limit, $i * $limit);

            /**
             * @var int $key
             * @var DataObject $dataObject
             */
            foreach ($dataListSubset as $dataObject) {
                $this->exportJson["data"][$dataObjectClassIndex]["data"][] = $formatter->convertDataObject($dataObject);
            }
        }
    }

    /**
     * Claims an EnvironmentExportRequest for processing. A claim can only success if there is at least 1 queued request
     * and no other request is currently being processed.
     * @return void
     */
    private function claimQueuedEnvironmentExportRequest() {
        try {
            $dbConnection = DB::get_conn();
            $dbConnection->transactionStart();
            $environmentExportRequestBeingProcessed = EnvironmentExportRequest::get()->filter(["Status" => "PROCESSING"])->first();
            if ($environmentExportRequestBeingProcessed) {
                Logger::infoLog("An EnvironmentExportRequest is still being processed, aborting");
                return;
            }

            DB::prepared_query("
                UPDATE `SurfSharekit_EnvironmentExportRequest`
                SET ProcessID = CASE
                    WHEN ProcessID IS NULL THEN ?
                    ELSE ProcessID END,
                    Status = 'PROCESSING',
                    Queued = 0
                WHERE Queued = 1
            ", [$this->processID]);
            $dbConnection->transactionEnd();
        } catch(Throwable $e) {
            Logger::infoLog("An error occurred while trying to claim an EnvironmentExportRequest: {$e->getMessage()}");
            $dbConnection->transactionRollback();
        }
    }

}