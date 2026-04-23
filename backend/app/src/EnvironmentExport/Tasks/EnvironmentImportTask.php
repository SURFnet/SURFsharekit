<?php

namespace SilverStripe\EnvironmentExport\Tasks;

use Exception;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\TestOnly;
use SilverStripe\EnvironmentExport\DataObjects\EnvironmentExportFile;
use SilverStripe\EnvironmentExport\DataObjects\EnvironmentExportRequest;
use SilverStripe\EnvironmentExport\DataObjects\EnvironmentImportRequest;
use SilverStripe\EnvironmentExport\Exportable;
use SilverStripe\EnvironmentExport\JSONExportDataFormatter;
use SilverStripe\EnvironmentExport\Models\Export;
use SilverStripe\EnvironmentExport\Models\ExportedDataObject;
use SilverStripe\EnvironmentExport\Models\ExportedDataObjectMetadata;
use SilverStripe\EnvironmentExport\Models\ExportedDataObjectRelationMetadata;
use SilverStripe\EnvironmentExport\RelationType;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SurfSharekit\constants\GroupConstant;
use SurfSharekit\Models\Helper\Logger;
use Throwable;

class EnvironmentImportTask extends BuildTask {
    protected string $processID;
    private array $dataObjectMetadata = [];

    /** @var array $relationCache Prevents the execution of duplicate queries during the import process */
    private array $relationCache;

    public function __construct() {
        parent::__construct();
        $this->processID = Uuid::uuid4()->toString();
        $this->relationCache = [];
    }
    public function run($request) {
        ini_set('max_execution_time', '-1');
        ini_set('memory_limit', '300M');

        if(Environment::getEnv('APPLICATION_ENVIRONMENT') == 'live') {
            throw new Exception("Importing an environment export is not allowed on the production environment");
        }

        /** @var null|EnvironmentImportRequest $environmentExportRequest */
        $environmentImportRequest = $this->claimQueuedEnvironmentImportRequest();
        if (!$environmentImportRequest) {
            return;
        }

        /** @var EnvironmentExportFile $jsonFile */
        $exportJson = $this->getExportFileContents($environmentImportRequest);

        $databaseConnection = DB::get_conn();
        try {
            $databaseConnection->transactionStart();
            $this->doImport($exportJson, $environmentImportRequest);
            $databaseConnection->transactionEnd();
        } catch (Throwable $e) {
            $databaseConnection->transactionRollback();
            Logger::warnLog("An error occurred during the environment import: {$e->getMessage()}");
        }
    }

    public function doImport(string $exportJson, ?EnvironmentImportRequest $environmentImportRequest) {
        $export = $this->ensureExportFileIntegrity($exportJson);

        // Truncate tables and fill them with data from the export file
        $this->importData($export);

        // Reconstruct all relations
        $this->reconstructRelations($export);

        // Set EnvironmentImportRequest status
        if ($environmentImportRequest) {
            $this->writeEnvironmentImportRequest($environmentImportRequest);
        }
    }

    /**
     * Gets the JSON string from the .json file linked to the EnvironmentImportRequest
     * @param EnvironmentImportRequest $environmentImportRequest
     * @return string
     */
    private function getExportFileContents(EnvironmentImportRequest $environmentImportRequest): string {
        $jsonFile = $environmentImportRequest->ImportFile();
        return  file_get_contents($jsonFile->getAbsoluteURL()) ?? "";
    }

