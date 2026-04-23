<?php

namespace SilverStripe\models\oai;

use SilverStripe\ORM\DataObject;

class Format extends DataObject {

    private static $table_name = 'formats';

    private static $db = [
        'prefix' => 'Varchar(16)',
        'namespaceUri' => 'Varchar(255)',
        'xmlSchema' => 'Varchar(255)'
    ];
}