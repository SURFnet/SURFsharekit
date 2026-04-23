<?php

namespace SilverStripe\EnvironmentExport;

use Exception;
use ReflectionException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\models\blueprints\Blueprint;
use SilverStripe\models\UploadApiUser;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionRole;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Person;

abstract class ExportDataFormatter {

    use Configurable;

    /**
     * Set priority from 0-100.
     * If multiple formatters for the same extension exist,
     * we select the one with highest priority.
     *
     * @var int
     */
    private static $priority = 50;

    /**
     * Allows overriding of the fields which are rendered for the
     * processed dataobjects. By default, this includes all
     * fields in {@link DataObject::inheritedDatabaseFields()}.
     *
     * @var array
     */
    protected $customFields = null;

    /**
     * Allows addition of fields
     * (e.g. custom getters on a DataObject)
     *
     * @var array
     */
    protected $customAddFields = null;

    /**
     * Fields which should be expicitly excluded from the export.
     * Comes in handy for field-level permissions.
     * Will overrule both {@link $customAddFields} and {@link $customFields}
     *
     * @var array
     */
    protected $removeFields = null;

    /**
     * Specifies the mimetype in which all strings
     * returned from the convert*() methods should be used,
     * e.g. "text/xml".
     *
     * @var string
     */
    protected $outputContentType = null;

    /**
     * Used to set totalSize properties on the output
     * of {@link convertDataObjectSet()}, shows the
     * total number of records without the "limit" and "offset"
     * GET parameters. Useful to implement pagination.
     *
     * @var int
     */
    protected $totalSize;

    /**
     * Backslashes in fully qualified class names (e.g. NameSpaced\ClassName)
     * kills both requests (i.e. URIs) and XML (invalid character in a tag name)
     * So we'll replace them with a hyphen (-), as it's also unambiguious
     * in both cases (invalid in a php class name, and safe in an xml tag name)
     *
     * @param string $classname
     * @return string 'escaped' class name
     */
    protected function sanitiseClassName($className)
    {
        return str_replace('\\', '-', $className);
    }

    /**
     * Get a ExportDataFormatter object suitable for handling the given file extension.
     *
     * @param string $extension
     * @return ExportDataFormatter
     */
    public static function for_extension($extension)
    {
        $classes = ClassInfo::subclassesFor(ExportDataFormatter::class);
        array_shift($classes);
        $sortedClasses = [];
        foreach ($classes as $class) {
            $sortedClasses[$class] = Config::inst()->get($class, 'priority');
        }
        arsort($sortedClasses);
        foreach ($sortedClasses as $className => $priority) {
            $formatter = new $className();
            if (in_array($extension, $formatter->supportedExtensions())) {
                return $formatter;
            }
        }
    }

    /**
     * Get formatter for the first matching extension.
     *
     * @param array $extensions
     * @return ExportDataFormatter
     */
    public static function for_extensions($extensions) {
        foreach ($extensions as $extension) {
            if ($formatter = self::for_extension($extension)) {
                return $formatter;
            }
        }

        return false;
    }

    /**
     * Get a ExportDataFormatter object suitable for handling the given mimetype.
     *
     * @param string $mimeType
     * @return ExportDataFormatter
     */
    public static function for_mimetype($mimeType) {
        $classes = ClassInfo::subclassesFor(ExportDataFormatter::class);
        array_shift($classes);
        $sortedClasses = [];
        foreach ($classes as $class) {
            $sortedClasses[$class] = Config::inst()->get($class, 'priority');
        }
        arsort($sortedClasses);
        foreach ($sortedClasses as $className => $priority) {
            $formatter = new $className();
            if (in_array($mimeType, $formatter->supportedMimeTypes())) {
                return $formatter;
            }
        }
    }

