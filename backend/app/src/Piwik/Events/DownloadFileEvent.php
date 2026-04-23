<?php

namespace SurfSharekit\Piwik\Events;

use SilverStripe\constants\UtmContent;
use SurfSharekit\Piwik\CustomEventDimension;

class DownloadFileEvent extends DownloadEvent {
    public function __construct(
        string $repoItemFileUuid,
        string $repoItemUuid,
        string $repoItemType,
        string $rootInstituteUuid,
        string $utmSource
    ) {
        $this->dimensions = [
            CustomEventDimension::REPO_ITEM_FILE_ID => $repoItemFileUuid,
            CustomEventDimension::REPO_ITEM_ID => $repoItemUuid,
            CustomEventDimension::REPO_TYPE => $repoItemType,
            CustomEventDimension::ROOT_INSTITUTE_ID => $rootInstituteUuid,
            CustomEventDimension::UTM_SOURCE => $utmSource,
            CustomEventDimension::UTM_CONTENT => UtmContent::DOWNLOAD,
        ];
    }
}