<?php

namespace SurfSharekit\Api;

use Ramsey\Uuid\Uuid;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DB;
use SimpleXMLElement;
use SurfSharekit\Models\Channel;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\ProtocolFilter;
use UuidExtension;

abstract class RepoItemDescribingNode extends OAIPMHVerbNode {
    static function addTo(SimpleXMLElement $node, HttpRequest $request, Channel $channel = null) {
        //select metadataPrefix
        $metadataPrefix = $request->getVar('metadataPrefix') ?: $request->postVar('metadataPrefix');
        $setId = $request->getVar('set') ?: $request->postVar('set');
        $from = $request->getVar('from') ?: $request->postVar('from');
        $until = $request->getVar('until') ?: $request->postVar('until');
        $limit = $request->getVar('limit') ?: $request->postVar('limit') ?: static::getFromResumptionToken($request, 'limit');
        $offset = $request->getVar('offset') ?: $request->postVar('offset') ?: static::getFromResumptionToken($request, 'offset');
        $purge = $request->getVar('purge') ?: $request->postVar('purge');

        $resumptionToken = static::getResumptionToken($request);
        if ($resumptionToken) {
            if (static::isValidResumptionToken($resumptionToken)) {
                $from = self::getFromDateFromResumptionToken($resumptionToken);
                $until = self::getUntilDateFromResumptionToken($resumptionToken);
                $limit = self::getSizeFromResumptionToken($resumptionToken);
                $offset = self::getPageFromResumptionToken($resumptionToken) * $limit;
                $metadataPrefix = self::getMetadataPrefixFromResumptionToken($resumptionToken);
                $setId = self::getSetIdFromResumptionToken($resumptionToken);
            } else {
                throw new BadResumptionTokenException('Repository received an incorrect resumption token');
            }
        }
        if (!$metadataPrefix) {
            throw new BadArgumentException('Missing metadataPrefix as argument');
        }
        $allOAIPMHPrefixes = Protocol::get()->filter('SystemKey', 'OAI-PMH')->setQueriedColumns('Prefix')->column('Prefix');
        if (!in_array($metadataPrefix, $allOAIPMHPrefixes)) {
            throw new CannotDisseminateFormatException("$metadataPrefix is not a valid prefix");
        }
        $protocol = Protocol::get()->filter(['Prefix' => $metadataPrefix])->filter(['SystemKey' => 'OAI-PMH'])->first();
        $setDbId = null;
        if ($setId) {
            if (!Uuid::isValid($setId)) {
                throw new BadArgumentException("Set is not valid");
            }
            $insituteSet = UuidExtension::getByUuid(Institute::class, $setId);
            if (!$insituteSet || !$insituteSet->exists()) {
                throw new BadArgumentException("Set does not exist");
            }
            $setDbId = $insituteSet->ID;
        }
        if ($from) {
            if (!RepoItemDescribingNode::isValidOaiPmhFilterDate($from)) {
                throw new BadArgumentException("Invalid date format");
            }
        }
        if ($until) {
            if (!RepoItemDescribingNode::isValidOaiPmhFilterDate($until)) {
                throw new BadArgumentException("Invalid date format");
            }
        }
        if ($limit) {
            $limit = intval($limit);
        } else {
            $limit = 100;
        }
        if ($offset) {
            $offset = intval($offset);
        } else {
            $offset = 0;
        }
        if ($purge) {
            $purge = intval($purge);
            set_time_limit(0); // increase time limit when purging
        } else {
            $purge = 0;
        }
        $channelFilter = static::getChannelFilter($channel);

        $listIdentifiersNode = $node->addChild(static::getNodeName());
        foreach (static::getAllNodes($metadataPrefix, $setDbId, $from, $until, 'OAI', $protocol->ID, $channel, $channelFilter, $limit, $offset, $purge, null, true) as $identifierNode) {
            $identifierNode->addTo($listIdentifiersNode);
        }

        $totalCount = static::getTotalCount($setDbId, $from, $until, 'OAI', $protocol->ID, $channel, $channelFilter);
        $topCountCurrentQuery = ($limit + $offset);
        if ($totalCount > $topCountCurrentQuery) {
            $fromUnix = $from ? strtotime($from) : '';
            $untilUnix = $until ? strtotime($until) : '';
            $nextResumptionToken = "$fromUnix;$untilUnix;$limit;" . (self::getPageFromResumptionToken($resumptionToken) + 1) . ";$metadataPrefix;" . $setId;
            $resumptionTokenNode = $listIdentifiersNode->addChild('resumptionToken', $nextResumptionToken);
            $resumptionTokenNode->addAttribute('cursor', $topCountCurrentQuery);
            $resumptionTokenNode->addAttribute('completeListSize', $totalCount);
        }
    }

