<?php

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\View\ArrayData;

abstract class SingletonModelAdmin extends ModelAdmin {
    /**
     * @return string[]
     */
    public static function getAllowedActions(): array {
        return [
            'EditForm',
            'doSave'
        ];
    }

    public function getEditForm($id = null, $fields = null) {
        $singletonInstance = $this->getModelClass()::get()->first();
        if (!$singletonInstance || !$singletonInstance->exists()){
            $singletonInstance = $this->getModelClass()::create();
            $singletonInstance->write();
        }
        // Build replacement form
        /**
         * @var $fields FieldList
         */
        $fields = $singletonInstance->getCMSFields();
        foreach ($singletonInstance->toMap() as $field => $value){
            if ($f = $fields->dataFieldByName($field)){
                $f->setValue($value);
            }
        }
        $form = Form::create(
            $this,
            'EditForm',
            $fields,
            $this->getFormActions()
        )->setHTMLID('Form_EditForm');

        $form->addExtraClass('cms-edit-form fill-height');
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $form->addExtraClass('ss-tabset cms-tabset ' . $this->BaseCSSClasses());
        $form->setAttribute('data-pjax-fragment', 'CurrentForm');
        $this->extend('updateEditForm', $form);

        return $form;
    }

    /**
     * Disable GridFieldDetailForm backlinks for this view, as its
     */
    public function Backlink() {
        return false;
    }

    public function Breadcrumbs($unlinked = false) {
        $crumbs = parent::Breadcrumbs($unlinked);

        // Name root breadcrumb based on which record is edited,
        // which can only be determined by looking for the fieldname of the GridField.
        // Note: Titles should be same titles as tabs in RootForm().
        $params = $this->getRequest()->allParams();
        if (isset($params['FieldName'])) {
            // TODO FieldName param gets overwritten by nested GridFields,
            // so shows "Members" rather than "Groups" for the following URL:
            // admin/security/EditForm/field/Groups/item/2/ItemEditForm/field/Members/item/1/edit
            $firstCrumb = $crumbs->shift();
            if ($params['FieldName'] == 'Users') {
                $crumbs->unshift(new ArrayData([
                    'Title' => _t(__CLASS__ . '.Users', 'Users'),
                    'Link' => $this->Link('users')
                ]));
            }
            $crumbs->unshift($firstCrumb);
        }

        return $crumbs;
    }

    protected function getFormActions() {
        $actions = new FieldList();
        $majorActions = CompositeField::create()->setName('MajorActions');
        $majorActions->setFieldHolderTemplate(get_class($majorActions) . '_holder_buttongroup');
        $actions->push($majorActions);

        $noChangesClasses = 'btn-primary font-icon-tick';
        $majorActions->push(FormAction::create('doSave', _t('Generic.GENERATE', 'Save'))
            ->addExtraClass($noChangesClasses)
            ->setAttribute('data-btn-alternate-add', 'btn-primary font-icon-settings')
            ->setUseButtonTag(true));
        return $actions;
    }

    public function doSave($record) {
        $object = $this->getModelClass()::get()->first();
        foreach ($record as $field => $value) {
            $object->$field = $value;
        }
        $result = $object->write();
    }

    public function providePermissions() {
        return [];
    }
}