    /**
     * Imports the data from the export file after truncating the involved tables
     * @param Export $export
     * @return void
     * @throws ValidationException|ReflectionException
     */
    private function importData(Export $export) {
        $dataObjects = ClassInfo::subclassesFor(DataObject::class, false);
        $exportableTables = [];
        /** @var DataObject $dataObject */
        foreach ($dataObjects as $dataObject) {
            if ($dataObject::singleton() instanceof TestOnly) continue;

            $schema = $dataObject::getSchema();
            $fields = $schema->databaseFields($dataObject, false);
            unset($fields['ID']);
            if ($fields) {
                $tableName = $dataObject::config()->get('table_name');
                $exportableTables[] = $tableName;
                if ($dataObject::has_extension(Versioned::class)) {
                    $exportableTables[] = $tableName . "_Versions";

                    /** @var String|DataObject|Versioned $singleton */
                    $singleton = $dataObject::singleton();
                    if ($singleton->hasStages()) {
                        $liveTable = $singleton->stageTable($tableName, Versioned::LIVE);
                        $exportableTables[] = $liveTable;
                    }
                }
            }
            if ($manyMany = $dataObject::config()->uninherited('many_many')) {
                foreach ($manyMany as $component => $spec) {
                    $manyManyComponent = $schema->manyManyComponent($dataObject, $component);
                    $tableOrClass = $manyManyComponent['join'];
                    $exportableTables[] = $tableOrClass;
                }
            }
        }

        foreach ($exportableTables as $table) {
            Logger::debugLog("Truncating table $table");
            $this->truncateTable($table);
        }

        Logger::debugLog("Generating default groups...");
        // Works admins group
        $worksadminGroup = Group::create();
        $worksadminGroup->setField('Title', GroupConstant::WORKSADMIN_TITLE);
        $worksadminGroup->setField('Code', GroupConstant::WORKSADMIN_CODE);
        $worksadminGroup->write();

        // API group
        $apiGroup = Group::create();
        $apiGroup->setField('Title', GroupConstant::API_TITLE);
        $apiGroup->setField('Code', GroupConstant::API_CODE);
        $apiGroup->write();

        // Administrators (may already exist)
        if (!Group::get()->find('Code', 'administrators')) {
            $adminGroup = Group::create();
            $adminGroup->setField('Title', "Administrators");
            $adminGroup->setField('Code', "administrators");
            $adminGroup->write();
        }

        Logger::debugLog("Start importing data...");
        /** @var ExportedDataObject $exportedDataObject */
        foreach ($export->getData() as $exportedDataObject) {
            $exportedDataObjectMetadata = $exportedDataObject->getMetadata();
            $className = $exportedDataObjectMetadata->getClass();

            Logger::debugLog("Starting import of {$exportedDataObjectMetadata->getCount()} objects of class '{$exportedDataObjectMetadata->getClass()}'");
            foreach ($exportedDataObject->getData() as $exportedDataObjectData) {
                $dataObject = $className::create(json_decode($exportedDataObjectData, true));
                $dataObject->ImportTaskWrite = true;
                $dataObject->write();
            }
            Logger::debugLog("Completed Importing {$exportedDataObjectMetadata->getCount()} objects of class '{$exportedDataObjectMetadata->getClass()}'");
        }
        Logger::debugLog("Completed the data import, proceeding to relation reconstruction phase...");
    }

    /**
     * Reconstructs all relations of the data included in the export file
     * @param Export $export
     * @return void
     * @throws Exception
     */
    private function reconstructRelations(Export $export) {
        Logger::debugLog("Starting relation reconstruction of the imported data");
        foreach ($export->getData() as $exportedDataObject) {
            /** @var ExportedDataObjectMetadata $exportedDataObjectMetadata */
            $exportedDataObjectMetadata = $exportedDataObject->getMetadata();
            $relationsMetaData = $exportedDataObjectMetadata->getRelations();
            /** @var class-string $className */
            $className = $exportedDataObjectMetadata->getClass();

            /** @var DataObject $className */
            $dataList = $className::get();
            if (method_exists($className, 'updateDataListForExport')) {
                $className::updateDataListForExport($dataList);
            }
            Logger::debugLog("Started relation reconstruction for class '{$exportedDataObjectMetadata->getClass()}'");
            $pageSize = 20000;
            $pageNum = ceil($dataList->count() / $pageSize);
            for ($curPage = 0; $curPage < $pageNum; $curPage++) {
                $subList = $dataList->limit($pageSize, $curPage * $pageSize);
                foreach ($subList as $dataObject) {
                    // for each dataobject we loop the relations
                    /** @var ExportedDataObjectRelationMetadata $relationMetaData */
                    foreach ($relationsMetaData as $relationMetaData) {
                        $this->reconstructRelation($dataObject, $relationMetaData);
                    }
                    $dataObject->ImportTaskWrite = true;
                $dataObject->write();
                }
            }
            Logger::debugLog("Completed relation reconstruction for class '{$exportedDataObjectMetadata->getClass()}'");
        }
        Logger::debugLog("Completed relation reconstruction");
    }

    /**
     * This function reconstructs the relation of a DataObject by relation type
     * @param DataObject $dataObject
     * @param ExportedDataObjectRelationMetadata $relationMetadata
     * @return void
     * @throws Exception
     */
    private function reconstructRelation(DataObject $dataObject, ExportedDataObjectRelationMetadata $relationMetadata) {
        $type = $relationMetadata->getType();
        switch ($type) {
            case RelationType::HAS_ONE: {
                $this->reconstructHasOneRelation($dataObject, $relationMetadata);
                break;
            }
            default: throw new Exception("Encountered an unsupported relation type: '$type'");
        }
    }

