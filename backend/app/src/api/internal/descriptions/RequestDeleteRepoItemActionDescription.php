<?php

class RequestDeleteRepoItemActionDescription extends DataObjectJsonApiDescription
{
    public $type_plural = "requestdeleterepoitem";
    public $type_singular = "requestdeleterepoitem";

    public $attributeToFieldMap = [
        "repoItemId" => "RepoItemID"
    ];
}