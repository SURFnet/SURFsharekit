<?php

namespace SurfSharekit\Tasks;

use DataObjectCSVFileEncoder;
use DataObjectJsonApiEncoder;
use Exception;
use ExternalPersonJsonApiDescription;
use ExternalRepoItemChannelJsonApiDescription;
use Psr\Container\NotFoundExceptionInterface;
use Ramsey\Uuid\Uuid;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SimpleXMLElement;
use SurfSharekit\Api\JsonApi;
use SurfSharekit\Api\ListRecordsNode;
use SurfSharekit\Models\Cache_RecordNode;
use SurfSharekit\Models\CacheClearRequest;
use SurfSharekit\Models\Channel;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\RepoItem;

class RefreshCacheTask extends BuildTask
{
    private $cacheClearRequest;
    private $taskId;
    private $type;

    private $protocol;
    private $channel;
    private $institute;

    private $itemCount = 0;
    private $itemsDone = 0;

    private $instituteLists = [];

    /**
     * This function takes the chosen filters of the CacheClearRequest and then filters the provided $list to
     * match records that contain all non-empty filters. Note that the DataList must already have Cache_RecordNode
     * joined to the result for this function to work correctly.
     *
     * @param DataList $list
     * @return DataList
     * @throws Exception|NotFoundExceptionInterface
     */
    private function applyCacheClearRequestFilter(DataList $list): DataList {
        $class = $list->dataClass();
        $dataClassSingleton = Injector::inst()->get($class);
        if (!($dataClassSingleton instanceof Cache_RecordNode) && !($dataClassSingleton instanceof RepoItem) && !($dataClassSingleton instanceof Member)) {
            throw new Exception("Provided a DataList of class $class, expected instance of Cache_RecordNode, RepoItem or Member");
        }

        if ($this->getProtocol()?->ID) {
            $list = $list
                ->where(["SurfSharekit_Cache_RecordNode.ProtocolID = ?" => $this->getProtocol()->ID])
                ->where(["SurfSharekit_Cache_RecordNode.ProtocolVersion <= ?" => $this->getProtocol()->Version]);
        }

        if ($this->getChannel()?->ID) {
            $list = $list->where(["SurfSharekit_Cache_RecordNode.ChannelID = ?" => $this->getChannel()->ID]);
        }

        // Can't filter cache records on institute
        if ($this->getInstitute()?->ID && !$dataClassSingleton instanceof Cache_RecordNode) {
            if ($dataClassSingleton instanceof Member) {
                $list = $list
                    ->innerJoin("Group_Members", "Group_Members.MemberID = SurfSharekit_Person.ID")
                    ->innerJoin("Group", "Group.ID = Group_Members.GroupID")
                    ->innerJoin("SurfSharekit_Institute", 'Group.InstituteID = SurfSharekit_Institute.ID');
            } else {
                $list = $list->innerJoin("SurfSharekit_Institute", 'SurfSharekit_RepoItem.InstituteID = SurfSharekit_Institute.ID');
            }
            $list = $list->where(['SurfSharekit_Institute.ID = ?' => $this->getInstitute()->ID]);
        }

        return $list;
    }

    private ?DataList $listOfRepoItems = null;
    private ?DataList $listOfPersons = null;
    private function getFilteredRepoList(): DataList {
        if ($this->listOfRepoItems) return $this->listOfRepoItems;

        $this->listOfRepoItems = RepoItem::get()
            ->innerJoin('SurfSharekit_Cache_RecordNode', 'SurfSharekit_Cache_RecordNode.RepoItemID = SurfSharekit_RepoItem.ID');
        $this->listOfRepoItems = $this->applyCacheClearRequestFilter($this->listOfRepoItems);
        return $this->listOfRepoItems;
    }

    private function getFilteredPersonList(): DataList {
        if ($this->listOfPersons) return $this->listOfPersons;

        $this->listOfPersons = Person::get()
            ->innerJoin('SurfSharekit_Cache_RecordNode', 'SurfSharekit_Cache_RecordNode.PersonID = SurfSharekit_Person.ID');
        $this->listOfPersons = $this->applyCacheClearRequestFilter($this->listOfPersons);
        return $this->listOfPersons;
    }

