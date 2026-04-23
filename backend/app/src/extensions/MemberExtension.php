<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\ORM\DataExtension;

class MemberExtension extends dataExtension{
    private static $indexes = [
        'FulltextSearchFirstName' => [
            'type' => 'fulltext',
            'columns' => ['FirstName']
        ],
        'FulltextSearchSurname' => [
            'type' => 'fulltext',
            'columns' => ['Surname']
        ],
    ];
}