<?php

namespace SilverStripe\Services\Channel;

use SilverStripe\ORM\DataList;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemMetaFieldValue;

interface IChannelService {

    /**
     * @param string $instituteUuid
     * @return DataList
     */
    public function getAllChannelsWithinInstituteSubtree(string $instituteUuid): DataList;

    /**
     * @param RepoItem $repoItem
     * @return DataList
     */
    public function getAllAllowedChannelsForRepoItem(RepoItem $repoItem): DataList;

    /**
     * @param RepoItem $repoItem
     * @return DataList
     */
    public function getAllEnabledChannelsForRepoItem(RepoItem $repoItem): DataList;

    /**
     * @param RepoItem $repoItem
     * @param RepoItemMetaFieldValue $channel
     * @return void
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function disableChannelForRepoItem(RepoItem $repoItem, RepoItemMetaFieldValue $channel): void;

    /**
     * @param RepoItem $repoItem
     * @param MetaField $channel
     * @return void
     * @throws BadRequestException
     */
    public function enableChannelForRepoItem(RepoItem $repoItem, MetaField $channel): void;

    /**
     * Validate that all provided channels in the request body exist and are valid for this particular repoitem,
     * meaning they are currently included in the template of the provided repoitem
     *
     * @param RepoItem $repoItem
     * @param array $channelUuids
     * @return DataList
     * @throws NotFoundException
     */
    public function getChannelsForRepoItem(RepoItem $repoItem, array $channelUuids): DataList;
}