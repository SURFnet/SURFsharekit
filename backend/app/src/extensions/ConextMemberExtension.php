<?php

namespace SurfSharekit\Api;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\Helper\Constants;

/**
 * Class ConextMemberExtension
 * @package SurfSharekit\Api
 * Extension to Silverstripe Member DataObject to add a databasefield for the members ConextCode and SramCode
 */
class ConextMemberExtension extends DataExtension {

    private static $db = [
        'SramCode' => 'Varchar(255)',
        'ConextCode' => 'Varchar(255)',
        'ConextRoles' => 'Varchar(255)',
        'SurnamePrefix' => 'Varchar(255)',
        'IsRemoved' => 'Boolean(0)'
    ];

    private static $indexes = [
        'FulltextSearch' => [
            'type' => 'fulltext',
            'columns' => ['FirstName', 'SurnamePrefix', 'Surname']
        ],
        'FulltextSearchSmall' => [
            'type' => 'fulltext',
            'columns' => ['FirstName', 'Surname']
        ],
        'ConextCode' => true,
        'SramCode' => true,
        'IsRemoved' => true
    ];

    private static $searchable_fields = [
        'FirstName',
        'SurnamePrefix',
        'Surname',
        'Email',
        'Uuid' => [
            'title' => 'Identifier',
            'filter' => 'ExactMatchFilter'
        ]
    ];


    public function updateValidator($validator)
    {
        if(isset($_POST['SkipEmail']) && $_POST['SkipEmail']) {
            $validator->removeRequiredField('Email');
        }
        return $validator;
    }

    public function updateCMSFields(FieldList $fields) {
        parent::updateCMSFields($fields);
        $UuidField = TextField::create('Uuid', 'Identifier')->setReadonly(true);
        $fields->insertBefore('ApiToken', $UuidField);
        return $fields;
    }

    public function getFullName() {

        $nameValues = [];

        if (!empty($firstName = trim($this->owner->getField('FirstName')))) {
            $nameValues[] = $firstName;
        }
        if (!empty($surnamePrefix = trim($this->getField('SurnamePrefix')))) {
            $nameValues[] = $surnamePrefix;
        }
        if (!empty($surname = trim($this->owner->getField('Surname')))) {
            $nameValues[] = $surname;
        }

        $fullName = implode(' ', $nameValues);
        return $fullName;
    }

    public function getTitle(){
        return $this->getFullName();
    }

    public function canCreate($member = null, $context = []) {
        return true;
    }

    public function isWorksAdmin() {
        if (!isset($GLOBALS['MemberIsWorksAdmin'])){
            $GLOBALS['MemberIsWorksAdmin'] = $this->owner->Groups()->filter('Roles.Title', RoleConstant::WORKSADMIN)->exists();
        }
        return $GLOBALS['MemberIsWorksAdmin'];
    }
}