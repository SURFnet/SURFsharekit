<?php

namespace SurfSharekit\Piwik;

use SilverStripe\constants\ApplicationEnvironment;
use SilverStripe\Core\Environment;
use SurfSharekit\Models\Helper\Logger;

class CustomEventDimension {
    const REPO_ITEM_ID = "RepoItemId";
    const REPO_TYPE = "RepoType";
    const ROOT_INSTITUTE_ID = "RootInstituteId";
    const REPO_ITEM_FILE_ID = "RepoItemFileId";
    const REPO_ITEM_LINK_ID = "RepoItemLinkId";
    const UTM_SOURCE = "UtmSource";
    const UTM_CONTENT = "UtmContent";
    
    private int $id;
    private int $slot;
    
    public function __construct(int $id, int $slot) {
        $this->id = $id;
        $this->slot = $slot;
    }

    /**
     * @return string|null
     * Returns a custom dimension key, this key can exclusively be used to track custom events, not for retrieval of data
     * Piwik expects the id of the custom event to be used when retrieving data
     */
    public function getTrackingKey(): string {
        return "event_custom_dimension_$this->id";
    }

    /**
     * @return string|null
     * Returns a custom dimension key, this key can exclusively be used for the retrieval of custom events
     * Piwik expects the slot of the custom event to be used when retrieving data
     */
    public function getRetrievalKey(): string {
        return "event_custom_dimension_$this->slot";
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getSlot(): int {
        return $this->slot;
    }
}