<?php

namespace SurfSharekit\Tasks;

use DataObjectJsonApiEncoder;
use ExternalPersonJsonApiDescription;
use ExternalRepoItemChannelJsonApiDescription;
use SilverStripe\Dev\BuildTask;
use SurfSharekit\Api\JsonApi;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\Channel;
use SurfSharekit\Models\Person;
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
        $descriptionForRepoItem = new ExternalRepoItemChannelJsonApiDescription($channel);
        $listOfRepoItems = $descriptionForRepoItem->applyGeneralFilter(RepoItem::get());

        $descriptionForPerson = new ExternalPersonJsonApiDescription($channel);
        $listOfPersons = $descriptionForRepoItem->applyGeneralFilter(Person::get());

        foreach ($listOfRepoItems as $repoItem) {
            if ($repoItem && $repoItem->exists()) {
                $cachedRepoItem = $descriptionForRepoItem->getCache($repoItem);
                if(is_null($cachedRepoItem)) {
                    $dataDescription = [];
                    $dataDescription[JsonApi::TAG_ATTRIBUTES] = $descriptionForRepoItem->describeAttributesOfDataObject($repoItem);
                    $dataDescription[JsonApi::TAG_TYPE] = DataObjectJsonApiEncoder::describeTypeOfDataObject($descriptionForRepoItem);
                    if ($metaInformation = $descriptionForRepoItem->describeMetaOfDataObject($repoItem)) {
                        $dataDescription[JsonApi::TAG_META] = $metaInformation;
                    }
                    $dataDescription[JsonApi::TAG_ID] = DataObjectJsonApiEncoder::describeIdOfDataObject($repoItem);
                    $descriptionForRepoItem->cache($repoItem, $dataDescription);
                }
            } else {
                $cacheNode = Cache_RecordNode::get()->filter('RepoItemID', $repoItem->ID)->first();
                if ($cacheNode && $cacheNode->exists()) {
                    $cacheNode->delete();
                }
            }
        }

        foreach ($listOfPersons as $person) {
            if ($person && $person->exists()) {
                $cachedPerson = $descriptionForPerson->getCache($person);
                if(is_null($cachedPerson)) {
                    $dataDescription = [];
                    $dataDescription[JsonApi::TAG_ATTRIBUTES] = $descriptionForPerson->describeAttributesOfDataObject($person);
                    $dataDescription[JsonApi::TAG_TYPE] = DataObjectJsonApiEncoder::describeTypeOfDataObject($descriptionForPerson);
                    if ($metaInformation = $descriptionForPerson->describeMetaOfDataObject($person)) {
                        $dataDescription[JsonApi::TAG_META] = $metaInformation;
                    }
                    $dataDescription[JsonApi::TAG_ID] = DataObjectJsonApiEncoder::describeIdOfDataObject($person);
                    $descriptionForPerson->cache($person, $dataDescription);
                }
            } else {
                $cacheNode = Cache_RecordNode::get()->filter('PersonID', $person->ID)->first();
                if ($cacheNode && $cacheNode->exists()) {
                    $cacheNode->delete();
                }
            }
        }


    }

}