<?php

namespace SilverStripe\buildtasks;

use DateTime;
use Ramsey\Uuid\Uuid;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Security\Security;
use SurfSharekit\Api\CSVJsonApiController;
use SurfSharekit\Models\ExportItem;

class GenerateExportTask extends BuildTask
{
    private string $taskID;
    public function __construct() {
        parent::__construct();

        $this->taskID = Uuid::uuid4()->toString();
    }

    public function run($request) {
        set_time_limit(0);
        $this->failOldExportItems();

        if ($exportItem = $this->reserveExportItem()) {
            $exportItem->update([
                'Status' => 'IN PROGRESS',
                'StartedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            ])->write();

            try {
                $args = json_decode($exportItem->Args, true);

                $args['ExportItem'] = $exportItem;

                Security::setCurrentUser($exportItem->Person);
                CSVJsonApiController::create()
                    ->setRequest((new HTTPRequest('GET', Environment::getEnv('SS_BASE_URL') . "/api/v1/csv/repoItems", $args, []))
                        ->setRouteParams([
                            'Action' => 'repoItems'
                        ])
                    )
                    ->generateExport();
                Security::setCurrentUser(null);

                $exportItem->update([
                    'Status' => 'FINISHED',
                    'FinishedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
                ])->write();
            } catch (\Exception $e) {
                $exportItem->update([
                    'Status' => 'FAILED',
                    'FailedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'FailReason' => $e->getMessage()
                ])->write();
            }
        }
    }

    private function reserveExportItem(): ?ExportItem {
        $tableName = ExportItem::config()->get('table_name');

        DB::prepared_query("
            UPDATE $tableName SET TaskID = ?
            WHERE TaskID IS NULL
            ORDER BY Created ASC
            LIMIT 1
        ", [$this->getTaskID()]);

        return ExportItem::get()->find('TaskID', $this->getTaskID());
    }

    public function getTaskID(): string {
        return $this->taskID;
    }

    private function failOldExportItems() {
        $tableName = ExportItem::config()->get('table_name');

        SQLUpdate::create($tableName)
            ->addWhere([
                "Created <= ?" => (new \DateTime())->modify('-1 day')->format('Y-m-d H:i:s'),
                "Status IN ('PENDING', 'IN PROGRESS')"
            ])
            ->addAssignments([
                '"FailedAt"' => (new DateTime())->format('Y-m-d H:i:s'),
                '"Status"' => 'FAILED',
            ])->execute();
    }

}