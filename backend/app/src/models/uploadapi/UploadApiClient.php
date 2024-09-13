<?php

namespace SurfSharekit\Models;

use Ramsey\Uuid\Uuid;
use SilverStripe\Authentication\Client;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\models\UploadApiUser;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Versioned\GridFieldArchiveAction;
use SurfSharekit\Extensions\Fields\ClientSecretField;
use SurfSharekit\models\notifications\Notification;
use SurfSharekit\models\notifications\NotificationType;
use SurfSharekit\models\notifications\NotificationVersion;

/**
 * Class UploadApiClient
 * @package SurfSharekit\Models
 * @property string ClientID
 * @property string ClientSecret
 * @property string Title
 * @property string IssuedToName
 * @property string IssuedToEmail
 * @property string ExpirationDate
 * @property bool IsDisabled
 * @property int UploadApiUserID
 * @method UploadApiUser UploadApiUser
 * @method HasManyList<UploadApiClientConfig> UploadApiClientConfigs
 */
class UploadApiClient extends DataObject implements Client {

    private static $table_name = 'SurfSharekit_UploadApiClient';
    private static $db = [
        'ClientID' => 'Varchar(255)',
        'ClientSecret' => 'Varchar(255)',
        'Title' => 'Varchar(255)',
        'IssuedToName' => 'Varchar(255)',
        'IssuedToEmail' => 'Varchar(255)',
        'ExpirationDate' => 'Datetime',
        'IsDisabled' => 'Boolean(0)'
    ];

    private static $has_one = [
        "UploadApiUser" => UploadApiUser::class
    ];

    private static $has_many = [
        'UploadApiClientConfigs' => UploadApiClientConfig::class
    ];
    private static $extensions = [];
    private static $defaults = [];
    private static $casting = [];

    private static $summary_fields = [
        'Title' => 'Title',
        'ExpirationDate' => 'Expiration Date',
        'getTotalInstitutes' => 'Total institutes',
    ];

    private static $required_fields = [
        'Title',
        'IssuedToName',
        'IssuedToEmail',
        "UploadApiUserID"
    ];

    function getCMSValidator(): RequiredFields {
        return new RequiredFields($this::$required_fields);
    }

    protected function onBeforeWrite() {
        parent::onBeforeWrite();

        if (!$this->isInDB()) {
            $this->ClientSecret = password_hash($this->ClientSecret, PASSWORD_DEFAULT);
        }

        if ($this->isChanged("UploadApiUserID")) {

            $changedFields = $this->getChangedFields();
            $oldID = $changedFields['UploadApiUserID']['before'];
            $newID = $this->UploadApiUserID;

            /** @var UploadApiUser $oldApiUser */
            $oldApiUser = UploadApiUser::get()->find("ID", $oldID);
            if ($oldApiUser) {
                $oldApiUser->removeUploadApiGroupsByUploadApiClient($this->ID);
            }

            /** @var UploadApiUser $newApiUser */
            $newApiUser = UploadApiUser::get()->find("ID", $newID);
            if ($newApiUser) {
                $newApiUser->addUploadApiGroupsByUploadApiClient($this->ID);
            }
        }
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->dataFieldByName('ClientID')->setReadonly(true);

        if ($this->isInDB()) {
            $fields->removeByName("ClientSecret");

            /** @var GridField $clientConfigGridField */
            $clientConfigGridField = $fields->dataFieldByName("UploadApiClientConfigs");
            $clientConfigGridField->getConfig()->removeComponentsByType([
                    GridFieldAddExistingAutocompleter::class,
                    GridFieldArchiveAction::class,
                    GridFieldDeleteAction::class
                ]
            );

        } else {
            $clientId = self::getPossibleClientID();
            $fields->replaceField('ClientID', new HiddenField('ClientID', 'Client ID', $clientId));
            $fields->addFieldToTab('Root.Main', new ReadonlyField('ClientIDReadOnly', 'Client ID', $clientId));

            $clientSecretField = new ClientSecretField('ClientSecret', 'Client secret', hash("sha256", Uuid::uuid4()->toString()));
            $clientSecretField->setDescription("Copy the secret before creating, you can not retrieve this afterwards!");

            $fields->replaceField("ClientSecret", $clientSecretField);
        }

        return $fields;
    }

    private static function getPossibleClientID(): string {
        $clientId = hash('ripemd128', str_replace('-', '', Uuid::uuid4()->toString()));

        if (null === self::get()->find('ClientID', $clientId)) {
            return $clientId;
        }

        return self::getPossibleClientID();
    }

    public function getAllInstitutes(): DataList {
        return Institute::get()
            ->innerJoin("SurfSharekit_UploadApiClientConfig", "uacc.InstituteID = SurfSharekit_Institute.ID", "uacc")
            ->innerJoin("SurfSharekit_UploadApiClient", "uac.UploadApiClientConfigID = uacc.ID", "uac")
            ->where(["uac.ID" => $this->ID]);
    }

    public function getTotalInstitutes() {
        return count($this->UploadApiClientConfigs());
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