    public function run($request) {
        $this->setTaskId(Uuid::uuid4()->toString());
        set_time_limit(60 * 60 * 72);//72 hours
        ini_set('memory_limit', '2048M');
        if (null !== $cacheClearRequest = $this->getNextCacheClearRequest()) {
            try {
                $this->executeCacheRequest($cacheClearRequest);

                $cacheClearRequest->markStatusAs('Done');
            } catch (Exception $e) {
                Logger::errorLog($e->getMessage());
                Logger::errorLog("At {$e->getFile()}:{$e->getLine()}");
                $cacheClearRequest->onFail($e->getMessage());
            }
        }
    }

    private function executeCacheRequest(CacheClearRequest $cacheClearRequest) {
        $this->setCacheClearRequest($cacheClearRequest);
        $cacheClearRequest->markStatusAs('Started');

        if ($cacheClearRequest->ProtocolID) $this->setProtocol($cacheClearRequest->Protocol());
        if ($cacheClearRequest->ChannelID) $this->setChannel($cacheClearRequest->Channel());
        if ($cacheClearRequest->InstituteID) $this->setInstitute($cacheClearRequest->Institute());

        $listOfRepoItems = $this->getFilteredRepoList();
        $listOfPersons = $this->getFilteredPersonList();

        if ($cacheClearRequest->ProtocolID !== 0) {
            if ($this->getProtocol()->SystemKey == 'OAI-PMH') {
                $listOfRepoItems = $listOfRepoItems->where("SurfSharekit_Cache_RecordNode.EndPoint = 'OAI'");
            } else if ($this->getProtocol()->SystemKey == 'CSV') {
                foreach ($listOfRepoItems as $repoItem) {
                    DataObjectCSVFileEncoder::getCSVRowFor($repoItem, true, $this->getProtocol());
                }
            }
        }
        if (!$this->getProtocol() && !$this->getChannel() && !$this->getInstitute()) {
            return;
        }

        $this->setItemCount($listOfRepoItems->count() + $listOfPersons->count());

        $this->refreshRepoItemsCache($listOfRepoItems);
        $this->refreshPersonsCache($listOfPersons);
    }

