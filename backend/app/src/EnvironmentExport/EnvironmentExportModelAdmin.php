<?php

namespace SilverStripe\EnvironmentExport;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\EnvironmentExport\DataObjects\EnvironmentExportRequest;
use SilverStripe\EnvironmentExport\DataObjects\EnvironmentImportRequest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;

class EnvironmentExportModelAdmin extends ModelAdmin {
    private static $url_segment = "environment-export";
    private static $menu_title = 'Environment export';
    private static $menu_priority = 300;
    private static $page_length = 200;
    private static $managed_models = [
        EnvironmentExportRequest::class,
        EnvironmentImportRequest::class
    ];

    public function getEditForm($id = null, $fields = null) {
        $form = parent::getEditForm($id, $fields);
        /** @var GridField $gridField */
        $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass));
        $gridFieldConfig = $gridField->getConfig();

        $gridFieldConfig->removeComponentsByType([
            GridFieldExportButton::class,
            GridFieldPrintButton::class,
            GridFieldImportButton::class,
            GridFieldDeleteAction::class
        ]);

        return $form;
    }

}