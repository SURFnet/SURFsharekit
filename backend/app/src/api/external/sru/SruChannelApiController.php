<?php

namespace SurfSharekit\Api;

use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Channel;

class SruChannelApiController extends SruApiController {

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
            $channel = $request->param('channel');
            if(is_null($channel)){
                throw new BadChannelException();
            }
            $this->channel = Channel::get()->filter(['slug'=>$channel])->first();
            if(is_null($this->channel)){
                throw new BadChannelException();
            }
        } catch (BadChannelException $e) {
            $this->addErrorNode($rootNode, 'badChannel', $e->getMessage());
            $this->getResponse()->addHeader('content-type', 'text/xml');
            $this->getResponse()->setStatusCode(200);
            return $rootNode->asXML();
        }
        
        if($this->channel->SkipAPIKeyValidation){
            /** @var Member $currentMember */
            $currentMember = $this->channel->Members()->first();
            if (is_null($currentMember)) {
                $this->addErrorNode($rootNode, 'noMember', 'No valid API member found for this channel');
                $this->getResponse()->addHeader('content-type', 'text/xml');
                $this->getResponse()->setStatusCode(401);
                return $rootNode->asXML();
            }
            $hasValidLogin = $this->userHasValidLogin($currentMember);

            if (!$hasValidLogin) {
                $this->addErrorNode($rootNode, 'invalidLogin', $this->getInvalidLoginMessage());
                $this->getResponse()->addHeader('content-type', 'text/xml');
                $this->getResponse()->setStatusCode(401);
                return $rootNode->asXML();
            }
            Security::setCurrentUser($currentMember);
        }

        try {
            $channel = $request->param('channel');
            if(is_null($channel)){
                throw new BadChannelException();
            }
            $this->channel = Channel::get()->filter(['slug'=>$channel])->first();
            if(is_null($this->channel)){
                throw new BadChannelException();
            }
            if(!$this->canAccess()){
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
        return parent::getSruRequest();
    }

    private function canAccess(){
        $currentMember = Security::getCurrentUser();
        if($currentMember && $this->channel->Members()->filter(['ID'=>$currentMember->ID])->first()){
            return true;
        }
        return false;
    }
}