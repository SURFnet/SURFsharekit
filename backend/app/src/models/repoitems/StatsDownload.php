<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;

class StatsDownload extends DataObject {
    private static $table_name = 'SurfSharekit_StatsDownload';

    private static $db = [
        'IsPublic' => 'Boolean(0)',
        'RepoType' => 'Varchar(255)',
        'DownloadDate' => 'Datetime'
    ];

    private static $has_one = [
        'RepoItemFile' => RepoItemFile::class,
        'Institute' => Institute::class,
        'RepoItem' => RepoItem::class
    ];

    private static $indexes = [
        'DownloadDateIndex' => [
            'columns' => ['DownloadDate']
        ]
    ];

    protected function onAfterWrite() {
        parent::onAfterWrite();
        if($this->isChanged('InstituteID')) {
            ScopeCache::removeCachedViewable(StatsDownload::class);
            ScopeCache::removeCachedDataList(StatsDownload::class);
        }
    }
}
