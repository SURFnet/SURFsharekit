<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\Queries\SQLDelete;

class ClearLog extends BuildTask
{
    protected $title = "Clear log";
    protected $description = "Clears log table";
    protected $enabled = true;
    public function run($request) {
        $where = [];

        if (null !== $type = $request->getVar('type')) {
            $where['"Type"'] = $type;
        }

        // retention in days
        if (null !== $retention = $request->getVar('retention')) {
            $date = (new \DateTime())->modify("-$retention days");
            $where['"Created" > ?'] = $date->format('Y-m-d H:i:s');
        }

        SQLDelete::create('SurfSharekit_Log', $where)->execute();
    }
}