    private function getNextCacheClearRequest(): ?CacheClearRequest {
        DB::prepared_query("
            UPDATE SurfSharekit_CacheClearRequest
            SET TaskID = ?
            WHERE TaskID IS NULL AND Status = 'Queued'
            LIMIT 1
        ", [$this->getTaskId()]);

        return CacheClearRequest::get()->find('TaskID', $this->getTaskId());
    }

    private function refreshRepoItemsCache(DataList $listOfRepoItems) {
        foreach ($listOfRepoItems as $repoItem) {
            if ($repoItem && $repoItem->exists()) {
                $cachedRecords = self::getCacheRecords('repoItem')->filter(['RepoItemID' => $repoItem->ID]);

                foreach($cachedRecords as $cachedRecord) {
                    $cachedProtocol = $cachedRecord->Protocol();
                    // check if cached record has a channel with the same protocol, if not then remove cached record
                    $channel = $cachedRecord->Channel();
                    if($channel->ProtocolID == $cachedProtocol->ID) {
                        try{

                        if ($cachedRecord->Protocol()->SystemKey == 'OAI-PMH') {
                            $repoItemSummary = [
                                'Uuid' => $repoItem->Uuid,
                                'LastEdited' => $repoItem->LastEdited,
                                'Cached' => 1,
                                'InstituteUuid' => $repoItem->InstituteUuid,
                                'PartOfChannel' => 1,
                                'IsActive' => $repoItem->IsActive
                            ];
                            $listRecordNode = ListRecordsNode::createNodeFrom($repoItemSummary, $channel, $cachedProtocol->Prefix, true);
                            $fakeNode = new SimpleXMLElement('<Fake></Fake>');
                            $listRecordNode->addTo($fakeNode); //when adding, it'll cache
                        } elseif ($cachedRecord->Protocol()->SystemKey == 'JSON:API') {
                            $descriptionForDataObject = new ExternalRepoItemChannelJsonApiDescription($channel);
                            $descriptionForDataObject->getCache($repoItem);
                            $dataDescription = [];
                            $dataDescription[JsonApi::TAG_ATTRIBUTES] = $descriptionForDataObject->describeAttributesOfDataObject($repoItem);
                            $dataDescription[JsonApi::TAG_TYPE] = DataObjectJsonApiEncoder::describeTypeOfDataObject($descriptionForDataObject);
                            if ($metaInformation = $descriptionForDataObject->describeMetaOfDataObject($repoItem)) {
                                $dataDescription[JsonApi::TAG_META] = $metaInformation;
                            }
                            $dataDescription[JsonApi::TAG_ID] = DataObjectJsonApiEncoder::describeIdOfDataObject($repoItem);
                            $descriptionForDataObject->cache($repoItem, $dataDescription);
                        }

                        } catch (Exception $e){
                            Logger::errorLog($e->getMessage());
                            // skip and continue
                        }
                    }else{
                        // old cache
                        $cachedRecord->delete();
                    }
                }
            } else {
                $cacheNode = Cache_RecordNode::get()->filter('RepoItemID', $repoItem->ID)->first();
                if ($cacheNode && $cacheNode->exists()) {
                    $cacheNode->delete();
                }
            }

            $this->incrementItemsDone();
        }
    }

    private function refreshPersonsCache(DataList $listOfPersons) {
        foreach ($listOfPersons as $person) {
            if ($person && $person->exists()) {
                $cachedRecords = self::getCacheRecords('person')->filter(['PersonID' => $person->ID]);

                foreach($cachedRecords as $cachedRecord) {
                    $channel = $cachedRecord->Channel;

                    $descriptionForDataObject = new ExternalPersonJsonApiDescription($channel);
                    $descriptionForDataObject->getCache($person);
                    $dataDescription = [];
                    $dataDescription[JsonApi::TAG_ATTRIBUTES] = $descriptionForDataObject->describeAttributesOfDataObject($person);
                    $dataDescription[JsonApi::TAG_TYPE] = DataObjectJsonApiEncoder::describeTypeOfDataObject($descriptionForDataObject);
                    if ($metaInformation = $descriptionForDataObject->describeMetaOfDataObject($person)) {
                        $dataDescription[JsonApi::TAG_META] = $metaInformation;
                    }
                    $dataDescription[JsonApi::TAG_ID] = DataObjectJsonApiEncoder::describeIdOfDataObject($person);
                    $descriptionForDataObject->cache($person, $dataDescription);

                }
            } else {
                $cacheNode = Cache_RecordNode::get()->filter('PersonID', $person->ID)->first();
                if ($cacheNode && $cacheNode->exists()) {
                    $cacheNode->delete();
                }
            }

            $this->incrementItemsDone();
        }
    }

    private function getCacheRecords(string $type): ?DataList {
        $list = $this->applyCacheClearRequestFilter(Cache_RecordNode::get());
        if ($this->getInstitute()?->ID) {
            if ($type === 'repoItem') {
                $list->innerJoin("SurfSharekit_RepoItem", "SurfSharekit_RepoItem.ID = SurfSharekit_Cache_RecordNode.RepoItemID");
            } else if ($type === 'person') {
                $list
                    ->innerJoin("Group_Members", "Group_Members.MemberID = SurfSharekit_Cache_RecordNode.PersonID")
                    ->innerJoin("Group", "Group.ID = Group_Members.GroupID");
            }
        }

        return $list;
    }

    /**
     * @return mixed
     */
    public function getTaskId() {
        return $this->taskId;
    }

    /**
     * @param mixed $taskId
     */
    public function setTaskId($taskId): void {
        $this->taskId = $taskId;
    }

    /**
     * @return Protocol
     */
    public function getProtocol(): ?Protocol {
        return $this->protocol;
    }

    /**
     * @param Protocol $protocol
     */
    public function setProtocol($protocol): void {
        $this->protocol = $protocol;
    }

    /**
     * @return Channel
     */
    public function getChannel(): ?Channel {
        return $this->channel;
    }

    /**
     * @param Channel $channel
     */
    public function setChannel($channel): void {
        $this->channel = $channel;
    }

    /**
     * @return Institute
     */
    public function getInstitute(): ?Institute {
        return $this->institute;
    }

    /**
     * @param Institute $institute
     */
    public function setInstitute($institute): void {
        $this->institute = $institute;
    }

    /**
     * @return mixed
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getItemCount(): int {
        return $this->itemCount;
    }

    /**
     * @param int $itemCount
     */
    public function setItemCount($itemCount): void {
        $this->itemCount = $itemCount;
    }

    /**
     * @return CacheClearRequest
     */
    public function getCacheClearRequest(): CacheClearRequest {
        return $this->cacheClearRequest;
    }

    /**
     * @param CacheClearRequest $cacheClearRequest
     */
    public function setCacheClearRequest($cacheClearRequest): void {
        $this->cacheClearRequest = $cacheClearRequest;
    }

    public function incrementItemsDone() {
        $this->itemsDone += 1;

        $this->getCacheClearRequest()->updateProgress($this->itemsDone / $this->getItemCount() * 100);
    }
}