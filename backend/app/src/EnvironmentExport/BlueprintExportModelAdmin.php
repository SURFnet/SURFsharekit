<?php

namespace SilverStripe\EnvironmentExport;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\EnvironmentExport\DataObjects\BlueprintExportRequest;
use SilverStripe\EnvironmentExport\DataObjects\BlueprintImportRequest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;

class BlueprintExportModelAdmin extends ModelAdmin {
    private static $url_segment = "blueprint-export";
    private static $menu_title = 'Blueprint export';
    private static $menu_priority = 300;
    private static $page_length = 200;
    private static $managed_models = [
        BlueprintExportRequest::class,
        BlueprintImportRequest::class
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