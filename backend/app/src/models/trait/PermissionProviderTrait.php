<?php

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Models\DefaultMetaFieldOptionPart;
use SurfSharekit\Models\Institute;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemMetaField;
use SurfSharekit\Models\RepoItemMetaFieldValue;
use SurfSharekit\Models\Template;
use SurfSharekit\Models\TemplateMetaField;

/***
 * Trait PermissionProviderTrait
 * A utility Trait that can be used to automatically check if the current user canView/canEdit/canDelete/etc. a given DataObject based on the scope of the member
 * This trait must be used in combination with SilverStripe PermissionProvider to automatically scope Permissions for a given member
 */
trait PermissionProviderTrait {
    public function canView($member = null, $context = []) {
        if ($this->dataObj()->isBoundToInsituteScope() && !$this->dataObj()->memberIsOfHigherOrSameLevelAs($member, $this->dataObj()->getRelatedInstitute())) {
            return false;
        }
        $name = strtoupper($this->dataObj()->ClassName);
        return Permission::check("${name}_VIEW") || ($this instanceof DataObject && parent::canView($member, $context));
    }

    /**
     * @return bool if this DataObject has a relatedInsitute
     */
    public function isBoundToInsituteScope(): bool {
        return $this->dataObj()->getRelatedInstitute() != null;
    }

    /**
     * @return mixed|PermissionProviderTrait|null
     * Method used to retrieve the related Institute of the DataObject if it is scoped to one
     */
    public function getRelatedInstitute() {
        $obj = $this->dataObj();

        if ($obj instanceof Institute) {
            return $obj;
        } else if ($obj instanceof Template) {
            return $obj->Institute;
        } else if ($obj instanceof TemplateMetaField) {
            return $obj->Template->Institute;
        } else if ($obj instanceof DefaultMetaFieldOptionPart) {
            return $obj->TemplateMetaField->Template->Institute;
        } else if ($obj instanceof RepoItem) {
            return $obj->Institute;
        } else if ($obj instanceof RepoItemMetaField) {
            return $obj->RepoItem->Institute;
        } else if ($obj instanceof RepoItemMetaFieldValue) {
            return $obj->RepoItemMetaField->RepoItem->Template->Institute;
        } else if ($obj instanceof Group) {
            return $obj->Institute;
        } else if ($obj instanceof Person) {
            return $obj->extend('getInstituteIdentifiers')[0];
        }
        return null;
    }

    /**
     * @return $this
     * Method used to ensure this trait can be used in extension classes as well
     */
    public function dataObj() {
        return $this;
    }

    /**
     * @param $member
     * @param Institute $institutes
     * @return bool if $member is on the same scope or a higher one than $institute
     */
    public function memberIsOfHigherOrSameLevelAs($member, $institutes): bool {
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if (Permission::check('Admin')) {
            return true;
        }
        if (InstituteScoper::getAll(Institute::class)->filter('ID', is_array($institutes) ? $institutes : $institutes->ID)->count() > 0) {
            return true;
        }
        return false;
    }

    public function canEdit($member = null, $context = []) {
        if ($this->dataObj()->isBoundToInsituteScope() && !$this->dataObj()->memberIsOfHigherOrSameLevelAs($member, $this->dataObj()->getRelatedInstitute())) {
            return false;
        }
        $name = strtoupper($this->dataObj()->ClassName);
        return Permission::check("${name}_EDIT") || ($this instanceof DataObject && parent::canView($member, $context));
    }

    public function canDelete($member = null, $context = []) {
        if ($this->dataObj()->isBoundToInsituteScope() && !$this->dataObj()->memberIsOfHigherOrSameLevelAs($member, $this->dataObj()->getRelatedInstitute())) {
            return false;
        }
        $name = strtoupper($this->dataObj()->ClassName);
        return Permission::check("${name}_DELETE") || ($this instanceof DataObject && parent::canView($member, $context));
    }

    public function canCreate($member = null, $context = []) {
        if ($this->dataObj()->isBoundToInsituteScope() && !$this->dataObj()->memberIsOfHigherOrSameLevelAs($member, $this->dataObj()->getRelatedInstitute())) {
            return false;
        }

        $name = strtoupper($this->dataObj()->ClassName);
        return Permission::check("${name}_CREATE") || Permission::check('ADMIN') || ($this instanceof DataObject && parent::canView($member, $context));
    }

    /**
     * @return array
     * Automatically generates model level permissions
     */
    public function providePermissions() {
        $name = strtoupper($this->dataObj()->ClassName);
        $namelc = strtolower($name);
        return [
            "${name}_VIEW" => "View $namelc Items",
            "${name}_EDIT" => "Edit $namelc Items",
            "${name}_DELETE" => "Delete $namelc Items",
            "${name}_CREATE" => "Create $namelc Items"
        ];
    }
}
