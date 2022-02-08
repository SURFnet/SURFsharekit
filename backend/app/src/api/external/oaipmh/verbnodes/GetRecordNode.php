<?php

namespace SurfSharekit\Api;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ValidationException;
use SimpleXMLElement;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Helper\XMLHelper;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\RepoItem;
use UuidExtension;

class GetRecordNode extends OAIPMHVerbNode {
    static function addTo(SimpleXMLElement $node, HttpRequest $request, $channel = null) {
        $identifier = $request->getVar('identifier') ?: $request->postVar('identifier');
        if (!$identifier) {
            throw new BadArgumentException('Missing record identifier');
        }
        $metadataPrefix = $request->getVar('metadataPrefix') ?: $request->postVar('metadataPrefix');
        if (!$metadataPrefix) {
            throw new BadArgumentException('Missing metadataPrefix to describe record in');
        }
        $purge = $request->getVar('purge') ?: $request->postVar('purge');
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
        $repoItemUuid = OaipmhApiController::getRepoItemIDFromIdentifier($identifier);

        $repoItemIsAvailable = DB::prepared_query("SELECT * FROM SurfSharekit_RepoItem 
                        $channelJoin
                        WHERE SurfSharekit_RepoItem.IsRemoved = 0 AND SurfSharekit_RepoItem.IsArchived = 0 AND SurfSharekit_RepoItem.Uuid = ? $channelFilter", [$repoItemUuid])->numRecords();

        $repoItem = UuidExtension::getByUuid(RepoItem::class, $repoItemUuid);

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
            return;
        }


        $protocol = Protocol::get()->filter(['Prefix' => $metadataPrefix])->filter(['SystemKey' => 'OAI-PMH'])->first();
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
            if ($channel) {
                $cachedNode->setField('ChannelID', $channel->ID);
            }
        }
        $cachedNode->setField('Data', (string)$recordNode->asXML());
        $cachedNode->setField('ProtocolVersion', $protocol->Version);
        $cachedNode->setField('CachedLastEdited', $repoItem->LastEdited);
        try {
            $cachedNode->write();
        } catch (ValidationException $e) {
            Logger::debugLog($e->getMessage());
        }
    }

    static function addTosru(SimpleXMLElement $node, HttpRequest $request) {
        $identifier = $request->getVar('identifier') ?: $request->postVar('identifier');
        if (!$identifier) {
            throw new BadArgumentException('Missing record identifier');
        }
        $metadataPrefix = $request->getVar('metadataPrefix') ?: $request->postVar('metadataPrefix');
        if (!$metadataPrefix) {
            throw new BadArgumentException('Missing metadataPrefix to describe record in');
        }

        $repoItemUuid = OaipmhApiController::getRepoItemIDFromIdentifier($identifier);
        $repoItem = UuidExtension::getByUuid(RepoItem::class, $repoItemUuid);
        if (!$repoItem || !$repoItem->exists()) {
            throw new IdDoesNotExistException();
        }

        $allProtocolsForRepoItem = DB::query(
            "SELECT DISTINCT SurfSharekit_Protocol.ID AS ProtocolID, SurfSharekit_Protocol.Prefix AS ProtocolPrefix FROM SurfSharekit_RepoItem
            LEFT JOIN SurfSharekit_ProtocolNode ON SurfSharekit_ProtocolNode.MetaFieldID = SurfSharekit_TemplateMetaField.MetaFieldID
            LEFT JOIN SurfSharekit_Protocol ON SurfSharekit_Protocol.ID = SurfSharekit_ProtocolNode.ProtocolID
            WHERE SurfSharekit_RepoItem.Uuid = '$repoItemUuid'
            AND SurfSharekit_Protocol.SystemKey = 'OAI-PMH'");

        $allProtocolPrefixesForRepoItem = $allProtocolsForRepoItem->column('ProtocolPrefix');

        if (count($allProtocolPrefixesForRepoItem) == 0) {
            throw new NoMetadataFormatsException('The requested item has no metadata descriptions available');
        }

        if (!in_array($metadataPrefix, $allProtocolPrefixesForRepoItem)) {
            throw new CannotDisseminateFormatException("$metadataPrefix is not a valid prefix");
        }

        $getRecordNode = $node->addChild('GetRecord');
        //create recordnode for repoitem
        $recordNode = $getRecordNode->addChild('srw:record');
        //Add identifier header to getRecord node
        $headerNode = new IdentifierNode($repoItem->Uuid, $repoItem->LastEdited, $repoItem->Institute->Uuid);
        $headerNode->addToSru($recordNode);
        $metadataNode = $recordNode->addChild('srw:recordData');

        //add actual metadata information to getRecord node
        $protocol = Protocol::get()->filter(['Prefix' => $metadataPrefix])->filter(['SystemKey' => 'OAI-PMH'])->first();
        foreach ($protocol->ProtocolNodes()->filter('ParentNodeID', 0) as $protocolRootNode) {
            $protocolRootNode->addTo($repoItem, $metadataNode);
        }
    }
}