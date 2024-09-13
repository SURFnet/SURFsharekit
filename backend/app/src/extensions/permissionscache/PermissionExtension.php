<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Group;
use SilverStripe\Security\PermissionRoleCode;
use SurfSharekit\Models\Helper\Logger;

/**
 * Extension to Silverstripe Permission DataObject to make sure permission caches are removed when nessecary
 */
class PermissionExtension extends DataExtension {
    public function onBeforeWrite() {
        parent::onBeforeWrite();
        if (!$this->owner->exists()){ //merely written again, not needed
            Logger::debugLog("Een code aan een groep toevoegen");
            $this->owner->CleanGroupCache = true; //do after write to generate new
        }
    }

    public function onAfterWrite() {
        parent::onAfterWrite();
        if ($this->owner->CleanGroupCache){
            ScopeCache::getPermissionsOfGroup($this->owner->Group());
        }
    }

    public function onAfterDelete() {
        parent::onBeforeDelete();
        Logger::debugLog("Een code van een groep verwijderen");
        ScopeCache::getPermissionsOfGroup($this->owner->Group());
    }
}