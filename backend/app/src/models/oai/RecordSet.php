<?php

namespace SilverStripe\models\oai;

use SilverStripe\ORM\DataObject;

class RecordSet extends DataObject {
    private static $table_name = 'records_sets';

    private static $db = [
        'record_identifier' => 'Varchar(255)',
        'record_metadataPrefix' => 'Varchar(16)',
        'set_spec' => 'Varchar(255)'
    ];
}