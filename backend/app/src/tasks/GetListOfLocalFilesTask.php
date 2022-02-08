<?php

namespace SurfSharekit\Tasks;

use Aws\Credentials\Credentials;

use Exception;

use League\Flysystem\Filesystem;
use SilverStripe\Assets\File;
use SilverStripe\Assets\FilenameParsing\ParsedFileID;
use SilverStripe\Assets\Flysystem\ProtectedAdapter;
use SilverStripe\Assets\Flysystem\PublicAdapter;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SurfSharekit\Models\RepoItemFile;

class GetListOfLocalFilesTask extends BuildTask {

    protected $title = 'Get List of local files';
    protected $description = 'This task checks for every file if they exist on amazon storage or not';

    /**
     * @var $public Filesystem
     */
    private $public;

    /**
     * @var $protected Filesystem
     */
    private $protected;
    private $publicHelpers;
    private $protectedHelpers;

    protected $enabled = true;

    function run($request) {
        $this->start = microtime(true);
        $this->time_elapsed_secs = 0;

        set_time_limit(0);
        $credentials = new Credentials(Environment::getEnv('AWS_ACCESS_KEY_ID'), Environment::getEnv('AWS_SECRET_ACCESS_KEY'), null);
        //echo 'Params that can be used: offset, limit, id, titleLike <br/>';

        $id = $request->getVar('id');
        $offset = $request->getVar('offset');
        $limit = $request->getVar('limit');
        $titleLike = $request->getVar('titleLike');
        $list = RepoItemFile::get()->sort('ID');
        if ($limit) {
            $list = $list->limit($limit ?: 0, $offset);
        } else if ($offset) {
            $list->offsetGet($offset);
        }
        if ($id) {
            $list = $list->filter(['ID' => $id]);
        }
        if ($titleLike) {
            $list = $list->filter('Title:PartialMatch', "%$titleLike%");
        }

        /***
         * @var $flySystemAssetStore AssetStore
         */

        $flySystemAssetStore = Injector::inst()->get(AssetStore::class);

        // Check with filesystem this asset exists in


        $this->public = $flySystemAssetStore->getPublicFilesystem();
        $this->protected = $flySystemAssetStore->getProtectedFilesystem();

        $this->publicHelpers = $flySystemAssetStore->getPublicResolutionStrategy()->getResolutionFileIDHelpers();
        $this->protectedHelpers = $flySystemAssetStore->getProtectedResolutionStrategy()->getResolutionFileIDHelpers();

        echo "Absolute Link;ID;Uuid;Status;URL\n";
        foreach ($list as $file) {
            /**
             * @var RepoItemFile $file
             */
            try {
                $result = static::fileExists($file, $credentials);
                if (is_null($result)) {
                    echo '"' . $file->Name . "\";$file->ID;$file->Uuid;Missing in s3;\n";
                }
                else {
                    echo '"' . $file->Name . "\";$file->ID;$file->Uuid;Found;\"$result\"\n";
                }
            } catch (Exception $e) {
                echo '"' . $file->Name . "\";$file->ID;$file->Uuid;Call error;\n";
            }
            unset($file);
        }
        $this->saveTime();
        if ($this->time_elapsed_secs) {
        //    echo "<br/><br/><br/><br/><br/> Time elapsed: $this->time_elapsed_secs";
        }

    }

    public function getAsURL(File $file) {

        $filename = $file->getFilename();
        $hash = $file->getHash();
        $variant = $file->getVariant();
        $tuple = new ParsedFileID($filename, $hash, $variant);


        $fileID = null;
        try {
            $fileID = $this->publicHelpers[1]->buildFileID($tuple, null, null, false);
        } catch (\Exception $e) {
        }
        /***
         * @var $parsedFileID ParsedFileID
         */
        if ($fileID && $this->public->has($fileID)) {
            /** @var PublicAdapter $publicAdapter */
            $publicAdapter = $this->public->getAdapter();
            return $publicAdapter->getPublicUrl($fileID);
        }

        $fileID = null;
        try {
            $fileID = $this->protectedHelpers[0]->buildFileID($tuple, null, null, false);
        } catch (\Exception $e) {
        }

        if ($fileID && $this->protected->has($fileID)) {
            /** @var ProtectedAdapter $protectedAdapter */
            $protectedAdapter = $this->protected->getAdapter();
            return $protectedAdapter->getProtectedUrl($fileID);
        }

        return null;
    }

    private function fileExists(File $file, $credentials) {
        $s3Link = $this->getAsURL($file);
        return $s3Link;
    }

    private function saveTime() {
        $this->time_elapsed_secs = microtime(true) - $this->start;
    }
}