    static function addToSru(SimpleXMLElement $node, HttpRequest $request, Channel $channel = null) {

        $metadataPrefix = 'didl_mods';

        $limit = $request->getVar('limit') ?: $request->postVar('limit');
        if ($limit) {
            $limit = intval($limit);
        } else {
            $limit = 0;
        }
        $offset = $request->getVar('offset') ?: $request->postVar('offset');
        if ($offset) {
            $offset = intval($offset);
        } else {
            $offset = 0;
        }

        $purge = $request->getVar('purge') ?: $request->postVar('purge');
        if ($purge) {
            $purge = intval($purge);
            set_time_limit(0); // increase time limit when purging
        } else {
            $purge = 0;
        }

        $query = $request->getVar('query') ?: $request->postVar('query');
        if (!is_null($query)) {
            $queryFilter = static::getQueryFilter($query, $channel);
            if (is_null($queryFilter)) {
                // no valid filter found, return
                return;
            }
        } else {
            $queryFilter = null;
        }

        $channelFilter = static::getChannelFilter($channel);
        $protocol = Protocol::get()->filter(['Prefix' => $metadataPrefix])->filter(['SystemKey' => 'OAI-PMH'])->first();
        $listIdentifiersNode = $node->addChild('srw:records');
        try {
            foreach (static::getAllNodes($metadataPrefix, null, null, null, 'SRU', $protocol->ID, $channel, $channelFilter, $limit, $offset, $purge, $queryFilter, false) as $identifierNode) {
                $identifierNode->addToSru($listIdentifiersNode);
            }
        } catch (NoRecordsMatchException $exception) {
            // do nothing
        }
    }

