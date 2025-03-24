<?php

namespace SilverStripe\api\external\jsonapi\descriptions;

use DataObjectJsonApiDescription;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;
use SurfSharekit\Models\Protocol;

class ExternalInstituteJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'institute';
    public $type_plural = 'institutes';

    public $channel;
    public $protocol;

    public $fieldToAttributeMap = [
        'Title' => 'name',
        'ROR' => 'ror',
        'Level' => 'level',
        'Description' => 'description',
        'ConsortiumChildren' => 'consortiumChildren'
    ];

    public $attributeToFieldMap = [
        'name' => 'Title',
        'parentName' => 'Institute.Title',
        'parentId' => 'Institute.Uuid',
        'ror' => 'ROR',
        'level' => 'Level',
        'description' => 'Description',
        'consortiumChildren' => 'ConsortiumChildren'
    ];

    public function __construct($channel = null) {

        $describingProtocolFilter = ['SystemKey' => 'JSON:API'];
        if (!is_null($channel)) {
            $describingProtocolFilter['ID'] = $channel->ProtocolID;
        }
        $this->channel = $channel;
        $this->protocol = Protocol::get()->filter($describingProtocolFilter)->first();
    }

    public function getFilterableAttributesToColumnMap(): array {
        return [
            'inactive' => '`SurfSharekit_Institute`.`IsRemoved`',
            'isHidden' => '`SurfSharekit_Institute`.`IsHidden`',
            'name' => '`SurfSharekit_Institute`.`Title`',
            'level' => '`SurfSharekit_Institute`.`Level`',
            'type' => '`SurfSharekit_Institute`.`Type`',
            'id' => '`SurfSharekit_Institute`.`Uuid`',
            'ror' => '`SurfSharekit_Institute`.`ROR`',
            'parentId' => '`SurfSharekit_Institute`.`InstituteUuid`',
        ];
    }

    public function describeAttributesOfDataObject(ViewableData $dataObject) {
        $consortiumChildren = [];
        $secretaryID = $dataObject->getSecretaryID();
        foreach ($dataObject->ConsortiumChildren() as $institute) {
            $consortiumChildren[] = [
                "id" => $institute->Uuid,
                "name" => $institute->Title,
                "ror" => $institute->ROR,
                "level" => $institute->Level,
                "type" => $institute->Type,
                "description" => $institute->Description,
                "inactive" => $institute->IsRemoved,
                "secretary" => $secretaryID === $institute->ID ? 1 : 0
            ];
        }

        return [
            "name" => $dataObject->Title,
            "parentId" => $dataObject->Institute()->Uuid ?? null,
            "parentName" => $dataObject->Institute()->Title ?? null,
            "ror" => $dataObject->ROR,
            "level" => $dataObject->Level,
            "type" => $dataObject->Type,
            "description" => $dataObject->Description,
            "inactive" => $dataObject->IsRemoved,
            "consortiumChildren" => $consortiumChildren
        ];
    }
}