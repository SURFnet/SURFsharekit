<?php

namespace SurfSharekit\Api;

use SilverStripe\Control\HTTPRequest;
use SimpleXMLElement;
use SurfSharekit\Models\Channel;
use SurfSharekit\Models\Protocol;

abstract class OAIPMHVerbNode {
    abstract static function addTo(SimpleXMLElement $node, HttpRequest $request);

    protected static function getResumptionToken(HTTPRequest $request) {
        return $request->getVar('resumptionToken') ?: $request->postVar('resumptionToken');
    }

    protected static function getFromDateFromResumptionToken($resumptionToken) {
        $fromUnix = static::getPartFromResumptionToken($resumptionToken, 0);
        if (!$fromUnix) {
            return null;
        }
        $fromUnix = intval($fromUnix);
        if (!$fromUnix || $fromUnix <= 0) {
            throw new BadResumptionTokenException('Repository received an incorrect resumption token');
        }
        return gmdate("Y-m-d\TH:i:s\Z", $fromUnix);
    }

    protected static function getUntilDateFromResumptionToken($resumptionToken) {
        $untilUnix = static::getPartFromResumptionToken($resumptionToken, 1);
        if (!$untilUnix) {
            return null;
        }
        $untilUnix = intval($untilUnix);
        if (!$untilUnix || $untilUnix <= 0) {
            throw new BadResumptionTokenException('Repository received an incorrect resumption token');
        }
        return gmdate("Y-m-d\TH:i:s\Z", $untilUnix);
    }

    protected static function getSizeFromResumptionToken($resumptionToken) {
        return static::getPartFromResumptionToken($resumptionToken, 2);
    }

    protected static function getPageFromResumptionToken($resumptionToken) {
        return static::getPartFromResumptionToken($resumptionToken, 3);
    }

    protected static function getMetadataPrefixFromResumptionToken($resumptionToken) {
        $prefix = static::getPartFromResumptionToken($resumptionToken, 4);
        if ($prefix) {
            $allOAIPMHPrefixes = Protocol::get()->filter('SystemKey', 'OAI-PMH')->setQueriedColumns('Prefix')->column('Prefix');
            if (!in_array($prefix, $allOAIPMHPrefixes)) {
                throw new CannotDisseminateFormatException($prefix . " is not a valid prefix");
            }
        }
        return $prefix;
    }

    protected static function getSetIdFromResumptionToken($resumptionToken) {
        return static::getPartFromResumptionToken($resumptionToken, 5);
    }

    protected static function getPartFromResumptionToken($resumptionToken, $part) {
        //from;until;size;page;metadataprefix;
        if (!$resumptionToken) {
            return null;
        }
        $tokenParts = explode(';', $resumptionToken);
        if (count($tokenParts) != 6) {
            return null;
        }
        return $tokenParts[$part];
    }

    /**
     * @param HTTPRequest $request
     * @param string $infoString limit, offset
     */
    protected static function getFromResumptionToken(HTTPRequest $request, string $infoString) {
        $resumptionToken = static::getResumptionToken($request);
        if (!$resumptionToken) {
            return null;
        }
        if (!static::isValidResumptionToken($resumptionToken)) {
            return null;
        }
    }

    protected static function isValidResumptionToken($resumptionToken) {
        //from unix, until unix, page size, page number, metadataprefix
        //from;until;size;page;metadataprefix;
        if (!$resumptionToken) {
            return false;
        }
        if (!(static::getSizeFromResumptionToken($resumptionToken) &&
            static::getPageFromResumptionToken($resumptionToken))) {
            return false;
        }
        return true;
    }

    /** var Channel|null $channel */
    public static function getChannelFilter($channel) {
        if (is_null($channel) || !$channel instanceof Channel) {
            return null;
        }
        $channelJoinString = '';
        $channelFilterString = '';

        $channelFilters = $channel->ChannelFilters()->filter(['Enabled' => 1]);
        foreach ($channelFilters as $channelFilter) {
            if ($channelFilter->RepoItemAttribute) {
                $channelFilterString .= " AND (SurfSharekit_RepoItem.{$channelFilter->RepoItemAttribute} = '$channelFilter->Value')";
            } else {
                $channelJoinString .= "
            LEFT JOIN SurfSharekit_RepoItemMetaField rimf{$channelFilter->ID} ON rimf{$channelFilter->ID}.RepoItemID = SurfSharekit_RepoItem.ID AND rimf{$channelFilter->ID}.MetaFieldID = $channelFilter->MetaFieldID 
            LEFT JOIN SurfSharekit_RepoItemMetaFieldValue rimfv{$channelFilter->ID} ON rimfv{$channelFilter->ID}.RepoItemMetaFieldID = rimf{$channelFilter->ID}.ID
            ";
                $channelFilterString .= " AND (rimfv{$channelFilter->ID}.Value = '$channelFilter->Value' AND rimfv{$channelFilter->ID}.IsRemoved = 0) ";
            }
        }

        //Scope on institutes if needed
        if ($channel->exists() && $channel->Institutes()->count() > 0) {
            $channelFilterString .= " AND SurfSharekit_RepoItem.InstituteID IN (" . InstituteScoper::getScopeFilter($channel->Institutes()->column('ID')) . ') ';
        }

        return ['channelFilterString' => $channelFilterString, 'channelJoinString' => $channelJoinString];
    }
}