<?php

namespace SurfSharekit\models\webhooks;

use PermissionProviderTrait;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\PermissionProvider;
use SurfSharekit\constants\WebhookTypeConstant;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\Channel;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\models\webhooks\exceptions\InvalidPayloadException;
use SurfSharekit\models\webhooks\exceptions\InvalidTypeException;
use SurfSharekit\models\webhooks\exceptions\ThumbprintException;
use UuidExtension;
use UuidRelationExtension;

/**
 * @package SurfSharekit\Models
 * @property string Uuid
 * @property string ProcessorID
 * @property bool Thumbprint
 * @property string Type
 * @property string Data
 * @property string Url
 * @property Int RepoItemID
 * @property string RepoItemUuid
 * @method RepoItem RepoItem
 */
class Webhook extends DataObject implements PermissionProvider {
    use PermissionProviderTrait;

    private static $singular_name = 'Webhook';
    private static $plural_name = 'Webhooks';
    private static $table_name = 'SurfSharekit_Webhook';

    private static $extensions = [
        UuidExtension::class,
        UuidRelationExtension::class
    ];

    private static $db = [
        "Type" => "Enum('Create, Update, Delete', null)",
        "Data" => "Text",
        "Url" => "Text",
        "ProcessorID" => "Varchar(255)"
    ];

    private static $indexes = [
        'ProcessorIDIndex' => ['ProcessorID']
    ];

    private static $has_one = [
        "RepoItem" => RepoItem::class
    ];

    private static $required_fields = [
        "Thumbprint",
        "Type",
    ];

    function getCMSValidator(): RequiredFields {
        return new RequiredFields($this::$required_fields);
    }

    protected function onBeforeWrite() {
        parent::onBeforeWrite();

        if (!$this->isInDB()) {

            if (!$this->Data) {
                $this->Data = $this->getPayload();
            }
        }
    }

    /**
     * @return string
     * @throws InvalidPayloadException
     * @throws InvalidTypeException
     */
    function getPayload(): string {
        switch ($this->Type) {
            case WebhookTypeConstant::CREATE:
            case WebhookTypeConstant::UPDATE:
            case WebhookTypeConstant::DELETE: {
                return $this->createPayload();
            }
            default: throw new InvalidTypeException("$this->Type is not a valid webhook type");
        }
    }

    /**
     * @return string|null
     * @throws InvalidPayloadException
     */
    function createPayload(): ?string {
        $payload = [
            "id" => $this->Uuid,
            "type" => $this->Type,
            "createdAt" => $this->Created,
            "data" => [
                "id" => $this->RepoItemUuid,
                "type" => "RepoItem",
            ]
        ];

        $encodedPayload = json_encode($payload);
        if (!$encodedPayload) {
            throw new InvalidPayloadException("An invalid payload was provided for webhook $this->Uuid");
        }

        return $encodedPayload;
    }
}