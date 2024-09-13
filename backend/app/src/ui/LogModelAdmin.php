<?php

namespace Zooma\SilverStripe\ModelAdmin;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SurfSharekit\Models\LogItem;

class LogModelAdmin extends ModelAdmin
{
    private static $url_segment = "logs";
    private static $menu_title = 'Logs';
    private static $menu_priority = 30;
    private static $page_length = 200;

    private static $managed_models = [
       LogItem::class
    ];

    private static $tabs = [
        'General logs' => LogItem::class,
        'Authentication logs' => LogItem::class
    ];

    public function getEditForm($id = null, $fields = null) {
        /** @var Form $form */
        $form = parent::getEditForm($id, $fields);
        /** @var GridField $gridField */
        $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass));
        $gridFieldConfig = $gridField->getConfig();

        $gridFieldConfig->removeComponentsByType(array(
            new GridFieldExportButton(),
            new GridFieldPrintButton(),
            new GridFieldImportButton(),
            new GridFieldDeleteAction()
        ));

        return $form;
    }
}