    /** var Channel|null $channel */
    public static function getQueryFilter($query, $channel) {
        if (is_null($channel) || !$channel instanceof Channel) {
            return null;
        }
        if (trim($query) == '*') {
            // special wildcard
            return ['joinItems' => [], 'joinParams' => []];
        }
        /** @var Protocol $protocol */
        $protocol = $channel->Protocol();
        $queryItems = explode(' and ', $query);
        $queryFields = [];

        foreach ($queryItems as $queryItem) {
            foreach (SruApiController::SRU_SUPPORTED_OPERATORS as $operator) {
                $queryProps = explode($operator, trim($queryItem));
                if (count($queryProps) == 2) {
                    $queryKey = trim($queryProps[0]);
                    $queryValue = trim($queryProps[1]);
                    if (strlen($queryKey) > 0 && strlen($queryValue) > 0) {
                        if ($operator == '=') {
                            $queryFields[$queryKey] = [$queryValue, 'is'];
                            break;
                        } else if ($operator == '<>') {
                            $queryFields[$queryKey] = [$queryValue, 'not'];
                            break;
                        } else if ($operator == ' any ') {
                            $queryFields[$queryKey] = [$queryValue, 'in'];
                            break;
                        }
                    }
                }
            }
        }

        $joinItems = [];
        $joinParams = [];
        foreach ($queryFields as $queryField => $query) {
            $queryValue = $query[0];
            $queryMode = $query[1];
            $operator = '=';
            if ($queryMode == 'not') {
                $operator = '!=';
                $explodedQueryValues = [$queryValue];
            } else if ($queryMode == 'in') {
                $operator = 'IN';
                $explodedQueryValues = explode(' ', $queryValue);
            } else {
                $explodedQueryValues = [$queryValue];
            }
            $protocolFilters = $protocol->ProtocolFilters()->filter(['Title' => $queryField]);

            foreach ($protocolFilters as $protocolFilter) {
                /** @var MetaField $metaField */
                $metaField = $protocolFilter->MetaField();
                $repoItemAttribute = $protocolFilter->RepoItemAttribute;
                if ($metaField->exists() && !$repoItemAttribute) {
                    $metaFieldTypeUuid = $metaField->getField('MetaFieldTypeUuid');
                    if ($metaFieldTypeUuid == 'e855c256-3efb-4731-8d39-e52a57a42197') {
                        // TODO, hard coded voor opleidingen, generiek oplossen
                        $joinKey = str_replace('-', '', Uuid::uuid4());
                        $joinItem = ' INNER JOIN SurfSharekit_RepoItemMetaField rmf' . $joinKey . ' ON rmf' . $joinKey . '.RepoItemID = ' . 'SurfSharekit_RepoItem.ID AND rmf' . $joinKey . '.MetaFieldID = ' . $metaField->ID;
                        $joinItem .= ' INNER JOIN SurfSharekit_RepoItemMetaFieldValue rmfv' . $joinKey . ' ON rmfv' . $joinKey . '.RepoItemMetaFieldID = rmf' . $joinKey . '.ID AND rmfv' . $joinKey . '.IsRemoved = 0 ';
                        $joinItem .= ' INNER JOIN SurfSharekit_Institute rmfvi' . $joinKey . ' ON rmfvi' . $joinKey . '.ID = rmfv' . $joinKey . '.InstituteID AND rmfvi' . $joinKey . '.Level = \'discipline\' AND rmfvi' . $joinKey . '.Title = ? ';
                        $joinItems[] = $joinItem;
                        $joinParams[] = array_merge($joinParams, $explodedQueryValues);
                    } elseif ($metaFieldTypeUuid == '590da2d9-10a6-468b-942b-c181342c6555' or $metaFieldTypeUuid == '7ff97962-68ab-4c1d-a218-64dd29efa3a2') {
                        // TODO, hard coded voor trefwoorden en dropdowns, generiek oplossen
                        $joinKey = str_replace('-', '', Uuid::uuid4());
                        $joinItem = ' INNER JOIN SurfSharekit_RepoItemMetaField rmf' . $joinKey . ' ON rmf' . $joinKey . '.RepoItemID = ' . 'SurfSharekit_RepoItem.ID AND rmf' . $joinKey . '.MetaFieldID = ' . $metaField->ID;
                        $joinItem .= ' INNER JOIN SurfSharekit_RepoItemMetaFieldValue rmfv' . $joinKey . ' ON rmfv' . $joinKey . '.RepoItemMetaFieldID = rmf' . $joinKey . '.ID AND rmfv' . $joinKey . '.IsRemoved = 0 ';
                        $joinItem .= ' INNER JOIN SurfSharekit_MetaFieldOption rmfvo' . $joinKey . ' ON rmfvo' . $joinKey . '.ID = rmfv' . $joinKey . '.MetaFieldOptionID AND rmfvo' . $joinKey . '.Value ' . $operator . ' ' . static::getParameterizedQueryValue($operator, $queryValue) . ' ';
                        $joinItems[] = $joinItem;
                        $joinParams = array_merge($joinParams, $explodedQueryValues);
                    } else {
                        /** @var MetaField $childMetaField */
                        $childMetaField = $protocolFilter->ChildMetaField();
                        // TODO, now hardcoded for Person, also support other childMetaFields
                        if ($childMetaField && $childMetaField->exists()) {
                            $joinKey = str_replace('-', '', Uuid::uuid4());
                            $joinItem = ' INNER JOIN SurfSharekit_RepoItemMetaField rmf' . $joinKey . ' ON rmf' . $joinKey . '.RepoItemID = ' . 'SurfSharekit_RepoItem.ID AND rmf' . $joinKey . '.MetaFieldID = ' . $metaField->ID;
                            $joinItem .= ' INNER JOIN SurfSharekit_RepoItemMetaFieldValue rmfv' . $joinKey . ' ON rmfv' . $joinKey . '.RepoItemMetaFieldID = rmf' . $joinKey . '.ID AND rmfv' . $joinKey . '.IsRemoved = 0 ';
                            $joinItem .= ' INNER JOIN SurfSharekit_RepoItem rmfvr' . $joinKey . ' ON rmfvr' . $joinKey . '.ID = rmfv' . $joinKey . '.RepoItemID ';
                            $joinItem .= ' INNER JOIN SurfSharekit_RepoItemMetaField rmfvrmf' . $joinKey . ' ON rmfvrmf' . $joinKey . '.RepoItemID = ' . 'rmfvr' . $joinKey . '.ID AND rmfvrmf' . $joinKey . '.MetaFieldID = ' . $childMetaField->ID;
                            $joinItem .= ' INNER JOIN SurfSharekit_RepoItemMetaFieldValue rmfvrmfv' . $joinKey . ' ON rmfvrmfv' . $joinKey . '.RepoItemMetaFieldID = rmfvrmf' . $joinKey . '.ID AND rmfvrmfv' . $joinKey . '.IsRemoved = 0 ';
                            $joinItem .= ' INNER JOIN SurfSharekit_Person rmfvrmfvp' . $joinKey . ' ON rmfvrmfvp' . $joinKey . '.ID = rmfvrmfv' . $joinKey . '.PersonID AND rmfvrmfvp' . $joinKey . '.PersistentIdentifier = ? ';
                            $joinItems[] = $joinItem;
                            $joinParams = array_merge($joinParams, $explodedQueryValues);
                        } else {
                            $joinKey = str_replace('-', '', Uuid::uuid4());
                            $joinItem = ' INNER JOIN SurfSharekit_RepoItemMetaField rmf' . $joinKey . ' ON rmf' . $joinKey . '.RepoItemID = ' . 'SurfSharekit_RepoItem.ID AND rmf' . $joinKey . '.MetaFieldID = ' . $metaField->ID;
                            $joinItem .= ' INNER JOIN SurfSharekit_RepoItemMetaFieldValue rmfv' . $joinKey . ' ON rmfv' . $joinKey . '.RepoItemMetaFieldID = rmf' . $joinKey . '.ID AND rmfv' . $joinKey . '.Value ' . $operator . ' ' . static::getParameterizedQueryValue($operator, $queryValue) . ' ' . 'AND rmfv' . $joinKey . '.IsRemoved = 0 ';
                            $joinItems[] = $joinItem;
                            $joinParams = array_merge($joinParams, $explodedQueryValues);
                        }
                    }
                    if ($protocolFilter->VirtualMetaField == 'hbo:namePart:departmentFromLowerInstitute') {
                        if ($channel->exists() && $channel->Institutes()->count() > 0) {
                            $scopedInstitutes = InstituteScoper::getScopeFilter($channel->Institutes()->column('ID'));
                            $departmentInstitute = Institute::get()->where("SurfSharekit_Institute.ID IN ( $scopedInstitutes )")->filter(['Title' => $query, 'Level' => 'department'])->first();
                            if (!is_null($departmentInstitute)) {
                                $lowerInstituteIDs = InstituteScoper::getScopeFilter([$departmentInstitute->ID]);
                                // hard coded form department
                                $joinKey = 'v' . $protocolFilter->ID;
                                $joinItem = ' INNER JOIN SurfSharekit_RepoItemMetaField rmf' . $joinKey . ' ON rmf' . $joinKey . '.RepoItemID = ' . 'SurfSharekit_RepoItem.ID AND rmf' . $joinKey . '.MetaFieldID in( 22 ,102) ';
                                $joinItem .= ' INNER JOIN SurfSharekit_RepoItemMetaFieldValue rmfv' . $joinKey . ' ON rmfv' . $joinKey . '.RepoItemMetaFieldID = rmf' . $joinKey . '.ID AND rmfv' . $joinKey . '.IsRemoved = 0 ';
                                $joinItem .= ' INNER JOIN SurfSharekit_Institute rmfvi' . $joinKey . ' ON rmfvi' . $joinKey . '.ID = rmfv' . $joinKey . '.InstituteID AND rmfvi' . $joinKey . '.ID in ( ' . $lowerInstituteIDs . ') ';
                                // $joinParam = $queryValue;
                                $joinItems[] = $joinItem;
                                //$joinParams[] = $joinParam;
                            } else {
                                // department not found return null
                                return null;
                            }
                        }
                    }
                } else if ($repoItemAttribute) {
                    if ($protocolFilter->RepoItemAttribute == 'RepoType') {
                        $joinKey = str_replace('-', '', Uuid::uuid4());
                        $joinItem = ' INNER JOIN SurfSharekit_RepoItem rmfvr' . $joinKey . ' ON SurfSharekit_RepoItem.RepoType ' . $operator . ' ' . static::getParameterizedQueryValue($operator, $queryValue) . ' ';
                        $joinItems[] = $joinItem;
                        $joinParams = array_merge($joinParams, $explodedQueryValues);
                    }
                }
            }
        }
        if (count($joinItems)) {
            return ['joinItems' => $joinItems, 'joinParams' => $joinParams];
        } else {
            return null;
        }
    }

