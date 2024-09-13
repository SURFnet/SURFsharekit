<?php

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SurfSharekit\extensions\Gridfield\Copy\CopyRecursiveRelation;
use SurfSharekit\extensions\Gridfield\Copy\CopyRelation;
use SurfSharekit\extensions\Gridfield\Copy\GridFieldCopyAction;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\CacheClearRequest;
use SurfSharekit\Models\Channel;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\ProtocolFilter;
use SurfSharekit\Models\ProtocolNode;
use SurfSharekit\Models\ProtocolNodeAttribute;
use SurfSharekit\Models\ProtocolNodeMapping;
use SurfSharekit\Models\ProtocolNodeNamespace;

class ProtocolModelAdmin extends ModelAdmin {
    private static $url_segment = "external-api";
    private static $menu_title = 'External API';
    private static $menu_priority = 260;
    private static $page_length = 200;

    private static $managed_models = [
        Channel::class,
        Protocol::class,
        CacheClearRequest::class,
        Cache_RecordNode::class
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

        if ($this->modelClass=== Protocol::class) {
            $gridFieldConfig->addComponent(new GridFieldCopyAction([
                (new CopyRelation(ProtocolNode::class, 'ProtocolNodes', 'ProtocolID', [
                    new CopyRelation(ProtocolNodeAttribute::class, 'NodeAttributes', 'ProtocolNodeID'),
                    new CopyRelation(ProtocolNodeNamespace::class, 'NodeNamespaces', 'ProtocolNodeID'),
                    new CopyRelation(ProtocolNodeMapping::class, 'Mapping', 'ProtocolNodeID'),

                ], function ($item) {
                    $item->ProtocolID = 0;
                    $item->ParentProtocolID = 0;
                }))->recursive('ChildrenNodes', 'ParentNodeID'),
                new CopyRelation(ProtocolFilter::class, 'ProtocolFilters', 'ProtocolID', []),
            ]));
        }

        return $form;
    }
}