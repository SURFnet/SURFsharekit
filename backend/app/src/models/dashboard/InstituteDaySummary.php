<?php

namespace SilverStripe\models\dashboard;

use SilverStripe\api\Upload\Data\Institute;
use SilverStripe\ORM\DataObject;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;

class InstituteDaySummary extends DataObject
{
    private static $table_name = 'SurfSharekit_InstituteDaySummary';
    private static $db = [
        'Day' => 'Date',
        'Downloads' => 'Int',
        'UtmSourceData' => 'Text'
    ];

    private static $has_one = [
        'Institute' => Institute::class
    ];
}