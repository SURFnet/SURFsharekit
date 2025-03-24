<?php

namespace SurfSharekit\Tasks;

use Ramsey\Uuid\Uuid;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SurfSharekit\Models\StatsDownload;
use SurfSharekit\Piwik\CustomEventDimension;
use SurfSharekit\Piwik\Tracker\PiwikTracker;

class MigrateStatsDownloadsToPiwikTask extends BuildTask
{
    private bool $dryRun = true;
    private ?string $from = null;
    private ?string $to = null;

    public function run($request) {
        set_time_limit(0);

        if ($request->getVar('dryRun') !== null) {
            $this->dryRun = !!$request->getVar('dryRun');
        }

        if ($request->getVar('from') !== null) {
            $this->from = $request->getVar('from');
        }

        if ($request->getVar('to') !== null) {
            $this->to = $request->getVar('to');
        }

        $this->print("Dry run: " . ($this->isDryRunEnabled() ? "enabled" : "disabled"));
        $this->print("---");

        $this->createMigrationTable();

        $this->doMigrate($this->from, $this->to);
    }

    private function doMigrate(?string $from, ?string $to, $offset = 0) {
        $where = [
            "TmpStatsDownloadsMigration.ID IS NULL"
        ];

        if ($from) {
            $where["DATE(DownloadDate) >= ?"] = $from;
        }

        if ($to) {
            $where["DATE(DownloadDate) <= ?"] = $to;
        }

        $downloads = StatsDownload::get()
            ->leftJoin("TmpStatsDownloadsMigration", "TmpStatsDownloadsMigration.ID = SurfSharekit_StatsDownload.ID")
            ->where($where)
            ->limit(1000, $offset);

        foreach ($downloads as $download) {
            if (!$download->RepoItem->exists() || !$download->RepoItemFile->exists()) {
                $this->print("$download->ID - migration failed: RepoItem or RepoItemFile does not exist");
                continue;
            }

            try {
                $this->execute(function () use ($download) {
                    $this->registerPiwikEvent($download);
                    $this->registerMigration($download->ID);
                }, "$download->ID - migrated");
            } catch (\Exception $e) {
                $this->print("$download->ID - migration failed: {$e->getMessage()}");
            }
        }

        if ($downloads->count()) {
            $this->doMigrate($from, $to,$offset + 1000);
        }
    }

    private function createMigrationTable() {
        DB::prepared_query("CREATE TABLE IF NOT EXISTS TmpStatsDownloadsMigration(
            ID int primary key unique NOT NULL,
            MigratedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )", []);
    }

    private function registerPiwikEvent(StatsDownload $download) {
        $baseUrl = str_replace("http://", "https://", Director::absoluteBaseURL());
        $repoItemFile = $download->RepoItemFile;

        $url = Controller::join_links(
            $baseUrl,
            "/api/v1/files/repoItemFiles/",
            $repoItemFile->Uuid
        );

        PiwikTracker::trackEvent(
            $url,
            "Downloads",
            "download",
            "download",
            [
                CustomEventDimension::REPO_ITEM_ID => $download->RepoItem->Uuid, // repo_item_id
                CustomEventDimension::ROOT_INSTITUTE_ID => $download->RepoItem->Institute->RootInstitute->Uuid, // root_institute_id
                CustomEventDimension::REPO_ITEM_FILE_ID => $download->RepoItemFile->Uuid, // repo_item_file_id
                CustomEventDimension::REPO_TYPE => $download->RepoItem->RepoType, // repo_type
            ],
            true,
            new \DateTime($download->DownloadDate),
            true
        );
    }

    private function registerMigration($id) {
        DB::prepared_query("INSERT INTO TmpStatsDownloadsMigration (ID, MigratedAt) VALUES ($id, NOW())", []);
    }

    /**
     * Only execute closure if dryRun is false
     *
     * @param \Closure $closure
     * @param $log
     * @return void
     */
    private function execute(\Closure $closure, $log = null) {
        if (!$this->isDryRunEnabled()) {
            $closure();
        }

        if ($log) {
            $this->print($log);
        }
    }

    private function isDryRunEnabled(): bool {
        return $this->dryRun;
    }

    private function print($message) {
        if (Director::is_cli()) {
            echo $message;
        } else {
            echo "<span>$message</span><br>";
        }
    }
}