    /**
     * Reconstructs a has_one relation by looking up relational DataObjects by Uuid so the ID field of the relation can be set
     * This function does not write the DataObject as this would mean a write for each has_one on the provided DataObject
     * @param DataObject $dataObject
     * @param ExportedDataObjectRelationMetadata $relationMetadata
     * @return void
     */
    private function reconstructHasOneRelation(DataObject $dataObject, ExportedDataObjectRelationMetadata $relationMetadata) {
        /** @var class-string $relationClass */
        $relationClass = $relationMetadata->getClass();
        $relationName = $relationMetadata->getName();
        $relationUUIDField = $relationName . "Uuid";
        $relationIDField = $relationName . "ID";

        $relationalDataObjectID = $this->relationCache[$relationMetadata->getClass()][$dataObject->$relationUUIDField] ?? null;
        if ($relationalDataObjectID) {
            $dataObject->$relationIDField = $relationalDataObjectID;
            return;
        }

        // Try to find relational DataObject in DB if not present in cache
        $relationDataObject = $relationClass::get()->find("Uuid", $dataObject->$relationUUIDField);
        if (!$relationDataObject) {
            $dataObject->$relationIDField = 0;
            return;
        }

        $this->addToRelationCache($relationDataObject);
        $relationalDataObjectID = $relationDataObject->ID;
        $dataObject->$relationIDField = $relationalDataObjectID;
    }

    /**
     * Adds a DataObject to the relation cache.
     * @param DataObject $relationDataObject
     * @return void
     */
    private function addToRelationCache(DataObject $relationDataObject) {
        $this->relationCache[get_class($relationDataObject)][$relationDataObject->Uuid] = $relationDataObject->ID;
    }

    /**
     * Parses the export json to a data transfer object and ensures the integrity of the export json structure
     * @param string $exportJson
     * @return Export
     * @throws ReflectionException
     */
    private function ensureExportFileIntegrity(string $exportJson): Export {
        $export = Export::fromJson($exportJson);

        if ($export === null) {
            throw new Exception("The provided export file is corrupt");
        }

        $formatter = new JSONExportDataFormatter();
        $exportableDataObjectClasses = $formatter->getAllExportableDataObjectClasses();

        $totalDataObjectCount = 0;
        /** @var ExportedDataObject $exportData */
        foreach ($export->getData() as $exportData) {
            $dataObjectMetadata = $exportData->getMetadata();

            if (!in_array($dataObjectMetadata->getClass(), $exportableDataObjectClasses)) {
                throw new Exception("The following class '{$dataObjectMetadata->getClass()}' does not exist or does not use the Exportable trait");
            }

            $totalDataObjectCount += $dataObjectMetadata->getCount();
        }

        if ($totalDataObjectCount != $export->getMetadata()->getTotalCount()) {
            throw new Exception("The provided export file is corrupt");
        }

        return $export;
    }

    /**
     * Truncates Database table by table name
     * @param string $tableName
     * @return void
     */
    private function truncateTable(string $tableName) {
        Logger::debugLog("Truncating table: " . $tableName);
        DB::query("
            IF EXISTS (
                SELECT 1 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_NAME = '$tableName'
            )
            THEN
                TRUNCATE TABLE \"$tableName\";
            END IF;
        ");
    }

    /**
     * @param EnvironmentImportRequest $environmentImportRequest
     * @return void
     * @throws ValidationException
     */
    private function writeEnvironmentImportRequest(EnvironmentImportRequest $environmentImportRequest) {
        $environmentImportRequest->Status = "COMPLETED";
        $environmentImportRequest->write();
    }

    private function claimQueuedEnvironmentImportRequest(): ?EnvironmentImportRequest {
        $dbConnection = DB::get_conn();
        try {
            $dbConnection->transactionStart();
            $environmentExportRequestBeingProcessed = EnvironmentImportRequest::get()->filter(["Status" => "PROCESSING"])->first();
            if ($environmentExportRequestBeingProcessed) {
                Logger::infoLog("An EnvironmentExportRequest is still being processed, aborting");
                return null;
            }

            DB::prepared_query("
                UPDATE `SurfSharekit_EnvironmentImportRequest`
                SET ProcessID = CASE
                    WHEN ProcessID IS NULL THEN ?
                    ELSE ProcessID END,
                    Status = 'PROCESSING',
                    Queued = 0
                WHERE Queued = 1
            ", [$this->processID]);
            $dbConnection->transactionEnd();

            return EnvironmentImportRequest::get()->find("ProcessID", $this->processID);
        } catch(Throwable $e) {
            Logger::infoLog("An error occurred while trying to claim an EnvironmentImportRequest: {$e->getMessage()}");
            $dbConnection->transactionRollback();
            return null;
        }
    }
}