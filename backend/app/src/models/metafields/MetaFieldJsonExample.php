<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;
use SurfSharekit\Models\MetaField;

class MetaFieldJsonExample extends DataObject {

    private static $table_name = 'SurfSharekit_MetaFieldJsonExample';
    private static $has_one = array();

    private static $db = [
        'Title' => 'Varchar(255)',
        'Example' => 'Text'
    ];
}