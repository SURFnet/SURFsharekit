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
        'Endpoint' => "Enum('OAI,SRU,JSON:API',null)",
        "Deleted" => "Boolean(0)",
        "DeleteWebhookSent" => "Boolean(0)"
    ];

    private static $has_one = [
        'Protocol' => Protocol::class,
        'RepoItem' => RepoItem::class,
        'Channel' => Channel::class,
        'Person' => Person::class
    ];

    private static $summary_fields = [
        "CachedLastEdited",
        "Status",
        "ProtocolVersion",
        "Endpoint"
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