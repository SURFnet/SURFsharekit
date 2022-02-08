<?php

namespace SurfSharekit\Api;

use SilverStripe\ORM\ValidationException;
use SimpleXMLElement;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Helper\XMLHelper;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\ProtocolNode;
use SurfSharekit\Models\RepoItem;
use UuidExtension;
use function Aws\boolean_value;

class ListRecordsNode extends RepoItemDescribingNode {
    static function createNodeFrom($repoItemSummary, $channel, $metadataPrefix, $purgeCache = false) {
        Logger::debugLog($repoItemSummary);
        $partOfChannel = boolean_value($repoItemSummary['PartOfChannel']);
        $isCached = boolean_value($repoItemSummary['Cached']);
        $headerNode = new IdentifierNode($repoItemSummary['Uuid'], $repoItemSummary['LastEdited'], $repoItemSummary['InstituteUuid'], $isCached && !$partOfChannel);
        $protocol = Protocol::get()->filter(['Prefix' => $metadataPrefix])->filter(['SystemKey' => 'OAI-PMH'])->first();
        $repoItem = UuidExtension::getByUuid(RepoItem::class, $repoItemSummary['Uuid']);
        return new RecordNode($headerNode, $protocol->ProtocolNodes()->filter('ParentNodeID', 0)->toArray(), $repoItem, $channel, $protocol, $purgeCache, $isCached && !$partOfChannel);
    }

    static function getNodeName() {
        return 'ListRecords';
    }
}

class RecordNode {
    private $headerNode;
    /** @var ProtocolNode[] $protocolRootNodes */
    private $protocolRootNodes;
    private $repoItem;
    /** @var Protocol $protocol */
    private $protocol;
    private $isDeleted;
    private $purgeCache = false;
    private $channel;

    public function __construct(IdentifierNode $headerNode, array $protocolRootNodes, RepoItem $repoItem, $channel, $protocol, $purgeCache = false, $isDeleted = false) {
        $this->headerNode = $headerNode;
        $this->protocolRootNodes = $protocolRootNodes;
        $this->repoItem = $repoItem;
        $this->channel = $channel;
        $this->protocol = $protocol;
        $this->purgeCache = $purgeCache;
        $this->isDeleted = $isDeleted;

        //add actual metadata information to getRecord node
    }

    function addTo(SimpleXMLElement $node) {
        Logger::debugLog("Add to : " . $this->repoItem->Uuid . ' : purge=' . $this->purgeCache);
        // use cache if not purged (by job)
        $cachedNode = Cache_RecordNode::get()->where(['Endpoint' => 'OAI', 'ProtocolID' => $this->protocol->ID, 'RepoItemID' => $this->repoItem->ID, 'CachedLastEdited' => $this->repoItem->LastEdited]);
        if ($this->channel) {
            $cachedNode = $cachedNode->filter('ChannelID', $this->channel->ID);
        }
        $cachedNode = $cachedNode->first();

        if (!$this->purgeCache && !$this->isDeleted) {
            if ($cachedNode && $cachedNode->exists()) {
                XMLHelper::simplexml_import_xml($node, $cachedNode->getField('Data'));
                return;
            }
        }

        $recordNode = $node->addChild('record');
        $this->headerNode->addTo($recordNode);
        if (!$this->isDeleted) {
            $metadataNode = $recordNode->addChild('metadata');
            /** @var ProtocolNode $protocolRootNode */
            foreach ($this->protocolRootNodes as $protocolRootNode) {
                $protocolRootNode->addTo($this->repoItem, $metadataNode);
            }
        } else {
            return;
        }
        $cachedNode = Cache_RecordNode::get()->where(['Endpoint' => 'OAI', 'ProtocolID' => $this->protocol->ID, 'RepoItemID' => $this->repoItem->ID]);
        if ($this->channel) {
            $cachedNode = $cachedNode->filter('ChannelID', $this->channel->ID);
        }
        $cachedNode = $cachedNode->first();
        if (!$cachedNode || !$cachedNode->exists()) {
            $cachedNode = Cache_RecordNode::create();
            $cachedNode->setField('Endpoint', 'OAI');
            $cachedNode->setField('RepoItemID', $this->repoItem->ID);
            $cachedNode->setField('ProtocolID', $this->protocol->ID);
            if ($this->channel) {
                $cachedNode->setField('ChannelID', $this->channel->ID);
            }
        }
        $cachedNode->setField('Data', (string)$recordNode->asXML());
        $cachedNode->setField('ProtocolVersion', $this->protocol->Version);
        $cachedNode->setField('CachedLastEdited', $this->repoItem->LastEdited);
        try {
            $cachedNode->write();
        } catch (ValidationException $e) {
            Logger::debugLog($e->getMessage());
        }
    }

    function addToSru(SimpleXMLElement $node) {
        Logger::debugLog("Add to SRU : " . $this->repoItem->Uuid . ' : purge=' . $this->purgeCache);
        if (!$this->purgeCache) {
            $cachedNode = Cache_RecordNode::get()->where(['Endpoint' => 'SRU', 'ProtocolID' => $this->protocol->ID, 'RepoItemID' => ($this->repoItem->ID), 'CachedLastEdited' => $this->repoItem->LastEdited])->first();
            if ($cachedNode) {
                XMLHelper::simplexml_import_xml($node, $cachedNode->getField('Data'));
                return;
            }
        }

        $recordNode = $node->addChild('srw:record', '', 'http://www.loc.gov/zing/srw/');
        $recordNode->addAttribute('_:xmlns:srw', 'http://www.loc.gov/zing/srw/');
        $recordNode->addAttribute('_:xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        unset($node->attributes('_:xmlns:dc', TRUE)[0]);
        $this->headerNode->addToSru($recordNode);
        $metadataNode = $recordNode->addChild('srw:recordData', '', 'http://www.loc.gov/zing/srw/');
        /** @var ProtocolNode $protocolRootNode */
        //$didlNode = $metadataNode->addChild('didl:DIDL', '', $this->protocol->NameSpaceURI);
        foreach ($this->protocolRootNodes as $protocolRootNode) {
            $protocolRootNode->addTo($this->repoItem, $metadataNode);
        }
        $cachedNode = Cache_RecordNode::get()->where(['Endpoint' => 'SRU', 'ProtocolID' => $this->protocol->ID, 'RepoItemID' => ($this->repoItem->ID)])->first();
        if (is_null($cachedNode)) {
            $cachedNode = Cache_RecordNode::create();
            $cachedNode->setField('Endpoint', 'SRU');
            $cachedNode->setField('RepoItemID', $this->repoItem->ID);
            $cachedNode->setField('ProtocolID', $this->protocol->ID);
        }

        $cachedNode->setField('Data', (string)$recordNode->asXML());
        $cachedNode->setField('ProtocolVersion', $this->protocol->Version);
        $cachedNode->setField('CachedLastEdited', $this->repoItem->LastEdited);
        try {
            $cachedNode->write();
        } catch (ValidationException $e) {
            Logger::debugLog($e->getMessage());
        }
    }
}