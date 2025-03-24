<?php

namespace SilverStripe\api\internal\descriptions;

class ExportItemJsonApiDescription extends \DataObjectJsonApiDescription {
    public $type_singular = 'exportItem';
    public $type_plural = 'exportItems';

    public $fieldToAttributeMap = [
        'Status' => 'status',
        'Created' => 'created',
        'FinishedAt' => 'finishedAt',
        'Institutes' => 'institutes',
        'From' => 'from',
        'Until' => 'until',
        'RepoType' => 'repoType',
        'ReportType' => 'reportType',
        'FileURL' => 'url'
    ];

    protected function getSortableAttributesToColumnMap(): array {
        return ['created' => 'Created'];
    }
}