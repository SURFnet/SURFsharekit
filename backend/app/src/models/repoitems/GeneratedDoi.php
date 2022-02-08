<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;

class GeneratedDoi extends DataObject {
    private static $table_name = 'SurfSharekit_GeneratedDoi';

    private static $db = [
        'DOI' => 'Varchar(255)'
    ];

    private static $has_one = [
        'Person' => Person::class,
        'RepoItem' => RepoItem::class
    ];
}
