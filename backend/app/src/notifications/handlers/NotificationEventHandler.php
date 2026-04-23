<?php

use SilverStripe\Core\Environment;
use SurfSharekit\Models\Event;
use SurfSharekit\Api\InstituteScoper;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Person;
use SurfSharekit\Models\RepoItem;

abstract class NotificationEventHandler {

    private static $instances = array();

    public static function getInstance() {
        $class = get_called_class();
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    abstract public function process(Event $event);

    protected function createDashboardURL(): string {
        return Environment::getEnv("FRONTEND_BASE_URL") . '/dashboard';
    }

    protected function createProfileURL(): string {
        return Environment::getEnv("FRONTEND_BASE_URL") . '/profile';
    }

    /**
     * Collects all recipients who can publish the given repo item type within the institute scope.
     */
    protected function getAllPersonsToMail(RepoItem $repoItem, ?string $emailToExclude = null): array {
        $clauses = [];
        foreach (Constants::ALL_REPOTYPES as $type) {
            $typeUpper = strtoupper($type);
            $clauses[] = "(SurfSharekit_RepoItem.RepoType = '$type' AND (Permission.Code = 'REPOITEM_PUBLISH_$typeUpper' OR PermissionRoleCode.Code = 'REPOITEM_PUBLISH_$typeUpper'))";
        }

        $personsToMail = Person::get()
            ->innerJoin('Group_Members', 'Group_Members.MemberID = SurfSharekit_Person.ID')
            ->innerJoin('Group', 'Group_Members.GroupID = Group.ID')
            ->innerJoin('(' . InstituteScoper::getInstitutesOfUpperScope([$repoItem->InstituteID])->sql() . ')', 'gi.ID = Group.InstituteID', 'gi')
            //get parents of groups
            ->leftJoin('Group_Roles', 'Group_Roles.GroupID = Group.ID')
            //join on permissions
            ->leftJoin('PermissionRoleCode', 'PermissionRoleCode.RoleID = Group_Roles.PermissionRoleID')
            ->leftJoin('Permission', 'Permission.GroupID = Group_Roles.GroupID')
            ->innerJoin('SurfSharekit_RepoItem', "SurfSharekit_RepoItem.ID = $repoItem->ID")
            ->whereAny($clauses)
            ->leftJoin('SurfSharekit_PersonConfig', 'SurfSharekit_Person.PersonConfigID = SurfSharekit_PersonConfig.ID');

        // Always exclude empty/null addresses; optionally exclude a specific owner email
        $emailsToExclude = ['', null];
        if ($emailToExclude) {
            $emailsToExclude[] = $emailToExclude;
        }

        return $personsToMail
            ->filter('Email:not', $emailsToExclude)
            ->columnUnique('Email');
    }
}
