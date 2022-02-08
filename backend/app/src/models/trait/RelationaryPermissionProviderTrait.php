<?php

use SilverStripe\Security\Group;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\ScopeCache;

/**
 * Trait RelationaryPermissionProviderTrait
 * This trait makes it possible to check the relation of a SilversTripe Member before checking the corresponding relation
 * e.g. 'Allow Authors of RepoItems to Publish' would be a permission given or not given to a Student-Role or Group
 * The resulting permission code would be REPOITEM_AUTHOR_PUBLISH
 * Before checking said permission, isAuthor($member, $context = []) will be automatically called to check if the relation is applicable
 * If the permission is applicable, it will be checked if the user has the code from their group or role.
 */
trait RelationaryPermissionProviderTrait {
    use PermissionProviderTrait;

    //Creating permissions
    //e.g:
    //ObjectName = "RepoItem"
    //MemberRelationKey = "COAUTHOR"
    //MemberRelationDescription = 'when member is a coathour'
    //Actions = ['CREATE', 'Publish' => 'Publiceren']

    /**
     * @param string $relationName
     * @param string $actionKey
     * @param $member
     * @param array $context
     * @return bool if the user is $relationName to this DataObject and has the checked permission
     * @throws Exception
     */
    function checkRelationPermission(string $relationName, string $actionKey, $member, $context = []): bool {
        return $this->checkRelationPermissionForObjectName($this->getPermissionObjectName(), $relationName, $actionKey, $member, $context);
    }

    /**
     * @param string $objectPermissionName
     * @param string $relationName
     * @param string $actionKey
     * @param $member
     * @param array $context
     * @return bool
     * @throws Exception
     * This method checks whether the member has the requested permission for this object.
     * If they do, this method then checks whether or not the $member is related to this object by called $this->>isRelationName (relationName with 'is' in front)
     * Always returns TRUE for admin
     */
    function checkRelationPermissionForObjectName(string $objectPermissionName, string $relationName, string $actionKey, $member, $context = []): bool {
        if ($member == null) {
            $member = Security::getCurrentUser();
        }
        if ($member == null) {
            return false;
        }
        if ($member->isDefaultAdmin()) {
            return true;
        }

        $relationName = strtolower($relationName);
        if (is_string($relationName) && method_exists($this->dataObj(), 'is' . ucfirst($relationName))) {
            $memberIsRelationCheckMethod = 'is' . ucfirst($relationName);
            if ($this->dataObj()->$memberIsRelationCheckMethod($member, $context)) {
                return self::checkPermission($this->getPermissionCode($objectPermissionName, $relationName, $actionKey), 'any', $member, $context);
            }
        } else if (is_string($relationName) && method_exists($this, 'is' . ucfirst($relationName))) {
            $memberIsRelationCheckMethod = 'is' . ucfirst($relationName);
            if ($this->$memberIsRelationCheckMethod($member, $context)) {
                return self::checkPermission($this->getPermissionCode($objectPermissionName, $relationName, $actionKey), 'any', $member, $context);
            }
        } else {
            throw new Exception("Missing is" . ucfirst($relationName) . " in " . $this->dataObj()->ClassName);
        }

        return false;
    }

    /***
     * @param string $objectName (e.g. RepoItem)
     * @param string $memberRelationKey (e.g. Author)
     * @param string $memberRelationDescription (e.g. 'their own RepoItem')
     * @param array $actions (['View'])
     * @return array (e.g. ['REPOITEM_AUTHOR_VIEW' => 'View their own RepoItem'])
     * Utility method to provide permissions based on a relation of $member to a DataObject instance of the implementing class.
     */
    private function provideRelationaryPermissionsForObjectName(string $objectName, string $memberRelationKey = "", string $memberRelationDescription = "", array $actions = []) {
        $permissions = [];
        foreach ($actions as $actionKey => $actionDescription) {
            if (is_int($actionKey)) { //allows you to add ['PUBLISH'] or '['PUBLISH CO'=> 'Publish as coauthor']
                $actionKey = $actionDescription;
            }

            $actionDescription = ucfirst(strtolower($actionDescription));
            $permissions[$this->getPermissionCode($objectName, $memberRelationKey, $actionKey)] = [
                'name' => "$actionDescription $memberRelationDescription",
                'category' => $objectName
            ];
        }

        return $permissions;
    }

    /**
     * @param string $objectName
     * @param string $relationKey
     * @param string $actionKey
     * @return string
     * Utility method to generate a permission code for a DataObject, relation of the member to that object and the action
     */
    function getPermissionCode(string $objectName, string $relationKey, string $actionKey) {
        $nameuc = strtoupper($objectName);
        $actionKey = strtoupper($actionKey);
        if ($relationKey) {
            $relationKey = strtoupper($relationKey);
            return "${nameuc}_${actionKey}_$relationKey";
        } else {
            return "${nameuc}_${actionKey}";
        }
    }

    /**
     * @return string
     * Method to cut of namespaces of DataObjects and possibly allow individual Object-level permissions
     */
    function getPermissionObjectName() {
        try {
            $reflect = new ReflectionClass($this->dataObj());
        } catch (ReflectionException $e) {
            return $e->getMessage();
        }
        return $reflect->getShortName();
    }

    /***
     * @param string $memberRelationKey
     * @param string $memberRelationDescription
     * @param array $actions
     * @return array
     */
    private function provideRelationaryPermissions(string $memberRelationKey = "", string $memberRelationDescription = "", array $actions = []) {
        return $this->provideRelationaryPermissionsForObjectName($this->getPermissionObjectName(), $memberRelationKey, $memberRelationDescription, $actions);
    }

    /**
     * @param $code
     * @param string $arg
     * @param null $member
     * @param array $context
     * Gateway to Permission::check, allows for filters such as permissions based on group context
     */
    private function checkPermission($code, $arg = "any", $member = null, $context = []) {
        if (isset($context[Group::class]) && $group = $context[Group::class]) {
            $instituteOfScope = $this->dataObj()->getRelatedInstitute();
            $scopeLevel = InstituteScoper::getScopeLevel($instituteOfScope->ID, $group->InstituteID);
            if ($scopeLevel == InstituteScoper::HIGHER_LEVEL || $scopeLevel == InstituteScoper::SAME_LEVEL) {
                $permissionsCodesInGroup = ScopeCache::getPermissionsFromCache($group);
                return in_array($code, $permissionsCodesInGroup);
            } else {
                return false;
            }
        }
        return Permission::check($code, $arg, $member);
    }

    static function getPermissionCases() {
        return [];
    }
}
