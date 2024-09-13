<?php

namespace SurfSharekit\Api;

class RepoItemUploadApiDescription extends \DataObjectJsonApiDescription {

    public function __construct() {
        $this->fieldToAttributeMap = [
            "Uuid" => "id",
            "FirstName" => 'firstName',
            "Surname" => 'surname',
            "SurnamePrefix" => "surnamePrefix",
            "Email" => 'email',
            "RootInstitutesSummary" => "institutes",
            'Position' => 'position',
            "PersistentIdentifier" => 'dai', //used for DAI
            "ORCID" => 'orcid',
            "ISNI" => 'isni',
            "HogeschoolID" => 'hogeschoolId',
        ];
    }

    public function getFilterableAttributesToColumnMap(): array {
        return [
            'email' => '`Member`.`Email`',
            'name' => "REPLACE(CONCAT(`Member`.`FirstName`,' ',COALESCE(`Member`.`SurnamePrefix`,''),' ', `Member`.`Surname`),'  ',' ')",
            'institute' => 'SurfSharekit_Institute.Uuid',
            'orcid' => 'ORCID',
            'dai' => 'PersistentIdentifier',
            'isni' => 'ISNI',
            'lastname' => 'Surname',
            'hogeschoolId' => 'HogeschoolID'
            //TODO: add sram ID filter after implementation
        ];
    }
}