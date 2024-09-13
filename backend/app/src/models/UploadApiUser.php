<?php

namespace SilverStripe\models;

use SilverStripe\ORM\DataList;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SurfSharekit\constants\RoleConstant;
use SurfSharekit\Models\UploadApiClient;

class UploadApiUser extends Member {

    private static $singular_name = 'Upload API user';
    private static $plural_name = 'Upload API users';
    private static $table_name = 'SurfSharekit_UploadApiUser';

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->fieldByName("Root.Main")->FieldList()->changeFieldOrder([
            "Uuid",
            "FirstName",
            "SurnamePrefix",
            "Surname",
        ]);

        $fields->removeByName([
            "SramCode",
            "ConextCode",
            "ConextRoles",
            "IsRemoved",
            "ApiToken",
            "ApiTokenAcc",
            "ApiTokenExpires",
            "Email",
            "OAuthSource",
            "FailedLoginCount",
            "Locale",
            "Password",
            "Passports"
        ]);

        $groupsField = $fields->dataFieldByName("DirectGroups");
        $fields->replaceField("DirectGroups", $groupsField->performReadonlyTransformation());

        if ($this->isInDB()) {
            $fields->removeByName([
                "ChangePassword",
            ]);
        } else {
            $fields->removeByName([
                "Identifier",
                "ConfirmPassword",
            ]);
        }

        return $fields;
    }

    public function removeUploadApiGroupsByUploadApiClient(int $uploadApiClientId) {
        $groupsToRemove = $this->getUploadApiGroupsForUploadApiClient($uploadApiClientId);
        /** @var Group $group */
        foreach ($groupsToRemove as $group) {
            $group->Members()->remove($this);
        }
    }

    public function addUploadApiGroupsByUploadApiClient(int $uploadApiClientId) {
        $groups = Group::get()
            ->innerJoin("PermissionRole", "PermissionRole.ID = Group.DefaultRoleID")
            ->innerJoin("SurfSharekit_Institute", "Institute.ID = Group.InstituteID", "Institute")
            ->innerJoin("SurfSharekit_UploadApiClientConfig", "UploadApiClientConfig.InstituteID = Institute.ID", "UploadApiClientConfig")
            ->innerJoin("SurfSharekit_UploadApiClient", "UploadApiClient.ID = UploadApiClientConfig.UploadApiClientID", "UploadApiClient")
            ->where([
                "UploadApiClient.ID" => $uploadApiClientId,
                "PermissionRole.Key" => RoleConstant::UPLOAD_API_USER
            ]);

        /** @var Group $group */
        foreach ($groups as $group) {
            $group->Members()->add($this);
        }
    }

    public function getUploadApiGroupsForUploadApiClient(int $uploadApiClientID): DataList {
        return Group::get()
            ->innerJoin("PermissionRole", "PermissionRole.ID = Group.DefaultRoleID")
            ->innerJoin("SurfSharekit_Institute", "Institute.ID = Group.InstituteID", "Institute")
            ->innerJoin("SurfSharekit_UploadApiClientConfig", "UploadApiConfig.InstituteID = Institute.ID", "UploadApiConfig")
            ->innerJoin("SurfSharekit_UploadApiClient", "UploadApiConfig.UploadApiClientID = UploadApiClient.ID", "UploadApiClient")
            ->where([
                "UploadApiClient.ID" => $uploadApiClientID,
                "PermissionRole.Key" => RoleConstant::UPLOAD_API_USER
            ]);
    }

}