    private static function getAllNodes($metadataPrefix, $setId, $from, $until, $endpoint, $protocolId, $channel, $channelFilterArray = null, $limit = 0, $offset = 0, $purgeCache = false, $queryFilterArray = null, $includeCachedItems = true): array {
        $queryWithParams = static::getSelectionQuery($setId, $from, $until, $endpoint, $protocolId, $channel, $channelFilterArray, $limit, $offset, $queryFilterArray, $includeCachedItems);
        $query = DB::prepared_query($queryWithParams[0], $queryWithParams[1]);
        $allChildNodes = [];
        while ($summary = $query->record()) {
            $allChildNodes[] = static::createNodeFrom($summary, $channel, $metadataPrefix, $purgeCache);
        }

        if (count($allChildNodes) === 0) {
            throw new NoRecordsMatchException("The combination of arguments resulted in an empty list");
        }

        return $allChildNodes;
    }

    private
    static function getTotalCount($setId, $from, $until, $endpoint, $protocolId, $channel, $channelFilterArray = null, $includeCachedItems = true) {
        $queryWithParams = static::getSelectionQuery($setId, $from, $until, $endpoint, $protocolId, $channel, $channelFilterArray, 0, 0, null, $includeCachedItems);

        return DB::prepared_query($queryWithParams[0], $queryWithParams[1])->numRecords();
    }