    /**
     * Get formatter for the first matching mimetype.
     * Useful for HTTP Accept headers which can contain
     * multiple comma-separated mimetypes.
     *
     * @param array $mimetypes
     * @return ExportDataFormatter
     */
    public static function for_mimetypes($mimetypes) {
        foreach ($mimetypes as $mimetype) {
            if ($formatter = self::for_mimetype($mimetype)) {
                return $formatter;
            }
        }

        return false;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function setCustomFields($fields) {
        $this->customFields = $fields;
        return $this;
    }

    /**
     * @return array
     */
    public function getCustomFields() {
        return $this->customFields;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function setCustomAddFields($fields) {
        $this->customAddFields = $fields;
        return $this;
    }

    /**
     * @return array
     */
    public function getCustomAddFields() {
        return $this->customAddFields;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function setRemoveFields($fields) {
        $this->removeFields = $fields;
        return $this;
    }

    /**
     * @return array
     */
    public function getRemoveFields() {
        return $this->removeFields;
    }

    /**
     * @return string
     */
    public function getOutputContentType() {
        return $this->outputContentType;
    }

    /**
     * @param int $size
     * @return $this
     */
    public function setTotalSize($size) {
        $this->totalSize = (int)$size;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalSize() {
        return $this->totalSize;
    }

    /**
     * Returns all fields on the object which should be shown
     * in the output. Can be customised through {@link self::setCustomFields()}.
     *
     * @param DataObject $obj
     * @return array
     */
    protected function getFieldsForObj($obj) {
        $dbFields = [];

        // if custom fields are specified, only select these
        if (is_array($this->customFields) && count($this->customFields)) {
            foreach ($this->customFields as $fieldName) {
                // @todo Possible security risk by making methods accessible - implement field-level security
                if (($obj->hasField($fieldName) && !is_object($obj->getField($fieldName)))
                    || $obj->$fieldName !== null
                ) {
                    $dbFields[$fieldName] = $fieldName;
                }
            }
        } else {
            // by default, all database fields are selected
            $dbFields = DataObject::getSchema()->fieldSpecs(get_class($obj));
            // $dbFields = $obj->inheritedDatabaseFields();
        }

        if (is_array($this->customAddFields)) {
            foreach ($this->customAddFields as $fieldName) {
                // @todo Possible security risk by making methods accessible - implement field-level security
                if (($obj->hasField($fieldName) && !is_object($obj->getField($fieldName)))
                    || $obj->$fieldName !== null) {
                    $dbFields[$fieldName] = $fieldName;
                }
            }
        }

        // add default required fields
        $dbFields = array_merge($dbFields, ['ID' => 'Int']);

        if (is_array($this->removeFields)) {
            $dbFields = array_diff_key($dbFields, array_combine($this->removeFields, $this->removeFields));
        }

        return $dbFields;
    }

    /**
     * Return an array of the extensions that this data formatter supports
     */
    abstract public function supportedExtensions();

    abstract public function supportedMimeTypes();

    /**
     * Convert a single data object to this format. Return a string.
     *
     * @param DataObjectInterface $dataObject
     */
    public function convertDataObject(DataObjectInterface $dataObject) {
        $implementsExportableInterface = in_array(Exportable::class, class_uses_recursive($dataObject));
        if (!$implementsExportableInterface) {
            // Check if one of the extensions implements the Exportable trait
            $extensionClasses = $dataObject::singleton()->getExtensionInstances();
            foreach ($extensionClasses as $extensionClass) {
                if (in_array(Exportable::class, class_uses($extensionClass))) {
                    $implementsExportableInterface = true;
                    break;
                }
            }
            if (!$implementsExportableInterface && !$dataObject instanceof Blueprint) {
                throw new Exception("Cannot export DataObject as it does not implement the Exportable interface");
            }
        }
        $includedFields = $dataObject->invokeWithExtensions('includedFieldsForImport');
        $includedFields = array_merge_recursive(...$includedFields);
        if (count($includedFields)) {
            $this->setCustomFields($includedFields);
            $this->setRemoveFields([]);
        } else {
            $excludedFields = $dataObject->invokeWithExtensions('excludedFieldsForImport');
            $excludedFields = array_merge_recursive($excludedFields);
            $this->setCustomFields([]);
            $this->setRemoveFields($excludedFields);
        }
        $addedFields = $dataObject->invokeWithExtensions('addedFieldsForImport');
        $addedFields = array_merge_recursive(...$addedFields);
        $this->setCustomAddFields($addedFields);
    }

    /**
     * Convert a data object set to this format. Return a string.
     *
     * @param SS_List $set
     * @return string
     */
    abstract public function convertDataObjectSet(SS_List $set);

    /**
     * Convert an array to this format. Return a string.
     *
     * @param $array
     * @return string
     */
    abstract public function convertArray($array);

    /**
     * @param string $strData HTTP Payload as string
     */
    public function convertStringToArray($strData) {
        user_error('ExportDataFormatter::convertStringToArray not implemented on subclass', E_USER_ERROR);
    }

    /**
     * Convert an array of aliased field names to their Dataobject field name
     *
     * @param string $className
     * @param string[] $fields
     * @return string[]
     */
    public function getRealFields($className, $fields) {
        $apiMapping = $this->getApiMapping($className);
        if (is_array($apiMapping) && is_array($fields)) {
            $mappedFields = [];
            foreach ($fields as $field) {
                $mappedFields[] = $this->getMappedKey($apiMapping, $field);
            }
            return $mappedFields;
        }
        return $fields;
    }

    /**
     * Get the DataObject field name from its alias
     *
     * @param string $className
     * @param string $field
     * @return string
     */
    public function getRealFieldName($className, $field) {
        $apiMapping = $this->getApiMapping($className);
        return $this->getMappedKey($apiMapping, $field);
    }

    /**
     * Get a DataObject Field's Alias
     * defaults to the fieldname
     *
     * @param string $className
     * @param string $field
     * @return string
     */
    public function getFieldAlias($className, $field) {
        $apiMapping = $this->getApiMapping($className);
        $apiMapping = array_flip($apiMapping);
        return $this->getMappedKey($apiMapping, $field);
    }

    /**
     * Get the 'api_field_mapping' config value for a class
     * or return an empty array
     *
     * @param string $className
     * @return string[]|array
     */
    protected function getApiMapping($className) {
        $apiMapping = Config::inst()->get($className, 'api_field_mapping');
        if ($apiMapping && is_array($apiMapping)) {
            return $apiMapping;
        }
        return [];
    }

    /**
     * Helper function to get mapped field names
     *
     * @param array $map
     * @param string $key
     * @return string
     */
    protected function getMappedKey($map, $key) {
        if (is_array($map)) {
            if (array_key_exists($key, $map)) {
                return $map[$key];
            } else {
                return $key;
            }
        }
        return $key;
    }

    /**
     * Parse many many relation class (works with through array syntax)
     *
     * @param string|array $class
     * @return string|array
     */
    public static function parseRelationClass($class)
    {
        // detect many many through syntax
        if (is_array($class)
            && array_key_exists('through', $class)
            && array_key_exists('to', $class)
        ) {
            $toRelation = $class['to'];

            $hasOne = Config::inst()->get($class['through'], 'has_one');
            if (empty($hasOne) || !is_array($hasOne) || !array_key_exists($toRelation, $hasOne)) {
                return $class;
            }

            return $hasOne[$toRelation];
        }

        return $class;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getAllExportableDataObjectClasses(): array {
        $exportableDataObjectClasses = [];
        $dataObjectClasses = ClassInfo::subclassesFor(DataObject::class);
        /** @var DataObject $dataObjectClass */
        foreach($dataObjectClasses as $dataObjectClass) {
            // Hard deny these two classes as they extend a class that does need export
            if ($dataObjectClass == Person::class || $dataObjectClass == UploadApiUser::class) continue;

            $implementsExportableInterface = in_array(Exportable::class, class_uses($dataObjectClass));

            // Also check for extensions to include SilverStripe classes such as Member
            if (!$implementsExportableInterface) {
                $extensionClasses = $dataObjectClass::singleton()->getExtensionInstances();
                foreach ($extensionClasses as $extensionClass) {
                    if (in_array(Exportable::class, class_uses($extensionClass))) {
                        $implementsExportableInterface = true;
                        break;
                    }
                }
            }

            if ($implementsExportableInterface) {
                $exportableDataObjectClasses[] = $dataObjectClass;
            }
        }

        return $exportableDataObjectClasses;
    }
}