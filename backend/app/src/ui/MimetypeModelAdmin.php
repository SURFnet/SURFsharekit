<?php

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SurfSharekit\Models\MimetypeObject;

class MimetypeModelAdmin extends ModelAdmin {

    private static $url_segment = "mimetype";
    private static $menu_title = 'Mimetype';
    private static $page_length = 200;
    private static $menu_priority = 270;

    private static $managed_models = [
        MimetypeObject::class
    ];

    public function getEditForm($id = null, $fields = null) {
        /** @var Form $form */
        $form = parent::getEditForm($id, $fields);

        /** @var GridField $gridField */
        $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass));
        $gridFieldConfig = $gridField->getConfig();

        $gridFieldConfig->removeComponentsByType([
            new GridFieldExportButton(),
            new GridFieldPrintButton(),
            new GridFieldImportButton(),
            new GridFieldDeleteAction()
        ]);

        if (null !== $columns = $gridFieldConfig->getComponentByType(GridFieldDataColumns::class)) {
            /** @var GridFieldDataColumns $columns */
            $columns->setDisplayFields([
                'Extension' => 'Extension',
                'MimeType' => 'Mime type',
                'Whitelist' => 'Whitelisted?'
            ]);

            // Set custom formatting for the 'Whitelist' field
            $columns->setFieldFormatting([
                'Whitelist' => function ($value, $item) {
                    return $value ? 'Yes' : 'No';
                }
            ]);
        }

        return $form;
    }
}