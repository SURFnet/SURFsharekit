<?php

namespace SurfSharekit\Models;

use Exception;
use RelationaryPermissionProviderTrait;
use SilverStripe\Assets\File;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SurfSharekit\Api\FileJsonApiController;
use SurfSharekit\Api\PermissionFilter;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\Helper\MimetypeHelper;

class RepoItemFile extends File implements PermissionProvider {
    use RelationaryPermissionProviderTrait;

    private static $table_name = 'SurfSharekit_RepoItemFile';

    private static $belongs_to = [
        'RepoItemMetaFieldValue' => RepoItemMetaFieldValue::class
    ];

    private static $db = [
        'Link' => 'Varchar(255)',
        'S3Key' => 'Varchar(255)',
        'ETag' => 'Varchar(255)',
        'ObjectStoreCheckedAt' => 'Datetime'
    ];

    const CLOSED_ACCESS = "closedaccess";
    const RESTRICTED_ACCESS = "restrictedaccess";
    const OPEN_ACCESS = "openaccess";
    const EMBARGOED_ACCESS = "EmbargoedAccess";

    const ERROR_UNAUTHORIZED_NOT_PUBLIC = 'FJAC_401';
    const ERROR_AUTHORIZED_NOT_PUBLIC = [
        'embargo' => 'FJAC_403_E',
        'restricted' => 'FJAC_403_R',
        'closed' => 'FJAC_403_C',
    ];

    public function onAfterWrite() {
        parent::onAfterWrite();
        $this->updateRelevantRepoItems();
    }

    public function getStreamURL() {
        return Environment::getEnv('SS_BASE_URL') . '/api/v1/files/repoItemFiles/' . $this->Uuid;
    }

    public function getPublicStreamURL() {
        return Environment::getEnv('FRONTEND_BASE_URL') . '/objectstore/' . $this->Uuid;
    }

    public function shouldUseRedirect() {
        return (bool)$this->Link;
    }

    public function getAccessRight() {
        $repoItem = $this->RepoItemRepoItemFile();
        if ($repoItem && $repoItem->exists()) {
            return $repoItem->AccessRight;
        }
        return null;
    }

    public function canView($member = null) {
        try {
            /** @var RepoItem $repoItem */
            if ($this->isPublic()) {
                return true;
            } else if ($member = Security::getCurrentUser()) {
                if (!is_null($repoItem = $this->RepoItem())) {
                    // A member has to be able to access the parent RepoItem OR has to be a member of one of the institutes from the multiselectinstitute field
                    $canViewParentRepoItem = $repoItem->canView();
                    $allowedInstitutes = $this->getAllAllowedInstitutesWhereMemberIsPartOf($member);
                    if ($canViewParentRepoItem || count($allowedInstitutes)) {
                        $repoItemRepoItemFile = $this->RepoItemRepoItemFile();
                        $accessRight = $repoItemRepoItemFile->AccessRight;

                        // first check if owner
                        if ($repoItemRepoItemFile->OwnerID === $member->ID) {
                            // Owner is always allowed to view his own files
                            return true;
                        }

                        // first check if RepoItemRepoItemFile has no embargo status
                        if ($repoItemRepoItemFile->Status !== "Embargo") {
                            if ($accessRight === self::OPEN_ACCESS) {
                                return true;
                            }

                            $allScopedGroups = [];
                            foreach ($allowedInstitutes as $allowedInstitute) {
                                $scopedGroups = $member->ScopedGroups($allowedInstitute);
                                $scopedGroups = $scopedGroups->toArray();
                                $allScopedGroups = array_merge($scopedGroups, $allScopedGroups);
                            }

                            if ($accessRight === self::CLOSED_ACCESS) {
                                foreach ($allScopedGroups as $group) {
                                    $permissionsCodesInGroup = ScopeCache::getPermissionsFromRequestCache($group);
                                    if (in_array("VIEW_CLOSED_FILE", $permissionsCodesInGroup)) {
                                        return true;
                                    }
                                }
                                FileJsonApiController::setErrorCode(self::ERROR_AUTHORIZED_NOT_PUBLIC['closed']);
                                return false;
                            }

                            if ($accessRight === self::RESTRICTED_ACCESS) {
                                foreach ($allScopedGroups as $group) {
                                    $permissionsCodesInGroup = ScopeCache::getPermissionsFromRequestCache($group);
                                    if (in_array("VIEW_RESTRICTED_FILE", $permissionsCodesInGroup)) {
                                        return true;
                                    }
                                }
                                FileJsonApiController::setErrorCode(self::ERROR_AUTHORIZED_NOT_PUBLIC['restricted']);
                                return false;
                            }
                        } else {

                            $allScopedGroups = [];
                            foreach ($allowedInstitutes as $allowedInstitute) {
                                $scopedGroups = $member->ScopedGroups($allowedInstitute)->toArray();
                                $allScopedGroups = array_merge($scopedGroups, $allScopedGroups);
                            }

                            // Only return true if member can view repoitems under embargo
                            foreach ($allScopedGroups as $group) {
                                $permissionsCodesInGroup = ScopeCache::getPermissionsFromRequestCache($group);
                                if (in_array("REPOITEM_VIEW_EMBARGO", $permissionsCodesInGroup)) {
                                    return true;
                                }
                            
                                FileJsonApiController::setErrorCode(self::ERROR_AUTHORIZED_NOT_PUBLIC['embargo']);
                                return false;
                            }
                        }
                    }
                } else {
                    // detached file, so only owner can access
                    if ($this->OwnerID == $member->ID) {
                        return true;
                    }
                }
            } else {
                FileJsonApiController::setErrorCode(self::ERROR_UNAUTHORIZED_NOT_PUBLIC);
            }
        } catch (Exception $exception) {
            return false;
        }
        return false;

    }

