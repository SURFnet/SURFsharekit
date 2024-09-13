<?php

namespace SurfSharekit\Api;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ValidationException;
use SimpleXMLElement;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\Channel;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Helper\XMLHelper;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\RepoItem;
use UuidExtension;

class GetRecordNode extends OAIPMHVerbNode {
    static function addTo(SimpleXMLElement $node, $request, $channel = null, $repoItem = null) {

        if(is_null($repoItem)) {
            $identifier = $request->getVar('identifier') ?: $request->postVar('identifier');
            if (!$identifier) {
                throw new BadArgumentException('Missing record identifier');
            }
            $metadataPrefix = $request->getVar('metadataPrefix') ?: $request->postVar('metadataPrefix');
            if (!$metadataPrefix) {
                throw new BadArgumentException('Missing metadataPrefix to describe record in');
            }
        } else {
            $metadataPrefix = $channel->Protocol()->Prefix;
        }

        if(is_null($repoItem)) {
            $purge = $request->getVar('purge') ?: $request->postVar('purge');
        } else {
            $purge = 0;
        }

        if ($purge) {
            $purge = intval($purge);
            set_time_limit(0); // increase time limit when purging
        } else {
            $purge = 0;
        }

        $channelFilterArray = static::getChannelFilter($channel);
        if (is_null($channelFilterArray)) {
            $channelFilter = '';
            $channelJoin = '';
        } else {
            $channelFilter = $channelFilterArray['channelFilterString'];
            $channelJoin = $channelFilterArray['channelJoinString'];
        }

        if(is_null($repoItem)) {
            $repoItemUuid = OaipmhApiController::getRepoItemIDFromIdentifier($identifier);
        } else {
            $repoItemUuid = $repoItem->Uuid;
        }

        $repoItemIsAvailable = DB::prepared_query("SELECT * FROM SurfSharekit_RepoItem 
                        $channelJoin
                        WHERE SurfSharekit_RepoItem.IsRemoved = 0 AND SurfSharekit_RepoItem.IsArchived = 0 AND SurfSharekit_RepoItem.Uuid = ? $channelFilter", [$repoItemUuid])->numRecords();

        if(is_null($repoItem)) {
            $repoItem = UuidExtension::getByUuid(RepoItem::class, $repoItemUuid);
        }

        if (!$repoItemIsAvailable) {
            //Item isn't added to this channel or inactive
            if ($repoItem && $repoItem->exists()) {
                $hasCache = Cache_RecordNode::get()->filter(['RepoItemID' => $repoItem->ID, 'ChannelID' => ($channel ? $channel->ID : -1)])->first();
                if(!$hasCache){
                    // never cached and removed
                    throw new IdDoesNotExistException();
                }
            }
            else{
                throw new IdDoesNotExistException();
            }
        }

        if ($channel && $channel->exists()) {
            $allProtocolsForRepoItem = Protocol::get()->filter('ID', $channel->ProtocolID);
        } else {
            $allProtocolsForRepoItem = Protocol::get()->filter('SystemKey', 'OAI-PMH');
        }
        $allProtocolPrefixesForRepoItem = $allProtocolsForRepoItem->column('Prefix');

        if (count($allProtocolPrefixesForRepoItem) == 0) {
            throw new NoMetadataFormatsException('The requested item has no metadata descriptions available');
        }

        if (!in_array($metadataPrefix, $allProtocolPrefixesForRepoItem)) {
            throw new CannotDisseminateFormatException("$metadataPrefix is not a valid prefix");
        }

        $getRecordNode = $node->addChild('GetRecord');

        $isDeleted = !$repoItemIsAvailable && $hasCache;


        //Add identifier header to getRecord node
        $headerNode = new IdentifierNode($repoItem->Uuid, $repoItem->LastEdited, $repoItem->Institute->Uuid, $isDeleted);

        if ($isDeleted) {
            //create recordnode for repoitem
            $recordNode = $getRecordNode->addChild('record');
            $headerNode->addTo($recordNode);
            $cachedNode = Cache_RecordNode::get()->filter(['RepoItemID' => $repoItem->ID, 'ChannelID' => ($channel ? $channel->ID : -1)])->first();
            if ($cachedNode) {
                $cachedNode->Deleted = true;
                $cachedNode->write();
            }
            return;
        }

        if ($channel && $channel->exists()) {
            $protocol = Protocol::get()->filter(['Prefix' => $metadataPrefix, 'ID' => $channel->ProtocolID, 'SystemKey' => 'OAI-PMH'])->first();
        } else {
            $protocol = Protocol::get()->filter(['Prefix' => $metadataPrefix, 'SystemKey' => 'OAI-PMH'])->first();
        }

        Logger::debugLog("Add to : " . $repoItem->Uuid . ' : purge=' . $purge);
        // use cache if not purged (by job)
        $cachedNode = Cache_RecordNode::get()->where(['Endpoint' => 'OAI', 'ProtocolID' => $protocol->ID, 'RepoItemID' => $repoItem->ID, 'CachedLastEdited' => $repoItem->LastEdited]);
        if ($channel) {
            $cachedNode = $cachedNode->filter('ChannelID', $channel->ID);
        }
        $cachedNode = $cachedNode->first();

        if (!$purge) {
            if ($cachedNode && $cachedNode->exists()) {
                XMLHelper::simplexml_import_xml($getRecordNode, $cachedNode->getField('Data'));
                return;
            }
        }

        //add actual metadata information to getRecord node
        $recordNode = $getRecordNode->addChild('record');
        $headerNode->addTo($recordNode);

        $metadataNode = $recordNode->addChild('metadata');

        foreach ($protocol->ProtocolNodes()->filter('ParentNodeID', 0) as $protocolRootNode) {
            $protocolRootNode->addTo($repoItem, $metadataNode);
        }
        $cachedNode = Cache_RecordNode::get()->where(['Endpoint' => 'OAI', 'ProtocolID' => $protocol->ID, 'RepoItemID' => $repoItem->ID]);
        if ($channel) {
            $cachedNode = $cachedNode->filter('ChannelID', $channel->ID);
        }
        $cachedNode = $cachedNode->first();
        if (!$cachedNode || !$cachedNode->exists()) {
            $cachedNode = Cache_RecordNode::create();
            $cachedNode->setField('Endpoint', 'OAI');
            $cachedNode->setField('RepoItemID', $repoItem->ID);
            $cachedNode->setField('ProtocolID', $protocol->ID);
            $cachedNode->setField('Deleted', false);
            $cachedNode->setField('DeleteWebhookSent', false);
            if ($channel) {
                $cachedNode->setField('ChannelID', $channel->ID);
            }
        }

        $cachedNode->setField('Data', (string)$recordNode->asXML());
        $cachedNode->setField('ProtocolVersion', $protocol->Version);
        $cachedNode->setField('CachedLastEdited', $repoItem->LastEdited);
        $cachedNode->setField('Deleted', false);
        $cachedNode->setField('DeleteWebhookSent', false);
        try {
            $cachedNode->write();
        } catch (ValidationException $e) {
            Logger::debugLog($e->getMessage());
        }
    }

    public static function repoItemPassesFilter(Channel $channel, RepoItem $repoItem): bool {
        if ($isXML = $channel->Protocol()->SystemKey == 'OAI-PMH') {
            $repoItemUuid = $repoItem->Uuid;

            $channelFilterArray = static::getChannelFilter($channel);
            if (is_null($channelFilterArray)) {
                $channelFilter = '';
                $channelJoin = '';
            } else {
                $channelFilter = $channelFilterArray['channelFilterString'];
                $channelJoin = $channelFilterArray['channelJoinString'];
            }

            $repoItemIsAvailable = DB::prepared_query("SELECT * FROM SurfSharekit_RepoItem 
                        $channelJoin
                        WHERE SurfSharekit_RepoItem.IsRemoved = 0 AND SurfSharekit_RepoItem.IsArchived = 0 AND SurfSharekit_RepoItem.Uuid = ? $channelFilter", [$repoItemUuid])->numRecords();

            if ($repoItemIsAvailable) {
                return true;
            }
        }

        return false;
    }
}