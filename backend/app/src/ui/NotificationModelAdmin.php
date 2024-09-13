<?php

namespace Zooma\SilverStripe\ModelAdmin;

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
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class NotificationModelAdmin extends ModelAdmin {
    private static $url_segment = "notifications";
    private static $menu_title = 'Notifications';
    private static $menu_priority = 300;
    private static $page_length = 200;

    private static $managed_models = [
        NotificationVersion::class,
        NotificationCategory::class,
        NotificationType::class,
        Notification::class,
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

        if ($this->modelClass == NotificationCategory::class) {
            $gridFieldConfig->addComponents(new GridFieldOrderableRows("SortOrder"));
        }


        return $form;
    }
}