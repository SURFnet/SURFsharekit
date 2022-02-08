<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Class MigrationLog
 * @package SurfSharekit\Models
 * DataObject with the results of the migration
 */
class MigrationLog extends DataObject  {
    private static $extensions = [
        Versioned::class . '.versioned',
    ];


    private static $table_name = 'SurfSharekit_MigrationLog';
    private static $default_sort = 'LastEdited DESC';

    private static $db = [
        'Source' => 'Varchar(1024)',
        'MigratedAt' => 'Datetime',
        'Log' => 'Text',
        'Data' => 'Text'

    ];

    private static $has_one = [
        'TargetObject' => DataObject::class
    ];

    private static $summary_fields = [
        'Source' => 'Source',
        'MigratedAt' => 'MigratedAt'
    ];

    private static $indexes = [

    ];

    public function canView($member = null) {
        return true;
    }

}
