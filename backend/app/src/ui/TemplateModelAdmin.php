<?php

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SurfSharekit\Models\Template;
use SurfSharekit\Models\TemplateSection;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class TemplateModelAdmin extends ModelAdmin {
    private static $url_segment = "template";
    private static $menu_title = 'Templates';
    private static $menu_priority = 280;
    private static $page_length = 200;

    private static $managed_models = [
        Template::class, TemplateSection::class
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

        if($this->modelClass == TemplateSection::class) {
            $gridFieldConfig->addComponents([new GridFieldOrderableRows('SortOrder')]);
        }

        return $form;
    }


    public function getList() {
//        $params = $this->getRequest()->postVar('filter');
//
//        $allowCustomization =( isset($params['SurfSharekit-Models-Template'])&&isset($params['SurfSharekit-Models-Template']['AllowCustomization'])) ? $params['SurfSharekit-Models-Template']['AllowCustomization'] : 1;
//        // only show templates which are customized
//        return parent::getList()->filterAny(['AllowCustomization'=>$allowCustomization, 'InstituteID'=> 0]);
        return parent::getList();
    }
}