<?php

namespace SilverStripe\EnvironmentExport\DataObjects;

use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SurfSharekit\Models\Helper\Logger;

/**
 * Class EnvironmentExportRequest
 * @package SurfSharekit\Models
 * @property bool Queued
 * @property string Status
 * @property string ProcessID
 * @property int EnvironmentExportFileID
 * @method EnvironmentExportFile EnvironmentExportFile
 */
class EnvironmentExportRequest extends DataObject {

    private static $table_name = "SurfSharekit_EnvironmentExportRequest";
    private static $singular_name = "Environment export";
    private static $plural_name = "Environment exports";
    private static $default_sort = "Created DESC";

    private static $db = [
        "Queued" => "Boolean(0)",
        "Status" => 'Enum(array("PENDING", "QUEUED", "PROCESSING", "COMPLETED", "FAILED"), "PENDING")',
        "ProcessID" => "Varchar(36)"
    ];

    private static $has_one = [
        "EnvironmentExportFile" => EnvironmentExportFile::class
    ];

    private static $owns = [
        "EnvironmentExportFile"
    ];

    private static $summary_fields = [
        "Created", "Status"
    ];

    protected function onBeforeWrite() {
        parent::onBeforeWrite();
        if ($this->Queued === true && $this->getChangedFields()["Queued"]) {
            $this->Status = "QUEUED";
        }
    }

    public function validate() {
        $res = parent::validate();
        if ($this->Queued && static::get()->filter(['ID:not' => $this->ID, 'Queued' => true])->count() > 0) {
            $res->addError("Only one request can be queued at any time");
        }
        return $res;
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        // Always remove upload field
        $fields->removeFieldsFromTab("Root.Main", [
            "EnvironmentExportFile",
        ]);

        $fields->replaceField('Status', TextField::create('Status')->setReadonly(true));
        $fields->removeByName('ProcessID');

        if (!$this->isInDB() || $this->Queued || $this->Status !== "PENDING") {
            $fields->removeFieldsFromTab("Root.Main", [
                "Queued",
            ]);
        }

        if (!$this->isInDB()) {
            $this->Status = 'PENDING';
        }

        if ($this->Status === "COMPLETED") {
            $downloadLink = $this->EnvironmentExportFile()->getStreamURL();
            Logger::debugLog($downloadLink);
            $linkFieldClasses = 'btn btn-secondary no-ajax font-icon-down-circled action_export';
            $linkFieldTitle = '<span class="btn__title">Download</span>';
            $linkField = LiteralField::create("DownloadLink", "<a class='$linkFieldClasses' href='$downloadLink'>$linkFieldTitle</a>");
            $fields->addFieldToTab("Root.Main", $linkField);
        }

        return $fields;
    }

    public function getTitle() {
        return $this->Created;
    }
}