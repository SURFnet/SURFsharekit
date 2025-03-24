<?php

namespace SilverStripe\buildtasks;

use Aws\Result;
use Aws\S3\S3Client;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SurfSharekit\Models\ReportFile;

class DeleteReportFilesTask extends BuildTask
{
    protected $title = "Delete ReportFiles + S3 Objects";
    protected $description = "Deletes ReportFiles + S3 Objects";

    private bool $dryRun = true;

    private S3Client $s3Client;

    public function __construct() {
        parent::__construct();

        $this->s3Client = Injector::inst()->create(S3Client::class);
    }

    public function run($request) {
        if ($request->getVar('dryRun') !== null) {
            $this->dryRun = !!$request->getVar('dryRun');
        }

        $this->print("Dry run: " . ($this->isDryRunEnabled() ? "enabled" : "disabled"));
        $this->print("---");

        $this->handleNextObjects();
    }

    private function handleNextObjects($offset = 0) {
        $reportFiles = ReportFile::get()->limit(100, $offset);

        foreach ($reportFiles as $reportFile) {
            $this->removeS3Object($reportFile);
        }

        if ($reportFiles->count()) {
            $this->handleNextObjects($offset + 100);
        }
    }

    private function removeS3Object(ReportFile $reportFile) {
        $this->execute(function () use ($reportFile) {
            $this->deleteS3Object("public/{$reportFile->Name}");

            $reportFile->delete();
        }, "Deleting: public/{$reportFile->Name}");
    }

    private function deleteS3Object(string $key): Result {
        return $this->s3Client->deleteObject([
            'Bucket' => Environment::getEnv('AWS_BUCKET_NAME'),
            'Key' => $key
        ]);
    }

    private function execute(\Closure $closure, $log) {
        if (!$this->isDryRunEnabled()) {
            $closure();
        }

        $this->print($log);
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