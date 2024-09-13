<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Group;
use SurfSharekit\Models\ScopeCache;

class GenerateGroupPermissionsCacheTask extends BuildTask {

    protected $title = 'Generate GroupPermission cache';
    protected $description = 'Generates cache for GroupPermissions';

    public function run($request) {
        set_time_limit(0);
        $groups = Group::get()
            ->leftJoin('SurfSharekit_SimpleCacheItem', 'SurfSharekit_SimpleCacheItem.DataObjectID = Group.ID')
            ->whereAny([
                "SurfSharekit_SimpleCacheItem.DataObjectID IS NULL",
                "SurfSharekit_SimpleCacheItem.DataObjectID = '0'"
            ]);

        foreach ($groups as $group) {
            echo("Generating permission cache for group with ID: $group->ID");
            ScopeCache::getPermissionsOfGroup($group);
        }
    }
}