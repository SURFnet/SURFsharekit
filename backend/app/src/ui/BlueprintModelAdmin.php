<?php

namespace Zooma\SilverStripe\ModelAdmin;

use SilverStripe\actions\ConvertBlueprintGridFieldButton;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\models\blueprints\BPInstitute;
use SilverStripe\models\blueprints\BPPerson;
use SilverStripe\models\blueprints\BPRepoItem;
use SilverStripe\models\blueprints\BPRepoItemFile;
use SilverStripe\processors\Blueprint\BlueprintInstituteConverterProcessor;
use SilverStripe\processors\Blueprint\BlueprintPersonConverterProcessor;
use SilverStripe\processors\Blueprint\BlueprintRepoItemConverterProcessor;
use SilverStripe\processors\Blueprint\BlueprintRepoItemFileConverterProcessor;
use SilverStripe\registries\BlueprintConverterRegistry;

class BlueprintModelAdmin extends ModelAdmin
{
    private static $url_segment = "Blueprint";
    private static $menu_title = 'Blueprints';
    private static $page_length = 200;
    private static $menu_priority = 50;
    private static $managed_models = [
        BPInstitute::class,
        BPPerson::class,
        BPRepoItem::class,
        BPRepoItemFile::class
    ];

    public function init()
    {
        parent::init();

        // Register converters
        BlueprintConverterRegistry::register(BPInstitute::class, new BlueprintInstituteConverterProcessor());
        BlueprintConverterRegistry::register(BPPerson::class, new BlueprintPersonConverterProcessor());
        BlueprintConverterRegistry::register(BPRepoItem::class, new BlueprintRepoItemConverterProcessor());
        BlueprintConverterRegistry::register(BPRepoItemFile::class, new BlueprintRepoItemFileConverterProcessor());
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass));
        $gridFieldConfig = $gridField->getConfig();

        $gridFieldConfig->removeComponentsByType([
            GridFieldExportButton::class,
            GridFieldPrintButton::class,
            GridFieldImportButton::class,
            GridFieldDeleteAction::class
        ]);

        $gridFieldConfig->addComponent(new ConvertBlueprintGridFieldButton());

        return $form;
    }
}