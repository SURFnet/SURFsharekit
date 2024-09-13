<?php

namespace SilverStripe\ui;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\models\UploadApiUser;
use SurfSharekit\Models\UploadApiClient;

class UploadApiClientModelAdmin extends ModelAdmin
{

    private static $url_segment = 'upload-api-clients';
    private static $menu_title = 'Upload API clients';

    private static $menu_priority = 60;

    private static $managed_models = [
        UploadApiClient::class,
        UploadApiUser::class
    ];

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        /** @var GridField $gridField */
        $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass));
        $gridField->getConfig()->removeComponentsByType([
            GridFieldExportButton::class,
            GridFieldPrintButton::class,
            GridFieldImportButton::class
        ]);

        return $form;
    }
}