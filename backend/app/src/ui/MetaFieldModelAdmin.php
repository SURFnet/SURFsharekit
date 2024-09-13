<?php

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SurfSharekit\Models\GenerateMetafieldOptionsTaskExtension;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\MetaFieldJsonExample;
use SurfSharekit\Models\MetaFieldType;
use SurfSharekit\Tasks\GetMetafieldOptionsFromJsonTask;

class MetaFieldModelAdmin extends ModelAdmin {
    private static $url_segment = "metafield";
    private static $menu_title = 'Metafields';
    private static $page_length = 200;
    private static $menu_priority = 270;

    private static $allowed_actions = [
        'doCustomAction',
    ];

    private static $managed_models = [
        MetaField::class,
        MetaFieldType::class,
        MetaFieldJsonExample::class
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

    public function doCustomAction($data)
    {
        $task = GetMetafieldOptionsFromJsonTask::create();

        try {
            $task->singleton()->run($this->owner->getRequest());
        } catch (HTTPResponse_Exception $e) {
            return $this->owner->redirectBack();
        }

        return $this->owner->redirectBack();
    }
}