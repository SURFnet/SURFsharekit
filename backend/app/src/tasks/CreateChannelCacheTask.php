<?php

namespace SurfSharekit\Tasks;

use DataObjectJsonApiEncoder;
use ExternalRepoItemChannelJsonApiDescription;
use SilverStripe\Dev\BuildTask;
use SurfSharekit\Api\JsonApi;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\Channel;
use SurfSharekit\Models\RepoItem;

class CreateChannelCacheTask extends BuildTask {

    protected $title = 'Create Channel Cache Task';
    protected $description = 'This task generates the caches of channels';

    protected $enabled = true;

    function run($request) {
        set_time_limit(60 * 60 * 24);//24 hours
        $channelId = $request->getVar('channelId');
        if($channelId){
            /** @var Channel $channel */
            $channel = Channel::get()->byID($channelId);
            $this::refreshCache($channel);
        }
    }

    private static function refreshCache(Channel $channel) {
        $descriptionForDataObject = new ExternalRepoItemChannelJsonApiDescription($channel);
        $listOfRepoItems = $descriptionForDataObject->applyGeneralFilter(RepoItem::get());

        foreach ($listOfRepoItems as $repoItem) {
            if ($repoItem && $repoItem->exists()) {
                $cachedRepoItem = $descriptionForDataObject->getCache($repoItem);
                if(is_null($cachedRepoItem)) {
                    $dataDescription = [];
                    $dataDescription[JsonApi::TAG_ATTRIBUTES] = $descriptionForDataObject->describeAttributesOfDataObject($repoItem);
                    $dataDescription[JsonApi::TAG_TYPE] = DataObjectJsonApiEncoder::describeTypeOfDataObject($descriptionForDataObject);
                    if ($metaInformation = $descriptionForDataObject->describeMetaOfDataObject($repoItem)) {
                        $dataDescription[JsonApi::TAG_META] = $metaInformation;
                    }
                    $dataDescription[JsonApi::TAG_ID] = DataObjectJsonApiEncoder::describeIdOfDataObject($repoItem);
                    $descriptionForDataObject->cache($repoItem, $dataDescription);
                }
            } else {
                $cacheNode = Cache_RecordNode::get()->filter('RepoItemID', $repoItem->ID)->first();
                if ($cacheNode && $cacheNode->exists()) {
                    $cacheNode->delete();
                }
            }
        }
    }

}