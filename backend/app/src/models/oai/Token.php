<?php

namespace SilverStripe\models\oai;

use SilverStripe\ORM\DataObject;

class Token extends DataObject {
    private static $table_name = 'tokens';

    private static $db = [
        'token' => 'Varchar(8)',
        'verb' => 'Varchar(16)',
        'parameters' => 'Text',
        'validUntil' => 'Datetime'
    ];
}