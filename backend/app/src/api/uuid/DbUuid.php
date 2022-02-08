<?php

use Ramsey\Uuid\Uuid;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;

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
class DBUuid extends DBField {

    public function requireField() {
        // Use direct sql statement here
        $sql = "varchar(255)";
        DB::require_field($this->tableName, $this->name, $sql);
    }

    /**
     * @return string A uuid identifier like 0564a64ecdd4a2-7731-3233-3435-7cea2b
     */
    public function Nice() {
        if (!$this->value) {
            return $this->nullValue();
        }
        return Uuid::fromString($this->value)->toString();
    }

    public function nullValue() {
        return null;
    }

    public function scaffoldFormField($title = null, $params = null) {
        return false;
    }

    public function prepValueForDB($value) {
        if (!$value) {
            return $this->nullValue();
        }
        // Uuid in string format have 36 chars
        // Strlen 16 = already binary
        if (strlen($value) === 16) {
            return $value;
        }
        return Uuid::fromString($value)->toString();
    }

    public function scaffoldSearchField($title = null) {
        return parent::scaffoldFormField($title);
    }
}