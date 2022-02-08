<?php

namespace SurfSharekit\Models;

use Exception;
use SilverStripe\Assets\File;
use SilverStripe\Core\Environment;
use SilverStripe\Security\Security;

class RepoItemFile extends File {

    private static $table_name = 'SurfSharekit_RepoItemFile';

    private static $belongs_to = [
        'RepoItemMetaFieldValue' => RepoItemMetaFieldValue::class
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

    public function canView($member = null) {
        try {
            if ($this->isPublic()) {
                return true;
            } else if ($member = Security::getCurrentUser()) {
                /** @var RepoItem $repoItem */
                $repoItem = $this->RepoItem();
                if(!is_null($repoItem)) {
                    return $repoItem->canView();
                } else {
                    // detached file, so only owner can access
                    if($this->OwnerID == $member->ID){
                        return true;
                    }
                }
            }
        } catch (Exception $e) {
        }
        return false;
    }

    public function getTitle() {
        return $this->RepoItemMetaFieldValue()->RepoItemMetaField()->RepoItem()->Title;
    }

    public function RepoItem() {
        if($this->ID > 0) {
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

        $repoItemsConnectedToThisFile = RepoItem::get()
            ->innerJoin('SurfSharekit_RepoItemMetaField', 'SurfSharekit_RepoItemMetaField.RepoItemID = SurfSharekit_RepoItem.ID')
            ->innerJoin('SurfSharekit_RepoItemMetaFieldValue', 'SurfSharekit_RepoItemMetaFieldValue.RepoItemMetaFieldID = SurfSharekit_RepoItemMetaField.ID')
            ->where(["SurfSharekit_RepoItemMetaFieldValue.IsRemoved" => 0, "SurfSharekit_RepoItemMetaFieldValue.RepoItemFileID" => $this->ID]);

        $checkIfRepoItemOrParentIsPublic = function ($repoItem) use (&$checkIfRepoItemOrParentIsPublic) {
            if (!$repoItem) {
                return false;
            }
            if ($repoItem->IsPublic) {
                return true;
            }

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
}
