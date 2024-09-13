<?php

namespace SurfSharekit\Tasks;

use DateTime;
use Exception;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\ValidationException;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\LogItem;
use SurfSharekit\models\notifications\NotificationSetting;
use SurfSharekit\models\notifications\NotificationVersion;
use SurfSharekit\Models\PersonConfig;

class SynchronizePersonConfigNotificationVersion extends BuildTask {

    protected $title = 'Synchronize all Person Configs with the latest NotificationVersion';
    protected $description = 'Task that auto-enables notification settings based on new notifications / notification types';

    /** @var NotificationVersion|null $latestNotificationVersion */
    private $latestNotificationVersion = null;

    public function run($request) {
        set_time_limit(0);

        /** @var NotificationVersion $latestNotificationVersion */
        $this->latestNotificationVersion = NotificationVersion::get()->sort("VersionCode DESC")->first();
        if ($this->latestNotificationVersion) {
            $personConfigsToSynchronize = PersonConfig::get()->filter(["NotificationVersion:LessThan" => $this->latestNotificationVersion->VersionCode]);
            /** @var PersonConfig $personConfig */
            foreach ($personConfigsToSynchronize as $personConfig) {
                try {
                    $this->synchronizePersonConfig($personConfig);
                } catch (Exception $e) {
                    Logger::debugLog($e->getMessage());
                    LogItem::errorLog("Something went wrong while synchronizing notification version of person " . $personConfig->Person()->Uuid);
                }
            }
        }

        // Update NotificationVersions
        $notificationVersionsToUpdate = NotificationVersion::get()->filter(["IsSynchronized" => false]);
        /** @var NotificationVersion $notificationVersion */
        foreach ($notificationVersionsToUpdate as $notificationVersion) {
            try {
                $notificationVersion->IsSynchronized = true;
                $notificationVersion->SynchronizationDatetime = (new DateTime())->getTimestamp();
                $notificationVersion->write();
            } catch (Exception $e) {
                Logger::debugLog($e->getMessage());
                LogItem::errorLog("Something went wrong while synchronizing NotificationVersion $notificationVersion->Uuid");
            }
        }
    }

    /**
     * @param PersonConfig $personConfig
     * @return void
     * @throws ValidationException
     */
    private function synchronizePersonConfig(PersonConfig $personConfig) {
        $configNotificationVersion = $personConfig->NotificationVersion;
        $versionsToSynchronize = range(($configNotificationVersion + 1), $this->latestNotificationVersion->VersionCode);

        if ($versionsToSynchronize) {
            // NotificationSettings to enable (VersionCode of these settings is higher than the version specified on PersonConfig)
            $notificationSettings = NotificationSetting::get()->filter(["NotificationVersion.VersionCode" => $versionsToSynchronize])->column("Key");
            if ($notificationSettings) {
                $currentlyEnabledNotifications = $personConfig->EnabledNotifications;
                $currentlyEnabledNotifications = array_merge($currentlyEnabledNotifications, $notificationSettings);
                $currentlyEnabledNotifications = array_unique($currentlyEnabledNotifications);
                $personConfig->EnabledNotifications = json_encode($currentlyEnabledNotifications);
                $personConfig->NotificationVersion = $this->latestNotificationVersion->VersionCode;
                $personConfig->write();
            }
        }
    }
}