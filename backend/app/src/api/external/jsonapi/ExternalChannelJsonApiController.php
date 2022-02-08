<?php

namespace SurfSharekit\Api;

use Exception;
use ExternalRepoItemChannelJsonApiDescription;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SurfSharekit\Models\Channel;
use SurfSharekit\Models\RepoItem;

class ExternalChannelJsonApiController extends ExternalJsonApiController {

    private static $url_handlers = [
        'GET $channel/$Action/$ID/$Relations/$RelationName' => 'getJsonApiRequest'
    ];

    private static $allowed_actions = [
        'getJsonApiRequest'
    ];

    protected function getApiURLSuffix() {
        return '/api/jsonapi/channel/v1/' . $this->channel->Slug ;
    }


    /**
     * @return mixed
     * returns a map of DataObjects to their Respective @see DataObjectJsonApiDescription
     */
    protected function getClassToDescriptionMap() {
        return [RepoItem::class => new ExternalRepoItemChannelJsonApiDescription($this->channel)];
    }

    public function getJsonApiRequest() {
        set_time_limit(600);
        $request = $this->getRequest();

        //handle json
        try {
            $channel = $request->param('channel');
            if (is_null($channel)) {
                throw new BadChannelException();
            }
            $this->channel = Channel::get()->filter(['Slug' => $channel])->first();
            if (is_null($this->channel)) {
                throw new BadChannelException();
            }
            else{
                $this->classToDescriptionMap = $this->getClassToDescriptionMap();
            }
            if (!$this->canAccess()) {
                throw new ChannelNotAllowedException();
            }
        } catch (BadChannelException $e) {
            $this->getResponse()->setStatusCode(400 );
            return 'badChannel';
        } catch (ChannelNotAllowedException $e) {
            $this->getResponse()->setStatusCode(405 );
            return 'channelNotAllowed';
        }
        // set defaults for this controller
        $this->pageSize = 10;
        $this->pageNumber = 1;
        return parent::getJsonApiRequest();
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
            $this->getResponse()->setStatusCode(400 );
            return 'badChannel';
        }

        if ($this->channel->SkipAPIKeyValidation) {
            /** @var Member $currentMember */
            $currentMember = $this->channel->Members()->first();
            Security::setCurrentUser($currentMember);
        } else {
            parent::setUserFromRequest($request);
        }
    }

    /**
     * @param $objectClass
     * @return mixed
     * Called when the use request all objects of a certain type
     */
    function getDataList($objectClass) {
        if ($this->sparseFields){
            throw new Exception("Sparse fields not supported");
        }
        return $objectClass::get();
    }
}