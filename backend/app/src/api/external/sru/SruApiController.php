<?php

namespace SurfSharekit\Api;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;
use SimpleXMLElement;
use SurfSharekit\Models\Channel;

class SruApiController extends LoginProtectedApiController {
    const SRU_SUPPORTED_VERSIONS = ['1.2'];
    const SRU_SUPPORTED_OPERATIONS = ['searchRetrieve'];
    const SRU_SUPPORTED_OPERATORS = ['=', '<>', ' any '];

    /** @var Channel $channel */
    protected $channel = null;

    private static $url_handlers = [
        'GET $Action' => 'getSruRequest',
        'POST $Action' => 'getSruRequest'
    ];

    private static $allowed_actions = [
        'getSruRequest'
    ];

    public function getSruRequest() {
        $request = $this->getRequest();

        $rootNode = $this->getRootNode();

        try {
            $this->addRequestNode($rootNode, $request);

            ListRecordsNode::addToSru($rootNode, $request, $this->channel);

        } catch (SRUVersionNotSupportedException $e) {
            $this->addErrorNode($rootNode, 'Unsupported Version', $e->getMessage());
        } catch (SRUOperationNotSupportedException $e) {
            $this->addErrorNode($rootNode, 'Unsupported Version', $e->getMessage());
        }
        $this->getResponse()->addHeader('content-type', 'text/xml');
        $this->getResponse()->setStatusCode(200);
        return $rootNode->asXML();
    }

    protected static function getRootNode(): SimpleXMLElement {
        $node = new SimpleXMLElement('<srw:searchRetrieveResponse xmlns:srw="http://www.loc.gov/zing/srw/" xmlns:diag="http://www.loc.gov/zing/srw/diagnostic/" xmlns:xcql="http://www.loc.gov/zing/cql/xcql/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meresco_srw="http://meresco.org/namespace/srw#"></srw:searchRetrieveResponse>' );
        return $node;
    }

    private static function addRequestNode(SimpleXMLElement $node, HTTPRequest $request) {

        $operation = $request->getVar('operation') ?: $request->postVar('operation');
        $version = $request->getVar('version') ?: $request->postVar('version');
        if(is_null($version)){
            $version = SruApiController::SRU_SUPPORTED_VERSIONS[count(SruApiController::SRU_SUPPORTED_VERSIONS)-1];
        }
        if(!in_array($version, SruApiController::SRU_SUPPORTED_VERSIONS)){
            throw new SRUVersionNotSupportedException($version);
        }

        if(!in_array($operation, SruApiController::SRU_SUPPORTED_OPERATIONS)){
            throw new SRUOperationNotSupportedException($operation);
        }

        $node->addChild('srw:version', $version);
    }

    protected static function addErrorNode(SimpleXMLElement $node, string $error, string $message) {
        $errorNode = $node->addChild('srw:diagnostics');
        $diagnosticNode = $errorNode->addChild('diagnostic');
        $diagnosticNode->addAttribute('xmlns', 'http://www.loc.gov/zing/srw/diagnostic/');
        $diagnosticNode->addChild('uri', 'info://srw/diagnostics/1/4');
        $diagnosticNode->addChild('details', $message);
        $diagnosticNode->addChild('message', $error);
    }

    public static function getRepoItemIDFromIdentifier($identifier) {
        return $identifier;
    }

    static function addSimpleXMLElement(SimpleXMLElement $parent, SimpleXMLElement $child) {
        $toDom = dom_import_simplexml($parent);
        $fromDom = dom_import_simplexml($child);
        $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
    }

    protected function userHasValidLogin(Member $member) {
        if ($member->isDefaultAdmin()) {
            return true;
        } else if (ApiMemberExtension::hasApiUserRole($member)) {
            return true;
        }
        return false;
    }

    protected function getInvalidLoginMessage() {
        return 'Member is not an API user';
    }
}