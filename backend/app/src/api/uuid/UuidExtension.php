<?php

use Ramsey\Uuid\Uuid;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\DB;

/**
 * https://github.com/lekoala/silverstripe-uuid/blob/master/LICENSE
 * Has dependency on https://github.com/tuupola/base62 and "ramsey/uuid"
 * A uuid field that stores Uuid in binary formats
 *
 * Some knowledge...
 *
 * @link https://paragonie.com/blog/2015/09/comprehensive-guide-url-parameter-encryption-in-php
 * @link https://www.percona.com/blog/2014/12/19/store-uuid-optimized-way/
 * @link https://mariadb.com/kb/en/library/guiduuid-performance/
 * @link https://stackoverflow.com/questions/28251144/inserting-and-selecting-uuids-as-binary16
 */
class UuidExtension extends DataExtension {
    static $summary_fields = ['Uuid'];

    private static $db = [
        "Uuid" => DBUuid::class,
    ];

    private static $indexes = [
        'Uuid' => [
            'type' => 'unique',
            'columns' => ['Uuid']
        ]
    ];

    /**
     * Get a record by its uuid
     *
     * @param string $class The class
     * @param string $uuid The uuid value
     * @param string $format Any UUID_XXXX_FORMAT constant or string
     * @return DataObject|false The DataObject or false if no record is found or format invalid
     */
    public static function getByUuid($class, $value, $format = null) {
        $uuid = Uuid::fromString($value);
        // Fetch the first record and disable subsite filter in a similar way as asking by ID
        $q = $class::get()->filter('Uuid', $uuid->toString())->setDataQueryParam('Subsite.filter', false);
        return $q->first();
    }

    /**
     * Assign a new uuid to this record. This will overwrite any existing uuid.
     *
     * @param string $field The field where the Uuid is stored in binary format
     * @param bool $check Check if the uuid is already taken
     * @return string The new uuid
     */
    public function assignNewUuid($field = 'Uuid', $check = false) {
        $uuid = Uuid::uuid4();
        if ($check) {
            $schema = DataObjectSchema::create();
            $table = $schema->tableName(get_class($this->owner));
            do {
                $this->owner->Uuid = $uuid->toString();
                // If we have something, keep checking
                $check = DB::prepared_query('SELECT count(ID) FROM ' . $table . ' WHERE Uuid = ?', [$this->owner->Uuid])->value() > 0;
            } while ($check);
        } else {
            $this->owner->Uuid = $uuid->toString();
        }

        return $this->owner->Uuid;
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();

        if (!$this->owner->Uuid || $this->owner->Uuid == '') {
            $this->assignNewUuid();
        }
    }
}