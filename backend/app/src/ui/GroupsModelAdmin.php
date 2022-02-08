<?php

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Security\Group;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

class GroupsModelAdmin extends ModelAdmin {
    private static $url_segment = "groups";
    private static $menu_title = 'Groups';
    private static $menu_priority = 300;
    private static $page_length = 200;

    private static $managed_models = [
        Group::class
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

    public function getList() {
        $list = parent::getList();
        $member = Security::getCurrentUser();
        if (!Permission::checkMember($member, 'ADMIN')) {
            // only show groups with valid institutes
            $list = $list->addFilter(['Institute.ID:not'=>null]);
        }
        return $list;
    }

}