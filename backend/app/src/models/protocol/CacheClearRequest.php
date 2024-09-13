<?php

namespace SurfSharekit\Models;

use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SurfSharekit\Extensions\HasOneAutocompleteField;

class CacheClearRequest extends DataObject implements PermissionProvider {
    use \PermissionProviderTrait;

    private static $table_name = 'SurfSharekit_CacheClearRequest';

    private static $db = [
        "TaskID" => "Varchar(255)",
        "Status" => "Enum('Created, Queued, Started, Done', 'Created')",
        "Queue" => "Boolean(1)",
        "Progress" => "Int"
    ];

    private static $has_one = [
        "Protocol" => Protocol::class,
        "Channel" => Channel::class,
        "Institute" => Institute::class
    ];

    private static $field_labels = [
        'Progress' => 'Progress (%)'
    ];

    private static $default_sort = 'Created DESC';

    private static $summary_fields = [
        "Created",
        "Status",
        "Type",
        "ProgressSummary" => "Progress"
    ];

    public function validate() {
        $result = parent::validate();

        $savedRelations = [];
        foreach ($this->hasOne() as $relation => $class) {
            if (!empty($this->{$relation . "ID"})) {
                $savedRelations[] = $relation;
            }
        }

        if (count($savedRelations) === 0) {
            $result->addError("Please choose a protocol, channel or institute");
        }
        if (count($savedRelations) > 1) {
            $result->addError("Please choose one protocol, channel or institute");
        }

        return $result;
    }

    protected function onBeforeWrite() {
        parent::onBeforeWrite();

        if ($this->isInDB()) {
            $changedFields = $this->getChangedFields();
            if (isset($changedFields['Queue']) && $this->Status === 'Created') {
                $this->Status = 'Queued';
            }
        } else {
            if ($this->Queue) {
                $this->Status = 'Queued';
            }
        }

    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->removeByName('TaskID');
        $fields->replaceField('Status', new ReadonlyField('Status', 'Status'));
        $fields->dataFieldByName('Progress')->setReadonly(true);

        if (!$this->isInDB()) {
            $fields->removeByName('Status');
            $fields->removeByName('Progress');

            $fields->replaceField('InstituteID', $instituteField = HasOneAutocompleteField::create('InstituteID', 'Institute', Institute::class, 'Title'));
            $instituteField
                ->setSearchFields(['Title']);
        } else {
            foreach ($this->hasOne() as $relation => $class) {
                if (!empty($this->{$relation . "ID"})) {
                    $fields->replaceField($relation . "ID", ReadonlyField::create('ChosenRelation', $relation, $this->{$relation}->Title));
                    continue;
                }
                $fields->removeByName($relation . "ID");
            }
        }

        if ($this->Queue) {
            $fields->removeByName('Queue');
        }

        $fields->changeFieldOrder([
            "Queue",
            "Status",
            "ProtocolID",
            "ChannelID",
            "InstituteID",
            "ChosenRelation"
        ]);

        return $fields;
    }

    public function markStatusAs(string $string) {
        $this->Status = $string;

        $this->write();
    }

    public function canEdit($member = null, $context = []) {
        if ($this->isInDB() && $this->Queue) {
            return false;
        }

        $name = strtoupper($this->dataObj()->ClassName);
        return Permission::check("${name}_EDIT");
    }

    public function getType() {
        if ($this->ProtocolID) {
            return "Protocol: " . $this->Protocol()->Title;
        }

        if ($this->ChannelID) {
            return "Channel: " . $this->Channel()->Title;
        }

        if ($this->InstituteID) {
            return "Institute: " . $this->Institute()->Title;
        }

        return "";
    }

    public function getProgressSummary() {
        if ($this->Queue) {
            if ($this->Status === 'Queued') {
                return "0%";
            }

            return $this->Progress . '%';
        }

        return "-";
    }

    public function updateProgress(int $percentage) {
        SQLUpdate::create('SurfSharekit_CacheClearRequest', ['"Progress"' => $percentage], ['"ID"' => $this->ID])->execute();
    }
}