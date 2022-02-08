<?php

namespace SurfSharekit\Api;

use SilverStripe\Control\HTTPRequest;
use SimpleXMLElement;
use SurfSharekit\Models\Channel;
use SurfSharekit\Models\Helper\XMLHelper;
use SurfSharekit\Models\Institute;

class ListSetsNode extends OAIPMHVerbNode {
    static function addTo(SimpleXMLElement $node, HttpRequest $request, Channel $channel = null) {
        $resumptionToken = static::getResumptionToken($request);
        $limit = 100;
        $offset = 0;
        if ($resumptionToken) {
            if (static::isValidResumptionToken($resumptionToken)) {
                $limit = self::getSizeFromResumptionToken($resumptionToken);
                $offset = self::getPageFromResumptionToken($resumptionToken) * $limit;
            } else {
                throw new BadResumptionTokenException('Repository received an incorrect resumption token');
            }
        }

        $listSetsNode = $node->addChild('ListSets');

        foreach (static::getSetNodes($request, $limit, $offset, $channel) as $setNode) {
            $setNode->addTo($listSetsNode);
        }
        if ($channel && $channel->exists() && $channel->Institutes()->count() > 0) {
            $totalCount = Institute::get()->byIDs($channel->Institutes()->column('ID'))->count();
        } else {
            $totalCount = Institute::get()->where('InstituteID = 0')->count();
        }

        $topCountCurrentQuery = ($limit + $offset);
        if ($totalCount > $topCountCurrentQuery) {
            $nextResumptionToken = ";;$limit;" . (self::getPageFromResumptionToken($resumptionToken) + 1) . ";;";
            $resumptionTokenNode = $node->addChild('resumptionToken', $nextResumptionToken);
            $resumptionTokenNode->addAttribute('cursor', $topCountCurrentQuery);
            $resumptionTokenNode->addAttribute('completeListSize', $totalCount);
        }
    }

    private static function getSetNodes(HTTPRequest $request, $limit, $offset, Channel $channel = null): array {
        $instituteToSet = function ($institute) {
            return new SetNode($institute->Uuid, $institute->Title);
        };

        if ($channel && $channel->exists() && $channel->Institutes()->filter('IsRemoved', false)->count() > 0) {
            return array_map($instituteToSet, Institute::get()->byIDs($channel->Institutes()->filter('IsRemoved', false)->column('ID'))->limit($limit, $offset)->toArray());
        }
        return array_map($instituteToSet, Institute::get()->where('InstituteID = 0')->filter('IsRemoved', false)->limit($limit, $offset)->toArray());
    }
}

class SetNode {
    private $spec;
    private $name;

    public function __construct($spec, $name) {
        $this->spec = $spec;
        $this->name = $name;
    }

    function addTo(SimpleXMLElement $node) {
        $childNode = $node->addChild('set');
        $childNode->addChild('setSpec', $this->spec);
        $childNode->addChild('setName', XMLHelper::encodeXMLString($this->name));
//        $childNode->addChild('setDescription', "This set contains all RepoItems uploaded on the scope of ".htmlspecialchars($this->name));
    }

}