<?php

namespace SurfSharekit\Models;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\models\UploadApiUser;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Group;
use SurfSharekit\constants\RoleConstant;

/**
 * Class UploadApiClientConfig
 * @package SurfSharekit\Models
 * @property string RepoTypeWhitelist
 * @property int UploadApiClientID
 * @property int InstituteID
 * @method UploadApiClient UploadApiClient
 * @method Institute Institute
 */
class UploadApiClientConfig extends DataObject {

    private static $table_name = 'SurfSharekit_UploadApiClientConfig';
    private static $db = [
        'RepoTypeWhitelist' => 'Text'
    ];

    private static $has_one = [
        'UploadApiClient' => UploadApiClient::class,
        'Institute' => Institute::class
    ];

    private static $summary_fields = [
        'getDisplayInstituteTitle' => 'Institute'
    ];

    private static $required_fields = [
        'RepoTypeWhitelist',
        'InstituteID'
    ];

    function getCMSValidator(): RequiredFields {
        return new RequiredFields($this::$required_fields);
    }

    public function setStringList(array $list) {
        $this->setField('RepoTypeWhitelist', json_encode($list));
    }

    public function getStringList() {
        return json_decode($this->getField('RepoTypeWhitelist'), true);
    }

    protected function onAfterWrite() {
        parent::onAfterWrite();

        if ($this->isChanged("ID")) {
            $this->addUploadApiUserToGroup();
        }
    }

    protected function onAfterDelete() {
        parent::onAfterDelete();

        $this->removeUploadApiUserFromGroup();
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        // Remove the has_one field
        $fields->removeByName('UploadApiClientID');

        $options = [
            "PublicationRecord" => "PublicationRecord",
            "LearningObject" => "LearningObject",
            "ResearchObject" => "ResearchObject",
            "Dataset" => "Dataset",
            "Project" => "Project"
        ];

        $fields->addFieldToTab('Root.Main', new ListboxField(
            'RepoTypeWhitelist',
            'Whitelisted Repo Types',
            $options
        ));

        if ($this->isInDB()){
            $fields->replaceField('InstituteID', ReadonlyField::create('InstituteTitle', 'Institute', $this->Institute()->Title));

            $fields->insertBefore('InstituteTitle', new ReadonlyField(
                'InstituteUuid',
                'Institute identifier',
                $this->Institute()->Uuid
            ));
        } else {
            $uploadApiClient = UploadApiClient::get()->byID($this->UploadApiClientID);

            if ($uploadApiClient) {
                $availableInstitutes = self::getAvailableInstitutes($uploadApiClient);

                    $parentInstituteField = DropdownField::create('InstituteID', 'Institute', $availableInstitutes->map())
                    ->setEmptyString('Select an institute')
                    ->setHasEmptyDefault(true);

                $fields->insertBefore('Title', $parentInstituteField);
            }
        }

        return $fields;
    }

    public function getDisplayInstituteTitle(){
        return $this->Institute()->Title;
    }

    public static function getAvailableInstitutes($uploadApiClient)
    {
        $connectedInstituteIDs = UploadApiClientConfig::get()
            ->filter('UploadApiClientID', $uploadApiClient->ID)
            ->column('InstituteID');

        // Check if UploadApiClient has already connected an institute
        if (count($connectedInstituteIDs) === 0) {
            $institutes = Institute::get()->filter('InstituteID', 0);
        } else {
            $institutes = Institute::get()
                ->filter('InstituteID', 0)
                ->exclude('ID', $connectedInstituteIDs);
        }

        return $institutes;
    }

    public function addUploadApiUserToGroup() {
        /** @var Group $group */
        $group = $this->Institute()->Groups()->filter([
            "DefaultRole.Key" => RoleConstant::UPLOAD_API_USER
        ])->first();

        if (!$group) {
            return;
        }

        $uploadApiUser = $this->UploadApiClient()->UploadApiUser();
        if ($uploadApiUser && $uploadApiUser->exists()) {
            $group->Members()->add($uploadApiUser);
        }
    }

    public function removeUploadApiUserFromGroup() {
        /** @var Group $group */
        $group = $this->Institute()->Groups()->filter([
            "DefaultRole.Key" => RoleConstant::UPLOAD_API_USER
        ])->first();

        if (!$group) {
            return;
        }

        $uploadApiUser = $this->UploadApiClient()->UploadApiUser();
        if ($uploadApiUser && $uploadApiUser->exists()) {
            $group->Members()->remove($uploadApiUser);
        }
    }

    public function canCreate($member = null, $context = []) {
        return true;
    }

    public function canEdit($member = null) {
        return true;
    }

    public function canView($member = null) {
        return true;
    }

    public function canDelete($member = null) {
        return true;
    }
}