<?php

namespace SurfSharekit\Api;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DB;
use SimpleXMLElement;
use SurfSharekit\Models\Protocol;
use SurfSharekit\Models\RepoItem;
use UuidExtension;

class ListMetaDataFormatsNode extends OAIPMHVerbNode {
    static function addTo(SimpleXMLElement $node, HTTPRequest $request, $channel = null) {
        $listSetsNode = $node->addChild('ListMetadataFormats');

        foreach (static::getMetadataFormatNodes($request, $channel) as $setNode) {
            $setNode->addTo($listSetsNode);
        }
    }

    private static function getMetadataFormatNodes(HTTPRequest $request, $channel = null): array {
        $protocolToSet = function ($protocol) {
            return new MetadataFormatNode($protocol->Prefix, $protocol->_Schema, $protocol->NamespaceURI); //cannot do getSchema due to premade silverstripe method
        };

        if ($channel && $channel->exists()) {
            $allSupportedProtocols = Protocol::get()->filter('ID', $channel->ProtocolID);
        } else {
            $allSupportedProtocols = Protocol::get()->filter('SystemKey', 'OAI-PMH');
        }

        //Filter all supported protocols by the requested identifier
        $identifier = $request->getVar('identifier') ?: $request->postVar('identifier');
        if ($identifier) {
            $repoItemUuid = OaipmhApiController::getRepoItemIDFromIdentifier($identifier);
            $repoItem = UuidExtension::getByUuid(RepoItem::class, $repoItemUuid);
            if (!$repoItem || !$repoItem->exists()) {
                throw new IdDoesNotExistException();
            }

            $repoItemUuid = OaipmhApiController::getRepoItemIDFromIdentifier($identifier);

            $channelFilterArray = static::getChannelFilter($channel);
            if (is_null($channelFilterArray)) {
                $channelFilter = '';
                $channelJoin = '';
            } else {
                $channelFilter = $channelFilterArray['channelFilterString'];
                $channelJoin = $channelFilterArray['channelJoinString'];
            }
            $repoItemIsAvailable = DB::prepared_query("SELECT * FROM SurfSharekit_RepoItem 
                        $channelJoin
                        WHERE SurfSharekit_RepoItem.Uuid = ? $channelFilter", [$repoItemUuid])->numRecords();

            if (!$repoItemIsAvailable) {
                throw new IdDoesNotExistException();
            }

            if ($allSupportedProtocols->count() == 0) {
                throw new NoMetadataFormatsException('The requested item has no metadata descriptions available');
            }
        }

        if ($allSupportedProtocols->count() == 0) {
            throw new NoMetadataFormatsException('No OAI protocols supported');
        }

        return array_map($protocolToSet, $allSupportedProtocols->toArray());
    }
}

class MetadataFormatNode {
    private $prefix;
    private $schema;
    private $metadataNamespace;

    public function __construct($prefix, $schema, $metadataNamespace) {
        $this->prefix = $prefix;
        $this->schema = $schema;
        $this->metadataNamespace = $metadataNamespace;
    }

    function addTo(SimpleXMLElement $node) {
        $childNode = $node->addChild('metadataFormat');
        $childNode->addChild('metadataPrefix', $this->prefix);
        $childNode->addChild('schema', $this->schema);
        $childNode->addChild('metadataNamespace', $this->metadataNamespace);
    }
}