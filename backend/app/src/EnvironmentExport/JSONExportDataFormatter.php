<?php

namespace SilverStripe\EnvironmentExport;

use Exception;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\helper\Export\EmptyJSONObject;
use SilverStripe\helper\Export\Not;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;

class JSONExportDataFormatter extends ExportDataFormatter {

    /**
     * @return array
     */
    public function supportedExtensions()
    {
        return array(
            'json',
            'js'
        );
    }

    /**
     * @return array
     */
    public function supportedMimeTypes()
    {
        return array(
            'application/json',
            'text/x-json'
        );
    }

    /**
     * @param $array
     * @return string
     */
    public function convertArray($array)
    {
        return json_encode($array);
    }

    /**
     * Generate a JSON representation of the given {@link DataObject}.
     *
     * @param DataObject $obj   The object
     * @param array $fields     If supplied, only fields in the list will be returned
     * @param $relations        Not used
     * @return String JSON
     */
    public function convertDataObject(DataObjectInterface $obj, $fields = null, $relations = null) {
        parent::convertDataObject($obj);
        $formatter = $this->setRemoveFields(["ID"]);
        return json_encode($this->convertDataObjectToJSONObject($obj, $fields, $relations));
    }

    /**
     * Internal function to do the conversion of a single data object. It builds an empty object and dynamically
     * adds the properties it needs to it. If it's done as a nested array, json_encode or equivalent won't use
     * JSON object notation { ... }.
     * @param DataObjectInterface $obj
     * @param  $fields
     * @param  $relations
     * @return EmptyJSONObject
     */
    public function convertDataObjectToJSONObject(DataObjectInterface $obj, $fields = null, $relations = null) {
        $className = get_class($obj);
        $objectToSerialize = ArrayData::array_to_object();

        foreach ($this->getFieldsForObj($obj) as $fieldName => $fieldType) {
            // Field filtering
            if ($fields && !in_array($fieldName, $fields)) {
                continue;
            }

            $fieldValue = self::cast($obj->obj($fieldName));
            $mappedFieldName = $this->getFieldAlias($className, $fieldName);
            $objectToSerialize->$mappedFieldName = $fieldValue;
        }


        $removeFields = $this->getRemoveFields();
        $includeFields = $this->getCustomFields();
        foreach ($obj->hasOne() as $relName => $relClass) {
            if (count($includeFields) && !in_array($relName, $includeFields)) {
                continue;
            }
            if (in_array($relName, $removeFields)) {
                continue;
            }

            // Field filtering
            $relationalObject = $obj->$relName();
            if ($fields && !in_array($relName, $fields)) {
                continue;
            }
            if ($relationalObject && (!$relationalObject->exists() || !$relationalObject->canView())) {
                continue;
            }

            // Check if a relational uuid field exists on the parent. This can be used to replace the auto increment ID of the relation
            if (!property_exists($relationalObject,  "Uuid")) {
//                    throw new Exception("All exported DataObjects should have a Uuid field");
            }

            // Set the ID to a Uuid so the environment where the export is imported can reconstruct the relations
            $relationalIDFieldName = $relName . 'ID';
            $relationalUUIDFieldName = $relName . 'Uuid';
            $objectToSerialize->$relationalIDFieldName = $obj->ID;
            $objectToSerialize->$relationalUUIDFieldName = $relationalObject->Uuid;
        }


        return $objectToSerialize;
    }

    /**
     * Generate a JSON representation of the given {@link SS_List}.
     *
     * @param SS_List $set
     * @return String XML
     */
    public function convertDataObjectSet(SS_List $set, $fields = null) {
        $items = array();
        foreach ($set as $do) {
            if (!$do->canView()) {
                continue;
            }
            $items[] = $this->convertDataObjectToJSONObject($do, $fields);
        }

        $serobj = ArrayData::array_to_object(array(
            "totalSize" => (is_numeric($this->totalSize)) ? $this->totalSize : null,
            "items" => $items
        ));

        return json_encode($serobj);
    }

    /**
     * @param string $strData
     * @return array|bool|void
     */
    public function convertStringToArray($strData)
    {
        return json_decode($strData, true);
    }

    public static function cast(FieldType\DBField $dbfield)
    {
        switch (true) {
            case $dbfield instanceof FieldType\DBInt:
                return (int)$dbfield->RAW();
            case $dbfield instanceof FieldType\DBFloat:
                return (float)$dbfield->RAW();
            case $dbfield instanceof FieldType\DBBoolean:
                return (bool)$dbfield->RAW();
            case is_null($dbfield->RAW()):
                return null;
        }
        return $dbfield->RAW();
    }
}