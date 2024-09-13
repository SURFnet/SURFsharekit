<?php

use SilverStripe\ORM\DataList;
use SilverStripe\Security\Group;
use SurfSharekit\Api\SearchApiController;
use SurfSharekit\Models\PersonImage;

class PersonMergeJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'personmerge';
    public $type_plural = 'personmerge';

    public $fieldToAttributeMap = [
        'SurnamePrefix' => 'surnamePrefix',
        'Surname' => 'surname',
        'FirstName' => 'firstName',
        'Email' => 'email',
        'IsRemoved' => 'isRemoved',
        'LinkedInUrl' => 'linkedInUrl',
        'TwitterUrl' => 'twitterUrl',
        'ResearchGateUrl' => 'researchGateUrl',
        'City' => 'city',
        'Phone' => 'phone',
        'FormOfAddress' => "title",
        'AcademicTitle' => "academicTitle",
        'Initials' => "initials",
        'SecondaryEmail' => "secondaryEmail",
        'PersistentIdentifier' => "persistentIdentifier",
        'ORCID' => "orcid",
        'ISNI' => "isni",
        'HogeschoolID' => "hogeschoolId",
        'Position' => "position"
    ];

    public $attributeToFieldMap = [
        'test' => 'Test',
        'mergePersonIds' => 'MergePersonIds',
        'surnamePrefix' => 'SurnamePrefix',
        'surname' => 'Surname',
        'firstName' => 'FirstName',
        'email' => 'Email',
        'isRemoved' => 'IsRemoved',
        'linkedInUrl' => 'LinkedInUrl',
        'twitterUrl' => 'TwitterUrl',
        'researchGateUrl' => 'ResearchGateUrl',
        'city' => 'City',
        'skipEmail' => 'SkipEmail',
        'disableEmailChange' => 'DisableEmailChange',
        'phone' => 'Phone',
        "title" => 'FormOfAddress',
        "academicTitle" => 'AcademicTitle',
        "initials" => 'Initials',
        "secondaryEmail" => 'SecondaryEmail',
        "persistentIdentifier" => 'PersistentIdentifier',
        "orcid" => 'ORCID',
        "isni" => 'ISNI',
        "hogeschoolId" => 'HogeschoolID',
        "position" => 'Position',
        "institute" => 'BaseInstitute',
        "discipline" => 'BaseDiscipline',
        "hasFinishedOnboarding" => 'HasFinishedOnboarding',
    ];
}