    public function getTitle() {
        return $this->RepoItemMetaFieldValue()->RepoItemMetaField()->RepoItem()->Title;
    }

    /**
     * @return DataObject|null
     */
    public function RepoItemRepoItemFile() {
        return RepoItem::get()
            ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaField.RepoItemID = SurfSharekit_RepoItem.ID')
            ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID')
            ->where(["SurfSharekit_RepoItemMetaFieldValue.IsRemoved" => 0, "SurfSharekit_RepoItemMetaFieldValue.RepoItemFileID" => $this->ID])->first();
    }

    public function RepoItem() {
        if ($this->ID > 0) {
            $repoItemsConnectedToThisFile = RepoItem::get()
                ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaField.RepoItemID = SurfSharekit_RepoItem.ID')
                ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID')
                ->where(["SurfSharekit_RepoItemMetaFieldValue.IsRemoved" => 0, "SurfSharekit_RepoItemMetaFieldValue.RepoItemFileID" => $this->ID])->first();
            if ($repoItemsConnectedToThisFile && $repoItemsConnectedToThisFile->exists()) {
                $topRepoItem = RepoItem::get()
                    ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaField.RepoItemID = SurfSharekit_RepoItem.ID')
                    ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID')
                    ->where(["SurfSharekit_RepoItemMetaFieldValue.IsRemoved" => 0, "SurfSharekit_RepoItemMetaFieldValue.RepoItemID" => $repoItemsConnectedToThisFile->ID])->first();

                if ($topRepoItem && $topRepoItem->exists()) {
                    return $topRepoItem;
                }
            }
        }
        return null;
    }