    public
    static function getSelectionQuery($setId, $from, $until, $endpoint, $protocolId, $channel, $channelFilterArray = null, $limit = 0, $offset = 0, $queryFilterArray = null, $includeCachedItems = true) {
        Logger::debugLog(var_export($queryFilterArray, true));

        $channelJoin = "LEFT JOIN SurfSharekit_Cache_RecordNode c ON c.RepoItemID = SurfSharekit_RepoItem.ID and c.ProtocolID = $protocolId and c.Endpoint = '" . $endpoint . "' AND c.ChannelID = " . ($channel ? $channel->ID : 0);
        if (is_null($channelFilterArray)) {
            $channelFilter = '';
        } else {
            $channelFilter = $channelFilterArray['channelFilterString'];
            $channelJoin .= $channelFilterArray['channelJoinString'];
        }

        if (is_null($queryFilterArray)) {
            $queryParams = [];
            $queryJoin = '';
        } else {
            $joinParams = $queryFilterArray['joinParams'];
            $queryJoin = '';
            $queryParams = [];
            foreach ($queryFilterArray['joinItems'] as $queryJoinItem) {
                $queryJoin = $queryJoin . $queryJoinItem;
            }
            $queryParams = array_merge($queryParams, $joinParams);
        }

        //select set if applicable
        $params = [];
        $setFilter = '';
        if ($setId) {
            $setFilter = " AND SurfSharekit_RepoItem.InstituteID IN (" . InstituteScoper::getScopeFilter([$setId]) . ")";
        }
        $dateFilter = '';
        if ($from) {
            $dateFilter .= " AND ((Date(SurfSharekit_RepoItem.Created) >= Date(?)) OR (Date(SurfSharekit_RepoItem.LastEdited) >= Date(?)))";
            $params[] = $from;
            $params[] = $from;
        }
        if ($until) {
            $dateFilter .= " AND ((Date(SurfSharekit_RepoItem.Created) <= Date(?)) OR (Date(SurfSharekit_RepoItem.LastEdited) <= Date(?)))";
            $params[] = $until;
            $params[] = $until;
        }
        $limitParam = '';
        if ($limit) {
            $limitParam = " LIMIT $limit ";

            if ($offset) {
                $limitParam .= " OFFSET $offset ";
            }
        }

        $params = array_merge($queryParams, $params);
        if ($from) {
            $params[] = $from;
            $params[] = $from;
        }
        if ($until) {
            $params[] = $until;
            $params[] = $until;
        }

        $generalFilter = "SurfSharekit_RepoItem.RepoType in ('PublicationRecord', 'LearningObject', 'ResearchObject') AND SurfSharekit_RepoItem.IsRemoved = 0 AND SurfSharekit_RepoItem.IsArchived = 0 ";

        if ($includeCachedItems) {
            $cachedQueryParams = "OR (c.ID IS NOT NULL $setFilter $dateFilter)";
        } else {
            $cachedQueryParams = '';
        }

        $query = "SELECT distinct SurfSharekit_RepoItem.ID, 
                                SurfSharekit_RepoItem.Uuid, 
                                SurfSharekit_RepoItem.LastEdited, 
                                SurfSharekit_RepoItem.InstituteUuid, 
                                if(c.RepoItemID IS NOT NULL, TRUE, FALSE) as Cached, if($generalFilter $channelFilter, TRUE, FALSE) AS PartOfChannel FROM SurfSharekit_RepoItem 
                        $channelJoin $queryJoin
                        WHERE (($generalFilter $channelFilter $setFilter $dateFilter) 
                        $cachedQueryParams
                        ) $limitParam";
        return [$query, $params];
    }

    abstract static function createNodeFrom($repoItemSummary, $channel, $metadataPrefix, $purgeCache);

    static function isValidOaiPmhFilterDate($date) {
        //matches YYYY-MM-DDThh:mm:ssZ and YYYY-MM-DDThh:mm:ss.SSSSZ
        $isValid = preg_match("/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(.\d{4})?)Z$/", $date) ? true : false;
        if (!$isValid) {
            $isValid = preg_match("/^(\d{4}-\d{2}-\d{2})$/", $date) ? true : false;
        }

        return $isValid;
    }

    static function getParameterizedQueryValue($operator, $queryValue) {
        if ($operator != 'IN') {
            return '?';
        }
        $paramCount = count(explode(' ', $queryValue));
        $newQueryValue = '(';
        for ($i = 0; $i < $paramCount; $i++) {
            $newQueryValue .= '?';
            if ($i < $paramCount - 1) {
                $newQueryValue .= ',';
            }
        }
        $newQueryValue .= ')';
        return $newQueryValue;
    }

    abstract static function getNodeName();

}