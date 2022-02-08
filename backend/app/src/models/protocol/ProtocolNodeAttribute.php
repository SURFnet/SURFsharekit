<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;

/**
 * Class ProtocolNodeAttribute
 * @package SurfSharekit\Models
 * DataObject representing a single attribute added to a @see ProtocolNode
 */
class ProtocolNodeAttribute extends DataObject {
    private static $table_name = 'SurfSharekit_ProtocolNodeAttribute';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Value' => 'Text',
        'NamespaceURI' => 'Varchar(255)'
    ];

    private static $has_one = [
        'ProtocolNode' => ProtocolNode::class
    ];

    private static $summary_fields = [
        'Title', 'Value'
    ];


    public function canCreate($member = null, $context = []) {
        return true;
    }

    public function canDelete($member = null) {
        return true;
    }

    public function canEdit($member = null) {
        return true;
    }

    public function canView($member = null) {
        return true;
    }

}