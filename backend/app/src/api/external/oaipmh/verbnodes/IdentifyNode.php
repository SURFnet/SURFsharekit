<?php

namespace SurfSharekit\Api;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\Security\DefaultAdminService;
use SimpleXMLElement;

class IdentifyNode extends OAIPMHVerbNode {
    static function addTo(SimpleXMLElement $node, HttpRequest $request) {
        $identifyNode = $node->addChild('Identify');
        //Must be included
        $identifyNode->addChild('repositoryName', 'SurfWorks OAI PMH Repository Version 1');
        $identifyNode->addChild('baseURL', Environment::getEnv('SS_BASE_URL') . '/api/oaipmh/v1');
        $identifyNode->addChild('protocolVersion', '2.0');
        $identifyNode->addChild('adminEmail', (new DefaultAdminService())->findOrCreateDefaultAdmin()->Email);
        $identifyNode->addChild('earliestDatestamp', date('Y-m-d\TH:i:s.Z\Z', 0)); //TODO change
        $identifyNode->addChild('deletedRecord', 'transient'); //[no, persistent, transient]
        $identifyNode->addChild('granularity', 'YYYY-MM-DDThh:mm:ssZ'); //[YYYY-MM-DDThh:mm:ssZ or YYYY-MM-DD]
    }

}