<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;

/**
 * Class ProtocolNode
 * @package SurfSharekit\Models
 * DataObject representing a single value added to a @see Protocol
 */
class ProtocolNodeMapping extends DataObject {
    private static $table_name = 'SurfSharekit_ProtocolNodeMapping';

    private static $db = [
        'SourceValue' => 'Varchar(1024)',
        'TargetValue' => 'Varchar(1024)'
    ];

    private static $has_one = [
        'ProtocolNode' => ProtocolNode::class
    ];

    private static $summary_fields = [
        'SourceValue', 'TargetValue'
    ];

    public function getTitle() {
        return $this->SourceValue;
    }

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