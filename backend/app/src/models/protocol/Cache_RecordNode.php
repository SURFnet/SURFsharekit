<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;

/**
 * Class ProtocolNode
 * @package SurfSharekit\Models
 * DataObject representing a record node
 */
class Cache_RecordNode extends DataObject {
    private static $table_name = 'SurfSharekit_Cache_RecordNode';

    private static $db = [
        'CachedLastEdited' => 'Datetime',
        'Data' => 'Text',
        'Status' => 'Enum(array("Active", "Inactive", "Active"))',
        'ProtocolVersion' => 'Int',
        'Endpoint' => "Enum('OAI,SRU,JSON:API',null)"
    ];

    private static $has_one = [
        'Protocol' => Protocol::class,
        'RepoItem' => RepoItem::class,
        'Channel' => Channel::class
    ];

    private static $indexes = [
        'Endpoint' => true
    ];

    public function canCreate($member = null, $context = []) {
        return true;
    }

    public function canEdit($member = null) {
        return true;
    }

    public function canView($member = null) {
        return true;
    }
}