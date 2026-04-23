<?php

namespace SurfSharekit\Piwik\Events;

use SilverStripe\constants\UtmContent;
use SurfSharekit\Piwik\CustomEventDimension;

class DownloadLinkEvent extends DownloadEvent {
    public function __construct(
        string $repoItemLinkUuid,
        string $repoItemUuid,
        string $repoItemType,
        string $rootInstituteUuid,
        string $utmSource,
        string $url
    ) {
        $this->dimensions = [
            CustomEventDimension::REPO_ITEM_LINK_ID => $repoItemLinkUuid,
            CustomEventDimension::REPO_ITEM_ID => $repoItemUuid,
            CustomEventDimension::REPO_TYPE => $repoItemType,
            CustomEventDimension::ROOT_INSTITUTE_ID => $rootInstituteUuid,
            CustomEventDimension::UTM_SOURCE => $utmSource,
            CustomEventDimension::UTM_CONTENT => UtmContent::LINK,
            CustomEventDimension::REPO_ITEM_LINK_URL => $url,
        ];
    }
}