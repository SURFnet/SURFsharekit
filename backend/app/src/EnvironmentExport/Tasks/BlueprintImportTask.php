<?php

namespace SilverStripe\EnvironmentExport\Tasks;

use Exception;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\TestOnly;
use SilverStripe\EnvironmentExport\DataObjects\BlueprintImportRequest;
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
use SilverStripe\models\blueprints\Blueprint;
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

class BlueprintImportTask extends EnvironmentImportTask {
    public function run($request) {
        ini_set('max_execution_time', '-1');
        ini_set('memory_limit', '300M');

        if(Environment::getEnv('APPLICATION_ENVIRONMENT') == 'live') {
            throw new Exception("Importing an environment export is not allowed on the production environment");
        }

        /** @var null|BlueprintImportRequest $environmentExportRequest */
        $environmentImportRequest = $this->claimQueuedBlueprintImportRequest();
        if (!$environmentImportRequest) {
            return;
        }

        /** @var EnvironmentExportFile $jsonFile */
        $exportJson = $this->getExportFileContents($environmentImportRequest);

        $databaseConnection = DB::get_conn();
        try {
            $databaseConnection->transactionStart();
            $this->doBlueprintImport($exportJson, $environmentImportRequest);
            $databaseConnection->transactionEnd();
        } catch (Throwable $e) {
            $databaseConnection->transactionRollback();
            Logger::warnLog("An error occurred during the environment import: {$e->getMessage()}");
        }
    }

    public function doBlueprintImport(string $exportJson, ?BlueprintImportRequest $environmentImportRequest) {
        $export = $this->ensureExportFileIntegrity($exportJson);

        // Truncate tables and fill them with data from the export file
        $this->importData($export);

        // Reconstruct all relations
        $this->reconstructRelations($export);

        // Set BlueprintImportRequest status
        if ($environmentImportRequest) {
            $this->writeBlueprintImportRequest($environmentImportRequest);
        }
    }

    /**
     * Gets the JSON string from the .json file linked to the BlueprintImportRequest
     * @param BlueprintImportRequest $environmentImportRequest
     * @return string
     */
    private function getExportFileContents(BlueprintImportRequest $environmentImportRequest): string {
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
        $dataObjects = ClassInfo::subclassesFor(Blueprint::class, false);
        $exportableTables = [];
        /** @var DataObject $dataObject */
        foreach ($dataObjects as $dataObject) {
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
        $exportableDataObjectClasses = array_values(ClassInfo::subclassesFor(Blueprint::class, false));;

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
     * @param BlueprintImportRequest $environmentImportRequest
     * @return void
     * @throws ValidationException
     */
    private function writeBlueprintImportRequest(BlueprintImportRequest $environmentImportRequest) {
        $environmentImportRequest->Status = "COMPLETED";
        $environmentImportRequest->write();
    }

    private function claimQueuedBlueprintImportRequest(): ?BlueprintImportRequest {
        $dbConnection = DB::get_conn();
        try {
            $dbConnection->transactionStart();
            $environmentExportRequestBeingProcessed = BlueprintImportRequest::get()->filter(["Status" => "PROCESSING"])->first();
            if ($environmentExportRequestBeingProcessed) {
                Logger::infoLog("An EnvironmentExportRequest is still being processed, aborting");
                return null;
            }

            DB::prepared_query("
                UPDATE `SurfSharekit_BlueprintImportRequest`
                SET ProcessID = CASE
                    WHEN ProcessID IS NULL THEN ?
                    ELSE ProcessID END,
                    Status = 'PROCESSING',
                    Queued = 0
                WHERE Queued = 1
            ", [$this->processID]);
            $dbConnection->transactionEnd();

            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return BlueprintImportRequest::get()->find("ProcessID", $this->processID);
        } catch(Throwable $e) {
            Logger::infoLog("An error occurred while trying to claim an BlueprintImportRequest: {$e->getMessage()}");
            $dbConnection->transactionRollback();
            return null;
        }
    }
}