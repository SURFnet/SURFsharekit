<?php

namespace SurfSharekit\Tasks;

use DataObjectCSVFileEncoder;
use DataObjectJsonApiEncoder;
use ExternalPersonJsonApiDescription;
use ExternalRepoItemChannelJsonApiDescription;
use SilverStripe\Dev\BuildTask;
use SimpleXMLElement;
use SurfSharekit\Api\JsonApi;
use SurfSharekit\Api\ListRecordsNode;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\RepoItem;

class RefreshProtocolCacheTask extends BuildTask {

    protected $title = 'Refresh Protocol Cache Task';
    protected $description = 'This task regenerates the caches of protocols set to invalidate = 1';

    protected $enabled = true;

    function run($request) {
        set_time_limit(60 * 60 * 72);//72 hours
        foreach (Protocol::get()->filter('InvalidateCache', 1)->filter('CacheLock', 0)->limit(1) as $protocolToInvalidateCacheOf) {
            $protocolToInvalidateCacheOf->CacheLock = 1;
            $protocolToInvalidateCacheOf->write();
            $protocolID = $protocolToInvalidateCacheOf->ID;
            /** @var Protocol $protocolToInvalidateCacheOf */
            $protocolToInvalidateCacheOf = Protocol::get()->byID($protocolID);
            Logger::debugLog('Start RefreshProtocolCacheTask ' . date('Y-m-d H:i:s'), self::class, __FUNCTION__);
            static::refreshCache($protocolToInvalidateCacheOf);
            Logger::debugLog('End RefreshProtocolCacheTask ' . date('Y-m-d H:i:s'), self::class, __FUNCTION__);
            $protocolToInvalidateCacheOf->InvalidateCache = 0;
            $protocolToInvalidateCacheOf->CacheLock = 0;
            $protocolToInvalidateCacheOf->write();
        }
    }

    private static function refreshCache(Protocol $protocol) {
        $listOfRepoItems = RepoItem::get()
            ->innerJoin('SurfSharekit_Cache_RecordNode', 'SurfSharekit_Cache_RecordNode.RepoItemID = SurfSharekit_RepoItem.ID')
            ->where("SurfSharekit_Cache_RecordNode.ProtocolID = $protocol->ID")
            ->where("SurfSharekit_Cache_RecordNode.ProtocolVersion < " . $protocol->Version);

        $listOfPersons = Person::get()
            ->innerJoin('SurfSharekit_Cache_RecordNode', 'SurfSharekit_Cache_RecordNode.PersonID = SurfSharekit_Person.ID')
            ->where("SurfSharekit_Cache_RecordNode.ProtocolID = $protocol->ID")
            ->where("SurfSharekit_Cache_RecordNode.ProtocolVersion < " . $protocol->Version);

        if ($protocol->SystemKey == 'OAI-PMH') {
            $listOfRepoItems = $listOfRepoItems->where("SurfSharekit_Cache_RecordNode.EndPoint = 'OAI'");
        } else if ($protocol->SystemKey == 'CSV') {
            foreach ($listOfRepoItems as $repoItem) {
                DataObjectCSVFileEncoder::getCSVRowFor($repoItem, true, $protocol);
            }
            // TODO, exit or skip next foreach check
        }

        foreach ($listOfRepoItems as $repoItem) {
            if ($repoItem && $repoItem->exists()) {
                $cachedRecords = Cache_RecordNode::get()->filter(['RepoItemID' => $repoItem->ID, 'ProtocolID' => $protocol->ID, 'ProtocolVersion:LessThan' => $protocol->Version, 'ChannelID:not' => 0]);
                foreach($cachedRecords as $cachedRecord) {
                    $channel = $cachedRecord->Channel;
                    if ($protocol->SystemKey == 'OAI-PMH') {
                        $repoItemSummary = [
                            'Uuid' => $repoItem->Uuid,
                            'LastEdited' => $repoItem->LastEdited,
                            'Cached' => 1,
                            'InstituteUuid' => $repoItem->InstituteUuid,
                            'PartOfChannel' => 1,
                            'IsActive' => $repoItem->IsActive
                        ];
                        $listRecordNode = ListRecordsNode::createNodeFrom($repoItemSummary, $channel, $protocol->Prefix, true);
                        $fakeNode = new SimpleXMLElement('<Fake></Fake>');
                        $listRecordNode->addTo($fakeNode); //when adding, it'll cache
                    }
                    elseif($protocol->SystemKey == 'JSON:API'){
                        $descriptionForDataObject = new ExternalRepoItemChannelJsonApiDescription($channel);
                        $descriptionForDataObject->getCache($repoItem);
                        $dataDescription = [];
                        $dataDescription[JsonApi::TAG_ATTRIBUTES] = $descriptionForDataObject->describeAttributesOfDataObject($repoItem);
                        $dataDescription[JsonApi::TAG_TYPE] = DataObjectJsonApiEncoder::describeTypeOfDataObject($descriptionForDataObject);
                        if ($metaInformation = $descriptionForDataObject->describeMetaOfDataObject($repoItem)) {
                            $dataDescription[JsonApi::TAG_META] = $metaInformation;
                        }
                        $dataDescription[JsonApi::TAG_ID] = DataObjectJsonApiEncoder::describeIdOfDataObject($repoItem);
                        $descriptionForDataObject->cache($repoItem, $dataDescription);
                    }
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
                $cachedRecords = Cache_RecordNode::get()->filter(['PersonID' => $person->ID, 'ProtocolID' => $protocol->ID, 'ProtocolVersion:LessThan' => $protocol->Version, 'ChannelID:not' => 0]);
                foreach($cachedRecords as $cachedRecord) {
                    $channel = $cachedRecord->Channel;

                    $descriptionForDataObject = new ExternalPersonJsonApiDescription($channel);
                    $descriptionForDataObject->getCache($person);
                    $dataDescription = [];
                    $dataDescription[JsonApi::TAG_ATTRIBUTES] = $descriptionForDataObject->describeAttributesOfDataObject($person);
                    $dataDescription[JsonApi::TAG_TYPE] = DataObjectJsonApiEncoder::describeTypeOfDataObject($descriptionForDataObject);
                    if ($metaInformation = $descriptionForDataObject->describeMetaOfDataObject($person)) {
                        $dataDescription[JsonApi::TAG_META] = $metaInformation;
                    }
                    $dataDescription[JsonApi::TAG_ID] = DataObjectJsonApiEncoder::describeIdOfDataObject($person);
                    $descriptionForDataObject->cache($person, $dataDescription);

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