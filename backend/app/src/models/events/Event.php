<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;

class Event extends DataObject {
    private static $singular_name = 'Event';
    private static $plural_name = 'Events';

    private static $table_name = 'SurfSharekit_Event';

    private static $db = [
        'ComparisonUuid' => 'Varchar(255)',
        'Type' => 'Varchar(255)',
        'Message' => 'Text',
        'Invalidated' => 'Boolean'
    ];

    private static $has_one = [
        'Object' => DataObject::class
    ];

}