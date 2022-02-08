<?php

namespace SurfSharekit\Api;

use Ramsey\Uuid\Uuid;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;
use SimpleXMLElement;
use SurfSharekit\Models\Channel;
use SurfSharekit\Models\Helper\DateHelper;
use SurfSharekit\Models\Helper\XMLHelper;

class OaipmhApiController extends LoginProtectedApiController {
    public static $OAI_PMH_VERBS = ['Identify', 'ListSets', 'ListMetadataFormats', 'ListIdentifiers', 'GetRecord', 'ListRecords'];

    public static $OAI_PREFIX = 'oai';
    public static $OAI_NAMESPACE = 'surfsharekit.nl';

    /** @var Channel $channel */
    protected $channel = null;

    private static $url_handlers = [
        'GET $Action' => 'getOaipmhRequest',
        'POST $Action' => 'getOaipmhRequest'
    ];

    private static $allowed_actions = [
        'getOaipmhRequest'
    ];

    public function getOaipmhRequest() {
        $request = $this->getRequest();

        $rootNode = $this->getRootNode();
        $this->addResponseDateNode($rootNode);
        $this->addRequestNode($rootNode, $request);

        try {
            $verb = $request->getVar('verb') ?: $request->postVar('verb');
            switch ($verb) {
                case 'Identify':
                    IdentifyNode::addTo($rootNode, $request);
                    break;
                case 'ListMetadataFormats':
                    ListMetadataFormatsNode::addTo($rootNode, $request, $this->channel);
                    break;
                case 'ListSets':
                    ListSetsNode::addTo($rootNode, $request, $this->channel);
                    break;
                case 'ListIdentifiers':
                    ListIdentifiersNode::addTo($rootNode, $request, $this->channel);
                    break;
                case 'ListRecords':
                    ListRecordsNode::addTo($rootNode, $request, $this->channel);
                    break;
                case 'GetRecord':
                    GetRecordNode::addTo($rootNode, $request, $this->channel);
                    break;
                default:
                    $this->addErrorNode($rootNode, 'badVerb', 'Illegal OAI verb');
                    break;
            }
        } catch (BadArgumentException $e) {
            $this->addErrorNode($rootNode, 'badArgument', $e->getMessage());
        } catch (IdDoesNotExistException $e) {
            $identifier = $request->getVar('identifier') ?: $request->postVar('identifier');
            $this->addErrorNode($rootNode, 'idDoesNotExist', "$identifier is not an existing identifier");
        } catch (NoMetadataFormatsException $e) {
            $this->addErrorNode($rootNode, 'noMetadataFormats', $e->getMessage());
        } catch (CannotDisseminateFormatException $e) {
            $this->addErrorNode($rootNode, 'cannotDisseminateFormat', $e->getMessage());
        } catch (NoRecordsMatchException $e) {
            $this->addErrorNode($rootNode, 'noRecordsMatch', $e->getMessage());
        } catch (BadResumptionTokenException $e) {
            $this->addErrorNode($rootNode, 'badResumptionToken', $e->getMessage());
        }
        $this->getResponse()->addHeader('content-type', 'text/xml');
        $this->getResponse()->setStatusCode(200);
        return $rootNode->asXML();
    }

    protected static function getRootNode(): SimpleXMLElement {
        $node = new SimpleXMLElement('<OAI-PMH></OAI-PMH>');
        $node->addAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/');
        $node->addAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd', 'http://www.w3.org/2001/XMLSchema-instance');
        return $node;
    }

    private static function addResponseDateNode(SimpleXMLElement $node) {
        $responseDate = DateHelper::iso8601zFromString(date('Y-m-d H:i:s'));
        $node->addChild('responseDate', $responseDate);
    }

    private static function addRequestNode(SimpleXMLElement $node, HTTPRequest $request) {
        $requestNode = $node->addChild('request', XMLHelper::encodeXMLString(Director::absoluteURL($request->getURL())));

        $verb = $request->getVar('verb') ?: $request->postVar('verb');
        if (in_array($verb, static::$OAI_PMH_VERBS)) {
            $requestNode->addAttribute('verb', $verb);
        } else {
            return null;
        }

        $addParameterToRequestNode = function ($parameterKey) use ($request, $requestNode) {
            $parameterValue = $request->getVar($parameterKey) ?: $request->postVar($parameterKey);
            if ($parameterValue) {
                $requestNode->addAttribute($parameterKey, $parameterValue);
            }
        };

        $addParameterToRequestNode('identifier');
        $addParameterToRequestNode('metadataPrefix');
        $addParameterToRequestNode('set');
        $addParameterToRequestNode('from');
        $addParameterToRequestNode('until');
    }

    protected static function addErrorNode(SimpleXMLElement $node, string $error, string $message) {
        $errorNode = $node->addChild('error', $message);
        $errorNode->addAttribute('code', $error);
    }

    public static function getRepoItemIDFromIdentifier($identifier) {
        $splitIdentifier = explode(':', $identifier);
        if (count($splitIdentifier) < 3) {
            throw new BadArgumentException("identifier has no valid format");
        }
        if ($splitIdentifier[0] != static::$OAI_PREFIX || $splitIdentifier[1] !== static::$OAI_NAMESPACE) {
            throw new BadArgumentException('identifier has incorrect namespace or prefix');
        }
        if (!Uuid::isValid($splitIdentifier[2])) {
            throw new BadArgumentException('identifier is not valid');
        }
        return $splitIdentifier[2];
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