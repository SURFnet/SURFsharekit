<?php

namespace SurfSharekit\Models;

use Illuminate\Support\Arr;
use SilverStripe\Assets\File;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SurfSharekit\Api\AccessTokenApiController;

/**
 * @property string Status
 * @property string Args
 * @property null|string StartedAt
 * @property null|string FinishedAt
 * @property null|string FailedAt
 * @property string TaskID
 * @property Person Person
 * @property null|File File
 */
class ExportItem extends DataObject {
    private static $table_name = 'SurfSharekit_ExportItem';

    private static $db = [
        "Status" => "Enum('PENDING, IN PROGRESS, FINISHED, FAILED','PENDING')",
        "Args" => "Text", // JSON
        "StartedAt" => "Datetime",
        "FinishedAt" => "Datetime",
        "FailedAt" => "Datetime",
        "TaskID" => "Varchar(36)",
        "FailReason" => "Text"
    ];

    private static $has_one = [
        'Person' => Person::class,
        'File' => File::class
    ];

    public function getFileURL() {
        if (!$this->FileID) return null;

        $file = Versioned::get_by_stage(File::class, Versioned::DRAFT)->where([
            'ID' => $this->FileID
        ])->first();

        if ($file) {
            if (($member = $this->Person) && $member->ID === Security::getCurrentUser()->ID) {
                $accessToken = AccessTokenApiController::generateAccessTokenForMember($member);
            }

            return Environment::getEnv('SS_BASE_URL') . '/api/v1/files/exportItemFiles/' . $file->Uuid . '?accessToken=' . $accessToken;
        }

        return null;
    }

    public function getInstitutes() {
        $scopes = $this->getArgument("filter.scope");

        $instituteUuids = explode(',', $scopes);

        $institutes = [];

        foreach ($instituteUuids as $instituteUuid) {
            if ($institute = Institute::get()->find('Uuid', $instituteUuid)) {
                $institutes[] = (new \InstituteJsonApiDescription())->describeAttributesOfDataObject($institute);
            }
        }

        return $institutes;
    }
    public function getFrom() {
        return $this->getArgument("filter.downloadDate.GE") ?? $this->getArgument("filter.publicationDate.GE");
    }
    public function getUntil() {
        return $this->getArgument("filter.downloadDate.LE") ?? $this->getArgument("filter.publicationDate.LE");
    }
    public function getRepoType() {
        return $this->getArgument("filter.repoType");
    }
    public function getReportType() {
        return $this->getArgument("reportType");
    }

    private function getArgument($key) {
        $args = json_decode($this->Args, true);

        return Arr::get($args, $key);
    }

    public function canDelete($member = null, $context = []) {
        if ($member = Security::getCurrentUser()) {
            return $this->PersonID === $member->ID;
        }

        return false;
    }

    public function canView($member = null, $context = []) {
        if ($member = Security::getCurrentUser()) {
            return $this->PersonID === $member->ID;
        }

        return false;
    }
}