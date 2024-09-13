<?php
namespace SurfSharekit\Tasks;

use League\Flysystem\Exception;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Member;
use SurfSharekit\Models\Helper\Logger;

class RemoveTokensTask extends BuildTask {

    protected $title = 'Remove token task';
    protected $description = 'Task to remove all access tokens from all users';

    protected $enabled = true;

    function run($request)
    {
        // Get all users with a conextcode
        $users = Member::get()->filter(['ConextCode:not' => null]);

        // Remove ApiToken and ApiTokenAcc for every user in SURFsharekit
        foreach ($users as $user) {
            if ($user->ApiToken !== null || $user->ApiTokenAcc !== null ||  $user->ApiTokenExpires !== null) {
                $user->ApiToken = null;
                $user->ApiTokenAcc = null;
                $user->ApiTokenExpires = null;
                $user->write();
            }
        }

        Logger::debugLog('Task successfully executed');
    }
}