    public function isPublic() {
        if (!$this->exists()) {
            return false;
        }

        // All RepoItemRepoItemFiles
        $repoItemsConnectedToThisFile = RepoItem::get()
            ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaField.RepoItemID = SurfSharekit_RepoItem.ID')
            ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID')
            ->where(["SurfSharekit_RepoItemMetaFieldValue.IsRemoved" => 0, "SurfSharekit_RepoItemMetaFieldValue.RepoItemFileID" => $this->ID]);

        // check if RepoItemRepoItemFile has embargo status
        foreach ($repoItemsConnectedToThisFile as $repoItemRepoItemFile) {
            /** @var RepoItem $repoItemRepoItemFile */
            if ($repoItemRepoItemFile->Status === "Embargo") {
                return false;
            }

            if ($repoItemRepoItemFile->AccessRight === self::CLOSED_ACCESS) {
                return false;
            }

            if ($repoItemRepoItemFile->AccessRight === self::RESTRICTED_ACCESS) {
                return false;
            }
        }

        $checkIfRepoItemOrParentIsPublic = function ($repoItem) use (&$checkIfRepoItemOrParentIsPublic) {
            if (!$repoItem) {
                return false;
            }
            if ($repoItem->IsPublic) {
                return true;
            }

            // root RepoItem
            $databaseParents = RepoItem::get()
                ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaField.RepoItemID = SurfSharekit_RepoItem.ID')
                ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID')
                ->where(["SurfSharekit_RepoItemMetaFieldValue.IsRemoved" => 0, "SurfSharekit_RepoItemMetaFieldValue.RepoItemID" => $repoItem->ID]);

            foreach ($databaseParents as $parent) {
                if ($parent->IsPublic) {
                    return true;
                }
            }
            return false;
        };

        foreach ($repoItemsConnectedToThisFile as $repoItem) {
            if ($checkIfRepoItemOrParentIsPublic($repoItem)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function isMemberPartOfAllowedInstitutes(Member $member): bool {
        $allowedInstitutes = $this->getAllAllowedInstitutesWhereMemberIsPartOf($member);
        if (count($allowedInstitutes)) {
            return true;
        }

        return false;
    }

    /**
     * @return array returns an array of Institute IDs
     */
    public function getAllAllowedInstitutesWhereMemberIsPartOf(Member $member): array {
        /** @var RepoItem $repoItemRepoItemFile */
        $repoItemRepoItemFile = $this->RepoItemRepoItemFile();
        if (!$repoItemRepoItemFile) {
            return [];
        }
//        Logger::debugLog($member->ID);

        $instituteIDs = $repoItemRepoItemFile->getAllRepoItemMetaFieldValues()
            ->innerJoin("SurfSharekit_MetaField", "mf.ID = rimf.MetaFieldID", "mf")
            ->where(["mf.AttributeKey" => "AllowedForInstitute"])
            ->column("SurfSharekit_RepoItemMetaFieldValue.InstituteID");
//        Logger::debugLog($instituteIDs);
        if (!$instituteIDs) {
            return [];
        }

        $memberInstituteIDs = $member->extend('getInstituteIdentifiers')[0];
//        Logger::debugLog($memberInstituteIDs);
        return array_intersect($instituteIDs, $memberInstituteIDs);
    }

    /**
     * Update the attributes of repoItems that make use of this object as an attribute via the attributeKey system
     */
    private function updateRelevantRepoItems() {
        //implied not the first time writing this object
        if (!$this->isChanged('ID') && $this->isChanged('Name')) {
            RepoItem::updateAttributeBasedOnMetafield($this->Name, "RepoItemFileID = $this->ID");
        }
    }

    function getLoggedInUserPermissions() {
        $loggedInMember = Security::getCurrentUser();
        return [
            'canView' => $this->canView($loggedInMember)
        ];
    }

    public function getRedirectLink() {
        $s3Client = Injector::inst()->create('Aws\\S3\\S3Client');

        //just get file path in bucket

        $bucketKey = [
            'Bucket' => Environment::getEnv("AWS_BUCKET_NAME"),
            'Key' => $this->S3Key
        ];

        $cmd = $s3Client->getCommand('GetObject', $bucketKey);

        $request = $s3Client->createPresignedRequest($cmd, '+1 hour');
        return $request->getUri();
    }

    public function getMimeType() {
        $mimeType = parent::getMimeType();
        if(is_null($mimeType)){
            if(!is_null($S3Key = $this->S3Key)){
                $ext = pathinfo($S3Key, PATHINFO_EXTENSION);
                $mimeType = MimetypeHelper::getMimeType($S3Key, null);

                if (strtolower($ext) === 'ino') {
                    return 'application/octet-stream';
                }

            }
        }
        return $mimeType;
    }

    public function exists() {
        return parent::exists() || ($this->isInDB() && $this->Link); //hosted via silverstripe or directly with just a link reference in s3 storage
    }

    public function providePermissions() {
        return [
            'VIEW_CLOSED_FILE' => [
                'name' => 'Can view closed-access RepoItemFile',
                'category' => 'RepoItemFile',
            ],
            'VIEW_RESTRICTED_FILE' => [
                'name' => 'Can view restricted access RepoItemFile',
                'category' => 'RepoItemFile',
            ]
        ];
    }
}
