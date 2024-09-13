<?php

namespace SurfSharekit\extensions\Gridfield\Copy;

use SilverStripe\Forms\GridField\GridField_ActionMenuItem;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;

class GridFieldCopyAction implements GridField_ColumnProvider, GridField_ActionProvider, GridField_ActionMenuItem {

    private $relations = [];

    public function __construct($relations) {
        $this->relations = $relations;
    }

    public function getTitle($gridField, $record, $columnName) {
        return 'Copy';
    }

    public function getExtraData($gridField, $record, $columnName) {
        $field = $this->getCopyAction($gridField, $record, $columnName);

        return $field->getAttributes();
    }

    public function getGroup($gridField, $record, $columnName) {
        return GridField_ActionMenuItem::DEFAULT_GROUP;
    }

    public function getActions($gridField) {
        return ['copy'];
    }

    public function handleAction(\SilverStripe\Forms\GridField\GridField $gridField, $actionName, $arguments, $data) {
        if ($actionName == 'copy') {
            $item = $gridField->getList()->byID($arguments['RecordID']);
            if (!$item) {
                return;
            }

            if (!$item->canCreate()) {
                throw new \SilverStripe\ORM\ValidationException(
                    _t(__CLASS__, '.CreatePermissionsFailure', 'No create permissions')
                );
            }

            $this->doCopy($item);
        }
    }

    public function augmentColumns($gridField, &$columns) {
        if (!in_array('Actions', $columns)) {
            $columns[] = 'Actions';
        }
    }

    public function getColumnsHandled($gridField) {
        return ['Actions'];
    }

    public function getColumnContent($gridField, $record, $columnName) {
        return $this->getCopyAction($gridField, $record, $columnName)->Field();
    }

    public function getColumnAttributes($gridField, $record, $columnName) {
        return ['class' => 'grid-field__col-compact'];
    }

    public function getColumnMetadata($gridField, $columnName) {
        if ($columnName == 'Actions') {
            return ['title' => ''];
        }
    }

    private function getCopyAction($gridField, $record, $columnName): GridField_FormAction {
        $title = $this->getTitle($gridField, $record, $columnName);

        return GridField_FormAction::create(
            $gridField,
            'copy' . $record->ID,
            false,
            "copy",
            ['RecordID' => $record->ID]
        )
            ->addExtraClass('action--copy btn--icon-md font-icon-plus-circled btn--no-text grid-field__icon-action action-menu--handled')
            ->setAttribute('classNames', 'action--copy font-icon-plus-circled')
            ->setDescription($title)
            ->setAttribute('aria-label', $title);
    }

    private function doCopy($item) {
        /** @var \SilverStripe\ORM\DataObject $item */
        $clone = $item->duplicate(false);

        if ($clone->hasField('Title')) {
            $clone->Title = $clone->Title . " (copy)";
        }

        $clone->write();

        foreach ($this->relations as $relation) {
            /** @var CopyRelation $relation */
            $relation->doCopyRelation($item, $clone);
       }
    }

    private function doCopyRelation2($orginItem, $clone, $relationName, $parentName, $childRelations = []) {
        $relationItems = $orginItem->{$relationName}();

        if ($relationItems->count() <= 0) {
            return;
        }

        if ($relationItems instanceof \SilverStripe\ORM\HasManyList) {
            foreach ($relationItems as $relationItem) {
                /** @var \SilverStripe\ORM\DataObject $relationItem */
                $relationItemClone = $relationItem->duplicate(false);

                if (array_key_exists(get_class($relationItemClone), $this->classMethods)) {
                    foreach ($this->classMethods[get_class($relationItemClone)] as $classMethod) {
                        $relationItemClone->{$classMethod}();
                    }
                }

                $relationItemClone->{$parentName} = $clone->ID;

                $relationItemClone->write();

                if (array_key_exists(get_class($relationItemClone), $this->recursiveRelations)) {
                    if (!array_key_exists($this->recursiveRelations[get_class($relationItemClone)], $childRelations)) {
                        $childRelations[$this->recursiveRelations[get_class($relationItemClone)]] = [];
                    }
                }

                // check if child also has children that need to be copied
                if (count($childRelations) > 0) {
                    foreach ($childRelations as $relation => $nextChildRelations) {
                        $explRelation = explode(':', $relation);
                        $this->doCopyRelation2($relationItem, $relationItemClone, $explRelation[0], $explRelation[1], $nextChildRelations);
                    }
                }
            }
        } else {
            throw new Exception(get_class($relationItems) . ' relation not supported');
        }

    }

    private function doCopyRelation($oldItem, $clone, $relation, $relationData) {
        $relationField = $relationData[0];
        $relationItems = $oldItem->{$relation}();

        if ($relationItems->count() <= 0) {
            return;
        }

        if ($relationItems instanceof \SilverStripe\ORM\HasManyList) {
            foreach ($relationItems as $relationItem) {
                /** @var \SilverStripe\ORM\DataObject $relationItem */
                $relationItemClone = $relationItem->duplicate(false);
                $relationItemClone->{$relationField} = $clone->ID;

                $relationItemClone->write();

                // check if child has extra children
                if (isset($relationData[1]) && is_array($relationData[1])) {
                    foreach ($relationData[1] as $childRelation => $childRelationData) {
                        $this->doCopyRelation($relationItem, $relationItemClone, $childRelation, $childRelationData);
                    }
                }

                // check if child should duplicate recursive
                if (array_key_exists($relation, $this->recursiveRelations)) {
                    $recursiveRelation = $this->recursiveRelations[$relation];
                    $this->doCopyRelation($relationItem, $relationItemClone, $recursiveRelation[0], $recursiveRelation[1]);
                }
            }
        } else {
            throw new Exception(get_class($relationItems) . ' relation not supported');
        }
    }
}