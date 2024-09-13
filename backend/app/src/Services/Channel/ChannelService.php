<?php

namespace SilverStripe\Services\Channel;

use SilverStripe\api\Upload\Processors\SwitchRowFieldMetaFieldProcessor;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataList;
use SilverStripe\Services\RepoItem\RepoItemService;
use SurfSharekit\Api\Exceptions\ApiErrorConstant;
use SurfSharekit\Api\Exceptions\BadRequestException;
use SurfSharekit\Api\Exceptions\NotFoundException;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\constants\RepoItemTypeConstant;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemMetaFieldValue;

class ChannelService implements IChannelService {
    use Injectable;
    use Configurable;

    /**
     * Returns all the channels from templates of an institute and all the institutes below it
     *
     * @param string $instituteUuid Uuid of the parent Institute. This can be any Institute inside an Institute tree
     * @param array $repoTypes
     * @return DataList
     */
    public function getAllChannelsWithinInstituteSubtree(string $instituteUuid, array $repoTypes = RepoItemTypeConstant::PRIMARY_TYPES): DataList {
        $institutes = InstituteScoper::getInstitutesOfLowerScope([$instituteUuid]);

        $instituteIds = implode(",", $institutes->getIDList());
        $instituteList = "(" . $instituteIds . ")";

        $repoTypes = implode("','", $repoTypes);
        $repoTypesList = "('" . $repoTypes . "')";

        return MetaField::get()
            ->innerJoin("SurfSharekit_MetaFieldType", "mft.ID = SurfSharekit_MetaField.MetaFieldTypeID", "mft")
            ->innerJoin('SurfSharekit_TemplateMetaField', 'tmf.MetaFieldUuid = SurfSharekit_MetaField.Uuid', "tmf")
            ->innerJoin('SurfSharekit_Template', 't.Uuid = tmf.TemplateUuid', "t")
            ->where([
                "t.InstituteID IN $instituteList",
                "mft.Key = 'Switch-row'",
                "t.RepoType IN $repoTypesList",
                "SurfSharekit_MetaField.JsonKey IS NOT NULL"
            ]);
    }

    /**
     * @param RepoItem $repoItem
     * @return DataList
     */
    public function getAllAllowedChannelsForRepoItem(RepoItem $repoItem): DataList {
        return MetaField::get()
            ->innerJoin("SurfSharekit_MetaFieldType", "mft.ID = SurfSharekit_MetaField.MetaFieldTypeID", "mft")
            ->innerJoin('SurfSharekit_TemplateMetaField', 'tmf.MetaFieldUuid = SurfSharekit_MetaField.Uuid', "tmf")
            ->innerJoin('SurfSharekit_Template', 't.Uuid = tmf.TemplateUuid', "t")
            ->where([
                "t.InstituteUuid = '$repoItem->InstituteUuid'",
                "mft.Key = 'Switch-row'",
                "t.RepoType = '$repoItem->RepoType'",
                "SurfSharekit_MetaField.JsonKey IS NOT NULL"
            ]);
    }

    /**
     * @param RepoItem $repoItem
     * @return DataList
     */
    public function getAllEnabledChannelsForRepoItem(RepoItem $repoItem): DataList {
        return $repoItem->getAllRepoItemMetaFieldValues()
            ->innerJoin("SurfSharekit_MetaField", "MetaField.ID = rimf.MetaFieldID", "MetaField")
            ->innerJoin("SurfSharekit_MetaFieldType", "MetaField.MetaFieldTypeID = MetaFieldType.ID", "MetaFieldType")
            ->where(["MetaFieldType.Key" => "Switch-row"]);
    }

    /**
     * @param RepoItem $repoItem
     * @param RepoItemMetaFieldValue $channel
     * @return void
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function disableChannelForRepoItem(RepoItem $repoItem, RepoItemMetaFieldValue $channel): void {
        $channel->IsRemoved = 1;
        $channel->write();
    }

    /**
     * @param RepoItem $repoItem
     * @param MetaField $channel
     * @return void
     * @throws BadRequestException
     */
    public function enableChannelForRepoItem(RepoItem $repoItem, MetaField $channel): void {
        $repoItemService = RepoItemService::create();
        $repoItemMetaField = $repoItemService->findOrCreateRepoItemMetaField($repoItem, $channel);
        $processor = SwitchRowFieldMetaFieldProcessor::create($repoItem, $channel, true);
        $validationResult = $processor->validate();
        if ($validationResult->hasErrors()) {
            throw new BadRequestException(ApiErrorConstant::GA_BR_001, $validationResult->getErrors()[0]);
        }
        $processor->save($repoItemMetaField);
    }

    /**
     * Validate that all provided channels in the request body exist and are valid for this particular repoitem,
     * meaning they are currently included in the template of the provided repoitem
     *
     * @param RepoItem $repoItem
     * @param array $channelUuids
     * @return DataList
     * @throws NotFoundException
     */
    public function getChannelsForRepoItem(RepoItem $repoItem, array $channelUuids): DataList {
        $channelMetaFieldsAllowedForRepoItem = $this->getAllAllowedChannelsForRepoItem($repoItem);

        // Validate that all provided channels in the request body exist and are valid for this particular repoitem,
        // meaning they are currently included in the template of the provided repoitem
        if ($channelUuids) {
            /** @var DataList<MetaField> $requestedChannelMetaFields */
            $requestedChannelMetaFields = $channelMetaFieldsAllowedForRepoItem->filter(["Uuid" => $channelUuids]);
            $requestedChannelMetaFieldUuids = $requestedChannelMetaFields->column("Uuid");

            if($invalidChannels = array_diff($channelUuids, $requestedChannelMetaFieldUuids)) {
                $channelString = implode(", ", $invalidChannels);
                throw new NotFoundException(ApiErrorConstant::GA_NF_002, "The following channels do not exist or are not allowed for this RepoItem: ( $channelString )");
            }

            return $requestedChannelMetaFields;
        } else {
            return MetaField::get()->filter(["ID" => 0]);
        }
    }
}