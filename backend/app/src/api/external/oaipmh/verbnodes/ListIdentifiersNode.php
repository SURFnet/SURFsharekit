<?php

namespace SurfSharekit\Api;

use SimpleXMLElement;
use SurfSharekit\Models\Helper\DateHelper;
use function Aws\boolean_value;

class ListIdentifiersNode extends RepoItemDescribingNode {
    static function createNodeFrom($repoItemSummary, $channel, $metadataPrefix, $purgeCache = false) {
        $partOfChannel = boolean_value($repoItemSummary['PartOfChannel']);
        $isCached = boolean_value($repoItemSummary['Cached']);
        return new IdentifierNode($repoItemSummary['Uuid'], $repoItemSummary['LastEdited'], $repoItemSummary['InstituteUuid'], !$partOfChannel && $isCached);
    }

    static function getNodeName() {
        return 'ListIdentifiers';
    }
}

class IdentifierNode {
    private $localID;
    private $createdDateTimeStamp;
    private $spec;
    private $isDeleted;

    public function __construct($localID, $createdDateTimeStamp, $spec, $isDeleted = false) {
        $this->localID = $localID;
        $this->createdDateTimeStamp = $createdDateTimeStamp;
        $this->spec = $spec;
        $this->isDeleted = $isDeleted;
    }

    function addTo(SimpleXMLElement $node) {
        $childNode = $node->addChild('header');
        if ($this->isDeleted) {
            $childNode->addAttribute('status', 'deleted');
        }
        $childNode->addChild('identifier', OaipmhApiController::$OAI_PREFIX . ':' . OaipmhApiController::$OAI_NAMESPACE . ':' . $this->localID);
        $childNode->addChild('datestamp', DateHelper::iso8601zFromString($this->createdDateTimeStamp)); //use created date
        if ($this->spec) {
            $childNode->addChild('setSpec', $this->spec);
        }
    }

    function addToSru(SimpleXMLElement $node) {
        $node->addChild('srw:recordSchema', 'didl_mods', 'http://www.loc.gov/zing/srw/');
        $node->addChild('srw:recordPacking', 'xml', 'http://www.loc.gov/zing/srw/');
        $node->addChild('srw:recordIdentifier', $this->localID, 'http://www.loc.gov/zing/srw/');
    }
}