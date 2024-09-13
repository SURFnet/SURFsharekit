<?php

class BulkActionJsonApiDescription extends DataObjectJsonApiDescription {
    public $type_singular = 'bulkaction';
    public $type_plural = 'bulkactions';

    public $fieldToAttributeMap = [
        'Created' => 'created',
        'LastEdited' => 'lastEdited',
        'FilterJSON' => 'filters',
        'Action' => 'action',
        'TotalCount' => 'totalCount',
        'SuccessCount' => 'successCount',
        'FailCount' => 'failCount',
        'ProcessStatus' => 'processStatus'
    ];

    public $attributeToFieldMap = [
        'filters' => 'FilterJSON',
        'action' => 'Action'
    ];

}