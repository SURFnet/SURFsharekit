<?php

namespace SurfSharekit\ui;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SurfSharekit\models\notifications\Notification;
use SurfSharekit\models\notifications\NotificationCategory;
use SurfSharekit\models\notifications\NotificationType;
use SurfSharekit\models\notifications\NotificationVersion;
use SurfSharekit\models\PermissionCategory;
use SurfSharekit\models\PermissionDescription;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class PermissionDescriptionModelAdmin extends ModelAdmin {
    private static $url_segment = "permission-descriptions";
    private static $menu_title = 'Permission Descriptions';
    private static $menu_priority = 300;
    private static $page_length = 200;

    private static $managed_models = [
        PermissionCategory::class,
        PermissionDescription::class,
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

        if ($this->modelClass == PermissionCategory::class) {
            $gridFieldConfig->addComponents(new GridFieldOrderableRows("SortOrder"));
        }


        return $form;
    }
}