<?php

namespace SilverStripe\models\oai;

use SilverStripe\ORM\DataObject;

class Record extends DataObject {

    private static $table_name = 'records';

    private static $db = [
        'identifier' => 'Varchar(255)',
        'lastChanged' => 'Datetime',
        'content' => 'Text',
        'metadataPrefix' => 'Varchar(16)'
    ];
}