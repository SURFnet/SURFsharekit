<?php

namespace SurfSharekit\Api;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Channel;

class OaipmhChannelApiController extends OaipmhApiController {

    private static $url_handlers = [
        'GET $Action' => 'getOaipmhRequest',
        'POST $Action' => 'getOaipmhRequest'
    ];

    private static $allowed_actions = [
        'getOaipmhRequest'
    ];

    public function getOaipmhRequest() {
        set_time_limit(600);
        $request = $this->getRequest();
        $rootNode = $this->getRootNode();

        //handle oaipmh
        try {
            $channel = $request->param('channel');
            if (is_null($channel)) {
                throw new BadChannelException();
            }
            $this->channel = Channel::get()->filter(['slug' => $channel])->first();
            if (is_null($this->channel)) {
                throw new BadChannelException();
            }
            if (!$this->canAccess()) {
                throw new ChannelNotAllowedException();
            }
        } catch (BadChannelException $e) {
            $this->addErrorNode($rootNode, 'badChannel', $e->getMessage());
            $this->getResponse()->addHeader('content-type', 'text/xml');
            $this->getResponse()->setStatusCode(200);
            return $rootNode->asXML();
        } catch (ChannelNotAllowedException $e) {
            $this->addErrorNode($rootNode, 'channelNotAllowed', $e->getMessage());
            $this->getResponse()->addHeader('content-type', 'text/xml');
            $this->getResponse()->setStatusCode(200);
            return $rootNode->asXML();
        }
        return parent::getOaipmhRequest();
    }

    private function canAccess() {
        $currentMember = Security::getCurrentUser();
        if ($currentMember && $this->channel->Members()->filter(['ID' => $currentMember->ID])->first()) {
            return true;
        } elseif ($currentMember->isDefaultAdmin()) {
            return true;
        }
        return false;
    }

    protected function setUserFromRequest(HTTPRequest $request) {
        //handle authentication and such
        try {
            $channel = $request->param('channel');
            if (is_null($channel)) {
                throw new BadChannelException();
            }
            $this->channel = Channel::get()->filter(['slug' => $channel])->first();
            if (is_null($this->channel)) {
                throw new BadChannelException();
            }
        } catch (BadChannelException $e) {
            $rootNode = $this->getRootNode();
            $this->addErrorNode($rootNode, 'badChannel', $e->getMessage());
            $this->getResponse()->addHeader('content-type', 'text/xml');
            $this->getResponse()->setStatusCode(200);
            $this->getResponse()->setBody($rootNode->asXML());
        }

        if ($this->channel->SkipAPIKeyValidation) {
            /** @var Member $currentMember */
            $currentMember = $this->channel->Members()->first();
            Security::setCurrentUser($currentMember);
        } else {
            parent::setUserFromRequest($request);
        }
    }
}