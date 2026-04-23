<?php

namespace SilverStripe\EnvironmentExport\DataObjects;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;

/**
 * Class EnvironmentExportRequest
 * @package SurfSharekit\Models
 * @property bool Queued
 * @property string Status
 * @property string ProcessID
 * @property int ImportFileID
 * @method EnvironmentExportFile ImportFile
 */
class EnvironmentImportRequest extends DataObject {

    private static $table_name = "SurfSharekit_EnvironmentImportRequest";

    private static $singular_name = "Environment import";
    private static $plural_name = "Environment imports";
    private static $default_sort = "Created DESC";

    private static $summary_fields = [
        "Created", "Status"
    ];

    private static $db = [
        "Queued" => "Boolean(0)",
        "Status" => 'Enum(array("PENDING", "QUEUED", "PROCESSING", "COMPLETED", "FAILED"), "PENDING")',
        "ProcessID" => "Varchar(36)"
    ];

    private static $has_one = [
        "ImportFile" => EnvironmentExportFile::class
    ];

    private static $owns = [
        "ImportFile"
    ];

    public function validate() {
        $res = parent::validate();
        if ($this->Queued && static::get()->filter(['ID:not' => $this->ID, 'Queued' => true])->count() > 0) {
            $res->addError("Only one request can be queued at any time");
        }
        return $res;
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        if (!$this->isInDB() || $this->Queued) {
            $fields->removeFieldsFromTab("Root.Main", [
                "Queued", "Status"
            ]);
        } else {
            $fields->replaceField('Status', TextField::create('Status')->setReadonly(true));
        }
        $fields->removeByName('ProcessID');

        return $fields;
    }

    public function getTitle() {
        return $this->Created;
    }
}